{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@150592 *}
<span class="inactive" style="vertical-align:6px">{if $PENDING_VERSION}{$MOD.LBL_VERSION_PENDING_CHANGES} <a href="javascript:;" onclick="ProfileUtils.closeVersion()" class="simpleSave">{$APP.LBL_SAVE_LABEL} ({$MOD.VTLIB_LBL_PACKAGE_VERSION|substr:0:1|strtolower}. {$PENDING_VERSION})</a>{else}{$MOD.VTLIB_LBL_PACKAGE_VERSION|substr:0:1|strtolower}. {$CURRENT_VERSION}{/if}</span>&nbsp;
{if $PERM_VERSION_EXPORT}
	<i class="vteicon md-link" title="{$MOD.LBL_EXPORT_VERSION}" onclick="ProfileUtils.exportVersion()">file_download</i>
{else}
	<i class="vteicon disabled" title="{$MOD.LBL_EXPORT_VERSION}">file_download</i>
{/if}
{if $PERM_VERSION_IMPORT}
	<i class="vteicon md-link" title="{$MOD.LBL_IMPORT_VERSION}" onclick="{if $CHECK_VERSION_IMPORT neq ''}alert('{$CHECK_VERSION_IMPORT|addslashes}');{else}jQuery('[name=versionfile]').click(){/if}">file_upload</i>
{else}
	<i class="vteicon disabled" title="{$MOD.LBL_IMPORT_VERSION}">file_upload</i>
{/if}
<div style="display:none">
	<form enctype="multipart/form-data" name="Import" method="POST" action="index.php">
		<input type="hidden" name="module" value="Settings">
		<input type="hidden" name="action" value="ListProfilesAjax">
		<input type="hidden" name="sub_mode" value="importVersion">
		<input type="file" name="versionfile" size="65" class=small onchange="VteJS_DialogBox.block(); this.form.submit();" />&nbsp;
		<input type="hidden" name="versionfile_hidden" value=""/>
	</form>
</div>