<?php
java_require('http://localhost:8080/e24Pipe.jar');
$myObj = new Java("com.fss.pg.plugin.e24Pipe");
$name = $_POST['name'];
$currcd=$_POST['currcd'];
$expmm  = $_POST['expmm'];
$expyy  = $_POST['expyy'];
$rnd = substr(number_format(time() * rand(),0,'',''),0,10);
$pan    = $_POST['pan'];
$cvv    = $_POST['cvv'];
$action = $_POST['action'];
$amount=1.00;
$path = "D:\\iPay\\Resource\\133\\cgn";
$myObj->setResponseURL("http://localhost/PHPSite/TranPipeResult.php");
$myObj->setErrorURL("http://localhost/PHPSite/TranPipeResult.php");
$myObj->setAlias("phptesting");
$myObj->setResourcePath($path);
$myObj->setKeystorePath($path);
$myObj->setAmt("1.00");
$_SESSION['amount']=$amount; 
$myObj->setCurrency($currcd);
$myObj->setMember('Test');
$myObj->setAction($action);
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
$myObj->setUdf5("UDF5");
$myObj->setResponseURL("http://10.44.71.154:80/PHPSite/TranPipeResult.php");
$url = "";

$myObj->setIvrFlag("IVR");
$myObj->setIvrPassword("password");
$myObj->setIvrPasswordStatus("Y");
$myObj->setItpauthiden("");
$myObj->setItpauthtran("");


$myObj->setNpc356availauthchannel("SMS");
$myObj->setNpc356chphoneid("23232323");
$myObj->setNpc356chphoneidformat("D");
$myObj->setNpc356itpcredential("erer454rerer34ere5");
$myObj->setNpc356pareqchannel("DIRECT");
$myObj->setNpc356shopchannel("IVR");

if(trim($myObj->performVbVTransaction())!= 0) 
{
	$url = "http://10.44.71.154/PHPSite/IVRVbVTranPipeResult.php?result="+$myObj->getError();
}
else
{
	$url = $myObj->getWebAddress();
}
echo "<meta http-equiv='refresh' content='0;url=$url'>";
?>
