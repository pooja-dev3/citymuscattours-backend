<?php session_start(); ?>
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
							$tranportalId="";
							$phpjavabridgeurl="";
						
							while(!feof($file))
							{
								$lineData=fgets($file);
								if (substr($lineData,0, strrpos($lineData,"="))=="gateway.terminal.tranportalid") 
									$tranportalId		=	 substr($lineData,strrpos($lineData,"=")+1);
								if (substr($lineData,0, strrpos($lineData,"="))=="php.java.bridge.url")
									$phpjavabridgeurl	= substr($lineData,strrpos($lineData,"=")+1);
							}
							java_require('http://localhost:8080/e24Pipe.jar');
                            $myObj = new Java("com.fss.pg.plugin.e24Pipe"); 
							$hashvalue = new Java("com.fss.plugin.Hashing");
							$amt="1.0";
							$trackId=$_SESSION['trackid'];
							$hashMess=trim($tranportalId);
							if(isset($_GET["trackid"]))
							{
								$hashMess=$hashMess.trim($trackId);
							}
							if(isset($_GET["amt"]))
							{
								$hashMess=$hashMess.trim($amt);
							}
							if(isset($_GET["result"]))
							{
								$hashMess=$hashMess.$_GET["result"];
							}
							if(isset($_GET["paymentid"]))
							{
								$hashMess=$hashMess.$_GET["paymentid"];
							}
							if(isset($_GET["ref"]))
							{
								$hashMess=$hashMess.$_GET["ref"];
							}
							if(isset($_GET["auth"]))
							{
								$hashMess=$hashMess.$_GET["auth"];
							}
							if(isset($_GET["tranid"]))
							{
								$hashMess=$hashMess.$_GET["tranid"];
							}
							//echo "hashing params::".$hashMess."</br>";
							$hashing=hash('sha256',$hashMess);//$hashvalue->hashMethod($hashMess);
						//echo "hashing ::".$hashing."</br>";
							$myObj = new Java("com.fss.plugin.e24Pipe");
							if(isset($_GET["ErrorText"]))
							{
							?>
							<TR>
									<TD>
										ErrorText
									</TD>
									<TD>
										&nbsp;&nbsp;<b><font size="2" color="red"><?php echo $_GET["ErrorText"]?></font></TD>
								</TR>
							<?php
							}
							else if($_GET["udf5"]!=$hashing)
							{
							?>
							<TR>
									<TD>
										ErrorText
									</TD>
									<TD>
										&nbsp;&nbsp;<b><font size="2" color="red"><?php echo "UDF MISHMATCH"?></font></TD>
								</TR>
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
										UDF5
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo trim($_GET["udf5"]);?></TD>
								</TR>
								<TR>
									<TD>
										Payment ID
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo trim($_GET["paymentid"]);?></TD>
								</TR>
							<?php
							}else{
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
										UDF5
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo trim($_GET["udf5"]);?></TD>
								</TR>
								<TR>
									<TD>
										Payment ID
									</TD>
									<TD>
										&nbsp;&nbsp;<?php echo trim($_GET["paymentid"]);?></TD>
								</TR>
							<?php
							
							}
							
							
		?>
			<tr></tr>
			<tr></tr>
			<tr></tr>
			
		</table>
	</BODY>
</HTML>
				