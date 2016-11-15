<?php
require_once('../simpletest/autorun.php');
require('../AvaTax4PHP/AvaTax.php');	// include in all Avalara Scripts
require('Credentials.php');	// where service URL, account, license key are set

class AddressSvcTest extends UnitTestCase
{
	private $client;
	
	public function __construct()
	{
		$this->client= new AddressServiceSoap("Development");
	}
	
	function testPing()
	{
		$result = $this->client->ping("");

		$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());
		
	}
	function testIsAuthorized()
	{
		$result = $this->client->isAuthorized("Ping,IsAuthorized,Validate");

		$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());
		$this->assertEqual("Ping,IsAuthorized,Validate", $result->getOperations());            
		
	}
	
	function testValidate()
	{
					
		$request = new ValidateRequest();
		$address = new Address();
		$address->setLine1("900 Winslow Way");
		$address->setLine2("Suite 130");
		$address->setCity("Bainbridge Is");
		$address->setRegion("WA");
		$address->setPostalCode("98110-2766");
		$request->setAddress($address);
		$request->setTextCase(TextCase::$Upper);
		$request->setTaxability(true);
			
		$result = $this->client->validate($request);

		$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());
		$validAddress = $result->getValidAddresses();
		$validAddress=$validAddress[0];
		$this->assertEqual("900 WINSLOW WAY E STE 130", $validAddress->getLine1());
		$this->assertEqual("BAINBRIDGE ISLAND WA 98110-2766", $validAddress->getLine4());
		$this->assertEqual("5303503736", $validAddress->getFipsCode());
		$this->assertEqual("KITSAP", $validAddress->getCounty());
		$this->assertEqual(true, $result->isTaxable());
	}
	function testInvalidAddress()
	{
		$request = new ValidateRequest();

		//No Address given
		$address = new Address();
		$request->setAddress($address);
		$request->setTextCase(TextCase::$Upper);
		
		$result = $this->client->validate($request);
		
		$this->assertEqual($result->getResultCode(), SeverityLevel::$Error);            
		$message=$result->getMessages();
		$message=$message[0];
		$this->assertEqual("InsufficientAddressError",$message->getName());
		
		//Invalid zip code
		$address->setLine1("900 Winslow Way");
		$address->setLine2("Suite 130");
		$address->setCity("Bainbridge Is");
		$address->setRegion("GA");
		$address->setPostalCode("00000");
		$request->setAddress($address);
		
		$result = $this->client->validate($request);
		
		$this->assertEqual($result->getResultCode(), SeverityLevel::$Error);
		$message=$result->getMessages();
		$message=$message[0];
		$this->assertEqual("CityError",$message->getName());
		
											
	}

	/*
	 * Tests address validation and return values.
	 * This testcase also verifies the ability to specify a Lat/Long as part of Address.
	 */    
	function testLatLongValidation() 
	{
	   
		$address = new Address();
		$address->setLine1("900 Winslow Way");
		$address->setLine2("Ste 130");
		$address->setCity("Bainbridge Island");
		$address->setRegion ("WA");
		$address->setPostalCode("98110");
		$address->setLongitude("-122.510359");
		$address->setLatitude("47.624972");

		$validateRequest = new ValidateRequest();
		$validateRequest->setAddress($address);
		$validateRequest->setTextCase(TextCase::$Upper);

		//added for 4.13 changes
		$validateRequest->setCoordinates(true);

		//Sets Profile name from Configuration File to "Jaas"
		//this will force it to Jaas (PostLocate)
		$this->client= new AddressServiceSoap("JaasDevelopement");
		
		//validate the Request
		$result= $this->client->validate($validateRequest);
		
		//Re-Assigning to the original Profile 
		$this->client= new AddressServiceSoap("Development");
						
		$this->assertEqual($result->getResultCode(), SeverityLevel::$Success); 
	   
		$validAddresses = $result->getValidAddresses();
		
		$this->assertEqual(1, count($validAddresses));

		$validAddresses = $result->getValidAddresses();
		
		if (count($validAddresses) != 1)
		{
			echo("Unexpected number of addresses returned.  Expected one address.");
		}
		else
		{
			$validAddress=$validAddresses[0];
		   
			$this->assertEqual(strtoupper($address->getLine1())." E " 
				.strtoupper($address->getLine2()) , $validAddress->getLine1());
				
			$this->assertEqual("",$validAddress->getLine2());
			$this->assertEqual(strtoupper($address->getCity()),$validAddress->getCity());
			$this->assertEqual(strtoupper($address->getRegion()),$validAddress->getRegion());
			$this->assertEqual($address->getPostalCode()."-2766",$validAddress->getPostalCode());
			$this->assertEqual("H",$validAddress->getAddressType());
			$this->assertEqual("C051",$validAddress->getCarrierRoute());
			
			//Ticket 21203: Modified Fips Code value for jaas enabled account.
			//$this->assertEqual("5303500000", $validAddress->getFipsCode());
			
			$this->assertEqual("5303503736", $validAddress->getFipsCode());
			$this->assertEqual("KITSAP", $validAddress->getCounty());
			$this->assertEqual(strtoupper($address->getCity())." " 
				.strtoupper($address->getRegion())." ".$address->getPostalCode()
				."-2766",$validAddress->getLine4());
				
			$this->assertEqual("981102766307", $validAddress->getPostNet());
			
			// Added 4.13 changes for the Lat Long
			// Update to check for ZIP+4 precision
			// Zip+4 precision coordinates       
			if (strlen($validAddress->getLatitude()) > 7)
			{
				echo("ZIP+4 precision coordinates received");

				$this->assertEqual($address->getLatitude(), $validAddress->getLatitude());
				$this->assertEqual($address->getLongitude(), $validAddress->getLongitude());
			}
			else
			{
				echo("ZIP5 precision coordinates received");
				 
				$this->assertEqual(substr($validAddress->getLatitude(),0,4), 
					substr($address->getLatitude(),0,4),"Expected Latitude to start with '47.64'");
					
				$this->assertEqual(substr($validAddress->getLongitude(),0,6), 
					substr($address->getLongitude(),0,6),"Expected Longitude to start with '-122.53'");
			  
			}
		}

	}
	
}
?>