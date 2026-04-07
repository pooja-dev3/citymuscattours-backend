
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<META name="GENERATOR" content="IBM WebSphere Studio">
<TITLE>Canon - Tranportal 3D Secure(Hashing)</TITLE>
</HEAD>
</HTML>
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
$amount= 1.00;
$PARes = isset($_POST['PaRes']);
$path = "D:\\iPay\\Resource\\133\\cgn";
	
if($PARes == null) 
{
		
	$myObj->setAlias("phptesting");
	$myObj->setResourcePath($path);
	$myObj->setKeystorePath($path);
	$myObj->setAmt("1.00");
	$_SESSION['amount']=$amount; 
	$myObj->setCurrency($currcd);
	$myObj->setMember("Test");
	$myObj->setAction($action);
	$myObj->setTrackId($rnd);
	$myObj->setCvv2($cvv);
	$myObj->setExpMonth($expmm);
	$myObj->setExpYear($expyy);
	$myObj->setExpDay($expmm);  
	$myObj->setCard($pan);
	$myObj->setUdf1("New Plugin New Plugin");
	$myObj->setUdf2("New Plugin New Plugin");
	$myObj->setUdf3("New Plugin New Plugin");
	$myObj->setUdf4("New Plugin New Plugin");
	$myObj->setUdf5("New Plugin New Plugin");	
	$myObj->setIvrFlag("IVR");
	$myObj->setNpc356availauthchannel("SMS");
	$myObj->setNpc356chphoneid("23232323");
	$myObj->setNpc356chphoneidformat("D");
	$myObj->setNpc356itpcredential("erer454rerer34ere5");
	$myObj->setNpc356pareqchannel("DIRECT");
	$myObj->setNpc356shopchannel("IVR");	
	
	$myObj->performIVRVETransaction();
	
	if(trim($myObj->getResult() != null)) 
	{	
		if(trim($myObj->getResult())=='ENROLLED') 
		{		
			if($myObj->getIvrFlag()!=null && trim($myObj->getIvrFlag())=='IVR'){			
				$myObj->setIvrPassword("password");
				$myObj->setIvrPasswordStatus("Y");
				$myObj->setItpauthiden("");
				$myObj->setItpauthtran("");
				$i = $myObj->performPAReqTransaction();				
				
				if(i==0){
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
			<TD>&nbsp;&nbsp;<b><font size="2" color="red"><?PHP echo $myObj->getResult();?></font></b></TD>
		</TR>
	<TR>
			<TD>Transaction Date</TD>
			<TD>&nbsp;&nbsp;<?PHP echo $myObj->getDate();?></TD>
		</TR>
		<TR>
			<TD>Transaction Reference ID</TD>
			<TD>&nbsp;&nbsp;<?PHP echo $myObj->getRef();?></TD>
		</TR>
		<TR>
			<TD>Mrch Track ID</TD>
			<TD>&nbsp;&nbsp;<?PHP echo $myObj->getTrackId();?></TD>
		</TR>
		<TR>
			<TD><b>Transaction ID</b></TD>
			<TD>&nbsp;&nbsp;<?PHP echo $myObj->getTransId();?></TD>
		</TR>
		<TR>
			<TD>Transaction Amt</TD>
			<TD>&nbsp;&nbsp;<?PHP echo $myObj->getAmt();?></TD>
		</TR>
		<TR>
			<TD>UDF5</TD>
			<TD>&nbsp;&nbsp;<?PHP echo $myObj->getUdf5();?></TD> 
		</TR>
		<TR>
			<TD>Payment ID</TD>
			<TD>&nbsp;&nbsp;<?PHP echo $myObj->getPaymentId();?></TD>
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
				<form name="form1" action="<?PHP echo $myObj->getAcsurl(); ?>" method="post">
					<input type="hidden" name="PaReq" value="<?PHP echo$myObj->getPareq();?>">
					<input type="hidden" name="MD" value="<?PHP echo$myObj->getPaymentId();?>">
					<?php
						$termURL = "http://10.44.71.154/PHPSite/VbVTranPipeBuy.php";
					?>
				<input type="hidden" name="TermUrl" value="<?PHP echo $termURL ?>">
				</form>
			<script language="JavaScript">
			function OnLoadEvent() 
			{
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
				$myObj->setAlias("phptesting");
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
						<TD><b><?PHP echo $myObj->getResult();?></b></TD>
					</TR>

					<TR>
						<TD> Transaction Id </TD>
						<TD><?PHP echo $myObj->getTransId();?></TD>
					</TR>
					<TR>
						<TD> Reference Id </TD>
						<TD><?PHP echo $myObj->getRef();?></TD>
					</TR>
					<tr>
						<td>TrackID</td>
						<td>
							<?PHP echo$myObj->getTrackId();?>
						</td>
					</tr>

					<TR>	
						<TD>Amount</TD>
						<TD><?PHP echo $_SESSION['amount'];?></TD>
					</TR>

					<TR>
						<TD>AUTH</TD>
						<TD><?PHP echo $myObj->getAuth();?></TD>
					</TR>

					<TR>
						<TD> UDF1 </TD>
						<TD><?PHP echo $myObj->getUdf1();?></TD>
					</TR>
					<TR>
						<TD> UDF2 </TD>
						<TD><?PHP echo $myObj->getUdf2(); ?></TD>
					</TR>
					<TR>
						<TD> UDF3 </TD>
						<TD><?PHP echo $myObj->getUdf3(); ?></TD>
					</TR>
					<TR>
						<TD> UDF4 </TD>
						<TD><?PHP echo $myObj->getUdf4(); ?></TD>
					</TR>
					<TR>
						<TD> UDF5 </TD>
						<TD><?PHP echo $myObj->getUdf5(); ?></TD>
					</TR>
					<TR>
						<TD>Error Message</TD>
						<TD><?PHP echo $myObj->getError_text();?></TD>
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
	$myObj->setAlias("phptesting");
	$myObj->setResourcePath($path);
	$myObj->setKeystorePath($path);	
	$myObj->setPaymentId(isset($_POST['MD']));
	$myObj->setPares(isset($_POST['PaRes']));
	$myObj->setUdf1("New Plugin New Plugin");
	$myObj->setUdf2("New Plugin New Plugin");
	$myObj->setUdf3("New Plugin New Plugin");
	$myObj->setUdf4("New Plugin New Plugin");
	$myObj->setUdf5("New Plugin New Plugin");
	$myObj->performPATransaction();
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
			<TD>&nbsp;&nbsp;<b><font size="2" color="red"><?PHP echo $myObj->getResult();?></font></b></TD>
		</TR>
	<TR>
			<TD>Transaction Date</TD>
			<TD>&nbsp;&nbsp;<?PHP echo $myObj->getDate();?></TD>
		</TR>
		<TR>
			<TD>Transaction Reference ID</TD>
			<TD>&nbsp;&nbsp;<?PHP echo $myObj->getRef();?></TD>
		</TR>
		<TR>
			<TD>Mrch Track ID</TD>
			<TD>&nbsp;&nbsp;<?PHP echo $myObj->getTrackId();?></TD>
		</TR>
		<TR>
			<TD><b>Transaction ID</b></TD>
			<TD>&nbsp;&nbsp;<?PHP echo $myObj->getTransId();?></TD>
		</TR>
		<TR>
			<TD>Transaction Amt</TD>
			<TD>&nbsp;&nbsp;<?PHP echo $myObj->getAmt();?></TD>
		</TR>
		<TR>
			<TD>UDF5</TD>
			<TD>&nbsp;&nbsp;<?PHP echo $myObj->getUdf5();?></TD> 
		</TR>
		<TR>
			<TD>Payment ID</TD>
			<TD>&nbsp;&nbsp;<?PHP echo $myObj->getPaymentId();?></TD>
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
} return; ?>