<!-- ver 21.01.2019 
Depends:
	Files
 		parser.php
 		change_num.php
		config_mail.php
	System
		PHP PECL

-->
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="style1.css">
	<title>Mail</title>
</head>
<body>
	<div class="R16_1"> 
<?php
include_once('parser.php');
include_once("change_num.php");
include_once("config_mail.php");
if ($_POST["datefrom"] == true && $_POST["dateto"] == true){
	//данные даты
	$dateTo  = date_format(DateTime::createFromFormat("Y-m-d", $_POST["dateto"]), 'j M Y');
	$dateFrom = date_format(DateTime::createFromFormat("Y-m-d", $_POST["datefrom"]), 'j M Y');
	$betweenDates = 'SINCE "'.$dateFrom.'" BEFORE "'.$dateTo.'"';
	$onDate = 'ON "'.$dateFrom.'"';
	if($dateFrom ==$dateTo){
		$betweenDates = $onDate;
	}
	$email = new Imap_parser();
	$data ['email']['betweenDates'] = $betweenDates;
	$result = $email->inbox($data);
	$DAT = [];
	$DAT_bad = [];
	$i = 0;
	if($result){
	// перебираем все письма
		foreach($result['inbox'] as $row){
			//Преобразуем дату
			$date_good = correctDate($row['date']);
			//Проверка на размер письма
			if ((int)$row['size'] < 50000){
			//Преобразуем данные
				$info = extractInfo($row['subject'], 'subject');
				if($info){
					$ls = check_num((int)$info[2]);
					if ($ls == true){
					//Запись хороших данных
						$DAT[$i] = [
							'email'=>$row['email'],
							'date'=>$date_good,
							'LS'=>(int)$ls,
							'CNT'=>(int)$info[3],
							'message' => $row['message'],
							'Size'=> (int)$row['size'],
							'subject' => $row['subject']
						];
					}else{
						$DAT_bad[$i] = [
							'email'=>$row['email'],
							'date'=>$date_good,
							'subject'=>$row['subject'],
							'message' => $row['message'],
							'Size'=> (int)$row['size']
						];
					}
				}else{
					$info = extractInfo($row['message'], 'message');
					if ($info){
						$ls = check_num((int)$info[2]);
						if ($ls == true){
							$DAT[$i] = [
								'email'=>$row['email'],
								'date'=>$date_good,
								'LS'=>(int)$ls,
								'CNT'=>(int)$info[3],
								'message' => $row['message'],
								'Size'=> (int)$row['size'],
								'subject' => $row['subject']
							];
						}else{
							$DAT_bad[$i] = [
								'email'=>$row['email'],
								'date'=>$date_good,
								'subject'=>$row['subject'],
								'message' => $row['message'],
								'Size'=> (int)$row['size']
							];
						}
					}else{
						$DAT_bad[$i] = [
							'email'=>$row['email'],
							'date'=>$date_good,
							'subject'=>$row['subject'],
							'message' => $row['message'],
							'Size'=> (int)$row['size']
						];
					}
				}
			}
			$i++;
		}
	}else{
		echo "<div class='R_1'>Ничего не выбрано</div>";
	}
	echo "<div class='R_21'>".$result['status']."</div>";
	// var_dump($DAT);
	// echo "BAD <br>";
	// var_dump($DAT_bad);
	createDBF($DAT, $DAT_bad);
}

function extractInfo($data, $param){
	if ($param == 'subject'){
		$s = explode("*", $data);
		$LS = (string)$s[2];
		if (count($s) >= 5 && strlen($LS) == 9 && $s[3] != ""){
			return $s;
		}
	}elseif($param == 'message'){
		$delimiter = '[\*\.\,\s]+';
		if (preg_match("/$delimiter(\d{1})$delimiter(\d{9})$delimiter(\d{1,5})$delimiter/", $data, $message)){
			$s = explode("*", $data);
			$LS = (string)$s[2];
			if (count($s) >= 5 && strlen($LS) == 9 && $s[3] != ""){
				if(strlen($s[3]) > 5){
					preg_match("/(\d{1,5})/", $s[3], $CNT);
					$s[3] = $CNT[0];
				}
				return $s;
			}else{
				return $false;
			}
		}
	}else{
		return false;
	}
}

function correctDate($dd){
	$a = strtotime($dd);
	$date_good = date('d.m.Y', $a);
	return $date_good;
}

function createDBF($DAT, $DAT_bad){
		// создаем email_good
	$def = array(
		array("DATA",     "C", 10),
		array("PHONE",    "C", 20),
		array("LS",       "N", 20, 0),
		array("CNT",      "N", 11, 0));
	$t=date("j_m_y_U");
	if (!dbase_create("./mail/email_$t.dbf", $def)) {
		echo "Ошибка, не получается создать базу данных\n";
	} 
	$pr = dbase_open("./mail/email_$t.dbf", 2);
	if ($pr){
		if(count($DAT) > 0){
			foreach ($DAT as $value){
				$v = [$value['date'], $value['email'], $value['LS'], $value['CNT']];
				dbase_add_record ($pr, $v);
			}
		}
	}
	dbase_close($pr);
	// создаем email_bad
	$def2 = array(
		array("Date",     "C", 11),
		array("Email",    "C", 40),
		array("Subject",  "C", 200),
		array("Message",  "C", 200));

	if (!dbase_create("./mail/email_bad_$t.dbf", $def2)) {
		echo "Ошибка, не получается создать базу данных\n";
	}	  
	$pr2 = dbase_open("./mail/email_bad_$t.dbf", 2);
	if ($pr2){
		if(count($DAT_bad) > 0){
			foreach ($DAT_bad as $value){
				$v = [$value['date'], $value['email'], $value['subject'],$value['message']];
				dbase_add_record ($pr2, $v);
			}
		}
	}
	dbase_close($pr2);
    // создаем urls
    
	echo "<div class='R_2'> <a href=\"./mail/email_$t.dbf\"><button>Файл для импорта в Gasolina</button></a></div>";
	echo "<br/>";
	echo "<div class='R_3'><a href=\"./mail/email_bad_$t.dbf\"><button>Файл для ручной обработки</button></a></div>";
}

?>
</div>
<div class="R_5"><a href="mailform.php"><button>Назад</button></a></div>
</body>
</html>