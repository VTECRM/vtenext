{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 ********************************************************************************/

*}

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tr>
    <td valign="top"></td>
    <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
    <div align=center>

	{include file='SetMenu.tpl'}
	{include file='Buttons_List.tpl'} {* crmv@30683 *} 
{literal}
<form name="newProfileForm" action="index.php" onsubmit="if(roleDeleteValidate()) { VteJS_DialogBox.block();} else { return false; }">
{/literal}
<input type="hidden" name="module" value="Users">
<input type="hidden" name="action" value="DeleteRole">
<input type="hidden" name="delete_role_id" value="{$ROLEID}">	
<table width="100%" border="0" cellpadding="3" cellspacing="0">
<tr>
	<td class="genHeaderSmall" align="left" style="border-bottom:1px solid #CCCCCC;" width="50%">{$CMOD.LBL_DELETE_ROLE}</td>
	<td style="border-bottom:1px solid #CCCCCC;">&nbsp;</td>
	<td align="right" style="border-bottom:1px solid #CCCCCC;" width="40%"><a href="#" onClick="window.history.back();">{$APP.LBL_BACK}</a></td>
</tr>
<tr>
	<td colspan="3">&nbsp;</td>
</tr>
<tr>
	<td width="50%"><b>{$CMOD.LBL_ROLE_TO_BE_DELETED}</b></td>
	<td width="2%"><b>:</b></td>
	<td width="48%"><b>{$ROLENAME}</b></td>
</tr>
<tr>
	<td style="text-align:left;"><b>{$CMOD.LBL_TRANSFER_USER_ROLE}</b></td>
	<td ><b>:</b></td>
	<td align="left">
	<input type="text" name="role_name"  id="role_name" value="" class="txtBox" readonly="readonly">&nbsp;
        	{$ROLEPOPUPBUTTON}
        <input type="hidden" name="user_role" id="user_role" value="">	
           
	</td>
</tr>
<tr><td colspan="3" style="border-bottom:1px dashed #CCCCCC;">&nbsp;</td></tr>
<tr>
    <td colspan="3" align="center"><input type="submit" name="Delete" value="{$APP.LBL_SAVE_BUTTON_LABEL}" class="crmbutton small save">
	</td>
</tr>
</table>
</form></div>
</td>
</tr>
</table>

</td>
</tr>
</table>
</td>
</tr>
</table>
</div>
<td valign="top"></td>
</tr>
</table>
<br>
<script>
{literal}
function roleDeleteValidate()
{
	if(document.getElementById('role_name').value == '')
	{
		{/literal}
                alert('{$APP.SPECIFY_ROLE_INFO}');
                return false;
                {literal}
	}
	return true;
}
{/literal}
</script>
