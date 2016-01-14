<?php
	require('../../AvaTax4PHP/AvaTax.php');	// include in all Avalara Scripts
	require('../Credentials.php');	// where service URL, account, license key are set

	$STDIN = fopen('php://stdin', 'r');
 
	$client = new TaxServiceSoap('Development');
	$request= new GetTaxHistoryRequest();

	$input = "bogus";	
	// Locate Document by Invoice Number
	echo "Enter Invoice Number (Document Code): ";
	$input = rtrim(fgets($STDIN));
	$request->setDocCode($input);		
	$request->setCompanyCode('DEFAULT');	// Dashboard Company Code
	$request->setDocType(DocumentType::$SalesInvoice);
	$request->setDetailLevel(DetailLevel::$Document);
	

	try
	{
		$result = $client->getTaxHistory($request);
		echo 'GetTaxHistory ResultCode is: '. $result->getResultCode()."\n";
		if($result->getResultCode() != SeverityLevel::$Success)
		{
			foreach($result->getMessages() as $msg)
			{
				echo $msg->getName().": ".$msg->getSummary()."\n";
			}
		} else {
			echo "Invoice Number: ".$result->getGetTaxRequest()->getDocCode();			
			echo "Current Status: ".$result->getGetTaxResult()->getDocStatus()."\n";
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
