{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script type="text/javascript" src="{"modules/Settings/resources/MailScanner.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tbody>
<tr>
	<td valign="top"></td>
    <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">
		<div align=center>
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'} {* crmv@30683 *}
				<!-- DISPLAY -->
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
				<tr>
					<td width=50 rowspan=2 valign=top><img src="{'mailScanner.gif'|resourcever}" alt="{$MOD.LBL_MAIL_SCANNER}" width="48" height="48" border=0 title="{$MOD.LBL_MAIL_SCANNER}"></td>
					<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_MAIL_SCANNER}</b></td> <!-- crmv@30683 -->
				</tr>
				<tr>
					<td valign=top class="small">{$MOD.LBL_MAIL_SCANNER_DESCRIPTION}</td>
				</tr>
				</table>
				
				<br>
				<table border=0 cellspacing=0 cellpadding=10 width=100% >
				
				<tr>
					<td>
						<table border=0 cellspacing=0 cellpadding=2 width=100% class="tableHeading">
						<tr>
							<td class="big" width="70%"><strong>{$MOD.LBL_MAILBOX}</strong></td>
							<td width="30%" nowrap align="right">
								<a href="index.php?module=Settings&action=MailScanner&parenttab=Settings&mode=edit&scannername="><img src="{'btnL3Add.gif'|resourcever}" border="0" /></a>
							</td>								
						</tr>
						</table>
					</td>
				</tr>
				
				{foreach item=SCANNER from=$SCANNERS}				
				
				{assign var="SCANNERINFO" value=$SCANNER->getAsMap()}				
				<tr>
				<td>

				<form action="index.php" method="post" id="form" onsubmit="VteJS_DialogBox.block();">
				<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
				<input type='hidden' name='module' value='Settings'>
				<input type='hidden' name='action' value='MailScanner'>
				<input type='hidden' name='mode' value='edit'>
				<input type='hidden' name='scannername' value='{$SCANNERINFO.scannername}'>
				<input type='hidden' name='return_action' value='MailScanner'>
				<input type='hidden' name='return_module' value='Settings'>
				<input type='hidden' name='parenttab' value='Settings'>
		
				{* When mode is Ajax, xmode will be set *}
				<input type='hidden' name='xmode' value=''>
				<input type='hidden' name='file' value=''>
		
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
				<tr>
				<td class="big" width="70%"><strong>{$SCANNERINFO.scannername} {$MOD.LBL_INFORMATION}</strong></td>
				<td width="30%" nowrap align="right">
				{if $SCANNERINFO.isvalid eq true}

					{if $SCANNERINFO.rules neq false}
						<input type="button" class="crmbutton small create" value="{$MOD.LBL_SCAN_NOW}" 
						onclick="VTE.Settings.MailScannerInfo.performScanNow('{$APP_KEY}','{$SCANNERINFO.scannername|@decode_html|@addslashes|@to_html}')" />
					{/if}

					<input type="submit" class="crmbutton small create" onclick="this.form.mode.value='folder'" value="{$MOD.LBL_SELECT} {$MOD.LBL_FOLDERS}" />
					<input type="submit" class="crmbutton small save" onclick="this.form.mode.value='rule'" value="{$MOD.LBL_SETUP} {$MOD.LBL_RULE}" />
				{/if}
				<input type="submit" class="crmbutton small create" value="{$APP.LBL_EDIT}" />
			     
				<input type="submit" class="crmbutton small delete" onclick="if(confirm(alert_arr.ARE_YOU_SURE)){ldelim}with(this.form) {ldelim}action.value='SettingsAjax';file.value='MailScanner';mode.value='Ajax';xmode.value='remove';{rdelim}{rdelim}else return false;" value="{$MOD.LBL_DELETE}" />
				</td>
				</tr>
				</table>
				
				<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
				<tr>
	         	    <td class="small" valign=top ><table width="100%"  border="0" cellspacing="0" cellpadding="5">
						<tr>
                            <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_SCANNER} {$MOD.LBL_NAME}</strong></td>
                            <td width="80%" class="small cellText">{$SCANNERINFO.scannername}</td>
                        </tr>
                        <tr>
                            <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_SERVER} {$MOD.LBL_NAME}</strong></td>
                            <td width="80%" class="small cellText">{$SCANNERINFO.server}</td>
                        </tr>
                        <tr>
							<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_PROTOCOL}</strong></td>
			                <td width="80%" class="small cellText">{$SCANNERINFO.protocol}</td>
						</tr>
						<tr>
			                <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_USERNAME}</strong></td>
               				<td width="80%" class="small cellText">{$SCANNERINFO.username}</td>
                        </tr>
						<tr>
			                <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_SSL} {$MOD.LBL_TYPE}</strong></td>
               				<td width="80%" class="small cellText">{$SCANNERINFO.ssltype}</td>
                        </tr>
						<tr>
			                <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_SSL} {$MOD.LBL_METHOD}</strong></td>
               				<td width="80%" class="small cellText">{$SCANNERINFO.sslmethod}</td>
                        </tr>
						<tr>
			                <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_CONNECT} {$MOD.LBL_URL_CAPS}</strong></td>
               				<td width="80%" class="small cellText">{$SCANNERINFO.connecturl}</td>
                        </tr>
                        {* crmv@178441 *}
                        <tr>
			                <td width="20%" nowrap class="small cellLabel" valign="top"><strong>{$MOD.LBL_PARAMETERS}</strong></td>
               				<td width="80%" class="small cellText">
               					{foreach from=$SCANNERINFO.imap_params item=param}
               						<div><b>{$param.name}</b>: {$param.value}</div>
               					{/foreach}
               				</td>
                        </tr>
                        <tr>
			                <td width="20%" nowrap class="small cellLabel"><strong>PEC</strong></td>
               				<td width="80%" class="small cellText">
               					{if $SCANNERINFO.is_pec eq true}<font color=green><b>{$MOD.LBL_ENABLED}</b></font>
								{elseif $SCANNERINFO.is_pec eq false}<font color=red><b>{$MOD.LBL_DISABLED}</b></font>{/if}
               				</td>
                        </tr>
                        {* crmv@178441e *}
						<tr>
			                <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_STATUS}</strong></td>
               				<td width="80%" class="small cellText">
								{if $SCANNERINFO.isvalid eq true}<font color=green><b>{$MOD.LBL_ENABLED}</b></font>
								{elseif $SCANNERINFO.isvalid eq false}<font color=red><b>{$MOD.LBL_DISABLED}</b></font>{/if}
							</td>
                        </tr></table>
				    </td>
            	</tr>
				</table>	
				
				{if $SCANNERINFO.isvalid}
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
					<tr>
					<td class="big" width="70%"><strong>{$MOD.LBL_SCANNING} {$MOD.LBL_INFORMATION}</strong></td>
					<td width="30%" nowrap align="right">&nbsp;</td>
					</tr>
					</table>

					<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
					<tr>
	        	 	    <td class="small" valign=top ><table width="100%"  border="0" cellspacing="0" cellpadding="5">
							<tr>
                    	        <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_LOOKFOR}</strong></td>
                        	    <td width="80%" class="small cellText">
									{if $SCANNERINFO.searchfor eq 'ALL'}{$MOD.LBL_ALL}
									{elseif $SCANNERINFO.searchfor eq 'UNSEEN'}{$MOD.LBL_UNREAD}{/if}
									{$MOD.LBL_MESSAGES_FROM_LASTSCAN}
									{if $SCANNERINFO.requireRescan} ({$MOD.LBL_RESCAN_FOLDERS}) {/if} {* crmv@111580 *}
								</td>
                        	</tr>
                        	{* crmv@2043m *}
							<tr valign="top">
                           		<td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_AFTER_SCAN}</strong></td>
                           		<td width="80%" class="small cellText">
									{if $SCANNERINFO.markas eq 'SEEN'}{$MOD.LBL_MARK_MESSAGE_AS} {$MOD.LBL_READ}<br />{/if}
									{if $SCANNERINFO.succ_moveto neq '' || $SCANNERINFO.no_succ_moveto neq ''}
										{$MOD.LBL_MOVE_MESSAGE} {if $SCANNERINFO.succ_moveto neq ''}{$SCANNERINFO.succ_moveto}{else}''{/if} {$MOD.LBL_MOVE_MESSAGE_ELSE} {if $SCANNERINFO.no_succ_moveto neq ''}{$SCANNERINFO.no_succ_moveto}{else}''{/if}
									{/if}
								</td>
    	                    </tr>
    	                    {* crmv@2043me *}
						</td></table>
					</tr>
					</table>
				{/if}
				</form>
				
				</td>
				</tr>
				
				{/foreach}
				
				</table>
			
			</td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
		
	</div>

</td>
        <td valign="top"></td>
   </tr>
</tbody>
</form>
</table>

</tr>
</table>

</tr>
</table>