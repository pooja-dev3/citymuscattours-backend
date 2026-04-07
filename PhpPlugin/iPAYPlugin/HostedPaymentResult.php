<HTML>
<HEAD>
<META name="GENERATOR" content="IBM WebSphere Studio">
<LINK href="css/main.css" type=text/css rel=stylesheet>
<TITLE>Canon - Hosted</TITLE>
</HEAD>
<BODY class="bg">
<br>
<TABLE align=center border=1  bordercolor=black>
	<tr>
		<td>
			<TABLE align=center border=0  bordercolor=black>
				<TR>
					<TD colspan="2" align="center">
						<FONT size="4"><B>Transaction Details   </B></FONT>
					</TD>
				</TR>
				<?php 

							$myFile = "fss.txt";
							$file = fopen($myFile, 'r');
							$resourcePath="";
							$phpjavabridgeurl="";
							$aliasName="";
							while(!feof($file))
							{
								$lineData=fgets($file);
								if (substr($lineData,0, strrpos($lineData,"="))=="gateway.resource.path") 
									$resourcePath		=	 substr($lineData,strrpos($lineData,"=")+1);
								if (substr($lineData,0, strrpos($lineData,"="))=="gateway.terminal.alias") 
									$aliasName		=	 substr($lineData,strrpos($lineData,"=")+1);
								if (substr($lineData,0, strrpos($lineData,"="))=="php.java.bridge.url")
									$phpjavabridgeurl	= substr($lineData,strrpos($lineData,"=")+1);
							}
							
							require_once("http://localhost:8080/JavaBridge/java/Java.inc");
							#java_require('http://localhost//iPAYPlugin//iPayPipe.jar');
							$myObj = new Java("com.fss.plugin.iPayPipe");
							$myObj->setKeystorePath(trim($resourcePath));
							$myObj->setAlias(trim($aliasName));
							$myObj->setResourcePath(trim($resourcePath));
							
							echo 'CHK 1';
							
							if((isset($_POST["trandata"]) && trim($myObj->parseEncryptedRequest(trim($_POST["trandata"])))!=0)||(isset($_GET["trandata"]) && trim($myObj->parseEncryptedRequest(trim($_GET["trandata"])))!=0))
							{
							echo 'CHK 2';
							echo '<TR>';
									echo '<TD>';
										 echo 'Errors';
									echo '</TD>';
									 echo '<TD>';
								 echo '	&nbsp;&nbsp;';
										 echo $myObj->getError();
										echo '</b>';
									echo '</TD>';
								 echo '</TR>';
							
							}else{
							if(!isset($_GET["ErrorText"]) && isset($_GET["result"]))
							{
							echo 'CHK 3';	
				?>
							<TR>
									<TD>
										Transaction Status
									</TD>
									<TD>
										&nbsp;&nbsp;
										<b><font size="2" color="red"><?php echo trim($_GET["result"]);?></font>
										</b>
									</TD>
								</TR>
								<TR>
									<TD>
										Post Date
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo trim($_GET["postdate"]);?></TD>
								</TR>
								<TR>
									<TD>
										Transaction Reference ID
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo trim($_GET["ref"]);?></TD>
								</TR>
								<TR>
									<TD>
										Mrch Track ID
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo trim($_GET["trackid"]);?></TD>
								</TR>
								<TR>
									<TD>
										<b>Transaction ID</b>
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo trim($_GET["tranid"]);?></TD>
								</TR>
								<TR>
									<TD>
										Transaction Amount
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo trim($_GET["amt"]);?></TD>
								</TR>
								
								<TR>
									<TD>
										Payment ID
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo trim($_GET["paymentid"]);?></TD>
								</TR>
						<?php
						}else if(!isset($_GET["ErrorText"]) && !isset($_GET["result"]))
						{
						 echo 'CHK 4';	
						?>
							<TR>
									<TD>
										ERROR
									</TD>
									<TD>
										&nbsp;&nbsp;
										<b><font size="2" color="red"><?php echo $myObj->getError();?></font>
										</b>
									</TD>
								</TR>
						<TR>
									<TD>
										Transaction Status
									</TD>
									<TD>
										&nbsp;&nbsp;
										<b><font size="2" color="red"><?php echo $myObj->getResult();?></font>
										</b>
									</TD>
								</TR>
								<TR>
									<TD>
										Post Date
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo $myObj->getDate();?></TD>
								</TR>
								<TR>
									<TD>
										Transaction Reference ID
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo $myObj->getRef();?></TD>
								</TR>
								<TR>
									<TD>
										Mrch Track ID
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo $myObj->getTrackId();?></TD>
								</TR>
								<TR>
									<TD>
										<b>Transaction ID</b>
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo $myObj->getTransId();?></TD>
								</TR>
								<TR>
									<TD>
										<b>customer ID</b>
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo $myObj->getCustid();?></TD>
								</TR>
								<TR>
									<TD>
										Transaction Amount
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo $myObj->getAmt();?></TD>
								</TR>
								<TR>
									<TD>
										UDF5
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo $myObj->getUdf5();?></TD>
								</TR>
								<TR>
									<TD>
										Payment ID
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo $myObj->getPaymentId();?></TD>
								</TR>
						<?php
						}
						else
						{
						echo 'CHK 5';
						?>
						<TR>
									<TD>
										ErrorText
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo $_GET["ErrorText"];?></TD>
								</TR>
								<?php
						}
							} 
						?>
					</table>
				</td>
			</tr>
		</table>
		<br>
		<TABLE align=center>
			<tr></tr>
			<tr></tr>
			<tr></tr>
			<TR>
				<td>
					<FONT size=2 color="BLUE"><A href="TranPipeIndex.php">Tranportal
							Transaction</A>
					</FONT>
				</td>
			</tr>
			<tr>
				<td>
					<FONT size=2 color="BLUE"><A href="HostedPaymentIndexHttp.php">Hosted
							Transaction</A>
					</FONT>
				</td>
			</tr>
		</table>
	</BODY>
</HTML>
				