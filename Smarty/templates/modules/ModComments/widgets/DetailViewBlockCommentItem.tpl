{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@43050 crmv@43448 crmv@59626 crmv@104853 crmv@192033 *}

{assign var=COMMENTID value=$COMMENTMODEL->id()}

{if $UNSEEN_IDS|is_array && $COMMENTID|in_array:$UNSEEN_IDS}
	{assign var="UNSEEN_CLASS" value=" ModCommUnseen"}
{else}
	{assign var="UNSEEN_CLASS" value=""}
{/if}
{if $UNSEEN_CLASS eq '' && $COMMENTMODEL->hasUnreadReplies($UNSEEN_IDS) eq false}
	{assign var="SEEN" value="true"}
{else}
	{assign var="SEEN" value="false"}
{/if}
{if $NEWS_MODE eq 'yes'}
	{assign var="INDICATOR" value="parent.document.getElementById('indicatorModCommentsNews')"}
{else}
	{assign var="INDICATOR" value="document.getElementById('indicator"|cat:$UIKEY|cat:"')"}
{/if}

{if $COMMENTMODEL->getCurrentUser() eq $COMMENTMODEL->authorId()}
	{assign var=ISMINE value=true}
{else}
	{assign var=ISMINE value=false}
{/if}
{assign var=ISPUBLIC value=$COMMENTMODEL->isPublic()}

{assign var=CONTENT value=$COMMENTMODEL->content()}

<table id="tbl{$UIKEY}_{$COMMENTID}" cellpadding="0" cellspacing="0" width="100%" class="small">
	<tr>
		<td class="dataImg" style="padding: 5px;">
			<input type="hidden" id="comment{$UIKEY}_lastchild_{$COMMENTID}" value="{$COMMENTMODEL->lastchildid()}" /> {* crmv@80503 *}
			<input type="hidden" id="comment{$UIKEY}_seen_{$COMMENTID}" value="{$SEEN}" /> {* crmv@80503 *}
			<img src="{$COMMENTMODEL->authorPhoto()}" alt="{$COMMENTMODEL->author()}" title="{$COMMENTMODEL->author()}" class="userAvatar" />
			<div style="text-align:center;vertical-align:middle;margin-top:10px;">
			{if $SEEN eq 'true'}
				<a href="javascript:;" title="{'LBL_SEEN_ACTION'|getTranslatedString:'Messages'}" onclick="VTE.ModCommentsCommon.setAsUnread('{$UIKEY}','{$COMMENTID}',{$INDICATOR});">
					<i class="vteicon md-sm">panorama_fish_eye</i>
				</a>
			{else}
				<a href="javascript:;" title="{'LBL_UNSEEN_ACTION'|getTranslatedString:'Messages'}" onclick="VTE.ModCommentsCommon.setAsRead('{$UIKEY}','{$COMMENTID}',{$INDICATOR});">
					<i class="vteicon md-sm">lens</i>
				</a>
			{/if}
			</div>
		</td>
		<td>
			<input type="hidden" id="listRecip_{$UIKEY}_{$COMMENTID}" name="listRecip_{$UIKEY}_{$COMMENTID}" value="{$COMMENTMODEL->getAllRecipients()|@implode:'|'}" />
			<div class="dataField{$UNSEEN_CLASS}" style="position: relative;" valign="top" onmouseover="{if $ISMINE}jQuery(this).find('.dataFieldReplyDel').show();{/if}" onmouseout="{if $ISMINE}jQuery(this).find('.dataFieldReplyDel').hide();{/if}" onclick="VTE.ModCommentsCommon.checkAndSetAsRead(this, '{$UIKEY}','{$COMMENTID}',{$INDICATOR});">
				{if $COMMENTMODEL->canDeletePost()} {* crmv@101967 *}
					<div class="dataFieldReplyDel" style="display: none; position: absolute; top: 3px; right: 3px;">
						<i class="vteicon md-sm md-link" title="{'LBL_DELETE'|getTranslatedString:$MODULE}" onclick="VTE.ModCommentsCommon.deleteComment('{$UIKEY}_{$COMMENTID}','{$COMMENTID}',{$INDICATOR});">clear</i>
					</div>
				{/if}
				<b>{$COMMENTMODEL->author()}</b>{$COMMENTMODEL->recipients()}{if !$ISPUBLIC} <span class="commentAddUserLink" ><a href="javascript:;" onclick="showUsersPopup(this, '{$COMMENTID}')">
					<i class="vteicon md-sm" title="{$APP.LBL_ADD_ITEM} {$APP.Users}">person_add</i>
				</a></span>{/if}
				{if $SHOWPREVIEW eq true}
					{assign var=TRUNCATE value=$CONTENT|truncateHtml:$COMMENTMODEL->previewCommentLength}
					{if $TRUNCATE eq $CONTENT}
						{$CONTENT}
					{else}
						<span id="contentSmall{$UIKEY}_{$COMMENTID}">{$TRUNCATE}</span><span id="contentShowFull{$UIKEY}_{$COMMENTID}">&nbsp;<a href="javascript:VTE.ModCommentsCommon.showFullContent('{$UIKEY}_{$COMMENTID}',{$SEEN},'{$UIKEY}','{$COMMENTID}',{$INDICATOR});">{'LBL_SHOW_FULL_COMMENT'|getTranslatedString:'ModComments'}</a></span>
						<span id="contentFull{$UIKEY}_{$COMMENTID}" style="display:none">{$CONTENT}</span>
					{/if}
				{else}
					{$CONTENT}
				{/if}
			</div>
			{assign var=relatedToStr value=$COMMENTMODEL->relatedToString()}
			<div class="dataLabel{$UNSEEN_CLASS}" valign="top" onclick="VTE.ModCommentsCommon.checkAndSetAsRead(this, '{$UIKEY}','{$COMMENTID}',{$INDICATOR});">
				<a href="javascript:;" title="{$COMMENTMODEL->timestamp()}" style="color: gray; text-decoration: none;">{$COMMENTMODEL->timestampAgo()}</a>
				{* crmv@98819 *}
				{if $relatedToStr neq ''}
					<input type="hidden" id="relatedTo_{$UIKEY}_{$COMMENTID}" value="{$relatedToStr.crmid}" /> {* crmv@179773 *}
					{'LBL_ABOUT'|getTranslatedString:'ModComments'}&nbsp;<a href="{$relatedToStr.url}" {if $SEEN eq 'false'}onClick="return VTE.ModCommentsCommon.setAsRead('{$UIKEY}','{$COMMENTID}',{$INDICATOR});"{/if} title="{$relatedToStr.entityType}" target="_parent">{$relatedToStr.displayValue}</a> ({$relatedToStr.entityType})
					<a href="{$relatedToStr.url}" {if $SEEN eq 'false'}onClick="return VTE.ModCommentsCommon.setAsRead('{$UIKEY}','{$COMMENTID}',{$INDICATOR});"{/if} title="{'LBL_DETACH_MESSAGE'|getTranslatedString:'Messages'}" target="_blank">
						<i class="vteicon md-sm" >open_in_new</i>
					</a>
				{else}
					&nbsp;<span class="commentAddLink"><a href="javascript:;" onclick="top.LPOP.openPopup('ModComments', '{$COMMENTID}', 'linkrecord', {ldelim}callback_link: 'commentsLinkModule',callback_create: 'commentsCreateModule', 'uikey': '{$UIKEY}' {rdelim}, window)">{"LBL_LINK_ACTION"|getTranslatedString:'Messages'}</a></span> {* crmv@43864 *}
				{/if}
				{* crmv@98819e *}
			</div>
			<div id="contentwrap_{$UIKEY}_{$COMMENTID}" class="ModCommReplies" valign="top" style="padding:3px 5px 0px 0px;display:block">
				{assign var="already_show_all" value="no"}
				{foreach name=reply item=REPLYMODEL from=$COMMENTMODEL->replies}
					{if $already_show_all eq 'no' && $smarty.foreach.reply.total > $REPLYMODEL->getMaxRepliesForComment()}
						<table id="showAll{$UIKEY}_{$COMMENTID}" cellpadding="0" cellspacing="0" width="100%" style="padding: 3px 5px 0px 0px;" onmouseover="jQuery(this).find('.dataFieldReplyDel').show();" onmouseout="jQuery(this).find('.dataFieldReplyDel').hide();">
							<tr>
								<td class="small dataFieldReply" style="padding: 5px;">
									<a href="javascript:;" onclick="showAllReplies('{$UIKEY}_{$COMMENTID}');">{$MOD.LBL_SHOW_ALL_REPLIES_1} {$smarty.foreach.reply.total} {$MOD.LBL_SHOW_ALL_REPLIES_2}</a>
								</td>
							</tr>
						</table>
						{assign var="already_show_all" value="yes"}
					{/if}
					{assign var="reverse_iteration" value=$smarty.foreach.reply.total-$smarty.foreach.reply.iteration+1}
					{include file="modules/ModComments/widgets/DetailViewBlockReplyItem.tpl"}
				{/foreach}
			</div>
			<div class="ModCommAnswerBox" valign="top" style="padding: 5px 5px 5px 0px;">
				<table cellpadding="0" cellspacing="0" width="100%">
					<tr>
						<td align="right" width="100%" class="dvtCellInfo">
							<textarea id="txtbox_{$UIKEY}_{$COMMENTMODEL->id()}" class="detailedViewTextBox detailedViewModCommTextBox" style="resize:vertical" onFocus="onModCommTextBoxFocus('txtbox_{$UIKEY}_{$COMMENTMODEL->id()}','{$UIKEY}_{$COMMENTMODEL->id()}','reply');" onBlur="onModCommTextBoxBlur('txtbox_{$UIKEY}_{$COMMENTMODEL->id()}','{$UIKEY}_{$COMMENTMODEL->id()}','reply');">{$DEFAULT_REPLY_TEXT}</textarea>
						</td>
					</tr>
					<tr>
						<td align="right">
							<div id="saveButtonRow_{$UIKEY}_{$COMMENTID}" style="display: none;">
								{if $NEWS_MODE eq 'yes'}
									{assign var="RELATED_TO" value=$COMMENTMODEL->relatedTo()}
								{else}
									{assign var="RELATED_TO" value=$ID}
								{/if}
								<input type="button" class="crmbutton small save" value="{$MOD.LBL_PUBLISH}" onclick="VTE.ModCommentsCommon.addReply('{$UIKEY}_{$COMMENTID}', '{$RELATED_TO}', '{$COMMENTID}', {$INDICATOR});"/>
							</div>
						</td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
	<tr><td colspan="2"><div class="divider"></div></td></tr>
</table>