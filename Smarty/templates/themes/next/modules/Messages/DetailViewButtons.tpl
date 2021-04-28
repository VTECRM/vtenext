{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

<script language="JavaScript" type="text/javascript" src="modules/Emails/Emails.js"></script> {* crmv@187622 *}

<ul class="vteUlTable" style="text-align:right;">
	{if $DETAILVIEWBUTTONSPERM.edit}
		<li>
			<div class="circle" onclick="javascript:OpenCompose('{$ID}','draft');">
				<i class="vteicon md-sm md-link" data-toggle="tooltip" data-placement="bottom" title="{'LBL_EDIT_BUTTON'|@getTranslatedString:$MODULE}">create</i>
			</div>
		</li>
	{/if}
	{* crmv@187622 *}
	{if $DETAILVIEWBUTTONSPERM.send}
		<li>
			<div class="circle" onclick="javascript:sendNow('{$ID}');">
				<i class="vteicon md-sm md-link" data-toggle="tooltip" data-placement="bottom" title="{'LBL_SEND_NOW_BUTTON'|@getTranslatedString:$MODULE}">send</i>
			</div>
		</li>
	{/if}
	{if $DETAILVIEWBUTTONSPERM.schedule}
		<li>
			<div class="circle" onclick="javascript:ScheduleSending.showOptions(false,'{$ID}');">
				<i class="vteicon md-sm md-link" data-toggle="tooltip" data-placement="bottom" title="{'LBL_SCHEDULE_SENDING'|@getTranslatedString:'Emails'}">event</i>
			</div>
		</li>
	{/if}
	{* crmv@187622e *}
	{if $DETAILVIEWBUTTONSPERM.reply}
		<li>
			<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="javascript:OpenCompose('{$ID}','reply');">
				<i class="vteicon md-sm md-link" data-toggle="tooltip" data-placement="bottom" title="{'LBL_REPLY_ACTION'|@getTranslatedString:$MODULE}">reply</i>
			</button>
		</li>
	{/if}
	{if $DETAILVIEWBUTTONSPERM.reply_all}
		<li>
			<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="javascript:OpenCompose('{$ID}','reply_all');">
				<i class="vteicon md-sm md-link" data-toggle="tooltip" data-placement="bottom" title="{'LBL_REPLY_ALL_ACTION'|@getTranslatedString:$MODULE}">reply_all</i>
			</button>
		</li>
	{/if}
	{if $DETAILVIEWBUTTONSPERM.forward}
		<li>
			<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="javascript:OpenCompose('{$ID}','forward');">
				<i class="vteicon md-sm md-link" data-toggle="tooltip" data-placement="bottom" title="{'LBL_FORWARD_ACTION'|@getTranslatedString:$MODULE}">forward</i>
			</button>
		</li>
	{/if}
	{if $DETAILVIEWBUTTONSPERM.seen}
		<li style="margin-left:20px;">
			{if $FLAG_BLOCK.seen.value eq 'no'|@getTranslatedString:$MODULE}
				<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="flag({$ID},'seen',1);">
					<i class="vteicon" data-toggle="tooltip" data-placement="bottom" title="{'LBL_UNSEEN_ACTION'|@getTranslatedString:$MODULE}">markunread</i>
				</button>
			{else}
				<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="flag({$ID},'seen',0);">
					<i class="vteicon" data-toggle="tooltip" data-placement="bottom" title="{'LBL_SEEN_ACTION'|@getTranslatedString:$MODULE}">drafts</i>
				</button>
			{/if}
		</li>
	{/if}
	{if $DETAILVIEWBUTTONSPERM.flagged}
		<li>
			{if $FLAG_BLOCK.flagged.value eq 'no'|@getTranslatedString:$MODULE}
				<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="flag({$ID},'flagged',1);">
					<i class="vteicon" data-toggle="tooltip" data-placement="bottom" title="{'LBL_UNFLAGGED_ACTION'|@getTranslatedString:$MODULE}">flag</i>
				</button>
			{else}
				<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="flag({$ID},'flagged',0);">
					<i class="vteicon" data-toggle="tooltip" data-placement="bottom" title="{'LBL_FLAGGED_ACTION'|@getTranslatedString:$MODULE}" style="color:red">flag</i>
				</button>
			{/if}
		</li>
	{/if}
	{if $DETAILVIEWBUTTONSPERM.move}
		<li>
			<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="MoveDisplay(this,'single','{$ID}');">
				<i class="vteicon" data-toggle="tooltip" data-placement="bottom" title="{'LBL_MOVE_ACTION'|@getTranslatedString:$MODULE}">move_to_inbox</i>
			</button>
		</li>
	{/if}
	{* crmv@46601 *}
	{if $DETAILVIEWBUTTONSPERM.spam}
		<li>
			{if $DETAILVIEWBUTTONSPERM.spam_status eq 'configure'}
				<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="if (confirm('{'LBL_CONFIGURE_SPAM'|getTranslatedString:'Messages'}')) openPopup('index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Folders','','','auto',600,500);">
					<i class="vteicon" data-toggle="tooltip" data-placement="bottom" title="{'LBL_SPAM_ACTION'|@getTranslatedString:$MODULE}">whatshot</i>
				</button>
			{elseif $DETAILVIEWBUTTONSPERM.spam_status eq 'off'}
				<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="Move('{$SPECIAL_FOLDERS.Spam}',{$ID});">
					<i class="vteicon" data-toggle="tooltip" data-placement="bottom" title="{'LBL_SPAM_ACTION'|@getTranslatedString:$MODULE}">whatshot</i>
				</button>					
			{else if $DETAILVIEWBUTTONSPERM.spam_status eq 'on'}
				<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="Move('{$SPECIAL_FOLDERS.INBOX}',{$ID});">
					<i class="vteicon" data-toggle="tooltip" data-placement="bottom" title="{'LBL_UNSPAM_ACTION'|@getTranslatedString:$MODULE}" style="color:red">whatshot</i>
				</button>
			{/if}
		</li>
	{/if}
	{* crmv@46601e *}
	{if $DETAILVIEWBUTTONSPERM.delete}
		<li>
			<button type="button" class="crmbutton only-icon delete crmbutton-nav" onClick="flag({$ID},'delete');">
				<i class="vteicon" data-toggle="tooltip" data-placement="bottom" title="{'LBL_TRASH_ACTION'|@getTranslatedString:$MODULE}">delete</i>
			</button>
		</li>
	{/if}
</ul>