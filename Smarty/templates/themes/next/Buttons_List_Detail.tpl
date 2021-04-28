{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}
{if !$FETCH_ONLY_NAVBAR}  {* crmv@171524 *}
<div id="vtbusy_info" class="linearLoadingIndicator" style="display:none;">
	{include file="LoadingIndicator.tpl" LINEAR=true}
</div>

<ul id="Buttons_Detail" class="vteUlTable buttonsList buttonsListFixed" data-minified="{$MENU_TOGGLE_STATE}">
{/if}  {* crmv@171524 *}
	<li>
		{include file="Buttons_List_Contestual.tpl"}
	</li>
	{if $MODULE neq 'Users'}
		<li>
			<a id="backToList" class="crmbutton only-icon save" href="index.php?action=index&module={$MODULE}&parenttab={$CATEGORY}">
				<i data-toggle="tooltip" data-placement="bottom" title="{$APP.LBL_BACK_TO_LIST}" class="vteicon md-link">arrow_back</i>
			</a>
		</li>
	{/if}
	<li>
		{* Module Record numbering, used MOD_SEQ_ID instead of ID *}
		{assign var="USE_ID_VALUE" value=$MOD_SEQ_ID}
		{if $USE_ID_VALUE eq ''} {assign var="USE_ID_VALUE" value=$ID} {/if}
		{* crmv@199229 *}
		<span class="dvHeaderText {if $LAYOUT_CONFIG['record_title_inline'] eq 1}dvHeaderTextInline{else}dvHeaderTextMultiLine{/if}" data-record-inline="{$LAYOUT_CONFIG['record_title_inline']}">
			<div class="recordTitleName">
				<span class="recordTitle1">{$SINGLE_MOD|@getTranslatedString:$MODULE}</span>
				{if $SHOW_RECORD_NUMBER eq true}
					[ {$USE_ID_VALUE} ]
				{/if}
				{* crmv@171524 *}
				<span class="recordName" title="{$NAME}">{$NAME} &nbsp;</span>
			</div>
			<span class="updateInfo">
				{if $IS_FREEZED}
					{'LBL_IS_FREEZED'|getTranslatedString}
				{else}
					{if $LAYOUT_CONFIG['hide_update_info'] eq 0}
						{$UPDATEINFO}
					{/if}
				{/if}
			</span>
			{* crmv@171524e *}
		</span>
		<span class="dvHeaderTextMin">
			<i class="vteicon" title="{$NAME}">chat</i>
		</span>
		{* crmv@199229e *}
		{* crmv@25620 *}
		<script type="text/javascript">
			updateBrowserTitle('{$SINGLE_MOD|@getTranslatedString:$MODULE} - {$NAME} [{$USE_ID_VALUE}]');
		</script>
		{* crmv@25620e *}
	</li>
	<li class="pull-right" style="padding-right:10px">
		<ul class="vteUlTable dvHeaderRight">
			{if $EDIT_PERMISSION eq 'yes'}
				<li style="margin-right:10px">
					{if $MODULE eq 'Users'}
						{$EDIT_BUTTON}
					{else}
						<button type="button" class="crmbutton with-icon success crmbutton-nav" onclick="DetailView.return_module.value='{$MODULE}'; DetailView.return_action.value='DetailView'; DetailView.return_id.value='{$ID}';DetailView.module.value='{$MODULE}'; submitFormForAction('DetailView','EditView');">
							<i class="vteicon">create</i>
							{$APP.LBL_EDIT_BUTTON_LABEL}
						</button>
					{/if}
				</li>
			{/if}
			{if $SHOW_DETAIL_TRACKER}
				<li>
					{include file="modules/SDK/src/CalendarTracking/DetailTracking.tpl"}
				</li>
			{/if}
			{if $SHOW_TURBOLIFT_CAL_BUTTONS}
				<li>
					<button type="button" class="crmbutton only-icon info crmbutton-nav" onClick="LPOP.openEventCreate('{$MODULE}', {$ID}, 'Events');">		
						<i data-toggle="tooltip" data-placement="bottom" class="vteicon md-link" title="{'LBL_ADD'|@getTranslatedString:'Calendar'}">event</i>
					</button>
				</li>
				<li>
					<button type="button" class="crmbutton only-icon info crmbutton-nav" onClick="LPOP.openEventCreate('{$MODULE}', {$ID}, 'Task');">
						<i data-toggle="tooltip" data-placement="bottom" class="vteicon md-link" title="{'Task'|getNewModuleLabel}" >assignment_turned_in</i>	{* crmv@59091 *}
					</button>
				</li>
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
					<li>
						<button type="button" class="crmbutton only-icon warning crmbutton-nav" onclick="sendmail('{$MODULE}',{$ID});">
							<i data-toggle="tooltip" data-placement="bottom" class="vteicon md-link" title="{'LBL_LINK_NEW_MAIL'|getTranslatedString:'Messages'}">email</i>
						</button>
					</li>
				{/if}
			{/if}
			{if $MODULE neq 'Users' && $MODULE neq 'Webforms'}
				<li>
					<button type="button" class="crmbutton only-icon warning crmbutton-nav" onclick="VTE.DetailView.setFavorite({$ID});"> {* crmv@171524 *}
						<i data-toggle="tooltip" data-placement="bottom" id="favoriteImg" class="vteicon md-link" title="{$APP.LBL_FAVORITE}">{$ID|getFavoriteCls}</i>
					</button>
				</li>
				{* crmv@164120 crmv@164122 *}
				<li>
					<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="ModNotificationsCommon.follow({$ID});">
						{assign var=FOLLOWIMG value=$ID|@getFollowImg}
						{if preg_match('/_on/', $FOLLOWIMG)}
							{assign var=FOLLOWTITLE value='LBL_UNFOLLOW'|getTranslatedString:'ModNotifications'}
						{else}
							{assign var=FOLLOWTITLE value='LBL_FOLLOW'|getTranslatedString:'ModNotifications'}
						{/if}
						<i data-toggle="tooltip" data-placement="bottom" id="followImg" class="vteicon md-link" title="{$FOLLOWTITLE}">{$ID|getFollowCls}</i>
					</button>
				</li>
			{/if}
			{if $SHOW_TURBOLIFT_LINK_BUTTON}
				<li>
					<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="LPOP.openPopup('{$MODULE}', '{$ID}', '{$MODE}');">
						<i data-toggle="tooltip" data-placement="bottom" class="vteicon md-link" title="{'LBL_LINK_ACTION'|@getTranslatedString:'Messages'}">link</i>
					</button>
				</li>
			{/if}
			<li>
				{if $MODULE eq 'Webforms'}
					{* do nothing *}
				{else}
					<div class="dropdown detailview-dropdown">
						<button type="button" class="crmbutton only-icon save crmbutton-nav dropdown-toggle" data-toggle="dropdown" onclick="jQuery('.loadDetailViewWidget').click();">
							<i data-toggle="tooltip" data-placement="bottom" class="vteicon md-link" title="{'LBL_OTHER'|getTranslatedString:'Users'}">reorder</i>
						</button>
						<div id="detailViewActionsContainer" class="dropdown-menu dropdown-menu-right detailview-menu-dropdown crmvDiv">
							{include file="DetailViewActions.tpl"}
						</div>
					</div>
				{/if}
			</li>
			{* crmv@26986 *}
			{if $MODULE eq 'Webforms'}
				<li>
					<button id="edit_form" name="edit_form" class="crmbutton small edit" onclick="Webforms.editForm({$WEBFORMMODEL->getId()})">{'LBL_EDIT_BUTTON_LABEL'|@getTranslatedString:$MODULE}</button>
					<button id="show_html" name="show_html" class="crmbutton small create" onclick="Webforms.getHTMLSource({$WEBFORMMODEL->getId()})">{'LBL_SOURCE'|@getTranslatedString:$MODULE}</button>
					<button id="delete_form" name="delete_form" class="crmbutton small delete" onclick="return Webforms.deleteForm('action_form',{$WEBFORMMODEL->getId()})">{'LBL_DELETE_BUTTON_LABEL'|@getTranslatedString:$MODULE}</button>
				</li>
			{/if}
			{* crmv@29386e *}
		</ul>
	</li>
{if !$FETCH_ONLY_NAVBAR} {* crmv@171524 *}
</ul>

<div id="Buttons_Detail_Placeholder"></div>

{literal}
<script type="text/javascript">
	var navbarHeight = jQuery('#Buttons_Detail').height();
	jQuery('#Buttons_Detail_Placeholder').height(navbarHeight);
</script>
{/literal}
{/if} {* crmv@171524 *}
{* crmv@171524 removed script set_favorite... *}