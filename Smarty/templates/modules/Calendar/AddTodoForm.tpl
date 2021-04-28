{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@20628 *}
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

	var fieldnameTaskCustom = {$VALIDATION_DATA_CUS_FIELDNAME};
	var fieldlabelTaskCustom = {$VALIDATION_DATA_CUS_FIELDLABEL};
	var fielddatatypeTaskCustom = {$VALIDATION_DATA_CUS_FIELDDATATYPE};
	var fielduitypeTaskCustom = {$VALIDATION_DATA_CUS_FIELDUITYPE};
	var fieldwstypeTaskCustom = {$VALIDATION_DATA_CUS_FIELDWSTYPE};
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
{assign var=EditViewForm value='createTodo'} {* crmv@106578 *}
{assign var=editViewType value='createTodo'} {* crmv@139842 *} 
<form name="createTodo" id="createTodo" method="POST" action="index.php">
    <input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	<input type="hidden" name="return_action" value="index">
	<input type="hidden" name="return_module" value="Calendar">
	<input type="hidden" name="module" value="Calendar">
	<input type="hidden" name="activity_mode" value="Task">
	<input type="hidden" name="action" value="TodoSave">
	<input type="hidden" name="view" value="{$view}">
	<input type="hidden" name="hour" value="{$hour}">
	<input type="hidden" name="day" value="{$day}">
	<input type="hidden" name="month" value="{$month}">
	<input type="hidden" name="year" value="{$year}">
	<input type="hidden" name="record" value="{$RECORD}">
	<input type="hidden" name="parenttab" value="{$CATEGORY}">
	<input type="hidden" name="mode" value="{$MODE}">
	<input type="hidden" name="time_start" id="time_start">
	<input type="hidden" name="viewOption" value="">
	<input type="hidden" name="subtab" value="">
	<input type="hidden" name="maintab" value="Calendar">
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
					{include file="modules/Calendar/TodoCollapseUI.tpl"}
				</div>
			</div>
		</div>
		<div class="col-xs-12" style="margin: 5px auto"></div>
	</div>
	{* crmv@138006e *}

{if empty($MODE) || $MODE eq 'edit'}
</form>
{/if}