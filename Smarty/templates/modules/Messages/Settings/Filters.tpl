{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@3082m *}

<form name="Filters" action="index.php">
	<input type="hidden" name="module" value="Messages">
	<input type="hidden" name="action" value="MessagesAjax">
	<input type="hidden" name="file" value="Settings/index">
	<input type="hidden" name="operation" value="">
	<input type="hidden" name="account" value="{$ACCOUNT}">
	
	{foreach item=filter from=$FILTER_LIST}
		<div class="cpanel_div" style="min-height: 0px;">
			<table border="0" cellpadding="0" cellspacing="5" width="100%">
				<tr>
					{if $filter.edit eq true}
						<td width="16"><a href="index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=EditFilter&account={$ACCOUNT}&sequence={$filter.sequence}">
							<i class="vteicon md-text" title="{'LBL_EDIT'|getTranslatedString}">create</i></a>
						</td>
					{/if}
					{if $filter.delete eq true}
						<td width="16"><a href="index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=DeleteFilter&account={$ACCOUNT}&sequence={$filter.sequence}">
							<i class="vteicon md-text" title="{'LBL_DELETE'|getTranslatedString}">delete</i></a>
						</td>
					{/if}
					<td width="48">
						{if $filter.move_down eq true}
							<a href="index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=MoveFilter&account={$ACCOUNT}&sequence={$filter.sequence}&to=down">
								<i class="vteicon md-text" title="{'DOWN'|getTranslatedString:'Settings'}">arrow_downward</i>
							</a>
						{/if}
						{if $filter.move_up eq true}
							<a href="index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=MoveFilter&account={$ACCOUNT}&sequence={$filter.sequence}&to=up">
								<i class="vteicon md-text" title="{'UP'|getTranslatedString:'Settings'}">arrow_upward</i>
							</a>
						{/if}
					</td>
					<td align="left" style="padding-left:10px;">
						{'LBL_FILTER_IF'|getTranslatedString} <b>{$WHERE_LIST[$filter.filter_where].label}</b> {'LBL_FILTER_CONTAIN'|getTranslatedString} <b>{$filter.filter_what}</b> {'LBL_FILTER_MOVE_IN'|getTranslatedString} <b>{$filter.filter_folder}</b>
					</td>
				</tr>
			</table>
		</div>
	{/foreach}
</form>