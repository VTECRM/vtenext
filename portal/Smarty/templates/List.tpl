{*+*************************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@173153 *}

<div class="row">
	<div class="col-sm-12">
		<h1 class="page-header">
			{$LIST_TITLE}
		</h1>
	</div>
	<div class="col-sm-12">
		<div class="row">
			<div class="col-sm-6 text-left">
				<form class="form-inline">
					<div class="" style="margin:10px 1px;">
						{if $SHOW_LIST_OWNER_FILTER}
						<label for="show_combo">{'SHOW'|getTranslatedString}</label>
						<select class="form-control" name="list_type" id="show_combo" onchange="getList(this, '{$MODULE}');" style="min-width:100px;"> {* crmv@157078 *}
							<option value="mine" {$MINE_SELECTED}>{'MINE'|getTranslatedString}</option>
							<option value="all" {$ALL_SELECTED}>{'ALL'|getTranslatedString}</option>
						</select>
						&nbsp;
						{/if}
						{if $SHOW_LIST_STATUS_FILTER}
						<label for="status_combo">{'TICKET_STATUS'|getTranslatedString}</label>
						<select name="list_type" class="form-control" id="status_combo" onchange="getList(this, '{$MODULE}');" style="min-width:100px;">
							{foreach key=VALUE item=LABEL from=$STATUS_FILTER_VALUES}
								<option value="{$VALUE}" {if $STATUS_FILTER eq $VALUE}selected=""{/if}>{$LABEL}</option>
							{/foreach}
						</select>
						{/if}
					</div>
				</form>
			</div>
			<div class="col-sm-6 text-right">
				{if $SHOW_LIST_SEARCH}
				<button class="btn btn-info" name="srch" type="button" onclick="showSearchFormNow('tabSrch');">
					<i class="material-icons">search</i>
					{'LBL_SEARCH'|getTranslatedString}
				</button>
				&nbsp;
				{/if}
				{if $CAN_CREATE_RECORD}
				<button class="btn btn-success" type="submit" onClick="window.location.href='?module={$MODULE}&amp;action=Create'">{'NEW_TICKET'|getTranslatedString}</button>
				{/if}
			</div>
		</div>
		{if $SHOW_LIST_SEARCH}
		<div class="row">
			<div class="col-sm-12">
				{include file='SearchForm.tpl'}
			</div>
		</div>
		{/if}
	</div>
	
	<div class="col-sm-12">
		{if $FIELDLISTVIEW eq 'MODULE_INACTIVE' || $FIELDLISTVIEW eq 'LBL_NOT_AVAILABLE'}
			{include file='ListViewEmpty.tpl' ERR_MESSAGE=$FIELDLISTVIEW}
		{elseif $MODULE eq 'Products' || $MODULE eq 'Services'}
			{foreach from=$FIELDLISTVIEW key=LABEL item=LIST}
				<div class="row">
					<div class="col-lg-12">
						<h4>{$LABEL}</h4>
					</div>
				</div>

				{if $LIST eq 'MODULE_INACTIVE' || $LIST eq 'LBL_NOT_AVAILABLE'}
					{include file='ListViewEmpty.tpl' ERR_MESSAGE=$FIELDLISTVIEW}
				{elseif empty($LIST.ENTRIES)}
					{include file='ListViewEmpty.tpl'}
				{else}
					{include file='ListViewFields.tpl' HEADER=$LIST.HEADER ENTRIES=$LIST.ENTRIES LINKS=$LIST.LINKS}
				{/if}
			{/foreach}
		{elseif $MODULE eq 'Potentials'}
			{include file='ListViewFieldsPotentials.tpl' HEADER=$FIELDLISTVIEW.HEADER ENTRIES=$FIELDLISTVIEW.ENTRIES LINKS=$FIELDLISTVIEW.LINKS}
		{elseif empty($FIELDLISTVIEW.ENTRIES)}
			{include file='ListViewEmpty.tpl'}
		{else}
			{include file='ListViewFields.tpl' HEADER=$FIELDLISTVIEW.HEADER ENTRIES=$FIELDLISTVIEW.ENTRIES LINKS=$FIELDLISTVIEW.LINKS}
		{/if}
	</div>
</div>