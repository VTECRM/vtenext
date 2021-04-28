{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 ********************************************************************************/

*}
{* crmv@26896 *}
{if $RELATED_LIST_HIDDEN_FORM_NAME eq ''}
	{assign var=RELATED_LIST_HIDDEN_FORM_NAME value=form}
{/if}
<form border="0" action="index.php" method="post" name="{$RELATED_LIST_HIDDEN_FORM_NAME}" id="{$RELATED_LIST_HIDDEN_FORM_NAME}">
{* crmv@26896e *}
<input type="hidden" name="module">
<input type="hidden" name="mode">
<input type="hidden" name="return_module" id="return_module" value="{$MODULE}">
<input type="hidden" name="RLreturn_module" id="RLreturn_module" value="{$MODULE}">
<input type="hidden" name="allselectedboxes"  id="allselectedboxes">
{if $SinglePane_View eq true}{* crmv@203484 *}
	<input type="hidden" name="return_action" value="DetailView">
{else}
	<input type="hidden" name="return_action" value="CallRelatedList">
{/if}
<input type="hidden" name="return_id" value="{$ID}">
<input type="hidden" name="parent_id" id="parent_id" value="{$ID}">
<input type="hidden" name="RLparent_id" id="RLparent_id" value="{$ID}">
<input type="hidden" name="parenttab" id="parenttab" value="{$CATEGORY}">
<input type="hidden" name="action">
{* crmv@21249 *}
{if $MODULE eq 'Calendar'}
	<input type="hidden" name="activity_mode" value="{$ACTIVITY_MODE}">
	<input type="hidden" name="user_id" value="{$USER_ID}">
	<input type="hidden" name="record" value="{$ID}">
{elseif $MODULE eq 'Accounts'}
{* crmv@21249e *}
        <input type="hidden" name="account_id" value="{$ID}">
	{$HIDDEN_PARENTS_LIST}
{elseif $MODULE eq 'Contacts'}
        <input type="hidden" name="contact_id" value="{$ID}">
        <input type="hidden" name="account_id" value="{$accountid}">
	<input type="hidden" name="campaignid" value="{$campaignid}">
	{$HIDDEN_PARENTS_LIST}
{elseif $MODULE eq 'Campaigns'}
        <input type="hidden" name="campaignid" value="{$ID}">
{elseif $MODULE eq 'Leads'}
	<input type="hidden" name="lead_id" value="{$ID}">
	<input type="hidden" name="campaignid" value="{$campaignid}">
	{$HIDDEN_PARENTS_LIST}
{elseif $MODULE eq 'Potentials'}
        <input type="hidden" name="potential_id" value="{$ID}">
{elseif $MODULE eq 'Quotes'}
        <input type="hidden" name="quoteid" value="{$ID}">
{elseif $MODULE eq 'SalesOrder'}
        <input type="hidden" name="salesorderid" value="{$ID}">
{elseif $MODULE eq 'PurchaseOrder'}
	<input type="hidden" name="purchaseorderid" value="{$ID}">
{elseif $MODULE eq 'Invoice'}
        <input type="hidden" name="invoiceid" value="{$ID}">
{elseif $MODULE eq 'Products'}
        <input type="hidden" name="product_id" value="{$ID}">
{elseif $MODULE eq 'Vendors'}
	<input type="hidden" name="vendor_id" value="{$ID}">
{elseif $MODULE eq 'PriceBooks'}
	<input type="hidden" name="pricebook_id" value="{$ID}">
{elseif $MODULE eq 'Emails'}
        <input type="hidden" name="email_id" value="{$ID}">
	<input type="hidden" name="user_id" value="{$USER_ID}">
	<input type="hidden" name="record" value="{$ID}">
{elseif $MODULE eq 'HelpDesk'}
        <input type="hidden" name="ticket_id" value="{$ID}">
{/if}
<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@205899 *}