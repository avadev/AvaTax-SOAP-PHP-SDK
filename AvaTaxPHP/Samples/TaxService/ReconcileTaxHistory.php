<?php
	require('../../AvaTax4PHP/AvaTax.php');	// include in all Avalara Scripts
	require('../Credentials.php');	// where service URL, account, license key are set

	$STDIN = fopen('php://stdin', 'r');
 
	$client = new TaxServiceSoap('Development');
	$request= new ReconcileTaxHistoryRequest();

	echo "Enter Start Date (yyyy-mm-dd):";
	$input = rtrim(fgets($STDIN));
	$request->setStartDate($input);

	echo "Enter End Date (yyyy-mm-dd):";
	$input = rtrim(fgets($STDIN));
	$request->setEndDate($input);
	
	$request->setCompanyCode('<Your Company Code Here>');	// Dashboard Company Code
	$request->setDocStatus(DocStatus::$Committed);
	$request->setLastDocCode("0");
	$request->setPageSize(1000);
	$request->setDocType(DocumentType::$SalesInvoice);
	

	try
	{
		$result = $client->reconcileTaxHistory($request);
		echo 'ReconcileTaxHistory ResultCode is: '. $result->getResultCode()."\n";
		if($result->getResultCode() != SeverityLevel::$Success)
		{
			foreach($result->getMessages() as $msg)
			{
				echo $msg->getName().": ".$msg->getSummary()."\n";
			}
		} else {
			echo "Committed Documents Dated Between ".$request->getStartDate()." and ".$request->getEndDate().":\n";
			foreach($result->getGetTaxResults() as $getRes)
			{
				echo "     Invoice Number: ".$getRes->getDocCode()." Invoice Amount: ".$getRes->getTotalAmount();
				echo "  Total Taxes on Invoice: ".$getRes->getTotalTax()."\n";
			}
		}
	
	}
	catch(SoapFault $exception)
	{
		$msg = "Exception: ";
		if($exception)
			$msg .= $exception->faultstring;
	
		echo $msg."\n";
		echo $client->__getLastRequest()."\n";
		echo $client->__getLastResponse()."\n";
	}

?>
