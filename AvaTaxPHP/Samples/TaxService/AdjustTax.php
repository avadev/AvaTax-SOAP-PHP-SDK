<?php
require('../../AvaTax4PHP/AvaTax.php');     // include in all Avalara Scripts
require('../Credentials.php');     // where service URL, account, license key are set

$client = new TaxServiceSoap('Development');
$STDIN = fopen('php://stdin','r');
echo "Enter Company Code: ";
$companyCode = rtrim(fgets($STDIN));
fclose($STDIN);

echo "Calculating Tax...\n";
$invoice = CalcTax($client,$companyCode);
echo "\nAdjusting Invoice ".$invoice."...\n";
$invoice = AdjustInvoice($client,$invoice,$companyCode);
        
function AdjustInvoice($taxSvcSoapClient,$invoiceNumber,$companyCode)
{
// first find the document to adjust
     $request= new GetTaxHistoryRequest();
     $request->setCompanyCode($companyCode);	// Dashboard Company Code
     $request->setDocType(DocumentType::$SalesInvoice);
     $request->setDetailLevel(DetailLevel::$Tax);	// we need fully populated GetTaxRequest
     $request->setDocCode($invoiceNumber);

     try
     {
          $result = $taxSvcSoapClient->getTaxHistory($request);
          if($result->getResultCode() != SeverityLevel::$Success)
          {
               foreach($result->getMessages() as $msg)
               {
                    echo $msg->getName().": ".$msg->getSummary()."\n";
               }
          } else {
               $adjreq = new AdjustTaxRequest();
               $adjreq->setAdjustmentReason(8);
               $adjreq->setAdjustmentDescription("Because I Said So");
/*
AdjustMentReason Codes: 0 Not Adjusted, 1 Sourcing Issue, 2 Reconciled with General Ledger,
     3 Exemption Certificate Applied, 4 Price or Quantity Adjusted, 5 Item Returned,
     6 Item Exchanged, 7 Bad Debt, 8 Other (Explain - Must provide AdjustmentDescription)
*/
               $gtreq = $result->getGetTaxRequest();
               $STDIN = fopen('php://stdin','r');
               echo "Enter New Amount for Line 1: ";
               $amt = rtrim(fgets($STDIN));
               $gtreq->getLine("1")->setAmount($amt);
               $adjreq->setGetTaxRequest($gtreq);
				echo "Calling AdjustTax\n";
               $adjres = $taxSvcSoapClient->AdjustTax($adjreq);
               if($adjres->getResultCode() != SeverityLevel::$Success)
               {
                    echo "AdjustTax returned ".$adjres->getResultCode()."\n";
                    foreach($result->getMessages() as $msg)
                    {
                         echo $msg->getName().": ".$msg->getSummary()."\n";
                    }
               } else {
                    echo "Invoice ".$adjres->getDocCode()." Adjusted";
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
}

function CalcTax($taxSvcSoapClient,$companyCode)
{
     $request= new GetTaxRequest();
     $origin = new Address();
     $destination=  new Address();
     $line1 = new Line();
          
     $origin->setLine1("435 Ericksen Ave NE");
     $origin->setLine2("Suite 200");
     $origin->setCity("Bainbridge Island");
     $origin->setRegion("WA");
     $origin->setPostalCode("98110-1896");

     $destination->setLine1("900 Winslow Way");
     $destination->setLine2("Suite 200");
     $destination->setCity("Bainbridge Island");
     $destination->setRegion("WA");
     $destination->setPostalCode("98110");
     
     $request->setOriginAddress($origin);           //Address
     $request->setDestinationAddress     ($destination);     //Address
     
     
     $request->setCompanyCode($companyCode);         // Your Company Code From the Dashboard
     $request->setDocType(DocumentType::$SalesInvoice);        // Only supported types are SalesInvoice or SalesOrder

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


     $request->setDetailLevel(DetailLevel::$Document);
     $request->setCommit("true");     // commit upon tax calc

     $request->setReferenceCode("");       //string Optional     
     $request->setLocationCode("");        //string Optional - aka outlet id for tax forms
     

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
     $getTaxResult = $taxSvcSoapClient->getTax($request);
     echo 'GetTax Result: '. $getTaxResult->getResultCode()."\n";

     if ($getTaxResult->getResultCode() == SeverityLevel::$Success)
        {
          echo "DocCode: ".$request->getDocCode()."\n";
          echo "TotalAmount: ".$getTaxResult->getTotalAmount()."\n";
          echo "TotalTax: ".$getTaxResult->getTotalTax()."\n";
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
          echo $taxSvcSoapClient->__getLastRequest()."\n";
          echo $taxSvcSoapClient->__getLastResponse()."\n";
     }
     return $request->getDocCode();
}

?>
