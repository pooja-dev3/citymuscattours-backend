
<?php
/*
PHP Version - 5.3.5 (Curl function enabled)
Apache Version - 2.2.17
Tested using - WampServer Version - 2.1
Operating System - Windows XP Professional Service Pack 2 and Windows 7
*/

$url = "https://securepgtest.fssnet.co.in/pgway/servlet/IVRXMLServlet";

/* declaring all the required input variables with values*/
$TranportalID = "<id>20082</id>";
$TranportalPWD = "<password>fssnet678</password>";
$strcard = "<card>5176351000000293</card>";
$strcvv = "<cvv2>123</cvv2>";
$strexpyear = "<expyear>2015</expyear>";
$strexpmonth = "<expmonth>12</expmonth>";
$strexpday = "<expday>12</expday>";
$strmember = "<member>Card Holder Name</member>";
$straction = "<action>1</action>";
$stramt = "<amt>1.00</amt>";
$strcurrency = "<currencycode>356</currencycode>";
$strtrackid = "<trackid>TRC001</trackid>";
$strinitudf1 = "<udf1></udf1>";
$strinitudf2 = "<udf2></udf2>";
$strinitudf3 = "<udf3></udf3>";
$strinitudf4 = "<udf4></udf4>";
$strinitudf5 = "<udf5></udf5>";
$strPhoneFormat = "<npc356chphoneidformat>D</npc356chphoneidformat>";     //Mandatory- "D" for Domestic Number and "I" for International Number
$strPhoneNumber = "<npc356chphoneid>1234567890</npc356chphoneid>";        //Non-Mandatory - Pass customer phone number here
$strShopChannel = "<npc356shopchannel>IVR</npc356shopchannel>";           //Mandatory - Should always be "IVR"
$strAuthChannel = "<npc356availauthchannel>IVR</npc356availauthchannel>"; //Mandatory - Should always be "IVR"
$strPaReqChannel = "<npc356pareqchannel>DIRECT</npc356pareqchannel>";     //Mandatory - Should always be "DIRECT"
$strMESite = "<merchantreciepturl>http://www.merchantdemo.com</merchantreciepturl>"; //Mandatory - Merchant site, if not available put http://www.merchantdemo.com

/*
Merchant should do validations for each field ensuring mandatory fields are not blank
If mandatory fields are blank, merchant will not receive "result" parameter in response
*/
$param =$TranportalID.$TranportalPWD.$strcard.$strcvv.$strexpyear.$strexpmonth.$strexpday.$strmember.$straction.$stramt.$strcurrency.$strtrackid.$strinitudf1.$strinitudf2.$strinitudf3.$strinitudf4.$strinitudf5.$strPhoneFormat.$strPhoneNumber.$strShopChannel.$strAuthChannel.$strPaReqChannel.$strMESite;

/* Method for posting starts here */
$ch = curl_init() or die(curl_error()); 
curl_setopt($ch, CURLOPT_POST,1); 
curl_setopt($ch, CURLOPT_POSTFIELDS,$param); 
curl_setopt($ch, CURLOPT_URL,$url); 
curl_setopt($ch, CURLOPT_PORT, 443);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0); 
$data1=curl_exec($ch) or die(curl_error());
curl_close($ch); 
$response = $data1;
/* Method for posting end here */
//var_dump($response); // to print the raw response from Payment Gateway.

/* Method to get tag name and values starts here*/
$conXML = "<xmltg>" .$response."</xmltg>";
function getTextBetweenTags($conXML, $tagname) 
{     
	$pattern = "/<$tagname ?.*>([\w\W]*?)<\/$tagname>/";
	preg_match($pattern, $conXML, $matches);     
	return $matches[1]; 
}  
	$str = $conXML; 
	$getResult =getTextBetweenTags($str, "result");
	$strPaymentID = getTextBetweenTags($str, "payid");
	$strPaymentIDRequest = "<paymentId>".$strPaymentID."</paymentId>";

/* Method to get tag name and values ends here*/

if($getResult=="ENROLLED")
{
	//print_r("HELLO ENROLLED");
	/* declaring all the required input variables with values if the response was ENROLLED*/
	$trackID = getTextBetweenTags($str, "trackid");
	$strudf1 = getTextBetweenTags($str, "udf1");
	$strudf2 = getTextBetweenTags($str, "udf2");
	$strudf3 = getTextBetweenTags($str, "udf3");
	$strudf4 = getTextBetweenTags($str, "udf4");
	$strudf5 = getTextBetweenTags($str, "udf5");
	$stramt = getTextBetweenTags($str, "amt");
	$strauthDataNameRes = getTextBetweenTags($str,"authDataName");
	$strauthDataLength = getTextBetweenTags($str,"authDataLength");
	$strauthDataType = getTextBetweenTags($str,"authDataType");
	$strauthDataLabel = getTextBetweenTags($str,"authDataLabel");
	$strauthDataPrompt = getTextBetweenTags($str,"authDataPrompt");
	$authDataEncryptMandatory = getTextBetweenTags($str,"authDataEncryptMandatory");
	$strPAReq = getTextBetweenTags($str,"PAReq");
	$strurl = getTextBetweenTags($str,"url");
	$strauthDataEncryptMandatory = "<authDataEncryptMandatory>"."FALSE"."</authDataEncryptMandatory>"; //Mandatory - should always be "FALSE"
	$srtauthDataNameReq = "<authDataName>".$strauthDataNameRes."</authDataName>";
	$strivrPasswordStatus = "<ivrPasswordStatus>Y</ivrPasswordStatus>";
	$OTPValueFromCustomer = "123456"; // This is OTP collected from the customer
	$strivrPassword = "<ivrPassword>".$OTPValueFromCustomer."</ivrPassword>"; // OTP is passed in this parameter
	$strPAReqRequest = "<PAReq>".$strPAReq."</PAReq>"; // Mandatory - should be the same value that is received from Payment Gateway
	$stracurlreq= "<acsurl>".$strurl."</acsurl>";
	$strtrackID = "<trackid>".$trackID."</trackid>";
	$strUDF1Req = "<udf1>".$strudf1."</udf1>";
	$strUDF2Req = "<udf2>".$strudf2."</udf2>";
	$strUDF3Req = "<udf3>".$strudf3."</udf3>";
	$strUDF4Req = "<udf4>".$strudf4."</udf4>";
	$strUDF5Req = "<udf5>".$strudf5."</udf5>";
	
	/* Below is string that is generated for Enrolled Cards*/
	$RequestER = $TranportalID.$TranportalPWD.$strPAReqRequest.$strPaymentIDRequest.$stracurlreq.$strtrackID.$strUDF1Req.$strauthDataEncryptMandatory.$srtauthDataNameReq.$strivrPasswordStatus.$strivrPassword;
	//print_r($RequestER);
	
	$url1 ="https://securepgtest.fssnet.co.in/pgway/servlet/IVRMPIPayerAuthenticationServlet";

	$ch2 = curl_init() or die(curl_error()); 
	curl_setopt($ch2, CURLOPT_POST,1); 
	curl_setopt($ch2, CURLOPT_POSTFIELDS,$RequestER); 
	curl_setopt($ch2, CURLOPT_URL,$url1); 
	curl_setopt($ch2, CURLOPT_PORT, 443);
	curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST,0); 
	curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER,0); 
	$data2=curl_exec($ch2) or die(curl_error());
	curl_close($ch2); 
	$response2 = $data2;
	
	//var_dump($response2); // to print the raw response from Payment Gateway.


	/* Method to get tag name and values recevied -starts here*/
	$conXML2 = "<xmltg>" .$response2."</xmltg>";
	$a2 = json_decode(json_encode((array) simplexml_load_string($conXML2)),1);
	echo "<pre>";
	$getResResult2 = $a2['result'];
	$authvalue= $a2['auth'];
	$refid = $a2['ref'];
	$avrvalue = $a2['avr'];
	$postdate=$a2['postdate'];
	$tranid=$a2['tranid'];
	$trackid=$a2['trackid'];
	$amt=$a2['amt'];

	print_r("Enrolled card Final txn status is ".$getResResult2. " Track ID ".$trackid. " Ref Id ".$refid);
	echo "</pre>";

	/* Method to get tag name and values ends here*/
}
elseif($getResult=="NOT ENROLLED")
{
	//print_r("HELLO NOT ENROLLED");


	$url3 = "https://securepgtest.fssnet.co.in/pgway/servlet/TranPortalXMLServlet";
	$param3 =$TranportalID.$TranportalPWD.$strcard.$strcvv.$strexpyear.$strexpmonth.$strexpday.$strmember.$straction.$stramt.$strcurrency.$strtrackid.$strinitudf1.$strinitudf2.$strinitudf3.$strinitudf4.$strinitudf5.$strPhoneFormat.$strPhoneNumber.$strShopChannel.$strAuthChannel.$strPaReqChannel.$strMESite.$strPaymentIDRequest;

	$ch3 = curl_init() or die(curl_error()); 
	curl_setopt($ch3, CURLOPT_POST,1); 
	curl_setopt($ch3, CURLOPT_POSTFIELDS,$param3); 
	curl_setopt($ch3, CURLOPT_URL,$url3); 
	curl_setopt($ch3, CURLOPT_PORT, 443);
	curl_setopt($ch3, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch3, CURLOPT_SSL_VERIFYHOST,0); 
	curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER,0); 
	$data3=curl_exec($ch3) or die(curl_error());
	curl_close($ch3); 
	$response3 = $data3;

	//var_dump($response3); //// to print the response from Payment Gateway.

	/* Method to get tag name and values recevied -starts here*/
	$conXML3 = "<xmltg>" .$response3."</xmltg>";
	$a3 = json_decode(json_encode((array) simplexml_load_string($conXML3)),1);
	echo "<pre>";
	
	$getResResult3 = $a3['result'];
	//$authvalue= $a3['auth'];
	//$refid = $a3['ref'];
	//$avrvalue = $a3['avr'];
	//$postdate=$a3['postdate'];
	//$tranid=$a3['tranid'];
	$trackid=$a3['trackid'];
	$amt=$a3['amt'];

	print_r("Not Enrolled Final txn status is ".$getResResult3. " Track ID ".$trackid. " Ref Id ".$refid);
	echo "</pre>";

	/* Method to get tag name and values ends here*/
}

else
{
	print_r("TRANSACTION DECLINED DUE TO ERROR AT INITIAL STAGE- CANNOT PROCEED FURTHER");
}


?>
