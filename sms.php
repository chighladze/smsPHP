<?php
//defined('_payments') or die('Error: restricted access');

function disDate($accountBalance = null, $tariffPrice = null, $setantaBillDate = null, $refundDate = null)
{
    $accountBalance = (float)$accountBalance;
    $tariffPrice = (float)$tariffPrice;
    $dateNow = date("Y-m-d");

    // setanta charge when setanta charge date is before refund date
    if ($refundDate != null) {
        while ($refundDate != $dateNow) {
            $dateNow = date('Y-m-d', strtotime('+1 day', strtotime($dateNow)));
            if($dateNow == $setantaBillDate){
                $accountBalance = $accountBalance - 5;
//                $lastDayOfTheMonth = date("t", strtotime($dateNow));
//                $accountBalance = $accountBalance - ($tariffPrice / $lastDayOfTheMonth);
            }
        }
        $dateNow = date('Y-m-d', strtotime('-1 day', strtotime($dateNow)));
    }

    // if customer pay between 00:00 - 02:00 hours
    $time = date("H:i:s");
    if($time >= "00:00:00" && $time <= "02:00:00"){
        $date = date('d-m-Y', strtotime('-1 day', strtotime($dateNow)));
    }

    // account daily charge
    while (!($accountBalance < 0)) {
        $nextDay = date('Y-m-d', strtotime('+1 day', strtotime($dateNow)));
        $dateNow = $nextDay;
        $lastDayOfTheMonth = date("t", strtotime($dateNow));
        $accountBalance = $accountBalance - ($tariffPrice / $lastDayOfTheMonth);
        if($dateNow == $setantaBillDate) {
            $accountBalance = $accountBalance - 5;
        }
    }

    return date('d-m-Y', strtotime($dateNow));
}
