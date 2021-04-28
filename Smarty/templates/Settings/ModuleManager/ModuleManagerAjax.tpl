{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@104853 *}

<script type="text/javascript">
{literal}
function vtlib_modulemanager_toggleTab(shownode, hidenode, highlighttab, dehighlighttab) {
	// crmv@192033
	jQuery('#'+shownode).show();
	jQuery('#'+hidenode).hide();
	jQuery('#'+highlighttab).addClass('dvtSelectedCell').removeClass('dvtUnSelectedCell');
	jQuery('#'+dehighlighttab).addClass('dvtUnSelectedCell').removeClass('dvtSelectedCell');
	// crmv@192033e
}
{/literal}
</script>

{if $DIR_NOTWRITABLE_LIST && !empty($DIR_NOTWRITABLE_LIST)}
<table class="small" width="100%" cellpadding=0 cellspacing=0 border=0>
	<tr>
		<td>
			<div style='background-color: #FFFABF; padding: 2px; margin: 0 0 2px 0; border: 1px solid yellow'>
			<b style='color: red'>{$MOD.VTLIB_LBL_WARNING}:</b> {$DIR_NOTWRITABLE_LIST|@implode:', '} <b>{$MOD.VTLIB_LBL_NOT_WRITEABLE}!</b>
		</td>
	</tr>
</table>
{/if}

<table class="small" width="50%" style="margin-top:20px;height:35px">
	<tr>
		<td class="dvtSelectedCell" style="width: 120px;" align="center" nowrap id="modmgr_standard_tab"
			onclick="vtlib_modulemanager_toggleTab('modmgr_standard','modmgr_custom','modmgr_standard_tab','modmgr_custom_tab');">
		{$MOD.VTLIB_LBL_MODULE_MANAGER_STANDARDMOD}</td>
		<td class="dvtTabCache" style="width: 10px;" nowrap>&nbsp;</td>
		<td class="dvtUnSelectedCell" style="width: 120px;" align="center" nowrap id="modmgr_custom_tab"
			onclick="vtlib_modulemanager_toggleTab('modmgr_custom','modmgr_standard','modmgr_custom_tab','modmgr_standard_tab');">
		{$MOD.VTLIB_LBL_MODULE_MANAGER_CUSTOMMOD}</td>
	</tr>
</table>

<!-- Custom Modules -->
<table class="table table-hover" id="modmgr_custom" style='display: none;'>
	{* crmv@64542 *}
	{* this row is here only to space the last columns of the next rows *}
	<thead>
		<tr height="35px"><th colspan="3"></th><tr>
	</thead>
	<tr>
		<td class="big tableHeading" colspan="3" align="right">
			{if $CAN_IMPORT_CUSTOM_MODULE}
			<form style="display: inline;" action="index.php?module=Settings&amp;action=ModuleManager&amp;module_import=Step1&amp;parenttab=Settings" method="POST">
				<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
				<input type="submit" class="crmbutton small create" value='{$MOD.LBL_IMPORT_NEW_MODULE}' title='{$APP.LBL_IMPORT}'>
			</form>
			{/if}
			{if $CAN_CREATE_CUSTOM_MODULE}
			<form style="display: inline;" action="index.php?module=Settings&amp;action=ModuleMaker&amp;mode=create&amp;module_maker_step=1" method="POST">
				<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
				<input type="submit" class="crmbutton small create" value="{$MOD.LBL_CREATE_NEW_MODULE}" title="{$MOD.LBL_CREATE_NEW_MODULE}" />
			</form>
			{/if}
		</td>
	</tr>
	{* crmv@64542e *}

	{assign var="totalCustomModules" value="0"}

	{foreach key=modulename item=modinfo from=$TOGGLE_MODINFO}
		{if $modinfo.customized eq true}
			{assign var="totalCustomModules" value=$totalCustomModules+1}
			{assign var="modulelabel" value=$modulename|@getTranslatedString:$modulename}
			<tr>
				<td width="15%" class="cell-vcenter">
					{if $modinfo.presence eq 0}
						<a href="javascript:void(0);" onclick="vtlib_toggleModule('{$modulename}', 'module_disable', '', '{$modulelabel}');"><i class="vteicon checkok" title="{$MOD.LBL_DISABLE} {$modulelabel}">check</i></a>
					{else}
						<a href="javascript:void(0);" onclick="vtlib_toggleModule('{$modulename}', 'module_enable', '', '{$modulelabel}');"><i class="vteicon checkko" title="{$MOD.LBL_ENABLE} {$modulelabel}">clear</i></a>
					{/if}
					
					&nbsp;&nbsp;
					
					<a href="index.php?module=Settings&action=ModuleManager&module_update=Step1&src_module={$modulename}&parenttab=Settings"><i class="vteicon" title="{$MOD.LBL_UPGRADE} {$modulelabel}">cached</i></a>
					
					&nbsp;&nbsp;
					
					{if $modulename eq 'Calendar' || $modulename eq 'Home'}
						<i class="vteicon md-sm">file_download</i>
					{else}
						<a href="index.php?module=Settings&action=ModuleManagerExport&module_export={$modulename}"><i class="vteicon" title="{$APP.LBL_EXPORT} {$modulelabel}">file_upload</i></a> {* crmv@37463 *}
					{/if}
					
					&nbsp;&nbsp;
					
					{if $modinfo.presence eq 0 && $modinfo.hassettings}
						<a href="index.php?module=Settings&action=ModuleManager&module_settings=true&formodule={$modulename}&parenttab=Settings"><i class="vteicon" title="{$modulelabel} {$MOD.LBL_SETTINGS}">settings_applications</i></a>
					{elseif $modinfo.hassettings eq false}
						&nbsp;
					{/if}
				</td>
				<td class="cell-vcenter"><i class="vteicon">extension</i></td>
				<td width="70%" class="cell-vcenter">{$modulelabel}</td>
			</tr>
		{/if}
	{/foreach}
	{foreach key=langprefix item=langinfo from=$TOGGLE_LANGINFO}
		{if $langprefix neq 'en_us'}
			{assign var="totalCustomModules" value=$totalCustomModules+1}
			<tr>
				<td width="15%" class="cell-vcenter">
					{if $langinfo.active eq 1}
						<a href="javascript:void(0);" onclick="vtlib_toggleModule('{$langprefix}', 'module_disable', 'language');"><i class="vteicon checkok" title="{$MOD.LBL_DISABLE} Language {$langinfo.label}">check</i></a>
					{else}
						<a href="javascript:void(0);" onclick="vtlib_toggleModule('{$langprefix}', 'module_enable', 'language');"><i class="vteicon checkko" title="{$MOD.LBL_ENABLE} Language {$langinfo.label}">clear</i></a>
					{/if}
					&nbsp;&nbsp;
					<a href="index.php?module=Settings&action=ModuleManager&module_update=Step1&src_module={$langprefix}&parenttab=Settings"><i class="vteicon" title="{$MOD.LBL_UPGRADE} {$langinfo.label}">cached</i></a>
				</td>
				<td class="cell-vcenter"><i class="vteicon">translate</i></td>
				<td width="90%" class="cell-vcenter">{$langinfo.label}</td>
			</tr>
		{/if}
	{/foreach}
	{if $totalCustomModules eq 0}
		<tr>
			<td class="cellLabel small" colspan=4><b>{$MOD.VTLIB_LBL_MODULE_MANAGER_NOMODULES}</b></td>
		</tr>
	{/if}
</table>

<!-- Standard modules -->
<table width=100% class="table table-hover" id="modmgr_standard">
	<thead>
		<tr>
			<th>&nbsp;</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	{foreach key=modulename item=modinfo from=$TOGGLE_MODINFO}
		{if $modinfo.customized eq false}
			{assign var="modulelabel" value=$modulename|@getTranslatedString:$modulename}
			<tr>
				<td class="cell-vcenter">
					{if $modinfo.presence eq 0}
						<a href="javascript:void(0);" onclick="vtlib_toggleModule('{$modulename}', 'module_disable', '', '{$modulelabel}');"><i class="vteicon checkok" title="{$MOD.LBL_DISABLE} {$modulelabel}">check</i></a>
					{else}
						<a href="javascript:void(0);" onclick="vtlib_toggleModule('{$modulename}', 'module_enable', '', '{$modulelabel}');"><i class="vteicon checkko" title="{$MOD.LBL_ENABLE} {$modulelabel}">clear</i></a>
					{/if}
					&nbsp;&nbsp;
					{if $modinfo.presence eq 0 && $modinfo.hassettings}
						<a href="index.php?module=Settings&action=ModuleManager&module_settings=true&formodule={$modulename}&parenttab=Settings"><i class="vteicon" title="{$modulelabel} {$MOD.LBL_SETTINGS}">settings_applications</i></a>
					{elseif $modinfo.hassettings eq false}
						<a href="javascript:void(0)"><i class="vteicon disabled" title="{$modulelabel} {$MOD.LBL_SETTINGS}">settings_applications</i></a>
					{/if}
				</td>
				<td width="90%" class="cell-vcenter">{$modulelabel}</td>
			</tr>
		{/if}
	{/foreach}
</table>