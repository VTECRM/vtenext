{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@96742 *}
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={$APP.LBL_CHARSET}">
<title>{$MOD.LBL_PRINT_REPORT} - {$APP.LBL_BROWSER_TITLE}</title> {* crmv@28324 *}
<link rel="stylesheet" media="print" href="themes/{$THEME}/style_print.css" type="text/css">
<link rel="stylesheet" href="themes/{$THEME}/reportprint.css" type="text/css">
</head>
<body style="text-align:center;" onLoad="JavaScript:window.print()">
	<table width="90%" border="0" cellpadding="5" cellspacing="0" align="center"> {* crmv@193931 *}
	<tr>
		<td align="left" valign="top">
		<h2>{$REPORT_NAME|getTranslatedString}</h2> {* crmv@150040 *}
		<font color="#666666"><div id="report_info"></div></font>
		</td>
		<td align="right" valign="top"><h3 style="color:#CCCCCC">{$COUNT} {$APP.LBL_RECORDS}</h3></td>
	</tr>
	{* crmv@29686 *}
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr>
		<td colspan="2">
		{$COUNT_TOTAL_HTML}
		</td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	{* crmv@29686e *}
	<tr>
		<td colspan="2">
		{$PRINT_CONTENTS}
		</td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr>
		<td colspan="2">
		{$TOTAL_HTML}
		</td>
	<tr>
	</table>
</body>
<script type="text/javascript">
	document.getElementById('report_info').innerHTML = window.opener.document.getElementById('report_info').innerHTML;
</script>
</html>