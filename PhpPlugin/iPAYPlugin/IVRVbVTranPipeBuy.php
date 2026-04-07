
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<META name="GENERATOR" content="IBM WebSphere Studio">
<TITLE>Canon - Tranportal 3D Secure</TITLE>
</HEAD>
</HTML>
<?php 
$resourcePath="";
$aliasName="";
$currency="";
$language="";
$action="";
$receiptURL="";
$errorURL="";
$phpjavabridgeurl="";
$myFile = "vbvtranportal.txt";
$file = fopen($myFile, 'r');
 
while(!feof($file))
    {
 
            $lineData=fgets($file);
        if (substr($lineData,0, strrpos($lineData,"="))=="tran.currency")
            $currency   = substr($lineData,strrpos($lineData,"=")+1);
        if (substr($lineData,0, strrpos($lineData,"="))=="consumer.language")
            $language   =   substr($lineData,strrpos($lineData,"=")+1);
        if (substr($lineData,0, strrpos($lineData,"="))=="tran.action")
            $action1 =   substr($lineData,strrpos($lineData,"=")+1);
        if (substr($lineData,0, strrpos($lineData,"="))=="merchant.receiptURL")
            $receiptURL =   substr($lineData,strrpos($lineData,"=")+1);
        if (substr($lineData,0, strrpos($lineData,"="))=="merchant.errorURL")
            $errorURL   =   substr($lineData,strrpos($lineData,"=")+1);
        if (substr($lineData,0, strrpos($lineData,"="))=="gateway.resource.path")
            $resourcePath       =    substr($lineData,strrpos($lineData,"=")+1);
        if (substr($lineData,0, strrpos($lineData,"="))=="gateway.terminal.alias")
            $aliasName      =    substr($lineData,strrpos($lineData,"=")+1);
        if (substr($lineData,0, strrpos($lineData,"="))=="php.java.bridge.url")
            $phpjavabridgeurl       =    substr($lineData,strrpos($lineData,"=")+1);
		 if (substr($lineData,0, strrpos($lineData,"="))=="tran.member")
            $cardholder       =    substr($lineData,strrpos($lineData,"=")+1);
		 if (substr($lineData,0, strrpos($lineData,"="))=="tran.amnt")
            $amnt       =    substr($lineData,strrpos($lineData,"=")+1);
		 if (substr($lineData,0, strrpos($lineData,"="))=="ivrflag")
            $ivrFlag       =    substr($lineData,strrpos($lineData,"=")+1);
		 //if (substr($lineData,0, strrpos($lineData,"="))=="udf1")
         //  $udf1       =    substr($lineData,strrpos($lineData,"=")+1);
		
    }
 
require_once("http://localhost:8080/JavaBridge/java/Java.inc");
$myObj = new Java("com.fss.plugin.iPayPipe");
//java_require('http://localhost:8080/e24Pipe.jar');
//$myObj = new Java("com.fss.pg.plugin.e24Pipe");
//$name = $_POST['name'];
$currcd=$_POST['currcd'];
$expmm  = $_POST['expmm'];
$expyy  = $_POST['expyy'];
$rnd = substr(number_format(time() * rand(),0,'',''),0,10);
$trackid = $_POST['trckId'];
$pan    = $_POST['pan'];
$cvv    = $_POST['cvv'];
$action = $_POST['action'];
$amount= $_POST['Amnt'];
$PARes = isset($_POST['PaRes']);
$path = "D:\\iPay\\Resource\\133\\cgn";
	
if($PARes == null) 
{
		
    $myObj->setAlias(trim($aliasName));
    $myObj->setResourcePath(trim($resourcePath));
    $myObj->setKeystorePath(trim($resourcePath));
    $myObj->setAmt(trim($amount));
    //$_SESSION['amount']=$amount;
    $myObj->setCurrency(trim($currency));
    $myObj->setMember(trim($cardholder));
    $myObj->setAction(trim($action));
    $myObj->setTrackId(trim($trackid));
    $myObj->setCvv2($cvv);
    $myObj->setExpMonth($expmm);
    $myObj->setExpYear($expyy);
    $myObj->setExpDay($expmm);  
    $myObj->setCard($pan);
    $myObj->setUdf1($udf1);
    $myObj->setUdf2("");
    $myObj->setUdf3("");
    $myObj->setUdf4("");
    $myObj->setUdf5("");  
    $myObj->setIvrFlag(trim($ivrFlag));
    $myObj->setTokenFlag(trim($_POST['tokenflag']));
    $myObj->setTokenNumber(trim($_POST['tokenNum']));
    $myObj->setResponseURL(trim($receiptURL));
    $myObj->setErrorURL(trim($errorURL));
    $myObj->setNpc356availauthchannel("SMS");
    $myObj->setNpc356chphoneid("23232323");
    $myObj->setNpc356chphoneidformat("D");
    $myObj->setType("D");
    $myObj->setNpc356itpcredential("erer454rerer34ere5");
    $myObj->setNpc356pareqchannel("DIRECT");
    $myObj->setNpc356shopchannel("IVR"); 
    $test = $myObj->getPaymentId();
    echo $test;
	$myObj->performIVRVETransaction();   
	
	if(trim($myObj->getResult() != null)) 
	{	
        if(trim($myObj->getResult())=='pass'){
                if($myObj->getUdf1()!=null && trim($myObj->getUdf1())=='OTP'){
                    ?>
                    <html>
							<BODY class="bg">
								<br>
								<form method="post" action="IVROTPTranPipeBuy.php">
									<TABLE align=center border=1  bordercolor=black>
										<tr>
											<td>
												<TABLE align=center border=0  bordercolor=black>
													<TR>
														<TD colspan="2" align="center">
															<FONT size="4"><B>OTP</B></FONT>
														</TD>
														<TD colspan="2" align="center">
															<input type="text" name="otp" id="otp" />
														</TD>
														<TD colspan="2" align="center">
															<input type="hidden" name="paymentid" id="paymentid" value="<?php echo $myObj->getPaymentId();?>" />
															<input type="submit" value="submit" />
														</TD>
													</TR>
												</TABLE>
											</td>
										</tr>
									</TABLE>
								</form>
							</BODY>
						</html>
                    <?php

                }
        } 
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
            <TD>Error</TD>
            <TD>&nbsp;&nbsp;<?PHP echo $myObj->getError();?></TD>
        </TR>
        <TR>
            <TD>Payment ID</TD>
            <TD>&nbsp;&nbsp;<?PHP echo $myObj->getPaymentId();?></TD>
            </TR>
        </table>
        </td></tr>
        </table>

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
						<TD><?PHP echo $myObj->getAmt();?></TD>
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