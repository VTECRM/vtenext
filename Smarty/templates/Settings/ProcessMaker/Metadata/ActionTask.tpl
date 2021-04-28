{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@92272 crmv@102879 crmv@115268 *}
{include file="Settings/ProcessMaker/Metadata/Header.tpl"}

<script src="{"modules/Settings/ProcessMaker/resources/ActionTaskScript.js"|resourcever}" type="text/javascript"></script>
<script type="text/javascript">
jQuery(document).ready(function(){ldelim}
	ActionTaskScript.init('{$ID}');
	{* crmv@195745 *}
	setTimeout(function(){ldelim}
		jQuery('#action_type').val('');
	{rdelim}, 200);
	{* crmv@195745e *}
{rdelim});
</script>

<div style="padding:5px;">
	<form class="form-config-shape" shape-id="{$ID}">
		<table class="tableHeading" width="100%" border="0" cellspacing="0" cellpadding="5">
			<tr>
				<td class="small" align="right">
					<input type="button" class="crmButton create small" value="{$MOD.LBL_PM_CREATE_ACTION}" id='new_task'/>
				</td>
			</tr>
			<tr>
				<td id="new_task_div" style="display:none;">
					<table width="100%" cellspacing="0" cellpadding="0" border="0">
						<tr><td align="center">
							{$MOD.LBL_PM_CREATE_ACTION_OF_TYPE} 
							<select id="action_type" class="small" onchange="ActionTaskScript.changeActionType(this)">
								<option value="">{'LBL_PLEASE_SELECT'|getTranslatedString}</option> {* crmv@195745 *}
								{foreach key=value item=actionType from=$actionTypes}
									{if $actionType.hide_main_menu neq true}
										<option value='{$value}'>{$actionType.label}</option>
									{/if}
								{/foreach}
							</select>
							{if $rowFields} {* crmv@195745 *}
							<span id="new_task_cycle" style="display:none">
								<span> {$APP.LBL_ON_FIELD} </span>
								<select id="table_fields" class="small" onchange="ActionTaskScript.changeCycleField(this)"> {* crmv@195745 *}
									{foreach key=groupid item=ginfo from=$rowFields} {* crmv@195745 *}
										<optgroup label="{$ginfo.label}">
										{foreach key=fieldid item=fieldlabel from=$ginfo.fields}
											<option value="{$fieldid}">{$fieldlabel}</option>
										{/foreach}
										</optgroup>
									{/foreach}
								</select>
								<span> {$APP.LBL_AND} {$APP.LBL_FOREACH_ROW} </span>
								<select id="cycle_action_type" class="small" onchange="ActionTaskScript.changeCycleActionType(this)">
									{foreach key=value item=actionType from=$cycleActionTypes}
										<option value='{$value}'>{$actionType.label}</option>
									{/foreach}
								</select>
								<span id="cycle_inserttablerow" style="display:none">
									<span> {$APP.LBL_ON_FIELD} </span>
									<select id="cycle_inserttablerow_table_fields" class="small">
										{foreach key=groupid item=ginfo from=$tableFields}
											<optgroup label="{$ginfo.label}">
											{foreach key=fieldid item=fieldlabel from=$ginfo.fields}
												<option value="{$fieldid}">{$fieldlabel}</option>
											{/foreach}
											</optgroup>
										{/foreach}
									</select>
								</span>
								{* crmv@195745 *}
								<span id="cycle_insertpblockrow" style="display:none">
									<span> {$APP.LBL_ON_FIELD} </span>
									<select id="cycle_insertpblockrow_table_fields" class="small">
										{foreach key=groupid item=ginfo from=$pblockFields}
											<optgroup label="{$ginfo.label}">
											{foreach key=fieldid item=fieldlabel from=$ginfo.fields}
												<option value="{$fieldid}">{$fieldlabel}</option>
											{/foreach}
											</optgroup>
										{/foreach}
									</select>
								</span>
								{* crmv@195745e *}
							</span>
							<span id="new_task_inserttablerow" style="display:none">
								<span> {$APP.LBL_ON_FIELD} </span>
								<select id="inserttablerow_table_fields" class="small">
									{foreach key=groupid item=ginfo from=$tableFields}
										<optgroup label="{$ginfo.label}">
										{foreach key=fieldid item=fieldlabel from=$ginfo.fields}
											<option value="{$fieldid}">{$fieldlabel}</option>
										{/foreach}
										</optgroup>
									{/foreach}
								</select>
							</span>
							{/if}
								{* crmv@203075 *}
								{if $rowFieldsRelated} {* crmv@195745 *}
									<span id="new_task_cycle_related" style="display:none">
								<span> {$APP.LBL_ON_MODULE} </span>
								<select id="table_fields_related" class="small" onchange="ActionTaskScript.changeCycleField(this)"> {* crmv@195745 *}
									{foreach key=groupid item=ginfo from=$rowFieldsRelated} {* crmv@195745 *}
										<optgroup label="{$ginfo.label}">
										{foreach key=fieldid item=fieldlabel from=$ginfo.fields}
											<option value="{$fieldid}">{$fieldlabel}</option>
										{/foreach}
										</optgroup>
									{/foreach}
								</select>
								<span> {$APP.LBL_AND} {$APP.LBL_FOREACH_ROW} </span>
								<select id="cycle_action_type_related" class="small" onchange="ActionTaskScript.changeCycleActionType(this)">
									{foreach key=value item=actionType from=$cycleActionTypesRelated}
										<option value='{$value}'>{$actionType.label}</option>
									{/foreach}
								</select>
								<span id="cycle_inserttablerow_related" style="display:none">
									<span> {$APP.LBL_ON_MODULE} </span>
									<select id="cycle_inserttablerow_table_fields_related" class="small">
										{foreach key=groupid item=ginfo from=$tableFieldsRelated}
											<optgroup label="{$ginfo.label}">
											{foreach key=fieldid item=fieldlabel from=$ginfo.fields}
												<option value="{$fieldid}">{$fieldlabel}</option>
											{/foreach}
											</optgroup>
										{/foreach}
									</select>
								</span>
							</span>
									<span id="new_task_inserttablerow_related" style="display:none">
								<span> {$APP.LBL_ON_MODULE} </span>
								<select id="inserttablerow_table_fields_related" class="small">
									{foreach key=groupid item=ginfo from=$tableFieldsRelated}
										<optgroup label="{$ginfo.label}">
										{foreach key=fieldid item=fieldlabel from=$ginfo.fields}
											<option value="{$fieldid}">{$fieldlabel}</option>
										{/foreach}
										</optgroup>
									{/foreach}
								</select>
							</span>
								{/if}
								{* crmv@203075e *}

							{* crmv@195745 *}
							{if $pblockFields}
							<span id="new_task_insertproductrow" style="display:none">
								<span> {$APP.LBL_ON_FIELD} </span>
								<select id="insertproductrow_inventory_fields" class="small">
									{foreach key=groupid item=ginfo from=$pblockFields}
										<optgroup label="{$ginfo.label}">
										{foreach key=fieldid item=fieldlabel from=$ginfo.fields}
											<option value="{$fieldid}">{$fieldlabel}</option>
										{/foreach}
										</optgroup>
									{/foreach}
								</select>
							</span>
							{/if}
							{* crmv@195745e *}
						</td></tr>
						<tr><td align="center">
							<input type="button" class="crmButton small save" value="{$APP.LBL_CREATE_BUTTON_LABEL}" name="save" onClick="ActionTaskScript.editaction('{$PROCESSID}','{$ID}',this.form.action_type.value,'');"/> 
							<input type="button" class="crmButton small cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" name="cancel" id="new_task_div_close"/> 
						</td></tr>
					</table>
				</td>
			</tr>
		</table>
		<table class="listTableTopButtons" width="100%" border="0" cellspacing="0" cellpadding="5">
			<tr>
				<td class="small"> <span id="status_message"></span> </td>			
			</tr>
		</table>
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<td class="dvInnerHeader">
					<b>{$MOD.LBL_PM_ACTIONS}</b>
				</td>
			</tr>
		</table>
		{if empty($METADATA.actions)}
			<div class="popupLinkListNoData">{$MOD.LBL_PM_NO_ACTIONS}</div>
		{else}
			<table class="listTable" width="100%" border="0" cellspacing="1" cellpadding="5" id='expressionlist'>
				<tr>
					<td class="colHeader small" width="10%">
						{$MOD.LBL_LIST_TOOLS}
					</td>
					<td class="colHeader small" width="90%">
						{$MOD.LBL_PM_ACTION}
					</td>
					{*
					<td class="colHeader small" width="15%">
						{$MOD.LBL_STATUS}
					</td>
					*}
				</tr>
				{foreach key=ACTION_ID item=ACTION from=$METADATA.actions}
				<tr>
					<td class="listTableRow small">
						{if $ACTION.action_type neq 'SDK'}
							<a href="javascript:ActionTaskScript.editaction('{$PROCESSID}','{$ID}','{$ACTION.action_type}','{$ACTION_ID}', '{$ACTION.cycle_field}', '{$ACTION.cycle_action}', '{$ACTION.inserttablerow_field}', '{$ACTION.insertpblockrow_field}');"> {* crmv@102879 crmv@195745 *}
								<i class="vteicon" title="{'LBL_EDIT'|getTranslatedString}">create</i>
							</a>
						{/if}
						<a href="javascript:ActionTaskScript.deleteaction('{$PROCESSID}','{$ID}','{$ACTION_ID}');">
							<i class="vteicon" title="{'LBL_DELETE'|getTranslatedString}">clear</i>
						</a>
					</td>
					<td class="listTableRow small">{$ACTION.action_title}</td>
					{* <td class="listTableRow small">{if $task->active}Active{else}Inactive{/if}</td> *}
				</tr>
				{/foreach}
			</table>
		{/if}
	</form>
</div>
<br>
{include file="Settings/ProcessMaker/Metadata/Helper.tpl"}