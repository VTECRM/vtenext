{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<html>
	<head>
		<title>Vte {$MODULE} Webforms</title>
		<link REL="SHORTCUT ICON" HREF="themes/logos/VTENEXT_favicon.ico">
		<style type="text/css">
		{literal}
table { font: inherit; }
.small {
	color:#000000;
	font-family:Arial,Helvetica,sans-serif;
	font-size:12px;
}

.big {
	font-family: Arial, Helvetica, sans-serif;
	font-size:14px;
}
.dvtCellLabel, .cellLabel {
	background-color:#FFe7e7;
	border:1px solid #DEDEDE;
	color: black;
	padding-left:10px;
	padding-right:10px;
	white-space:nowrap;
	text-align: right;
}
.bold{
	font-weight: bold;
}
{/literal}
		</style>
	</head>
	<body class="small">
		<div id="vteWebforms">
			<table border="0" cellpadding="0" cellspacing="1" width="100%">
				<tbody>
					<tr>
						<td colspan="2" style="text-align: right;">

						</td>
					</tr>
					<tr>
						<td class="dvtCellLabel big bold" style="text-align: center;">
							<img src="{$PATH}{$IMAGEPATH}denied.gif" style="float: left;left: 0px;width: 36px;"/>
							{$MOD.LBL_FAILURE}<br />
							<div style="text-align: center;">
										<span>{$MOD.LBL_ERROR_CODE}: </span><span>{$DATA.code}</span><br />
										<span>{$MOD.LBL_ERROR_MESSAGE}: </span><span>{$DATA.message}</span>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</body>
</html>