{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

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

{capture name="buttons"}

{include file="TurboliftButtons.tpl"}

{if $MODULE eq 'Accounts' || $MODULE eq 'Contacts' || $MODULE eq 'Leads'}
	<button type="button" id="receivingNewsletterButton1" class="crmbutton edit crmbutton-turbolift" onclick="lockUnlockReceivingNewsletter('{$ID}','lock');" {if $RECEIVINGNEWSLETTER eq false}style="display:none;"{/if}>
		{$APP.LBL_NEWSLETTER_UNSUB_ENABLE}
	</button>
	<button type="button" id="receivingNewsletterButton2" class="crmbutton edit crmbutton-turbolift" onclick="lockUnlockReceivingNewsletter('{$ID}','unlock');" {if $RECEIVINGNEWSLETTER eq true}style="display:none;"{/if}>
		{$APP.LBL_NEWSLETTER_UNSUB_DISABLE}
	</button>
{/if}

{if $MODULE eq 'HelpDesk' && $CONVERTASFAQ eq 'permitted'}
	<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="{$TURBOLIFT_HREF_TARGET_LOCATION}='index.php?return_module={$MODULE}&return_action=DetailView&record={$ID}&return_id={$ID}&module={$MODULE}&action=ConvertAsFAQ';">
		{$MOD.LBL_CONVERT_AS_FAQ_BUTTON_LABEL}
	</button>
{/if}

{if $MODULE eq 'Potentials' && $CONVERTINVOICE eq 'permitted'}
	<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="{$TURBOLIFT_HREF_TARGET_LOCATION}='index.php?return_module={$MODULE}&return_action=DetailView&return_id={$ID}&convertmode={$CONVERTMODE}&module=Invoice&action=EditView&account_id={$ACCOUNTID}';">
		{'Invoice'|getNewModuleLabel}
	</button>
{/if}

{if $MODULE eq 'Leads' && $CONVERTLEAD eq 'permitted'}
	<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="callConvertLeadDiv('{$ID}');">
		{$APP.LBL_CONVERT_BUTTON_LABEL}
	</button>
{/if}

{if $MODULE eq 'Documents'}
	{if ( $DLD_TYPE eq 'I' || $DLD_TYPE eq 'B' ) && $FILE_STATUS eq '1'} {* crmv@125060 *}
		<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="dldCntIncrease({$NOTESID});{$TURBOLIFT_HREF_TARGET_LOCATION}='index.php?module=uploads&action=downloadfile&fileid={$FILEID}&entityid={$NOTESID}';">
			{$MOD.LBL_DOWNLOAD_FILE}
		</button>
	{elseif $DLD_TYPE eq 'E' && $FILE_STATUS eq '1'}
		<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="dldCntIncrease({$NOTESID});{$TURBOLIFT_HREF_TARGET_LOCATION}='{$DLD_PATH}';">
			{$MOD.LBL_DOWNLOAD_FILE}
		</button>
	{/if}
	{if $CHECK_INTEGRITY_PERMISSION eq 'yes'}
		<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="checkFileIntegrityDetailView({$NOTESID});">
			{$MOD.LBL_CHECK_INTEGRITY}
		</button>
		<div class="integrity-container">
			<input type="hidden" id="dldfilename" name="dldfilename" value="{$FILEID}-{$FILENAME}">
			<span id="vtbusy_integrity_info" style="display:none;">{include file="LoadingIndicator.tpl"}</span>
			<span id="integrity_result" style="display:none"></span>
		</div>
	{/if}
	{if $DLD_TYPE eq 'I' || $DLD_TYPE eq 'B'} {* crmv@125060 *}
		<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="document.DetailView.return_module.value='Documents'; document.DetailView.return_action.value='DetailView'; document.DetailView.module.value='Documents'; document.DetailView.action.value='EmailFile'; document.DetailView.record.value={$NOTESID}; document.DetailView.return_id.value={$NOTESID}; sendfile_email();">
			{$MOD.LBL_EMAIL_FILE}
		</button>
		<div class="sendfile-container">
			<input type="hidden" id="dldfilename" name="dldfilename" value="{$FILEID}-{$FILENAME}">
		</div>
	{/if}
{/if}

{if $MODULE eq 'SalesOrder'}
	{if 'Invoice'|vtlib_isModuleActive}
		<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="document.DetailView.module.value='Invoice'; document.DetailView.action.value='EditView'; document.DetailView.return_module.value='SalesOrder'; document.DetailView.return_action.value='DetailView'; document.DetailView.return_id.value='{$ID}'; document.DetailView.record.value='{$ID}'; document.DetailView.convertmode.value='sotoinvoice'; document.DetailView.submit();">
			{'Invoice'|getNewModuleLabel}
		</button>
	{/if}
{/if}

{if $MODULE eq 'Quotes'}
	{if 'Invoice'|vtlib_isModuleActive}
		<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="document.DetailView.return_module.value='{$MODULE}'; document.DetailView.return_action.value='DetailView'; document.DetailView.convertmode.value='{$CONVERTMODE}'; document.DetailView.module.value='Invoice'; document.DetailView.action.value='EditView'; document.DetailView.return_id.value='{$ID}'; document.DetailView.submit();">
			{$APP.LBL_GENERATE} {$APP.Invoice}
		</button>
	{/if}
	{if 'SalesOrder'|vtlib_isModuleActive}
		<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="document.DetailView.return_module.value='{$MODULE}'; document.DetailView.return_action.value='DetailView'; document.DetailView.convertmode.value='quotetoso'; document.DetailView.module.value='SalesOrder'; document.DetailView.action.value='EditView'; document.DetailView.return_id.value='{$ID}'; document.DetailView.submit();">
			{$APP.LBL_GENERATE} {$APP.SalesOrder}
		</button>
	{/if}
{/if}

{* crmv@57221 *}
{if $OLD_STYLE eq false}
	{include file='CustomLinks.tpl' CUSTOM_LINK_TYPE="DETAILVIEWBASIC"}
	{include file='CustomLinks.tpl' CUSTOM_LINK_TYPE="DETAILVIEW"}
{/if}
{* crmv@57221e *}

{if $MODULE eq 'Users'}
	{if $IS_ADMIN eq 'true'}
		{* crmv@204903 *}
		<a href="index.php?module=Settings&action=AuditTrailList&parenttab=Settings&reset_session_menu=true&uid={$ID}" class="crmbutton edit crmbutton-turbolift" style="text-align: center;">
			{$MOD.LBL_VIEW_AUDIT_TRAIL}
		</a>
		{* crmv@204903e *}
	{/if}
	{if $CATEGORY eq 'Settings'}
		{$DUPLICATE_BUTTON}
	{/if}
	{* crmv@161368 *}
	{if $IS_ADMIN eq 'true'}
		<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="VTE.Users.confirmRemoteWipe('{$ID}');">
			{$MOD.LBL_REMOTE_WIPE}
		</button>
		{* crmv@164355 *}
		<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="exportAuditTrail();">
			{$MOD.LBL_EXPORT_AUDIT_TRAIL}
		</button>
		{* crmv@164355e *}
	{/if}
	{if $CATEGORY eq 'Settings' && $ID neq 1 && $ID neq $CURRENT_USERID}
		<button type="button" class="crmbutton delete crmbutton-turbolift" onclick="deleteUser({$ID});">
			{$APP.LBL_DELETE_BUTTON_LABEL}
		</button>
	{/if}
	{* crmv@161368e *}
{/if}

{if $MODULE neq 'MyNotes'}
	{if $EDIT_DUPLICATE eq 'permitted' && $MODULE neq 'Documents' && $MODULE neq 'Processes'}
		<button type="button" class="crmbutton edit crmbutton-turbolift" onclick="DetailView.return_module.value='{$MODULE}'; DetailView.return_action.value='DetailView'; DetailView.isDuplicate.value='true';DetailView.module.value='{$MODULE}'; submitFormForAction('DetailView','EditView');">
			{$APP.LBL_DUPLICATE_BUTTON_LABEL}
		</button>
	{/if}
	{if $DELETE eq 'permitted'}
		<button type="button" name="Delete" class="crmbutton delete crmbutton-turbolift" onclick="DetailView.return_module.value='{$MODULE}'; DetailView.return_action.value='index'; {if $MODULE eq 'Accounts'} var confirmMsg = '{$APP.NTC_ACCOUNT_DELETE_CONFIRMATION}' {else} var confirmMsg = '{$APP.NTC_DELETE_CONFIRMATION}' {/if}; submitFormForActionWithConfirmation('DetailView', 'Delete', confirmMsg);">
			{$APP.LBL_DELETE_BUTTON_LABEL}
		</button>
	{/if}
{/if}

{/capture}

<script type="text/javascript">var detailViewActionsContainer2length = 0;</script>

<div class="row">
	<div class="col-sm-12" id="detailViewActionsContainer1">{$smarty.capture.buttons}</div>
	{if $OLD_STYLE eq false}
		<div class="col-sm-6" id="detailViewActionsContainer2" style="display:none;padding-left:5px;">
			{include file='CustomLinks.tpl' CUSTOM_LINK_TYPE="DETAILVIEWWIDGET"}
		</div>
	{/if}
</div>

<script type="text/javascript">
if (window.detailViewActionsContainer2length > 0) {ldelim}
	jQuery('#detailViewActionsContainer').css('min-width', '500px');
	jQuery('#detailViewActionsContainer1').removeClass('col-sm-12').addClass('col-sm-6');
	jQuery('#detailViewActionsContainer2').show();
{rdelim}
</script>