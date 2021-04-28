{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@42752 crmv@43864 crmv@52912 *}

<script type="text/javascript">
function detailviewLinksToggle(title,load){ldelim}
	var label_button;
	var image_button;
	if(getObj(load).style.display == 'none'){ldelim}
		label_button = '{$APP.LBL_FEWER_BUTTON}';
		image_button = '{"activate.gif"|resourcever}';
	{rdelim}else{ldelim}
		label_button = '{$APP.LBL_MORE}';
		image_button = '{"inactivate.gif"|resourcever}';
	{rdelim}
	jQuery('#'+load).toggle();
	jQuery('#'+title).html(label_button+'<div style="float:right;"><img border="0" src="'+image_button+'" /></div>');
{rdelim}

var resizedetailViewActionsContainer = function() {ldelim}
	var top = parseInt(jQuery('#detailViewActionsContainer').css('top'));
	var b_margin = jQuery('#detailViewActionsContainer').height() - top;
	var w_height = jQuery(window).height() - top - 40;
	if (b_margin > w_height)
		jQuery('#detailViewActionsContainer').children().css('max-height',w_height);
{rdelim}
</script>

<table cellpadding="0" cellspacing="0" width="100%">
<tr>
<td width="{if $OLD_STYLE eq true}100{else}50{/if}%" valign="top" id="detailViewActionsContainer1">	{* crmv@57221 *}

{include file="TurboliftButtons.tpl"}

{* crmv@55961 *}
{if $MODULE eq 'Accounts' || $MODULE eq 'Contacts' || $MODULE eq 'Leads'}
	<div class="turboliftEntry1 btn" id="receivingNewsletterButton1" onClick="lockUnlockReceivingNewsletter('{$ID}','lock');" {if $RECEIVINGNEWSLETTER eq false}style="display:none;"{/if}>
		<div>
			{$APP.LBL_NEWSLETTER_UNSUB_ENABLE}
		</div>
	</div>
	<div class="turboliftEntry1 btn" id="receivingNewsletterButton2" onClick="lockUnlockReceivingNewsletter('{$ID}','unlock');" {if $RECEIVINGNEWSLETTER eq true}style="display:none;"{/if}>
		<div>
			{$APP.LBL_NEWSLETTER_UNSUB_DISABLE}
		</div>
	</div>
{/if}
{* crmv@55961e *}

{if $MODULE eq 'HelpDesk' && $CONVERTASFAQ eq 'permitted'}
	<div class="turboliftEntry1 btn" onClick="{$TURBOLIFT_HREF_TARGET_LOCATION}='index.php?return_module={$MODULE}&return_action=DetailView&record={$ID}&return_id={$ID}&module={$MODULE}&action=ConvertAsFAQ';">
		<div>
			{$MOD.LBL_CONVERT_AS_FAQ_BUTTON_LABEL}
		</div>
	</div>
{/if}

{if $MODULE eq 'Potentials' && $CONVERTINVOICE eq 'permitted'}
	<div class="turboliftEntry1 btn" onClick="{$TURBOLIFT_HREF_TARGET_LOCATION}='index.php?return_module={$MODULE}&return_action=DetailView&return_id={$ID}&convertmode={$CONVERTMODE}&module=Invoice&action=EditView&account_id={$ACCOUNTID}';">
		<div>
			{'Invoice'|getNewModuleLabel}
		</div>
	</div>
{/if}

{if $MODULE eq 'Leads' && $CONVERTLEAD eq 'permitted'}
	<div class="turboliftEntry1 btn" onClick="releaseOverAll('detailViewActionsContainer'); callConvertLeadDiv('{$ID}');">
		<div>
			{$APP.LBL_CONVERT_BUTTON_LABEL}
		</div>
	</div>
{/if}

<!-- Start: Actions for Documents Module -->
{if $MODULE eq 'Documents'}
	{if ( $DLD_TYPE eq 'I' || $DLD_TYPE eq 'B' ) && $FILE_STATUS eq '1'} {* crmv@125060 *}
		<div class="turboliftEntry1 btn" onClick="releaseOverAll('detailViewActionsContainer'); dldCntIncrease({$NOTESID});{$TURBOLIFT_HREF_TARGET_LOCATION}='index.php?module=uploads&action=downloadfile&fileid={$FILEID}&entityid={$NOTESID}';">
			<div>
				{$MOD.LBL_DOWNLOAD_FILE}
			</div>
		</div>
	{elseif $DLD_TYPE eq 'E' && $FILE_STATUS eq '1'}
		<div class="turboliftEntry1 btn" onClick="releaseOverAll('detailViewActionsContainer'); dldCntIncrease({$NOTESID});{$TURBOLIFT_HREF_TARGET_LOCATION}='{$DLD_PATH}';">
			<div>
				{$MOD.LBL_DOWNLOAD_FILE}
			</div>
		</div>
	{/if}
	{if $CHECK_INTEGRITY_PERMISSION eq 'yes'}
		<div class="turboliftEntry1 btn" onClick="releaseOverAll('detailViewActionsContainer'); checkFileIntegrityDetailView({$NOTESID});">
			<div>
				{$MOD.LBL_CHECK_INTEGRITY}
				<input type="hidden" id="dldfilename" name="dldfilename" value="{$FILEID}-{$FILENAME}">
				<span id="vtbusy_integrity_info" style="display:none;">{include file="LoadingIndicator.tpl"}</span>
				<span id="integrity_result" style="display:none"></span>
			</div>
		</div>
	{/if}
	{if $DLD_TYPE eq 'I' || $DLD_TYPE eq 'B'} {* crmv@125060 *}
		<div class="turboliftEntry1 btn" onClick="releaseOverAll('detailViewActionsContainer'); document.DetailView.return_module.value='Documents'; document.DetailView.return_action.value='DetailView'; document.DetailView.module.value='Documents'; document.DetailView.action.value='EmailFile'; document.DetailView.record.value={$NOTESID}; document.DetailView.return_id.value={$NOTESID}; sendfile_email();">
			<div>
				{$MOD.LBL_EMAIL_FILE}
				<input type="hidden" id="dldfilename" name="dldfilename" value="{$FILEID}-{$FILENAME}">	<!-- //crmv@16312 -->
			</div>
		</div>
	{/if}
{/if}
<!-- End: Actions for Documents Module -->

{if $MODULE eq 'SalesOrder'}
	{if 'Invoice'|vtlib_isModuleActive}
		<div class="turboliftEntry1 btn" onClick="document.DetailView.module.value='Invoice'; document.DetailView.action.value='EditView'; document.DetailView.return_module.value='SalesOrder'; document.DetailView.return_action.value='DetailView'; document.DetailView.return_id.value='{$ID}'; document.DetailView.record.value='{$ID}'; document.DetailView.convertmode.value='sotoinvoice'; document.DetailView.submit();">
			<div>
				{'Invoice'|getNewModuleLabel}
			</div>
		</div>
	{/if}
{/if}

{if $MODULE eq 'Quotes'}
	{if 'Invoice'|vtlib_isModuleActive}
		<div class="turboliftEntry1 btn" onClick="document.DetailView.return_module.value='{$MODULE}'; document.DetailView.return_action.value='DetailView'; document.DetailView.convertmode.value='{$CONVERTMODE}'; document.DetailView.module.value='Invoice'; document.DetailView.action.value='EditView'; document.DetailView.return_id.value='{$ID}'; document.DetailView.submit();">
			<div>
				{$APP.LBL_GENERATE} {$APP.Invoice}
			</div>
		</div>
	{/if}
	{if 'SalesOrder'|vtlib_isModuleActive}
		<div class="turboliftEntry1 btn" onClick="document.DetailView.return_module.value='{$MODULE}'; document.DetailView.return_action.value='DetailView'; document.DetailView.convertmode.value='quotetoso'; document.DetailView.module.value='SalesOrder'; document.DetailView.action.value='EditView'; document.DetailView.return_id.value='{$ID}'; document.DetailView.submit();">
			<div>
				{$APP.LBL_GENERATE} {$APP.SalesOrder}
			</div>
		</div>
	{/if}
{/if}

{* crmv@57221 *}
{if $OLD_STYLE eq false}
	{include file='CustomLinks.tpl' CUSTOM_LINK_TYPE="DETAILVIEWBASIC"}
	{include file='CustomLinks.tpl' CUSTOM_LINK_TYPE="DETAILVIEW"}
{/if}
{* crmv@57221e *}

{* crmv@20054 *}
{if $MODULE eq 'Users'}
	{if $IS_ADMIN eq 'true'}
		{* crmv@204903 *}
		<div class="turboliftEntry1 btn">
			<a href="index.php?module=Settings&action=AuditTrailList&parenttab=Settings&reset_session_menu=true&uid={$ID}">
				{$MOD.LBL_VIEW_AUDIT_TRAIL}
			</a>
		</div>
		{* crmv@204903e *}
		{* crmv@164355 *}
		<div class="turboliftEntry1 btn" onclick="exportAuditTrail();">
			<div>
				{$MOD.LBL_EXPORT_AUDIT_TRAIL}
			</div>
		</div>
		{* crmv@164355e *}
	{/if}
	{if $CATEGORY eq 'Settings'}
		{$DUPLICATE_BUTTON}
	{/if}
	{* crmv@161368 *}
	{if $IS_ADMIN eq 'true'}
		<div class="turboliftEntry1 btn delete" onclick="VTE.Users.confirmRemoteWipe('{$ID}');">
			<div>
				{$MOD.LBL_REMOTE_WIPE}
			</div>
		</div>
	{/if}
	{if $CATEGORY eq 'Settings' && $ID neq 1 && $ID neq $CURRENT_USERID}
		<div class="turboliftEntry1 btn delete" onclick="deleteUser({$ID});" title="{$APP.LBL_DELETE_BUTTON_TITLE}" accessKey="{$APP.LBL_DELETE_BUTTON_KEY}">
			<div>
				{$APP.LBL_DELETE_BUTTON_LABEL}
			</div>
		</div>
	{/if}
	{* crmv@161368e *}
{/if}
{* crmv@20054e *}

{if $MODULE neq 'MyNotes'}
	{if $EDIT_DUPLICATE eq 'permitted' && $MODULE neq 'Documents' && $MODULE neq 'Processes'}
		<div class="turboliftEntry1 btn" title="{$APP.LBL_DUPLICATE_BUTTON_TITLE}" accessKey="{$APP.LBL_DUPLICATE_BUTTON_KEY}" onclick="DetailView.return_module.value='{$MODULE}'; DetailView.return_action.value='DetailView'; DetailView.isDuplicate.value='true';DetailView.module.value='{$MODULE}'; submitFormForAction('DetailView','EditView');">
			<div>
				{$APP.LBL_DUPLICATE_BUTTON_LABEL}
			</div>
		</div>
	{/if}
	{if $DELETE eq 'permitted'}
		<input title="{$APP.LBL_DELETE_BUTTON_TITLE}" accessKey="{$APP.LBL_DELETE_BUTTON_KEY}" class="btn delete turboliftEntry1" onclick="DetailView.return_module.value='{$MODULE}'; DetailView.return_action.value='index'; {if $MODULE eq 'Accounts'} var confirmMsg = '{$APP.NTC_ACCOUNT_DELETE_CONFIRMATION}' {elseif $MODULE eq 'Contacts'} var confirmMsg = '{$APP.NTC_CONTACT_DELETE_CONFIRMATION}' {else} var confirmMsg = '{$APP.NTC_DELETE_CONFIRMATION}' {/if}; submitFormForActionWithConfirmation('DetailView', 'Delete', confirmMsg);" type="button" name="Delete" value="{$APP.LBL_DELETE_BUTTON_LABEL}">	{* crmv@144123 *}
	{/if}
{/if}

</td>

{* crmv@57221 *}
<script type="text/javascript">var detailViewActionsContainer2length = 0;</script>
{if $OLD_STYLE eq false}
	<td width="50%" valign="top" id="detailViewActionsContainer2" style="display:none; padding-left:5px;">
		{include file='CustomLinks.tpl' CUSTOM_LINK_TYPE="DETAILVIEWWIDGET"}
	</td>
{/if}
{* crmv@57221e *}

</tr>
</table>

<script type="text/javascript">
if (window.detailViewActionsContainer2length > 0) {ldelim}
	jQuery('#detailViewActionsContainer').width('40%');
	jQuery('#detailViewActionsContainer2').show();
{rdelim}
</script>