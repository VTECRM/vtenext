{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@32079 *}

{if $SERVER_ACCOUNT eq 'smtp'}
	<table width="100%"  border="0" cellspacing="0" cellpadding="5">
		{if $MAILSERVERNOTE neq ''}
			<tr>
				<td width="20%" nowrap class="cellLabel"></td>
				<td width="80%" class="cellText"><span class="helpmessagebox" style="font-style: italic;">{$MAILSERVERNOTE|getTranslatedString:'Settings'}</span></td>
			</tr>
		{/if}
		<tr>
			<td width="20%" nowrap class="cellLabel"><strong>* {$MOD.LBL_OUTGOING_MAIL_SERVER}</strong></td>
			<td width="80%" class="cellText">
				<div class="dvtCellInfo">
					<input type="text" class="detailedViewTextBox" value="{$MAILSERVER}" name="server" />
				</div>
		    </td>
		</tr>
		<tr>
			<td width="20%" nowrap class="cellLabel"><strong>{$MOD.LBL_OUTGOING_MAIL_PORT}</strong></td>
			<td width="80%" class="cellText">
				<div class="dvtCellInfo">
					<input type="text" class="detailedViewTextBox" value="{$MAILSERVERPORT}" name="port" />
				</div>
		    </td>
		</tr>
		<tr>
			<td nowrap class="cellLabel"><strong>{$MOD.LBL_USERNAME}</strong></td>
			<td class="cellText">
				<div class="dvtCellInfo">
					<input type="text" class="detailedViewTextBox" value="{$USERNAME}" name="server_username" />
				</div>
			</td>
		</tr>
		<tr>
			<td nowrap class="cellLabel"><strong>{$MOD.LBL_PASWRD}</strong></td>
			<td class="cellText">
				<div class="dvtCellInfo">
					{* crmv@43764 *}
					<input type="password" value="{if !empty($PASSWORD)}********{/if}" class="detailedViewTextBox" onFocus="this.value='';" onChange="document.getElementById('server_password').value=this.value;" />
					<input type="hidden" id="server_password" name="server_password" value="" />
					{* crmv@43764e *}
				</div>
	    	</td>
		</tr>
		<tr> 
			<td nowrap class="cellLabel"><strong>{$MOD.LBL_REQUIRES_AUTHENT}</strong></td>
			<td class="cellText">
				<input type="checkbox" name="smtp_auth" {$SMTP_AUTH} />
	    	</td>
		</tr>
	</table>
{elseif $SERVER_ACCOUNT eq 'imap'}
	<tr id="imap_account_div_{$i}">
		<td align="center">
			<input type="hidden" id="account_imap_deleted_{$i}" name="account_imap_deleted_{$i}" value="0" />
			<a href="javascript:;" onClick="jQuery('#imap_account_div_{$i}').hide();jQuery('#account_imap_deleted_{$i}').val(1);">
				<i class="vteicon" title="{'LBL_DELETE'|getTranslatedString}">clear</i>
			</a>
		</td>
		<td class="cellText">
			<div class="dvtCellInfo">
				<select name="account_imap_{$i}" onchange="VTE.Settings.EmailConfig.calculateAccount(this.value,'imap',{$i});" class="detailedViewTextBox">
					{foreach key=k item=v from=$ACCOUNT_IMAP_LIST}
						<option value="{$k}" {if $account.account_type eq $k}selected{/if}>{$v}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td class="cellText">
			<div class="dvtCellInfo">
				<input type="text" class="detailedViewTextBox" value="{$account.server}" name="server_imap_{$i}" />
			</div>
		</td>
		<td class="cellText">
			<div class="dvtCellInfo">
				<input type="text" class="detailedViewTextBox" value="{$account.port}" name="port_imap_{$i}" />
			</div>
		</td>
		<td class="cellText">
			<div class="dvtCellInfo">
				<select name="ssl_tls_imap_{$i}" class="detailedViewTextBox">
					<option value="" {if $account.ssl eq ''}selected{/if}>{$APP.LBL_NONE}</option>
					<option value="ssl" {if $account.ssl eq 'ssl'}selected{/if}>SSL</option>
					<option value="tls" {if $account.ssl eq 'tls'}selected{/if}>TLS</option>
				</select>
			</div>
		</td>
		<td class="cellText">
			<div class="dvtCellInfo">
				<input type="text" class="detailedViewTextBox" value="{$account.domain}" name="domain_{$i}" />
			</div>
		</td>
	</tr>
{/if}