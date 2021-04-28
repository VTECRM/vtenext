{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@103863 *}
<div style="float:right;">
	{if $DETAILVIEWBUTTONSPERM.edit}
		<div style="float:left;">
			<button class="crmbutton with-icon edit" type="button" onclick="javascript:OpenCompose('{$ID}','draft');">
				<i class="vteicon md-text md-sm" title="{'LBL_EDIT_BUTTON'|@getTranslatedString:$MODULE}">create</i>
				{'LBL_EDIT_BUTTON'|@getTranslatedString:$MODULE}
			</button>
		</div>
	{/if}
	{if $DETAILVIEWBUTTONSPERM.reply}
		<div style="float:left;">
			<button class="crmbutton with-icon edit" type="button" onclick="javascript:OpenCompose('{$ID}','reply');">
				<i class="vteicon md-text md-sm" title="{'LBL_REPLY_ACTION'|@getTranslatedString:$MODULE}">reply</i>
				{'LBL_REPLY_ACTION'|@getTranslatedString:$MODULE}
			</button>
		</div>
	{/if}
	{if $DETAILVIEWBUTTONSPERM.reply_all}
		<div style="float:left;">
			<button class="crmbutton with-icon edit" type="button" onclick="javascript:OpenCompose('{$ID}','reply_all');">
				<i class="vteicon md-text md-sm" title="{'LBL_REPLY_ALL_ACTION'|@getTranslatedString:$MODULE}">reply_all</i>
				{'LBL_REPLY_ALL_ACTION'|@getTranslatedString:$MODULE}
			</button>
		</div>
	{/if}
	{if $DETAILVIEWBUTTONSPERM.forward}
		<div style="float:left;">
			<button class="crmbutton with-icon edit" type="button" onclick="javascript:OpenCompose('{$ID}','forward');">
				<i class="vteicon md-text md-sm" title="{'LBL_FORWARD_ACTION'|@getTranslatedString:$MODULE}">forward</i>
				{'LBL_FORWARD_ACTION'|@getTranslatedString:$MODULE}
			</button>
		</div>
	{/if}
	<div style="float:left;padding:5px">
		<table border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td>
				{if $DETAILVIEWBUTTONSPERM.seen}
					{if $FLAG_BLOCK.seen.value eq 'no'|@getTranslatedString:$MODULE}
						<a href="javascript:;" onClick="flag({$ID},'seen',1);"><i class="vteicon" title="{'LBL_UNSEEN_ACTION'|@getTranslatedString:$MODULE}">markunread</i></a>
					{else}
						<a href="javascript:;" onClick="flag({$ID},'seen',0);"><i class="vteicon" title="{'LBL_SEEN_ACTION'|@getTranslatedString:$MODULE}">drafts</i></a>
					{/if}
				{/if}
				</td>
				<td>
				{if $DETAILVIEWBUTTONSPERM.flagged}
					{if $FLAG_BLOCK.flagged.value eq 'no'|@getTranslatedString:$MODULE}
						<a href="javascript:;" onClick="flag({$ID},'flagged',1);"><i class="vteicon" title="{'LBL_UNFLAGGED_ACTION'|@getTranslatedString:$MODULE}">flag</i></a>
					{else}
						<a href="javascript:;" onClick="flag({$ID},'flagged',0);"><i class="vteicon" title="{'LBL_FLAGGED_ACTION'|@getTranslatedString:$MODULE}" style="color:red">flag</i></a>
					{/if}
				{/if}
				</td>
				<td>
				{if $DETAILVIEWBUTTONSPERM.move}
					<a href="javascript:;" onClick="MoveDisplay(this,'single','{$ID}');"><i class="vteicon" title="{'LBL_MOVE_ACTION'|@getTranslatedString:$MODULE}" border="0">move_to_inbox</i></a>
				{/if}
				</td>
				<td>
				{* crmv@46601 *}
				{if $DETAILVIEWBUTTONSPERM.spam}
					{if $DETAILVIEWBUTTONSPERM.spam_status eq 'configure'}
						<a href="javascript:;" onClick="if (confirm('{'LBL_CONFIGURE_SPAM'|getTranslatedString:'Messages'}')) openPopup('index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Folders','','','auto',600,500);"><i class="vteicon" title="{'LBL_SPAM_ACTION'|@getTranslatedString:$MODULE}" border="0">whatshot</i></a>
					{elseif $DETAILVIEWBUTTONSPERM.spam_status eq 'off'}
						<a href="javascript:;" onClick="Move('{$SPECIAL_FOLDERS.Spam}',{$ID});"><i class="vteicon" title="{'LBL_SPAM_ACTION'|@getTranslatedString:$MODULE}" border="0">whatshot</i></a>
					{else if $DETAILVIEWBUTTONSPERM.spam_status eq 'on'}
						<a href="javascript:;" onClick="Move('{$SPECIAL_FOLDERS.INBOX}',{$ID});"><i class="vteicon" title="{'LBL_UNSPAM_ACTION'|@getTranslatedString:$MODULE}" style="color:red" border="0">whatshot</i></a>
					{/if}
				{/if}
				{* crmv@46601e *}
				</td>
				<td>
				{if $DETAILVIEWBUTTONSPERM.delete}
					<a href="javascript:;" onClick="flag({$ID},'delete');"><i class="vteicon" title="{'LBL_TRASH_ACTION'|@getTranslatedString:$MODULE}">delete</i></a>
				{/if}
				</td>
			</tr>
		</table>
	</div>
</div>