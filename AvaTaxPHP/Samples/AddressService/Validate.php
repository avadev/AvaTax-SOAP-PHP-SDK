<?php

require('../../AvaTax4PHP/AvaTax.php');	// include in all Avalara Scripts
require('../Credentials.php');	// where service URL, account, license key are set

$client = new AddressServiceSoap('Development');
$STDIN = fopen('php://stdin', 'r');
 
try
{
	$address = new Address();

// Get The address from the user via keyboard input
	echo "Address Line 1: ";
	$input = rtrim(fgets($STDIN));
	$address->setLine1($input);

	echo "Address Line 2: ";
	$input = rtrim(fgets($STDIN));
	$address->setLine2($input);

	echo "Address Line 3: ";
	$input = rtrim(fgets($STDIN));
	$address->setLine3($input);

	echo "City: ";
	$input = rtrim(fgets($STDIN));
	$address->setCity($input);

	echo "State/Province: ";
	$input = rtrim(fgets($STDIN));
	$address->setRegion($input);

	echo "Postal Code: ";
	$input = rtrim(fgets($STDIN));
	$address->setPostalCode($input);

	$textCase = TextCase::$Mixed;
	$coordinates = 1;
	
	$request = new ValidateRequest($address, ($textCase ? $textCase : TextCase::$Default), $coordinates);
	$result = $client->Validate($request);
	
	echo "\n".'Validate ResultCode is: '. $result->getResultCode()."\n";
	if($result->getResultCode() != SeverityLevel::$Success)
	{
		foreach($result->getMessages() as $msg)
		{
			echo $msg->getName().": ".$msg->getSummary()."\n";
		}
	}
	else
	{
		echo "Normalized Address:\n";
	   	foreach($result->getvalidAddresses() as $valid)
    		{
        		echo "Line 1: ".$valid->getline1()."\n";
		        echo "Line 2: ".$valid->getline2()."\n";
		        echo "Line 3: ".$valid->getline3()."\n";
		        echo "Line 4: ".$valid->getline4()."\n";
		        echo "City: ".$valid->getcity()."\n";
		        echo "Region: ".$valid->getregion()."\n";
		        echo "Postal Code: ".$valid->getpostalCode()."\n";
		        echo "Country: ".$valid->getcountry()."\n";
		        echo "County: ".$valid->getcounty()."\n";
		        echo "FIPS Code: ".$valid->getfipsCode()."\n";
		        echo "PostNet: ".$valid->getpostNet()."\n";
		        echo "Carrier Route: ".$valid->getcarrierRoute()."\n";
		        echo "Address Type: ".$valid->getaddressType()."\n";
		        if($coordinates == 1)
		        {
		             echo "Latitude: ".$valid->getlatitude()."\n";
		             echo "Longitude: ".$valid->getlongitude()."\n";
		        }
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
