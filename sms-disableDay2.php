<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=jarvisdb', 'local', '~!q1w2Local~!');

    $query = $db->query('SELECT
	na.id,
	na.payID,
	na.balance,
	nt.price,
	na.mobile,
	nc.smsCompanyID,
	na.disDate,
	nt.minPay 
FROM
	ns_accounts na
INNER JOIN ns_tariffs nt ON
	na.tariffID = nt.id
INNER JOIN ns_company nc ON
	na.companyID = nc.id
WHERE
	na.statusID = 0
	AND na.mobile IS NOT NULL
    AND na.refund IS NULL
	AND na.balance < 0
	AND nt.price > 0
	AND nc.smsCompanyID IS NOT NULL
	AND na.disDate = DATE_SUB(CURDATE(), INTERVAL 2 DAY)');
    $db->beginTransaction();
    foreach ($query as $row) {
        $accountID = $row['id'];
        $accountBalance = $row['balance'];
        $payID = $row['payID'];
        $smsCompanyID = $row['smsCompanyID'];
        $number = $row['mobile'];
		$price = floor($row['price']);
        $minPay = $row['minPay'];
        $debtPayment = round($accountBalance - $minPay , 2);
        $debt = abs($debtPayment);

        $message = "შეგახსენებთ, ინტერნეტ სერვისი ($payID) გათიშულია გადაუხდელობის გამო. მომსახურების გასააქტიურებლად მინიმალური გადასახადი შეადგენს ($debt) ლარს. სააბონენტო თანხა - ($price) ლარი. გადახდა შეგიძლიათ სწრაფი ჩარიცხვის აპარატის ან ინტერნეტ ბანკის მეშვეობით.";
        $db->prepare("INSERT INTO ns_sms (aID, sign, smsCompanyID, `number`, message, sended) VALUES('" . $accountID . "', 'disableDay', '" . $smsCompanyID . "', '" . $number . "', '" . $message . "', 0);")->execute();
    }
    $db->commit();

    $db = null;
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}

?>
