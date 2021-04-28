{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@16265 crmv@18549 crmv@18592 crmv@24822 crmv@21996 crmv@22622 crmv@30356 crmv@7220 crmv@44723 crmv@54707 crmv@82831 crmv@169305 *}

{include file="HTMLHeader.tpl" head_include="all"}

<body class="small {if $HIDE_MENUS eq true}hide-menus{/if} {if $THEME_CONFIG['body_light']}body-light{/if}">
<a name="top"></a>

{* crmv@82419 used to insert some extra code in the body *}
{include file="Theme.tpl" THEME_MODE="body"}
{* crmv@82419e *}

{SDK::checkJsLanguage()}	{* crmv@sdk-18430 *} {* crmv@181170 *}
{include file='CachedValues.tpl'}	{* crmv@26316 *}
{include file='modules/SDK/src/Reference/Autocomplete.tpl'}	{* crmv@29190 *}

<div id="login_overlay" style="display:none;" class="login_overlay"></div> {* crmv@91082 *}

{* crmv@140887 *}
{include file="header/LateralMenu.tpl"}
{* crmv@140887e *}

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
			<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
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
	<div id="OutgoingCall" style="display:none;position:absolute;z-index:200;" class="layerPopup">
		<table border="0" cellpadding="10" cellspacing="0" width="100%">
			<tr>
				<td rowspan="2" style="padding:10px"><i class="vteicon nohover">phone</i></td> {* crmv@164368 *}
				<td id="outgoing_handle">
					<b>{$APP.LBL_OUTGOING_CALL}</b>
				</td>
			</tr>
			<tr>
				<td>{$APP.LBL_OUTGOING_CALL_MESSAGE}</td>
			</tr>
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
{if $FAST_PANEL neq ''}
<script type="text/javascript">
	setTimeout(function(){ldelim}
		if (jQuery('[data-module="{$FAST_PANEL}"]').length > 0) jQuery('[data-module="{$FAST_PANEL}"]').click();
	{rdelim},500);
</script>
{/if}
{* crmv@187621e *}