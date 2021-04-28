{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@18592 crmv@54707 *}
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tr>
	<td valign="top"></td>
    <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

	<div align=center>
<!-- in setMenu table is opened and ends with one open td tag -->
		{include file='SetMenu.tpl'}
		{include file='Buttons_List.tpl'} {* crmv@30683 *} 
		<form name="EditView" method="POST" action="index.php" ENCTYPE="multipart/form-data">
		<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
		<input type="hidden" name="module" value="Settings">
		<input type='hidden' name='parenttab' value='Settings'>
		<input type="hidden" name="action">

		<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
		<tr>
			<td width=50 rowspan=2 valign=top><img src="{'menuSettings.gif'|resourcever}" width="48" height="48" border=0 ></td>
			<td class=heading2 valign=bottom><b><a href="index.php?module=Settings&action=menuSettings&parenttab=Settings">{$MOD.LBL_SETTINGS}</a> > {$MOD.LBL_MENU_TABS}</b></td>
		</tr>
		<tr>
			<td valign=top class="small">{$MOD.LBL_MENU_TABS_DESCRIPTION}</td>
		</tr>
		</table>
        <br>
        
        <div width="100%" height="30%" align="right"><input type="button" value="{$APP.LBL_EDIT_BUTTON}" class="crmButton small edit" onclick="window.location.href = 'index.php?module=Settings&action=menuSettings&parenttab=Settings&mode=edit'" ></div>
        
        <table class="tableHeading" border="0" cellpadding="5" cellspacing="0" width="100%">
        <tr>
        <td><strong>{$MOD.LBL_MENU_TYPE}</strong></td>
        <td class="small" align=right>&nbsp;</td>
        </tr>
        </table>
        <table class="small" border="0" cellpadding="5" cellspacing="0" width="100%">
        <tr>
			<td class="small cellLabel" width="50%">
				{if $MENU_LAYOUT.type eq 'modules'}
					{$MOD.LBL_MENU_MODULELIST}
				{else}
					{$MOD.LBL_MENU_TABLIST}
				{/if}
			</td>
			<td class="small cellLabel" width="50%">
				{if $ENABLE_AREAS eq 'checked'}
					<i class="vteicon md-sm checkok md-text nohover">check</i>
				{else}
					<i class="vteicon md-sm checkko md-text nohover">clear</i>
				{/if}
				<label for="enable_areas">{'LBL_AREAS'|getTranslatedString}</label>
			</td>
		</tr>
		<tr height="5"><td></td></tr>
        </table>
        
        <table class="tableHeading" border="0" cellpadding="5" cellspacing="0" width="100%">
        <tr>
        <td><strong>{$MOD.LBL_MENU_TABS_AVAIL}</strong></td>
        <td class="small" align=right>&nbsp;</td>
        </tr>
        </table>
		{if $MENU_LAYOUT.type eq 'modules'}
	        <table id="modules_Table" class="small" border="0" cellpadding="5" cellspacing="0" width="100%">
	        	<tr>
        		<td width=50% class="small colHeader" style="border-left: 1px solid #ddd;"><b>{$MOD.LBL_FAST_MODULES}</b></td>
				<td width=50% class="small colHeader"><b>{$MOD.LBL_OTHER_MODULES}</b></td>
	        	</tr>
	        	<tr valign="top" class="cellLabel">
					<td width=50%>
						<table cellspacing="0" cellpadding="0">
						{foreach key=id item=info from=$VisibleModuleList}
							<tr><td>{$info.name|getTranslatedString:$info.name}</td></tr>
						{/foreach}
						</table>
					 </td>
					<td width=50%>
					    <table cellspacing="0" cellpadding="0">
						{foreach key=id item=info from=$OtherModuleList}
							<tr><td>{$info.name|getTranslatedString:$info.name}</td></tr>
						{/foreach}
						</table>
					</td>
				</tr>
	        </table>
	    {else}
			<table id="tabs_Table" class="small" align="center" border="0" cellpadding="5" cellspacing="0" width="100%">
	        <tr>
	        	<td class="small colHeader" align="center" style="border-left: 1px solid #ddd;">{$MOD.LBL_MENU_TABS_ACTIVE}</td>
	            <td class="small colHeader">{$MOD.LBL_MENU_TABS_NAME}</td>
	        </tr>
			{foreach key=id item=tab from=$TABS}
				<tr>
	               	<td class="small cellLabel" width="30px" align="center" >
						{if $tab.hidden eq '0'}
							<i class="vteicon md-sm checkok md-text nohover">check</i>
						{else}
							<i class="vteicon md-sm checkko md-text nohover">clear</i>
						{/if}
	               	</td>
					<td class="small cellLabel">{$tab.parenttab_label|getTranslatedString}</td>
				</tr>
			{/foreach}
	        </table>
		{/if}
		</form>
		<!-- chiudo SetMenu.tpl i -->
		</td></tr></table>
		</td></tr></table>
		<!-- chiudo SetMenu.tpl e -->
	</div>
	</td>
	<td valign="top"></td>
	</tr>
</table>
{* crmv@18592e *}