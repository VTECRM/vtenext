{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
<form name="Layout" action="index.php">
	<input type="hidden" name="module" value="Messages">
	<input type="hidden" name="action" value="MessagesAjax">
	<input type="hidden" name="file" value="Settings/index">
	<input type="hidden" name="operation" value="SaveLayout">
	
	<table border="0" cellpadding="0" cellspacing="5" width="100%" align="center" style="padding-top:20px">
		<tr>
			<td align="right" width="40%"><input type="checkbox" id="list_descr_preview" name="list_descr_preview" {if $SETTINGS.list_descr_preview eq '1'}checked{/if}></td>
			<td align="left" width="60%"><label for="list_descr_preview">{'LBL_LIST_DESCR_PREVIEW'|getTranslatedString:'Messages'}</label></td>
		</tr>
		<tr>
			<td align="right" width="40%"><input type="checkbox" id="thread" name="thread" {if $SETTINGS.thread eq '1'}checked{/if}></td>
			<td align="left" width="60%"><label for="thread">{'LBL_THREAD_VIEW'|getTranslatedString:'Messages'}</label></td>
		</tr>
		{* crmv@192843 *}
		<tr>
			<td align="right" width="40%"><input type="checkbox" id="merge_account_folders" name="merge_account_folders" {if $SETTINGS.merge_account_folders eq '1'}checked{/if}></td>
			<td align="left" width="60%"><label for="merge_account_folders">{'LBL_MERGE_ACCOUNT_FOLDERS'|getTranslatedString:'Messages'}</label></td>
		</tr>
		{* crmv@192843e *}
	</table>
</form>