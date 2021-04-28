{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@105588 *}

<style type="text/css">
{literal}
	.messageRightButton {
		margin-top:5px;
		cursor:pointer;
		background-color:#f0f0f0;
	}
{/literal}
</style>

<div id="messageCalendarCont" style="background-color:white;position:fixed;left:0px;top:20px;width:550px;z-index:100;display:none"></div>

{* check if in compose *}
{if $MODULE neq 'Emails'}
	{assign var=MODE value=""}
{else}
	{assign var=MODE value="compose"}
{/if}

{if $ID eq 'current_record'}
	{assign var=ID value="current_record"}
{else}
	{assign var=ID value="'"|cat:$ID|cat:"'"}
{/if}

{* crmv@62394 *}
{if $SHOW_TURBOLIFT_TRACKER}
	{include file="modules/SDK/src/CalendarTracking/TurboliftTracking.tpl"}
{/if}
{* crmv@62394e *}

{if $SHOW_TURBOLIFT_CAL_BUTTONS}
	<div id="turbolift_back_button" class="turboliftEntry1 turboliftEntryWithImage btn" onClick="releaseOverAll('detailViewActionsContainer'); LPOP.openEventCreate('{$MODULE}', {$ID}, 'Events');">
		<div class="row no-gutter">
			<div class="col-sm-12">
				<div class="col-sm-6 vcenter text-left">
					<span>{'Event'|getNewModuleLabel}</span>	{* crmv@59091 *}
				</div><!-- 
				 --><div class="col-sm-6 vcenter text-right">
					<i class="vteicon md-text" title="{'LBL_ADD'|@getTranslatedString:'Calendar'}" >event</i>
				</div>
			</div>
		</div>
	</div>

	<div id="turbolift_back_button" class="turboliftEntry1 turboliftEntryWithImage btn" onClick="releaseOverAll('detailViewActionsContainer'); LPOP.openEventCreate('{$MODULE}', {$ID}, 'Task');">
		<div class="row no-gutter">
			<div class="col-sm-12">
				<div class="col-sm-6 vcenter text-left">
					<span>{'Task'|getNewModuleLabel}</span>	{* crmv@59091 *}
				</div><!-- 
				 --><div class="col-sm-6 vcenter text-right">
					<i class="vteicon md-text" title="{'Task'|getNewModuleLabel}" >assignment_turned_in</i>	{* crmv@59091 *}
				</div>
			</div>
		</div>
	</div>
{/if}

{if $SHOW_TURBOLIFT_CREATE_BUTTON}
	<div id="turbolift_back_button" class="turboliftEntry1 turboliftEntryWithImage btn" onClick="releaseOverAll('detailViewActionsContainer'); LPOP.openPopup('{$MODULE}', {$ID}, 'onlycreate');">
		<div class="row no-gutter">
			<div class="col-sm-12">
				<div class="col-sm-6 vcenter text-left">
					<span>{'LBL_CREATE_AND_LINK_ACTION'|@getTranslatedString}</span> {* crmv@43050 *}
				</div><!-- 
				 --><div class="col-sm-6 vcenter text-right">
					<i class="vteicon md-text" title="{'LBL_CREATE_NEW'|@getTranslatedString:'Reports'}">add</i>
				</div>
			</div>
		</div>
	</div>
{/if}

{* crmv@44609 *}
{if $SHOW_TURBOLIFT_CONVERT_BUTTON}
	<div id="turbolift_back_button" class="turboliftEntry1 turboliftEntryWithImage btn" onClick="releaseOverAll('detailViewActionsContainer'); LPOP.openPopup('{$MODULE}', {$ID}, 'onlycreate', {ldelim}'callback_create':'LPOP.convert'{rdelim});">
		<div class="row no-gutter">
			<div class="col-sm-12">
				<div class="col-sm-6 vcenter text-left">
					<span>{'LBL_CONVERT_ACTION'|@getTranslatedString}</span>
				</div>
				<div class="col-sm-6 vcenter text-right">
					<i class="vteicon md-text" title="{'LBL_CONVERT_ACTION'|@getTranslatedString}">link</i>
				</div>
			</div>
		</div>
	</div>
{/if}
{* crmv@44609e *}

{if $MODULE eq 'Messages' || $MODULE eq 'Emails' || $MODULE eq 'MyNotes'}
	{if $SHOW_TURBOLIFT_LINK_BUTTON}
		<div id="turbolift_back_button" class="turboliftEntry1 turboliftEntryWithImage btn" onClick="releaseOverAll('detailViewActionsContainer'); LPOP.openPopup('{$MODULE}', {$ID}, '{$MODE}');">
			<div class="row no-gutter">
				<div class="col-sm-12">
					<div class="col-sm-6 vcenter text-left">
						<span style="padding:2px;" >{'LBL_LINK_ACTION'|@getTranslatedString:'Messages'}</span>
					</div>
					<div class="col-sm-6 vcenter text-right">
						<i class="vteicon md-text" title="{'LBL_LINK_ACTION'|@getTranslatedString:'Messages'}">link</i>
					</div>
				</div>
			</div>
		</div>
	{/if}
	{foreach key=entity_id item=info from=$LINKS}
		{if $MODULE neq 'Emails'}
			{assign var=CARDCLICK value="preView('"|cat:$info.link_module|cat:"','"|cat:$entity_id|cat:"');"} {* crmv@162866 *}
		{else}
			{assign var=CARDCLICK value=""}
		{/if}
		{include file='Card.tpl' TURBOLIFTCARD=true CARDRECORD=$entity_id CARDID="preView"|cat:$entity_id CARDMODULE=$info.module CARDMODULE_LBL=$info.modulelbl CARDNAME=$info.name CARDDETAILS=$info.details CARDONCLICK=$CARDCLICK IMG=$info.img} {* crmv@152802 *}
	{/foreach}
{/if}

{* crmv@93990 *}
{if $RELATED_PROCESS neq false}
	<div id="turbolift_back_button" class="turboliftEntry1 turboliftEntryWithImage btn" onClick="releaseOverAll('detailViewActionsContainer'); DynaFormScript.popup({$RELATED_PROCESS});">
		<div class="row no-gutter">
			<div class="col-sm-12">
				<div class="col-sm-6 vcenter text-left">
					<span style="padding:2px;" >{'LBL_RUN_PROCESSES'|@getTranslatedString:'Processes'}</span>
				</div>
				<div class="col-sm-6 vcenter text-right">
					<i class="md-text icon-module icon-processes" data-first-letter="P"></i>
				</div>
			</div>
		</div>
	</div>
{/if}
{* crmv@93990 *}