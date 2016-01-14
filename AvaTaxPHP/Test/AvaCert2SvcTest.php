<?php
require_once('../simpletest/autorun.php');

require('../AvaTax4PHP/AvaTax.php');	// include in all Avalara Scripts
require('Credentials.php');	// where service URL, account, license key are set

class AvaCert2SvcTest extends UnitTestCase    
{
	private $client;
	
	public function __construct()
	{
		global $client;		    
		$client=new AvaCert2Soap('Development'); 		   
	}
	
	function testPing()
	{
		global $client;
		$result = $client->ping("");
		$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());            
	}
	
	function testCustomerSave()
	{
		global $client;	
		$customer=$this->getCustomer();

		//Success
		$customerSaveRequest = new CustomerSaveRequest();
		$customerSaveRequest->setCompanyCode("Default");
		$customerSaveRequest->setCustomer($customer);
		$customerSaveResult = $client->customerSave($customerSaveRequest);
		$this->assertEqual(SeverityLevel::$Success,$customerSaveResult->getResultCode());
			
		//CustomerSaveDuplicateTest
		$customerSaveRequest->setCustomer($customer);
		$customerSaveResult = $client->customerSave($customerSaveRequest);
		$this->assertEqual(SeverityLevel::$Success,$customerSaveResult->getResultCode());

		//CustomerSaveWarningTest
		//failure-while Passing new customer code, customer corresponding to old customer code not present
		$dateTime=new DateTime();
		$customer->setCustomerCode("PHP_CC".date_format($dateTime,"dmyGis"));
		$customer->setNewCustomerCode("PHP_NCC".date_format($dateTime,"dmyGis"));
		$customerSaveRequest->setCustomer($customer);
		$customerSaveResult= $client->customerSave($customerSaveRequest);
		$this->assertEqual(SeverityLevel::$Warning,$customerSaveResult->getResultCode());
		$messages= $customerSaveResult->getMessages();
		$this->assertEqual("NewCustomerCode", $messages[0]->getRefersTo());
		$this->assertEqual("AvaCertNewCustCodeWarning", $messages[0]->getName());

		//CustomerSaveInvalidEmailTest
		$customer->setEmail("Invalid_EmailId");
		$customerSaveRequest->setCustomer($customer);
		$customerSaveResult = $client->customerSave($customerSaveRequest);
		$this->assertEqual(SeverityLevel::$Error,$customerSaveResult->getResultCode());
		$messages= $customerSaveResult->getMessages();
		$this->assertEqual("Error saving the Customer.", $messages[0]->getSummary());
		$this->assertEqual("Record Skipped; Error: 'Invalid_EmailId' is not a well-formed email address", $messages[0]->getDetails());
		
		//CustomerSaveNewCustomerCodeErrorTest
		$customer->setNewCustomerCode($customer->getCustomerCode());
		$customer->setCustomerCode("avatax4jCust1");
		$customerSaveRequest->setCustomer($customer);
		$customerSaveResult = $client->customerSave($customerSaveRequest);
		$this->assertEqual(SeverityLevel::$Error,$customerSaveResult->getResultCode());
		$messages= $customerSaveResult->getMessages();
		$this->assertEqual("Customer", $messages[0]->getRefersTo());
		$this->assertEqual("Error saving the Customer.", $messages[0]->getSummary());
		
		//CustomerSaveParentCustCodeErrorTest
		$customer->setParentCustomerCode("ParentCustomerCode_NotExist");
		$customerSaveRequest->setCustomer($customer);
		$customerSaveResult = $client->customerSave($customerSaveRequest);
		$this->assertEqual(SeverityLevel::$Error,$customerSaveResult->getResultCode());
		$messages= $customerSaveResult->getMessages();
		$this->assertEqual("Error saving the Customer.", $messages[0]->getSummary());
		
		//CustomerSaveStateErrorTest
		$customer->setState("AA");
		$customer->setEmail("");
		$customerSaveRequest->setCustomer($customer);
		$customerSaveResult = $client->customerSave($customerSaveRequest);
		$this->assertEqual(SeverityLevel::$Error,$customerSaveResult->getResultCode());
		$messages= $customerSaveResult->getMessages();
		$this->assertEqual("Error saving the Customer.", $messages[0]->getSummary());
		$this->assertEqual("Record Skipped; Error: 'AA' is not a valid USPS State Code", $messages[0]->getDetails());
		 
		//CustomerSaveInvalidCustomerTypeTest
		$customer->setType("InvalidCustomerType");
		$customer->setState("WA");
		$customerSaveRequest->setCustomer($customer);
		$customerSaveResult = $client->customerSave($customerSaveRequest);
		$this->assertEqual(SeverityLevel::$Error,$customerSaveResult->getResultCode());
		$messages= $customerSaveResult->getMessages();
		$this->assertEqual("Error saving the Customer.", $messages[0]->getSummary());
		$this->assertEqual("Record Skipped; Error: Invalid customer type", $messages[0]->getDetails());
		
		//CustomerSaveValidateTest
		$customer->setCustomerCode("");
		$customer->setBusinessName("");
		$customerSaveRequest->setCustomer($customer);
		$customerSaveResult = $client->customerSave($customerSaveRequest);
		$this->assertEqual(SeverityLevel::$Error,$customerSaveResult->getResultCode());
		$messages= $customerSaveResult->getMessages();
		$this->assertEqual("Error saving the Customer.", $messages[0]->getSummary());
		$this->assertEqual("Record Skipped; Error: BUSINESS_NAME is required; Error: CustomerCode is required", $messages[0]->getDetails());
				 
		//failure-empty customer object is passed 
		$customer = new Customer();
		$customerSaveRequest->setCustomer($customer);
		$customerSaveResult= $client->customerSave($customerSaveRequest);
		$this->assertEqual(SeverityLevel::$Error,$customerSaveResult->getResultCode());	
	}
	
	function testCertificateRequestInitiate()
	{
		global $client;
		$customer=$this->getCustomer();
		$customerSaveRequest = new CustomerSaveRequest();
		$customerSaveRequest->setCompanyCode("Default");
		$customerSaveRequest->setCustomer($customer);
		$customerSaveResult = $client->customerSave($customerSaveRequest);
		$this->assertEqual(SeverityLevel::$Success,$customerSaveResult->getResultCode());
		
		//CertificateRequestInitiateTest - Success
		$certificateRequestInitiateRequest=new CertificateRequestInitiateRequest();
		$certificateRequestInitiateRequest->setCompanyCode("Default");
		$certificateRequestInitiateRequest->setCustomerCode($customer->getCustomerCode());
		$certificateRequestInitiateRequest->setCommunicationMode(CommunicationMode::$EMAIL);
		$certificateRequestInitiateRequest->setCustomMessage("Testing");
		$certificateRequestInitiateResult = $client->certificateRequestInitiate($certificateRequestInitiateRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateRequestInitiateResult->getResultCode());

		//Failure - duplicate request
		$certificateRequestInitiateResult= $client->certificateRequestInitiate($certificateRequestInitiateRequest);
		$this->assertEqual(SeverityLevel::$Error,$certificateRequestInitiateResult->getResultCode());
		$messages=$certificateRequestInitiateResult->getMessages();
		$this->assertEqual("application.request.open-request-exists",$messages[0]->getRefersTo());
		$this->assertEqual("Request Skipped; open request exists for customer",$messages[0]->getDetails());

		//CertificateRequestInitiateNonExistCustomerCodeTest
		$certificateRequestInitiateRequest->setCustomerCode($this->getCustomerCode());
		$certificateRequestInitiateResult= $client->certificateRequestInitiate($certificateRequestInitiateRequest);
		$this->assertEqual(SeverityLevel::$Error,$certificateRequestInitiateResult->getResultCode());
		$messages=$certificateRequestInitiateResult->getMessages();
		$this->assertEqual("Error saving the CertificateRequestInitiate.",$messages[0]->getSummary());
		$this->assertEqual("application.customer.customer-not-found",$messages[0]->getRefersTo());
		$this->assertEqual("Request Skipped; customer does not exist",$messages[0]->getDetails());

		//CertificateRequestInitiateCustomerCompanyCodesTest
		$customer=$this->getCustomer();
		$customerSaveRequest = new CustomerSaveRequest();
		$customerSaveRequest->setCompanyCode("Default");
		$customerSaveRequest->setCustomer($customer);
		$customerSaveResult = $client->customerSave($customerSaveRequest);
		$this->assertEqual(SeverityLevel::$Success,$customerSaveResult->getResultCode());
					
		$certificateRequestInitiateRequest=new CertificateRequestInitiateRequest();
		$certificateRequestInitiateRequest->setCompanyCode("Default");
		$certificateRequestInitiateRequest->setCustomerCode($customer->getCustomerCode());
		$certificateRequestInitiateRequest->setCommunicationMode(CommunicationMode::$EMAIL);
		$certificateRequestInitiateResult = $client->certificateRequestInitiate($certificateRequestInitiateRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateRequestInitiateResult->getResultCode());
		
		//CertificateRequestInitiateDirectTypeTest
		$customer=$this->getCustomer();
		$customerSaveRequest = new CustomerSaveRequest();
		$customerSaveRequest->setCompanyCode("Default");
		$customerSaveRequest->setCustomer($customer);
		$customerSaveResult = $client->customerSave($customerSaveRequest);
		$this->assertEqual(SeverityLevel::$Success,$customerSaveResult->getResultCode());
					
		$certificateRequestInitiateRequest=new CertificateRequestInitiateRequest();
		$certificateRequestInitiateRequest->setCompanyCode("Default");
		$certificateRequestInitiateRequest->setCustomerCode($customer->getCustomerCode());
		$certificateRequestInitiateRequest->setType(RequestType::$DIRECT);
		$certificateRequestInitiateResult = $client->certificateRequestInitiate($certificateRequestInitiateRequest);
		$this->assertEqual(SeverityLevel::$Success, $certificateRequestInitiateResult->getResultCode());
		$this->assertNotNull($certificateRequestInitiateResult->getWizardLaunchUrl());
		
		//CertificateRequestInitiateMinRequiredFieldsTest
		$customer = new Customer();
		$customer->setCustomerCode($this->getCustomerCode());
		$customer->setBusinessName("BusinessName");
		$customer->setEmail("");
		$customerSaveRequest = new CustomerSaveRequest();
		$customerSaveRequest->setCompanyCode("Default");
		$customerSaveRequest->setCustomer($customer);
		$customerSaveResult = $client->customerSave($customerSaveRequest);
		$this->assertEqual(SeverityLevel::$Success,$customerSaveResult->getResultCode());
					
		$certificateRequestInitiateRequest=new CertificateRequestInitiateRequest();
		$certificateRequestInitiateRequest->setCompanyCode("Default");
		$certificateRequestInitiateRequest->setCustomerCode($customer->getCustomerCode());
		$certificateRequestInitiateRequest->setCommunicationMode(CommunicationMode::$EMAIL);
		$certificateRequestInitiateResult = $client->certificateRequestInitiate($certificateRequestInitiateRequest);
		$this->assertEqual(SeverityLevel::$Error,$certificateRequestInitiateResult->getResultCode());
		$messages=$certificateRequestInitiateResult->getMessages();
		$this->assertEqual("validation.common.email-required",$messages[0]->getRefersTo());

		//CertificateRequestInitiateLocationErrorTest
		$certificateRequestInitiateRequest=new CertificateRequestInitiateRequest();
		$certificateRequestInitiateRequest->setCompanyCode("Default");
		$certificateRequestInitiateRequest->setCustomerCode($customer->getCustomerCode());
		$certificateRequestInitiateRequest->setCommunicationMode(CommunicationMode::$EMAIL);
		$certificateRequestInitiateRequest->setSourceLocationCode("LocationCode");
		$certificateRequestInitiateResult = $client->certificateRequestInitiate($certificateRequestInitiateRequest);
		$this->assertEqual(SeverityLevel::$Error,$certificateRequestInitiateResult->getResultCode());
		$messages=$certificateRequestInitiateResult->getMessages();
		$this->assertEqual("application.configuration.location-override-disabled",$messages[0]->getRefersTo());
		$this->assertEqual("Request Skipped; location override is disabled",$messages[0]->getDetails());
		
		//CertificateRequestInitiateCustomerCodeMissingTest
		$certificateRequestInitiateRequest->setCustomerCode("");
		$certificateRequestInitiateResult= $client->certificateRequestInitiate($certificateRequestInitiateRequest);
		$this->assertEqual(SeverityLevel::$Error,$certificateRequestInitiateResult->getResultCode());
		$messages=$certificateRequestInitiateResult->getMessages();
		$this->assertEqual("validation.common.field-value-error",$messages[0]->getRefersTo());
		$this->assertEqual("Request Skipped; Error: CustomerCode is required; Warning: REQUEST_ID ignored; Warning: TYPE ignored",$messages[0]->getDetails());
		
		//CertificateRequestInitiateFieldMissingTest
		$certificateRequestInitiateRequest = new CertificateRequestInitiateRequest();
		$certificateRequestInitiateRequest->setCompanyCode("Default");
		$certificateRequestInitiateRequest->setCustomerCode("");
		$certificateRequestInitiateResult= $client->certificateRequestInitiate($certificateRequestInitiateRequest);
		$this->assertEqual(SeverityLevel::$Error,$certificateRequestInitiateResult->getResultCode());
		$messages=$certificateRequestInitiateResult->getMessages();
		$this->assertEqual("validation.common.field-value-error",$messages[0]->getRefersTo());
					
		//CertificateRequestInitiateCommunicationModeTest - FAX
		$certificateRequestInitiateRequest=new CertificateRequestInitiateRequest();
		$certificateRequestInitiateRequest->setCompanyCode("Default");
		$certificateRequestInitiateRequest->setCustomerCode($customer->getCustomerCode());
		$certificateRequestInitiateRequest->setCommunicationMode(CommunicationMode::$FAX );
		$certificateRequestInitiateResult= $client->certificateRequestInitiate($certificateRequestInitiateRequest);
		$this->assertEqual(SeverityLevel::$Error,$certificateRequestInitiateResult->getResultCode());
		$messages=$certificateRequestInitiateResult->getMessages();
		$this->assertEqual("validation.common.fax-required",$messages[0]->getRefersTo());
		$this->assertEqual("Request Skipped; fax number is required",$messages[0]->getDetails());
		
		//CertificateRequestInitiateCommunicationModeTest - MAIL
		$customer->setAddress1("");
		$certificateRequestInitiateRequest->setCommunicationMode(CommunicationMode::$MAIL);
		$certificateRequestInitiateResult= $client->certificateRequestInitiate($certificateRequestInitiateRequest);
		$this->assertEqual(SeverityLevel::$Error,$certificateRequestInitiateResult->getResultCode());
		$messages=$certificateRequestInitiateResult->getMessages();
		$this->assertEqual("validation.request.mail-address-incomplete",$messages[0]->getRefersTo());
		$this->assertEqual("Request Skipped; mailing address is incomplete",$messages[0]->getDetails());
	}
	
	function testCertificateGet()
	{
		global $client;
	
		// Success test
		$certificateGetRequest = new CertificateGetRequest();
		$certificateGetRequest->setCompanyCode("Default");
		$dateTime=new DateTime();	    
		$certificateGetRequest->setModToDate(date_format($dateTime,"Y-m-d"));	        
		$dateTime->modify("-10 day");	    
		$certificateGetRequest->setModFromDate(date_format($dateTime,"Y-m-d"));
		$certificateGetResult = $client->certificateGet($certificateGetRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateGetResult->getResultCode());
		 
		// CertificateGetByCustomerTest
		$certificateGetRequest = new CertificateGetRequest();
		$certificateGetRequest->setCompanyCode("Default");
		$dateTime=new DateTime();
		$certificateGetRequest->setCustomerCode("avatax4PHP".date_format($dateTime,"dmyGis"));
		$certificateGetResult = $client->certificateGet($certificateGetRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateGetResult->getResultCode());
		
		// CertificateGetReasonCodeTest
		$certificateGetRequest = new CertificateGetRequest();
		$certificateGetRequest->setCompanyCode("Default");
		$certificateGetResult = $client->certificateGet($certificateGetRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateGetResult->getResultCode());
		$certificates = $certificateGetResult->getCertificates();
		if (count($certificates) > 0)
		{
			/*foreach ($certificates as $certificate)
			{
				if($certificate->getReviewStatus() == ReviewStatus::REJECTED)
				{
					$this->assertNotEqual("",$certificate->getRejectionReasonCode());
					if (strcmp($certificate->getRejectionReasonCode(),"OTHER_REASON") == 0)
					{
						$this->assertNotEqual("",$certificate->getRejectionReasonCustomText());
					}
				}
			}*/
			$certificate;
			for($i=0;$i<count($certificates);$i++)
			{
				$certificate=$certificates[$i];
				if($certificate->getReviewStatus() == ReviewStatus::$REJECTED)
				{
					$this->assertNotEqual("",$certificate->getRejectionReasonCode());
					//if (strcmp($certificate->getRejectionReasonCode(),"OTHER_REASON") == 0)
					if ($certificate->getRejectionReasonCode() == "OTHER_REASON")
					{
						$this->assertNotEqual("",$certificate->getRejectionReasonCustomText());
					}
				}
			}
		}
		
		// CertificateGetWithoutDatesTest
		$certificateGetRequest = new CertificateGetRequest();
		$certificateGetRequest->setCompanyCode("Default");
		$certificateGetResult = $client->certificateGet($certificateGetRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateGetResult->getResultCode());	        
	}
	
	function testCertificateRequestGet()
	{
		global $client;
	
		$customer = $this->getCustomer();
		$customerSaveRequest = new CustomerSaveRequest();
		$customerSaveRequest->setCompanyCode("Default");
		$customerSaveRequest->setCustomer($customer);
		$customerSaveResult = $client->customerSave($customerSaveRequest);
		$this->assertEqual(SeverityLevel::$Success,$customerSaveResult->getResultCode());
					
		$certificateRequestInitiateRequest = new CertificateRequestInitiateRequest();
		$certificateRequestInitiateRequest->setCompanyCode("Default");
		$certificateRequestInitiateRequest->setCustomerCode($customer->getCustomerCode());
		$certificateRequestInitiateRequest->setCommunicationMode(CommunicationMode::$EMAIL);
		$certificateRequestInitiateResult = $client->certificateRequestInitiate($certificateRequestInitiateRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateRequestInitiateResult->getResultCode());
		
		// CertificateRequestGet_CustomerCodeTest
		$certificateRequestGetRequest = new CertificateRequestGetRequest();
		$certificateRequestGetRequest->setCompanyCode("Default");
		$certificateRequestGetRequest->setCustomerCode($customer->getCustomerCode());
		$certificateRequestGetResult = $client->certificateRequestGet($certificateRequestGetRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateRequestGetResult->getResultCode());
		 
		// CertificateRequestGet_CustomerCodeRequestStatusTest
		$certificateRequestGetRequest->setRequestStatus(CertificateRequestStatus::$OPEN);
		$certificateRequestGetResult = $client->certificateRequestGet($certificateRequestGetRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateRequestGetResult->getResultCode());
		$this->assertEqual(1, Count($certificateRequestGetResult->getCertificateRequests()));
		$certificateRequests = $certificateRequestGetResult->getCertificateRequests();
		$this->assertEqual($certificateRequestInitiateResult->getTrackingCode(), $certificateRequests[0]->getTrackingCode());
		$this->assertEqual($certificateRequestInitiateResult->getCustomerCode(), $certificateRequests[0]->getCustomerCode());
		$this->assertEqual($certificateRequestInitiateRequest->getCommunicationMode(), $certificateRequests[0]->getCommunicationMode());
		
		// CertificateRequestGet_CustomerCodeRequestStatusClosedTest
		$certificateRequestGetRequest->setRequestStatus(CertificateRequestStatus::$CLOSED);
		$certificateRequestGetResult = $client->certificateRequestGet($certificateRequestGetRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateRequestGetResult->getResultCode());
		$certificateRequests = $certificateRequestGetResult->getCertificateRequests();
		$this->assertEqual(0, Count($certificateRequests));
		
		//CertificateRequestGet_RequestStatusTest
		$certificateRequestGetRequest = new CertificateRequestGetRequest();
		$certificateRequestGetRequest->setCompanyCode("Default");
		$certificateRequestGetRequest->setRequestStatus(CertificateRequestStatus::$OPEN);
		$certificateRequestGetResult = $client->certificateRequestGet($certificateRequestGetRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateRequestGetResult->getResultCode());
		
		// CertificateRequestGet_ModDateRangeTest
		$certificateRequestGetRequest->setCustomerCode($customer->getCustomerCode());
		$dateTime=new DateTime();	    
		$certificateRequestGetRequest->setModToDate(date_format($dateTime,"Y-m-d"));	        
		$dateTime->modify("-1 day");	    
		$certificateRequestGetRequest->setModFromDate(date_format($dateTime,"Y-m-d"));
		$certificateRequestGetResult = $client->certificateRequestGet($certificateRequestGetRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateRequestGetResult->getResultCode());
		$this->assertEqual(1, Count($certificateRequestGetResult->getCertificateRequests()));
		$certificateRequests = $certificateRequestGetResult->getCertificateRequests();
		$this->assertEqual($certificateRequestInitiateResult->getTrackingCode(), $certificateRequests[0]->getTrackingCode());
		$this->assertEqual($certificateRequestInitiateResult->getCustomerCode(), $certificateRequests[0]->getCustomerCode());
		$this->assertEqual($certificateRequestInitiateRequest->getCommunicationMode(), $certificateRequests[0]->getCommunicationMode());
		
		// CertificateRequestGet_InvalidModDateRangeTest
		$certificateRequestGetRequest->setCustomerCode($customer->getCustomerCode());
		$dateTime=new DateTime();	  
		$dateTime->modify("-5 day");	  
		$certificateRequestGetRequest->setModToDate(date_format($dateTime,"Y-m-d"));	        
		$dateTime->modify("-10 day");	    
		$certificateRequestGetRequest->setModFromDate(date_format($dateTime,"Y-m-d"));
		$certificateRequestGetResult = $client->certificateRequestGet($certificateRequestGetRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateRequestGetResult->getResultCode());
		$this->assertEqual(0, Count($certificateRequestGetResult->getCertificateRequests()));
		
		// CertificateRequestGet_CustomerCodeModFromDateTest
		$certificateRequestGetRequest = new CertificateRequestGetRequest();
		$certificateRequestGetRequest->setCompanyCode("Default");
		$certificateRequestGetRequest->setCustomerCode($customer->getCustomerCode());
		$dateTime=new DateTime();        
		$dateTime->modify("-1 day");	    
		$certificateRequestGetRequest->setModFromDate(date_format($dateTime,"Y-m-d"));
		$certificateRequestGetResult = $client->certificateRequestGet($certificateRequestGetRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateRequestGetResult->getResultCode());
		$this->assertEqual(1, Count($certificateRequestGetResult->getCertificateRequests()));
		$certificateRequests = $certificateRequestGetResult->getCertificateRequests();
		$this->assertEqual($certificateRequestInitiateResult->getTrackingCode(), $certificateRequests[0]->getTrackingCode());
		$this->assertEqual($certificateRequestInitiateResult->getCustomerCode(), $certificateRequests[0]->getCustomerCode());
		$this->assertEqual($certificateRequestInitiateRequest->getCommunicationMode(), $certificateRequests[0]->getCommunicationMode());
	}
	
	function testCertificateImageGet()
	{
		global $client;
	
		// CertificateImageGet_PNGTest
		$certificateImageGetRequest = new CertificateImageGetRequest();
		$certificateImageGetRequest->setCompanyCode("Default");	         
		$certificateImageGetRequest->setAvaCertId("CBSK");	
		$certificateImageGetRequest->setFormat(FormatType::$PNG);
		$certificateImageGetRequest->setPageNumber(1);
		$certificateImageGetResult = $client->certificateImageGet($certificateImageGetRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateImageGetResult->getResultCode());
		$this->assertNotNull($certificateImageGetResult->getImage());
		/*$fp = fopen("C:/aa.png","w"); 
		$byteArray=$certificateImageGetResult->getImage();
		fwrite($fp, $byteArray); 
		fclose($fp); 
		*/
		
		// CertificateImageGet_PDFTest
		$certificateImageGetRequest = new CertificateImageGetRequest();
		$certificateImageGetRequest->setCompanyCode("Default");	         
		$certificateImageGetRequest->setAvaCertId("CBSK");	
		$certificateImageGetRequest->setFormat(FormatType::$PDF);
		$certificateImageGetResult = $client->certificateImageGet($certificateImageGetRequest);
		$this->assertEqual(SeverityLevel::$Success,$certificateImageGetResult->getResultCode());
		/*$this->assertNotNull($certificateImageGetResult->getImage());
		$fp = fopen("C:/aa.pdf","w"); 
		$byteArray=$certificateImageGetResult->getImage();
		fwrite($fp, $byteArray); 
		fclose($fp);
		*/
		
		// CertificateImageGet_MissingAvaCertIdTest
		$certificateImageGetRequest = new CertificateImageGetRequest();
		$certificateImageGetRequest->setCompanyCode("Default");	 
		$certificateImageGetRequest->setFormat(FormatType::$PNG);
		$certificateImageGetResult = $client->certificateImageGet($certificateImageGetRequest);
		$this->assertEqual(SeverityLevel::$Error,$certificateImageGetResult->getResultCode());
		$messages=$certificateImageGetResult->getMessages();
		$this->assertEqual("AvaCertImageError",$messages[0]->getName());
		$this->assertEqual("ERROR: Resource not found.  One or more of the following is invalid: CompanyCode, AvaCertId.\r\n",$messages[0]->getDetails());
		
		// CertificateImageGet_InvalidAvaCertIdTest
		$certificateImageGetRequest = new CertificateImageGetRequest();
		$certificateImageGetRequest->setCompanyCode("Default");	 
		$certificateImageGetRequest->setAvaCertId("InvalidId");
		$certificateImageGetResult = $client->certificateImageGet($certificateImageGetRequest);
		$this->assertEqual(SeverityLevel::$Error,$certificateImageGetResult->getResultCode());
		$messages=$certificateImageGetResult->getMessages();
		$this->assertEqual("AvaCertImageError",$messages[0]->getName());
		$this->assertEqual("CertificateNotFound",$messages[0]->getRefersTo());
		$this->assertEqual("ERROR: Invalid certificate ID.\r\n",$messages[0]->getDetails());
	}
	
	private function getCustomer()
	{
		$customer = new Customer();
		$dateTime=new DateTime();
		$customer->setCustomerCode ("avatax4jCust".date_format($dateTime,"dmyGis"));
		$customer->setCountry ("US");
		$customer->setCity ("BainbridgeIsland");
		$customer->setZip ("98110");
		$customer->setEmail("devadmin@avalara.com");
		$customer->setState ("WA");
		$customer->setBusinessName ("Test");
		$customer->setType("Bill_To");

		return $customer; 	
   }
		
	private function getCustomerCode()
	{	        
		$dateTime = new DateTime();
		return "avatax4jCust".date_format($dateTime,"dmyGis");
	}
}
?>