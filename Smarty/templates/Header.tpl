{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@16265 crmv@18549 crmv@18592 crmv@24822 crmv@21996 crmv@22622 crmv@30356 crmv@7220 crmv@44723 crmv@54707 crmv@82831 crmv@169305 *}

{include file="HTMLHeader.tpl" head_include="all"}

<body class="small">
<a name="top"></a>

{* crmv@82419 used to insert some extra code in the body *}
{include file="Theme.tpl" THEME_MODE="body"}
{* crmv@82419e *}

{SDK::checkJsLanguage()}	{* crmv@sdk-18430 *} {* crmv@181170 *}
{include file='CachedValues.tpl'}	{* crmv@26316 *}
{include file='modules/SDK/src/Reference/Autocomplete.tpl'}	{* crmv@29190 *}

<div id="login_overlay" style="display:none;" class="login_overlay" ></div> {* crmv@91082 *}
 
{* crmv@82419 *}
{if $HIDE_MENUS neq true}	{* crmv@62447 *}
	<div id="vte_menu" class="navbar navbar-default" {if isMobile() neq true}style="position:fixed;"{/if}>	{* crmv@30356 *}
	    
	    {* crmv@23715 crmv@75301 *}
	    {if $smarty.session.menubar neq 'no' && $MENU_TPL}
			{include file=$MENU_TPL}
		{/if}
		{* crmv@23715e crmv@75301e *}
		
		<div class="drop_mnu" id="Preferences_sub" style="width:150px;">
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
				<tr><td><a class="drop_down" href="index.php?module=Users&action=DetailView&record={$CURRENT_USER_ID}&modechk=prefview">{$APP.LBL_PREFERENCES}</a></td></tr>
				<tr><td><a class="drop_down" href="index.php?module=Users&action=Logout">{$APP.LBL_LOGOUT}</a></td></tr>
				{if $HEADERLINKS}
					{foreach item=HEADERLINK from=$HEADERLINKS}
						{assign var="headerlink_href" value=$HEADERLINK->linkurl}
						{assign var="headerlink_label" value=$HEADERLINK->linklabel}
						{if $headerlink_label eq ''}
							{assign var="headerlink_label" value=$headerlink_href}
						{else}
							{assign var="headerlink_label" value=$headerlink_label|@getTranslatedString:$HEADERLINK->module()}
						{/if}
						<tr><td><a href="{$headerlink_href}" class="drop_down">{$headerlink_label}</a></td></tr>
					{/foreach}
				{/if}
			</table>
		</div>

		{* crmv@75301 *}
		{if $HEADER_OVERRIDE.post_menu_bar}
			{$HEADER_OVERRIDE.post_menu_bar}
		{/if}
		{* crmv@75301e *}
		
		<!-- Level 3 tabs starts -->
		{* crmv@22622 crmv@29079 crmv@37362 *}
		{if $smarty.cookies.crmvWinMaxStatus eq ''}
			{setWinMaxStatus()} {* crmv@181170 *}
			<script>setCookie('crmvWinMaxStatus','{$smarty.cookies.crmvWinMaxStatus}');</script>
		{/if}
		{if $smarty.cookies.crmvWinMaxStatus eq 'close'}
			{assign var="minImg" value="_min"}
			{assign var="minIcon" value=""}
		{else}
			{assign var="minImg" value=""}
			{assign var="minIcon" value="md-lg"}
		{/if}
		{if $smarty.cookies.crmvWinMaxStatus eq 'close'}
			{assign var="orangeTableHeight" value="38"}
		{else}
			{assign var="orangeTableHeight" value="57"}
		{/if}
		<div id="orange" class="winMaxAnimate">{* crmv@21996 crmv@30356 crmv@98866 *}
		<table id="orangeTable" border=0 cellspacing=0 cellpadding=0 class="level2Bg" width="100%">
		<tr>
			<td>
				<table border=0 cellspacing=0 cellpadding=0 width="100%" height="{$orangeTableHeight}px">
					<tr>
						<td><div id="Buttons_List_SiteMap" class="winMaxWait"></div></td>
						<td><div id="Buttons_List_Fixed"></div></td>
						<td>
							<div id="Buttons_List_QuickCreate" style="display: none">
								<a href="javascript:;">
									<i data-toggle="tooltip" data-placement="top" class="vteicon {$minIcon}" title="{$APP.LBL_QUICK_CREATE}" onclick="showFloatingDiv('Create_sub', this);">flash_on</i>
								</a>	{* crmv@31197 *} {* crmv@82419 *}
							</div>
						</td>
						<td><div id="Buttons_List_Contestual" style="display:none;" {if $smarty.cookies.crmvWinMaxStatus eq 'close'}class="ButtonsListContestualSmall"{else}class="ButtonsListContestualLarge"{/if}></div></td>	{* crmv@2963m *}
						<td width="100%" align="right">
							{* crmv@82419 *}
							<div class="globalSearch">
								<form name="UnifiedSearchNew" onSubmit="UnifiedSearchAreasObj.show(document.getElementById('orange'),'search');return false;">
								<input type="text" id="unifiedsearchnew_query_string" name="query_string" value="{$QUERY_STRING}" class="form-control searchBox" onFocus="this.value='';" onBlur="if(this.value=='')this.value='{"LBL_GLOBAL_SEARCH_STRING"|getTranslatedString}';">	{* crmv@31197 *}
								<span class="searchIcon">
									<i class="vteicon" onClick="UnifiedSearchAreasObj.show(document.getElementById('orange'),'search');">search</i>
								<span>
								</form>
							</div>
							{* crmv@82419e *}
							<div style="float:right;padding:4px 8px 0px 0px;">
								<div id="status" style="display:none;">{include file="LoadingIndicator.tpl"}</div>
							</div>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</table>
		</div>{* crmv@21996 *}
		{* crmv@22622e crmv@29079e crmv@37362e *}
		<!-- Level 3 tabs ends -->

		{* crmv@75301 *}
		{if $HEADER_OVERRIDE.post_primary_bar}
			{$HEADER_OVERRIDE.post_primary_bar}
		{/if}
		{* crmv@75301e *}
		
		<!-- Level 4 tabs starts -->
		<div id="Buttons_List_3" class="level4Bg" style="display:none;"></div>
		<!-- Level 4 tabs ends -->

		{* crmv@75301 *}
		{if $HEADER_OVERRIDE.post_secondary_bar}
			{$HEADER_OVERRIDE.post_secondary_bar}
		{/if}
		{* crmv@75301e *}
	</div>
	
	{if isMobile() neq true}
		<div id="vte_menu_white" class="winMaxAnimate"></div>
		<script>
			{literal}
			// crmv@120023
			jQuery('#vte_menu_white').height(parseInt(jQuery('#vte_menu').height()));
			jQuery(window).resize(function () { 
				jQuery('#vte_menu_white').height(parseInt(jQuery('#vte_menu').height()));
			});
			// crmv@120023e
			{/literal}
		</script>
	{/if}
{else}
	{if $smarty.request.useical eq 'true'}
		{assign var="PAGE_TITLE" value='LBL_PREVIEW_INVITATION'|@getTranslatedString:$MODULE} 
		{assign var="CAL_MODE" value='on'}
		{assign var="OP_MODE" value='calendar_preview_buttons'}
		{include file='SmallHeader.tpl'}
	{else}
		{if isset($smarty.request.page_title)}
			{assign var="PAGE_TITLE" value=$smarty.request.page_title|@getTranslatedString:$MODULE}
			{assign var="OP_MODE" value=$smarty.request.op_mode}
		{* crmv@68357 *}
		{else}
			{if $smarty.request.activity_mode eq 'Events'}
				{assign var="PAGE_TITLE" value='LBL_ADD'|@getTranslatedString:$MODULE}
			{else}
				{assign var="PAGE_TITLE" value='LBL_ADD_TODO'|@getTranslatedString:$MODULE}
			{/if}
			{assign var="CAL_MODE" value='on'}
			{assign var="OP_MODE" value='calendar_buttons'}
		{/if}
		{* crmv@68357e *}
		{include file='SmallHeader.tpl'}
		{include file='Buttons_List4.tpl'}
		<div id="Buttons_List_3" class="level4Bg" style="display:none;"></div>
	{/if}
{/if}
{* crmv@62447e *}
{* crmv@82419e *}

{include file='modules/Area/Menu.tpl'}	{* crmv@113771 *}

{* crmv@180714 - removed code *}
	
{if $MODULE_NAME eq 'Calendar'}
	{* Calendar export floating div *}
	{assign var="FLOAT_TITLE" value=$APP.LBL_EXPORT}
	{assign var="FLOAT_WIDTH" value="300px"}
	{capture assign="FLOAT_CONTENT"}
		<table border=0 celspacing=0 cellpadding=5 width="100%" align="center">
			<tr>
				<td align="right" nowrap class="cellLabel small"><b>{'LBL_FILENAME'|@getTranslatedString} </b></td>
				<td align="left">
					<div class="dvtCellInfo">
						<input class="detailedViewTextBox" type='text' name='ics_filename' id='ics_filename' size='25' value='vte.calendar'>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<input type="button" onclick="return exportCalendar();" value="Export" class="crmbutton small edit" name="button">
				</td>
			</tr>
		</table>
	{/capture}
	{include file="FloatingDiv.tpl" FLOAT_ID="CalExport" FLOAT_BUTTONS=""}
	
	{* Calendar import floating div *}
	{assign var="FLOAT_TITLE" value=$APP.LBL_IMPORT}
	{assign var="FLOAT_WIDTH" value="300px"}
	{capture assign="FLOAT_CONTENT"}
		<form name='ical_import' id='ical_import' onsubmit="VteJS_DialogBox.block();" enctype="multipart/form-data" action="index.php" method="POST">
			<input type='hidden' name='module' value=''>
			<input type='hidden' name='action' value=''>
			<table border="0" celspacing="0" cellpadding="5" width="100%" align="center">
				<tr>
					<td align="right" nowrap class="cellLabel small"><b>{'LBL_FILENAME'|@getTranslatedString} </b></td>
					<td align="left">
						<input class="small" type='file' name='ics_file' id='ics_file'/>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center">
						<input type="button" onclick="return importCalendar();" value="Import" class="crmbutton small save" name="button"/>
					</td>
				</tr>
			</table>
		</form>
	{/capture}
	{include file="FloatingDiv.tpl" FLOAT_ID="CalImport" FLOAT_BUTTONS=""}
{/if}

<div id="calculator_cont" style="position:fixed;"></div>

{include file="Clock.tpl"}

<div id="qcform" class="qcform" style="display:none;"></div>

<!-- Unified Search module selection feature -->
<div id="UnifiedSearch_moduleformwrapper" class="crmvDiv" style="position:fixed;z-index:100002;display:none;"></div>

{* crmv@21048m crmv@82419 - Container for Popup *}
<div id="popupContainer" style="display:none;"></div>

{* crmv@31197 *}
{* QuickCreate floating div *}
{assign var="FLOAT_TITLE" value=$APP.LBL_QUICK_CREATE}
{assign var="FLOAT_WIDTH" value="300px"}
{assign var="FLOAT_BUTTONS" value=""}
{capture assign="FLOAT_CONTENT"}
<table cellspacing="0" cellpadding="5" border="0" width="100%">
	{assign var="count" value=0}
	{foreach  item=detail from=$QCMODULE}
		{if $count is div by 2}
			{assign var="count_tmp" value=1}
			<tr>
		{/if}
			<td><a href="javascript:;" onclick="NewQCreate('{$detail.1}');"><img src="{$detail.2}" border="0" align="absmiddle" />&nbsp;{$detail.0}</a></td>	{* crmv@31197 *}
		{if $count_tmp is div by 2}
			</tr>
		{/if}
		{assign var="count" value=$count+1}
		{assign var="count_tmp" value=1}
	{/foreach}
</table>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="Create_sub"}
{* crmv@31197e *}

{* Recents floating div *}
{assign var="FLOAT_TITLE" value=$APP.LBL_LAST_VIEWED}
{assign var="FLOAT_WIDTH" value="300px"}
{assign var="FLOAT_BUTTONS" value=""}
{capture assign="FLOAT_CONTENT"}
<table border="0" cellpadding="5" cellspacing="0" width="100%" class="hdrNameBg" id="lastviewed_list"></table>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="Tracker"}

{* crmv@26986 *}
{* Favourites floating div *}
{assign var="FLOAT_TITLE" value=$APP.LBL_FAVORITES}
{assign var="FLOAT_WIDTH" value="300px"}
{capture assign="FLOAT_BUTTONS"}
<input id="favorites_button" type="button" value="{$APP.LBL_ALL}" name="button" class="crmbutton small edit" title="{$APP.LBL_ALL}" onClick="get_more_favorites();">
{/capture}
{capture assign="FLOAT_CONTENT"}
<table border="0" cellpadding="5" cellspacing="0" width="100%" class="hdrNameBg" id="favorites_list"></table>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="favorites"}
{* crmv@26986e *}

{include file="modules/SDK/src/Todos/TodoContainer.tpl"} {* crmv@36871 *}
{include file="modules/SDK/src/Events/EventContainer.tpl"} {* crmv@3078m *}

{* crmv@29079	crmv@31301 *}
{* ModComments floating div *}
{assign var="DEFAULT_TEXT" value='LBL_ADD_COMMENT'|getTranslatedString:'ModComments'}
{assign var="DEFAULT_REPLY_TEXT" value='LBL_DEFAULT_REPLY_TEXT'|getTranslatedString:'ModComments'}
<script id="default_labels" type="text/javascript">
	var default_text = '{$DEFAULT_TEXT}';
	var default_reply_text = '{$DEFAULT_REPLY_TEXT}';
</script>
{assign var="FLOAT_TITLE" value='LBL_MODCOMMENTS_COMMUNICATIONS'|getTranslatedString:'ModComments'}
{assign var="FLOAT_WIDTH" value="840px"} {* crmv@80503 *}
{if isMobile() eq true}
	{assign var="FLOAT_HEIGHT" value="500px"}
{else}
	{assign var="FLOAT_HEIGHT" value=""}
{/if}
{capture assign="FLOAT_BUTTONS"}
	</td>
	<td width="30%" align="right">
		{* crmv@82419 *}
		<div class="form-group basicSearch">
			<input id="modcomments_search_text" class="form-control searchBox" type="text" value="{$APP.LBL_SEARCH_TITLE}{'ModComments'|getTranslatedString:'ModComments'}" onclick="clearTextModComments(this,'modcomments_search')" onblur="restoreDefaultTextModComments(this, '{$APP.LBL_SEARCH_TITLE}{'ModComments'|getTranslatedString:'ModComments'}','modcomments_search')" name="search_text" onkeypress="launchModCommentsSearch(event,'modcomments_search');">
			<span class="cancelIcon">
				<i class="vteicon md-sm md-link" id="modcomments_search_icn_canc" onclick="cancelSearchTextModComments('{$APP.LBL_SEARCH_TITLE}{'ModComments'|getTranslatedString:'ModComments'}','modcomments_search','ModCommentsNews_iframe','indicatorModCommentsNews')" title="Reset" style="display:none">cancel</i>&nbsp;
			</span>
			<span class="searchIcon">
				<i class="vteicon md-link" id="modcomments_search_icn_go" onclick="loadModCommentsNews(eval(jQuery('#ModCommentsNews_iframe').contents().find('#max_number_of_news').val()),'','',jQuery('#modcomments_search_text').val());" title="{$APP.LBL_FIND}">search</i>
			</span>
		</div>
		{* crmv@82419e *}
{/capture}
{capture assign="FLOAT_CONTENT"}
	{* crmv@30356 crmv@80503 *}
	<iframe id="ModCommentsNews_iframe" name="ModCommentsNews_iframe" width="100%" height="500px" frameborder="0" src="" {if isMobile() neq true}scrolling="auto"{/if}></iframe>
	{* crmv@30356e crmv@80503e *}
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="ModCommentsNews"}
{* crmv@29079e	crmv@31301e *}

{* crmv@29617 *}
{* Notifications floating div *}
{assign var="FLOAT_TITLE" value='ModNotifications'|getTranslatedString:'ModNotifications'}
{assign var="FLOAT_WIDTH" value="700px"}
{assign var="FLOAT_HEIGHT" value="500px"}
{capture assign="FLOAT_BUTTONS"}
<input type="button" class="crmbutton small edit" value="{'LBL_SET_ALL_AS_READ'|getTranslatedString:'ModNotifications'}" onclick="ModNotificationsCommon.markAllAsRead()" title="{'LBL_SET_ALL_AS_READ'|getTranslatedString:'ModNotifications'}" /> {* crmv@43194 *}
{/capture}
{assign var="FLOAT_CONTENT" value=""}
{include file="FloatingDiv.tpl" FLOAT_ID="ModNotifications" FLOAT_MAX_WIDTH="700px"}
{* crmv@29617e *}

<!-- ActivityReminder Customization for callback -->
{* crmv@98866 *}
<div class="lvtCol fixedLay1" id="ActivityRemindercallback" style="display:none;font-weight:normal;" align="left">
	{include file="ActivityReminderContainer.tpl"}
</div>
{* crmv@98866 end *}
<!-- End -->

<!-- divs for asterisk integration -->
<div class="notificationDiv" id="notificationDiv" style="display:none;"></div> {* crmv@164368 *}

{if $USE_ASTERISK eq 'true'}
	<div id="OutgoingCall" style="display: none;position: absolute;z-index:200;" class="layerPopup">
		<table  border='0' cellpadding='5' cellspacing='0' width='100%'>
			<tr style='cursor:move;' >
				<td class='mailClientBg small' id='outgoing_handle'>
					<b>{$APP.LBL_OUTGOING_CALL}</b>
				</td>
			</tr>
		</table>
		<table  border='0' cellpadding='0' cellspacing='0' width='100%' class='hdrNameBg'>
			</tr>
			<tr><td style='padding:10px;' colspan='2'>
				{$APP.LBL_OUTGOING_CALL_MESSAGE}
			</td></tr>
		</table>
	</div>
{/if}
<!-- divs for asterisk integration :: end-->

<script>{Users::m_de_cryption_get(2)}</script> {* crmv@181170 *}

<div class="lvtCol fixedLay1" id="CheckAvailableVersionDiv" style="border: 0; right: 0px; bottom: 2px; display:none; padding: 2px; z-index: 10; font-weight: normal;" align="left"></div>

<script type="text/javascript">bindButtons();</script>	{* crmv@59626 *}

{* crmv@125629 *}
{if $VTEALERT neq ''}
<script type="text/javascript">
	setTimeout(function(){ldelim}
		vtealert('{$VTEALERT}',null,{ldelim}'html':true{rdelim});
	{rdelim},1000);
</script>
{/if}
{* crmv@125629e *}

{* crmv@187621 *}
{if $FAST_PANEL eq 'ModComments'}
<script type="text/javascript">
	setTimeout(function(){ldelim}
		jQuery('#ModCommentsCheckChangesImg').click();
	{rdelim},500);
</script>
{/if}
{* crmv@187621e *}