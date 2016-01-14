<?php

require('../../AvaTax4PHP/AvaTax.php');	// include in all Avalara Scripts
require('../Credentials.php');	// where service URL, account, license key are set

$client = new AccountServiceSoap('Development');

try
{
	$result = $client->isAuthorized("GetTax");
	echo 'IsAuthorized ResultCode is: '. $result->getResultCode()."\n";
	if($result->getResultCode() != SeverityLevel::$Success)	// call failed
	{
		echo "isAuthorized(\"Validate\") failed\n";
		foreach($result->Messages() as $idx => $msg)
		{
			echo $msg->getName().": ".$msg->getSummary()."\n";
		}
	} 
	else 
	{
		echo "isAuthorized succeeded\n";
		echo 'Expiration: '. $result->getexpires()."\n\n";
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
