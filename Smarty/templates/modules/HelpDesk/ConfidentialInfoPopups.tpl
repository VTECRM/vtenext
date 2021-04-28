{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@160733 *}

{assign var="FLOAT_TITLE" value=$MOD.LBL_SAVE_CONFIDENTIAL_COMMENT}
{assign var="FLOAT_WIDTH" value="800px"}
{assign var="FLOAT_BUTTONS" value=""}
{capture assign="FLOAT_CONTENT"}
<p>{$MOD.LBL_REQUEST_CONFIDENTIAL_INFO_DESC}:</p>
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="small">
	<tbody>
	<tr>
		<td class="dvtCellLabel" width="100%" align="left">{"LBL_PASSWORD"|getTranslatedString:'Users'}</td>
	</tr>
	<tr>
		<td align="left">
			<div class="dvtCellInfo"><input type="password" id="confinfo_pwd1" name="confinfo_pwd1" class="detailedViewTextBox"></div>
		</td>
	</tr>
	<tr>
		<td class="dvtCellLabel" width="100%" align="left">{"Confirm Password"|getTranslatedString:'Users'}</td>
	</tr>
	<tr>
		<td align="left">
			<div class="dvtCellInfo"><input type="password" id="confinfo_pwd2" name="confinfo_pwd2" class="detailedViewTextBox"></div>
		</td>
	</tr>
	<tr>
		<td width="100%" align="left"><br><p>{$MOD.LBL_REQUEST_CONFIDENTIAL_INFO_MORE}</p></td>
	</tr>
	<tr>
		<td class="dvtCellLabel" width="100%" align="left">{"LBL_ADD_COMMENT"|getTranslatedString:'HelpDesk'} ({$MOD.LBL_WONT_BE_ENCRYPTED})</td>
	</tr>
	<tr>
		<td class="dvtCellInfo" width="100%" align="left">
			<textarea rows="8" style="min-height:150px" id="confinfo_comment" name="confinfo_comment" class="detailedViewTextBox"></textarea>
		</td>
	</tr>
	<tr>
		<td class="dvtCellLabel" width="100%" align="left">{"LBL_ADD_ENCRYPTED_COMMENT"|getTranslatedString:'HelpDesk'}</td>
	</tr>
	<tr>
		<td class="dvtCellInfo" width="100%" align="left">
			<textarea rows="8" style="min-height:150px" id="confinfo_more" name="confinfo_more" class="detailedViewTextBox"></textarea>
		</td>
	</tr>
	<tr>
		<td align="center">
			<br>
				{if $editmode eq 'detailview'}
				<input type="button" title="{$APP.LBL_SAVE_BUTTON_LABEL}" value="{$APP.LBL_SAVE_BUTTON_LABEL}" name="button" 
					onclick="VTE.HelpDesk.ConfidentialInfo.requestInfo('{$MODULE}', '{$ID}', '{$label}');"
					class="crmbutton small save"
				>
				{elseif $editmode eq 'editview'}
				<input type="button" title="OK" value="OK" name="button" 
					onclick="VTE.HelpDesk.ConfidentialInfo.savePassword();"
					class="crmbutton small save"
				>
				{/if}
				<input type="button" title="{$APP.LBL_CANCEL_BUTTON_LABEL}" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" name="button" 
					onclick="VTE.HelpDesk.ConfidentialInfo.cancelAskPassword()" 
					class="crmbutton small cancel"
				>
			<br><br>
		</td>
	</tr>
</tbody></table>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="reqConfInfo"}
<script type="text/javascript">
	// capture the X icon
	jQuery('#reqConfInfo').find('.closebutton').on('click', function() {ldelim}
		VTE.HelpDesk.ConfidentialInfo.cancelAskPassword();
	{rdelim});
</script>

{assign var="FLOAT_TITLE" value=$MOD.LBL_CONFIDENTIAL_INFO}
{assign var="FLOAT_WIDTH" value="800px"}
{assign var="FLOAT_BUTTONS" value=""}
{capture assign="FLOAT_CONTENT"}
<input type="hidden" name="confinfo_commentid" id="confinfo_commentid" />
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="small">
	<tbody>
	<tr>
		<td class="dvtCellLabel" width="100%" align="left">{$MOD.LBL_CONFIDENTIAL_REQUEST}</td>
	</tr>
	<tr>
		<td align="left">
			<div class="dvtCellInfoOff"><textarea rows="8" id="confinfo_data_more" name="confinfo_data_more" class="detailedViewTextBox" readonly=""></textarea></div>
		</td>
	</tr>
	<tr>
		<td class="dvtCellLabel" width="100%" align="left">{$MOD.LBL_CONFIDENTIAL_RESPONSE}</td>
	</tr>
	<tr>
		<td align="left">
			<div class="dvtCellInfo"><textarea rows="8" style="min-height:300px" id="confinfo_data" name="confinfo_data" class="detailedViewTextBox"></textarea></div>
		</td>
	</tr>
	<tr>
		<td class="dvtCellLabel" width="100%" align="left">{$MOD.Comment} ({$MOD.LBL_WONT_BE_ENCRYPTED})</td>
	</tr>
	<tr>
		<td align="left">
			<div class="dvtCellInfo"><textarea rows="8" id="confinfo_data_comment" name="confinfo_data_comment" class="detailedViewTextBox"></textarea></div>
		</td>
	</tr>
	<tr>
		<td align="center">
			<br>
				<input type="button" title="{$APP.LBL_SAVE_BUTTON_LABEL}" value="{$APP.LBL_SAVE_BUTTON_LABEL}" name="button" onclick="VTE.HelpDesk.ConfidentialInfo.provideInfo('{$MODULE}', '{$ID}', '{$keyfldname}', '{$label}');" class="crmbutton small save">
				<input type="button" title="{$APP.LBL_CANCEL_BUTTON_LABEL}" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" name="button" onclick="hideFloatingDiv('provideConfInfo')" class="crmbutton small cancel">
			<br><br>
		</td>
	</tr>
</tbody></table>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="provideConfInfo"}

{assign var="FLOAT_TITLE" value=$MOD.LBL_CONFIDENTIAL_INFO}
{assign var="FLOAT_WIDTH" value="800px"}
{assign var="FLOAT_BUTTONS" value=""}
{capture assign="FLOAT_CONTENT"}
<input type="hidden" name="confinfo_see_commentid" id="confinfo_see_commentid" />
<p>{$MOD.LBL_CONFIDENTIAL_INFO_SEE_DESC}</p>
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="small">
	<tbody>
	<tr>
		<td class="dvtCellLabel" width="100%" align="left">{"LBL_PASSWORD"|getTranslatedString:'Users'}</td>
	</tr>
	<tr>
		<td align="left">
			<div class="dvtCellInfo"><input type="password" id="confinfo_pwd" name="confinfo_pwd" class="detailedViewTextBox" onkeyup="VTE.HelpDesk.ConfidentialInfo.onPwdKeyup(event, '{$MODULE}', '{$ID}')"></div>
		</td>
	</tr>
	<tr>
		<td align="center">
			<br>
				<input type="button" title="{$MOD.LBL_CONFIDENTIAL_INFO_SHOW_BTN}" value="{$MOD.LBL_CONFIDENTIAL_INFO_SHOW_BTN}" name="button" onclick="VTE.HelpDesk.ConfidentialInfo.loadData('{$MODULE}', '{$ID}');" class="crmbutton small save">
			<br>
		</td>
	</tr>
	<tr>
		<td class="dvtCellLabel" width="100%" align="left">{$MOD.LBL_CONFIDENTIAL_INFO}</td>
	</tr>
	<tr>
		<td align="left">
			<div class="dvtCellInfoOff"><textarea rows="8" style="min-height:300px" id="confinfo_see_data" name="confinfo_see_data" class="detailedViewTextBox" readonly="readonly"></textarea></div>
		</td>
	</tr>
	
</tbody></table>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="showConfInfo"}