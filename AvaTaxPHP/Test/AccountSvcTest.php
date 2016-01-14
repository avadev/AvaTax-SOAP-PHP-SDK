<?php
require_once('../simpletest/autorun.php');
require('../AvaTax4PHP/AvaTax.php');	// include in all Avalara Scripts
require('Credentials.php');	// where service URL, account, license key are set

class AccountSvcTest extends UnitTestCase
{
	private $client;
	
	public function __construct()
	{
		$this->client= new AccountServiceSoap("Development");
	}
	
	function testPing()
	{
		$result = $this->client->ping("");

		$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());
	}

	function testIsAuthorized()
	{
		$result = $this->client->isAuthorized("Ping,IsAuthorized");

		$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());
		$this->assertEqual("Ping,IsAuthorized", $result->getOperations());            
	}

	function testCompanies()
	{
		global $client;
		$result = $this->client->CompanyFetch("");

		$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());
		$validCompaniesObject = $result->getValidCompanies();
		
		$this->assertNotNull($validCompaniesObject[0]->CompanyCode);
		$this->assertNotNull($validCompaniesObject[0]->CompanyName);
	}
}
?>