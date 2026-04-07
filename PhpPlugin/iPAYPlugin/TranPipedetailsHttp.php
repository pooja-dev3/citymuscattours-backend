<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<TITLE>Canon - Tranportal</TITLE>
</HEAD>
<LINK href="css/main.css" type=text/css rel=stylesheet>
<BODY class="bg">
<CENTER>
<table width="100%" height="100%" cellpadding="1" cellspacing="1">
	
	<!--  BODY -->
	<tr>
		<td>
			<FORM name="form1" ACTION="TranPipeCardInfoHttp.php" METHOD="POST">
		<?php
				$sku = $_POST['SKU'];
				$desc = $_POST['NAME'];
				$price = $_POST['PRICE'];
				$qty = $_POST['QTY'];
				$totalPrice = $qty * $price;
				$formatted_number=number_format((double)$totalPrice, 2, '.', '');
				?>
			<input type="hidden" name="InstituteID" value="2">
			<input type="hidden" name="SKU"   value="<?PHP print $sku ; ?>">
			<input type="hidden" name="NAME"  value="<?PHP print $desc ; ?>">
			<input type="hidden" name="QTY" value="<?PHP print $price ; ?>">
			<input type="hidden" name="PRICE" value="<?PHP print $qty ; ?>">
			<input type="hidden" name="AMOUNT" value="<?PHP print $formatted_number  ; ?>">
			<TABLE width="50%" cellpadding="1" cellspacing="1" border="1" align="center">
				<TR>
					<TD colspan="1">SKU:</TD>
					<TD colspan="1">Description:</TD>
					<TD colspan="1">Unit Price:</TD>
					<TD colspan="1">Qty:</TD>
					<TD colspan="1">Price:</TD>
				</TR>
				<TR>
					<TD colspan="1"><?PHP print $sku ; ?></TD>
					<TD colspan="1"><?PHP print $desc ; ?></TD>
					<TD colspan="1"><?PHP print $price ; ?></TD>
					<TD colspan="1"><?PHP print $qty ; ?></TD>
					<TD colspan="1"><?PHP print $formatted_number  ; ?></TD>
				</TR>
				<TR>
					<TD colspan="4" align="right"><b>Total Price</b>&nbsp;&nbsp;</TD>
					<TD><?PHP print $formatted_number  ; ?></TD>
				</TR>
			</TABLE>
			<BR>
			<TABLE width="50%" cellpadding="1" cellspacing="1" border="1" align="center">
				<TR>
					<TD width="100%" colspan="2" align="center"><FONT size="3"><B>Shipping Details:</B></FONT></TD>
				</TR>
				<TR>
					<TD width="100%" colspan="2" align="center">
						<TABLE width="100%" cellpadding="1" cellspacing="1" border="0" align="center">
							<TR>
								<TD width="30%">Name:</TD>
								<TD><input size="20" type="text" name="name"></TD>
							</TR>
							<TR>
								<TD width="30%">Address Line 1:</TD>
								<TD><input size="20" type="text" name="addr1"></TD>
							</TR>
							<TR>
								<TD width="30%">Address Line 2:</TD>
								<TD><input size="20" type="text" name="addr2"></TD>
							</TR>
							<TR>
								<TD width="30%">Address Line 3:</TD>
								<TD><input size="20" type="text" name="addr3"></TD>
							</TR>
							<TR>
								<TD width="30%">City:</TD>
								<TD><input size="20" type="text" name="city"></TD>
							</TR>
							<TR>
								<TD width="30%">State:</TD>
								<TD><input size="20" type="text" name="state"></TD>
							</TR>
							<TR>
								<TD width="30%">Country:</TD>
								<TD><input size="20" type="text" name="country"></TD>
							</TR>
							<TR>
								<TD width="30%">Postal Code:</TD>
								<TD><input size="10" type="text" name="postalcd"></TD>
							</TR>
							<TR>
								<TD width="100%" colspan="2" align="center"><input type="Submit" value="Buy"></TD>
							</TR>
						</TABLE>
					</TD>
				</TR>
			</TABLE>
			</FORM>
		</td>
	</tr>

</table>
</CENTER>
</BODY>
</HTML>
