{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@92272 crmv@100972 *}

<script type="text/javascript" language="Javascript">
function validateForm(form) {ldelim}
	if(!emptyCheck("name","{$MOD.LBL_PROCESS_MAKER_RECORD_NAME}","text")) {ldelim}
		form.name.focus();
		return false;
	{rdelim}
	return true;
{rdelim}
</script>

<br>
<form enctype="multipart/form-data" name="Import" method="POST" action="index.php" onsubmit="VteJS_DialogBox.block();">
	<input type="hidden" name="module" value="Settings">
	<input type="hidden" name="action" value="SettingsAjax">
	<input type="hidden" name="file" value="ProcessMaker">
	<input type="hidden" name="mode" value="import">
	<table width="50%" align="center">
		{if !empty($ERROR)}
			<tr><td class="errorString" colspan="2">{$ERROR}</td></tr>
			<tr><td colspan="2">&nbsp;</td></tr>
		{/if}
		<tr>
			<td class="dvtCellLabel" align="right" width="20%"><span>{$MOD.LBL_PROCESS_MAKER_RECORD_NAME}</span>&nbsp;&nbsp;</td>
			<td align="left" width="250">
				{include file="EditViewUI.tpl" NOLABEL=true DIVCLASS="dvtCellInfoM" uitype=1 keymandatory=true fldlabel=$MOD.LBL_PROCESS_MAKER_RECORD_NAME fldname="name" fldvalue=$DATA.name}
			</td>
		</tr>
		<tr>
			<td class="dvtCellLabel" valign="top" align="right" width="20%"><span>{$MOD.LBL_PROCESS_MAKER_RECORD_DESC}</span>&nbsp;&nbsp;</td>
			<td align="left" width="250">
				{include file="EditViewUI.tpl" NOLABEL=true DIVCLASS="dvtCellInfo" uitype=21 keymandatory=true fldlabel=$MOD.LBL_PROCESS_MAKER_RECORD_DESC fldname="description" fldvalue=$DATA.description}
			</td>
		</tr>
		<tr style="display:none">
			<td class="dvtCellLabel" valign="top" align="right" width="20%"><span>{$APP.LBL_IMPORT}</span>&nbsp;&nbsp;</td>
			<td align="left" width="250">
				<input type="file" name="bpmnfile" size="65" class=small onchange="VteJS_DialogBox.block(); this.form.submit();" />&nbsp;
				<input type="hidden" name="bpmnfile_hidden" value=""/>
				<i>.bpmn .vtebpmn</i>
			</td>
		</tr>
		<tr><td colspan="2">&nbsp;</td></tr>
		<tr><td align="right" colspan="2">
			<input title="{$APP.LBL_IMPORT}" class="crmButton small save" type="button" name="button" value="{$APP.LBL_IMPORT}..." onclick="if (validateForm(this.form)) jQuery('[name=bpmnfile]').click();">
			<input title="{'LBL_CREATE_NEW'|getTranslatedString:'Reports'}" class="crmButton small save" type="button" name="button" value="{'LBL_CREATE_NEW'|getTranslatedString:'Reports'}" onclick="if (validateForm(this.form)) {ldelim} VteJS_DialogBox.block(); this.form.submit(); {rdelim}">
			<input type="submit" onclick="this.form.action.value='ProcessMaker'; this.form.file.value=''; this.form.mode.value='';" class="crmbutton small cancel" value='{$MOD.LBL_CANCEL_BUTTON}' title='{$MOD.LBL_CANCEL_BUTTON}'>
		</td></tr>
	</table>
</form>