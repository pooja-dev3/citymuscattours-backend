<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<META name="GENERATOR" content="IBM WebSphere Studio">
</HEAD>
</HTML>
<?php 
try 
{
$amount =$_POST['AMOUNT'];
$tokenflag = $_POST['tokenflag'];
$tokenNum = $_POST['tokenNum'];
$resourcePath="";
$aliasName="";
$currency="";
$language="";
$action= $_POST['ACTION'];
$receiptURL="";
$errorURL="";
$phpjavabridgeurl="";
$myFile = "fss.txt";
$file = fopen($myFile, 'r');
echo "Open file";
while(!feof($file))
	{
			$lineData=fgets($file);
		if (substr($lineData,0, strrpos($lineData,"="))=="tran.currency")
			$currency	= substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="consumer.language") 
			$language	=	substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="tran.action") 
			$action1	=	substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="merchant.receiptURL")
			$receiptURL	=	substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="merchant.errorURL") 
			$errorURL	=	substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="gateway.resource.path") 
			$resourcePath		=	 substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="gateway.terminal.alias") 
			$aliasName		=	 substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="php.java.bridge.url") 
			$phpjavabridgeurl		=	 substr($lineData,strrpos($lineData,"=")+1);
	}
//java_require('http://localhost:8080/e24Pipe.jar');
//$myObj = new Java("com.fss.pg.plugin.e24Pipe");


require_once("http://localhost:8080/JavaBridge/java/Java.inc");
#java_require('http://localhost//iPAYPlugin//iPayPipe.jar');
$myObj = new Java("com.fss.plugin.iPayPipe");

$rnd = substr(number_format(time() * rand(),0,'',''),0,10);

$trackid = $rnd;
$myObj->setResourcePath(trim($resourcePath));
$myObj->setKeystorePath(trim($resourcePath));
$myObj->setAlias(trim($aliasName));
$myObj->setAction(trim($action));
$myObj->setCurrency(trim($currency));
$myObj->setLanguage(trim($language));
$myObj->setResponseURL(trim($receiptURL));
$myObj->setErrorURL(trim($errorURL));
$myObj->setAmt(trim($amount));
$myObj->setTrackId($trackid);
$myObj->setUdf1("");
$myObj->setUdf2("");
$myObj->setUdf3("10.44.71.168");
$myObj->setUdf4("");
$myObj->setUdf5("");
$myObj->setTokenFlag(trim($tokenflag));
$myObj->setTokenNumber(trim($tokenNum));
echo"Before";
if(trim($myObj->performPaymentInitializationHTTP())!=0) 
{

  echo("ERROR OCCURED! SEE CONSOLE FOR MORE DETAILS");
  return -1;
}
else
{
echo "test";
$payID = $myObj->getPaymentId();
$payURL =$myObj->getPaymentPage();
$url=$myObj->getWebAddress();
header( 'Location:'.$url ) ;
die();
}
}
catch(Exception $e)
{	 
	echo 'Exception->' .$e;
	 echo 'Message: ' .$e->getFile();
	 echo 'Message1 : ' .$e->getCode();
	  
}

?>

