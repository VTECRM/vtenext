{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@43194 *}
{* crmv@104853 *}
{if $UNSEEN_IDS|is_array && $COMMENTMODEL->id()|in_array:$UNSEEN_IDS}
	{assign var="UNSEEN_CLASS" value=" ModCommUnseen"}
{else}
	{assign var="UNSEEN_CLASS" value=""}
{/if}
<table id="tbl{$UIKEY}_{$COMMENTMODEL->id()}" class="notificationItem" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td valign="top" class="dataImg" style="padding:10px">
			<img src="{$COMMENTMODEL->authorPhoto()}" alt="{$COMMENTMODEL->author()}" title="{$COMMENTMODEL->author()}" class="userAvatar" />
			<div style="margin-top:10px">
				<i class="seenIcon vteicon md-sm" width="20px" style="cursor:pointer;{if $UNSEEN_CLASS neq ''}display:none{/if}" title="{'LBL_SEEN_ACTION'|getTranslatedString:'Messages'}" onclick="ModNotificationsCommon.markAsUnread('{$COMMENTMODEL->id()}', '{$UIKEY}')">panorama_fish_eye</i>
				<i class="unseenIcon vteicon md-sm" width="20px" style="cursor:pointer;{if $UNSEEN_CLASS eq ''}display:none{/if}" title="{'LBL_UNSEEN_ACTION'|getTranslatedString:'Messages'}" onclick="ModNotificationsCommon.markAsRead('{$COMMENTMODEL->id()}', '{$UIKEY}')">lens</i>
			</div>
		</td>
		<td valign="middle" class="dataContent{$UNSEEN_CLASS}" style="word-wrap:break-word">
			<div class="dataId">{$COMMENTMODEL->id()}</div>	{* crmv@30850 *}
			<div class="dataField{$UNSEEN_CLASS}">
				{assign var="AUTHOR" value=$COMMENTMODEL->author()}
				{if $AUTHOR neq ''}<b>{$AUTHOR}</b>&nbsp;{/if}{$COMMENTMODEL->content()}
			</div>
			<div class="dataLabel{$UNSEEN_CLASS}" style="padding-top:5px">
				<a href="javascript:;" title="{$COMMENTMODEL->timestamp()}" style="color:gray;text-decoration:none;">{$COMMENTMODEL->timestampAgo()}</a>
			</div>
		</td>
	</tr>
</table>