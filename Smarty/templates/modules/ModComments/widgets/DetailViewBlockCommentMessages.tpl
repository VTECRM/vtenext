{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@start *} {* crmv@31301 *}

{if $NEWS_MODE eq 'yes'}

{include file="HTMLHeader.tpl" head_include="jquery,prototype"}

<body class="small">
	<script type="text/javascript" src="modules/ModComments/ModCommentsCommon.js"></script>
	<script type="text/javascript">
		parent.jQuery('#indicatorModCommentsNews').hide(); // crmv@192033
	</script>

{/if}

{if $smarty.request.target_frame eq 'ModCommentsNews_iframe'}
	{assign var="WIDGET_MODE" value="Button"}
{else}
	{assign var="WIDGET_MODE" value="Home"}
{/if}

{if empty($smarty.request.ajax)}
	{* crmv@43448 *}
	<div id="ModCommentsUsers2" style="display:none;position:fixed;" class="crmvDiv">
		<input type="hidden" id="ModComments_addCommentId" name="ModComments_addCommentId" /> {* crmv@43050 *}
		<table border="0" cellpadding="5" cellspacing="0" width="100%">
			<tr style="cursor:move;" height="34">
				<td id="ModCommentsUsers_Handle" style="padding:5px" class="level3Bg">
					<table cellpadding="0" cellspacing="0" width="100%" class="small">
					<tr>
						<td width="80%"><b>{'Users'|getTranslatedString:'ModComments'}</b></td>
						<td width="20%" align="right">
							<input type="button" value="{'LBL_PUBLISH'|getTranslatedString:'ModComments'}" name="button" class="crmbutton small save" title="{'LBL_PUBLISH'|getTranslatedString:'ModComments'}" onClick="jQuery('#ModCommentsUsers_idlist').val(jQuery('#ModCommentsUsers_idlist2').val()); VTE.ModCommentsCommon.addComment('{$UIKEY}', '{$ID}', 'Users', '{$INDICATOR}');">
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
					<input type="text" class="detailedViewTextBox" id="ModCommentsUsers_InputUsers2" name="ModCommentsUsers_InputUsers2" value="">
				</td>
				<td class="cellText" style="padding: 5px;" align="left" nowrap>
					<span class="mailClientCSSButton">
						<i class="vteicon md-link" title="{$APP.LBL_CLEAR}" onClick="jQuery('#ModCommentsUsers_InputUsers2').val('');getObj('ModCommentsUsers_InputUsers2').focus();">highlight_off</i>
					</span>
				</td>
			</tr>
		</table>
		<div id="ModCommentsUsers_list2" class="txtBox1" style="margin: 8px; padding: 2px 0px 2px 0px; width: 600px; height: 150px; overflow-y: auto;"></div>
		<div class="closebutton" onClick="fninvsh('ModCommentsUsers2'); removeAllUsers();"></div>
	</div>
	
	<input type="hidden" id="ModCommentsUsers_idlist2" />
	<input type="hidden" id="ModCommentsUsers_idlist3" />
	
	<script type="text/javascript">
		{if isMobile() neq true}
			{* crmv@192014 *}
			jQuery('#ModCommentsUsers2').draggable({ldelim}
				handle: '#ModCommentsUsers_Handle'
			{rdelim});
			{* crmv@192014e *}
		{/if}
	</script>
	{* crmv@43448e *}

	<input type="hidden" id="ModCommentsUsers_idlist" name="users_comm" />

	<table border="0" cellpadding="0" cellspacing="0" width="100%" id="container_tblModCommentsDetailViewBlockCommentWidget">
		<tr height="30px">
			<td>
				<div class="messages-talk-users-wrapper">
					<div class="messages-talk-users-methods-wrapper">
						<i class="vteicon" style="vertical-align:middle;padding-right:7px;">chat</i>
						<span>{'SINGLE_ModComments'|getTranslatedString:'ModComments'}</span>
						<div class="messages-talk-users-methods" id="saveOptionsRow_{$UIKEY}" style="display:none;">
							<span> per</span>
							{* crmv@82419 *}
							{if $ENABLE_PUBLIC_TALKS}
								<div class="radio radio-primary radio-horiz">
									<label for="ModCommentsMethodAll">
										<input type="radio" name="ModCommentsMethod" id="ModCommentsMethodAll" value="All" onClick="jQuery('#ModCommentsUsers_{$UIKEY}').hide();">
										{'All'|getTranslatedString:'ModComments'}
									</label>
								</div>
							{/if}
							<div class="radio radio-primary radio-horiz">
								<label for="ModCommentsMethodUsers">
									<input type="radio" name="ModCommentsMethod" id="ModCommentsMethodUsers" value="Users" onClick="jQuery('#ModCommentsUsers_{$UIKEY}').show();jQuery('#ModCommentsUsers_InputUsers').focus();">
									{'Users'|getTranslatedString:'ModComments'}
								</label>
							</div>
							{* crmv@82419e *}
						</div>
					</div>
					<div class="messages-talk-users-list-wrapper" id="ModCommentsUsers_{$UIKEY}" style="display:none;">
						<div id="ModCommentsUsers_list" class="dvtCellInfo" onClick="jQuery('#ModCommentsUsers_InputUsers').focus();">
							<input type="text" class="detailedViewTextBox input-inline" id="ModCommentsUsers_InputUsers" name="ModCommentsUsers_InputUsers">
						</div>
					</div>
					{if $smarty.request.module neq 'Emails'}
						<div class="messages-talk-publish-wrapper" id="saveButtonRow_{$UIKEY}" style="display:none;">
							<button type="button" class="crmbutton save" onclick="VTE.ModCommentsCommon.addComment('{$UIKEY}', '{$ID}', jQuery('input[name=ModCommentsMethod]:checked').val(), 'indicator{$UIKEY}');">{$MOD.LBL_PUBLISH}</button>
						</div>
					{/if}
				</div>
			</td>
		</tr>
	</table>
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
						<tr><td class="dvtCellInfo" align="right">
							<textarea id="txtbox_{$UIKEY}" name="comment" class="detailedViewTextBox detailedViewModCommTextBox" onFocus="onModCommTextBoxFocus('txtbox_{$UIKEY}','{$UIKEY}');" onBlur="onModCommTextBoxBlur('txtbox_{$UIKEY}','{$UIKEY}');" cols="90" rows="8">{$DEFAULT_TEXT}</textarea>
						</td></tr>
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
<script type="text/javascript">
	jQuery('#ModCommentsUsers_InputUsers').css('zIndex',findZMax()+1);
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
			source: function( request, response ) {
				jQuery.getJSON( "index.php?module=ModComments&action=ModCommentsAjax&file=Autocomplete&mode=Users&idlist="+getObj('ModCommentsUsers_idlist').value, {
					term: extractLast( request.term )
				}, response );
			},
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
				//ui.item.img
				var span = '<span id="ModCommentsUsers_list_'+ui.item.value+'" class="addrBubble" style="float:left;">'+ui.item.full_name
						+' <div id="ModCommentsUsers_list_'+ui.item.value+'_remove" class="ImgBubbleDelete" onClick="removeUser(\''+ui.item.value+'\');"><i class="vteicon small">clear</i></div>'
						+'</span>';
				jQuery("#ModCommentsUsers_list").prepend(span);

				getObj('ModCommentsUsers_idlist').value = getObj('ModCommentsUsers_idlist').value+ui.item.value+'|';

				return false;
			}
		}
	);
	// crmv@43448
	jQuery("#ModCommentsUsers_InputUsers2")
		// don't navigate away from the field on tab when selecting an item
		.bind( "keydown", function( event ) {
			if ( event.keyCode === jQuery.ui.keyCode.TAB &&
				jQuery( this ).data( "autocomplete" ).menu.active ) {
				event.preventDefault();
			}
		})
		.autocomplete({
			appendTo: '#ModCommentsUsers2',
			source: function( request, response ) {
				var extraids = jQuery('#ModCommentsUsers_idlist2').val() + '|' + jQuery('#ModCommentsUsers_idlist3').val();
				jQuery.getJSON( "index.php?module=ModComments&action=ModCommentsAjax&file=Autocomplete&mode=Users&idlist="+encodeURIComponent(extraids), {
					term: extractLast( request.term )
				}, response );
			},
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
				var span = '<span id="ModCommentsUsers_list2_'+ui.item.value+'" class="addrBubble">'
						+'<table cellpadding="3" cellspacing="0" class="small">'
						+'<tr>'
						+	'<td rowspan="2"><img src="'+ui.item.img+'" class="userAvatar" /></td>'
						+	'<td>'+ui.item.full_name+'</td>'
						+	'<td rowspan="2" align="right" valign="top"><div id="ModCommentsUsers_list2_'+ui.item.value+'_remove" class="ImgBubbleDelete" onClick="removeUser(\''+ui.item.value+'\');"><i class="vteicon small">clear</i></div></td>'
						+'</tr>'
						+'<tr>'
						+	'<td>'+ui.item.user_name+'</td>'
						+'</tr>'
						+'</table>'
						+'</span>';
				jQuery("#ModCommentsUsers_list2").append(span);

				getObj('ModCommentsUsers_idlist2').value = getObj('ModCommentsUsers_idlist2').value + ui.item.value + '|';

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
		if (d && olddiv) d.removeChild(olddiv);

		// remove also for panel
		var tmp = getObj('ModCommentsUsers_idlist2').value;
		tmp = tmp.replace(id,'');
		getObj('ModCommentsUsers_idlist2').value = tmp;

		var d = document.getElementById('ModCommentsUsers_list2');
		var olddiv = document.getElementById('ModCommentsUsers_list2_'+id);
		if (d && olddiv) d.removeChild(olddiv);
	}
	function removeAllUsers() {
		getObj('ModCommentsUsers_idlist').value='';
		getObj('ModCommentsUsers_idlist2').value='';
		jQuery('#ModCommentsUsers_list span').remove();
		jQuery('#ModCommentsUsers_list2').html('');
		jQuery('#ModComments_addCommentId').val(''); // crmv@104139
	}
	function showUsersPopup(self, commentid) {
		if (!commentid) commentid = '';
		jQuery('#ModComments_addCommentId').val(commentid);

		if (commentid) {
			var tab = jQuery(self).closest('table')[0],
				uikey = tab.id.replace(/^tbl/, '').replace(/_[0-9]*$/, ''),
				moreids = jQuery('#listRecip_'+uikey+'_'+commentid).val();

			// add list of all recipients
			jQuery('#ModCommentsUsers_idlist3').val(moreids);
		}

		fnvshobj(self,'ModCommentsUsers2');
		// center it (the standard function is buggy)
		var mainCont = jQuery('#container_tblModCommentsDetailViewBlockCommentWidget'),
			contPos = mainCont.offset();
		jQuery('#ModCommentsUsers2').css({
			'left': contPos.left + (mainCont.width() - jQuery('#ModCommentsUsers2').width())/2 ,
			'top': contPos.top - jQuery('#ModCommentsUsers2').height() + 60
		});
		
		var zmax = findZMax()+1;
		getObj('ModCommentsUsers2').style.zIndex = zmax;
		getObj('ModCommentsUsers_InputUsers').focus();
	}
	// crmv@43448e
	{/literal}
</script>
{/if}
<!-- crmv@16903e -->

{if $NEWS_MODE eq 'yes'}
	</body>
{/if}
{* crmv@end *}	{* crmv@31301e *}