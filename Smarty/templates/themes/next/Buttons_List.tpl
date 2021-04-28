{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

{if $MODULE neq ''}
	<script type="text/javascript" src="{"modules/`$MODULE`/`$MODULE`.js"|resourcever}"></script>
{/if}

<ul id="Buttons_List_Fixed_Container" style="display:none;">

	{* Search *}
	<li data-module="GlobalSearch" data-fastpanel="custom" data-fastsize="70%">
		<a href="javascript:;">
			<i data-toggle="tooltip" data-placement="top" class="vteicon" title="{$APP.LBL_SEARCH}">search</i>
		</a>
	</li>
	
	{* Processes *}
	{if 'Processes'|vtlib_isModuleActive}
		<li data-module="Processes" data-fastpanel="full">
			<a href="javascript:;">
				<i data-toggle="tooltip" data-placement="top" id="ProcessesCheckChangesImg" class="vteicon" title="{'Processes'|getTranslatedString:'Processes'}">call_split</i>
			</a>
			<span class="badge vte-top-badge" id="ProcessesCheckChangesDivCount"></span>
		</li>
	{/if}
	
	{* crmv@180714 - removed code *}
	
	{if $WORLD_CLOCK_DISPLAY eq 'true'}
		<li><a href="javascript:;"><i class="vteicon" data-toggle="tooltip" data-placement="top" title="{$APP.LBL_CLOCK_TITLE}" onClick="fnvshobj(this,'wclock');">access_time</i></a></li> {* crmv@82419 *}
	{/if}
	
	{* crmv@208475 *}
	
	{* Last Viewed *}
	<li data-module="LastViewed" data-fastpanel="custom" data-fastsize="350px">
		<a href="javascript:;">
			<i data-toggle="tooltip" data-placement="top" class="vteicon" title="{$APP.LBL_LAST_VIEWED}">list</i>
		</a>
	</li>
	
	{* Calendar *}
	<li data-module="Calendar" data-fastpanel="full" data-hover-module="EventList" data-hover-fastpanel="custom" data-hover-size="220px">
		<a href="javascript:;">
			<i data-toggle="tooltip" data-placement="top" class="vteicon" title="{'Calendar'|getTranslatedString:'Calendar'}">event</i>
		</a>
	</li>
	
	{* Messages *}
	{if 'Messages'|vtlib_isModuleActive}
		<li data-module="Messages" data-fastpanel="full">
			<a href="javascript:;">
				<i data-toggle="tooltip" data-placement="top" id="MessagesCheckChangesImg" class="vteicon" title="{'Messages'|getTranslatedString:'Messages'}">email</i> {* crmv@120023 *}
			</a>
			<span class="badge vte-top-badge" id="MessagesCheckChangesDivCount"></span> {* crmv@120023 *}
		</li>
	{/if}
	
	{* Talks *}
	{if 'ModComments'|vtlib_isModuleActive}
		<li data-module="ModComments" data-fastpanel="half">
			<a href="javascript:;">
				<i data-toggle="tooltip" data-placement="top" id="ModCommentsCheckChangesImg" class="vteicon" title="{'LBL_MODCOMMENTS_COMMUNICATIONS'|getTranslatedString:'ModComments'}">chat</i>
			</a>
			<span class="badge vte-top-badge" id="ModCommentsCheckChangesDivCount"></span>
		</li>
	{/if}
	
	{* Notifications *}
	<li data-module="ModNotifications" data-fastpanel="half">
		<a href="javascript:;">
			<i data-toggle="tooltip" data-placement="top" class="vteicon" id="ModNotificationsCheckChangesImg" title="{'ModNotifications'|getTranslatedString:'ModNotifications'}">language</i>
		</a>
		<span class="badge vte-top-badge" id="ModNotificationsCheckChangesDivCount"></span>
	</li>
	
	{* Todos *}
	<li data-module="TodoList" data-fastpanel="half">
		<a href="javascript:;">
			<i data-toggle="tooltip" data-placement="top" id="TodosCheckChangesImg" class="vteicon" title="{'Todos'|getTranslatedString:'ModComments'}">assignment_turned_in</i>
		</a>
		<span class="badge vte-top-badge" id="TodosCheckChangesDivCount"></span>
	</li>
	
	{* Quick create *}
	<li data-module="QuickCreate" data-fastpanel="custom" data-fastsize="350px">
		<a href="javascript:;">
			<i data-toggle="tooltip" data-placement="top" class="vteicon" title="{$APP.LBL_QUICK_CREATE}">flash_on</i>
		</a>
	</li>
	
	{* Fixed buttons *}
	
	{assign var=FIXED_BUTTONS value=$SDK->getMenuRawButton('fixed')}
	
	{foreach from=$FIXED_BUTTONS item=button}
		{assign var=button_id value=$button.id}
		{assign var=button_module value=$button.module}
		{assign var=button_title value=$button.title}
		{assign var=button_onclick value=$button.onclick}
		{assign var=button_image value=$button.image}
		{assign var=panel_size value="400px"}
		
		{if $button_title eq 'LBL_TRACK_MANAGER'}
			{assign var=panel_size value="50%"}
		{/if}
		
		{if $button_title neq 'Events'}
			<li data-module="{$button_title}" data-fastpanel="custom" data-fastsize="{$panel_size}" data-onclick="{$button_onclick}">
				<a href="javascript:;">
					{if $button.is_image}
						<img data-toggle="tooltip" data-placement="top" src="{$button_image}" alt="{$button_title|getTranslatedString}" title="{$button_title|getTranslatedString}" style="cursor:pointer;">
					{else}
						<i class="vteicon md-link" data-toggle="tooltip" data-placement="top" title="{$button_title|getTranslatedString}">{$button_image}</i>
					{/if}
				</a>
			</li>
		{/if}
	{/foreach}
	
</ul>

<script type="text/javascript">

jQuery('#Buttons_List_Fixed').html(jQuery('#Buttons_List_Fixed_Container').html());
jQuery('#Buttons_List_Fixed_Container').html('');

{* crmv@183872 *}
{if $MODULE neq 'Messages'}
	{assign var="NOTIFICATION_MODULES" value="Messages,ModComments,ModNotifications,Todos,Processes"}
{else}
	{assign var="NOTIFICATION_MODULES" value="ModComments,ModNotifications,Todos,Processes"}
{/if}
{* crmv@183872e *}

jQuery('#Buttons_List_Fixed_Container').ready(function() {ldelim}
	NotificationsCommon.showChangesFirst('CheckChangesDiv','CheckChangesImg','{$NOTIFICATION_MODULES}','{$PERFORMANCE_CONFIG.NOTIFICATION_INTERVAL_TIME}');
	NotificationsCommon.showChangesInterval('CheckChangesDiv','CheckChangesImg','{$NOTIFICATION_MODULES}','{$PERFORMANCE_CONFIG.NOTIFICATION_INTERVAL_TIME}');
{rdelim});

</script>