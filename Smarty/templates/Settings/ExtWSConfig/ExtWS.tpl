{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@146670 crmv@146671 *}

{* javascript for the extws config *}
<script type="text/javascript" src="{"modules/Settings/ExtWSConfig/ExtWSConfig.js"|resourcever}"></script>

<link rel="stylesheet" type="text/css" media="screen" href="include/js/codemirror/lib/codemirror.css" />
<link rel="stylesheet" type="text/css" media="screen" href="include/js/codemirror/theme/eclipse.css" />
<script type="text/javascript" src="include/js/codemirror/lib/codemirror.js"></script>
<script type="text/javascript" src="include/js/codemirror/mode/clike/clike.js"></script>
<script type="text/javascript" src="include/js/codemirror/mode/http/http.js"></script>
<script type="text/javascript" src="include/js/codemirror/mode/javascript/javascript.js"></script>
<script type="text/javascript" src="include/js/codemirror/mode/xml/xml.js"></script>
<script type="text/javascript" src="include/js/codemirror/mode/css/css.js"></script>
<script type="text/javascript" src="include/js/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script type="text/javascript" src="include/js/codemirror/plugins/formatting.js"></script>

{* some CSS *}
<style type="text/css">
{literal}
	.extws_subfield_header {
		font-size: 90%;
		color: #707070;
	}
	.CodeMirror {
		border: 1px solid #b0b0b0;
		border-radius: 5px;
		-webkit-border-radius: 5px;
		-moz-border-radius: 5px;
	}
{/literal}
</style>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tr>
	<td valign="top"></td>
    <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

	<div align=center>
		{include file='SetMenu.tpl'}
		{include file='Buttons_List.tpl'} {* crmv@30683 *}
		<table class="settingsSelUITopLine" border="0" cellpadding="5" cellspacing="0" width="100%">
			<tr>
				<td rowspan="2" valign="top" width="50"><img src="{'extws_config.png'|@vtecrm_imageurl:$THEME}" alt="{$MOD.LBL_EXTWS_CONFIG}" title="{$MOD.LBL_EXTWS_CONFIG}" border="0" height="48" width="48"></td>
				<td class="heading2" valign="bottom"><b> {$MOD.LBL_SETTINGS} &gt; {$MOD.LBL_EXTWS_CONFIG}</b></td> <!-- crmv@30683 -->
			</tr>
			<tr>
				<td class="small" valign="top">{$MOD.LBL_EXTWS_CONFIG_DESC}</td>
			</tr>
		</table>
				
		<table border="0" cellpadding="10" cellspacing="0" width="100%">
			<tr>
				<td>
					
					{if $MODE eq 'create' || $MODE eq 'edit'} 
			
						<form id="extws_form" name="extws_form" method="POST" action="index.php?module=Settings&amp;action=SettingsAjax&amp;file=ExtWSConfig&amp;parentTab=Settings">
							
							{* some basic variables *}
							<input type="hidden" name="extwsid" id="extwsid" value="{$EXTWSID}" />
							<input type="hidden" name="mode" value="save" />
							
							{* box to dispaly errors *}
							<div id="extws_error_box" class="dvtCellInfo" style="width:98%;color:red;font-weight:700;margin-bottom:30px;padding:10px;{if $STEP_ERROR eq ''}display:none;{/if}">{$STEP_ERROR}</div>
							
							{include file="Settings/ExtWSConfig/Edit.tpl"}
							
						</form>
						
					{else}
					
						{* box to dispaly errors *}
						<div id="extws_error_box" class="dvtCellInfo" style="width:98%;color:red;font-weight:700;margin-bottom:30px;padding:10px;{if $LIST_ERROR eq ''}display:none;{/if}">{$LIST_ERROR}</div>
					
						{include file="Settings/ExtWSConfig/List.tpl"}
					{/if}
			
					<br><br>
					
					{include file='Settings/ScrollTop.tpl'}
				</td>
			</tr>
		</table>
		<!-- End of Display -->
		
   </div>

   </td>
   <td valign="top"></td>
</tr>
</table>

{* Test WS Popup *}
{assign var="FLOAT_TITLE" value=$MOD.LBL_EXTWS_TEST_RESULT}
{assign var="FLOAT_WIDTH" value="720px"}
{assign var="FLOAT_HEIGHT" value="400px"}
{assign var="FLOAT_BUTTONS" value=""}
{capture assign="FLOAT_CONTENT"}
<table border=0 cellspacing=0 cellpadding=3 width=100% class="small">
	<tr>
		<td class="dvtSelectedCell" align="center" onClick="ExtWSConfig.gotoTestTab('result')" id="wstab_result" nowrap=""><a>{$MOD.LBL_RESULT}</a></td>
		<td class="dvtUnSelectedCell" align="center" onClick="ExtWSConfig.gotoTestTab('headers')" id="wstab_headers" nowrap=""><a>{$MOD.LBL_HEADERS}</a></td>
		<td class="dvtUnSelectedCell" align="center" onClick="ExtWSConfig.gotoTestTab('response')" id="wstab_response" nowrap=""><a>{$MOD.LBL_RESPONSE}</a></td>
		<td class="dvtTabCache" align="right" style="width:100%"></td>
	</tr>
</table>
<br>
<div id="wstab_result_div">
	<table border="0" width="100%">

		<tr>
			<td align="right" width="20%"><span>{$APP.LBL_STATUS}</span>&nbsp;&nbsp;</td>
			<td align="left">
				<div class="dvtCellInfo dvtCellInfoOff">
					<span id="wstest_status" style="font-weight:700"></span>
				</div>
			</td>
		</tr>
		
		<tr>
			<td align="right" width="20%"><span>{$MOD.LBL_RETURN_CODE}</span>&nbsp;&nbsp;</td>
			<td align="left">
				<div class="dvtCellInfo dvtCellInfoOff">
					<span id="wstest_code"></span>
				</div>
			</td>
		</tr>
		
	</table>
</div>
<div id="wstab_headers_div" style="display:none">
	<textarea id="wstest_headers"></textarea>
</div>
<div id="wstab_response_div" style="display:none">
	<table border="0" width="100%">
		<tr>
			<td>
				<span>{$MOD.LBL_EXTWS_VIEW_RESPONSE_AS}: </span>
				<div style="display:inline-block;width:100px">
					<select id="wstest_formatas" class="detailedViewTextBox" onchange="ExtWSConfig.formatResponse(this)">
						<option value="raw">RAW</option>
						<option value="json">JSON</option>
						<option value="xml">XML</option>
						<option value="html">HTML</option>
					</select>
				</div>
			</td>
			<td align="right">
				<button class="crmbutton small edit" type="button" onclick="ExtWSConfig.automapFields()">{$MOD.LBL_AUTOMAP_FIELDS}</button>
			</td>
		</tr>
	</table>
	<textarea id="wstest_body"></textarea>
</div>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="TestWSWindow"}