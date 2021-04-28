{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@146671 *}

<div style="padding:10px">

<div id="block_auth" class="editBlock">
	<table border="0" cellspacing="0" cellpadding="5" width=100% class="small editBlockHeader">
		<tr>
			<td colspan=4 class="detailedViewHeader"><b>{"LBL_AUTHENTICATION"|getTranslatedString:'Settings'}</b></td>
		</tr>
	</table>
									
	<table width="100%" border="0" id="table_auth" {if !$WSFIELDS.has_auth}style="display:none"{/if}>
		{include file="DisplayFields.tpl" data=$WSFIELDS.auth}
	</table>
	
	<button type="button" id="button_auth" class="crmbutton small edit" {if $WSFIELDS.has_auth}style="display:none"{/if} onclick="ActionCallWSScript.addAuth()">{"LBL_SET_AUTHENTICATION"|getTranslatedString:'Settings'}</button>
</div>
<br>

<div id="block_params" class="editBlock">
	<table border="0" cellspacing="0" cellpadding="5" width=100% class="small editBlockHeader">
		<tr>
			<td colspan=4 class="detailedViewHeader"><b>{"LBL_PARAMETERS"|getTranslatedString:'Settings'}</b></td>
		</tr>
	</table>
									
	<table width="100%" border="0" id="table_params">
		{include file="DisplayFields.tpl" data=$WSFIELDS.params}
	</table>
</div>
<br>

<div id="block_custom_params" class="editBlock">
	<table border="0" cellspacing="0" cellpadding="5" width=100% class="small editBlockHeader" id="header_custom_params" style="display:none">
		<tr>
			<td colspan=4 class="detailedViewHeader"><b>{"LBL_ADDITIONAL_PARAMETERS"|getTranslatedString:'Settings'}</b></td>
		</tr>
	</table>
	
	<table width="100%" border="0" id="table_custom_params" style="display:none">
		<tr id="param_row_tpl" style="display:none">
			<td width="50%">
				{include file="EditViewUI.tpl" uitype=1 fldname="param_name" fldlabel="LBL_PARAMETER_NAME"|getTranslatedString:'Settings' fldvalue=""}
			</td>
			<td>
				{include file="EditViewUI.tpl" uitype=1 fldname="param_value" fldlabel="LBL_PARAMETER_VALUE"|getTranslatedString:'Settings' fldvalue=""}
			</td>
			<td width="50" align="right">
				<i class="vteicon md-link" onclick="ActionCallWSScript.delParam(this)">delete</i>
			</td>
		</tr>
		{* TODO: ciclo per quelli esistenti *}
	</table>
	<input type="hidden" id="last_param_id" value="0" />

	<button type="button" class="crmbutton small edit" onclick="ActionCallWSScript.addParam()">{"LBL_ADD_PARAMETER"|getTranslatedString:'Settings'}</button>
</div>
<br>

{* crmv@190014 *}
<div id="block_rawbody" class="editBlock">
	<table border="0" cellspacing="0" cellpadding="5" width=100% class="small editBlockHeader">
		<tr>
			<td colspan=4 class="detailedViewHeader"><b>{"LBL_EXTWS_RAWBODY"|getTranslatedString:'Settings'}</b></td>
		</tr>
	</table>
	<table width="100%" border="0" id="table_rawbody">
		<tr>
			<td>
				{include file="EditViewUI.tpl" uitype=19 fldname="rawbody" fldlabel="Corpo grezzo" fldvalue=$WSFIELDS.rawbody}
			</td>
		</tr>
	</table>
</div>
{* crmv@190014e *}

<div id="block_results" class="editBlock">
	<table border="0" cellspacing="0" cellpadding="5" width=100% class="small editBlockHeader">
		<tr>
			<td colspan=4 class="detailedViewHeader"><b>{"LBL_EXTWS_RESULTS"|getTranslatedString:'Settings'}</b></td>
		</tr>
	</table>
									
	<table width="100%" border="0">
		{include file="DisplayFields.tpl" data=$WSFIELDS.results}
	</table>
</div>
<br>

<div id="block_custom_results" class="editBlock">
	<table border="0" cellspacing="0" cellpadding="5" width=100% class="small editBlockHeader" id="header_custom_results" style="display:none">
		<tr>
			<td colspan=4 class="detailedViewHeader"><b>{"LBL_ADDITIONAL_RESULTS"|getTranslatedString:'Settings'}</b></td>
		</tr>
	</table>
	
	<table width="100%" border="0" id="table_custom_results" style="display:none">
		<tr id="result_row_tpl" style="display:none">
			<td width="50%">
				{include file="EditViewUI.tpl" uitype=1 fldname="result_name" fldlabel="LBL_EXTWS_RESULT_NAME"|getTranslatedString:'Settings' fldvalue=""}
			</td>
			<td>
				{include file="EditViewUI.tpl" uitype=1 fldname="result_value" fldlabel="LBL_EXTWS_RESULT_VALUE"|getTranslatedString:'Settings' fldvalue=""}
			</td>
			<td width="50" align="right">
				<i class="vteicon md-link" onclick="ActionCallWSScript.delResult(this)">delete</i>
			</td>
		</tr>
		{* TODO: ciclo per quelli esistenti *}
	</table>
	<input type="hidden" id="last_result_id" value="0" />

	<button type="button" class="crmbutton small edit" onclick="ActionCallWSScript.addResult()">{"LBL_EXTWS_ADD_RESULT"|getTranslatedString:'Settings'}</button>
</div>
<br>

</div>