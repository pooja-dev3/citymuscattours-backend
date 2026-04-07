<?php
/*
******************************************************************
			* COMPANY    - FSS Pvt. Ltd.
*****************************************************************

Name of the Program : Tranportal UMI Sample Pages
Page Description    : Allows Merchant to connect Payment Gateway and send request
Request parameters  : TranporatID,TranportalPassword,Action,Amount,CardNumber,CVV,ExpiryDate,
					  ExpiryMonth,ExpiryYear,CardHolderName,Currency,TrackID,TransID,UDF1-UDF5,
					  Transaction ID
Response parameters : Result,Amount,Track ID, Trasnaction ID, Reference ID, UDF1-UDF5, Auth Code, AVR
Values from Session : No
Values to Session   : No
Created by          : FSS Payment Gateway Team
Created On          : 19-04-2011
Version             : Version 1.0
******************************************************************************
PHP Version - 5.3.5 (Curl function enabled)
Apache Version - 2.2.17
Tested using - WampServer Version - 2.1
Operating System - Windows XP Professional Service Pack 2 and Windows 7 
*******************************************************************************
*/

/* This is Payment Gateway Test URL where merchant sends request. This is test enviornment URL, 
production URL will be different and will be shared by Bank during production movement */
$url = "https://securepgtest.fssnet.co.in/pgway/servlet/TranPortalXMLServlet";

/***************IMPORTANT INFORMATION***************************
This document is provided by Financial Software and System Pvt Ltd on the basis 
that you will treat it as private and confidential.

Data used in examples and sample data files are intended to be fictional and any 
resemblance to real persons or entities is entirely coincidental.

This example assumes that a form has been posted to this example with the required 
fields which comes from Merchant backend system. The example then processes the 
command which sends request to Payment Gateway and recevies from Payment Gateway and
displays the receipt or error to a HTML page in the users web browser. */

// @ Start-> PG parameters have to set here - //

/* to pass Tranportal ID provided by the bank to merchant. Tranportal ID is sensitive information
of merchant from the bank, merchant MUST ensure that Tranportal ID is never passed to customer 
browser by any means. Merchant MUST ensure that Tranportal ID is stored in secure environment & 
securely at merchant end. Tranportal Id is referred as id. Tranportal ID for test and production will be 
different, please contact bank for test and production Tranportal ID*/
// Here XXXXXX refers to tranportal id of the respective merchant terminal	
$TranportalID = "<id>XXXXXX</id>"; //Mandatory

/* to pass Tranportal password provided by the bank to merchant. Tranportal password is sensitive 
information of merchant from the bank, merchant MUST ensure that Tranportal password is never passed 
to customer browser by any means. Merchant MUST ensure that Tranportal password is stored in secure 
environment & securely at merchant end. Tranportal password is referred as password. Tranportal 
password for test and production will be different, please contact bank for test and production
Tranportal password */
// Here XXXXXX refers to tranportal password of the respective merchant terminal
$TranportalPWD = "<password>XXXXXX</password>";  //Mandatory

// Here XXXXXXXXXXXXXXXX refers card number
$strcard = "<card>XXXXXXXXXXXXXXXX</card>";		//Mandatory for Action code "1" & "4"

// Here XXX is card verification value (CVV)
$strcvv = "<cvv2>XXX</cvv2>";					//Mandatory for Action code "1" & "4"

// Here YYYY is card expiry year in YYYY format
$strexpyear = "<expyear>YYYY</expyear>";		//Mandatory for Action code "1" & "4"

// Here MM is card expiry month 
$strexpmonth = "<expmonth>MM</expmonth>";		//Mandatory for Action code "1" & "4"

// Here ABCDE is Customer name. 	
$strmember = "<member>Card Holder Name</member>";  //Mandatory

/* Action Code of the transaction, this refers to type of transaction. Action Code 1 stands of 
Purchase transaction and action code 4 stands for Authorization (pre-auth). Merchant should 
confirm from Bank action code enabled for the merchant by the bank*/  
$straction = "<action>1</action>";  //Mandatory

/* Action Code List - Action Code 1 is for Purchase
	                      Action Code 2 is for Refund
						  Action Code 4 is for Authorization (Pre-auth)
						  Action Code 5 is for Capture (completion of pre-auth)
						  Action code 8 is for Inquiry */


/* Transaction Amount that will be send to payment gateway by merchant for processing
NOTE - Merchant MUST ensure amount is sent from merchant back-end system like database
and not from customer browser. In below sample AMT is hard-coded, merchant to pass 
trasnaction amount here. */
// Here XX.XX is trasnaction amount inclduing decimal point
$stramt = "<amt>XX.XX</amt>";  //Mandatory

/* Currency code of the transaction. By default INR i.e. 356 is configured. If merchant wishes 
to do multiple currency code transaction, merchant needs to check with bank team on the available 
currency code */
$strcurrency = "<currencycode>356</currencycode>";  //Mandatory

/* To pass the merchant track id, in below sample merchant track id is hard-coded. Merchant
MUST pass his transaction ID (track ID) in this parameter. Track Id passed here should be 
from merchant backend system like database and not from customer browser. For support 
transactions like Refund and Capture merchant should pass the original track id */
$strtrackid = "<trackid></trackid>";  //Mandatory

/* transid field is mandatory for support txn like Refund and Capture. merchant should pass original
transaction id of payment gateway for refund / capture transaction. Refer intergration document
for more help. For Purhase and Auth transaction this field should be LEFT BLANK */
$strtransID = "<transid></transid>";  //Optional

/* User Defined Fileds as per Merchant or bank requirment. Merchant MUST ensure merchant 
merchant is not passing junk values OR CRLF in any of the UDF. In below sample UDF values 
are not utilized */
$strinitudf1 = "<udf1></udf1>";
$strinitudf2 = "<udf2></udf2>";
$strinitudf3 = "<udf3></udf3>";
$strinitudf4 = "<udf4></udf4>";
$strinitudf5 = "<udf5></udf5>";

// @ End-> PG parameters have to set here - //


/* Merchant should do validations for each field ensuring mandatory fields are not blank
If mandatory fields are blank, merchant will not receive "result" parameter in response */

/* Now merchant sets all the inputs in one string for passing to the Payment Gateway URL */	
$param =$TranportalID.$TranportalPWD.$strcard.$strcvv.$strexpyear.$strexpmonth.$strmember.$straction.$stramt.$strcurrency.$strtrackid.$strinitudf1.$strinitudf2.$strinitudf3.$strinitudf4.$strinitudf5.$strtransID;
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
//var_dump($response);
$testXML = "<xmltg>" . $response . "</xmltg>";
$xmlSTR = simplexml_load_string( $testXML,null,true);
$getResult = $xmlSTR-> result;
$getAuthCode = $xmlSTR->auth;
$getTrackID=$xmlSTR->trackid;
$getTranid=$xmlSTR->tranid;
$getamt=$xmlSTR->amt;
$getUDF1 = $xmlSTR->udf1;

// Reading & Printing the PG response from below -
print_r("txn status " . $getResult.".".$getAuthCode.".".$getTrackID.".".$getTranid.".".$getUDF1);

?>
