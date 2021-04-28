{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/menu.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="include/js/picklist.js"></script>
<script language="JAVASCRIPT" src="modules/Home/Homestuff.js" type="text/javascript"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
	<td valign="top"></td>
	<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

	<div align=center> 
		{include file='SetMenu.tpl'}
		{include file='Buttons_List.tpl'} {* crmv@30683 *}
		<!-- DISPLAY -->
		<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
		<tr>
			<td width=50 rowspan=2 valign=top><img src="{'picklist.gif'|resourcever}" width="48" height="48" border=0 ></td>
			{if $smarty.request.module_manager eq 'yes'}
			<td class="heading2" valign="bottom">
				<b><a href="index.php?module=Settings&action=ModuleManager&parenttab=Settings">{$MOD.VTLIB_LBL_MODULE_MANAGER}</a>
				&gt;<a href="index.php?module=Settings&action=ModuleManager&module_settings=true&formodule={$MODULE}&parenttab=Settings">{if $APP.$MODULE } {$APP.$MODULE} {elseif $MOD.$MODULE} {$MOD.$MODULE} {else} {$MODULE} {/if}</a> &gt;
				{$MOD.LBL_PICKLIST_EDITOR}</b>
			</td>
			{else}
			<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_PICKLIST_EDITOR}</b></td> <!-- crmv@30683 -->
			{/if}
		</tr>
		<tr>
			<td valign=top class="small">{$MOD.LBL_PICKLIST_DESCRIPTION}</td>
		</tr>
		</table>

		<table border=0 cellspacing=0 cellpadding=10 width=100% >
		<tr>
			<td valign=top>
			<table border=0 cellspacing=0 cellpadding=0 width=100% class="tableHeading">
			<tr>
				<td class="small" width="20%" nowrap>
					<strong>{$MOD.LBL_SELECT_MODULE}</strong>&nbsp;&nbsp;
				</td>
				<td class="dvtCellInfo" align="left" width="30%">
					{if $smarty.request.module_manager eq 'yes'}
						{$MODULE|getTranslatedString:$MODULE}
					{/if}
					<select name="pickmodule" id="pickmodule" class="detailedViewTextBox" onChange="changeModule();" {if $smarty.request.module_manager eq 'yes'}style="display:none"{/if}>
					{foreach key=module item=modulelabel from=$MODULE_LISTS}
						{if $MODULE eq $module}
							<option value="{$module}" selected>{$modulelabel}</option>
						{else}
							<option value="{$module}">{$modulelabel}</option>
						{/if}
					{/foreach}
					</select>
				</td>
				<td class="small" align="right">&nbsp;</td>
			</tr>
			<tr height="10"><td colspan="2"></td></tr>
			</table>

			<table border=0 cellspacing=0 cellpadding=0 width=100% class="tableHeading">
			<tr>
				<td class="big" rowspan="2">
				<div id="picklist_datas">	
					{include file='modules/PickList/PickListContents.tpl'}
				</div>
				</td>
			</tr>
			</table>

			{include file='Settings/ScrollTop.tpl'}
			</td>
		</tr>
		</table>
	</div>
	</td>
</tr>
</tbody>
</table>

<div id="actiondiv" style="display:block;position:absolute;"></div>
<div id="editdiv" style="display:block;position:absolute;width:510px;"></div>
			