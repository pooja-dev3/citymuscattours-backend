<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<META name="GENERATOR" content="IBM WebSphere Studio">
<TITLE>Canon - Tranportal 3D Secure</TITLE>
</HEAD>
</HTML>
<?php 
//$amount =$_POST['AMOUNT'];
$resourcePath="";
$aliasName="";
$currency="";
$language="";
//$action=$_POST['action'];
$receiptURL="";
$errorURL="";
$phpjavabridgeurl="";
$tranportalId="";
$termurl="";
//$comment = $_POST['comment'];
//$pan    = $_POST['pan'];
//$cvv    = $_POST['cvv'];
//$expmm  = $_POST['expmm'];
//$expyy  = $_POST['expyy'];
//$instituteId=$_POST['InstituteID'];
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
		if (substr($lineData,0, strrpos($lineData,"="))=="term.url") 
			$termurl		=	 substr($lineData,strrpos($lineData,"=")+1);
	}

java_require('http://localhost:8080/e24Pipe.jar');
$myObj = new Java("com.fss.pg.plugin.e24Pipe");//$amount=1.00;
$PARes =null;
//$_SESSION['amount']=trim($_POST['AMOUNT']); 
$path = trim($resourcePath);
if( isset($_POST['PaRes'])){
$PARes =$_POST['PaRes'];
}
if(isset($_POST['name']) && isset($_POST['currcd'])&& isset($_POST['expmm'])&& isset($_POST['expyy'])&& isset($_POST['pan'])&& isset($_POST['cvv'])&& isset($_POST['action']))
{
$name = $_POST['name'];
$currcd=$_POST['currcd'];
$expmm  = $_POST['expmm'];
$expyy  = $_POST['expyy'];
$rnd = substr(number_format(time() * rand(),0,'',''),0,10);
$pan    = $_POST['pan'];
$cvv    = $_POST['cvv'];
$action = $_POST['action'];
}

if($PARes == null) 
{
if(isset($_POST['name']) && isset($_POST['currcd'])&& isset($_POST['expmm'])&& isset($_POST['expyy'])&& isset($_POST['pan'])&& isset($_POST['cvv'])&& isset($_POST['action']))
{
	$myObj->setAlias(trim($aliasName));
	$myObj->setResourcePath($path);
	$myObj->setKeystorePath($path);
	$myObj->setAmt($_POST['AMOUNT']);
	$_SESSION['amount']=trim($_POST['AMOUNT']); 
	$myObj->setCurrency(trim($currency));
	$myObj->setMember('Test');
	$myObj->setAction(trim($_POST['action']));
	$myObj->setTrackId($rnd);
	$myObj->setCvv2(trim($cvv));
	$myObj->setExpMonth(trim($_POST['expmm']));
	$myObj->setExpYear(trim($_POST['expyy']));
	$myObj->setExpDay(trim($_POST['expmm']));  
	$myObj->setCard(trim($_POST['pan']));
	$myObj->setUdf1("New Plugin New Plugin");
	$myObj->setUdf2("New Plugin New Plugin");
	$myObj->setUdf3("New Plugin New Plugin");
	$myObj->setUdf4("New Plugin New Plugin");
	$myObj->setUdf5($_POST['udf5']);	
	$myObj->performVETransaction();
	}
	if($myObj->getResult() != null) 
	{	
		if(trim($myObj->getResult())=='ENROLLED') 
		{			
			if($myObj->getIvrFlag()!=null && trim($myObj->getIvrFlag())=='IVR'){
			
				$myObj->setIvrPassword("password");
				$myObj->setIvrPasswordStatus("Y");
				$myObj->setItpauthiden("");
				$myObj->setItpauthtran("");
				$i = trim($myObj->performPAReqTransaction());			
				if($i==0){
?>
<HTML>
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
			<TD>&nbsp;&nbsp;<b><font size="2" color="red"><?php echo  $myObj->getResult();?></font></b></TD>
		</TR>
	<TR>
			<TD>Transaction Date</TD>
			<TD>&nbsp;&nbsp;<?php echo  $myObj->getDate();?></TD>
		</TR>
		<TR>
			<TD>Transaction Reference ID</TD>
			<TD>&nbsp;&nbsp;<?php echo  $myObj->getRef();?></TD>
		</TR>
		<TR>
			<TD>Mrch Track ID</TD>
			<TD>&nbsp;&nbsp;<?php echo  $myObj->getTrackId();?></TD>
		</TR>
		<TR>
			<TD><b>Transaction ID</b></TD>
			<TD>&nbsp;&nbsp;<?php echo  $myObj->getTransId();?></TD>
		</TR>
		<TR>
			<TD>Transaction Amt</TD>
			<TD>&nbsp;&nbsp;<?php echo  $myObj->getAmt();?></TD>
		</TR>
		<TR>
			<TD>UDF5</TD>
			<TD>&nbsp;&nbsp;<?php echo  $myObj->getUdf5();?></TD> 
		</TR>
		<TR>
			<TD>Payment ID</TD>
			<TD>&nbsp;&nbsp;<?php echo  $myObj->getPaymentId();?></TD>
			</TR>
		</table>
		</td></tr>
		</table>
<br>
		<TABLE align=center><tr></tr> <tr></tr><tr></tr>
		<TR>
		<td><FONT size=2 color="BLUE"><A href="VbVTranPipeIndex.php">Tranportal Transaction</A></FONT></td>
		</tr>
	<tr><td>
	<FONT size=2 color="BLUE"><A href="HostedPaymentIndex.php">Hosted Transaction</A></FONT>
	</td></tr></table>


</BODY>
</HTML>
	<?php
	}
				
			} else {
			?>
			<HTML>
			<BODY OnLoad="OnLoadEvent();">
				<form name="form1" action="<?php echo trim($myObj->getAcsurl());?>" method="post">
					<input type="hidden" name="PaReq" value="<?php echo trim($myObj->getPareq());?>">
					<input type="hidden" id="payi" name="MD" value="<?php echo trim($myObj->getPaymentId());?>">
					<?php
						$termURL = trim($termurl);
					?>
				<input type="hidden" name="TermUrl" value="<?php echo $termURL; ?>">
				</form>
			<script language="JavaScript">
			function OnLoadEvent() 
			{
			var pid=document.getElementById("payi").value
			document.form1.submit();
			}
			</script>
			</BODY>
			</HTML>
			<?php
			}
		} 
		else 
		{  
			if(trim($myObj->getResult())=='NOT ENROLLED') 
			{
				$myObj->setAlias(trim($aliasName));
				$myObj->setResourcePath($path);
				$myObj->setKeystorePath($path);
				if ($myObj->getUdf1()==null) $myObj->setUdf1(" ");
     			if ($myObj->getUdf2()==null) $myObj->setUdf2(" "); 
				if ($myObj->getUdf3()==null) $myObj->setUdf3(" ");
				if ($myObj->getUdf4()==null) $myObj->setUdf4(" ");
				if ($myObj->getUdf5()==null) $myObj->setUdf5(" ");
				$myObj->performTransaction();
				
			} 
			?>
			<HTML>
			<BODY>
			<TABLE align=center border=1  bordercolor=black><tr><td>

			<TABLE align=center border=0  bordercolor=black>
					<TR>
						<TD colspan="2" align="center">
							<FONT size="4"><B>Transaction Details   </B></FONT>
						</TD>
					</TR>
					<TR>
						<TD colspan="2" align="center">
							<HR>
						</TD>
					</TR>
					<TR>
						<TD>Transaction Status</TD>
						<TD><b><?php echo  $myObj->getResult();?></b></TD>
					</TR>

					<TR>
						<TD> Transaction Id </TD>
						<TD><?php echo  $myObj->getTransId();?></TD>
					</TR>
					<TR>
						<TD> Reference Id </TD>
						<TD><?php echo  $myObj->getRef();?></TD>
					</TR>
					<tr>
						<td>TrackID</td>
						<td>
							<?php echo $myObj->getTrackId();?>
						</td>
					</tr>

					<TR>	
						<TD>Amount</TD>
						<TD><?php echo  $_SESSION['amount']?></TD>
					</TR>

					<TR>
						<TD>AUTH</TD>
						<TD><?php echo  $myObj->getAuth();?></TD>
					</TR>

					<TR>
						<TD> UDF1 </TD>
						<TD><?php echo  $myObj->getUdf1();?></TD>
					</TR>
					<TR>
						<TD> UDF2 </TD>
						<TD><?php echo  $myObj->getUdf2();?></TD>
					</TR>
					<TR>
						<TD> UDF3 </TD>
						<TD><?php echo  $myObj->getUdf3();?></TD>
					</TR>
					<TR>
						<TD> UDF4 </TD>
						<TD><?php echo  $myObj->getUdf4();?></TD>
					</TR>
					<TR>
						<TD> UDF5 </TD>
						<TD><?php echo  $myObj->getUdf5(); ?></TD>
					</TR>
					<TR>
						<TD>Error Message</TD>
						<TD><?php echo  $myObj->getError_text();?></TD>
					</TR>
					</table>

			<br>
					<TABLE align=center border=1  bordercolor=black><tr><td>

			<TABLE align=center border=0  bordercolor=black>

					<TR>
						<TD colspan="2" align="center">
							<FONT size="4"><B>Customer Shipping Details    </B></FONT>
						</TD>
					</TR>
					<TR>
						<TD colspan="2" align="center">
							<HR>
						</TD>
					</TR>
				</TABLE></td></tr></table><td></tr></table>
			<br>
					<TABLE align=center><tr></tr> <tr></tr><tr></tr>
					<TR>
					<td></td>
					</tr>
				<tr><td>
				</td></tr></table>
			</BODY>
			</HTML>
			<?php
		}    
	}
}

 else 
 {
 	if(isset($_POST['MD'])&&isset($_POST['PaRes']))
	{
	$myObj->setAlias(trim($aliasName));
	$myObj->setResourcePath($path);
	$myObj->setKeystorePath($path);	
	$myObj->setPaymentId($_POST['MD']);
	$myObj->setPares($_POST['PaRes']);
	$myObj->setUdf1("New Plugin New Plugin");
	$myObj->setUdf2("New Plugin New Plugin");
	$myObj->setUdf3("New Plugin New Plugin");
	$myObj->setUdf4("New Plugin New Plugin");
	$myObj->setUdf5("New Plugin New Plugin");
	$myObj->performPATransaction();
	}
?>
<HTML>
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
			<TD>&nbsp;&nbsp;<b><font size="2" color="red"><?php echo  $myObj->getResult();?></font></b></TD>
		</TR>
	<TR>
			<TD>Transaction Date</TD>
			<TD>&nbsp;&nbsp;<?php echo  $myObj->getDate();?></TD>
		</TR>
		<TR>
			<TD>Transaction Reference ID</TD>
			<TD>&nbsp;&nbsp;<?php echo  $myObj->getRef();?></TD>
		</TR>
		<TR>
			<TD>Mrch Track ID</TD>
			<TD>&nbsp;&nbsp;<?php echo  $myObj->getTrackId();?></TD>
		</TR>
		<TR>
			<TD><b>Transaction ID</b></TD>
			<TD>&nbsp;&nbsp;<?php echo  $myObj->getTransId();?></TD>
		</TR>
		<TR>
			<TD>Transaction Amt</TD>
			<TD>&nbsp;&nbsp;<?php echo  $myObj->getAmt();?></TD>
		</TR>
		<TR>
			<TD>UDF5</TD>
			<TD>&nbsp;&nbsp;<?php echo  $myObj->getUdf5();?></TD> 
		</TR>
		<TR>
			<TD>Payment ID</TD>
			<TD>&nbsp;&nbsp;<?php echo  $myObj->getPaymentId();?></TD>
			</TR>
		</table>
		</td></tr>
		</table>
<br>
		<TABLE align=center><tr></tr> <tr></tr><tr></tr>
		<TR>
		<td><FONT size=2 color="BLUE"><A href="VbVTranPipeIndex.php">Tranportal Transaction</A></FONT></td>
		</tr>
	<tr><td>
	<FONT size=2 color="BLUE"><A href="HostedPaymentIndex.php">Hosted Transaction</A></FONT>
	</td></tr></table>


</BODY>
</HTML>
<?php
 }
?>