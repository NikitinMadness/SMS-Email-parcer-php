<!DOCTYPE <!DOCTYPE html>
<html>
<link rel="stylesheet" href="style1.css">
   <div class="main_1">
<?php
include_once("change_num.php");
include_once("config_sms.php");

// Создание соединения
$conn = new mysqli($servername, $username, $password, $dbname);
// version 23_01_2019_v5
// Проверка соединения
if ($conn->connect_error) {
   die("Ошибка подключения: " . $conn->connect_error);
}

// Выбрать данные
$sql = "SELECT dtSms, CallerID, SMS FROM sms  WHERE  (dtSms BETWEEN \"$_POST[datefrom] 00:00:00\" AND \"$_POST[dateto] 23:59:59\")";
$result = $conn->query($sql);
$data = [];
$data_bad = [];
if ($result->num_rows > 0) {
   // Выводим данные каждой строк
   $i = 0;
   while ($row = $result->fetch_assoc()) {
      $delimiter = '[\*\.\,\s\#]+';
      if (preg_match("/$delimiter(\d{1})$delimiter(\d{9})$delimiter(\d{1,5})/", $row["SMS"], $subject)) {
         $ls = check_num($subject[2]);
         if ($ls == true){
            $d = $row['dtSms'];
            $date = substr("$d", 0, 10);
            $a = strtotime($date);
            $date_good = date('d.m.Y', $a);
            $data[$i]["dtSms"] = $date_good;
            $data[$i]["CallerID"] = $row["CallerID"];
            $LS = str_replace(""," ", $ls);
            $data[$i]["Ls"] = $LS;
            $data[$i]["Cnt"] = $subject[3];
            $i++;
         }else{
            $data_bad[$i]["dtSms"] = $date;
            $data_bad[$i]["CallerID"] = $row["CallerID"];
            $data_bad[$i]["SMS"] = $row["SMS"];
         }
       //echo "dtSms: " . $date_good . " CallerID: " . $row["CallerID"] . " SMS " . $subject[2] . " " . $subject[3] . "</br>";
    
      } else {
       $data_bad[$i]["dtSms"] = $date;
       $data_bad[$i]["CallerID"] = $row["CallerID"];
       $data_bad[$i]["SMS"] = $row["SMS"];
      }
     }
   
     }  else {
   echo "<div class='d_3'>ничего не выбрано</div>";

// Закрыть подключение
} 

$conn->close();

$def = array(
   array("DATA",     "C", 10),
   array("PHONE",    "C", 20),
   array("LS",       "N", 20, 0),
   array("CNT",      "N", 11, 0)
 );
 $t=date("j_m_y_U");
 //echo $t;
 // создаем
 if (!dbase_create("./sms/sms_$t.dbf", $def)) {
   echo "Ошибка, не получается создать базу данных\n";
 }


$pr = dbase_open("./sms/sms_$t.dbf", 2);
if ($pr){
   foreach($data as $u){
      $v = [ $u["dtSms"],
      $u["CallerID"],
      $u["Ls"],
      $u["Cnt"]];
      //print_r($u);
      dbase_add_record ($pr, $v);
   }  
}
dbase_close($pr);

//bad
$def = array(
   array("Date",  "C", 11),
   array("Phone", "C", 20),
   array("SMS",   "C", 70)
 );

 // создаем
 if (!dbase_create("./sms/bad_sms_$t.dbf", $def)) {
   echo "Ошибка, не получается создать базу данных\n";
 }

$pr = dbase_open("./sms/bad_sms_$t.dbf", 2);
if ($pr){
   foreach($data_bad as $o){
      $l = [ $o["dtSms"],
      $o["CallerID"],
      $o["SMS"]];
      dbase_add_record ($pr, $l);
   }
}
dbase_close($pr);
echo "<div class='ttx'>Результат выборки</div>";
echo "<div class='l_1'><a href=\"./sms/sms_$t.dbf\" download><button class='but_1'>Файл для импорта в Gasolina</button></a></div>";
echo "<br/>";
echo "<div class='l_2'><a href=\"./sms/bad_sms_$t.dbf\" download><button class='but_2'>Файл для ручной обработки</button></a></div>";
echo "<br/>"


//запись в бд если будет нужно
/*

$conn3 = new mysqli($servername, $username, $password, $dbname3);
// Проверка соединения
if ($conn3->connect_error) {
    die("Ошибка подключения: " . $conn3->connect_error);
 }
 

 foreach ($data as $k){
      $sql2 = "INSERT INTO test.successtable2 VALUES ('id','$k[dtSms]', '$k[CallerID]', '$k[Ls]', $k[Cnt])";
      //echo $sql2 . "</br>";
      $conn3->query($sql2);
     if ($conn3->query($sql2)) {
         echo "создана новая запись";
      } else {
         echo "Ошибка: " . $sql2 . "<br>" . $conn3->error;
 }
 }
$conn3->close();
*/
//
?>
<br/>
<div class="bb_3">
<a href="smsform.php"><button class="b_11">Назад</button></a>
</div>
</div>
</body>
</html>