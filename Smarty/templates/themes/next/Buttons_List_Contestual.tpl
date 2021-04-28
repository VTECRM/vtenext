{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

<ul id="Buttons_List_Contestual" class="vteUlTable">
	{if !empty($BUTTONS)}
		{foreach key=button_check item=button_label from=$BUTTONS}
			{if $button_check eq 'back'}
				<li>
					<a class="crmbutton with-icon save crmbutton-nav" href='index.php?module={$MODULE}&action=index'>
						{if $FOLDERID > 0}
							<i class="vteicon">undo</i>
							{$APP.LBL_GO_BACK}
						{else}
							<i class="vteicon">folder</i>
							{$APP.LBL_FOLDERS}
						{/if}
					</a>
				</li>
			{/if}
		{/foreach}
	{/if}
	
	{if $MODULE eq 'Home' && $REQUEST_ACTION eq 'index'}
		<li>
			<div class="dropdown">
				<button type="button" class="crmbutton with-icon success crmbutton-nav" data-toggle="dropdown">
					<i class="vteicon">add</i>
					{'LBL_HOME_ADDWINDOW'|getTranslatedString:$MODULE}
				</button>
				<ul class="dropdown-menu dropdown-autoclose">
					<li>
						<a href="javascript:VTE.Homestuff.chooseType('Module');" id="addmodule">
							{$MOD.LBL_HOME_MODULE}
						</a>
					</li>
					{if $ALLOW_RSS eq "yes"}
						<li>
							<a href="javascript:VTE.Homestuff.chooseType('RSS');" id="addrss">
								{$MOD.LBL_HOME_RSS}
							</a>
						</li>
					{/if}
					{if $ALLOW_CHARTS eq "yes"}
						<li>
							<a href="javascript:VTE.Homestuff.chooseType('Charts');" id="addchart">
								{$APP.SINGLE_Charts}
							</a>
						</li>
					{/if}
					<li>
						<a href="javascript:VTE.Homestuff.chooseType('URL');" id="addURL">
							{$MOD.LBL_URL}
						</a>
					</li>
				</ul>
			</div>
		</li>
	{elseif $CHECK.EditView eq 'yes' || ($MODULE eq 'Projects' && ($ISPROJECTADMIN eq 'yes' || $ISPROJECTLEADER eq 'yes'))}
		{if $MODULE eq 'Messages'}
			<li>
				<button type="button" class="crmbutton with-icon success crmbutton-nav" onclick="OpenCompose('','create');">
					<i class="vteicon">add</i>
					{'LBL_COMPOSE'|getTranslatedString:'Messages'}
				</button>
			</li>
			<li>
				<button type="button" class="crmbutton with-icon info crmbutton-nav" onclick="fetch();">
					<i class="vteicon" title="{'LBL_FETCH'|getTranslatedString:'Messages'}" id="fetchImg">autorenew</i>
					{include file="LoadingIndicator.tpl" LIID="fetchImgLoader" LIEXTRASTYLE="display:none" LIOLDMODE=true}
					{'LBL_FETCH'|getTranslatedString:'Messages'}
				</button>
			</li>
		{elseif $MODULE neq 'Calendar' && $HIDE_BUTTON_CREATE neq true}
			<li>
				<a class="crmbutton with-icon success crmbutton-nav" href="index.php?module={$MODULE}&action=EditView&return_action=DetailView&parenttab={$CATEGORY}&folderid={$FOLDERID}">
					<i class="vteicon">add</i>
					{$APP.LBL_CREATE_BUTTON_LABEL}
				</a>
			</li>
		{/if}
	{/if}
	{if $MODULE eq 'Calendar' && $REQUEST_ACTION eq 'index' && !$DISABLE_CAL_CONTESTUAL_BUTTON}
		<li id="CalendarAddButton" style="display:none"></li>
	{/if}
	{if $MODULE eq 'Webforms' && $REQUEST_ACTION eq 'index'}
		<li>
			<a class="crmbutton with-icon success crmbutton-nav" href="index.php?module={$MODULE}&action=WebformsEditView&return_action=DetailView&parenttab={$CATEGORY}">
				<i class="vteicon add">add</i>
				{$APP.LBL_CREATE_BUTTON_LABEL} {$SINGLE_MOD|getTranslatedString:$MODULE}
			</a>
		</li>
	{/if}
	{if $MODULE eq 'Reports'}
		<li>
			<button type="button" class="crmbutton with-icon success crmbutton-nav" onclick="Reports.createNew('{$FOLDERID}')">
				<i class="vteicon">add</i>
				{'LBL_CREATE_REPORT'|@getTranslatedString:$MODULE}
			</button>
		</li>
	{/if}
	{if $MODULE eq 'Home' && $REQUEST_ACTION eq 'index'}
		<li>
			<button type="button" class="crmbutton with-icon save crmbutton-nav" onclick="VTE.Homestuff.showOptions('changeLayoutDiv');">
				<i class="vteicon">view_module</i>
				{'LBL_HOME_LAYOUT'|getTranslatedString:$MODULE}
			</button>
		</li>
		<li>
			<a class="crmbutton with-icon save crmbutton-nav" href="index.php?module=Users&action=EditView&record={$CURRENT_USER_ID}&scroll=home_page_components&return_module=Home&return_action=index">
				<i class="vteicon">settings</i>
				{$APP.LBL_SETTINGS} {$MODULELABEL}
			</a>
		</li>
	{/if}
	{* crmv@197575 *}
	{if ($MODULE eq 'Campaigns' || $MODULE eq 'Newsletter') && ($REQUEST_ACTION eq 'index' || $REQUEST_ACTION eq 'ListView')}
		<li>
			<a class="crmbutton with-icon success crmbutton-nav" href="javascript:openNewsletterWizard('$MODULE$', '');">
				<i class="vteicon2 fa-magic no-hover"></i> {'Newsletter'|getTranslatedString}
			</a>
		</li>
	{/if}
	{* crmv@197575e *}
	{if $MODULE eq 'Calendar' && $REQUEST_ACTION eq 'index' && !$DISABLE_CAL_CONTESTUAL_BUTTON}
		{assign var=scroll value="LBL_CALENDAR_CONFIGURATION"|getTranslatedString:"Users"}
		{assign var=scroll value=$scroll|replace:' ':'_'}
		{if 'Geolocalization'|vtlib_isModuleActive} {* crmv@186646 *}
			<li id="geoCalendarContainer">
				<button type="button" class="crmbutton with-icon save crmbutton-nav" onclick="window.wdCalendar.GeoCalendar();">
					<i class="vteicon">location_on</i>
					{'Geolocalization'|getTranslatedString:'Geolocalization'}
				</button>
			</li>
		{/if}
		{* crmv@194723 *}
		<li id="loadRolesModalContainer" style="display:none;">
			<button type="button" id="loadRolesModal" class="crmbutton with-icon save crmbutton-nav" onclick="window.wdCalendar.CalendarResources.onLoadRolesModal();">
				<i class="vteicon">people</i>
				{'LBL_SELECT_RESOURCES'|getTranslatedString:'Calendar'}
			</button>
		</li>
		{* crmv@194723e *}
		{* crmv@158543 *}
		<li>
			{if $IS_ADMIN == 1}
				<div class="dropdown">
					<button type="button" class="crmbutton only-icon save crmbutton-nav" data-toggle="dropdown">
						<i class="vteicon" data-toggle="tooltip" data-placement="bottom" title="{$APP.LBL_SETTINGS} {$MODULELABEL}">settings_applications</i>
					</button>
					<ul class="dropdown-menu dropdown-autoclose">
						<li>
							<a href="index.php?module=Users&action=EditView&record={$CURRENT_USER_ID}&scroll={$scroll}&return_module=Calendar&return_action=index">{$APP.LBL_SETTINGS} {$MODULELABEL}</a>
						</li>
						<li>
							<a href="index.php?module=Settings&amp;action=ModuleManager&amp;module_settings=true&amp;formodule={$MODULE}&amp;parenttab=Settings">{$APP.LBL_ADVANCED}</a>
						</li>
					</ul>
				</div>
			{else}
				<a class="crmbutton only-icon save crmbutton-nav" href="index.php?module=Users&action=EditView&record={$CURRENT_USER_ID}&scroll={$scroll}&return_module=Calendar&return_action=index">
					<i class="vteicon" data-toggle="tooltip" data-placement="bottom" title="{$APP.LBL_SETTINGS} {$MODULELABEL}">settings</i>
				</a>
			{/if}
		</li>
		{* crmv@158543e *}
	{/if}
	
	{* Contestual buttons *}
	
	{if $REQUEST_ACTION neq 'UnifiedSearch' && !$DISABLE_CAL_CONTESTUAL_BUTTON}
	
		{include file="Buttons/SDKButtons.tpl"}
	
	{/if}
	
	{if $MODULE neq 'Home' && $MODULE neq 'Messages' && $MODULE neq 'Reports' && ($REQUEST_ACTION eq 'index' || $REQUEST_ACTION eq 'ListView' || empty($REQUEST_ACTION)) && !$DISABLE_CAL_CONTESTUAL_BUTTON}
		<li>
			<div class="dropdown otherButton listview-dropdown">
				<button type="button" class="crmbutton with-icon save crmbutton-nav" data-toggle="dropdown">
					<i class="vteicon">reorder</i>
					{'LBL_OTHER'|getTranslatedString:'Users'}
				</button>
				<div class="dropdown-menu dropdown-menu-left dropdown-autoclose crmvDiv listview-menu-dropdown">
					{if !empty($BUTTONS)}
						{foreach key=button_check item=button_label from=$BUTTONS}
							{if $button_check eq 'del'}
								<button type="button" class="crmbutton delete crmbutton-turbolift" onclick="return massDelete('{$MODULE}')">
									<div>{$button_label}</div>
								</button>
							{elseif $button_check eq 's_mail'}
								<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="return eMail('{$MODULE}',this)">
									<div>{$button_label}</div>
								</button>
							{elseif $button_check eq 's_fax'}
								<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="return Fax('{$MODULE}',this)">
									<div>{$button_label}</div>
								</button>
							{elseif $button_check eq 's_sms'}
								<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="return Sms('{$MODULE}',this)">
									<div>{$button_label}</div>
								</button>
							{elseif $button_check eq 's_cmail'}
								<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="return massMail('{$MODULE})"> {* crmv@192040 *}
									<div>{$button_label}</div>
								</button>
							{elseif $button_check eq 'c_status'}
								<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="return change(this,'changestatus')">
									<div>{$button_label}</div>
								</button>
							{elseif $button_check eq 'mass_edit'}
								<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="return mass_edit(this, 'massedit', '{$MODULE}', '{$CATEGORY}')">
									<div>{$button_label}</div>
								</button>
		                     {/if}
						{/foreach}
						
						<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="selectAllIds();">
							{if ($ALL_IDS eq 1)}
								<div id="select_all_button">{$APP.LBL_UNSELECT_ALL_IDS}</div>
								<input type="hidden" id="select_all_button_top" value="{$APP.LBL_UNSELECT_ALL_IDS}" />
							{else}
								<div id="select_all_button">{$APP.LBL_SELECT_ALL_IDS}</div>
								<input type="hidden" id="select_all_button_top" value="{$APP.LBL_SELECT_ALL_IDS}" />
							{/if}
						</button>
					{/if}
					
					{* vtlib customization: Custom link buttons on the List view basic buttons *}
					{if $CUSTOM_LINKS && $CUSTOM_LINKS.LISTVIEWBASIC}
						{foreach item=CUSTOMLINK from=$CUSTOM_LINKS.LISTVIEWBASIC}
							{assign var="customlink_href" value=$CUSTOMLINK->linkurl}
							{assign var="customlink_label" value=$CUSTOMLINK->linklabel}
							{if $customlink_label eq ''}
								{assign var="customlink_label" value=$customlink_href}
							{else}
								{* Pickup the translated label provided by the module *}
								{assign var="customlink_label" value=$customlink_label|@getTranslatedString:$CUSTOMLINK->module()}
							{/if}
							<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="{$customlink_href}">
								<div>{$customlink_label}</div>
							</button>
						{/foreach}
					{/if}
					
					{* vtlib customization: Custom link buttons on the List view *}
					{if $CUSTOM_LINKS && !empty($CUSTOM_LINKS.LISTVIEW)}
						{foreach item=CUSTOMLINK from=$CUSTOM_LINKS.LISTVIEW}
							{assign var="customlink_href" value=$CUSTOMLINK->linkurl}
							{assign var="customlink_label" value=$CUSTOMLINK->linklabel}
							{if $customlink_label eq ''}
								{assign var="customlink_label" value=$customlink_href}
							{else}
								{* Pickup the translated label provided by the module *}
								{assign var="customlink_label" value=$customlink_label|@getTranslatedString:$CUSTOMLINK->module()}
							{/if}
							<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="{$customlink_href}">
								<div>{$customlink_label}</div>
							</button>
						{/foreach}
					{/if}
					
					{* vtlib customization: Hook to enable import/export button for custom modules. Added CUSTOM_MODULE *}
					{if $MODULE eq 'Assets' || $MODULE eq 'ServiceContracts' || $MODULE eq 'Vendors' || $MODULE eq 'HelpDesk' || $MODULE eq 'Contacts' || $MODULE eq 'Leads' || $MODULE eq 'Accounts' || $MODULE eq 'Potentials' || $MODULE eq 'Products' || $MODULE eq 'Services' || $MODULE eq 'Calendar' || $CUSTOM_MODULE eq 'true'}
				   		{if $CHECK.Import eq 'yes' && $MODULE neq 'Calendar'}
							<button type="button" class="crmbutton with-icon edit crmbutton-turbolift" onclick="location.href='index.php?module={$MODULE}&action=Import&step=1&return_module={$MODULE}&return_action=index&parenttab={$CATEGORY}'">
								<i class="vteicon md-text">file_download</i>
								<span>{$APP.LBL_IMPORT}</span>
							</button>
						{elseif  $CHECK.Import eq 'yes' && $MODULE eq 'Calendar' && $REQUEST_ACTION eq 'ListView'}
							<button type="button" class="crmbutton with-icon edit crmbutton-turbolift" onclick="showFloatingDiv('CalImport', this);">
								<i class="vteicon md-text">file_download</i>
								<span>{$APP.LBL_IMPORT}</span>
							</button>
						{/if}
						{if $CHECK.Export eq 'yes' && $MODULE neq 'Calendar'}
							<button type="button" class="crmbutton with-icon edit crmbutton-turbolift" onclick="return selectedRecords('{$MODULE}','{$CATEGORY}')">
								<i class="vteicon md-text">file_upload</i>
								<span>{$APP.LBL_EXPORT}</span>
							</button>
						{elseif  $CHECK.Export eq 'yes' && $MODULE eq 'Calendar' && $REQUEST_ACTION eq 'ListView'}
							<button type="button" class="crmbutton with-icon edit crmbutton-turbolift" onclick="showFloatingDiv('CalExport', this);">
								<i class="vteicon md-text">file_upload</i>
								<span>{$APP.LBL_EXPORT}</span>
							</button>
						{/if}
					{elseif $MODULE eq 'Documents' && $CHECK.Export eq 'yes' && $REQUEST_ACTION eq 'ListView'}
						<button type="button" class="crmbutton with-icon edit crmbutton-turbolift" onclick="return selectedRecords('{$MODULE}','{$CATEGORY}')">
							<i class="vteicon md-text">file_upload</i>
							<span>{$APP.LBL_EXPORT}</span>
						</button>
					{/if}
					{if $MODULE eq 'Contacts' || $MODULE eq 'Leads' || $MODULE eq 'Accounts'|| $MODULE eq 'Products'|| $MODULE eq 'Potentials'|| $MODULE eq 'HelpDesk'|| $MODULE eq 'Vendors' || $MODULE eq 'Services' || $CUSTOM_MODULE eq 'true'} {* crmv@206281 *}
						{if $CHECK.DuplicatesHandling eq 'yes'}
							<button type="button" class="crmbutton with-icon edit crmbutton-turbolift" onclick="MergeFieldsAjax()">
								<i class="vteicon md-text">pageview</i>
								<span>{$APP.LBL_FIND_DUPLICATES}</span>
							</button>
						{/if}
					{/if}
				</div>
			</div>
		</li>
	{/if}
	
	{if $CAN_ADD_HOME_BLOCKS || $CAN_ADD_HOME_VIEWS || $CAN_DELETE_HOME_VIEWS || $CAN_TOGGLE_EDITMODE || $CAN_EDIT_COUNTS} {* crmv@160778 *} {* crmv@173746 *}
		{assign var="CAN_EDIT_HOMEVIEW" value="yes"}
	{else}
		{assign var="CAN_EDIT_HOMEVIEW" value="no"}
	{/if}
	{if $CHECK.moduleSettings eq 'yes' && ($IS_ADMIN == 1 || $CAN_EDIT_HOMEVIEW == 'yes') && !$DISABLE_CAL_CONTESTUAL_BUTTON} {* crmv@160778 *}
       	<li class="dropdown" id="moduleSettingsTd">
			{if $IS_ADMIN eq 1}
				<button type="button" class="crmbutton only-icon save crmbutton-nav" data-toggle="dropdown">
					<i class="vteicon" data-toggle="tooltip" data-placement="bottom" title="{"LBL_CONFIGURATION"|getTranslatedString:"Settings"}">settings_applications</i>
				</button>
			{else}
				<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="ModuleHome.toggleEditMode()">
					<i class="vteicon" data-toggle="tooltip" data-placement="bottom" title="{$APP.LBL_CONFIG_PAGE}">settings_applications</i>
				</button>
			{/if}
			<ul class="dropdown-menu">
				<li><a href="javascript:;" onclick="ModuleHome.toggleEditMode()">{$APP.LBL_CONFIG_PAGE}</a></li>
				{if $IS_ADMIN eq 1}
					<li><a href="index.php?module=Settings&amp;action=ModuleManager&amp;module_settings=true&amp;formodule={$MODULE}&amp;parenttab=Settings">{$APP.LBL_ADVANCED}</a></li>
				{/if}
			</ul>
       	</li>
       	<li id="moduleSettingsResetTd" style="display:none">
			<button type="button" class="crmbutton with-icon save crmbutton-nav" href="javascript:;" onclick="ModuleHome.toggleEditMode()">
				<i class="vteicon">settings_applications</i>
				{$APP.LBL_DONE_BUTTON_TITLE}
			</button>
		</li>
	{/if}
</ul>