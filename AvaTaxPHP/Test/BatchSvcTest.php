<?php
require_once('../simpletest/autorun.php');
//require('../AvaTax4PHP/classes/BatchSvc/AvaTaxBatchSvc.php');	// include in all Avalara Scripts
require('../AvaTax4PHP/AvaTax.php');	// include in all Avalara Scripts
require('Credentials.php');	// where service URL, account, license key are set
//require('../AvaTax4PHP/TestScript.php');	// where service URL, account, license key are set

class BatchSvcTest extends UnitTestCase
{
	private $client;
	
	public function __construct()
	{
	    global $client;
	    
	    $client=new BatchSvc('Test'); 
	    
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
		$result = $client->IsAuthorized("BatchSave");
		
		$this->assertEqual(SeverityLevel::$Success, $result->getResultCode());
	            
	}
	function testBatch()
    {
    	global $client;
        
    	$batchID = 0;
        $uploadedFilePath = "";
        $path = "";
                       	        	
        $batch = new Batch();
        $batch->setBatchStatusId("Waiting");
        $batch->setBatchTypeId("ItemImport");
            
        //ToDo: Define how to set companyId 
        $batch->setCompanyId(31115);
            
        $batch->setName("ItemImportTest.xls");
        $batch->setOptions("Add File");                
            
        $batchFile = new BatchFile();
        $batchFile->setName($batch->getName());
        $batchFile->setContentType("application/vnd.ms-excel");
           
        $filename = "c:\\Batch\\ItemImportTest.xls";			
		$contents = $this->ReadContents($filename);			
			
		$batchFile->setFilePath($filename);			
		$batchFile->setSize(strlen($contents));
		$batchFile->setContent($contents);
	                
		$batchFiles = array($batchFile);                
	    $batch->setFiles($batchFiles);
	        
	    //Batch Save
	    $batchSaveResult = $client->BatchSave($batch);
	        
	    $this->assertEqual(SeverityLevel::$Success,$batchSaveResult->getResultCode());
	    $request = new FetchRequest();
	    $request->setFields("*,Files.Content");
	    $request->setFilters("BatchId=".$batchSaveResult->getBatchId());
	    $batchID = $batchSaveResult->getBatchId();
	        
	    //Batch Fetch
	    $fetchResult = $client->BatchFetch($request);	        
	    $this->assertEqual(SeverityLevel::$Success,$fetchResult->getResultCode());
        	
	    foreach ($fetchResult->getBatches() as $tempBatch)
	    {
	        		            
	        $this->assertEqual($batchSaveResult->getBatchId(),$tempBatch->getBatchId());
	        $this->assertEqual($batch->getBatchTypeId(),$tempBatch->getBatchTypeId());	            
	        $this->assertEqual($batch->getName(),$tempBatch->getName());
	        $this->assertEqual($batch->getOptions(),$tempBatch->getOptions());	
	        
	        foreach($tempBatch->getFiles() as $file)
	        {
	        	$tempContent= $file->getContent();
	        	$this->assertEqual($contents,$tempContent);
	        }
	         
        }                 
                                            
        //BatchDelete
        $delRequest = new DeleteRequest();
        $delRequest->setFilters("BatchId=".$batchID);
        $delResult = $client->BatchDelete($delRequest);
        $this->assertEqual(SeverityLevel::$Success,$delResult->getResultCode());
                                   
    }
		function testBatchFile()
        {
            global $client;
        	$batchID = 0;
            $uploadedFilePath = "";
            
            
            //BatchSave
            $batch = new Batch();
            $batch->setBatchStatusId("Waiting");
            $batch->setBatchTypeId("ItemImport");
            $batch->setCompanyId(31115);
            $batch->setName("ItemImportTest.xls");
            $batch->setOptions("Add File");
                
            $batchFile = new BatchFile();
            $batchFile->setName("ItemImportTest.xls");
                               
            $filename = "c:\\Batch\\ItemImportTest.xls";
			$contents = $this->ReadContents($filename); 											
			$batchFile->setFilePath($filename);			
			$batchFile->setSize(strlen($contents));
			$batchFile->setContent($contents);													        	                                              
			$batchFiles = array($batchFile);                	        	                
            $batch->setFiles($batchFiles);
                
            $batchSaveResult = $client->BatchSave($batch);
                
            $this->assertEqual(SeverityLevel::$Success,$batchSaveResult->getResultCode());                
            $batchID = $batchSaveResult->getBatchId();

            //BatchFileSave- save BatchFile in that BAtchFile only
            $file = new BatchFile();
            //Set BatchId for recently stored batch
            $file->setBatchId($batchID);
            $file->setName("Error.xls");
            $file->setSize(100);
            $file->setContentType("content type");
                                
            $filename = "c:\\Batch\\Errors.xls";
            $contents=$this->ReadContents($filename);
            $file->setContent($contents);
                                               
            $fileSaveResult = $client->BatchFileSave($file);
            $this->assertEqual(SeverityLevel::$Success,$fileSaveResult->getResultCode());                

            //BatchFileFetch
            $fetchRequest = new FetchRequest();
            $fetchRequest->setFields("*,Content");
            $fetchRequest->setFilters("BatchFileId=".$fileSaveResult->getBatchFileId());
            $batchFileFetchResult = $client->BatchFileFetch($fetchRequest);
            $this->assertEqual(SeverityLevel::$Success,$batchFileFetchResult->getResultCode());
            foreach ($batchFileFetchResult->getBatchFiles() as $batchFile)
            {            	                    
                $this->assertEqual("Error.xls",$batchFile->getName());
            }
            
           
                
             //BatchFile delete
             $delRequest = new DeleteRequest();
             $delRequest->setFilters("BatchFileId=".$fileSaveResult->getBatchFileId());
             $delRequest->setMaxCount(1);
             $delResult = $client->BatchFileDelete($delRequest);
             $this->assertEqual(SeverityLevel::$Success,$delResult->getResultCode());
             
             //Batch Delete
             $delRequest = new DeleteRequest();
	         $delRequest->setFilters("BatchId=".$batchID);
	         $delResult = $client->BatchDelete($delRequest);
	         $this->assertEqual(SeverityLevel::$Success,$delResult->getResultCode());
                        
        }
		
        public function ReadContents($filePath)
        {
        	$handle = fopen($filePath, "r");
			$contents = fread($handle, filesize($filePath));
			fclose($handle);			
			return $contents;        	        	
        }	
}

?>