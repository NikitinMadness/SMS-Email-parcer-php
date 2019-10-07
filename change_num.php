<?php


// if ($_POST['number'] == true){
//     $d = check_num($_POST['number']);
//     if($d == true){
//         echo $d;
//     }else{
//         echo $_POST['number']."bad";
//     }

// }

function check_num($data){
    $good_num = ['100', '111', '112', '110', '104', '105', '106', '107', '108', '109'];
    $bad_num = ['200', '201', '202', '203', '204', '205', '206', '207', '208', '209'];
    $num = (string)$data;
    if ($num[0] == 2){
        $d = str_split($num, 3);
        $key = array_search($d[0], $bad_num);
        if(is_int($key) == true){
            $d[0] = $good_num[$key];
            $r = implode("", $d);
            return $r;
        }
    }elseif($num[0] == 1){
        $d = str_split($num, 3);
        $key = array_search($d[0], $good_num);
        if(is_int($key) == true){
            return $num;
        }
    }
}
?>
