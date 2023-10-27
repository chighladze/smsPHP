<?php
defined('_payments') or die('Error: restricted access');

function disDate($accountBalance, $tariffPrice, $refund=null)
{
    $accountBalance = (float)$accountBalance;
    $tariffPrice = (float)$tariffPrice;
    if ($refund != null){
        $date = date($refund);
    }else{
        $date = date("d-m-Y");
    }
    $time = date("H:i:s");
    if($time >= "00:00:00" && $time <= "02:00:00"){
        $date = date('d-m-Y', strtotime('-1 day', strtotime($date)));
    }

    while (!($accountBalance < 0)) {
        $nextDay = date('d-m-Y', strtotime('+1 day', strtotime($date)));
        echo $nextDay;
        $date = $nextDay;
        $lastDayOfTheMonth = date("t", strtotime($date));
        $accountBalance = $accountBalance - ($tariffPrice / $lastDayOfTheMonth);
    }
    return $date;
}
