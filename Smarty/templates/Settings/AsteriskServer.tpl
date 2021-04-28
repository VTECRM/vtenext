{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
	<div align=center>
			{include file="SetMenu.tpl"}
			{include file='Buttons_List.tpl'} {* crmv@30683 *} 
				<!-- DISPLAY -->
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
				<form action="index.php" method="post" name="tandc">
				<input type="hidden" name="server_type" value="asterisk">
				<input type="hidden" name="module" value="Settings">
				<input type="hidden" name="action" value="index">
				<input type="hidden" name="asterisk_server_mode">
				<input type="hidden" name="parenttab" value="Settings">
				<tr>
					<td width=50 rowspan=2 valign=top><img src="{'ogasteriskserver.gif'|resourcever}" alt="{$MOD.LBL_ASTERISK}" width="48" height="48" border=0 title="{$MOD.LBL_ASTERISK}"></td>
					<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_ASTERISK_SERVER_SETTINGS} </b></td> <!-- crmv@30683 -->
				</tr>
				<tr>
					<td valign=top class="small">{$MOD.LBL_ASTERISK_SERVER_DESC} </td>
				</tr>
				</table>
				
				<br>
				<table border=0 cellspacing=0 cellpadding=10 width=100% >
				<tr>
				<td>
				
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
					<tr>
						<td class="big"><strong>{$MOD.LBL_ASTERISK_SERVER_SETTINGS}<br>{$ERROR_MSG}</strong></td>
						{if $ASTERISK_SERVER_MODE neq 'edit'}
						{if $ACTIVE eq 'yes'}
							<strong>{$MOD.LBL_ASTERISK_SERVER_DISABLE}<input type="checkbox" name="disable" id="disable"  onClick="this.form.action.value='Save';this.form.submit();">
							{$MOD.STATUS} : <font color="red"><strong>{$MOD.ACTIVE}</strong></font>
						{else}
							{$MOD.STATUS} : <font color="red"><strong>{$MOD.NOT_ACTIVE}</strong></font>	
						{/if}
																				
						<td class="small" align=right>
							<input title="{$APP.LBL_EDIT_BUTTON_TITLE}" accessKey="{$APP.LBL_EDIT_BUTTON_KEY}" class="crmButton small edit" onclick="this.form.action.value='AsteriskConfig';this.form.asterisk_server_mode.value='edit'" type="submit" name="Edit" value="{$APP.LBL_EDIT_BUTTON_LABEL}">

						</td>
						{else}
						<td class="small" align=right>
							<input title="{$APP.LBL_SAVE_BUTTON_LABEL}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="crmButton small save" type="submit" name="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" onclick="this.form.action.value='Save'; return validate()">&nbsp;&nbsp;
						    <input title="{$APP.LBL_CANCEL_BUTTON_LABEL}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmButton small cancel" onclick="javascript:document.location.href='index.php?module=Settings&action=AsteriskConfig&parenttab=Settings'" type="button" name="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}">
						</td>
						{/if}
					</tr>
					</table>
				
			{if $ASTERISK_SERVER_MODE eq 'edit'}	
			<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
			<tr>
      			    <td class="small" valign=top ><table width="100%"  border="0" cellspacing="0" cellpadding="5">
                        <tr>
                            <td width="20%" nowrap class="small cellLabel"><font color="red">*</font><strong>{$MOD.LBL_SERVER_ADDRESS} </strong></td>
                            <td width="80%" class="small cellText">
				{if $smarty.request.server neq ''}
				<input type="text" class="detailedViewTextBox small" value="{$smarty.request.server}" name="server" id="server">
				{else}
				<input type="text" class="detailedViewTextBox small" value="{$ASTERISKSERVER}" name="server" id="server">
				{/if}
			    </td>
                          </tr>
			  <tr>
                            <td width="20%" nowrap class="small cellLabel"><font color="red">*</font><strong>{$MOD.LBL_ASTERISK_PORT} </strong></td>
                            <td width="80%" class="small cellText">
				{if $smarty.request.port neq ''}
                                <input type="text" class="detailedViewTextBox small" value="{$smarty.request.port}" name="port" id="port">
				{else}
                                <input type="text" class="detailedViewTextBox small" value="{$ASTERISKPORT}" name="port" id="port">
				{/if}
                            </td>
                          </tr>
                          <tr valign="top">

                            <td nowrap class="small cellLabel"><font color="red">*</font><strong>{$MOD.LBL_USERNAME}</strong></td>
                            <td class="small cellText">
				{if $smarty.request.server_username neq ''}
				<input type="text" class="detailedViewTextBox small" value="{$smarty.request.server_username}" name="server_username" id="server_username">
				{else}
				<input type="text" class="detailedViewTextBox small" value="{$ASTERISKUSER}" name="server_username" id="server_username">
				{/if}
			    </td>
                          </tr>
                          <tr>
                            <td nowrap class="small cellLabel"><font color="red">*</font><strong>{$MOD.LBL_PASWRD}</strong></td>
                            <td class="small cellText">
				<input type="password" class="detailedViewTextBox small" value="{$ASTERISKPASSWORD}" name="server_password" id="server_password">
			    </td>
                          </tr>
                          <tr>
                            <td nowrap class="small cellLabel"><font color="red">*</font><strong>{$MOD.LBL_INC_CALL}</strong></td>
                            <td class="small cellText">
				<input type="checkbox" class="detailedViewTextBox small" value="1" name="inc_call" id="inc_call" {if ($ASTERISKINC_CALL eq 1)} checked {/if}>
			    </td>
                          </tr>
                        </table>
			{else}
			<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
			<tr>
	         	    <td class="small" valign=top ><table width="100%"  border="0" cellspacing="0" cellpadding="5">
                        <tr>
                            <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_SERVER_ADDRESS} </strong></td>
                            <td width="80%" class="small cellText"><strong>{$ASTERISKSERVER}&nbsp;</strong></td>
                        </tr>
			<tr valign="top">
                            <td nowrap class="small cellLabel"><strong>{$MOD.LBL_ASTERISK_PORT}</strong></td>
                            <td class="small cellText">{$ASTERISKPORT}&nbsp;</td>
                        </tr>
                        <tr valign="top">
                            <td nowrap class="small cellLabel"><strong>{$MOD.LBL_USERNAME}</strong></td>
                            <td class="small cellText">{$ASTERISKUSER}&nbsp;</td>
                        </tr>
                        <tr>
                            <td nowrap class="small cellLabel"><strong>{$MOD.LBL_PASWRD}</strong></td>
                            <td class="small cellText">
				{if $ASTERISKPASSWORD neq ''}
				******
				{/if}&nbsp;
				</tr>
				<tr valign="top">
                            <td nowrap class="small cellLabel"><strong>{$MOD.LBL_INC_CALL}</strong></td>
                            <td class="small cellText">
								{if ($ASTERISKINC_CALL eq 1)}
										{$MOD.LBL_YES}
								{else} {$MOD.LBL_NO}
								{/if}
			    </td>
                        </tr>
                        </table>
					
			{/if}				
						</td>
					  </tr>
					</table>
					<!--table border=0 cellspacing=0 cellpadding=5 width=100% >
					<tr>
					  <td class="small" nowrap align=right><a href="#top">{$MOD.LBL_SCROLL}</a></td>
					</tr>
					</table-->
				</td>
				</tr>
				</table>
			
			
			
			</td>
			</tr>
			</table>
		</td>
	</tr>
	</form>
	</table>
		
	</div>
</td>
        <td valign="top"></td>
   </tr>
</tbody>
</table>
{literal}
<script>
function validate() {
	if (!emptyCheck("server","Asterisk Server Name","text")) return false
	if (!emptyCheck("port","Port Number","text")) return false
	if(isNaN(document.tandc.port.value)){
		alert(alert_arr.LBL_ENTER_VALID_PORT);
		return false;
	}
	if (!emptyCheck("server_username","Asterisk User Name","text")) return false
	if (!emptyCheck("server_password","Asterisk Password","text")) return false
			return true;

}
function disable(check) {
	document.Editview.action.value='Save';
	document.Editview.submit();
}
</script>
{/literal}