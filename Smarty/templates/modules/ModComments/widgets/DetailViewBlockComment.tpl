{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@31301 crmv@192033 *}

{if $NEWS_MODE eq 'yes'}

{include file="HTMLHeader.tpl" head_include="jquery,jquery_plugins,jquery_ui,prototype"}
	
<body class="small">
		<script type="text/javascript" src="modules/ModComments/ModCommentsCommon.js"></script>
		<script type="text/javascript">
			parent.jQuery('#indicatorModCommentsNews').hide();
		</script>

{/if}

{include file="modules/ModComments/widgets/DetailViewBlockCommentHeaders.tpl"}

{if $smarty.request.target_frame eq 'ModCommentsNews_iframe'}
	{assign var="WIDGET_MODE" value="Button"}
{else}
	{assign var="WIDGET_MODE" value="Home"}
{/if}

{assign var="DEFAULT_TEXT" value='LBL_ADD_COMMENT'|getTranslatedString:'ModComments'}
{assign var="DEFAULT_REPLY_TEXT" value='LBL_DEFAULT_REPLY_TEXT'|getTranslatedString:'ModComments'}
<script id="default_labels">
	var default_text = '{$DEFAULT_TEXT}';
	var default_reply_text = '{$DEFAULT_REPLY_TEXT}';
</script>

<!-- crmv@16903 -->
{if empty($smarty.request.ajax)}
<div style="width:auto;display:{$DEFAULT_DISPLAY_BLOCK};" id="tbl{$UIKEY}">
{/if}
<!-- crmv@16903e -->

<input type="hidden" id="unseen_ids" value="{','|implode:$UNSEEN_IDS}" />
<input type="hidden" id="uikey" value="{$UIKEY}" /> {* crmv@80503 *}

{if $NEWS_MODE eq 'yes'}
	{assign var="INDICATOR" value="parent.document.getElementById('indicatorModCommentsNews')"}
{else}
	{assign var="INDICATOR" value="document.getElementById('indicator"|cat:$UIKEY|cat:"')"}
{/if}

<table class="small" border="0" cellpadding="0" cellspacing="0" width="100%">
	{if $ALLOW_GENERIC_TALKS eq 'yes'}
		<tr style="height: 3px;"><td></td></tr>
		<tr style="height: 25px;">
			<td width="100%">
				<div id="editareaModComm">
					<table cellpadding="0" cellspacing="0" width="100%">
						{* crmv@43448 *}
						<tr><td class="dvtCellInfo" colspan="2">
							<textarea id="txtbox_{$UIKEY}" class="detailedViewTextBox detailedViewModCommTextBox" onFocus="onModCommTextBoxFocus('txtbox_{$UIKEY}','{$UIKEY}');" onBlur="onModCommTextBoxBlur('txtbox_{$UIKEY}','{$UIKEY}');">{$DEFAULT_TEXT}</textarea>
						</td></tr>
						<tr id="saveButtonRow_{$UIKEY}" style="display: none;">
							<td align="left" class="small">
							{if $ID eq ''}
								&nbsp;<span class="commentAddLink"><a href="javascript:;" onclick="top.LPOP.openPopup('ModComments', '', 'linkrecord', {ldelim}callback_link: 'commentsLinkModule',callback_create: 'commentsCreateModule', 'uikey': '{$UIKEY}' {rdelim}, window)">{"LBL_LINK_ACTION"|getTranslatedString:'Messages'}</a></span> {* crmv@43864 *}

								<input type="hidden" name="ModCommentsParentId" id="ModCommentsParentId" value="">
								<span id="ModCommentsNewRelatedLabel" style="display:none">{'LBL_ABOUT'|getTranslatedString:'ModComments'}</span>
								<span id="ModCommentsNewRelatedName" style="display:none;font-weight:700"></span>
							{/if}
							</td>
						{* crmv@43448w *}
							<td align="right">
								<input type="hidden" id="ModCommentsUsers_idlist" />
								<input type="hidden" id="ModCommentsUsers_idlist2" /> {* crmv@43050 *}
								<button class="crmbutton small save" style="margin:0px"
									onmouseover="VTE.ModCommentsCommon.mouseOverPublishBtn(this, true)" {* crmv@199112 - always true otherwise not working in softed theme *}
									onmouseout="VTE.ModCommentsCommon.mouseOutPublishBtn(this)"> {* crmv@199112 *}
									{$MOD.LBL_PUBLISH}&nbsp;{$MOD.LBL_TO}&nbsp;<span class="caret"></span>
								</button>	{* crmv@2963m *}
								<div id="ModComments_sub" onmouseover="jQuery('#ModComments_sub').show();" onmouseout="VTE.ModCommentsCommon.mouseOutPublishBtn()" style="display: none;"> {* crmv@197996 crmv@199112 *}
									<table width="100%" border="0" cellpadding="0" cellspacing="0">
										{* crmv@35267 *}
										{if $ENABLE_PUBLIC_TALKS}
											<tr><td><a class="drop_down" href="javascript:;" onclick="VTE.ModCommentsCommon.addComment('{$UIKEY}', '{$ID}', 'All', {$INDICATOR});">{'All'|getTranslatedString:'ModComments'}</a></td></tr>
										{/if}
										{* crmv@35267e *}
										<tr><td><a class="drop_down" href="javascript:;" onclick="showUsersPopup(this)">{'Users'|getTranslatedString:'ModComments'}</a></td></tr> {* crmv@43050 *}
									</table>
								</div>
								{if empty($COMMENTS)}<br><br>{/if} {* crmv@149827 *}
							</td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
	{/if}

	<tr>
		<td>
			<div id="contentwrap_{$UIKEY}" style="width: 100%;">
				{include file="modules/ModComments/widgets/DetailViewBlockCommentPage.tpl"} {* crmv@80503 *}
			</div>
		</td>
	</tr>

	{* crmv@2963m *}
	{if $smarty.request.file eq 'PreView'}
		<tr id="ModCommentsBottomWhiteSpace" style="display:none;" height="50px"><td></td></tr>
	{/if}
	{* crmv@2963me *}

	{if $NEWS_MODE eq 'yes'}
		<input type="hidden" id="max_number_of_news" value="{$MAX_NUM_OF_NEWS}" />
		{if $TOTAL_NUM_OF_NEWS > $MAX_NUM_OF_NEWS}
			<tr>
				<td class="ModCommAnswerBox" align="center" style="padding: 3px 0px;">
					{if $WIDGET_MODE eq 'Home'}
						{assign var="search_prefix" value="modcomments_widg_search"}
					{else}
						{assign var="search_prefix" value="modcomments_search"}
					{/if}
					(<span id="comments_counter_from_{$UIKEY}">1</span>-<span id="comments_counter_to_{$UIKEY}">{$MAX_NUM_OF_NEWS}</span> {'LBL_OF'|getTranslatedString:'Settings'} <span id="comments_counter_total_{$UIKEY}">{$TOTAL_NUM_OF_NEWS}</span>)&nbsp;<span id="comments_counter_link_{$UIKEY}"><a href="javascript:loadModCommentsPage({$MAX_NUM_OF_NEWS}+eval(VTE.ModCommentsCommon.default_number_of_news),'{$smarty.request.target_frame}','{$smarty.request.indicator}',parent.jQuery('#{$search_prefix}_text').val());">{'LBL_SHOW_OTHER_TALKS'|getTranslatedString:'ModComments'}</a></span> {* crmv@80503 *}
				</td>
			</tr>
		{/if}
	{/if}

	</table>

<!-- crmv@16903 -->
{if empty($smarty.request.ajax)}
</div>
{/if}

{if empty($smarty.request.ajax) || $NEWS_MODE eq 'yes'}
<div id="ModCommentsUsers" style="display:none;position:fixed;" class="crmvDiv">
	<input type="hidden" id="ModComments_addCommentId" name="ModComments_addCommentId" /> {* crmv@43050 *}
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr style="cursor:move;" height="34">
			<td id="ModCommentsUsers_Handle" style="padding:5px" class="level3Bg">
				<table cellpadding="0" cellspacing="0" width="100%" class="small">
				<tr>
					<td width="80%"><b>{'Users'|getTranslatedString:'ModComments'}</b></td>
					<td width="20%" align="right">
						<input type="button" value="{'LBL_PUBLISH'|getTranslatedString:'ModComments'}" name="button" class="crmbutton small save" title="{'LBL_PUBLISH'|getTranslatedString:'ModComments'}" onClick="VTE.ModCommentsCommon.addComment('{$UIKEY}', '{$ID}', 'Users', {$INDICATOR});">
					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td>&nbsp;{$APP.LBL_SEARCH_BUTTON_LABEL}:</td>
			<td class="cellText" align="left" style="width:500px">
				<input type="text" class="detailedViewTextBox" id="ModCommentsUsers_InputUsers" name="ModCommentsUsers_InputUsers" value="">
			</td>
			<td class="cellText" style="padding: 5px;" align="left" nowrap>
				<span class="mailClientCSSButton">
					<i class="vteicon md-link" title="{$APP.LBL_CLEAR}" onClick="jQuery('#ModCommentsUsers_InputUsers').val('');getObj('ModCommentsUsers_InputUsers').focus();">highlight_off</i>
				</span>
			</td>
		</tr>
	</table>
	<div id="ModCommentsUsers_list" class="txtBox1" style="margin: 8px; padding: 2px 0px 2px 0px; width: 600px; height: 150px; overflow-y: auto;"></div>
	<div class="closebutton" onClick="fninvsh('ModCommentsUsers'); removeAllUsers();"></div>
</div>

<script type="text/javascript">
	jQuery('#ModCommentsUsers_InputUsers').css('zIndex',findZMax()+1);
	{* crmv@30356 *}
	{if isMobile() neq true}
		{* crmv@192014 *}
		jQuery('#ModCommentsUsers').draggable({ldelim}
			handle: '#ModCommentsUsers_Handle'
		{rdelim});
		{* crmv@192014e *}
	{/if}
	{* crmv@30356e *}
	{literal}
	function split( val ) {
		return val.split( /,\s*/ );
	}
	function extractLast( term ) {
		return split( term ).pop();
	}
	jQuery("#ModCommentsUsers_InputUsers")
		// don't navigate away from the field on tab when selecting an item
		.bind( "keydown", function( event ) {
			if ( event.keyCode === jQuery.ui.keyCode.TAB &&
					jQuery( this ).data( "autocomplete" ).menu.active ) {
				event.preventDefault();
			}
		})
		.autocomplete({
			appendTo: '#ModCommentsUsers',
			source: function( request, response ) {
				//crmv@91082
				if(!SessionValidator.check()) {
					SessionValidator.showLogin();
					return false;
				}
				//crmv@91082e
				var extraids = jQuery('#ModCommentsUsers_idlist').val() + '|' + jQuery('#ModCommentsUsers_idlist2').val();
				jQuery.getJSON( "index.php?module=ModComments&action=ModCommentsAjax&file=Autocomplete&mode=Users&idlist="+encodeURIComponent(extraids), {
					term: extractLast( request.term )
				}, response );
			},
			// crmv@110481
			open: function() {
				if (typeof window.findZMax == 'function') {
					var zmax = findZMax();
					jQuery(this).autocomplete('widget').css('z-index', zmax+2);
				}
				return false;
			},
			// crmv@110481e
			search: function() {
				// custom minLength
				var term = extractLast( this.value );
				if ( term.length < 3 ) {
					return false;
				}
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function( event, ui ) {
				var terms = split( this.value );
				// remove the current input
				terms.pop();
				// add placeholder to get the comma-and-space at the end
				terms.push('');
				this.value = terms.join(', ');

				// add the selected item
				var span = '<span id="ModCommentsUsers_list_'+ui.item.value+'" class="addrBubble">'
						+'<table cellpadding="3" cellspacing="0" class="small">'
						+'<tr>'
						+	'<td rowspan="2"><img src="'+ui.item.img+'" class="userAvatar" /></td>'
						+	'<td>'+ui.item.full_name+'</td>'
						+	'<td rowspan="2" align="right" valign="top"><div id="ModCommentsUsers_list_'+ui.item.value+'_remove" class="ImgBubbleDelete" onClick="removeUser(\''+ui.item.value+'\');"><i class="vteicon small">clear</i></div></td>'
						+'</tr>'
						+'<tr>'
						+	'<td>'+ui.item.user_name+'</td>'
						+'</tr>'
						+'</table>'
						+'</span>';
				jQuery("#ModCommentsUsers_list").append(span);

				getObj('ModCommentsUsers_idlist').value = getObj('ModCommentsUsers_idlist').value+ui.item.value+'|';

				return false;
			}
		}
	);
	function removeUser(id) {
		var tmp = getObj('ModCommentsUsers_idlist').value;
		tmp = tmp.replace(id,'');
		getObj('ModCommentsUsers_idlist').value = tmp;

		var d = document.getElementById('ModCommentsUsers_list');
		var olddiv = document.getElementById('ModCommentsUsers_list_'+id);
		d.removeChild(olddiv);
	}
	function removeAllUsers() {
		getObj('ModCommentsUsers_idlist').value='';
		getObj('ModCommentsUsers_list').innerHTML='';
		jQuery('#ModComments_addCommentId').val(''); // crmv@104139
	}
	// crmv@43050
	function showUsersPopup(self, commentid) {
		if (!commentid) commentid = '';
		jQuery('#ModComments_addCommentId').val(commentid);

		if (commentid) {
			var tab = jQuery(self).closest('table')[0],
				uikey = tab.id.replace(/^tbl/, '').replace(/_[0-9]*$/, ''),
				moreids = jQuery('#listRecip_'+uikey+'_'+commentid).val();

			// add list of all recipients
			jQuery('#ModCommentsUsers_idlist2').val(moreids);
		}

		fnvshobj(self,'ModCommentsUsers');
		placeAtCenter(getObj('ModCommentsUsers'));
		var zmax = findZMax()+1;
		getObj('ModCommentsUsers').style.zIndex = zmax;
		getObj('ModCommentsUsers_InputUsers').focus();
	}
	// crmv@43050e
	{/literal}
</script>
{/if}
<!-- crmv@16903e -->
{*
{if empty($smarty.request.ajax)}
	<table class="small" border="0" cellpadding="0" cellspacing="0" width="100%" id="container_tblModCommentsDetailViewBlockCommentWidget">
	<tr>
		<td>
			{$APP.LBL_SHOW}
			<select id="ModCommentReload" class="small" onchange="VTE.ModCommentsCommon.reloadContentWithFiltering('{$WIDGET_NAME}', '{$ID}', this.value, 'tbl{$UIKEY}', 'indicator{$UIKEY}');">
				<option value="All" {if $CRITERIA eq 'All'}selected{/if}>{$APP.LBL_ALL}</option>
				<option value="Last5" {if $CRITERIA eq 'Last5'}selected{/if}>{$MOD.LBL_LAST5}</option>
				<option value="Mine" {if $CRITERIA eq 'Mine'}selected{/if}>{$MOD.LBL_MINE}</option>
			</select>
			{include file="LoadingIndicator.tpl" LIID="indicator$UIKEY" LIEXTRASTYLE="display:none;"}
		</td>
	</tr>
	</table>
{/if}
*}
{if $NEWS_MODE eq 'yes'}
	<script type="text/javascript">bindButtons(window.top);</script>	{* crmv@59626 *}
	</body>
{/if}
{* crmv@end *}	{* crmv@31301e *}