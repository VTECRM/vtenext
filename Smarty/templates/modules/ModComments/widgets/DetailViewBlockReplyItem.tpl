{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@59626 *}
{* crmv@104853 *}

{if $reverse_iteration > $REPLYMODEL->getMaxRepliesForComment()}
	{assign var="reply_display" value="none"}
{else}
	{assign var="reply_display" value="block"}
{/if}
{if $UNSEEN_IDS|is_array && $REPLYMODEL->id()|in_array:$UNSEEN_IDS}
	{assign var="UNSEEN_CLASS" value=" ModCommUnseen"}
{else}
	{assign var="UNSEEN_CLASS" value=""}
{/if}
{assign var=REPLYCONTENT value=$REPLYMODEL->content()}
<table id="tbl{$UIKEY}_{$REPLYMODEL->id()}" class="small tbl_ModCommReplies" cellpadding="0" cellspacing="0" width="100%" style="padding: 3px 0px 0px 0px; display: {$reply_display};" {* onmouseover="jQuery(this).find('.dataFieldReplyDel').show();" onmouseout="jQuery(this).find('.dataFieldReplyDel').hide();" *}>
	<tr>
		<td class="dataImgReply">
			<img src="{$REPLYMODEL->authorPhoto()}" alt="{$REPLYMODEL->author()}" title="{$REPLYMODEL->author()}" class="userAvatar" />
		</td>
		<td width="100%" style="padding:0px" {if $REPLYMODEL->getCurrentUser() eq $REPLYMODEL->authorId()}onmouseover="jQuery(this).find('.dataFieldReplyDel').show();" onmouseout="jQuery(this).find('.dataFieldReplyDel').hide();"{/if}>
			<div class="dataFieldReply{$UNSEEN_CLASS}" style="position: relative;" valign="top" onclick="VTE.ModCommentsCommon.checkAndSetAsRead(this,'{$UIKEY}','{$COMMENTID}',{$INDICATOR});">
				{if $REPLYMODEL->canDeletePost()} {* crmv@101967 *}
					<div class="dataFieldReplyDel" style="display: none; position: absolute; top: 3px; right: 3px;">
						<i class="vteicon md-sm md-link" title="{'LBL_DELETE'|getTranslatedString:$MODULE}" onclick="VTE.ModCommentsCommon.deleteComment('{$UIKEY}_{$REPLYMODEL->id()}','{$REPLYMODEL->id()}',{$INDICATOR});">clear</i>
					</div>
				{/if}
				<b>{$REPLYMODEL->author()}</b>
				{if $SHOWPREVIEW eq true}
					{assign var=TRUNCATE value=$REPLYCONTENT|truncateHtml:$COMMENTMODEL->previewReplyLength}
					{if $TRUNCATE eq $REPLYCONTENT}
						{$REPLYCONTENT}
					{else}
						<span id="contentSmall{$UIKEY}_{$REPLYMODEL->id()}">{$TRUNCATE}</span><span id="contentShowFull{$UIKEY}_{$REPLYMODEL->id()}">&nbsp;<a href="javascript:VTE.ModCommentsCommon.showFullContent('{$UIKEY}_{$REPLYMODEL->id()}',{$SEEN},'{$UIKEY}','{$COMMENTID}',{$INDICATOR});">{'LBL_SHOW_FULL_COMMENT'|getTranslatedString:'ModComments'}</a></span>
						<span id="contentFull{$UIKEY}_{$REPLYMODEL->id()}" style="display:none">{$REPLYCONTENT}</span>
					{/if}
				{else}
					{$REPLYCONTENT}
				{/if}
			</div>
			<div class="dataLabelReply{$UNSEEN_CLASS}" valign="top" onclick="VTE.ModCommentsCommon.checkAndSetAsRead(this,'{$UIKEY}','{$COMMENTID}',{$INDICATOR});">
  				<a href="javascript:;" title="{$REPLYMODEL->timestamp()}" style="color: gray; text-decoration: none;">{$REPLYMODEL->timestampAgo()}</a>
			</div>
		</td>
	</tr>
</table>