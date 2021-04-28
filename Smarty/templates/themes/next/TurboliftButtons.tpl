{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

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

{if $SHOW_TURBOLIFT_TRACKER}
	{include file="modules/SDK/src/CalendarTracking/TurboliftTracking.tpl"}
{/if}

{if $MODULE eq 'Messages' || $MODULE eq 'Emails' || $MODULE eq 'MyNotes'}
	{if $SHOW_TURBOLIFT_CAL_BUTTONS}
		<button type="button" class="crmbutton with-icon edit crmbutton-turbolift" onclick="releaseOverAll('detailViewActionsContainer'); LPOP.openEventCreate('{$MODULE}', {$ID}, 'Events');">
			<i class="vteicon md-text" title="{'LBL_ADD'|@getTranslatedString:'Calendar'}">event</i>
			<span>{'Event'|getNewModuleLabel}</span>
		</button>
	
		<button type="button" class="crmbutton with-icon edit crmbutton-turbolift" onclick="releaseOverAll('detailViewActionsContainer'); LPOP.openEventCreate('{$MODULE}', {$ID}, 'Task');">
			<i class="vteicon md-text" title="{'Task'|getNewModuleLabel}">assignment_turned_in</i>
			<span>{'Task'|getNewModuleLabel}</span>
		</button>
	{/if}
{/if}

{if $SHOW_TURBOLIFT_CREATE_BUTTON}
	<button type="button" class="crmbutton with-icon edit crmbutton-turbolift" onclick="LPOP.openPopup('{$MODULE}', {$ID}, 'onlycreate');">
		<i class="vteicon md-text" title="{'LBL_CREATE_NEW'|@getTranslatedString:'Reports'}">add</i>
		<span>{'LBL_CREATE_AND_LINK_ACTION'|@getTranslatedString}</span>
	</button>
{/if}

{* crmv@44609 *}
{if $SHOW_TURBOLIFT_CONVERT_BUTTON}
	<button type="button" class="crmbutton with-icon edit crmbutton-turbolift" onclick="LPOP.openPopup('{$MODULE}', {$ID}, 'onlycreate', {ldelim}'callback_create':'LPOP.convert'{rdelim});">
		<i class="vteicon md-text" title="{'LBL_CONVERT_ACTION'|@getTranslatedString}">link</i>
		<span>{'LBL_CONVERT_ACTION'|@getTranslatedString}</span>
	</button>
{/if}
{* crmv@44609e *}

{if $MODULE eq 'Messages' || $MODULE eq 'Emails' || $MODULE eq 'MyNotes'}
	{if $SHOW_TURBOLIFT_LINK_BUTTON}
		<button type="button" class="crmbutton with-icon edit crmbutton-turbolift" onclick="releaseOverAll('detailViewActionsContainer'); LPOP.openPopup('{$MODULE}', {$ID}, '{$MODE}');">
			<i class="vteicon md-text" title="{'LBL_LINK_ACTION'|@getTranslatedString:'Messages'}">link</i>
			<span>{'LBL_LINK_ACTION'|@getTranslatedString:'Messages'}</span>
		</button>
	{/if}
	<div class="turbolift-relations">
		{foreach key=entity_id item=info from=$LINKS}
			{if $MODULE neq 'Emails'}
				{assign var=CARDCLICK value="preView('"|cat:$info.link_module|cat:"','"|cat:$entity_id|cat:"');"} {* crmv@162866 *}
			{else}
				{assign var=CARDCLICK value=""}
			{/if}
			{include file='Card.tpl' TURBOLIFTCARD=true CARDRECORD=$entity_id CARDID="preView"|cat:$entity_id CARDMODULE=$info.module CARDMODULE_LBL=$info.modulelbl CARDNAME=$info.name CARDDETAILS=$info.details CARDONCLICK=$CARDCLICK IMG=$info.img} {* crmv@152802 *}
		{/foreach}
	</div>
{/if}

{* crmv@93990 *}
{if $RELATED_PROCESS neq false}
	<button type="button" class="crmbutton with-icon edit crmbutton-turbolift" onclick="DynaFormScript.popup({$RELATED_PROCESS});">
		<i class="icon-module icon-processes md-text" data-first-letter="P"></i>
		<span>{'LBL_RUN_PROCESSES'|@getTranslatedString:'Processes'}</span>
	</button>
{/if}
{* crmv@93990 *}