{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<input type="hidden" id="{$UIKEY}_unseen_ids" value="{','|implode:$UNSEEN_IDS}" />
<input type="hidden" id="{$UIKEY}_max_number_of_news" value="{$CRITERIA}" />
{assign var="INDICATOR" value="document.getElementById('indicator"|cat:$UIKEY|cat:"')"} {* crmv@192033 *}

<table class="small" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td>
		<div id="contentwrap_{$UIKEY}" style="width: 100%;">
			{foreach item=COMMENTMODEL from=$COMMENTS}
				{include file="modules/ModNotifications/widgets/DetailViewBlockCommentItem.tpl" COMMENTMODEL=$COMMENTMODEL}
			{/foreach}
		</div>
	</td>
</tr>
{if $TOTAL > $CRITERIA}
	<tr>
		<td class="ModCommAnswerBox" align="center" style="padding: 3px 0px;">
			(1-{$CRITERIA} {'LBL_OF'|getTranslatedString:'Settings'} {$TOTAL})&nbsp;<a href="javascript:ModNotificationsCommon.loadModNotifications({$CRITERIA}+eval(ModNotificationsCommon.default_number_of_news),'{$smarty.request.target_frame}','{$smarty.request.indicator}');">{'LBL_SHOW_OTHER_NOTIFICATIONS'|getTranslatedString:'ModNotifications'}</a>
		</td>
	</tr>
{/if}
</table>