{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@128159 *}
{if $sdk_mode eq 'detail'}
	{include file="DetailViewUI.tpl" keyid=1 keyreadonly=99 AJAXEDITTABLEPERM=false}
{elseif $sdk_mode eq 'edit'}
	{if $readonly eq 99}
		{include file='DisplayFieldsReadonly.tpl' uitype=1}
	{elseif $readonly eq 100}
		{include file="DisplayFieldsHidden.tpl" uitype=1}
	{else}
		{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
		<div class="{$DIVCLASS}">
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr>
			{if $smarty.request.enable_editoptions eq 'yes'}
				<td width="50%">
					<div class="dvtCellInfo">
						<select class="detailedViewTextBox" name="{$fldname}_options" onChange="ActionTaskScript.calendarDateOptions(this.value,'{$fldname}')">
							<option value="">{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
							<option value="custom">{'Custom'|getTranslatedString:'CustomView'}</option>
							<option value="now">{'LBL_NOW'|getTranslatedString}</option>
						</select>
					</div>
				</td>
			{/if}
			<td>
				<input name="{$fldname}" class="form-control" tabindex="{$vt_tab}" id="jscal_field_{$fldname}" type="text"  size="11" maxlength="10" value="{$fldvalue}" {if $fromlink eq 'qcreate' && $fldname eq 'date_start'}onchange="parent.calDuedatetimeQC(this.form,'date');"{/if}  {if $smarty.request.enable_editoptions eq 'yes'}style="display:none"{/if}>
			</td>
			<td style="padding-right:2px;">
				<i class="vteicon md-link" id="jscal_trigger_{$fldname}" {if $smarty.request.enable_editoptions eq 'yes'}style="display:none"{/if}>access_time</i>
			</td>
			</tr>
			{if $smarty.request.enable_editoptions eq 'yes'}
			<tr>
				<td colspan="4" nowrap>
					<div class="editoptions" fieldname="{$fldname}_opt_num" style="float:right; display:none"></div>
				</td>
			</tr>
			<tr>
				<td colspan="4" nowrap>
					<div id="{$fldname}_adv_options" style="display:none">
						<div style="float:left; width:10%; padding-right:10px">
							<select class="detailedViewTextBox" name="{$fldname}_opt_operator">
								<option value="add">+</option>
								<option value="sub">-</option>
							</select>
						</div>
						<div style="float:left; width:30%; padding-right:5px">
							<input type="text" class="detailedViewTextBox" name="{$fldname}_opt_num">
						</div>
						<div style="float:left; padding-right:10px">
							<i class="vteicon md-link" title="{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}" id="{$fldname}_opt_num_editoptions_more" onclick="ActionTaskScript.toggleFieldEditOptions('{$fldname}_opt_num')">more_horiz</i>
							{* <i class="vteicon md-link" title="{'LBL_PM_FIELD_GO_BACK'|getTranslatedString:'Settings'}" id="{$fldname}_opt_num_editoptions_cancel" onclick="ActionTaskScript.toggleFieldEditOptions('{$fldname}_opt_num')" style="display:none">highlight_off</i> *}
						</div>
						<div style="float:left; width:30%; padding-right:10px">
							<select class="detailedViewTextBox" name="{$fldname}_opt_unit">
								<option value="hour">{'lbl_hours'|getTranslatedString:'ModComments'}</option>
								<option value="minute">{'lbl_minutes'|getTranslatedString:'ModComments'}</option>
							</select>
						</div>
					</div>
				</td>
			</tr>
			{/if}
			</table>
			<script type="text/javascript" id='massedit_calendar_{$fldname}'>
				(function() {ldelim}
					setupDatePicker('jscal_field_{$fldname}', {ldelim}
						trigger: 'jscal_trigger_{$fldname}',
						date: false,
						time: true,
						date_format: 'HH:mm',
						language: "{$APP.LBL_JSCALENDAR_LANG}",
					{rdelim});
				{rdelim})();
			</script>
		</div>
	{/if}
{/if}