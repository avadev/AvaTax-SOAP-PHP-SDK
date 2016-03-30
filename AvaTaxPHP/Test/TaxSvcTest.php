<?php
require_once('../simpletest/autorun.php');

require('../AvaTax4PHP/AvaTax.php');	// include in all Avalara Scripts
require('Credentials.php');	// where service URL, account, license key are set

class TaxSvcTest extends UnitTestCase
{
	private $getTaxResult;
	private $getTaxRequest;
	private $getTaxHistoryRequest;
	private $getTaxHistoryResult;
	private $client;
	private $postResult;
	private $commitResult;

	public function __construct()
	{
		global $client;

		$client=new TaxServiceSoap('Development');

	}
	function testPing()
	{
		global $client;
		$result = $client->ping("");

		$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());

	}
	function testIsAuthorized()
	{
		global $client;
		$result = $client->isAuthorized("Ping,IsAuthorized,Validate");
		$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());
	}
	function testGetTax()
	{
		global $getTaxResult,$getTaxRequest,$client;

		$dateTime=new DateTime();
		$docCode= "PHP".date_format($dateTime,"dmyGis");

		$getTaxRequest=$this->CreateTaxRequest($docCode);
		$getTaxResult = $client->getTax($getTaxRequest);

		$this->assertEqual(SeverityLevel::$Success,$getTaxResult->getResultCode());
		$this->assertEqual(DocStatus::$Saved,$getTaxResult->getDocStatus());
		$this->assertNotEqual('1',$getTaxResult->getDocId());
		$this->assertEqual(1010,$getTaxResult->getTotalAmount());
		$this->assertEqual(96.96,$getTaxResult->getTotalTax());


		//TaxDetail
		$this->assertEqual(count($getTaxRequest->getLines()),count($getTaxResult->getTaxLines()),"Tax line count");

		$taxLineArray=$getTaxResult->getTaxLines();
		$taxLine=$taxLineArray[0];

		$taxDetailArray=$taxLine->getTaxDetails();
		$taxDetail=$taxDetailArray[0];

		$this->assertEqual(53,$taxDetail->getJurisCode());
		$this->assertEqual(JurisdictionType::$State,$taxDetail->getJurisType());
		$this->assertEqual(1000,$taxDetail->getTaxable());
		$this->assertEqual(0.065000,$taxDetail->getRate());
		$this->assertEqual(65,$taxDetail->getTax());
		$this->assertEqual(0,$taxDetail->getNonTaxable());

	}
	function testTaxHistory()
	{
		global $getTaxResult,$getTaxHistoryRequest,$getTaxRequest,$getTaxHistoryResult,$client;

		$getTaxHistoryRequest = new GetTaxHistoryRequest();
		$getTaxHistoryRequest->setCompanyCode($getTaxRequest->getCompanyCode());
		$getTaxHistoryRequest->setDocId($getTaxResult->getDocId());
		$getTaxHistoryRequest->setDocCode($getTaxRequest->getDocCode());
		$getTaxHistoryRequest->setDetailLevel(DetailLevel::$Diagnostic);
		$getTaxHistoryRequest->setDocType(DocumentType::$SalesInvoice);

		$getTaxHistoryResult = $client->getTaxHistory($getTaxHistoryRequest);


		$this->assertEqual("Success",$getTaxHistoryResult->getResultCode());

		$historyTaxRequest = $getTaxHistoryResult->getGetTaxRequest();
		$this->assertNotNull($historyTaxRequest);
		$this->assertEqual(count($getTaxRequest->getLines()), count($historyTaxRequest->getLines()));
		$this->assertEqual(count($getTaxRequest->getAddresses()), count($historyTaxRequest->getAddresses()));

		//compare all properties
		$this->CompareHistory($getTaxRequest,$getTaxResult,$getTaxHistoryResult);

	}
	function testPostTax()
	{
		global $getTaxRequest,$getTaxResult,$client,$postResult;

		//Post tax
		$postRequest = new PostTaxRequest();

		$postRequest->setCompanyCode($getTaxRequest->getCompanyCode());
		$postRequest->setDocId($getTaxResult->getDocId());
		$postRequest->setDocType($getTaxRequest->getDocType());
		$postRequest->setDocCode($getTaxRequest->getDocCode());
		$postRequest->setDocDate($getTaxRequest->getDocDate());
		$postRequest->setTotalAmount($getTaxResult->getTotalAmount());
		$postRequest->setTotalTax($getTaxResult->getTotalTax());

		$postResult = $client->postTax($postRequest);

		$this->assertEqual($getTaxResult->getDocId(),$postResult->getDocId());
		$this->assertEqual(SeverityLevel::$Success, $postResult->getResultCode());

	}
	function testCommitTax()
	{
		global $postResult,$getTaxRequest,$client,$commitResult;

		$commitRequest = new CommitTaxRequest();
		$commitRequest->setCompanyCode($getTaxRequest->getCompanyCode());
		$commitRequest->setDocId($postResult->getDocId());
		$commitRequest->setDocCode($getTaxRequest->getDocCode());
		$commitRequest->setDocType($getTaxRequest->getDocType());

		$commitResult = $client->commitTax($commitRequest);
		$this->assertEqual($postResult->getDocId(),$commitResult->getDocId());
		$this->assertEqual(SeverityLevel::$Success, $commitResult->getResultCode());

	}
	function testCancelTax()
	{
		global $getTaxRequest,$commitResult,$client;

		$cancelRequest = new CancelTaxRequest();

		$cancelRequest->setCompanyCode($getTaxRequest->getCompanyCode());
		$cancelRequest->setDocId($commitResult->getDocId());
		$cancelRequest->setDocCode($getTaxRequest->getDocCode());
		$cancelRequest->setDocType($getTaxRequest->getDocType());
		$cancelRequest->setCancelCode(CancelCode::$DocDeleted);

		$cancelResult = $client->cancelTax($cancelRequest);
		$this->assertEqual($commitResult->getDocId(),$cancelResult->getDocId());
		$this->assertEqual(SeverityLevel::$Success, $cancelResult->getResultCode());

	}
	function testAdjustTax()
	{
		global $client;

		$dateTime=new DateTime();
		$docCode= "PHPAdjustTaxTest".date_format($dateTime,"dmyGis");
		$getTaxRequest=$this->CreateTaxRequest($docCode);
		$getTaxRequest->setCommit(true);

		$getTaxResult= $client->getTax($getTaxRequest);
		$this->assertEqual(SeverityLevel::$Success,$getTaxResult->getResultCode());

		$adjustTaxRequest=new AdjustTaxRequest();
		$adjustTaxRequest->setAdjustmentReason(8);
		$adjustTaxRequest->setAdjustmentDescription("For testing");

		$getTaxRequest->getLine("1")->setAmount(2000);
		$adjustTaxRequest->setGetTaxRequest($getTaxRequest);

		$adjustTaxResult= $client->adjustTax($adjustTaxRequest);

		$this->assertEqual(SeverityLevel::$Success,$adjustTaxResult->getResultCode());

		$var=$adjustTaxResult->getTotalTax();

		$cancelRequest = new CancelTaxRequest();
		$cancelRequest->setDocCode($getTaxRequest->getDocCode());
	//	$cancelRequest->setDocId($getTaxResult->getDocId());
		$cancelRequest->setDocType($getTaxRequest->getDocType());
		$cancelRequest->setCompanyCode($getTaxRequest->getCompanyCode());
		$cancelRequest->setCancelCode(CancelCode::$AdjustmentCancelled);

		$cancelResult = $client->cancelTax($cancelRequest);

		$this->assertEqual(SeverityLevel::$Success, $cancelResult->getResultCode());

		//Change status to DocDeleted and call cancel again
		$cancelRequest->setCancelCode(CancelCode::$DocDeleted);
		$cancelResult = $client->cancelTax($cancelRequest);

		$this->assertEqual(SeverityLevel::$Success, $cancelResult->getResultCode());



	}
	function testTaxOverrideHeader()
	{
		global $client;

		$dateTime=new DateTime();

		$request = $this->CreateTaxRequest("TaxOverrideTest".date_format($dateTime,"dmyGis"));

		$taxOverride = new TaxOverride();
		$taxOverride->setTaxOverrideType(TaxOverrideType::$TaxAmount);
		$taxOverride->setTaxAmount(5);
		$dateTime=new DateTime();
		$taxOverride->setTaxDate(date_format($dateTime,"Y-m-d"));
		$taxOverride->setReason("Return");

		$request->setTaxOverride($taxOverride);

		$result = $client->getTax($request);

		$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());

		$this->assertEqual($result->getTotalTax(),5);
		$this->assertEqual($result->getTotalTaxCalculated(),96.96);

	}
	function testTaxOverrideLine()
	{
		global $client;


		$dateTime=new DateTime();
		$docCode= "TaxOverrideLineTest".date_format($dateTime,"dmyGis");
		$request = $this->CreateTaxRequestForTaxOverride($docCode);

		$result = $client->getTax($request);
		$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());
		$this->assertEqual($result->getTotalTax(),5);


		$taxLine=$result->getTaxLines();
		$taxLine=$taxLine[0];
		$this->assertEqual($taxLine->getTax(),5);
		$this->assertEqual($taxLine->getTaxCalculated(),29);


	}
	function testApplyPayment()
	{

		global $client;

		$dateTime=new DateTime();
		$docCode= "ApplyPaymentTest".date_format($dateTime,"dmyGis");
		$request = $this->CreateTaxRequest($docCode);

		$request->setDetailLevel(DetailLevel::$Tax);

		$dateTime->modify("-2 day");
		$request->setDocDate(date_format($dateTime,"Y-m-d"));

		$request->setCommit(true);
		$result = $client->getTax($request);

		$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());

		$applyPaymentRequest = new ApplyPaymentRequest();
		$applyPaymentRequest->setCompanyCode($request->getCompanyCode());
		$applyPaymentRequest->setDocCode($request->getDocCode());
		$applyPaymentRequest->setDocId($result->getDocId());
		$dateTime=new DateTime();
		$applyPaymentRequest->setPaymentDate(date_format($dateTime,"Y-m-d"));
		$applyPaymentRequest->setDocType(DocumentType::$SalesInvoice);
		$applyPaymentResult = $client->applyPayment($applyPaymentRequest);


		if(SeverityLevel::$Warning==$applyPaymentResult->getResultCode())
		{
			$message=$applyPaymentResult->getMessages();
			$message=$message[0];
			$message=$message->getName();
			$this->assertEqual("ApplyPaymentWarning",$message);
		}
		else
		{
			$this->assertEqual(SeverityLevel::$Success,$applyPaymentResult->getResultCode());
		}

	}
	function testReconcileTaxHistory()
	{
		global $client;

		$dateTime=new DateTime();
		$docCode= "PHPReconcile".date_format($dateTime,"dmyGis");
		$getTaxRequest=$this->CreateTaxRequest($docCode);
		$getTaxRequest->setCommit(true);
		$getTaxResult = $client->getTax($getTaxRequest);

		$this->assertEqual(SeverityLevel::$Success,$getTaxResult->getResultCode());
		$this->assertEqual(DocStatus::$Committed,$getTaxResult->getDocStatus());

		$reconcileTaxHistoryRequest = new ReconcileTaxHistoryRequest();
		$reconcileTaxHistoryRequest->setCompanyCode("DEFAULT");
		//request.setReconciled(false);
		$reconcileTaxHistoryRequest->setStartDate(date_format($dateTime,"Y-m-d"));
		$reconcileTaxHistoryRequest->setEndDate(date_format($dateTime,"Y-m-d"));
		$reconcileTaxHistoryRequest->setDocStatus(DocStatus::$Committed);
		$reconcileTaxHistoryRequest->setLastDocId("0");
		$reconcileTaxHistoryRequest->setLastDocCode("0");
		$reconcileTaxHistoryRequest->setPageSize(1000);
		$reconcileTaxHistoryRequest->setDocType(DocumentType::$SalesInvoice);

		//Calling reconHistory Method

		$reconcileTaxHistoryResult = $client->reconcileTaxHistory($reconcileTaxHistoryRequest);
		$this->assertEqual(SeverityLevel::$Success,$reconcileTaxHistoryResult->getResultCode());
		$taxResults = $reconcileTaxHistoryResult->getGetTaxResults();
		$this->assertTrue(count($taxResults) > 0,"Expected > 0 reconcile records");
		$this->assertTrue($reconcileTaxHistoryResult->getRecordCount() >= count($taxResults),"RecordCount has to be equal to or more than number of records fetched");
		$found = false;

		/*do
		{
			foreach ($taxResults as $taxResult)
			{
				$this->assertEqual(DocStatus::$Committed, $taxResult->getDocStatus());

				if (strcmp($taxResult->getDocCode(),$getTaxRequest->getDocCode()) == 0)
				{
					$found = true;
				}
			}

			$reconcileTaxHistoryRequest->setLastDocCode($reconcileTaxHistoryResult->getLastDocCode());
			$reconcileTaxHistoryResult = $client->reconcileTaxHistory($reconcileTaxHistoryRequest);
			$taxResults = $reconcileTaxHistoryResult->getGetTaxResults();
		}
		while ( count($taxResults) > 0);*/

		//$this->assertTrue($found,"ReconcileCommittedTest doc not found");

		//Cancel Tax
		$cancelRequest = new CancelTaxRequest();

		$cancelRequest->setCompanyCode($getTaxRequest->getCompanyCode());
		$cancelRequest->setDocCode($getTaxRequest->getDocCode());
		$cancelRequest->setDocType($getTaxRequest->getDocType());
		$cancelRequest->setCancelCode(CancelCode::$DocDeleted);

		$cancelResult = $client->cancelTax($cancelRequest);

		$this->assertEqual(SeverityLevel::$Success, $cancelResult->getResultCode());


	}
	function testTaxIncluded()
	{
		global $client;
		$dateTime=new DateTime();
		$docCode= "PHPtestTaxIncluded".date_format($dateTime,"dmyGis");
		$request = $this->CreateTaxRequest($docCode);
		$lines=$request->getLines();
		$line = $lines[0];
		$line->setTaxIncluded(true);

		$result = $client->getTax($request);

		$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());
		$this->assertEqual(DocStatus::$Saved, $result->getDocStatus());
		//$this->assertEqual($result->isReconciled());
		$this->assertEqual(922.41, $result->getTotalAmount());
		$this->assertEqual(88.55, $result->getTotalTax());
		$taxLines= $result->getTaxLines();
		$taxLine= $taxLines[0];
		$this->assertTrue($taxLine->getTaxIncluded());


		// Check tax history
		$taxHistoryRequest = new GetTaxHistoryRequest();
		$taxHistoryRequest->setCompanyCode($request->getCompanyCode());
		$taxHistoryRequest->setDocCode($request->getDocCode());
		$taxHistoryRequest->setDocType($request->getDocType());
		$taxHistoryRequest->setDetailLevel(DetailLevel::$Diagnostic);
		$taxHistoryResult = $this->waitForTaxHistory($taxHistoryRequest, $result->getTimestamp());

		$this->assertEqual(SeverityLevel::$Success, $taxHistoryResult->getResultCode());
		$this->compareHistory($request, $result, $taxHistoryResult);
	}
function testTaxOverrideType()
{
   global $client;

   $dateTime=new DateTime();
   $docCode= "TaxOverrideLineTypeTest".date_format($dateTime,"dmyGis");
   $request = $this->CreateTaxRequestForTaxOverrideType($docCode);

   $result = $client->getTax($request);
   $this->assertEqual(SeverityLevel::$Success, $result->getResultCode());
   $this->assertEqual($request->getDocType(),$result->getDocType());
   $this->assertEqual($result->getTotalTaxCalculated(),21.1);
}
function testGetTaxWithDocType()
{
	global $client;

	$docType = DocumentType::$InventoryTransferInvoice;
	$docType1 = DocumentType::$InventoryTransferOrder;

	//Testing Document Type InventoryTransferInvoice.
	$request = $this->CreateTaxRequestDocType($docType);

	$result = $client->getTax($request);
	$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());
	$this->assertEqual($result->getTotalAmount(),1010);
	$this->assertEqual($result->getTotalTax(),96.96);

	//Testing Document Type InventoryTransferOrder.
	$request = $this->CreateTaxRequestDocType($docType1);

	$result = $client->getTax($request);
	$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());
	$this->assertEqual($result->getTotalAmount(),1010);
	$this->assertEqual($result->getTotalTax(),96.96);
}

function testGetTaxBusinessIdentificationNo()
{
	global $getTaxResult,$getTaxRequest,$client;

	$getTaxRequest=$this->CreateTaxRequestForBINo("HL123");

	$getTaxResult = $client->getTax($getTaxRequest);

	$this->assertEqual(SeverityLevel::$Success,$getTaxResult->getResultCode());
	$this->assertEqual(1010,$getTaxResult->getTotalAmount());
	$this->assertEqual(96.96,$getTaxResult->getTotalTax());
	// Check tax history
	$taxHistoryRequest = new GetTaxHistoryRequest();
	$taxHistoryRequest->setCompanyCode($getTaxRequest->getCompanyCode());
	$taxHistoryRequest->setDocCode($getTaxRequest->getDocCode());
	$taxHistoryRequest->setDocType($getTaxRequest->getDocType());
	$taxHistoryRequest->setDetailLevel(DetailLevel::$Tax);
	$taxHistoryResult = $this->waitForTaxHistory($taxHistoryRequest, $getTaxResult->getTimestamp());
	$this->assertEqual(SeverityLevel::$Success, $taxHistoryResult->getResultCode());
	$this->assertEqual($taxHistoryResult->getGetTaxRequest()->getBusinessIdentificationNo(),$getTaxRequest->getBusinessIdentificationNo());
	for ($i=0;$i< count($getTaxRequest->getLines());$i++)
	{

		$requestLine =$getTaxRequest->getLines();
		$requestLine=$requestLine[$i];

		$historyLine = $taxHistoryResult->getGetTaxRequest()->getLine($requestLine->getNo());
		$this->assertEqual($requestLine->getNo(), $historyLine->getNo());
		$this->assertEqual($requestLine->getAmount(), $historyLine->getAmount());
		$this->assertEqual($requestLine->getItemCode(),$historyLine->getItemCode());
		$this->assertEqual($requestLine->getBusinessIdentificationNo(),$historyLine->getBusinessIdentificationNo());
	}

}

 function testTaxDetailStateAssignedNo()
{

	global $client;
	$getTaxRequest = $this->CreateTaxRequest("testStateAssignedNo");
	$getTaxRequest->setDetailLevel(DetailLevel::$Diagnostic);

	//Set origin Address
	$origin = new Address();
	$origin->setLine1("Avalara");
	$origin->setLine2("900 winslow way");
	$origin->setLine3("Suite 100");
	$origin->setCity("Bainbridge Island");
	$origin->setRegion("WA");
	$origin->setPostalCode("98110-1896");
	$origin->setCountry("USA");
	$getTaxRequest->setOriginAddress($origin);

	//Set destination address
	$destination=  new Address();
	$destination->setLine1("400 Embassy Row NE Ste 580");
	$destination->setCity("Atlanta");
	$destination->setRegion("GA");
	$destination->setPostalCode("30328-7000");
	$destination->setCountry("USA");
	$getTaxRequest->setDestinationAddress($destination);


	//StateAssignedNo returned by GetTax
	$getTaxResult=$client->getTax($getTaxRequest);
	$this->assertEqual(SeverityLevel::$Success,$getTaxResult->getResultCode());


	$isStateAssignedNo = false;
	$resultTaxDetail=$getTaxResult->getTaxSummary();
	if ( count($resultTaxDetail)  > 0)
	{
		for($i=0;$i<count($resultTaxDetail);$i++)
		{
			$taxDetail=$resultTaxDetail[$i];
			 if ($taxDetail->getStateAssignedNo() != null && $taxDetail->getStateAssignedNo() != "")
			 {
				 $this->assertEqual("060", $taxDetail->getStateAssignedNo());
				 $isStateAssignedNo=true;
			 }
		}
	}
	$this->assertTrue($isStateAssignedNo,"Failed to fetch State Assigned No for the given address");



	$isStateAssignedNo = false;
	$taxLines=$getTaxResult->getTaxLines();
	$taxDetails= $taxLines[0]->getTaxDetails();
	if (count($taxLines) > 0 && count($taxDetails)> 0)
	{

		for($i=0;$i<count($taxDetails);$i++)
		{
			$taxDetail=$taxDetails[$i];
			if ($taxDetail->getStateAssignedNo() != null && $taxDetail->getStateAssignedNo() != "")
			{
				$this->assertEqual("060", $taxDetail->getStateAssignedNo());
				$isStateAssignedNo = true;
			}
		}
	}
   $this->assertTrue($isStateAssignedNo,"Failed to fetch State Assigned No for the given address");

	// 2. StateAssignedNo is returned by GetTaxHistory
	$historyRequest = new GetTaxHistoryRequest();
	$historyRequest->setCompanyCode($getTaxRequest->getCompanyCode());
	$historyRequest->setDocType($getTaxRequest->getDocType());
	$historyRequest->setDocCode($getTaxRequest->getDocCode());
	$historyRequest->setDetailLevel( DetailLevel::$Diagnostic);

	$historyResult = $client->getTaxHistory($historyRequest);

	$this->assertEqual(SeverityLevel::$Success, $historyResult->getResultCode());
	$this->assertNotNull($historyResult->getGetTaxRequest());
	$this->assertNotNull($historyResult->getGetTaxResult());

	$isStateAssignedNo = false;
	$historyTaxSummary=$historyResult->getGetTaxResult()->getTaxSummary();
	if (count($historyTaxSummary) > 0)
	{
		$taxDetail;
		for($i=0;$i<count($historyTaxSummary);$i++)
		{
			$taxDetail=$historyTaxSummary[$i];
			if ($taxDetail->getStateAssignedNo() != null && $taxDetail->getStateAssignedNo()!= "")
			{
				$this->assertEqual("060", $taxDetail->getStateAssignedNo());
				$isStateAssignedNo = true;
			}
		}
	}
	$this->assertTrue($isStateAssignedNo,"Failed to fetch State Assigned No for the given address");

	$isStateAssignedNo = false;
	$taxLines=$historyResult->getGetTaxResult()->getTaxLines();
	$taxDetails= $taxLines[0]->getTaxDetails();
	if (count($taxLines) > 0 && count($taxDetails)> 0)
	{

		for($i=0;$i<count($taxDetails);$i++)
		{
			$taxDetail=$taxDetails[$i];
			if ($taxDetail->getStateAssignedNo() != null && $taxDetail->getStateAssignedNo() != "")
			{
				$this->assertEqual("060", $taxDetail->getStateAssignedNo());
				$isStateAssignedNo = true;
			}
		}
	}
   $this->assertTrue($isStateAssignedNo,"Failed to fetch State Assigned No for the given address");
}
	private function CompareHistory($getTaxRequest,$getTaxResult,$getTaxHistoryResult)
	{
		//global $getTaxResult,$getTaxHistoryRequest,$getTaxRequest,$getTaxHistoryResult;

		$historyRequest = $getTaxHistoryResult->getGetTaxRequest();
		$historyResult = $getTaxHistoryResult->getGetTaxResult();

		$this->assertEqual($getTaxRequest->getCompanyCode(), $historyRequest->getCompanyCode());
		$this->assertEqual($getTaxRequest->getDiscount(), $historyRequest->getDiscount());
		$this->assertEqual($getTaxRequest->getDocCode(), $historyRequest->getDocCode());
		$this->assertEqual($getTaxRequest->getDocDate(), $historyRequest->getDocDate());
		$this->assertEqual($getTaxRequest->getDocType(), $historyRequest->getDocType());
		$this->assertEqual($getTaxRequest->getExemptionNo(),$historyRequest->getExemptionNo());
		$this->assertEqual($getTaxRequest->getCustomerCode(),$historyRequest->getCustomerCode());
		$this->assertEqual($getTaxRequest->getCustomerUsageType(),$historyRequest->getCustomerUsageType());
		$this->assertEqual($getTaxRequest->getExchangeRate(), $historyRequest->getExchangeRate());
		$this->assertEqual($getTaxRequest->getExchangeRateEffDate(),$historyRequest->getExchangeRateEffDate());

		$this->assertEqual($getTaxResult->getDocCode(),$historyResult->getDocCode());
		$this->assertEqual($getTaxResult->getDocDate(), $historyResult->getDocDate());
		$this->assertEqual($getTaxResult->getDocType(), $historyResult->getDocType());
		$this->assertEqual($getTaxResult->getDocStatus(), $historyResult->getDocStatus());
		$this->assertEqual(new DateTime($getTaxResult->getTimestamp()), new DateTime($historyResult->getTimestamp()));
		$this->assertEqual($getTaxResult->getTotalAmount(), $historyResult->getTotalAmount());
		$this->assertEqual($getTaxResult->getTotalTaxable(), $historyResult->getTotalTaxable());
		$this->assertEqual($getTaxResult->getTotalTax(), $historyResult->getTotalTax());
		$this->assertEqual($getTaxResult->getTotalTaxCalculated(), $historyResult->getTotalTaxCalculated());


		$this->assertEqual(count($getTaxRequest->getLines()),count($historyRequest->getLines()));
		for ($i=0;$i< count($getTaxRequest->getLines());$i++)
		{

			$requestLine =$getTaxRequest->getLines();
			$requestLine=$requestLine[$i];

			$historyLine = $historyRequest->getLine($requestLine->getNo());
			$this->assertNotNull($historyLine);
			$this->assertEqual($requestLine->getNo(), $historyLine->getNo());
			$this->assertEqual($requestLine->getAmount(), $historyLine->getAmount());
			$this->assertEqual($requestLine->getItemCode(),$historyLine->getItemCode());
		}

		//TaxResult.TaxSummary
		for($i=0;$i<count($getTaxResult->getTaxSummary());$i++)
		{
			$resultTaxDetail=$getTaxResult->getTaxSummary();
			$resultTaxDetail=$resultTaxDetail[$i];

			$historyResultTaxDetail=$this->FindTaxDetail($resultTaxDetail->getJurisType(), $resultTaxDetail->getJurisCode(), $historyResult->getTaxSummary());
			$this->assertNotNull($historyResultTaxDetail);

			$this->assertEqual($resultTaxDetail->getCountry(), $historyResultTaxDetail->getCountry());
			$this->assertEqual($resultTaxDetail->getRegion(), $historyResultTaxDetail->getRegion());
			$this->assertEqual($resultTaxDetail->getJurisType(), $historyResultTaxDetail->getJurisType());
			$this->assertEqual($resultTaxDetail->getJurisCode(), $historyResultTaxDetail->getJurisCode());
			$this->assertEqual($resultTaxDetail->getTaxType(), $historyResultTaxDetail->getTaxType());
			$this->assertEqual($resultTaxDetail->getBase(), $historyResultTaxDetail->getBase());
			$this->assertEqual($resultTaxDetail->getTaxable(), $historyResultTaxDetail->getTaxable());
			$this->assertEqual($resultTaxDetail->getRate(),$historyResultTaxDetail->getRate());
			$this->assertEqual($resultTaxDetail->getTax(), $historyResultTaxDetail->getTax());
			$this->assertEqual($resultTaxDetail->getTaxCalculated(), $historyResultTaxDetail->getTaxCalculated());
			$this->assertEqual($resultTaxDetail->getNonTaxable(), $historyResultTaxDetail->getNonTaxable());
			$this->assertEqual($resultTaxDetail->getExemption(), $historyResultTaxDetail->getExemption());
			$this->assertEqual($resultTaxDetail->getJurisName(), $historyResultTaxDetail->getJurisName());
			$this->assertEqual($resultTaxDetail->getTaxName(), $historyResultTaxDetail->getTaxName());
			$this->assertEqual($resultTaxDetail->getTaxAuthorityType(), $historyResultTaxDetail->getTaxAuthorityType());
			$this->assertEqual($resultTaxDetail->getTaxGroup(), $historyResultTaxDetail->getTaxGroup());
		}

		//GetTaxResult.TaxLine
		$this->assertEqual(count($getTaxResult->getTaxLines()), count($historyResult->getTaxLines()));
		for ($i=0;$i<count($getTaxResult->getTaxLines());$i++)
		{

			$resultLine=$getTaxResult->getTaxLines();
			$resultLine=$resultLine[$i];

			$historyResultLine = $historyResult->getTaxLine($resultLine->getNo());

			$this->assertNotNull($historyResultLine);

			$this->assertEqual($resultLine->getNo(), $historyResultLine->getNo());
			$this->assertEqual($resultLine->getTaxable(),$historyResultLine->getTaxable());
			$this->assertEqual($resultLine->getRate(), $historyResultLine->getRate());
			$this->assertEqual($resultLine->getTax(), $historyResultLine->getTax());
			$this->assertEqual($resultLine->getTaxCode(), $historyResultLine->getTaxCode());
			$this->assertEqual($resultLine->getTaxCalculated(),$historyResultLine->getTaxCalculated());
			$this->assertEqual(new DateTime($resultLine->getReportingDate()),new DateTime($historyResultLine->getReportingDate()));
			$this->assertEqual($resultLine->getAccountingMethod(),$historyResultLine->getAccountingMethod());

			// TODO: Addresses


			// Line details
			$this->assertEqual(count($resultLine->getTaxDetails()), count($historyResultLine->getTaxDetails()));
			for ($j=0;$j < count($resultLine->getTaxDetails());$j++)
			{
				$resultDetail =  $resultLine->getTaxDetails();
				$resultDetail=$resultDetail[$j];

				$historyDetail = $this->FindTaxDetail($resultDetail->getJurisType(), $resultDetail->getJurisCode(), $historyResultLine->getTaxDetails());
				$this->assertNotNull($historyDetail);

				$this->assertEqual($resultDetail->getTaxType(), $historyDetail->getTaxType());
				$this->assertEqual($resultDetail->getBase(), $historyDetail->getBase());
				$this->assertEqual($resultDetail->getJurisCode(), $historyDetail->getJurisCode());
				$this->assertEqual($resultDetail->getJurisType(), $historyDetail->getJurisType());
				$this->assertEqual($resultDetail->getRate(), $historyDetail->getRate());
				$this->assertEqual($resultDetail->getTax(), $historyDetail->getTax());
			}
		}

	}
	private function CreateTaxRequest($docCode)
	{


		$request=new GetTaxRequest();

		//Set origin Address
		$origin = new Address();
		$origin->setLine1("Avalara");
		$origin->setLine2("900 winslow way");
		$origin->setLine3("Suite 100");
		$origin->setCity("Bainbridge Island");
		$origin->setRegion("WA");
		$origin->setPostalCode("98110-1896");
		$origin->setCountry("USA");
		$request->setOriginAddress($origin);

		//Set destination address
		$destination=  new Address();
		$destination->setLine1("3130 Elliott");
		$destination->setCity("Seattle");
		$destination->setRegion("WA");
		$destination->setPostalCode("98121");
		$destination->setCountry("USA");
		$request->setDestinationAddress($destination);

		//Set line
		$line1 = new Line();
		$line1->setNo ("1");                  //string  // line Number of invoice
		$line1->setItemCode("SKU123");            //string
		$line1->setDescription("Invoice Calculated From PHP SDK");         //string
		$line1->setTaxCode("");             //string
		$line1->setQty(1.0);                 //decimal
		$line1->setAmount(1000.00);              //decimal // TotalAmmount
		$line1->setDiscounted(false);          //boolean
		$line1->setRevAcct("");             //string
		$line1->setRef1("");                //string
		$line1->setRef2("");                //string
		$line1->setExemptionNo("");         //string
		$line1->setCustomerUsageType("");   //string


		$line2 = new Line();
		$line2->setNo ("2");                  //string  // line Number of invoice
		$line2->setItemCode("SKU124");            //string
		$line2->setDescription("Invoice Calculated From PHP SDK");         //string
		$line2->setTaxCode("");             //string
		$line2->setQty(1.0);                 //decimal
		$line2->setAmount(10.00);              //decimal // TotalAmmount
		$line2->setDiscounted(false);          //boolean
		$line2->setRevAcct("");             //string
		$line2->setRef1("");                //string
		$line2->setRef2("");                //string
		$line2->setExemptionNo("");         //string
		$line2->setCustomerUsageType("");   //string

		//$request->setLines(array ($line1,$line2));
		//Changed to object as it is not working in PHP 7
		$lineObject = new stdClass();
		$lineObject->Line = array ($line1,$line2);
		$request->setLines($lineObject);

		$request->setCompanyCode('DEFAULT');         // Your Company Code From the Dashboard
		$request->setDocType(DocumentType::$SalesInvoice);   	// Only supported types are SalesInvoice or SalesOrder

		//$dateTime=new DateTime();
		//$docCode= "PHP".date_format($dateTime,"dmyGis");
		$request->setDocCode($docCode);             //    invoice number

		$dateTime=new DateTime();
		$docDate=date_format($dateTime,"Y-m-d");
		//$request->setDocDate("2008-01-24");           //date
		$request->setDocDate($docDate);           //date
		$request->setSalespersonCode("");             // string Optional
		$request->setCustomerCode("Cust123");        //string Required
		$request->setCustomerUsageType("");   //string   Entity Usage
		$request->setDiscount(0.00);            //decimal
		$request->setPurchaseOrderNo("");     //string Optional
		$request->setExemptionNo("");         //string   if not using ECMS which keys on customer code

		$request->setDetailLevel(DetailLevel::$Diagnostic);         //Summary or Document or Line or Tax or Diagnostic

		$request->setReferenceCode("Reference");       //string Optional
		$request->setLocationCode("");        //string Optional - aka outlet id for tax forms



		return $request;
	}
	private function CreateTaxRequestForTaxOverride($docCode)
	{
		$request = new GetTaxRequest();

		$request->setCompanyCode("DEFAULT");
		$request->setDocCode($docCode);
		$request->setDocType(DocumentType::$SalesInvoice);

		$dateTime=new DateTime();
		$docDate=date_format($dateTime,"Y-m-d");
		$request->setDocDate($docDate);
		$request->setCustomerCode("TaxSvcTest");
		$request->setSalespersonCode("");
		$request->setDetailLevel(DetailLevel::$Tax);

		$origin = new Address();
		$origin->setAddressCode("Origin");
		$origin->setCity("Denver");
		$origin->setRegion("CO");
		$origin->setPostalCode("80216-1022");
		$origin->setCountry("USA");
		$request->setOriginAddress($origin);

		$destination = new Address();
		$destination->setAddressCode("Dest");
		$destination->setLine1("11051 S Parker Rd");
		$destination->setCity("Parker");
		$destination->setRegion("CO");
		$destination->setPostalCode("80134-7441");
		$destination->setCountry("USA");
		$request->setDestinationAddress($destination);

		$line = new Line();
		$line->setNo("1");
		$line->setQty(1);
		$line->setAmount(1000);

		$taxOverride = new TaxOverride();
		$taxOverride->setTaxOverrideType(TaxOverrideType::$TaxAmount);
		$taxOverride->setTaxAmount(5);
		$dateTime=new DateTime();
		$taxOverride->setTaxDate(date_format($dateTime,"Y-m-d"));
		$taxOverride->setReason("Return");
		$line->setTaxOverride($taxOverride);

		$request->setLines(array ($line));

		return $request;
	}
	// Function for Test Tax Override Type
	private function CreateTaxRequestForTaxOverrideType($docCode){
		$request = new GetTaxRequest();

		$request->setCompanyCode("DEFAULT");
		$request->setDocCode($docCode);
		$request->setDocType(DocumentType::$PurchaseOrder);

		$dateTime=new DateTime();
		$docDate=date_format($dateTime,"Y-m-d");
		$request->setDocDate($docDate);
		$request->setCustomerCode("TaxSvcTest");
		$request->setSalespersonCode("");
		$request->setDetailLevel(DetailLevel::$Tax);

		$origin = new Address();
		$origin->setAddressCode("Origin");
		$origin->setCity("Denver");
		$origin->setRegion("CO");
		$origin->setPostalCode("80216-1022");
		$origin->setCountry("USA");
		$request->setOriginAddress($origin);

		$destination = new Address();
		$destination->setAddressCode("Dest");
		$destination->setLine1("11051 S Parker Rd");
		$destination->setCity("Parker");
		$destination->setRegion("CO");
		$destination->setPostalCode("80134-7441");
		$destination->setCountry("USA");
		$request->setDestinationAddress($destination);

		$line = new Line();
		$line->setNo("1");
		$line->setQty(1);
		$line->setAmount(0);

		$taxOverride = new TaxOverride();
		$taxOverride->setTaxOverrideType(TaxOverrideType::$AccruedTaxAmount);
		$taxOverride->setTaxAmount(21.1);
		$taxOverride->setReason("Accrued");
		$line->setTaxOverride($taxOverride);

		$request->setLines(array ($line));

		return $request;
	}
	//Function for Document Type
	private function CreateTaxRequestDocType($docType)
	{
		$request=new GetTaxRequest();

		//Set origin Address
		$origin = new Address();
		$origin->setLine1("Avalara");
		$origin->setLine2("900 winslow way");
		$origin->setLine3("Suite 100");
		$origin->setCity("Bainbridge Island");
		$origin->setRegion("WA");
		$origin->setPostalCode("98110-1896");
		$origin->setCountry("USA");
		$request->setOriginAddress($origin);

		//Set destination address
		$destination=  new Address();
		$destination->setLine1("3130 Elliott");
		$destination->setCity("Seattle");
		$destination->setRegion("WA");
		$destination->setPostalCode("98121");
		$destination->setCountry("USA");
		$request->setDestinationAddress($destination);

		//Set line
		$line1 = new Line();
		$line1->setNo ("1");                  //string  // line Number of invoice
		$line1->setItemCode("INV123");            //string
		$line1->setQty(1.0);                 //decimal
		$line1->setAmount(1000.00);              //decimal // TotalAmmount

		$line2 = new Line();
		$line2->setNo ("2");                  //string  // line Number of invoice
		$line2->setItemCode("INV124");            //string
		$line2->setQty(1.0);                 //decimal
		$line2->setAmount(10.00);              //decimal // TotalAmmount

		$request->setLines(array ($line1,$line2));

		$request->setCompanyCode('DEFAULT');         // Your Company Code From the Dashboard
		$request->setDocCode("DocTypeTest");
		$request->setDocType($docType);
		$request->setDocDate(date_format(new DateTime(),"Y-m-d"));
		$request->setCustomerCode("TaxSvcTest");        //string Required
		$request->setSalespersonCode("");             // string Optional
		$request->setDetailLevel(DetailLevel::$Diagnostic);         //Summary or Document or Line or Tax or Diagnostic


	return $request;
}

private function CreateTaxRequestForBINo($bino)
{
	$request=new GetTaxRequest();

	//Set origin Address
	$origin = new Address();
	$origin->setLine1("Avalara");
	$origin->setLine2("900 winslow way");
	$origin->setLine3("Suite 100");
	$origin->setCity("Bainbridge Island");
	$origin->setRegion("WA");
	$origin->setPostalCode("98110-1896");
	$origin->setCountry("USA");
	$request->setOriginAddress($origin);

	//Set destination address
	$destination=  new Address();
	$destination->setLine1("3130 Elliott");
	$destination->setCity("Seattle");
	$destination->setRegion("WA");
	$destination->setPostalCode("98121");
	$destination->setCountry("USA");
	$request->setDestinationAddress($destination);

	//Set line
	$line = new Line();
	$line->setNo ("1");                  //string  // line Number of invoice
	$line->setBusinessIdentificationNo("LL123");
	$line->setItemCode("Item123");            //string
	$line->setQty(1.0);                 //decimal
	$line->setAmount(1010.00);

	$request->setLines(array ($line));

	$request->setCompanyCode('DEFAULT');         // Your Company Code From the Dashboard
	$request->setDocCode("DocTypeTest");
	$request->setBusinessIdentificationNo($bino);
	$request->setDocDate(date_format(new DateTime(),"Y-m-d"));
	$request->setCustomerCode("TaxSvcTest");        //string Required
	$request->setSalespersonCode("");             // string Optional
	$request->setDetailLevel(DetailLevel::$Tax);         //Summary or Document or Line or Tax or Diagnostic

		return $request;
	}
	private function waitForTaxHistory($getTaxHistoryRequest)
	{
		global $client;
		$result = null;

		$retryCount = 0;
		do
		{
			$result = $client->getTaxHistory($getTaxHistoryRequest);
			if($result->getResultCode() == SeverityLevel::$Error)
			{
				$messages= $result->getMessages();
				$message=$messages[0];
			}
			if ($result->getResultCode() != SeverityLevel::$Error ||
				($result->getMessages() != null && $message->getName()=="DocumentNotFoundError"))
			{
				break;
			}
			try
			{
				sleep(1); //time in seconds
			}
			catch (Exception $ex)
			{
				// Ignore
			}
			$retryCount=$retryCount+1;
		} while (retryCount <= 10);

		return $result;
	}
	private function FindTaxDetail($jurisdictionType, $jurisdictionCode, $taxDetails)
	{
		$match = null;

		for ($i=0;$i<count($taxDetails);$i++)
		{
			$detail = $taxDetails[$i];
			if ( ($detail->getJurisType()==$jurisdictionType) && ($detail->getJurisCode()==$jurisdictionCode))
			{
				$match = $detail;
				break;
			}
		}

		return $match;
	}

}
?>