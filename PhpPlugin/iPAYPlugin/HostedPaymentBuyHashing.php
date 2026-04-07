<?php session_start(); ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<META name="GENERATOR" content="IBM WebSphere Studio">
</HEAD>
</HTML>
<?php 
$amount =$_POST['AMOUNT'];
$_SESSION['amount']=$amount;
$resourcePath="";
$aliasName="";
$currency="";
$language="";
$action="";
$receiptURL="";
$errorURL="";
$phpjavabridgeurl="";
$tranportalId="";
$myFile = "fsshashing.txt";
$file = fopen($myFile, 'r');
while(!feof($file))
	{
			$lineData=fgets($file);
		if (substr($lineData,0, strrpos($lineData,"="))=="tran.currency")
			$currency	= substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="consumer.language") 
			$language	=	substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="tran.action") 
			$action	=	substr($lineData,strrpos($lineData,"=")+1);
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
java_require('http://localhost:8080/iPayPipe.jar');
$myObj = new Java("com.fss.pg.plugin.iPayPipe");

try 
{
$myObj = new Java("com.fss.plugin.iPayPipe");
$hashvalue = new Java("com.fss.plugin.Hashing");
$rnd = substr(number_format(time() * rand(),0,'',''),0,10);
$trackid = $rnd;
$_SESSION['trackid']=$trackid;
$myObj->setResourcePath(trim($resourcePath));
$myObj->setKeystorePath(trim($resourcePath));
$myObj->setAlias(trim($aliasName));
$myObj->setAction(trim($action));
$myObj->setCurrency(trim($currency));
$myObj->setLanguage(trim($language));
$myObj->setResponseURL(trim($receiptURL));
$myObj->setErrorURL(trim($errorURL));
$myObj->setAmt($amount);
$myObj->setTrackId($trackid);
$myObj->setUdf1("");
$myObj->setUdf2("");
$myObj->setUdf3("");
$myObj->setUdf4("");
$hashMess=$hashvalue->hashMethod(trim($tranportalId),$trackid,trim($amount),trim($currency),trim($action));
$myObj->setUdf5($hashMess);
 
$resval=0;
$retval=$myObj->performPaymentInitialization();
if(trim($retval)!=0) 
{
  echo("ERROR OCCURED! SEE CONSOLE FOR MORE DETAILS");
  return -1;
}
$payID = $myObj->getPaymentId();
$payURL =$myObj->getPaymentPage();
$url=$payURL. "?PaymentID=".$payID;
echo "<meta http-equiv='refresh' content='0;url=$url'>";
}
catch(Exception $e)
{
	  echo 'Message: ' .$e->getFile();
	  echo 'Message1 : ' .$e->errorMessage();
	  return false;
}
?>