{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@97566 *}
{include file="Settings/ProcessMaker/Metadata/Header.tpl"}
<div style="padding:5px;">
	<form class="form-config-shape" shape-id="{$ID}">
		<table border=0 align="center" width="100%">
			<tr><td>
				<span class="helpmessagebox" style="font-style: italic;">{$MOD.LBL_PM_START_EVENT_NOTE}</span>
			</td></tr>
			<tr><td style="padding-top:15px">
				<table border=0>
					<tr>
						<td>
							{include file="EditViewUI.tpl" DIVCLASS="dvtCellInfo" uitype=5 fldlabel="Start Date & Time"|getTranslatedString fldname="date_start" fldvalue=$START_DATE_FLDVALUE secondvalue=$START_DATE_SECONDVALUE}
						</td>
						<td>
							{$START_TIME_COMBO}
						</td>
					</tr>
				</table>
			</td></tr>
			<tr><td style="padding-top:15px">
				<input type="checkbox" name="recurrence" id="recurrence_check" class="small" {if $METADATA.recurrence eq 'on'}checked{/if}>
				<label for="recurrence_check">{$MOD.LBL_PM_ENABLE_RECURRENCE}</label>
				<div id="cron_display" style="padding-top:5px{if $METADATA.recurrence neq 'on'}display:none"{/if}">
					{include file="DisplayFieldsHidden.tpl" DIVCLASS="dvtCellInfo" uitype=1 fldlabel=$MOD.LBL_PM_CRON_VALUE_SELECTED fldname="cron_value" fldvalue=$METADATA.cron_value}
				</div>
			</td></tr>
			<tr id="end_condition" {if $METADATA.recurrence neq 'on'}style="display:none"{/if}><td style="padding-top:15px">
				<table border=0>
					<tr>
						<td>
							{include file="EditViewUI.tpl" DIVCLASS="dvtCellInfo" uitype=5 fldlabel="End Date & Time"|getTranslatedString fldname="date_end" fldvalue=$END_DATE_FLDVALUE secondvalue=$END_DATE_SECONDVALUE MASS_EDIT=true}
						</td>
						<td>
							{$END_TIME_COMBO}
						</td>
					</tr>
				</table>
			</td></tr>
		</table>
	</form>
	<div id="preview" style="padding-top:15px">
		{include file="Settings/ProcessMaker/Metadata/TimerStartPreviewRecurrences.tpl"}
	</div>
</div>

<script language="JavaScript" type="text/javascript" src="modules/Settings/ProcessMaker/thirdparty/jqcron/src/jqCron.js"></script>
<script language="JavaScript" type="text/javascript" src="modules/Settings/ProcessMaker/thirdparty/jqcron/src/jqCron.{$JQCRONLANG}.js"></script>
<link rel="stylesheet" href="modules/Settings/ProcessMaker/thirdparty/jqcron/src/jqCron.css">
<script>
{if $METADATA.date_end_mass_edit_check eq 'on'}
	jQuery("#date_end_mass_edit_check").prop('checked',true);
{/if}
{literal}
jQuery(function(){
    jQuery('#cron_value').jqCron({
        enabled_minute: false,
        multiple_dom: true,
        multiple_month: true,
        multiple_mins: true,
        multiple_dow: true,
        multiple_time_hours: true,
        multiple_time_minutes: true,
        no_reset_button: true,
        lang: '{/literal}{$JQCRONLANG}{literal}',
        bind_method: {
			set: function($element, value) {
				$element.is(':input') ? $element.val(value) : $element.data('jqCronValue', value);
				ProcessMakerScript.previewRecurrence('{/literal}{$ID}{literal}');
			},
		}
    });
});
jQuery("#recurrence_check").change(function(){
	if (this.checked) {
		jQuery('#cron_display').show();
		jQuery('#end_condition').show();
	} else { 
		jQuery('#cron_display').hide();
		jQuery('#end_condition').hide();
	}
});
ProcessMakerScript.previewRecurrence('{/literal}{$ID}{literal}');
jQuery("#jscal_field_date_start").change(function(){ ProcessMakerScript.previewRecurrence('{/literal}{$ID}{literal}'); });
jQuery("#starthr").change(function(){ ProcessMakerScript.previewRecurrence('{/literal}{$ID}{literal}'); });
jQuery("#startmin").change(function(){ ProcessMakerScript.previewRecurrence('{/literal}{$ID}{literal}'); });
jQuery("#recurrence_check").change(function(){ ProcessMakerScript.previewRecurrence('{/literal}{$ID}{literal}'); });
jQuery("#date_end_mass_edit_check").change(function(){ ProcessMakerScript.previewRecurrence('{/literal}{$ID}{literal}'); });
jQuery("#jscal_field_date_end").change(function(){ ProcessMakerScript.previewRecurrence('{/literal}{$ID}{literal}'); });
jQuery("#endhr").change(function(){ ProcessMakerScript.previewRecurrence('{/literal}{$ID}{literal}'); });
jQuery("#endmin").change(function(){ ProcessMakerScript.previewRecurrence('{/literal}{$ID}{literal}'); });
{/literal}
</script>