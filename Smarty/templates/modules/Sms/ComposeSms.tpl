{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@16703 *}
{assign var="FLOAT_TITLE" value="TITLE_COMPOSE_SMS"|@getTranslatedString:"Sms"}
{assign var="FLOAT_WIDTH" value="400px"}
{capture assign="FLOAT_CONTENT"}
	<table width="100%" cellpadding="5" cellspacing="0" border="0" align="center">
	<tr>
		<td>
			{"LBL_MESSAGE"|@getTranslatedString:"Sms"}:<br/>
			<textarea name="description" class="small" rows="12" cols="10" onkeyup="jQuery('#__smsnotifer_compose_wordcount__').html(this.value.length)"></textarea>
		</td>
	<tr>
		<td align="right"><span id="__smsnotifer_compose_wordcount__">0</span> {"LBL_CHARACTERS"|@getTranslatedString:"Sms"}</td>	
	</tr>
	</table>
	
	<table width="100%" cellpadding="5" cellspacing="0" border="0" class="layerPopupTransport">
	<tr>
		<td class="small" align="center">
			<input name="parent_id" id="parent_id" type="hidden" value="{$IDLISTS}">
			<input name="parent_module" id="parent_module" type="hidden" value="{$select_module}"> {* crmv@152701 *}
			<input type="hidden" name="saved_toid" value="{$TO_SMS}">
			<input type="hidden" name="send_sms">
			<input type="hidden" name="contact_id" value="{$CONTACT_ID}">
			<input type="hidden" name="user_id" value="{$USER_ID}">
			<input type="hidden" name="old_id" value="{$OLD_ID}">
			<input type="hidden" name="module" value="{$MODULE}">
			<input type="hidden" name="record" value="{$ID}">
			<input type="hidden" name="mode" value="{$MODE}">
			<input type="hidden" name="action" value="Save"> {* crmv@142472 *}
			<input type="hidden" name="popupaction" value="create">
			<input type="hidden" name="hidden_toid" id="hidden_toid">

			<input name="{$MOD.LBL_SEND}" value="{$APP.LBL_SEND}" class="crmbutton small save" type="submit" onclick="this.form.send_sms.value='true'">&nbsp;
			<input name="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" value=" {$APP.LBL_CANCEL_BUTTON_LABEL} " class="crmbutton small cancel" type="button" onClick="hideFloatingDiv('smssendpopup'); jQuery('#sendsms_cont').html('');">
		</td>
	</tr>
	</table>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="smssendpopup" FLOAT_BUTTONS=""}


<script type="text/javascript" id="sms_script">
conf_sms_srvr_err_msg = '{$MOD.LBL_CONF_MAILSERVER_ERROR}';
no_description = '{$MOD.MESSAGE_NO_DESCRIPTION}';
</script>