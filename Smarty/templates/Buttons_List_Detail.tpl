{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@18592 crmv@82419 *}
<div id="Buttons_List_3_Container" style="display:none;">
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
	{* crmv@27061 *}
	{if $MODULE eq 'Calendar'}
		<td style="padding:5px" nowrap>
			<button class="crmbutton small edit" onclick="listToCalendar('Today')">{'LBL_DAY'|@getTranslatedString:$MODULE}</button>
			<button class="crmbutton small edit" onclick="listToCalendar('This Week')">{'LBL_WEEK'|@getTranslatedString:$MODULE}</button>
			<button class="crmbutton small edit" onclick="listToCalendar('This Month')">{'LBL_MONTH'|@getTranslatedString:$MODULE}</button>
			<button class="crmbutton small edit" onclick="location.href = 'index.php?action=ListView&module=Calendar&parenttab={$CATEGORY}'">{'LBL_CAL_TO_FILTER'|@getTranslatedString:$MODULE}</button>
		</td>
	{/if}
	{* crmv@27061e *}
	{* crmv@30976 *}
	{if $FOLDERID > 0}
	<td style="padding:5px">
		<a href="index.php?module={$MODULE}&action=ListView&folderid={$FOLDERID}"><img src="{'folderback.png'|resourcever}" alt="{$APP.LBL_GO_BACK}" title="{$APP.LBL_GO_BACK}" align="absbottom" border="0" /></a>
	</td>
	{/if}
	{* crmv@30976e *}
	<td width="40%" style="padding:5px">
  		{* Module Record numbering, used MOD_SEQ_ID instead of ID *}
  		{assign var="USE_ID_VALUE" value=$MOD_SEQ_ID}
  		{if $USE_ID_VALUE eq ''} {assign var="USE_ID_VALUE" value=$ID} {/if}
 		<span class="dvHeaderText">
			<span class="recordTitle1">{$SINGLE_MOD|@getTranslatedString:$MODULE}</span>
			{if $SHOW_RECORD_NUMBER eq true}
				[ {$USE_ID_VALUE} ]
			{/if}
			{* crmv@171524 *}
			{$NAME}
			&nbsp;
			<span class="updateInfo" style="font-weight:normal;">
				{if $IS_FREEZED}
					{'LBL_IS_FREEZED'|getTranslatedString}
				{else}
					{$UPDATEINFO}
				{/if}
			</span>
			{* crmv@171524e *}
		</span>
		{* crmv@25620 *}
 		<script type="text/javascript">
			updateBrowserTitle('{$SINGLE_MOD|@getTranslatedString:$MODULE} - {$NAME} [{$USE_ID_VALUE}]');
		</script>
		{* crmv@25620e *}
 	</td>
 	<td width="60%" style="padding:5px" align="right" nowrap>
 	
		<div style="float:right;padding-left:6px;">
			{if $PERFORMANCE_CONFIG.DETAILVIEW_RECORD_NAVIGATION}
		 		{if $INVENTORY_VIEW eq 'true'}
					{if $privrecord neq ''}
						<img align="top" title="{$APP.LNK_LIST_PREVIOUS}" accessKey="{$APP.LNK_LIST_PREVIOUS}" onclick="location.href='index.php?module={$MODULE}&viewtype={$VIEWTYPE}&action=DetailView&record={$privrecord}&parenttab={$CATEGORY}'" name="privrecord" value="{$APP.LNK_LIST_PREVIOUS}" src="{'rec_prev.png'|resourcever}">
					{else}
						<img align="top" title="{$APP.LNK_LIST_PREVIOUS}" src="{'rec_prev_disabled.png'|resourcever}">
					{/if}
					{if $privrecord neq '' || $nextrecord neq ''}
						<img align="top" title="{$APP.LBL_JUMP_BTN}" accessKey="{$APP.LBL_JUMP_BTN}" onclick="var obj = this;var lhref = getListOfRecords(obj, '{$MODULE}',{$ID},'{$CATEGORY}');" name="jumpBtnIdTop" id="jumpBtnIdTop" src="{'rec_jump.png'|resourcever}" height="24">
					{/if}
					{if $nextrecord neq ''}
						<img align="top" title="{$APP.LNK_LIST_NEXT}" accessKey="{$APP.LNK_LIST_NEXT}" onclick="location.href='index.php?module={$MODULE}&viewtype={$VIEWTYPE}&action=DetailView&record={$nextrecord}&parenttab={$CATEGORY}'" name="nextrecord" src="{'rec_next.png'|resourcever}">
					{else}
						<img align="top" title="{$APP.LNK_LIST_NEXT}" src="{'rec_next_disabled.png'|resourcever}">
					{/if}
		 		{else}
					{if $privrecord neq ''}
						<img align="top" title="{$APP.LNK_LIST_PREVIOUS}" accessKey="{$APP.LNK_LIST_PREVIOUS}" onclick="location.href='index.php?module={$MODULE}&viewtype={$VIEWTYPE}&action=DetailView&record={$privrecord}&parenttab={$CATEGORY}&start={$privrecordstart}'" name="privrecord" value="{$APP.LNK_LIST_PREVIOUS}" src="{'rec_prev.png'|resourcever}">
					{else}
						<img align="top" title="{$APP.LNK_LIST_PREVIOUS}" src="{'rec_prev_disabled.png'|resourcever}">
					{/if}
					{if $privrecord neq '' || $nextrecord neq ''}
						<img align="top" title="{$APP.LBL_JUMP_BTN}" accessKey="{$APP.LBL_JUMP_BTN}" onclick="var obj = this;var lhref = getListOfRecords(obj, '{$MODULE}',{$ID},'{$CATEGORY}');" name="jumpBtnIdTop" id="jumpBtnIdTop" src="{'rec_jump.png'|resourcever}" height="24">
					{/if}
					{if $nextrecord neq ''}
						<img align="top" title="{$APP.LNK_LIST_NEXT}" accessKey="{$APP.LNK_LIST_NEXT}" onclick="location.href='index.php?module={$MODULE}&viewtype={$VIEWTYPE}&action=DetailView&record={$nextrecord}&parenttab={$CATEGORY}&start={$nextrecordstart}'" name="nextrecord" src="{'rec_next.png'|resourcever}">
					{else}
						<img align="top" title="{$APP.LNK_LIST_NEXT}" src="{'rec_next_disabled.png'|resourcever}">
					{/if}
				{/if}
			{/if}
		</div>
		
		{if $MODULE eq 'Webforms'}
			{* do nothing *}
		{else}
			<div class="pull-right" onClick="showOverAll(this,'detailViewActionsContainer');jQuery('.loadDetailViewWidget').click();">
				<button type="button" class="small crmbutton save">
					{'LBL_OTHER'|getTranslatedString:'Users'}&nbsp;<span class="caret"></span>
				</button>
			</div>
			<div id="detailViewActionsContainer" style="display:none; position:fixed; width:20%;">
				<div class="crmvDiv" style="max-height:500px; overflow-y:auto; padding:0px 5px 5px 5px;">
					{include file="DetailViewActions.tpl"}
				</div>
			</div>
		{/if}
		
		<div style="float:right;">
		
			{if $EDIT_PERMISSION eq 'yes'}	{* crmv@131239 *}
				{* crmv@20054 *}
		 		{if $MODULE eq 'Users'}
		 			{$EDIT_BUTTON}
		 		{else}
					<button title="{$APP.LBL_EDIT_BUTTON_TITLE}" accessKey="{$APP.LBL_EDIT_BUTTON_KEY}" class="crmbutton small edit" onclick="DetailView.return_module.value='{$MODULE}'; DetailView.return_action.value='DetailView'; DetailView.return_id.value='{$ID}';DetailView.module.value='{$MODULE}'; submitFormForAction('DetailView','EditView');" name="Edit">{$APP.LBL_EDIT_BUTTON_LABEL}</button>
				{/if}
		 		{* crmv@20054e *}
			{/if}
	
			{* crmv@26986e *}
			{if $MODULE eq 'Webforms'}
				<button id="edit_form" name="edit_form" class="crmbutton small edit" onclick="Webforms.editForm({$WEBFORMMODEL->getId()})">{'LBL_EDIT_BUTTON_LABEL'|@getTranslatedString:$MODULE}</button>
				<button id="show_html" name="show_html" class="crmbutton small create" onclick="Webforms.getHTMLSource({$WEBFORMMODEL->getId()})">{'LBL_SOURCE'|@getTranslatedString:$MODULE}</button>
				<button id="delete_form" name="delete_form" class="crmbutton small delete" onclick="return Webforms.deleteForm('action_form',{$WEBFORMMODEL->getId()})">{'LBL_DELETE_BUTTON_LABEL'|@getTranslatedString:$MODULE}</button>
			{/if}
			{* crmv@29386e *}
			
			{if $SHOW_TURBOLIFT_LINK_BUTTON}
				<button title="{'LBL_LINK_ACTION'|@getTranslatedString:'Messages'}" class="crmbutton small edit" onclick="LPOP.openPopup('{$MODULE}', '{$ID}', '{$MODE}');">{'LBL_LINK_ACTION'|@getTranslatedString:'Messages'}</button>
			{/if}
		</div>
		
		<div style="float:right;" class="btn-group detail-view-topbar-group">
			{* crmv@26986 crmv@29386 crmv@29617 *}
			{if $MODULE neq 'Users' && $MODULE neq 'Webforms'}
				<div class="btn btn-default">
					<i id="favoriteImg" class="vteicon" title="{$APP.LBL_FAVORITE}" onClick="VTE.DetailView.setFavorite({$ID});">{$ID|getFavoriteCls}</i> {* crmv@171524 *}
				</div>
				{* crmv@164120 crmv@164122 *}
				<div class="btn btn-default">
					{* crmv@83305 *}
					{assign var=FOLLOWIMG value=$ID|@getFollowImg}
					{if preg_match('/_on/', $FOLLOWIMG)}
						{assign var=FOLLOWTITLE value='LBL_UNFOLLOW'|getTranslatedString:'ModNotifications'}
					{else}
						{assign var=FOLLOWTITLE value='LBL_FOLLOW'|getTranslatedString:'ModNotifications'}
					{/if}
					<i id="followImg" class="vteicon" title="{$FOLLOWTITLE}" onClick="ModNotificationsCommon.follow({$ID});">{$ID|getFollowCls}</i>
					{* crmv@83305e *}
				</div>
			{/if}
			{* crmv@26986e crmv@29386e crmv@29617e *}
		</div>
		
		{* crmv@62394 *}
		<div style="float:right;" class="btn-group detail-view-topbar-group">
			{if $SHOW_DETAIL_TRACKER}
				{include file="modules/SDK/src/CalendarTracking/DetailTracking.tpl"}
			{/if}
		</div>
		{* crmv@62394e *}
		
		<div style="float:right;" class="btn-group detail-view-topbar-group">
			<div class="track-label">
				<span id="vtbusy_info" style="display:none; float:right; padding-top:3px; padding-right:5px;">{include file="LoadingIndicator.tpl"}</span>
			</div>
		</div>
	</td>
 </tr>
 </table>
</div>
{* crmv@171524 *}
{if !$IS_FETCHING}
<script>calculateButtonsList3();</script>
{/if}
{* crmv@171524e *}
{*<!-- crmv@18592e -->*}
{* crmv@171524 removed script set_favorite *}