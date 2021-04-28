{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@17001 *} 
{* crmv@20602 *} 
{* crmv@26807 *} 
{* crmv@31315 *} 
{* crmv@95751 *} 
{* crmv@98866 *} 
{* crmv@103922 *}
{* crmv@112297 *}

{if empty($MODE) || $MODE eq 'edit'}
<script type="text/javascript">
	var fieldname = {$VALIDATION_DATA_FIELDNAME};
	var fieldlabel = {$VALIDATION_DATA_FIELDLABEL};
	var fielddatatype = {$VALIDATION_DATA_FIELDDATATYPE};
	var fielduitype = {$VALIDATION_DATA_FIELDUITYPE};
	var fieldwstype = {$VALIDATION_DATA_FIELDWSTYPE};

	var fieldnameCustom = {$VALIDATION_DATA_CUS_FIELDNAME};
	var fieldlabelCustom = {$VALIDATION_DATA_CUS_FIELDLABEL};
	var fielddatatypeCustom = {$VALIDATION_DATA_CUS_FIELDDATATYPE};
	var fielduitypeCustom = {$VALIDATION_DATA_CUS_FIELDUITYPE};
	var fieldwstypeCustom = {$VALIDATION_DATA_CUS_FIELDWSTYPE};
	
	setAllDayEvent({$ACTIVITYDATA.is_all_day_event});
</script>
{else}
<script type="text/javascript">
	var fieldname = new Array({$VALIDATION_DATA_FIELDNAME});
	var fieldlabel = new Array({$VALIDATION_DATA_FIELDLABEL});
	var fielddatatype = new Array({$VALIDATION_DATA_FIELDDATATYPE});
	var fielduitype = new Array({$VALIDATION_DATA_FIELDUITYPE});
	var fieldwstype = new Array({$VALIDATION_DATA_FIELDWSTYPE});
</script>
{/if}

{if empty($MODE) || $MODE eq 'edit'}
<form name="EditView" method="POST" action="index.php">
	<input type="hidden" name="return_action" value="index">
	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	<input type="hidden" name="return_module" value="Calendar">
	<input type="hidden" name="module" value="Calendar">
	<input type="hidden" name="activity_mode" value="Events">
	<input type="hidden" name="action" value="Save">
	<input type="hidden" name="view" value="{$view}">
	<input type="hidden" name="hour" value="{$hour}">
	<input type="hidden" name="day" value="{$day}">
	<input type="hidden" name="month" value="{$month}">
	<input type="hidden" name="year" value="{$year}">
	<input type="hidden" name="record" value="{$RECORD}">
	<input type="hidden" name="mode" value="{$MODE}">

	<div id="calAddEventPopup">
		<input type="hidden" name="time_start" id="time_start">
		<input type="hidden" name="time_end" id="time_end">
	</div>

	<input type="hidden" name="followup_due_date" id="followup_due_date">
	<input type="hidden" name="followup_time_start" id="followup_time_start">
	<input type="hidden" name="followup_time_end" id="followup_time_end">
	<input type="hidden" name="duration_hours" value="0">
	<input type="hidden" name="duration_minutes" value="0">

	<input type="hidden" name="inviteesid" id="inviteesid" value="">
	<input type="hidden" name="inviteesid_con" id="inviteesid_con" value="">

	<input type="hidden" name="parenttab" value="{$CATEGORY}">
	<input type="hidden" name="viewOption" value="{$viewOption}">
	<input type="hidden" name="view_filter" value="{$view_filter}">
	<input type="hidden" name="subtab" value="{$subtab}">
	<input type="hidden" name="maintab" value="{$maintab}">
	<input type="hidden" name="dateformat" value="{$DATEFORMAT}">
	<input type="hidden" name="ajaxCalendar" value="detailedAdd">
{else}
<form name="DetailView" method="POST" action="index.php">
	<input type="hidden" name="module" value="{$MODULE}">
{/if}

	{* crmv@138006 *}
	<div class="row">
		<div class="col-xs-12" style="margin: 5px auto"></div>
		<div class="col-xs-12">
			<div class="row">
				<div class="col-xs-12 col-md-6 content-left">
					{include file="modules/Calendar/DisplayFields.tpl"}
				</div>
				<div class="col-xs-12 col-md-6">
					{include file="modules/Calendar/EventCollapseUI.tpl"}
				</div>
			</div>
		</div>
		<div class="col-xs-12" style="margin: 5px auto"></div>
	</div>
	{* crmv@138006e *}
</form>

{if empty($MODE) || $MODE eq 'edit'}


{literal}
<script type="text/javascript">
	//crmv@26807 crmv@101475
	/*jQuery('#bbit-cal-txtSearch').keyup(jQuery.debounce(300, function() {
		searchFunction('bbit-cal-txtSearch');
	}));*/

	//crmv26807e crmv@101475e
	
	//crmv@29190
	var searchString = "{/literal}{'LBL_SEARCH_STRING'|getTranslatedString}{literal}";
	jQuery('#bbit-cal-txtSearch').focus(function() {
		focusSearchInput(this, searchString);
	});
	jQuery('#bbit-cal-txtSearch').blur(function() {
		blurSearchInput(this, searchString);
	});
	//crmv@29190e
</script>
{/literal}
{/if}