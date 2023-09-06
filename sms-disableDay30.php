<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=jarvisdb', 'local', '~!q1w2Local~!');
    $query = $db->query('
SELECT na.id                     as aID,
       na.payID                  as payID,
       na.balance                as balance,
       nt.price                  as netTariffPrice,
       nt.minPay                 as netTariffMinPay,
       (SELECT IFNULL(SUM(ntt.price), 0) as tvTariffPrice
        FROM ns_accounts_tv natv
                 LEFT JOIN ns_tv_tariffs ntt ON natv.tariffID = ntt.id
        WHERE natv.aID = na.id
          AND natv.status = 0)   as tvTariffPrice,
       (ROUND((SELECT IFNULL(sum(nast.price), 0)
               FROM ns_accounts_services_type nast
                        INNER JOIN ns_accounts_services nas ON nas.typeID = nast.id
               WHERE nas.aID = na.id
                 AND nast.disabled = 0),
              2))                AS servicePrice,
       na.mobile                 as mobile,
       nc.smsCompanyID           as smsCompanyID
FROM ns_accounts na
         INNER JOIN ns_users nu ON na.uID = nu.id
         INNER JOIN ns_tariffs nt ON na.tariffID = nt.id
         INNER JOIN ns_company nc ON na.companyID = nc.id
WHERE na.statusID = 0
  AND nu.typeID = 1
  AND na.mobile IS NOT NULL
  AND LENGTH(na.mobile) = 9
  AND LEFT(na.mobile, 1) = "5"
  AND na.refund IS NULL
  AND na.balance < 0
  AND nt.price > 0
  AND nc.smsCompanyID IS NOT NULL
  AND -DATEDIFF(na.disDate, CURDATE()) = 30');
    $db->beginTransaction();
    foreach ($query as $row) {
        $accountID = $row['aID'];
        $accountBalance = $row['balance'];
        $payID = $row['payID'];
        $smsCompanyID = $row['smsCompanyID'];
        $number = $row['mobile'];
        $netTariffPrice = floor($row['netTariffPrice']);
        $netTariffMinPay = $row['netTariffMinPay'];
        $tvTariffPrice = $row['tvTariffPrice'];
        $servicePrice = $row['servicePrice'];

        $fullPay = $netTariffPrice + $tvTariffPrice + $servicePrice;
        $debtPayment = round($accountBalance - $netTariffMinPay - $tvTariffPrice, 2);
        $debt = abs($debtPayment);

        if ($tvTariffPrice > 0) {
            $message = "შეგახსენებთ, ინტერნეტ+ტვ სერვისი ($payID) გათიშულია გადაუხდელობის გამო. მომსახურების გასააქტიურებლად, გთხოვთ გადაიხადოთ სააბონენტო თანხა ($fullPay) ლარი სწრაფი ჩარიცხვის აპარატის ან ინტერნეტ ბანკინგის მეშვეობით.";
        } else {
            $message = "შეგახსენებთ, ინტერნეტ სერვისი ($payID) გათიშულია გადაუხდელობის გამო. მომსახურების გასააქტიურებლად, გთხოვთ გადაიხადოთ სააბონენტო თანხა ($fullPay) ლარი სწრაფი ჩარიცხვის აპარატის ან ინტერნეტ ბანკინგის მეშვეობით.";
        }
        $db->prepare("INSERT INTO ns_sms (aID, sign, smsCompanyID, `number`, message, sended, adminID) VALUES('" . $accountID . "', 'passiveAccounts', '" . $smsCompanyID . "', '" . $number . "', '" . $message . "', 0, 1);")->execute();
    }

    $db->commit();
    $db = null;
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>
