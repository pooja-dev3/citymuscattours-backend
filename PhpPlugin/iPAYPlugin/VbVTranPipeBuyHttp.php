<?php 

$amount =$_POST['AMOUNT'];
$resourcePath="";
$aliasName="";
$currency="";
$language="";
$action=$_POST['action'];
$receiptURL="";
$errorURL="";
$phpjavabridgeurl="";
$tranportalId="";
$comment = $_POST['comment'];
$pan    = $_POST['pan'];
$cvv    = $_POST['cvv'];
$expmm  = $_POST['expmm'];
$expyy  = $_POST['expyy'];
$currcd=$_POST['currcd'];
$instituteId=$_POST['InstituteID'];
$myFile = "vbvtranportal.txt";
$file = fopen($myFile, 'r');
while(!feof($file))
	{
			$lineData=fgets($file);
		if (substr($lineData,0, strrpos($lineData,"="))=="tran.currency")
			$currency	= substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="consumer.language") 
			$language	=	substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="merchant.receiptURL")
			$receiptURL	=	substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="merchant.errorURL") 
			$errorURL	=	substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="gateway.resource.path") 
			$resourcePath		=	 substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="gateway.terminal.tranportalid") 
			$tranportalId		=	 substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="gateway.terminal.alias") 
			$aliasName		=	 substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="php.java.bridge.url") 
			$phpjavabridgeurl		=	 substr($lineData,strrpos($lineData,"=")+1);
	}

java_require('http://localhost:8080/e24Pipe.jar');
$myObj = new Java("com.fss.pg.plugin.e24Pipe");
$name ='Test';

$rnd = substr(number_format(time() * rand(),0,'',''),0,10);
$path = trim($resourcePath);
$myObj->setResponseURL(trim($receiptURL));
$myObj->setErrorURL(trim($errorURL));
$myObj->setAlias(trim($aliasName));
$myObj->setResourcePath($path);
$myObj->setKeystorePath($path);
$myObj->setAmt(trim($amount));
$_SESSION['amount']=$amount; 
$myObj->setCurrency(trim($currcd));
$myObj->setMember($name);
$myObj->setAction(trim($action));
$myObj->setTrackId($rnd);
$myObj->setCvv2($cvv);
$myObj->setExpMonth($expmm);
$myObj->setExpYear($expyy);
$myObj->setExpDay($expmm);  
$myObj->setCard($pan);
$myObj->setUdf1("UDF1");
$myObj->setUdf2("UDF2");
$myObj->setUdf3("UDF3");
$myObj->setUdf4("UDF4");
$myObj->setUdf5($_POST['udf5']);
$url = "";

if(trim($myObj->performVbVTransaction()) != 0) 
{
	$url = $myObj->getErrorURL()."?result=".$myObj->getError();
}
else
{
	$url = trim($myObj->getWebAddress());
}
echo "<meta http-equiv='refresh' content='0;url=$url'>";

?>
