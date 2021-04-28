{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{include file='com_workflow/Header.tpl'}
<script src="modules/{$module->name}/resources/functional.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/{$module->name}/resources/workflowlistscript.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript" charset="utf-8">
	fn.addStylesheet('modules/{$module->name}/resources/style.css');
</script>

{* New workflow popup *}
{assign var="FLOAT_TITLE" value=$MOD.LBL_CREATE_WORKFLOW}
{assign var="FLOAT_WIDTH" value="400px"}
{capture assign="FLOAT_CONTENT"}
<form action="index.php" method="post" accept-charset="utf-8" onsubmit="VteJS_DialogBox.block();">
	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	<div class="popup_content">
		<table width="100%" cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td><input type="radio" name="source" value="from_module" checked="true" class="workflow_creation_mode">
					{$MOD.LBL_FOR_MODULE}</td>
				<td><input type="radio" name="source" value="from_template" class="workflow_creation_mode">
					{$MOD.LBL_FROM_TEMPLATE}</td>
			</tr>
		</table>
		<table width="100%" cellpadding="5" cellspacing="0" border="0">
			<tr>
				<td nowrap="nowrap">{$MOD.LBL_CREATE_WORKFLOW_FOR}</td>
				<td>
					<select name="module_name" id="module_list" class="small">
						{foreach key=moduleName item=label from=$moduleNames}
						<option value="{$moduleName}" {if $moduleName eq $listModule}selected="selected"{/if}>
							{$label}
						</option>
						{/foreach}
					</select>
				</td>
			</tr>
			<tr id="template_select_field" style="display:none;">
				<td>{$MOD.LBL_CHOOSE_A_TEMPLATE}</td>
				<td>
					<span id="template_list_busyicon"><b>{$MOD.LBL_LOADING}</b>{include file="LoadingIndicator.tpl"}</span>
					<span id="template_list_foundnone" style='display:none;'><b>{$MOD.LBL_NO_TEMPLATES}</b></span>
					<select id="template_list" name="template_id" class="small"></select>						
				</td>
			</tr>
		</table>
		<input type="hidden" name="save_type" value="new" id="save_type_new">
		<input type="hidden" name="module" value="{$module->name}" id="save_module">
		<input type="hidden" name="action" value="editworkflow" id="save_action">
		<table width="100%" cellspacing="0" cellpadding="5" border="0" class="layerPopupTransport">
			<tr><td align="center">
				<input type="submit" class="crmButton small save" value="{$APP.LBL_CREATE_BUTTON_LABEL}" name="save" id='new_workflow_popup_save'/> 
				<input type="button" class="crmButton small cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL} " name="cancel" id='new_workflow_popup_cancel'/>
			</td></tr>
		</table>
	</div>
</form>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="new_workflow_popup" FLOAT_BUTTONS=""}
{* Done Popups *}

{include file='SetMenu.tpl'}
{include file='Buttons_List.tpl'} {* crmv@30683 *}
<div id="view">
	{include file='com_workflow/ModuleTitle.tpl'}
	<table class="tableHeading" width="100%" border="0" cellspacing="0" cellpadding="5">
		<tr>
			<td class="big" nowrap="nowrap">
				<strong><span id="module_info"></span></strong>
			</td>
			<td class="small" align="right">
				<form action="index.php" method="get" accept-charset="utf-8" id="filter_modules" onsubmit="VteJS_DialogBox.block();" style="display: inline;">
					<b>{$MOD.LBL_SELECT_MODULE}: </b>
					<select class="importBox" name="list_module" id='pick_module'>
						<option value="All">{$APP.LBL_ALL}</a>
						<option value="All" disabled="disabled" >-----------------------------</a>
						{foreach key=moduleName item=label from=$moduleNames}
						<option value="{$moduleName}" {if $moduleName eq $listModule}selected="selected"{/if}>
							{$label}
						</option>
						{/foreach}
					</select>
					<input type="hidden" name="module" value="{$module->name}">
					<input type="hidden" name="action" value="workflowlist">
					<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
				</form>

			</td>
		</tr>
	</table>

	<table class="listTableTopButtons" width="100%" border="0" cellspacing="0" cellpadding="5">
		<tr>
			<td class="small"> <span id="status_message"></span> </td>
			<td class="small" align="right">
				<input type="button" class="crmButton create small" 
					value="{$MOD.LBL_NEW_WORKFLOW}" id='new_workflow'/>
			</td>
		</tr>
	</table>
	<table class="listTable" width="100%" border="0" cellspacing="0" cellpadding="5" id='expressionlist'>
		<tr>
			<td class="colHeader small" width="20%">
				{'LBL_MODULE'|getTranslatedString}
			</td>
			<td class="colHeader small" width="65">
				{'Description'|getTranslatedString}
			</td>
			<td class="colHeader small" width="15%">
				{'Tools'|getTranslatedString}
			</td>
		</tr>
{foreach item=workflow from=$workflows}
		<tr>
			<td class="listTableRow small">{$workflow->moduleName|@getTranslatedString:$workflow->moduleName}</td>
			<td class="listTableRow small">{$workflow->description|@to_html}</td>
			<td class="listTableRow small">
				<a href="{$module->editWorkflowUrl($workflow->id)}">
					<i class="vteicon md-sm" title="Edit" id="expressionlist_editlink_{$workflow->id}">create</i>
				</a>
				<a href="{$module->deleteWorkflowUrl($workflow->id)}" onclick="return confirm('{$APP.SURE_TO_DELETE}');">
					<i class="vteicon md-sm" title="Delete" id="expressionlist_deletelink_{$workflow->id}">delete</i>
				</a>
			</td>
		</tr>
{/foreach}
	</table>
</div>
{include file='com_workflow/Footer.tpl'}