<?php
	require('../../AvaTax4PHP/AvaTax.php');	// include in all Avalara Scripts
	require('../Credentials.php');	// where service URL, account, license key are set

	$STDIN = fopen('php://stdin', 'r');
 
	$client = new TaxServiceSoap('Development');
	$request= new CancelTaxRequest();

	
	// Locate Document by Invoice Number (Document Code)
	echo "Enter Invoice Number (Document Code): ";
	$input = rtrim(fgets($STDIN));
	$request->setDocCode($input);
	$request->setDocType('SalesInvoice');
	
	
	$request->setCompanyCode('DEFAULT');	// Dashboard Company Code
	$request->setDocType('SalesInvoice');

	$input = "bogus";

	while($input != "D" && $input != "P")
	{
		echo "CancelCode: Enter D for DocDeleted, or P for PostFailed: [D]";
		$input = strtoupper(rtrim(fgets($STDIN)));
		if($input == '') $input = 'D';
	}
	$code = CancelCode::$DocDeleted;
	if($input == 'P')
		$code = CancelCode::$PostFailed;

	$request->setCancelCode($code);
 
try
{

	$result = $client->cancelTax($request);
	echo 'CancelTax ResultCode is: '.$result->getResultCode()."\n";
	
	if ($result->getResultCode() != "Success")
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
