
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<META name="GENERATOR" content="IBM WebSphere Studio">
</HEAD>
</HTML>
<?php
try
{

$amount =1;
$resourcePath="";
$aliasName="";
$currency="";
$language="";
$action="";
$receiptURL="";
$errorURL="";
$phpjavabridgeurl="";
$myFile = "fss.txt";
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
		if (substr($lineData,0, strrpos($lineData,"="))=="gateway.terminal.alias")
			$aliasName		=	 substr($lineData,strrpos($lineData,"=")+1);
		if (substr($lineData,0, strrpos($lineData,"="))=="php.java.bridge.url")
			$phpjavabridgeurl		=	 substr($lineData,strrpos($lineData,"=")+1);
	}


//java_require('http://localhost//CBO//phpnormal//e24Pipe.jar');
java_require('http://localhost//CBO//phpnormal//e24Pipe.jar');
//$myObj = new Java("com.fss.pg.plugin.e24Pipe");
$myObj = new Java("com.fss.plugin.e24Pipe");
echo "sucesss fully connected in java::::";
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
$myObj->setAmt($amount);
$myObj->setTrackId($trackid);
$myObj->setUdf1("");
$myObj->setUdf2("");
$myObj->setUdf3("10.44.71.168");
$myObj->setUdf4("");
$myObj->setUdf5("");
echo"resourcePath::".trim($resourcePath)."</br>";
echo"aliasName::".trim($aliasName)."</br>";
echo"action::".trim($action)."</br>";
echo"currency::".trim($currency)."</br>";
echo"language::".trim($language)."</br>";
echo"receiptURL::".trim($receiptURL)."</br>";
echo"errorURL::".trim($errorURL)."</br>";
echo"amount::".trim($amount)."</br>";
echo"trackids::".trim($trackid)."</br>";
//break;
$resval=0;
echo"before";
$retval=$myObj->performPaymentInitialization();
echo"After";

echo"Result->".$retval;
if(trim($retval)!=0)
{

  echo("ERROR OCCURED! SEE CONSOLE FOR MORE DETAILS");
  return -1;
}
echo"Paymentid->".$myObj->getPaymentId();
$payID = $myObj->getPaymentId();
$payURL =$myObj->getPaymentPage();
$url=$payURL. "?PaymentID=".$payID;
echo"PaymentID::".$url;
echo "<meta http-equiv='refresh' content='0;url=$url'>";
}
catch(Exception $e)
{

	  echo 'Message1 : ' .$e;
	  return false;
}
?>