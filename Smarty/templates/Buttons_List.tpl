{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<!-- crmv@18549 crmv@19842 -->
{if $MODULE neq ''}
	<script type="text/javascript" src="{"modules/`$MODULE`/`$MODULE`.js"|resourcever}"></script>
{/if}
{* crmv@22622 *}
{if $smarty.cookies.crmvWinMaxStatus eq 'close'}
	{assign var="minImg" value="_min"}
	{assign var="minFontSize" value="font-size:14px;"}
	{assign var="minIcon" value=""}
{else}
	{assign var="minImg" value=""}
	{assign var="minFontSize" value=""}
	{assign var="minIcon" value="md-lg"}
{/if}
{* crmv@30356 *}
{if isMobile() neq true}
<div id="Buttons_List_SiteMap_Container" style="display:none;">
	<table border=0 cellspacing=0 cellpadding=5 class=small>
	<tr>
		{assign var="MODULELABEL" value=$MODULE|@getTranslatedString:$MODULE}
		{* crmv@20209 *}
		{if $smarty.request.module eq 'Users' || $smarty.request.module eq 'Administration'}
			{assign var=MODULE value=Users}
			{assign var="MODULELABEL" value=$MODULE|@getTranslatedString:$MODULE}
			{assign var=CATEGORY value=$smarty.request.parenttab}
			<td style="padding-left:10px;padding-right:50px" class="moduleName" nowrap><a class="hdrLink" style="{$minFontSize}" href="index.php?action=index&module=Administration&parenttab={$CATEGORY}">{$MODULELABEL}</a></td>
		{* crmv@30683 *}
		{elseif $smarty.request.module eq 'Settings' || $smarty.request.module eq 'PickList' || $smarty.request.module eq 'Picklistmulti' || $smarty.request.module eq 'com_workflow' || $smarty.request.module eq 'Conditionals' || $smarty.request.module eq 'Transitions'}
			{assign var=MODULE value=Settings}
			{assign var="MODULELABEL" value=$MODULE|@getTranslatedString:$MODULE}
			<td style="padding-left:10px;padding-right:50px" class="moduleName" nowrap><a class="hdrLink" style="{$minFontSize}" href="index.php?module=Settings&action=index&parenttab=Settings&reset_session_menu_tab=true">{$MODULELABEL}</a></td>
		{* crmv@30683e *}
		{* crmv@20209e *}
		{elseif $CATEGORY eq 'Settings'}
			<!-- No List View in Settings - Action is index -->
			<td style="padding-left:10px;padding-right:50px" class="moduleName" nowrap><a class="hdrLink" style="{$minFontSize}" href="index.php?action=index&module={$MODULE}&parenttab={$CATEGORY}">{$MODULELABEL}</a></td>
		{elseif $smarty.request.module eq 'Home' && $REQUEST_ACTION eq 'UnifiedSearch'}	
			<td style="padding-left:10px;padding-right:50px" class="moduleName" nowrap><a class="hdrLink" style="{$minFontSize}" href="javascript:;">{'LBL_SEARCH'|@getTranslatedString:'Home'}</a></td>
		{* crmv@43942 *}
		{elseif $MODULE eq 'Area' && $REQUEST_ACTION eq 'index'}
			<td style="padding-left:10px;padding-right:50px" class="moduleName" nowrap><a class="hdrLink" style="{$minFontSize}" href="index.php?module=Area&action=index&area={$AREAID}">{$AREALABEL}</a></td>
		{* crmv@43942e *}
		{elseif $MENU_LAYOUT.type neq 'modules'}
			<td style="padding-left:10px;padding-right:50px" class="moduleName" nowrap>{$APP.$CATEGORY} > <a class="hdrLink" style="{$minFontSize}" href="index.php?action=index&module={$MODULE}&parenttab={$CATEGORY}">{$MODULELABEL}</a></td>	{* crmv@102334 *}
		{else}
			<td style="padding-left:10px;padding-right:50px" class="moduleName" nowrap><a class="hdrLink" style="{$minFontSize}" href="index.php?action=index&module={$MODULE}&parenttab={$CATEGORY}">{$MODULELABEL}</a></td>	{* crmv@102334 *}
		{/if}
	</tr>
	</table>
</div>
{/if}
{* crmv@30356e crmv@82419 *}
<div id="Buttons_List_Fixed_Container" style="display:none;">
	<table border=0 cellspacing=0 cellpadding=2 class=small>
	<tr>
		{* crmv@188276 *}
		{* Processes *}
		{if 'Processes'|vtlib_isModuleActive}
		<td>
			<div style="position:relative;">
				<a target="_blank" href="index.php?module=Processes&amp;action=ListView&amp;viewname=Pending">
					<i data-toggle="tooltip" data-placement="top" id="ProcessesCheckChangesImg" class="vteicon {$minIcon}" title="{'Processes'|getTranslatedString:'Processes'}">call_split</i>
				</a>
				<span class="badge vte-top-badge" id="ProcessesCheckChangesDivCount"></span>
			</div>
		</td>
		{/if}
		{* crmv@188276e *}
		{* crmv@180714 - removed code *}
		{if $WORLD_CLOCK_DISPLAY eq 'true'}
			<td><a href="javascript:;"><i class="vteicon {$minIcon}" data-toggle="tooltip" data-placement="top" title="{$APP.LBL_CLOCK_TITLE}" onClick="fnvshobj(this,'wclock');">access_time</i></a></td> {* crmv@82419 *}
		{/if}
		{* crmv@208475 *}
		<!-- All Menu -->
		<td><a href="javascript:;">
			<i data-toggle="tooltip" data-placement="top" class="vteicon {$minIcon}" title="{$APP.LBL_LAST_VIEWED}" onClick="showFloatingDiv('Tracker', this); getLastViewedList();">list</i>
		</a>
		</td>	{* crmv@32429 crmv@82419 *}
		{$SDK->getMenuButton('fixed')}	{* crmv@24189 *}
		{* crmv@2963m *}
		{if $MODULE neq 'Messages' && 'Messages'|vtlib_isModuleActive}
			<td>
				<div style="position:relative;">
					<a href="javascript:;">
						<i data-toggle="tooltip" data-placement="top" id="MessagesCheckChangesImg" class="vteicon {$minIcon}" title="{'Messages'|getTranslatedString:'Messages'}" onmouseover="fnDropDown(this,'MessagesNotification_sub');" onmouseout="fnHideDrop('MessagesNotification_sub');">email</i> {* crmv@120023 *}
					</a>
					<span class="badge vte-top-badge" id="MessagesCheckChangesDivCount" onmouseover="fnDropDown(getObj('MessagesCheckChangesImg'),'MessagesNotification_sub');" onmouseout="fnHideDrop('MessagesNotification_sub');"></span> {* crmv@120023 *}
				</div>
				<div class="drop_mnu" id="MessagesNotification_sub" onmouseout="fnHideDrop('MessagesNotification_sub')" onmouseover="fnShowDrop('MessagesNotification_sub')" style="width:200px;">
					<table width="100%" border="0" cellpadding="0" cellspacing="0">
						<tr><td><a class="drop_down" style="width:200px;" href="javascript:OpenCompose('','create');">{'LBL_COMPOSE'|getTranslatedString:'Messages'}</a></td></tr>
						<tr><td><a class="drop_down" style="width:200px;" href="index.php?module=Messages&action=index">{'LBL_VIEW_MESSAGES'|getTranslatedString:'Messages'}</a></td></tr>
						{if !empty($BUTTONS.s_mail)}
							<tr><td><a class="drop_down" style="width:200px;" href="javascript:;" onClick="return eMail('{$MODULE}',this);">{'LBL_MASS_MAIL'|getTranslatedString:'Messages'}</a></td></tr>
						{/if}
						{if !empty($NAME) && !empty($MODULE) && !empty($ID)}
							{assign var="SHOW_COMPOSE_TO" value=false}
							{if !empty($IS_REL_LIST) && 'Messages'|in_array:$IS_REL_LIST}
								{assign var="SHOW_COMPOSE_TO" value=true}
							{elseif !empty($RELATEDLISTS)}
								{foreach key=header item=detail from=$RELATEDLISTS}
									{assign var=related_module value=$detail.related_tabid|@getTabModuleName}
									{if $related_module eq 'Messages'}
										{assign var="SHOW_COMPOSE_TO" value=true}
									{/if}
								{/foreach}
							{/if}
							{if $SHOW_COMPOSE_TO eq true}
								<tr><td><a class="drop_down" style="width:200px;" href="javascript:;" onClick="fnvshobj(this,'sendmail_cont');sendmail('{$MODULE}',{$ID});">{'LBL_LINK_NEW_MAIL'|getTranslatedString:'Messages'}</a></td></tr>
							{/if}
						{/if}
					</table>
				</div>
			</td>
		{/if}
		{* crmv@2963me *}
		{* crmv@28295 *}
		<td nowrap>
			<div style="position:relative;">
				<a href="javascript:;">
					<i data-toggle="tooltip" data-placement="top" id="TodosCheckChangesImg" class="vteicon {$minIcon}" onclick="fnvshobj(this,'todos');getTodoList();" title="{'Todos'|getTranslatedString:'ModComments'}">assignment_turned_in</i>
				</a>
				<span class="badge vte-top-badge" id="TodosCheckChangesDivCount" onclick="fnvshobj(this,'todos');getTodoList();"></span>
			</div>
		</td>
		{* crmv@28295e *}
		{* crmv@29079 *}
		{if 'ModComments'|vtlib_isModuleActive}
			<td>
				<div style="position:relative;">
					<a href="javascript:;">
						<i data-toggle="tooltip" data-placement="top" id="ModCommentsCheckChangesImg" class="vteicon {$minIcon}" title="{'LBL_MODCOMMENTS_COMMUNICATIONS'|getTranslatedString:'ModComments'}" onClick="getModCommentsNews(this);">chat</i>
					</a>
					<span class="badge vte-top-badge" id="ModCommentsCheckChangesDivCount" onclick="getModCommentsNews(this);"></span>
				</div>
			</td>
		{/if}
		{* crmv@29079e *}
		{* crmv@29617 *}
		<td>
			<div style="position:relative;">
				<a href="javascript:;">
					<i data-toggle="tooltip" data-placement="top" class="vteicon {$minIcon}" id="ModNotificationsCheckChangesImg" title="{'ModNotifications'|getTranslatedString:'ModNotifications'}" onClick="ModNotificationsCommon.getLastNotifications(this);">language</i>
				</a>
				<span class="badge vte-top-badge" id="ModNotificationsCheckChangesDivCount" onclick="ModNotificationsCommon.getLastNotifications(this);"></span>
			</div>
		</td>
		{* crmv@29617e *}
	</tr>
	</table>
</div>
<div id="Buttons_List_Contestual_Container" style="display:none;">
	<table id="Buttons_List_Contestual_Container_Table" border=0 cellspacing=0
			cellpadding="{if $smarty.cookies.crmvWinMaxStatus eq 'close'}4{else}6{/if}"
			class="small" style="height: {if $smarty.cookies.crmvWinMaxStatus eq 'close'}38px{else}57px{/if}"> {* crmv@181170 *}
	<tr>
		{if $MODULE eq 'Home' && $REQUEST_ACTION eq 'index'}
			<td>
				<i data-toggle="tooltip" data-placement="top" class="vteicon {$minIcon}" onClick='fnAddWindow(this,"addWidgetDropDown",-4);' onMouseOut='VTE.Homestuff.fnRemoveWindow();' title="{'LBL_HOME_ADDWINDOW'|getTranslatedString:$MODULE}" style="cursor:pointer;">add</i>
			</td>{*crmv@23264*}
		{elseif $CHECK.EditView eq 'yes' || ($MODULE eq 'Projects' && ( $ISPROJECTADMIN eq 'yes' || $ISPROJECTLEADER eq 'yes'))}
			{* crmv@2963m *}
			{if $MODULE eq 'Messages'}
				<td>
					<a href="javascript:;" onclick="OpenCompose('','create');" style="text-decoration:none;">
						<i class="vteicon {$minIcon}" style="vertical-align:middle">add</i>
						&nbsp;{'LBL_COMPOSE'|getTranslatedString:'Messages'}
					</a>
				</td>
				<td><a href="javascript:;" onclick="fetch();" style="text-decoration:none;">
					<i class="vteicon {$minIcon}" id="fetchImg" style="vertical-align:middle;">autorenew</i>
					{include file="LoadingIndicator.tpl" LIID="fetchImgLoader" LIEXTRASTYLE="display:none"}
					&nbsp;{'LBL_FETCH'|getTranslatedString:'Messages'}
				</a></td>
				<td><a href="javascript:;" onclick="openPopup('index.php?module=Messages&action=MessagesAjax&file=Settings/index','','','auto',720,500);"><i class="vteicon {$minIcon}" title="{$APP.LBL_SETTINGS}">settings_applications</i></a></td> {* crmv@114260 *}
			{* crmv@2963me *}
			{elseif $MODULE neq 'Calendar' && $HIDE_BUTTON_CREATE neq true} {* crmv@30014 *}
				<td><a href="index.php?module={$MODULE}&action=EditView&return_action=DetailView&parenttab={$CATEGORY}&folderid={$FOLDERID}">
					<i data-toggle="tooltip" data-placement="top" class="vteicon {$minIcon}" title="{$APP.LBL_CREATE_BUTTON_LABEL} {$SINGLE_MOD|getTranslatedString:$MODULE}">add</i>
				</a></td> {* crmv@30967 *}
			{/if}
		{/if}
		{* crmv@81193 *}
		{if $MODULE eq 'Calendar' && $REQUEST_ACTION eq 'index'}
			<td id="CalendarAddButton" style="height: {if $smarty.cookies.crmvWinMaxStatus eq 'close'}18px{else}32px{/if}"></td>	{* crmv@20480 *} {* crmv@181170 *}
		{/if}
		{* crmv@81193e *}
		{* crmv@29386 *}
		{if $MODULE eq 'Webforms'}
			<td><a href="index.php?module={$MODULE}&action=WebformsEditView&return_action=DetailView&parenttab={$CATEGORY}"><i class="vteicon add {$minIcon}" data-toggle="tooltip" data-placement="top" title="{$APP.LBL_CREATE_BUTTON_LABEL} {$SINGLE_MOD|getTranslatedString:$MODULE}">add</i></a></td>
		{/if}
		{* crmv@29386e *}
		{if $REQUEST_ACTION eq 'index' || $REQUEST_ACTION eq 'ListView'}
			{* vtlib customization: Hook to enable import/export button for custom modules. Added CUSTOM_MODULE *}
			{if $MODULE eq 'Assets' || $MODULE eq 'ServiceContracts' || $MODULE eq 'Vendors' || $MODULE eq 'HelpDesk' || $MODULE eq 'Contacts' || $MODULE eq 'Leads' || $MODULE eq 'Accounts' || $MODULE eq 'Potentials' || $MODULE eq 'Products' || $MODULE eq 'Services' || $MODULE eq 'Calendar' || $CUSTOM_MODULE eq 'true'} {* crmv@32465 *}
		   		{if $CHECK.Import eq 'yes' && $MODULE neq 'Calendar'}
					<td><a href="index.php?module={$MODULE}&action=Import&step=1&return_module={$MODULE}&return_action=index&parenttab={$CATEGORY}">
						<i data-toggle="tooltip" data-placement="top" class="vteicon {$minIcon}" title="{$APP.LBL_IMPORT} {$APP.$MODULE}">file_download</i>
					</a></td>
				{elseif  $CHECK.Import eq 'yes' && $MODULE eq 'Calendar' && $REQUEST_ACTION eq 'ListView'} {* crmv@104881 *}
					<td><a name='export_link' href="javascript:void(0);" onclick="showFloatingDiv('CalImport', this);" >
						<i data-toggle="tooltip" data-placement="top" class="vteicon {$minIcon}" title="{$APP.LBL_IMPORT} {$MODULELABEL}">file_download</i>
					</a></td>	<!-- crmv@16531 -->
				{/if}
				
				{if $CHECK.Export eq 'yes' && $MODULE neq 'Calendar'}
					<td><a name='export_link' href="javascript:void(0)" onclick="return selectedRecords('{$MODULE}','{$CATEGORY}')">
						<i data-toggle="tooltip" data-placement="top" class="vteicon {$minIcon}" title="{$APP.LBL_EXPORT} {$APP.$MODULE}">file_upload</i>
					</a></td>
				{elseif  $CHECK.Export eq 'yes' && $MODULE eq 'Calendar' && $REQUEST_ACTION eq 'ListView'}  {* crmv@104881 *}
					<td><a name='export_link' href="javascript:void(0);" onclick="showFloatingDiv('CalExport', this);" >
						<i data-toggle="tooltip" data-placement="top" class="vteicon {$minIcon}" title="{$APP.LBL_EXPORT} {$MODULELABEL}">file_upload</i>
					</a></td>
				{/if}
			{elseif $MODULE eq 'Documents' && $CHECK.Export eq 'yes' && $REQUEST_ACTION eq 'ListView'} {* crmv@30967 *}
				<td><a name='export_link' href="javascript:void(0)" onclick="return selectedRecords('{$MODULE}','{$CATEGORY}')"><i class="vteicon {$minIcon}" data-toggle="tooltip" data-placement="top" title="{$APP.LBL_EXPORT} {$APP.$MODULE}">file_upload</i></a></td>
			{/if}
		{/if}
		<!-- crmv@8719 -->
		{if ($REQUEST_ACTION eq 'index' || $REQUEST_ACTION eq 'ListView') && ($MODULE eq 'Contacts' || $MODULE eq 'Leads' || $MODULE eq 'Accounts'|| $MODULE eq 'Products'|| $MODULE eq 'Potentials'|| $MODULE eq 'HelpDesk'|| $MODULE eq 'Vendors' || $MODULE eq 'Services' || $CUSTOM_MODULE eq 'true')} {* crmv@206281 *}
			{if $CHECK.DuplicatesHandling eq 'yes'}
				<td><a href="javascript:;" onClick="MergeFieldsAjax();">
					<i data-toggle="tooltip" data-placement="top" class="vteicon {$minIcon}" title="{$APP.LBL_FIND_DUPLICATES}">pageview</i>
				</a></td>	{* crmv@69201 *}
			{/if}
		{/if}
		<!-- crmv@8719e -->
		{if $MODULE eq 'Reports'}
			<td><a href="javascript:;" onclick="Reports.createNew('{$FOLDERID}')"><i class="vteicon {$minIcon}" data-toggle="tooltip" data-placement="top" title="{'LBL_CREATE_REPORT'|@getTranslatedString:$MODULE}">add</i></a></td> {* crmv@97237 *}
			{* crmv@29686 crmv@30967 - removed *}
		{/if}
		{if $MODULE eq 'Home' && $REQUEST_ACTION eq 'index'}
			<td>
				<i data-toggle="tooltip" data-placement="top" class="vteicon {$minIcon}" onClick='VTE.Homestuff.showOptions("changeLayoutDiv");' title="{'LBL_HOME_LAYOUT'|getTranslatedString:$MODULE}">create</i>
			</td>
			<td><a href='index.php?module=Users&action=EditView&record={$CURRENT_USER_ID}&scroll=home_page_components&return_module=Home&return_action=index'>
				<i data-toggle="tooltip" data-placement="top" class="vteicon {$minIcon}"  title="{$APP.LBL_SETTINGS} {$MODULELABEL}">settings_applications</i>
			</a></td>
		{/if}
		{* crmv@20209 crmv@81193 *}
		{if $MODULE eq 'Calendar' && $REQUEST_ACTION eq 'index'}
			{assign var=scroll value="LBL_CALENDAR_CONFIGURATION"|getTranslatedString:"Users"}
			{assign var=scroll value=$scroll|replace:' ':'_'}
			{if $smarty.cookies.crmvWinMaxStatus eq 'close'}
				{assign var="minStatus" value=""}
			{else}
				{assign var="minStatus" value="maximize"}
			{/if}
			{* crmv@158543 *}
			<td>
				{if $IS_ADMIN == 1}
				<a onmouseover="fnDropDown(this,'moduleSettings_sub');" onmouseout="fnHideDrop('moduleSettings_sub');">
				{else}
				<a href='index.php?module=Users&action=EditView&record={$CURRENT_USER_ID}&scroll={$scroll}&return_module=Calendar&return_action=index'>
				{/if}
					<span class="stackedicon {$minIcon}" data-toggle="tooltip" data-placement="top" title="{$APP.LBL_SETTINGS} {$MODULELABEL}">
						<i class="vteicon {$minIcon}">event</i>
						<i class="vteicon {$minIcon} md-pedix">settings</i>
					<span>
				</a>
				{if $IS_ADMIN eq 1}
				<div class="drop_mnu" id="moduleSettings_sub" onmouseout="fnHideDrop('moduleSettings_sub')" onmouseover="fnShowDrop('moduleSettings_sub')" style="width:200px;">
					<table width="100%" border="0" cellpadding="0" cellspacing="0">
						<tr><td><a class="drop_down" style="width:200px" href="index.php?module=Users&action=EditView&record={$CURRENT_USER_ID}&scroll={$scroll}&return_module=Calendar&return_action=index">{$APP.LBL_SETTINGS} {$MODULELABEL}</a></td></tr>
						<tr><td><a class="drop_down" style="width:200px" href="index.php?module=Settings&amp;action=ModuleManager&amp;module_settings=true&amp;formodule={$MODULE}&amp;parenttab=Settings">{$APP.LBL_ADVANCED}</a></td></tr>
					</table>
				</div>
				{/if}
			</td>
			{* crmv@158543e *}
			{* crmv@194723 *}
			<td id="loadRolesModalContainer" style="display:none;">
				<button type="button" id="loadRolesModal" class="crmbutton with-icon save crmbutton-nav" onclick="window.wdCalendar.CalendarResources.onLoadRolesModal();">
					<i class="vteicon">people</i>
					{'LBL_SELECT_RESOURCES'|getTranslatedString:'Calendar'}
				</button>
			</td>
			{* crmv@194723e *}
		{/if}
		{* crmv@20209e crmv@81193e *}
		{* crmv@20640 crmv@105193 *}
		{if $CAN_ADD_HOME_BLOCKS || $CAN_ADD_HOME_VIEWS || $CAN_DELETE_HOME_VIEWS || $CAN_TOGGLE_EDITMODE} {* crmv@160778 *}
			{assign var="CAN_EDIT_HOMEVIEW" value="yes"}
		{else}
			{assign var="CAN_EDIT_HOMEVIEW" value="no"}
		{/if}
		{if $CHECK.moduleSettings eq 'yes' && ($IS_ADMIN == 1 || $CAN_EDIT_HOMEVIEW == 'yes') && $MODULE neq 'Users'} {* crmv@139855 *} {* crmv@160778 *}
        	<td id="moduleSettingsTd">
				{if $IS_ADMIN eq 1}
					<i data-toggle="tooltip" data-placement="top" class="vteicon {$minIcon} md-link" title="{"LBL_CONFIGURATION"|getTranslatedString:"Settings"}" onmouseover="fnDropDown(this,'moduleSettings_sub');" onmouseout="fnHideDrop('moduleSettings_sub');">settings_applications</i> {* crmv@120023 *}
				{else}
					<i data-toggle="tooltip" data-placement="top" class="vteicon {$minIcon} md-link" title="{$APP.LBL_CONFIG_PAGE}" onclick="ModuleHome.toggleEditMode()">settings_applications</i>
				{/if}
				<div class="drop_mnu" id="moduleSettings_sub" onmouseout="fnHideDrop('moduleSettings_sub')" onmouseover="fnShowDrop('moduleSettings_sub')" style="width:200px;">
					<table width="100%" border="0" cellpadding="0" cellspacing="0">
						<tr><td><a class="drop_down" style="width:200px" href="javascript:;" onclick="ModuleHome.toggleEditMode()">{$APP.LBL_CONFIG_PAGE}</a></td></tr>
						{if $IS_ADMIN eq 1}
						<tr><td><a class="drop_down" style="width:200px" href="index.php?module=Settings&amp;action=ModuleManager&amp;module_settings=true&amp;formodule={$MODULE}&amp;parenttab=Settings">{$APP.LBL_ADVANCED}</a></td></tr>
						{/if}
					</table>
				</div>
        	</td>
        	<td id="moduleSettingsResetTd" style="display:none">
				<a href="javascript:;" onclick="ModuleHome.toggleEditMode()">
					<i class="vteicon {$minIcon} md-link" style="vertical-align:middle" data-toggle="tooltip" data-placement="top" title="{$APP.LBL_DONE_BUTTON_TITLE}">settings_applications</i>
					<span>{$APP.LBL_DONE_BUTTON_TITLE}</span>
				</a>
			</td>
		{/if}
		{* crmv@20640e crmv@105193e *}
		{* crmv@24189 *}
		{if $REQUEST_ACTION neq 'UnifiedSearch'}
			{$SDK->getMenuButton('contestual',$MODULE)}
			{$SDK->getMenuButton('contestual',$MODULE,$REQUEST_ACTION)}
		{/if}
		{* crmv@24189e *}
	</tr>
	</table>
</div>
{* crmv@22622e crmv@82419e *}
<script type="text/javascript">
jQuery('#Buttons_List_SiteMap').html(jQuery('#Buttons_List_SiteMap_Container').html());jQuery('#Buttons_List_SiteMap_Container').html('');
{if $MENU_LAYOUT.type eq 'modules'}
	//crmv@30356
	{if isMobile()}
		jQuery('#Buttons_List_SiteMap').width(10);
	{else}
		jQuery('#Buttons_List_SiteMap').width(200);
	{/if}
{else}
	{if isMobile()}
		jQuery('#Buttons_List_SiteMap').width(10);
	{else}
		jQuery('#Buttons_List_SiteMap').width(280);
	{/if}
	//crmv@30356e
{/if}

{* crmv@22622 *}
{* crmv@30356 *}
jQuery('#Buttons_List_Fixed').html(jQuery('#Buttons_List_Fixed_Container').html());
jQuery('#Buttons_List_Fixed_Container').html('');
jQuery('#Buttons_List_QuickCreate').show();
{* crmv@30356e *}
contestual_menu = jQuery('#Buttons_List_Contestual_Container').html();
jQuery('#Buttons_List_Contestual').html(contestual_menu);jQuery('#Buttons_List_Contestual_Container').html('');
//crmv@20445
if ((contestual_menu.indexOf('IMG') != -1) || (contestual_menu.indexOf('img') != -1) || (contestual_menu.indexOf('i') != -1)) {ldelim}
	jQuery('#Buttons_List_Contestual').show();
{rdelim}
//crmv@20445e
jQuery('#vte_menu_white').height(jQuery('#vte_menu').height());

{* crmv@22622 e *}
var menubar = "{VteSession::get('menubar')}"; // crmv@181170
{literal}
jQuery('.level2Bg img').on('mouseover mouseout', null, function(event) { // crmv@82419
	if (getCookie('crmvWinMaxStatus') != 'close' || menubar != 'no') {	//crmv@23715
		if (event.type == 'mouseover') {
			if (jQuery(this).attr('title') != '')
		    	var title = jQuery(this).attr('title');
		    else
		    	var title = jQuery(this).attr('title1');
		    if (title == '' || title == undefined) return false;

		    jQuery('#menu_tooltip_text').html(title);
		    jQuery(this).attr('title1',title);
		    jQuery(this).attr('title','');

			jQuery('#menu_tooltip').width('10');
		    var position = jQuery(this).offset();
		    jQuery('#menu_tooltip').width(jQuery('#menu_tooltip_text').width()+2);
		    //jQuery('#menu_tooltip').css('left',position.left+(jQuery(this).width()/2)-(jQuery('#menu_tooltip').width()/2));
		    jQuery('#menu_tooltip').css('left',position.left);
		    //crmv@23715
		    if (menubar == 'no') {
		    	jQuery('#menu_tooltip').css('top',8);
			}
			//crmv@23715e
		    jQuery('#menu_tooltip').show();
		} else {
			jQuery('#menu_tooltip').hide();
		}
	}
});
{/literal}
{* crmv@29079 crmv@29617 crmv@28295 crmv@35676 crmv@2963m crmv@OPER5904 *}
{if $MODULE neq 'Messages'}
	{assign var="NOTIFICATION_MODULES" value="Messages,ModComments,ModNotifications,Todos"}
{else}
	{assign var="NOTIFICATION_MODULES" value="ModComments,ModNotifications,Todos"}
{/if}
jQuery('#Buttons_List_Fixed_Container').ready(function(){ldelim}
	NotificationsCommon.showChangesFirst('CheckChangesDiv','CheckChangesImg','{$NOTIFICATION_MODULES}','{$PERFORMANCE_CONFIG.NOTIFICATION_INTERVAL_TIME}'); {*crmv@82948*}
	NotificationsCommon.showChangesInterval('CheckChangesDiv','CheckChangesImg','{$NOTIFICATION_MODULES}','{$PERFORMANCE_CONFIG.NOTIFICATION_INTERVAL_TIME}');
{rdelim});
{* end tags *}
</script>
<!-- crmv@18549e crmv@19842e -->