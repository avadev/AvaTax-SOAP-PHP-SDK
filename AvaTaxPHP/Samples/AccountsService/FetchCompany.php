<?php
require('../../AvaTax4PHP/AvaTax.php');	// include in all Avalara Scripts
require('../Credentials.php');	// where service URL, account, license key are set

$client = new AccountServiceSoap('Development');

try
{
	$result = $client->CompanyFetch("");
	echo 'Ping ResultCode is: '. $result->getResultCode()."\n";

	$message = "";
	if($result->getResultCode() != SeverityLevel::$Success)
	{
		$message .= "Error - AvaTax Account Service Message\n";
		
		foreach($result->getMessages() as $msg)
		{
			//$message .= $msg->getName().": ".$msg->getSummary()."<br/>";
			$message .= $msg->getSummary();
		}	
		$response["msg"]=$message;
		$response["address"]="";
	}
	else if($result->getResultCode() == SeverityLevel::$Success && $result->getValidCompanies() != "")
	{
		$arr=array();
		$validCompanies=array();
		$validCompanies=$result->getValidCompanies();
		$message .= "<table border='1'><tr><td>Company Code</td><td>Company Name</td></tr>";
		foreach ($validCompanies as $obj) {
			$message .= "<tr><td>".$obj->CompanyCode."</td><td>".$obj->CompanyName."</td></tr>";
		}
		echo "</table>";
		//$message .= json_encode($arr);
	}
	echo "<br>Result: ".$message;
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
