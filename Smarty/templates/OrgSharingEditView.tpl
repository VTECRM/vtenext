{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">


	<div align=center>
			{include file="SetMenu.tpl"}
			{include file='Buttons_List.tpl'} {* crmv@30683 *} 
				<!-- DISPLAY -->
				<form action="index.php" method="post" name="def_org_share" id="form" onsubmit="VteJS_DialogBox.block();">
				<input type="hidden" name="module" value="Users">
				<input type="hidden" name="action" value="SaveOrgSharing">
				<input type="hidden" name="parenttab" value="Settings">
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
				<tr>
					<td width=50 rowspan=2 valign=top><img src="{'shareaccess.gif'|resourcever}" alt="{$MOD.LBL_USERS}" width="48" height="48" border=0 title="{$MOD.LBL_USERS}"></td>
					<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_EDIT} {$MOD.LBL_SHARING_ACCESS} </b></td> <!-- crmv@30683 -->
					<td rowspan=2 class="small" align=right>&nbsp;</td>
				</tr>
				<tr>
					<td valign=top class="small">{$MOD.LBL_SHARING_ACCESS_DESCRIPTION}</td>
				</tr>
				</table>

				<br>
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
				<tr>
					<td class="big"><strong>{$CMOD.LBL_GLOBAL_ACCESS_PRIVILEGES}</strong></td>
					<td class="small" align=right>
						<input class="crmButton small save" title="Save" accessKey="C" type="submit" name="Save" value="{$CMOD.LBL_SAVE_PERMISSIONS}">&nbsp;
						<input class="crmButton small cancel" title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" type="button" name="Cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onClick="window.history.back();">
					</td>
				</tr>
				</table>

	
				<table cellspacing="0" cellpadding="5" class="table" width="100%">
				{foreach item=elements from=$ORGINFO}	
					{assign var="MODULELABEL" value=$elements.0|@getTranslatedString:$elements.0}
				<tr>
                    		    <td width="30%" class="" nowrap>{$MODULELABEL}</td>
				    <td width="70%" class="">{$elements.2}</td>
				<tr>
				{/foreach}
				</table>
	</form>
	</table>

				{include file="Settings/ScrollTop.tpl"}
			
			
			
			</td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
		


</td>
        <td valign="top"></td>
   </tr>
</tbody>
</table>
<script>

function checkAccessPermission(share_value)
{ldelim}
	if (share_value == "3")
	{ldelim}
		alert("{$APP.ACCOUNT_ACCESS_INFO}");
		document.getElementById('2_perm_combo').options[3].selected=true
		document.getElementById('13_perm_combo').options[3].selected=true
		document.getElementById('20_perm_combo').options[3].selected=true
		document.getElementById('22_perm_combo').options[3].selected=true
		document.getElementById('23_perm_combo').options[3].selected=true

	{rdelim}
{rdelim}
</script>