<?php
	require('../../AvaTax4PHP/AvaTax.php');	// include in all Avalara Scripts
	require('../Credentials.php');	// where service URL, account, license key are set

	$client = new TaxServiceSoap('Development');
	$request= new GetTaxRequest();
					
	//Add Origin Address
	$origin = new Address();
	$origin->setLine1("435 Ericksen Ave NE");
	$origin->setLine2("Suite 200");
	$origin->setCity("Bainbridge Island");
	$origin->setRegion("WA");
	$origin->setPostalCode("98110-1896");
	$request->setOriginAddress($origin);	      //Address

	//Add Destination address
	$destination=  new Address();
	$destination->setLine1("900 Winslow Way");
	$destination->setLine2("Suite 200");
	$destination->setCity("Bainbridge Island");
	$destination->setRegion("WA");
	$destination->setPostalCode("98110");
	$request->setDestinationAddress	($destination);     //Address
	
	
	$request->setCompanyCode('DEFAULT');         // Your Company Code From the Dashboard
    $request->setDocType(DocumentType::$SalesInvoice);   	// Only supported types are SalesInvoice or SalesOrder

	$dateTime=new DateTime();
	$docCode= "PHPSample".date_format($dateTime,"dmyGis");
    $request->setDocCode($docCode);             //    invoice number
    $request->setDocDate(date_format($dateTime,"Y-m-d"));           //date
    $request->setSalespersonCode("");             // string Optional
    $request->setCustomerCode("Cust123");        //string Required
    $request->setCustomerUsageType("");   //string   Entity Usage
    $request->setDiscount(0.00);            //decimal
    $request->setPurchaseOrderNo("");     //string Optional
    $request->setExemptionNo("");         //string   if not using ECMS which keys on customer code
    $request->setDetailLevel(DetailLevel::$Tax);         //Summary or Document or Line or Tax or Diagnostic
    
    $request->setLocationCode("");        //string Optional - aka outlet id for tax forms

	//Add line
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
	$request->setLines(array ($line1));               //array
	
	try
	{
		$getTaxResult = $client->getTax($request);
		echo 'GetTax is: '. $getTaxResult->getResultCode()."\n";
	
		if ($getTaxResult->getResultCode() == SeverityLevel::$Success)
	        {
			echo "DocCode: ".$request->getDocCode()."\n";			
	        echo "TotalAmount: ".$getTaxResult->getTotalAmount()."\n";
	        echo "TotalTax: ".$getTaxResult->getTotalTax()."\n";
			foreach($getTaxResult->getTaxLines() as $ctl)
			{
				echo "     Line: ".$ctl->getNo()." Tax: ".$ctl->getTax()." TaxCode: ".$ctl->getTaxCode()."\n";
	
				foreach($ctl->getTaxDetails() as $ctd)
				{
					echo "          Juris Type: ".$ctd->getJurisType()."; Juris Name: ".$ctd->getJurisName()."; Rate: ".$ctd->getRate()."; Amt: ".$ctd->getTax()."\n";
				}
				echo"\n";
			}
		}
	        else
	        {
			foreach($getTaxResult->getMessages() as $msg)
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
