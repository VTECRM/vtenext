{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 ********************************************************************************/

*}
{* crmv@115268 : all forms with enctype multipart *}
{* crmv@139398 *}

{if $smarty.request.ajxaction eq 'DYNAFORMPOPUP'}
	{assign var="ON_SUBMIT" value="VteJS_DialogBox.progress();"}
{else}
	{assign var="ON_SUBMIT" value="VteJS_DialogBox.block();"}
{/if}

{if $MODULE eq 'Emails'}	
	<form name="EditView" method="POST" action="index.php" enctype="multipart/form-data" onsubmit="{$ON_SUBMIT}">
	<input type="hidden" name="form">
    <input type="hidden" name="send_mail">
    <input type="hidden" name="contact_id" value="{$CONTACT_ID}">
    <input type="hidden" name="user_id" value="{$USER_ID}">
    <input type="hidden" name="filename" value="{$FILENAME}">
    <input type="hidden" name="old_id" value="{$OLD_ID}">

{elseif $MODULE eq 'Contacts'}
	{$ERROR_MESSAGE}
	<form name="EditView" method="POST" action="index.php" enctype="multipart/form-data" onsubmit="{$ON_SUBMIT}">
	<input type="hidden" name="activity_mode" value="{$ACTIVITY_MODE}">
	<input type="hidden" name="opportunity_id" value="{$OPPORTUNITY_ID}">
	<input type="hidden" name="contact_role">
	<input type="hidden" name="case_id" value="{$CASE_ID}">
	<INPUT TYPE="HIDDEN" NAME="MAX_FILE_SIZE" VALUE="800000">
	<input type="hidden" name="campaignid" value="{$campaignid}">

{elseif $MODULE eq 'Potentials'}
	<form name="EditView" method="POST" action="index.php" enctype="multipart/form-data" onsubmit="{$ON_SUBMIT}">
	<input type="hidden" name="contact_id" value="{$CONTACT_ID}">

{elseif $MODULE eq 'Campaigns'}
	<form name="EditView" method="POST" action="index.php" enctype="multipart/form-data" onsubmit="{$ON_SUBMIT}">

{elseif $MODULE eq 'Calendar'}
	<input type="hidden" name="activity_mode" value="{$ACTIVITY_MODE}" enctype="multipart/form-data" onsubmit="{$ON_SUBMIT}">
	<input type="hidden" name="product_id" value="{$PRODUCTID}">

{elseif $MODULE|isInventoryModule}
	<form id="frmEditView" name="EditView" method="POST" action="index.php" enctype="multipart/form-data" onSubmit="settotalnoofrows();calcTotal();{$ON_SUBMIT}return VTE.Inventory.saveRecord(this);"> {* crmv@150748 *}
	<input type="hidden" name="hidImagePath" id="hidImagePath" value="{$IMAGE_PATH}"/>
	{if $MODULE eq 'Invoice' || $MODULE eq 'PurchaseOrder' ||  $MODULE eq 'SalesOrder'}
       	 <input type="hidden" name="convertmode">
	{/if}

{elseif $MODULE eq 'HelpDesk'}
	<form name="EditView" method="POST" action="index.php" enctype="multipart/form-data" onsubmit="{$ON_SUBMIT}">
	<input type="hidden" name="old_smownerid" value="{$OLDSMOWNERID}">
	<input type="hidden" name="old_id" value="{$OLD_ID}">

{elseif $MODULE eq 'Leads'}
	<form name="EditView" method="POST" action="index.php" enctype="multipart/form-data" onsubmit="{$ON_SUBMIT}">
	<input type="hidden" name="campaignid" value="{$campaignid}">

{* crmv@25346 *}
{elseif $MODULE eq 'Accounts'}
	<form name="EditView" method="POST" action="index.php" enctype="multipart/form-data" onsubmit="{$ON_SUBMIT}">
	<input type="hidden" name="address_change" value="">

{elseif $MODULE eq 'Faq' || $MODULE eq 'PriceBooks' || $MODULE eq 'Vendors'}
{* crmv@25346e *}
	<form name="EditView" method="POST" action="index.php" enctype="multipart/form-data" onsubmit="{$ON_SUBMIT}">

{elseif $MODULE eq 'Documents'}
	<form name="EditView" method="POST" action="index.php" enctype="multipart/form-data" onsubmit="{$ON_SUBMIT}">
	<input type="hidden" name="max_file_size" value="{$MAX_FILE_SIZE}">
	<input type="hidden" name="form">
	<input type="hidden" name="email_id" value="{$EMAILID}">
	<input type="hidden" name="ticket_id" value="{$TICKETID}">
	<input type="hidden" name="fileid" value="{$FILEID}">
	<input type="hidden" name="old_id" value="{$OLD_ID}">
	<input type="hidden" name="parentid" value="{$PARENTID}">

{elseif $MODULE eq 'Products'}
	{$ERROR_MESSAGE}
	<form name="EditView" method="POST" action="index.php" enctype="multipart/form-data" onsubmit="{$ON_SUBMIT}">
	<input type="hidden" name="activity_mode" value="{$ACTIVITY_MODE}">
	<INPUT TYPE="HIDDEN" NAME="MAX_FILE_SIZE" VALUE="800000">

{* crmv@3079m *}
{elseif $MODULE eq 'Myfiles'}
 	<form name="EditView" method="POST" action="index.php" enctype="multipart/form-data" onsubmit="{$ON_SUBMIT}">
	<input type="hidden" name="max_file_size" value="{$MAX_FILE_SIZE}">
	<input type="hidden" name="form">
	<input type="hidden" name="fileid" value="{$FILEID}">
	<input type="hidden" name="old_id" value="{$OLD_ID}">
	<input type="hidden" name="parentid" value="{$PARENTID}">   
{* crmv@3079me *}

{else}
	{$ERROR_MESSAGE}
	<form name="EditView" method="POST" action="index.php" enctype="multipart/form-data" onsubmit="{$ON_SUBMIT}">
{/if}

<input type="hidden" name="pagenumber" value="{$smarty.request.start|@vtlib_purify}">
<input type="hidden" name="module" value="{$MODULE}">
<input type="hidden" name="record" value="{$ID}">
<input type="hidden" name="mode" value="{$MODE}">
<input type="hidden" name="action">
<input type="hidden" name="parenttab" value="{$CATEGORY}">
<input type="hidden" name="return_module" value="{$RETURN_MODULE}">
<input type="hidden" name="return_id" value="{$RETURN_ID}">
<input type="hidden" name="return_action" value="{$RETURN_ACTION}">
<input type="hidden" name="return_viewname" value="{$RETURN_VIEWNAME}">
{* merge check - start *}
<input type="hidden" name="merge_check_fields" value="{$MERGE_USER_FIELDS}">
{* merge check - ends *}
{* crmv@19198 *}
<input type="hidden" name="isDuplicate" value="{$DUPLICATE}">
{if $DUPLICATE eq 'true'}
	<input type="hidden" name="isDuplicateFrom" value="{$smarty.request.record|@vtlib_purify}">
{/if}
{* crmv@19198e *}
<input type="hidden" name="return2detail">	{* crmv@54375 *}
<input type="hidden" name="run_processes">	{* crmv@100495 *}
<input type="hidden" name="editview_etag" id="editview_etag" value="{$smarty.request.editview_etag}"> {* crmv@175737 *}
<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}