{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{include file='Buttons_List1.tpl'} {* crmv@187110 *}
<script language="JavaScript" type="text/javascript" src="modules/Import/resources/Import.js"></script>

<form onsubmit="VteJS_DialogBox.block();" action="index.php" enctype="multipart/form-data" method="POST" name="importBasic">
	<input type="hidden" name="module" value="{$FOR_MODULE}" />
	<input type="hidden" name="action" value="Import" />
	<input type="hidden" name="mode" value="upload_and_parse" />
	<table style="width:80%;margin-left:auto;margin-right:auto;margin-top:10px;" cellpadding="5" cellspacing="12" class="searchUIBasic">
		<tr>
			<td class="heading2" align="left" colspan="2">
				{'LBL_IMPORT'|@getTranslatedString:$MODULE} {$FOR_MODULE|@getTranslatedString:$FOR_MODULE}
			</td>
		</tr>
		{if $ERROR_MESSAGE neq ''}
		<tr>
			<td class="style1" align="left" colspan="2">
				{$ERROR_MESSAGE}
			</td>
		</tr>
		{/if}
		<tr>
			<td class="leftFormBorder1" width="60%" valign="top">
			{include file='modules/Import/Import_Step1.tpl'}
			</td>
			<td class="leftFormBorder1" width="40%" valign="top">
			{include file='modules/Import/Import_Step2.tpl'}
			</td>
		</tr>
		<tr>
			<td class="leftFormBorder1" colspan="2" valign="top">
			{include file='modules/Import/Import_Step3.tpl'}
			</td>
		</tr>
		<tr>
			<td align="right" colspan="2">
			{include file='modules/Import/Import_Basic_Buttons.tpl'}
			</td>
		</tr>
	</table>
</form>