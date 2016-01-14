<?php

require('../../AvaTax4PHP/AvaTax.php');	// include in all Avalara Scripts
require('../Credentials.php');	// where service URL, account, license key are set

$STDIN = fopen('php://stdin', 'r');
 

	$client = new TaxServiceSoap('Development');
	$request= new PostTaxRequest();
	$input = "bogus";

	// Locate Document by Invoice Number
	echo "Enter Invoice Number(Document Code): ";
	$input = rtrim(fgets($STDIN));
	$request->setDocCode($input);		
	$request->setDocDate($input);
	$request->setDocType('SalesInvoice');
	
	$request->setCompanyCode('DEFAULT');	//<Your Dashboard Company Code Here>');

	echo "Enter Total Invoice Amount: ";
	$input = rtrim(fgets($STDIN));
	$request->setTotalAmount($input);
	echo "Enter Total Invoice Tax: ";
	$input = rtrim(fgets($STDIN));
	$request->setTotalTax($input);
	echo "Enter Doc Date (yyyy-mm-dd): ";
    $input = rtrim(fgets($STDIN));
    $request->setDocDate($input);

	try
	{
		$result = $client->postTax($request);
		echo 'PostTax ResultCode is: '.$result->getResultCode()."\n";
		
		if ($result->getResultCode()!=SeverityLevel::$Success)
		{	
			foreach($result->getMessages() as $msg)
			{
				echo $msg->getName().": ".$msg->getSummary()."\n";
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
