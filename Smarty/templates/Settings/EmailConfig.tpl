{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@32079 *}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/menu.js"|resourcever}"></script>
<script type="text/javascript" src="{"modules/Settings/resources/EmailConfig.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
				{if $EMAILCONFIG_MODE neq 'edit'}	
					<form action="index.php" method="post" name="MailServer" id="form" onsubmit="VteJS_DialogBox.block();">
					<input type="hidden" name="emailconfig_mode">
				{else}
					{literal}
					<form action="index.php" method="post" name="MailServer" id="form" onsubmit="if (VTE.Settings.EmailConfig.validate_mail_server(MailServer)) { VteJS_DialogBox.block(); return true; } else { return false; }">
					{/literal}
					<input type="hidden" name="server_type" value="email">
				{/if}
                <input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
				<input type="hidden" name="module" value="Settings">
				<input type="hidden" name="action">
				<input type="hidden" name="parenttab" value="Settings">
				<input type="hidden" name="return_module" value="Settings">
				<input type="hidden" name="return_action" value="EmailConfig">
			
				{include file="SetMenu.tpl"}
				{include file='Buttons_List.tpl'} {* crmv@30683 *}
		 
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
					<tr>
						<td width=50 rowspan=2 valign=top><img src="{'ogmailserver.gif'|resourcever}" alt="{$MOD.LBL_USERS}" width="48" height="48" border=0 title="{$MOD.LBL_USERS}"></td>
						<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_MAIL_SERVER_SETTINGS} </b></td> <!-- crmv@30683 -->
					</tr>
					<tr>
						<td valign=top>{$MOD.LBL_MAIL_SERVER_DESC}</td>
					</tr>
				</table>
			
				<br>
			
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
					<tr>
						<td class="big"><strong>{$MOD.LBL_MAIL_SERVER_SMTP}</strong></td>
						{if $EMAILCONFIG_MODE neq 'edit'}	
							<td align=right>
								<button class="crmbutton edit" onclick="this.form.action.value='EmailConfig';this.form.emailconfig_mode.value='edit'" type="submit" name="Edit">{$APP.LBL_EDIT_BUTTON_LABEL}</button>
							</td>
						{else}
							<td align=right>
								<button class="crmbutton save" onclick="this.form.action.value='Save';" type="submit" name="button">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
								<button class="crmbutton cancel" onclick="window.history.back()" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
							</td>
						{/if}
					</tr>
					{if $ERROR_MSG neq ''}
						<tr>
							{$ERROR_MSG}
						</tr>
					{/if}
				</table>
					
				{if $EMAILCONFIG_MODE neq 'edit'}
					<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
						<tr>
							<td valign=top>
								<table width="100%" border="0" cellspacing="0" cellpadding="5">
									{if $SMTP_EDITABLE eq 1}	{* crmv@94084 *}
										<tr>
											<td width="20%" nowrap class="cellLabel"><strong>Account</strong></td>
											<td width="80%" class="cellText">{if $ACCOUNT_SMTP eq ''}{$MOD.LBL_ACCOUNT_MAIL_UNDEFINED}{elseif $ACCOUNT_SMTP eq 'Other'}{$MOD.LBL_ACCOUNT_MAIL_OTHER}{else}{$ACCOUNT_SMTP}{/if}</td>
										</tr>
									{/if}	{* crmv@94084 *}
									{if $ACCOUNT_SMTP neq ''}
										{* crmv@94084 *}
										<tr>
											<td width="20%" nowrap class="cellLabel"><strong>{$MOD.LBL_OUTGOING_MAIL_SERVER}</strong></td>
											<td width="80%" class="cellText">{$MAILSERVER}</td>
										</tr>
										{if $SMTP_EDITABLE eq 1}
											<tr>
												<td nowrap class="cellLabel"><strong>{$MOD.LBL_OUTGOING_MAIL_PORT}</strong></td>
												<td class="cellText">{$MAILSERVERPORT}</td>
											</tr>
											<tr valign="top">
												<td nowrap class="cellLabel"><strong>{$MOD.LBL_USERNAME}</strong></td>
												<td class="cellText">{$USERNAME}</td>
											</tr>
											<tr>
												<td nowrap class="cellLabel"><strong>{$MOD.LBL_PASWRD}</strong></td>
												<td class="cellText">
													{if $PASSWORD neq ''}******{/if}
												</td>
											</tr>
											<tr> 
												<td nowrap class="cellLabel"><strong>{$MOD.LBL_REQUIRES_AUTHENT}</strong></td>
												<td class="cellText">
													{if $SMTP_AUTH eq 'checked'}
														{$MOD.LBL_YES}
													{else}
														{$MOD.LBL_NO}
													{/if}
												</td>
											</tr>
										{/if}
										{* crmv@94084e *}
									{/if}
								</table>
							</td>
						</tr>
					</table>

					<br>

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
						<tr>
							<td class="big"><strong>{$MOD.LBL_MAIL_SERVER_IMAP}</strong></td>
						</tr>
					</table>
					{if empty($IMAP_ACCOUNTS)}
						<table width="100%" border="0" cellspacing="0" cellpadding="5">
							<tr>
								<td class="cellText">{$MOD.LBL_ACCOUNT_MAIL_UNDEFINED}</td>
							</tr>
						</table>
					{else}
						<table class="vtetable">
							<thead>
								<tr>
									<th width="20%" class="text-nowrap">Account</th>
									<th width="20%" class="text-nowrap">{$MOD.LBL_OUTGOING_MAIL_SERVER}</th>
									<th width="20%" class="text-nowrap">{$MOD.LBL_OUTGOING_MAIL_PORT}</th>
									<th width="20%" class="text-nowrap">SSL/TLS</th>
									<th width="20%" class="text-nowrap">{$MOD.LBL_DOMAIN}</th>
								</tr>
							</thead>
							<tbody>
								{foreach item=account from=$IMAP_ACCOUNTS}
									<tr>
										<td>{if $account.account_type eq 'Other'}{$MOD.LBL_ACCOUNT_MAIL_OTHER}{else}{$account.account_type}{/if}</td>
										<td>{$account.server}</td>
										<td>{$account.port}</td>
										<td>{if $account.ssl eq 'ssl'}SSL{elseif $account.ssl eq 'tls'}TLS{/if}</td>
										<td>{$account.domain}</td>
									</tr>
								{/foreach}
							</tbody>
						</table>
					{/if}
				{else}
					<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
						<tr>
							<td valign=top>
								{* crmv@94084 *}
								{if $SMTP_EDITABLE eq 1}
									<table width="100%" border="0" cellspacing="0" cellpadding="5">
										<tr>
											<td width="20%" nowrap class="cellLabel"><strong>Account</strong></td>
											<td width="80%" class="cellText">
												<div class="dvtCellInfo">
													<select name="account_smtp" onchange="VTE.Settings.EmailConfig.calculateAccount(this.value,'smtp');" class="detailedViewTextBox">
														{foreach key=i item=v from=$ACCOUNT_SMTP_LIST}
															<option value="{$i}" {if $ACCOUNT_SMTP eq $i}selected{/if}>{$v}</option>
														{/foreach}
													</select>
												</div>
											</td>
										</tr>
									</table>
									<div id="account_container_smtp">
										{if $ACCOUNT_SMTP neq ''}
											{assign var="SERVER_ACCOUNT" value="smtp"}
											{include file="Settings/EmailConfigAccount.tpl"}
										{/if}
									</div>
								{else}
									<table width="100%"  border="0" cellspacing="0" cellpadding="5">
										<tr>
											<td width="20%" nowrap class="cellLabel"><strong>* {$MOD.LBL_OUTGOING_MAIL_SERVER}</strong></td>
											<td width="80%" class="cellText">
												<div class="dvtCellInfoOff">
													<input type="text" class="detailedViewTextBox" value="{$MAILSERVER}" name="server" readonly />
												</div>
											</td>
										</tr>
									</table>
								{/if}
								{* crmv@94084e *}
							</td>
						</tr>
					</table>

					<br>
							
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
						<tr>
							<td class="big"><strong>{$MOD.LBL_MAIL_SERVER_IMAP}</strong></td>
						</tr>
					</table>

					<table border=0 cellspacing=0 cellpadding=0 width=100%>
						<tr>
							<td valign=top>
								<table class="vtetable" id="account_container_imap">
									<thead>
										<tr>
											<th>{'LBL_ACTIONS'|getTranslatedString}</th>
											<th width="20%" class="text-nowrap">Account</th>
											<th width="20%" class="text-nowrap">{$MOD.LBL_OUTGOING_MAIL_SERVER}</th>
											<th width="20%" class="text-nowrap">{$MOD.LBL_OUTGOING_MAIL_PORT}</th>
											<th width="20%" class="text-nowrap">SSL/TLS</th>
											<th width="20%" class="text-nowrap">{$MOD.LBL_DOMAIN}</th>
										</tr>
									</thead>
									<tbody>
										{assign var="SERVER_ACCOUNT" value="imap"}
										{foreach key=i item=account from=$IMAP_ACCOUNTS}
											{include file="Settings/EmailConfigAccount.tpl"}
										{/foreach}
									</tbody>
								</table>
								<table border="0" cellpadding="0" cellspacing="5" width="100%">
									<tr>
										<td>
											<button type="button" class="crmbutton create" onclick="VTE.Settings.EmailConfig.addAccount('{$SERVER_ACCOUNT}');">{'LBL_ADD_BUTTON'|getTranslatedString}</button>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				{/if}
				
				{* SetMenu.tpl *}
				</td>
				</tr>
				</table>
				</td>
				</tr>
				</table>
				</form>
			</td>
			<td valign="top"></td>
		</tr>
	</tbody>
</table>