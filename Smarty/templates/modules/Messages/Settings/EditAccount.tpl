{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@3082m crmv@51684 crmv@57983 crmv@114260 crmv@206145 *}

<script>
var available_accounts = JSON.parse('{$ACCOUNTS_AVAILABLE_JSON}');

{literal}
function changeAccountPicklist() {
	var type = jQuery('#account').val();
	
	if (type) {
		// show all
		jQuery('table.hideableTable').show();
	} else {
		// hide all
		jQuery('table.hideableTable').hide();
		return;
	}
	if (type == 'Custom') {
		jQuery('#server_div').show();
		jQuery('#smtp_account').val('Custom');
	} else {
		jQuery('#server_div').hide();
		if (type && jQuery('#smtp_account').val() != 'Custom') {
			jQuery('#smtp_account').val(type);
		}
	}
	jQuery.each(available_accounts, function(i, acc) {
		if (type == acc['account']) {
			if (typeof(acc['authentication']) == 'undefined')
				supported_authentications = [];
			else
				supported_authentications = acc['authentication'];
			jQuery('#authentication option').each(function(){
				if (supported_authentications.indexOf(jQuery(this).val()) > -1)
					jQuery(this).prop('disabled',false);
				else
					jQuery(this).prop('disabled','disabled');
				
				jQuery('#authentication option:not([disabled]):first').prop('selected','selected');
			});
		}
	});
	
	changeSmtpAccount();
	
	jQuery('#username')
		.attr('readonly',false)
		.css('background-color',false)
		.val('')
		.focus();
	jQuery('#email').val('');
	jQuery('#password_insert').val('');
	jQuery('#password').val('');
	
	jQuery('#smtp_username')
		.attr('readonly',false)
		.css('background-color',false)
		.val('');
	jQuery('#smtp_password_insert').val('');
	jQuery('#smtp_password').val('');
}
function changeSmtpAccount() {
	var type = jQuery('#account').val(),
		smtp_type = jQuery('#smtp_account').val(),
		authentication = jQuery('#authentication').val();

	if (smtp_type == 'Custom') {
		jQuery('.smtp_server_tr').show();
	} else {
		jQuery('.smtp_server_tr').hide();
	}
	if (smtp_type == 'Custom' || (smtp_type == type &&  authentication == 'oauth2')) {
		jQuery('.smtp_server_passw_tr').show();
	} else {
		jQuery('.smtp_server_passw_tr').hide();
	}
}
// crmv@152167
function validateSaveAccount() {
	var type = jQuery('#account').val(),
		smtp_type = jQuery('#smtp_account').val(),
		authentication = jQuery('#authentication').val();
	
	if (!emptyCheck("account","Account","text")) return false;
	if (!emptyCheck("username","User Name","text")) return false;
	if (authentication == 'password' && !emptyCheck("password_insert","Password","text")) return false;
	if (typeof(top.alert_arr.LBL_IMAP_SERVER_NAME) == 'undefined') top.alert_arr.LBL_IMAP_SERVER_NAME = 'Imap Server Name';
	if (typeof(top.alert_arr.LBL_SMTP_SERVER_NAME) == 'undefined') top.alert_arr.LBL_SMTP_SERVER_NAME = 'Smtp Server Name';
	if (type == 'Custom' && !emptyCheck("server",top.alert_arr.LBL_IMAP_SERVER_NAME,"text")) return false;
	if (smtp_type == 'Custom' && !emptyCheck("smtp_server",top.alert_arr.LBL_SMTP_SERVER_NAME,"text")) return false;
	// only on create
	if (authentication == 'oauth2' && jQuery('[name="id"]').val() == '' && jQuery('[name="token"]').val() == '') {
		alert(top.alert_arr.LBL_AUTHENTICATION_REQUIRED);
		return false;
	}
	
	if (jQuery('#smtp_password_insert').val() == '' && jQuery('#smtp_password').val() == '') jQuery('#smtp_password').val('********');
	
	return true;
}
function changeAuthenticationPicklist() {
	var authentication = jQuery('#authentication').val();
	if (authentication == 'oauth2') {
		jQuery('#tr_password').hide();
		if (jQuery('[name="id"]').val() == '') jQuery('#get_token_link').show(); // only on create
	} else if (authentication == 'password') {
		jQuery('#tr_password').show();
		if (jQuery('[name="id"]').val() == '') jQuery('#get_token_link').hide(); // only on create
	}
	jQuery('#password_insert').val('');
	jQuery('#password').val('');
	
	changeSmtpAccount();
}
function openOAuthPage() {
	var account = jQuery('#account').val(),
		server = jQuery('#server').val(),
		username = jQuery('#username').val();
	if (typeof(server) == 'undefined') server = '';
	
	window.open('index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=GetOAuthToken&account='+account+'&server='+server+'&username='+username);
}
// crmv@152167e
{/literal}
</script>

<form name="SaveAccount" action="index.php" method="POST">
	<input type="hidden" name="module" value="Messages">
	<input type="hidden" name="action" value="MessagesAjax">
	<input type="hidden" name="file" value="Settings/index">
	<input type="hidden" name="operation" value="SaveAccount">
	<input type="hidden" name="id" value="{$KEY}">
	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	<table border="0" cellpadding="2" cellspacing="5" width="100%">
		<tr>
			<td align="right" width="40%" style="padding:5px">Account</td>
			<td style="padding:5px">
				<select id="account" name="account" class="detailedViewTextBox" onChange="changeAccountPicklist();">
					{foreach item=av from=$ACCOUNTS_AVAILABLE}
						<option value="{$av.account}" {if $ACCOUNT.account eq $av.account}selected{/if}>{$av.label}</option>
					{/foreach}
				</select>
			</td>
			<td width="20%"></td>
		</tr>
	</table>
	
	<table border="0" cellpadding="2" cellspacing="5" width="100%" id="account_info" class="hideableTable" {if $ACCOUNT.account eq ''}style="display:none"{/if}>
		<tr>
			<td align="right" width="40%" style="padding:5px">{'LBL_USERNAME'|getTranslatedString:'Settings'}</td>
			<td style="padding:5px">
				<input type="text" id="username" name="username" value="{$ACCOUNT.username}" class="detailedViewTextBox" {if $ACCOUNT.username neq ''}readonly="readonly"{/if}/>
			</td>
			<td width="20%"></td>
		</tr>
		<tr>
			<td align="right" width="40%" style="padding:5px">{'LBL_AUTHENTICATION_METHOD'|getTranslatedString:'Settings'}</td>
			<td style="padding:5px">
				<select id="authentication" name="authentication" class="detailedViewTextBox" onChange="changeAuthenticationPicklist();">
					<option value="password" {if $ACCOUNT.authentication eq 'password'}selected{/if}>Password</option>
					<option value="oauth2" {if $ACCOUNT.authentication eq 'oauth2'}selected{/if}>OAuth2</option>
				</select>
			</td>
			<td width="20%">
				<a id="get_token_link" {if $ACCOUNT.authentication neq 'oauth2'}style="display:none"{/if} href="javascript:openOAuthPage();">{'LBL_AUTHENTICATE_LINK'|getTranslatedString:'Settings'}</a>
				<input type="hidden" id="token" name="token" value="" />
				<input type="hidden" id="refresh_token" name="refresh_token" value="" />
				<input type="hidden" id="expires" name="expires" value="" />
			</td>
		</tr>
		{* crmv@50745 *}
		<tr>
			<td align="right" style="padding:5px">{'LBL_EMAIL_ADDRESS'|getTranslatedString:'Settings'}</td>
			<td style="padding:5px">
				<input type="text" id="email" name="email" value="{$ACCOUNT.email}" class="detailedViewTextBox" placeholder="{'LBL_OPTIONAL'|getTranslatedString:'Messages'|strtolower}" />
			</td>
			<td width="20%"></td>
		</tr>
		{* crmv@50745e *}
		<tr id="tr_password" {if $ACCOUNT.authentication neq '' && $ACCOUNT.authentication neq 'password'}style="display:none"{/if}>
			<td align="right" style="padding:5px">{'LBL_PASWRD'|getTranslatedString:'Settings'}</td>
			<td style="padding:5px">
				{* crmv@43764 *}
				<input type="password" id="password_insert" value="{if !empty($ACCOUNT.password)}********{/if}" class="detailedViewTextBox" onFocus="this.value='';" onChange="document.getElementById('password').value=this.value;" />
				<input type="hidden" id="password" name="password" value="" />
				{* crmv@43764e *}
			</td>
			<td width="20%"></td>
		</tr>
		<tr>
			<td align="right" style="padding:5px">{'LBL_DESCRIPTION'|getTranslatedString}</td>
			<td style="padding:5px">
				<input type="text" name="description" value="{$ACCOUNT.description}" class="detailedViewTextBox" placeholder="{'LBL_OPTIONAL'|getTranslatedString:'Messages'|strtolower}" />
			</td>
			<td width="20%"></td>
		</tr>
		<tr>
			<td align="right" style="padding:5px">{'LBL_MAIN'|getTranslatedString:'Messages'}</td>
			<td style="padding:5px">
				<div class="dvtCellInfo checkbox">
				<label>
					<input type="checkbox" name="main" class="" {if $ACCOUNT.main eq 1}checked{/if} />
				</label>
				</div>
			</td>
			<td width="20%"></td>
		</tr>
	</table>
	
	<table border="0" cellpadding="5" cellspacing="5" width="100%" id="server_div" class="hideableTable" {if $ACCOUNT.account neq 'Custom' || $ACCOUNT.account eq ''}style="display:none;"{/if}>
		<tr>
			<td colspan="3" align="center" class="dvInnerHeader">
				<div><b>{'LBL_MAIL_SERVER_IMAP'|getTranslatedString:'Settings'}</b></div>
			</td>
		</tr>
		<tr>
			<td align="right" width="40%" style="padding:5px">{'LBL_OUTGOING_MAIL_SERVER'|getTranslatedString:'Settings'}</td>
			<td style="padding:5px">
				<input type="text" name="server" value="{$ACCOUNT.server}" class="detailedViewTextBox" />
			</td>
			<td width="20%"></td>
		</tr>
		<tr>
			<td align="right" style="padding:5px">{'LBL_OUTGOING_MAIL_PORT'|getTranslatedString:'Settings'}</td>
			<td style="padding:5px">
				<input type="text" name="port" value="{$ACCOUNT.port}" class="detailedViewTextBox" />
			</td>
			<td width="20%"></td>
		</tr>
		<tr>
			<td align="right" style="padding:5px">SSL/TLS</td>
			<td style="padding:5px">
				<select name="ssl_tls" class="detailedViewTextBox">
					<option value="" {if $ACCOUNT.ssl_tls eq ""}selected{/if}>{'LBL_NONE'|getTranslatedString}</option>
					<option value="ssl" {if $ACCOUNT.ssl_tls eq "ssl"}selected{/if}>SSL</option>
					<option value="tls" {if $ACCOUNT.ssl_tls eq "tls"}selected{/if}>TLS</option>
				</select>
			</td>
			<td width="20%"></td>
		</tr>
		<tr>
			<td align="right" style="padding:5px">{'LBL_DOMAIN'|getTranslatedString:'Settings'}</td>
			<td style="padding:5px">
				<input type="text" name="domain" value="{$ACCOUNT.domain}" class="detailedViewTextBox" />
			</td>
			<td width="20%"></td>
		</tr>
	</table>
	
	<table border="0" cellpadding="2" cellspacing="5" width="100%" class="hideableTable" {if $ACCOUNT.account eq ''}style="display:none"{/if}>
		<tr>
			<td colspan="3" align="center" class="dvInnerHeader">
				<div><b>{'LBL_MAIL_SERVER_SMTP'|getTranslatedString:'Settings'}</b></div>
			</td>
		</tr>
		<tr>
			<td align="right" width="40%" style="padding:5px">{'LBL_SMTP_SERVER'|getTranslatedString:'Messages'}</td>
			<td style="padding:5px">
				<select id="smtp_account" name="smtp_account" class="detailedViewTextBox" onchange="changeSmtpAccount()">
					{foreach item=av from=$SMTP_ACCOUNTS_AVAILABLE}
						<option value="{$av.account}" {if $ACCOUNT.smtp_account eq $av.account}selected{/if}>{$av.label}</option>
					{/foreach}
				</select>
			</td>
			<td width="20%"></td>
		</tr>
	</table>
	
	<table border="0" cellpadding="5" cellspacing="5" width="100%" class="hideableTable">
		<tr class="smtp_server_tr" {if $ACCOUNT.smtp_account neq 'Custom'}style="display:none;"{/if}>
			<td align="right" width="40%" style="padding:5px">{'LBL_OUTGOING_MAIL_SERVER'|getTranslatedString:'Settings'}</td>
			<td style="padding:5px">
				<input type="text" name="smtp_server" value="{$ACCOUNT.smtp_server}" class="detailedViewTextBox" />
			</td>
			<td width="20%"></td>
		</tr>
		<tr class="smtp_server_tr" {if $ACCOUNT.smtp_account neq 'Custom'}style="display:none;"{/if}>
			<td align="right" style="padding:5px">{'LBL_OUTGOING_MAIL_PORT'|getTranslatedString:'Settings'}</td>
			<td style="padding:5px">
				<input type="text" name="smtp_port" value="{$ACCOUNT.smtp_port}" class="detailedViewTextBox" />
			</td>
			<td width="20%"></td>
		</tr>
		<tr class="smtp_server_tr" {if $ACCOUNT.smtp_account neq 'Custom'}style="display:none;"{/if}>
			<td align="right" style="padding:5px">{'LBL_USERNAME'|getTranslatedString:'Settings'}</td>
			<td style="padding:5px">
				<input type="text" id="smtp_username" name="smtp_username" value="{$ACCOUNT.smtp_username}" class="detailedViewTextBox" />	{* crmv@152167 *}
			</td>
			<td width="20%"></td>
		</tr>
		{if $ACCOUNT.smtp_account eq 'Custom' || ($ACCOUNT.smtp_account eq $ACCOUNT.account && $ACCOUNT.authentication eq 'oauth2')}
			{assign var="DISPLAY_PASSWORD_TR" value=""}
		{else}
			{assign var="DISPLAY_PASSWORD_TR" value="display:none"}
		{/if}
		<tr class="smtp_server_passw_tr" style="{$DISPLAY_PASSWORD_TR}">
			<td align="right" width="40%" style="padding:5px">{'LBL_PASWRD'|getTranslatedString:'Settings'}</td>
			<td style="padding:5px">
				<input type="password" id="smtp_password_insert" value="{if !empty($ACCOUNT.smtp_password)}********{/if}" class="detailedViewTextBox" onFocus="this.value='';" onChange="jQuery('#smtp_password').val(this.value);" />
				<input type="hidden" id="smtp_password" name="smtp_password" value="" />
			</td>
			<td width="20%"></td>
		</tr>
		<tr class="smtp_server_tr" {if $ACCOUNT.smtp_account neq 'Custom'}style="display:none;"{/if}>
			<td align="right" style="padding:5px">{'LBL_REQUIRES_AUTHENT'|getTranslatedString:'Settings'}</td>
			<td style="padding:5px">
				<div class="dvtCellInfo checkbox">
					<label>
						<input type="checkbox" name="smtp_auth" class="" {if $ACCOUNT.smtp_auth eq 'true'}checked{/if}/>
					</label>
				</div>
			</td>
			<td width="20%"></td>
		</tr>
	</table>
	
	{* crmv@44037 *}
	<table border="0" cellpadding="0" cellspacing="5" width="100%" class="hideableTable" {if $ACCOUNT.account eq ''}style="display:none"{/if}>
		<tr>
			<td align="center" class="dvInnerHeader">
				<div><b>{'Signature'|getTranslatedString}</b></div>
			</td>
		</tr>
		<tr>
			<td>
				<textarea class="detailedViewTextBox" onFocus="this.className='detailedViewTextBoxOn'" name="signature" onBlur="this.className='detailedViewTextBox'" cols="90" rows="8">{$ACCOUNT.signature}</textarea>
				{if $FCKEDITOR_DISPLAY eq 'true'}
					{* crmv@42752 *}
					<script type="text/javascript">
						/* this is to have it working inside popups */
						window.CKEDITOR_BASEPATH = 'include/ckeditor/';
					</script>
					{* crmv@42752e *}
					<script type="text/javascript" src="include/ckeditor/ckeditor.js"></script>
					<script type="text/javascript">
						var current_language_arr = "{$AUTHENTICATED_USER_LANGUAGE}".split("_"); // crmv@181170
						var curr_lang = current_language_arr[0];
						var fldname = 'signature';
						{literal}
						jQuery(document).ready(function() {
							CKEDITOR.replace(fldname, {
								filebrowserBrowseUrl: 'include/ckeditor/filemanager/index.html',
								toolbar : 'Basic',
								language : curr_lang
							});
						});
						{/literal}
					</script>
				{/if}
			</td>
		</tr>
	</table>
	{* crmv@44037e *}
</form>