{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@155145 *}
<span class="inactive" style="vertical-align:6px">{if $PENDING_VERSION}{'LBL_VERSION_PENDING_CHANGES'|getTranslatedString:'Settings'} <a href="javascript:;" onclick="ConditionalsUtils.closeVersion()" class="simpleSave">{$APP.LBL_SAVE_LABEL} ({'VTLIB_LBL_PACKAGE_VERSION'|getTranslatedString:'Settings'|substr:0:1|strtolower}. {$PENDING_VERSION})</a>{else}{'VTLIB_LBL_PACKAGE_VERSION'|getTranslatedString:'Settings'|substr:0:1|strtolower}. {$CURRENT_VERSION}{/if}</span>&nbsp;
{if $PERM_VERSION_EXPORT}
	<i class="vteicon md-link" title="{'LBL_EXPORT_VERSION'|getTranslatedString:'Settings'}" onclick="ConditionalsUtils.exportVersion()">file_download</i>
{else}
	<i class="vteicon disabled" title="{'LBL_EXPORT_VERSION'|getTranslatedString:'Settings'}">file_download</i>
{/if}
{if $PERM_VERSION_IMPORT}
	<i class="vteicon md-link" title="{'LBL_IMPORT_VERSION'|getTranslatedString:'Settings'}" onclick="{if $CHECK_VERSION_IMPORT neq ''}alert('{$CHECK_VERSION_IMPORT|addslashes}');{else}jQuery('[name=versionfile]').click(){/if}">file_upload</i>
{else}
	<i class="vteicon disabled" title="{'LBL_IMPORT_VERSION'|getTranslatedString:'Settings'}">file_upload</i>
{/if}
<div style="display:none">
	<form enctype="multipart/form-data" name="Import" method="POST" action="index.php">
		<input type="hidden" name="module" value="Conditionals">
		<input type="hidden" name="action" value="ConditionalsUtilsAjax">
		<input type="hidden" name="sub_mode" value="importVersion">
		<input type="file" name="versionfile" size="65" class=small onchange="VteJS_DialogBox.block(); this.form.submit();" />&nbsp;
		<input type="hidden" name="versionfile_hidden" value=""/>
	</form>
</div>