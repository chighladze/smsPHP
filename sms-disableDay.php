<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=jarvisdb', 'local', '~!q1w2Local~!');
    $query = $db->query('
SELECT t2.aID                 as aID,
       t2.payID               as payID,
       t2.balance             as balance,
       t2.netTariffPrice      as netTariffPrice,
       t2.tvTariffPrice       as tvTariffPrice,
       t2.servicePrice        as servicePrice,
       t2.mobile              as mobile,
       t2.companyID           as companyID,
       t2.balanceTomorrow     as balanceTomorrow,
       t2.balanceAfterTwoDays as balanceAfterTwoDays,
       t2.passiveDate         as passiveDate
FROM (SELECT t1.aID                                                        as aID,
             t1.payID                                                      as payID,
             t1.balance                                                    as balance,
             t1.netTariffPrice                                             as netTariffPrice,
             t1.tvTariffPrice                                              as tvTariffPrice,
             t1.servicePrice                                               as servicePrice,
             t1.mobile                                                     as mobile,
             t1.companyID                                                  as companyID,
             (t1.balance - ((t1.netTariffPrice + t1.servicePrice + t1.tvTariffPrice) /
                            DAYOFMONTH(LAST_DAY(NOW() + INTERVAL 1 DAY)))) as balanceTomorrow,
             t1.balance -
             (((t1.netTariffPrice + t1.servicePrice + t1.tvTariffPrice) / DAYOFMONTH(LAST_DAY(NOW() + INTERVAL 1 DAY)))
                 +
              ((t1.netTariffPrice + t1.servicePrice + t1.tvTariffPrice) / DAYOFMONTH(LAST_DAY(NOW() + INTERVAL 2 DAY))))
                                                                           as balanceAfterTwoDays,
             DATE_FORMAT(NOW() + INTERVAL 2 DAY, "%d/%m/%Y")                                      as passiveDate
      FROM (SELECT na.id                   as aID,
                   na.payID                as payID,
                   na.balance              as balance,
                   nt.price                as netTariffPrice,
                   (SELECT IFNULL(SUM(ntt.price), 0) as tvTariffPrice
                    FROM ns_accounts_tv natv
                             LEFT JOIN ns_tv_tariffs ntt ON natv.tariffID = ntt.id
                    WHERE natv.aID = na.id
                      AND natv.status = 0) as tvTariffPrice,
                   (ROUND((SELECT IFNULL(sum(nast.price), 0)
                           FROM ns_accounts_services_type nast
                                    INNER JOIN ns_accounts_services nas ON nas.typeID = nast.id
                           WHERE nas.aID = na.id
                             AND nast.disabled = 0),
                          2))              AS servicePrice,
                   na.mobile               as mobile,
                   nc.smsCompanyID         as companyID
            FROM ns_accounts na
                     LEFT JOIN ns_tariffs nt ON na.tariffID = nt.id
                     LEFT JOIN ns_company nc ON na.companyID = nc.id
            WHERE na.statusID = 1
              AND na.mobile IS NOT NULL
              AND LENGTH(na.mobile) = 9
              AND LEFT(na.mobile, 1) = "5"
              AND na.refund IS NULL
              AND na.balance > 0
              AND nt.price > 0
              AND nc.smsCompanyID IS NOT NULL) as t1) as t2
WHERE t2.balanceTomorrow > 0
  AND t2.balanceAfterTwoDays < 0'
    );
    $db->beginTransaction();

    foreach ($query as $row) {
        $aID = $row['aID'];
        $payID = $row['payID'];
        $balance = $row['balance'];
        $netTariffPrice = $row['netTariffPrice'];
        $tvTariffPrice = $row['tvTariffPrice'];
        $servicePrice = $row['servicePrice'];
        $mobile = $row['mobile'];
        $smsCompanyID = $row['companyID'];
        $balanceTomorrow = $row['balanceTomorrow'];
        $balanceAfterTwoDays = $row['balanceAfterTwoDays'];
        $passiveDate = $row['passiveDate'];

        $price = $netTariffPrice + $tvTariffPrice + $servicePrice;
        if ($tvTariffPrice > 0) {
            $message = "შეგახსენებთ, რომ ინტერნეტ+ტვ სერვისი ($payID) აქტიურია  $passiveDate-მდე. გთხოვთ დაფაროთ სააბონენტო თანხა $price ლარი, იმისთვის რომ არ მოხდეს  მომსახურების შეზღუდვა.";
        } else {
            $message = "შეგახსენებთ, რომ ინტერნეტ სერვისი ($payID) აქტიურია  $passiveDate-მდე. გთხოვთ დაფაროთ სააბონენტო თანხა $price ლარი, იმისთვის რომ არ მოხდეს  მომსახურების შეზღუდვა.";
        }
        $db->prepare("INSERT INTO ns_sms (aID, sign, smsCompanyID, `number`, message, sended) VALUES('" . $aID . "', 'disableDay', '" . $smsCompanyID . "', '" . $mobile . "', '" . $message . "', 0);")->execute();
    }

    $db->commit();
    $db = null;
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>

