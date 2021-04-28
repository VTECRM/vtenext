{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<!-- crmv@18199 -->
<script src="modules/{$module->name}/resources/functional.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/{$module->name}/resources/webservices.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/{$module->name}/resources/parallelexecuter.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/{$module->name}/resources/fieldvalidator.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/{$module->name}/resources/createupdatefieldtaskscript.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript" charset="utf-8">
var moduleName = '{$entityName}';
var fieldName = '{$task->fieldName}';
var fieldValue = '{$task->fieldValue}';
createupdatefieldtaskscript(jQuery,fieldName,fieldValue);
</script>

<table border="0" cellpadding="0" cellspacing="5" width="100%" class="small">
	<tr>
		<td class='dvtCellLabel' align="right" width=15% nowrap="nowrap">{$APP.Field}</td>
		<td class='dvtCellInfo'>
			<span id="fieldName_busyicon"><b>{$MOD.LBL_LOADING}</b>{include file="LoadingIndicator.tpl"}</span>
			<select name="fieldName" id="fieldName" class="detailedViewTextBox" style="display: none;"></select>
		</td>
	</tr>
	<tr>
		<td class='dvtCellLabel' align="right" width=15% nowrap="nowrap">{$MOD.LBL_FIELD_VALUE}</td>
		<td class='dvtCellInfo'>
			<span id="fieldValue_busyicon"><b>{$MOD.LBL_LOADING}</b>{include file="LoadingIndicator.tpl"}</span>
			<input name="fieldValue" id="fieldValue" class="detailedViewTextBox" style="display: none;"/>
		</td>
	</tr>
</table>
<!-- crmv@18199e -->