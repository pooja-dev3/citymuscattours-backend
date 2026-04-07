<HTML>
<HEAD>
<META name="GENERATOR" content="IBM WebSphere Studio">
<LINK href="css/main.css" type=text/css rel=stylesheet>
<TITLE>Canon - Tranportal</TITLE>
</HEAD>


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
$instituteId=$_POST['InstituteID'];
$myFile = "fsstranportal.txt";
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

java_require('http://localhost:8080/iPayPipe.jar');
$myObj = new Java("com.fss.pg.plugin.iPayPipe");
$name ='TEST';
$myObj->setTrackId(trim($_POST['trckId']));
$myObj->setAlias(trim($aliasName));
$myObj->setResourcePath(trim($resourcePath));
$myObj->setAction(trim($action));
$myObj->setAmt(trim($amount));
$myObj->setCurrency(trim($currency));
$myObj->setCard($pan);
$myObj->setCvv2($cvv);
$myObj->setExpMonth($expmm);
$myObj->setExpYear($expyy);
$myObj->setMember($name);
$myObj->setType("C");
$myObj->setTransId($comment);
$myObj->setUdf1($_POST['udf1']);
$myObj->setUdf2($_POST['udf2']);
$myObj->setUdf3($_POST['udf3']);
$myObj->setUdf4($_POST['udf4']);
$myObj->setUdf5($_POST['udf5']);
$myObj->setKeystorePath(trim($resourcePath));
$myObj->setResponseURL(trim($receiptURL));
$myObj->setErrorURL(trim($errorURL));
$resval=0;
if(trim($myObj->performTransactionHTTP())!=0) 
{
	$url = $myObj->getErrorURL()."?result=".$myObj->getError();
}
else
{
	$url =trim($myObj->getWebAddress());
}
echo "<meta http-equiv='refresh' content='0;url=$url'>";
?>

</HTML>