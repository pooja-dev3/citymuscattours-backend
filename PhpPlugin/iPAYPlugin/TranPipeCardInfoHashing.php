
<HTML>
<HEAD>
<TITLE>Canon - Tranportal(Hashing)</TITLE>
</HEAD>
<LINK href="css/main.css" type=text/css rel=stylesheet>
<BODY class="bg">

<table width="100%" height="100%" cellpadding="1" cellspacing="1">
	
	<!--  BODY -->
	<tr>
		<td>
			<DIV align="center">
  <font size=5>Plug-in Test Tool</font>
</DIV>

<?php
$instituteId = $_POST['InstituteID'];
$amt = $_POST['AMOUNT'];
$name = $_POST['name'];
$addr1 = $_POST['addr1'];
$addr2 = $_POST['addr2'];
$addr3 = $_POST['addr3'];
$city = $_POST['city'];
$state = $_POST['state'];
$country = $_POST['country'];
$postalCd = $_POST['postalcd'];

?>

<CENTER>
<P>
<FONT size="5"><B>Transaction Details</B></FONT>
<FORM name="form1" ACTION="TranPipeBuyHashing.php" METHOD="POST">
<TABLE align=center border=0  bordercolor=black><tr><td>
<TABLE align=center border=1  bordercolor=black>
	<TR>
		<TD width="40%">Action Type</TD>
		<TD>
			<select name="action" class="select">
			<option value="1"> 1 - Purchase </option> 
			<option value="2"> 2 - Credit  </option> 
            <option value="3"> 3 - Void Purchase </option> 
	        <option value="4"> 4 - Authorization </option> 
			<option value="5"> 5 - Capture   </option> 
			<option value="6"> 6 - Void Credit </option> 
			<option value="7"> 7 - Void Capture </option> 
			<option value="8"> 8 - Inquiry </option> 
			<option value="9"> 9 - Void Authorization </option> 
			</select>
		</TD>
    </TR>

   <TR>
		<TD >Card Number:</TD>
		<TD><input size="20" type="text" name="pan" value="4000000000000002"></TD>
	</TR>

	<TR>
		<TD >CVV:</TD>
		<TD><input size="3" type="text" name="cvv" maxlength=4 value="123"></TD>
	</TR> 


	<TR>
		<TD >Expiry Month &amp; Year</TD>
		<TD>
			<select class="select" name="expmm" >
			<option value="">SELECT</option>
			<option value="1">1</option> 
			<option value="2">2</option> 
			<option value="3">3</option> 
			<option value="4">4</option> 
			<option value="5">5</option> 
			<option value="6">6</option> 
			<option value="7">7</option> 
			<option value="8">8</option> 
			<option value="9">9</option> 
			<option value="10">10</option> 
			<option value="11">11</option> 
			<option value="12" selected>12</option> 
			</select>
			&nbsp;
			<select class="select" name="expyy" >
			<option value="">SELECT</option>
			<option value="2011">2011</option> 
			<option value="2012">2012</option> 
			<option value="2013">2013</option> 
			<option value="2014" selected>2014</option> 
			<option value="2015">2015</option> 
			</select>
		</TD></tr>
		<TR>
		<TD >Cardholder's Name</TD>
		<TD><input size="20" type="text" name="name" value="Test"></TD>
		</TR>
		<TR>
			<TD width="40%">Transaction ID</TD>
			<TD><input size="20" type="text" name="comment"></TD>
		</TR>
		<TR>
			<TD width="40%">UDF1</TD>
			<TD><input size="20" type="text" name="udf1"></TD>
		</TR>
		<TR>
			<TD width="40%">UDF2</TD>
			<TD><input size="20" type="text" name="udf2"></TD>
		</TR>
		<TR>
			<TD width="40%">UDF3</TD>
			<TD><input size="20" type="text" name="udf3"></TD>
		</TR>
		<TR>
			<TD width="40%">UDF4</TD>
			<TD><input size="20" type="text" name="udf4"></TD>
		</TR>
		<TR>
			<TD width="40%">UDF5</TD>
			<TD>
				<select class="select" name="udf5" >
			<option value="">SELECT</option>
			<option value="PaymentID">PaymentID</option> 
			<option value="TrackID">TrackID</option> 
			<option value="SeqNum">SeqNum</option>
			<option value="HostTranID">HostTranID</option> 
			<option value="Test">Test</option>
			</select>
			</TD>
		</TR>
		<TR>
			<TD width="40%">Track ID</TD>
			<TD><input size="20" type="text" name="trckId" value="<?php echo substr(number_format(time() * rand(),0,'',''),0,10);?>"></TD>
		</TR>
	</TABLE> 
	</td></tr></table>

<TABLE align=center border=0  bordercolor=black>

<TR>
		<TD ></TD>
		<TD><input type="Submit" value="Buy"></TD>
	</TR></table>
<P>
<input type=hidden name=InstituteID value="<?php echo $instituteId;?>" >
<!--<input type=hidden name=action value="1" > -->
<input type=hidden name=type value="C" >
<input type=hidden name=currcd value="356" >
<input type=hidden name=AMOUNT value="<?php echo $amt;?>" >
<input type=hidden name="name"  value="<?php echo $name;?>">
<input type=hidden name="addr1"  value="<?php echo $addr1;?>">
<input type=hidden name="addr2"  value="<?php echo $addr2;?>">
<input type=hidden name="addr3"  value="<?php echo $addr3;?>">
<input type=hidden name="city"  value="<?php echo $city;?>">
<input type=hidden name="state"  value="<?php echo $state;?>">
<input type=hidden name="country"  value="<?php echo $country;?>">
<input type=hidden name="postalcd"  value="<?php echo $postalCd;?>">

</FORM>
</CENTER>
		</td>
	</tr>
	
</table>

</BODY>

</HTML>
