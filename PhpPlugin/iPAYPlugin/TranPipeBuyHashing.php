
<<HTML>
<HEAD>
<META name="GENERATOR" content="IBM WebSphere Studio">
<LINK href="css/main.css" type=text/css rel=stylesheet>
<TITLE>Canon - Tranportal(Hashing)</TITLE>
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

java_require('http://localhost:8080/e24Pipe.jar');
$myObj = new Java("com.fss.pg.plugin.e24Pipe");$name ='TEST';
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
if(trim($myObj->performTransaction())!=0) 
{
	echo "Error sending TranPipe Request: ".$myObj->getDebugMsg();
}else{
?>

<BODY class="bg">
<br>
<TABLE align=center border=1  bordercolor=black><tr><td>

<TABLE align=center border=0  bordercolor=black>
		<TR>
			<TD colspan="2" align="center">
				<FONT size="4"><B>Transaction Details</B></FONT>
			</TD>
		</TR>
		<TR>
			<TD>Transaction Status</TD>
			<TD>&nbsp;&nbsp;<b><font size="2" color="red"><?php echo $myObj->getResult();?></font></b></TD>
		</TR>
	<TR>
			<TD>Transaction Date</TD>
			<TD>&nbsp;&nbsp;<?php echo $myObj->getDate();?></TD>
		</TR>
		<TR>
			<TD>Transaction Reference ID</TD>
			<TD>&nbsp;&nbsp;<?php echo $myObj->getRef();?></TD>
		</TR>
		<TR>
			<TD>Mrch Track ID</TD>
			<TD>&nbsp;&nbsp;<?php echo $myObj->getTrackId();?></TD>
		</TR>
		<TR>
			<TD><b>Transaction ID</b></TD>
			<TD>&nbsp;&nbsp;<?php echo $myObj->getTransId();?></TD>
		</TR>
		<TR>
			<TD>Transaction Amt</TD>
			<TD>&nbsp;&nbsp;<?php echo $myObj->getAmt();?></TD>
		</TR>
		<TR>
			<TD>UDF5</TD>
			<TD>&nbsp;&nbsp;<?php echo $myObj->getUdf5();?></TD> 
		</TR>
		<TR>
			<TD>Payment ID</TD>
			<TD>&nbsp;&nbsp;<?php echo $myObj->getPaymentId();?></TD>
			</TR>
		</table>
		</td></tr>
		</table>
<br>
		<TABLE align=center><tr></tr> <tr></tr><tr></tr>
		<TR>
		<td><FONT size=2 color="BLUE"><A href="TranPipeIndex.php">Tranportal Transaction</A></FONT></td>
		</tr>
	<tr><td>
	<FONT size=2 color="BLUE"><A href="HostedPaymentIndex.php">Hosted Transaction</A></FONT>
	</td></tr></table>


</BODY>
<?php
}
?>
</HTML>
