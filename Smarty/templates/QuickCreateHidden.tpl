{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 ********************************************************************************/

*}
<!-- crmv@16265 : QuickCreatePopup -->
{if $QUICKCREATEPOPUP eq true}
	<form name="QcEditView" id="QcEditView" method="POST" action="index.php">
{else}
	{* crmv@18625 - aggiunto ENCTYPE *}
	<form name="QcEditView" onSubmit="if(SubmitQCForm('{$MODULE}',this)) {ldelim} VteJS_DialogBox.block(); return true; {rdelim} else {ldelim} return false; {rdelim}" method="POST" action="index.php" ENCTYPE="multipart/form-data">
{/if}
<!-- crmv@16265e -->

{if $MODULE eq 'Calendar'}
	<input type="hidden" name="activity_mode" value="{$ACTIVITY_MODE}">
{elseif $MODULE eq 'Events'}
        <input type="hidden" name="activity_mode" value="{$ACTIVITY_MODE}">
{/if}
	<input type="hidden" name="record" value="">
<!-- crmv@16265 : QuickCreatePopup -->
	<input type="hidden" name="action" value="{$MODULE}Ajax">
	<input type="hidden" name="file" value="QuickCreate">
	<input type="hidden" name="mode" value="Save">
<!-- crmv@16265e -->
	<input type="hidden" name="module" value="{$MODULE}">
<!-- merge check - start -->
<input type="hidden" name="merge_check_fields" value="{$MERGE_USER_FIELDS}">
<!-- merge check - ends -->
<!-- crmv@16265 : QuickCreatePopup -->
<input type="hidden" name="quickcreatepopop" value="{$QUICKCREATEPOPUP}">
<!-- crmv@16265e -->
<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}