{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<!-- module header -->
<script language="JavaScript" type="text/javascript">
	var current_account = '{$CURRENT_ACCOUNT}';
	var current_folder = '{$CURRENT_FOLDER}';
	var list_status = 'view';
	var folders_status = 'view';
	var current_record = '';			// current message selected
	var preview_current_record = '';	// when I display the preview of a linked record preview_current_record = current_record
	var preview_id = '';				// last preview displayed
	var last_thread_clicked = '{$LAST_THREAD_CLICKED}';
	var specialFolders = eval({$SPECIAL_FOLDERS_ENCODED});
	var ajax_enable = true;
	var hidden_button = '';
	var iAmInThread = false;
</script>
<script language="JavaScript" type="text/javascript" src="{"include/js/ListView.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/search.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/Merge.js"|resourcever}"></script> {* crmv@8719 *}
{* crmv@30967 - moved js functions to ListView.js *}
<script language="JavaScript" type="text/javascript" src="modules/{$MODULE}/{$MODULE}.js"></script>
<script language="JavaScript" type="text/javascript" src="{"modules/Calendar/script.js"|resourcever}"></script> {* crmv@42752 *}
<script language="JavaScript" type="text/javascript" src="{"modules/Popup/Popup.js"|resourcever}"></script> {* crmv@43864 *}

{include file='modules/Messages/Move2Folder.tpl'}
{include file='modules/Messages/CreateFolder.tpl'}

<input type="hidden" id="user_dateformat" name="user_dateformat" value="{$DATEFORMAT}">
<textarea name="select_ids" id="select_ids" style="display:none;"></textarea>

{include file='Buttons_List.tpl'}
{if $smarty.request.ajax eq ''}
	<div id="Buttons_List_3_Container" style="display:none;">
		<table id="bl3" border=0 cellspacing=0 cellpadding=2 width=100% class="small">
			<tr>
				<td align="right" width="24%" id="Messages_Buttons_List" nowrap>
					{include file='modules/Messages/Button_List.tpl'}
				</td>
                <td align="right" width="61%">
					<table cellspacing="0" cellpadding="0" border="0">
					<tr>
						{* crmv@140887 *}
						<td style="padding-right:20px" nowrap>
							{include file="Buttons_List_Contestual.tpl"}
						</td>
						{* crmv@140887e *}
						<td id="Button_List_Detail" align="right" width="100%" style="padding-right:20px"></td>
					</tr>
					</table>
				</td>
				<td align="right" width="15%">
					{if $smarty.request.ajax eq ''}
						{* crmv@31245 crmv@105588 *}
						<form id="basicSearch" name="basicSearch" method="post" action="index.php" onSubmit="return callSearch('Basic', '{$FOLDERID}');">
							<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
							<input type="hidden" name="searchtype" value="BasicSearch" />
	                        <input type="hidden" name="module" value="{$MODULE}" />
	                        <input type="hidden" name="parenttab" value="{$CATEGORY}" />
	            			<input type="hidden" name="action" value="index" />
	                        <input type="hidden" name="query" value="true" />
	            			<input type="hidden" id="basic_search_cnt" name="search_cnt" />
	
							<div class="form-group basicSearch advIconSearch">
								<input type="text" class="form-control searchBoxMessages" id="basic_search_text" name="search_text" value="{$APP.LBL_SEARCH}" onclick="clearText(this)" onblur="restoreDefaultText(this, '{$APP.LBL_SEARCH}')" />
								<span class="cancelIcon">
									<img id="basic_search_icn_canc" style="display:none" border="0" alt="Reset" title="Reset" style="cursor:pointer" onclick="cancelSearchText('{$APP.LBL_SEARCH}')" src="{'close_little.png'|resourcever}" />&nbsp;
								</span>
								<span class="searchIcon">
									<i id="basic_search_icn_go" class="vteicon" title="{$APP.LBL_FIND}" style="cursor:pointer" onclick="jQuery('#basicSearch').submit();" >search</i>
								</span>
								<span class="advSearchIcon">
									<i id="adv_search_icn_go" class="vteicon" title="{$APP.LNK_ADVANCED_SEARCH}" style="cursor:pointer" onclick="advancedSearchOpenClose(); if (!advance_search_submitted) updatefOptions(document.getElementById('Fields0'), 'Condition0');">keyboard_arrow_down</i>
								</span>
							</div>
						</form>
						{* crmv@31245e crmv@22259e crmv@105588e  *}
					{/if}
				</td>
			</tr>
		</table>
	</div>
	<script type="text/javascript">
		{if $MULTIACCOUNT eq true}
			var isMultiAccount = true;
			{if $CURRENT_ACCOUNT eq 'all' || $CURRENT_FOLDER eq $SPECIAL_FOLDERS.INBOX}
				{assign var="fastinbox" value="true"}
			{else}
				{assign var="fastinbox" value="false"}
			{/if}
		{else}
			var isMultiAccount = false;
			{assign var="fastinbox" value="false"}
		{/if}
		changeButtons('ListView','',{$fastinbox});
		{literal}jQuery(document).ready(function() { calculateButtonsList3(); });{/literal}
	</script>
{/if}

<!-- Contents -->
<table border=0 cellspacing=0 cellpadding=0 width=100% align=center>	{*<!-- crmv@18592 -->*}
	<tr>
	    <td class="showPanelBg" valign="top" width=100% style="padding:0px;">

	    	{include file="AdvancedSearch.tpl"}

			<!-- crmv@8719 -->
			<div id="mergeDup" style="z-index:1;display:none;position:relative;">
				{include file="MergeColumns.tpl"}
			</div>
			<!-- crmv@8719e -->

			<!-- PUBLIC CONTENTS STARTS-->
			<div id="PageContents" style="width:100%; height:100%;">

			    <div id="Accounts" style="width: {$DIV_DIMENSION.Folders}; float: left; display: none;">
			    	{include file="modules/Messages/Accounts.tpl"}
			    </div>

			    <div id="Folders" style="width: {$DIV_DIMENSION.Folders}; float: left; display: none;">
			    	{include file="modules/Messages/Folders.tpl"}
			    </div>

			    <div id="ListViewContents" style="width: {$DIV_DIMENSION.ListViewContents}; float: left;">
			    	{include file="modules/Messages/ListViewEntries.tpl" MOD=$MOD}
			    </div>

			    {* DetailViewContents - start *}
				<script language="JavaScript" type="text/javascript" src="{"include/js/dtlviewajax.js"|resourcever}"></script>
				{include file="modules/ModComments/widgets/DetailViewBlockCommentHeaders.tpl"}
			    <div id="DetailViewContents" style="width: {$DIV_DIMENSION.PreDetailViewContents}; float: left;">&nbsp;</div>
			    {* DetailViewContents - end *}

				<div id="TurboliftContents" style="width: {$DIV_DIMENSION.TurboliftContents}; float: left;">
					<div id="TurboliftButtons" class="turbolift-buttons" {if empty($LISTENTITY)}style="display:none;"{/if}>
						{include file="TurboliftButtons.tpl" SHOW_TURBOLIFT_CAL_BUTTONS=true SHOW_TURBOLIFT_LINK_BUTTON=true SHOW_TURBOLIFT_CREATE_BUTTON=true ID="current_record"}
					</div>
					<div id="TurboliftContentRelations" class="turbolift-relations"></div>
				</div>
				
				{if $SHOW_TURBOLIFT_TRACKER}
					{include file="modules/SDK/src/CalendarTracking/PopupTracking.tpl" ID=0}
				{/if}
			</div>
		</td>
	</tr>
</table>
<div id="account_error" style="display:none">{$ACCOUNT_ERROR}</div>	{* crmv@125629 *}
<form name="SendMail"><div id="sendmail_cont" style="z-index:100001;position:absolute;"></div></form>
<form name="SendFax"><div id="sendfax_cont" style="z-index:100001;position:absolute;"></div></form>
<form name="SendSms" id="SendSms" method="POST" action="index.php"><div id="sendsms_cont" style="z-index:100001;position:absolute;"></div></form>	{* crmv@16703 *}
{*
<script language="javascript" type="text/javascript" src="include/js/jquery_plugins/slimscroll/jquery.min.js"></script>
<script language="javascript" type="text/javascript" src="include/js/jquery_plugins/slimscroll/jquery-ui.min.js"></script>
<script language="javascript" type="text/javascript" src="include/js/jquery_plugins/slimscroll/jquery.slimscroll.js"></script>
*}
<link href="include/js/jquery_plugins/mCustomScrollbar/jquery.mCustomScrollbar.css" rel="stylesheet" type="text/css" />
<script src="include/js/jquery_plugins/mCustomScrollbar/jquery.mCustomScrollbar.concat.min.js"></script>
<script language="javascript" type="text/javascript" src="include/js/jquery_plugins/slimscroll/jquery.slimscroll.min.js"></script>
<link href="include/js/jquery_plugins/mCustomScrollbar/VTE.mCustomScrollbar.css" rel="stylesheet" type="text/css" />
{literal}
<script type='text/javascript'>
	//jQuery('.descriptionPreview').width(jQuery('#ListViewContents').width()-35);
	function setmCustomScrollbar(target,height) {
		jQuery(target).mCustomScrollbar({
			set_height: height,
			//set_width:  '{/literal}{$DIV_DIMENSION.ListViewContents}{literal}',
			mouseWheelPixels:350,
			theme:"dark-thick",
			autoHideScrollbar:true,
			callbacks:{
		        onTotalScroll:function(){
		        	//jQuery('#indicatorAppend').show();
					if (jQuery("#appendNextListViewEntries").length > 0)
						jQuery('#appendNextListViewEntries').click();
					//else	// se in cache non ho altri messaggi faccio fetch e ritento l'append
					//	appendFetch();
		            //jQuery('#indicatorAppend').hide();
		        }
		    }
		});
	}
	(function($){
		$(window).load(function(){
			var buttonList3 = parseInt(jQuery('#Buttons_List_3').height());
			var pagecontent_height = eval($(window).height()-$('#vte_menu').height()-$('#vte_footer').height()-5-buttonList3);
			$('#PageContents').height(pagecontent_height);

			setmCustomScrollbar('#ListViewContents',pagecontent_height);

			$('#Accounts').slimScroll({
				wheelStep: 10,
				height: pagecontent_height + 'px',
				width:  '{/literal}{$DIV_DIMENSION.Folders}{literal}'
			});
			$('#Folders').slimScroll({
				wheelStep: 10,
				height: pagecontent_height + 'px',
				width:  '{/literal}{$DIV_DIMENSION.Folders}{literal}'
			});
			/*
			$('#ListViewContents').slimScroll({
				wheelStep: 10,
				height: pagecontent_height + 'px',
				width:  '{/literal}{$DIV_DIMENSION.ListViewContents}{literal}'
			}).bind('slimscroll', function(e, pos){
				if (pos == 'bottom') {
					$('#appendNextListViewEntries').click();
				}
			});
			*/
			$('#DetailViewContents').slimScroll({
				wheelStep: 10,
				height: pagecontent_height + 'px',
				width: '{/literal}{$DIV_DIMENSION.DetailViewContents}{literal}'
			});
			$('#TurboliftContents').slimScroll({
				wheelStep: 10,
				height: pagecontent_height + 'px',
				width: '{/literal}{$DIV_DIMENSION.TurboliftContents}{literal}'
			});
			if ($('.slimScrollDiv').length > 0) {
				$('.slimScrollDiv').css('float','left');
				$('#Accounts').css('width','100%');
				//$('#Accounts').parent().css('outline','1px solid #E0E0E0');
				$('#Folders').css('width','100%');
				//$('#Folders').parent().css('outline','1px solid #E0E0E0');
				$('#DetailViewContents').css('width','100%');
				$('#DetailViewContents').css('overflow-x','visible');
				$('#DetailViewContents').parent().css('overflow-x','visible');
				$('#TurboliftContents').css('width','100%');
				//$('#TurboliftContents').parent().css('outline','1px solid #E0E0E0');
			}
			{/literal}
			{if !empty($RECORD)}
				selectRecord({$RECORD},'','','',false);
			{/if}
			{literal}
			// the first time wait 10 sec to fetch
			//fetch();
			//setTimeout(function(){ fetch(); }, 6000);	//crmv@48159
		});
	})(jQuery);
	{/literal}
	{* crmv@48501 crmv@125629 *}
	{if $NOTIFY neq ''}
		setTimeout(function(){ldelim}
			vtealert('{$NOTIFY}');
		{rdelim},2000);
	{/if}
	{* crmv@48501e crmv@125629e *}
	{* crmv@125629 *}
	{if $ACCOUNT_ERROR neq ''}
		setTimeout(function(){ldelim}
			vtealert(jQuery('#account_error').html(), null, {ldelim}'html':true{rdelim});
		{rdelim},200);
	{/if}
	{* crmv@125629e *}
</script>
{$ADVFILTER_JAVASCRIPT}	{* crmv@48693 *}