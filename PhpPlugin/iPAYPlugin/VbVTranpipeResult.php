<HTML>
	<BODY class="bg">
		<br><br>
		<TABLE align=center border=1 bordercolor=black>
			<tr>
				<td>

					<TABLE align=center border=0 bordercolor=black>
						<TR>
							<TD colspan="2" align="center">
								<FONT size="4"><B>Transaction Details</B></FONT>
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
							java_require('http://localhost:8080/e24Pipe.jar');
							$myObj = new Java("com.fss.pg.plugin.e24Pipe");
							$myObj->setKeystorePath(trim($resourcePath));
							$myObj->setAlias(trim($aliasName));
							$myObj->setResourcePath(trim($resourcePath));
							if(isset($_GET["trandata"]) && trim($myObj->parseEncryptedResult(trim($_GET["trandata"])))!=0)
							{
								 echo '<TR>';
									echo '<TD>';
										 echo 'Error';
									echo '</TD>';
									 echo '<TD>';
								 echo '	&nbsp;&nbsp;';
										 echo $myObj->getError();
										echo '</b>';
									echo '</TD>';
								 echo '</TR>';
							
							}else if(isset($_GET["ErrorText"]))
							{?>
							<TR>
									<TD>
										ErrorText
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo $_GET["ErrorText"];?></TD>
								</TR>
							<?php
							}
							
							
							else{?>
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
								<TR>
									<TD>
										ErrorText
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo $myObj->getError();?></TD>
								</TR>
							<?php
							}
							
				?>
							
							
		</table>
	</BODY>
</HTML>