{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{*//Hidden fields for modules DetailView//  *}
<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
<input type="hidden" name="parenttab" value="{$CATEGORY}">
<input type="hidden"  name="allselectedboxes" id="allselectedboxes">
{if $MODULE eq 'Accounts'}
	<input type="hidden" name="module" value="{$MODULE}">
	<input type="hidden" name="record" value="{$ID}">
	<input type="hidden" name="isDuplicate" value=false>
	<input type="hidden" name="action">
	<input type="hidden" name="return_module">
	<input type="hidden" name="return_action">
	<input type="hidden" name="return_id">
	<input type="hidden" name="contact_id">
	<input type="hidden" name="member_id">
	<input type="hidden" name="opportunity_id">
	<input type="hidden" name="case_id">
	<input type="hidden" name="task_id">
	<input type="hidden" name="meeting_id">
	<input type="hidden" name="call_id">
	<input type="hidden" name="email_id">
	<input type="hidden" name="source_module">
	<input type="hidden" name="entity_id">
	{$HIDDEN_PARENTS_LIST}
{elseif $MODULE eq 'Contacts'}
	<input type="hidden" name="module" value="{$MODULE}">
	<input type="hidden" name="record" value="{$ID}">
	<input type="hidden" name="isDuplicate" value=false>
	<input type="hidden" name="action">
	<input type="hidden" name="return_module">
	<input type="hidden" name="return_action">
	<input type="hidden" name="return_id">
	<input type="hidden" name="reports_to_id">
	<input type="hidden" name="opportunity_id">
	<input type="hidden" name="contact_id" value="{$ID}">
	<input type="hidden" name="parent_id" value="{$ID}">
	<input type="hidden" name="contact_role">
	<input type="hidden" name="task_id">
	<input type="hidden" name="meeting_id">
	<input type="hidden" name="call_id">
	<input type="hidden" name="case_id">
	<input type="hidden" name="new_reports_to_id">
	<input type="hidden" name="email_directing_module">
	{$HIDDEN_PARENTS_LIST}
{elseif $MODULE eq 'Potentials'}
	<input type="hidden" name="module" value="{$MODULE}">
	<input type="hidden" name="record" value="{$ID}">
	<input type="hidden" name="isDuplicate" value=false>
	<input type="hidden" name="action">
	<input type="hidden" name="return_module">
	<input type="hidden" name="return_action">
	<input type="hidden" name="return_id" >
	<input type="hidden" name="contact_id">
	<input type="hidden" name="contact_role">
	<input type="hidden" name="opportunity_id" value="{$ID}">
	<input type="hidden" name="task_id">
	<input type="hidden" name="meeting_id">
	<input type="hidden" name="call_id">
	<input type="hidden" name="email_id">
	<input type="hidden" name="source_module">
	<input type="hidden" name="entity_id">
	<input type="hidden" name="convertmode">
	<input type="hidden" name="account_id" value="{$ACCOUNTID}">
{elseif $MODULE eq 'Leads'}
	<input type="hidden" name="module" value="{$MODULE}">
	<input type="hidden" name="record" value="{$ID}">
	<input type="hidden" name="isDuplicate" value=false>
	<input type="hidden" name="action">
	<input type="hidden" name="return_module">
	<input type="hidden" name="return_action">
	<input type="hidden" name="return_id">
	<input type="hidden" name="lead_id" value="{$ID}">
	<input type="hidden" name="parent_id" value="{$ID}">
	<input type="hidden" name="email_directing_module">
	{$HIDDEN_PARENTS_LIST}	
{elseif $MODULE eq 'Products' || $MODULE eq 'Vendors' || $MODULE eq 'PriceBooks' || $MODULE eq 'Services'}
	{if $MODULE eq 'Products'}
		<input type="hidden" name="product_id" value="{$id}">
	{elseif $MODULE eq 'Vendors'}
		<input type="hidden" name="vendor_id" value="{$id}">
	{/if}
	<input type="hidden" name="parent_id" value="{$id}">
	<input type="hidden" name="module" value="{$MODULE}">
	<input type="hidden" name="action">
	<input type="hidden" name="isDuplicate" value=false>
	<input type="hidden" name="mode">
	<input type="hidden" name="record" value="{$ID}">
	<input type="hidden" name="return_module" value="{$RETURN_MODULE}">
	<input type="hidden" name="return_id" value="{$RETURN_ID}">
	<input type="hidden" name="return_action" value="">
{elseif $MODULE eq 'Documents'}
	<input type="hidden" name="module" value="{$MODULE}">
	<input type="hidden" name="record" value="{$ID}">
	<input type="hidden" name="isDuplicate" value=false>
	<input type="hidden" name="action">
	<input type="hidden" name="return_module">
	<input type="hidden" name="return_action">
	<input type="hidden" name="return_id">
{elseif $MODULE eq 'Emails'}
	<input type="hidden" name="module" value="Emails">
	<input type="hidden" name="record" value="{$ID}">
	<input type="hidden" name="isDuplicate" value=false>
	<input type="hidden" name="action">
	<input type="hidden" name="contact_id" value="{$CONTACT_ID}">
	<input type="hidden" name="user_id" value="{$USER_ID}">
	<input type="hidden" name="return_module" value="{$RETURN_MODULE}">
	<input type="hidden" name="return_action" value="{$RETURN_ACTION}">
	<input type="hidden" name="return_id" value="{$RETURN_ID}">
	<input type="hidden" name="source_module">
	<input type="hidden" name="entity_id">
{elseif $MODULE eq 'HelpDesk'}
	<input type="hidden" name="module" value="{$MODULE}">
	<input type="hidden" name="action">
	<input type="hidden" name="mode">
	<input type="hidden" name="record" value="{$ID}">
	<input type="hidden" name="return_module" value="{$RETURN_MODULE}">
	<input type="hidden" name="return_id" value="{$RETURN_ID}">
	<input type="hidden" name="return_action" value="">
	<input type="hidden" name="isDuplicate">
	<input type="hidden" name="convertmode">
{elseif $MODULE eq 'Faq'}
	<input type="hidden" name="module" value="{$MODULE}">
	<input type="hidden" name="record" value="{$ID}">
	<input type="hidden" name="isDuplicate" value=false>
	<input type="hidden" name="action">
	<input type="hidden" name="return_module">
	<input type="hidden" name="return_action">
	<input type="hidden" name="return_id">
	<input type="hidden" name="source_module">
	<input type="hidden" name="entity_id">
{elseif $MODULE eq 'Quotes'}
	<input type="hidden" name="module" value="{$MODULE}">
	<input type="hidden" name="record" value="{$ID}">
	<input type="hidden" name="isDuplicate" value=false>
	<input type="hidden" name="action">
	<input type="hidden" name="convertmode">
	<input type="hidden" name="return_module">
	<input type="hidden" name="return_action">
	<input type="hidden" name="return_id">
	<input type="hidden" name="member_id">
	<input type="hidden" name="opportunity_id">
	<input type="hidden" name="case_id">
	<input type="hidden" name="task_id">
	<input type="hidden" name="meeting_id">
	<input type="hidden" name="call_id">
	<input type="hidden" name="email_id">
	<input type="hidden" name="source_module">
	<input type="hidden" name="entity_id">
	<!-- //ds@12 -->
	<input type="hidden" name="titleSite" value="on">
	<input type="hidden" name="placingOfOrder" value="on">
	<input type="hidden" name="directory" value="on">
	<input type="hidden" name="pdf_header" value="on">
	<input type="hidden" name="pdf_footer" value="on">  
	<input type="hidden" name="common" value="on"> 
	<input type="hidden" name="intro" value="on">		
	<!-- //ds@12e -->
{elseif $MODULE eq 'PurchaseOrder' || $MODULE eq 'SalesOrder' || $MODULE eq 'Invoice'}
	<input type="hidden" name="module" value="{$MODULE}">
	<input type="hidden" name="record" value="{$ID}">
	<input type="hidden" name="isDuplicate" value=false>
	<input type="hidden" name="action">
	<input type="hidden" name="return_module">
	<input type="hidden" name="return_action">
	<input type="hidden" name="return_id">
	<input type="hidden" name="member_id">
	<input type="hidden" name="opportunity_id">
	<input type="hidden" name="case_id">
	<input type="hidden" name="task_id">
	<input type="hidden" name="meeting_id">
	<input type="hidden" name="call_id">
	<input type="hidden" name="email_id">
	<input type="hidden" name="source_module">
	<input type="hidden" name="entity_id">
	{if $MODULE eq 'SalesOrder'}
		<input type="hidden" name="convertmode">
	{/if}
{elseif $MODULE eq 'Calendar'}
	<input type="hidden" name="module" value="Calendar">
	<input type="hidden" name="record" value="{$ID}">
	<input type="hidden" name="activity_mode" value="{$ACTIVITY_MODE}">
	<input type="hidden" name="isDuplicate" value=false>
	<input type="hidden" name="action">
	<input type="hidden" name="return_module">
	<input type="hidden" name="return_action">
	<input type="hidden" name="return_id">
	<input type="hidden" name="user_id" value="{$USER_ID}">
{elseif $MODULE eq 'Campaigns'}
	<input type="hidden" name="module" value="{$MODULE}">
	<input type="hidden" name="record" value="{$ID}">
	<input type="hidden" name="isDuplicate" value=false>
	<input type="hidden" name="action">
	<input type="hidden" name="return_module">
	<input type="hidden" name="return_action">
	<input type="hidden" name="return_id">
<!-- crmv@15771 -->	
{elseif $MODULE eq 'Projects'}
	<input type="hidden" name="mode">
	<input type="hidden" name="module" value="{$MODULE}">
	<input type="hidden" name="record" value="{$ID}">
	<input type="hidden" name="isDuplicate" value=false>
	<input type="hidden" name="action" value="{$ACTION}">
	<input type="hidden" name="return_module" value="{$MODULE}">
	<input type="hidden" name="return_action" value="{$ACTION}">
	<input type="hidden" name="return_id">
	<input type="hidden" name="mode_view" value="{$MODE_VIEW}">  
<!-- crmv@15771 end -->	
{else}
	<input type="hidden" name="module" value="{$MODULE}">
	<input type="hidden" name="record" value="{$ID}">
	<input type="hidden" name="isDuplicate" value=false>
	<input type="hidden" name="action">
	<input type="hidden" name="return_module">
	<input type="hidden" name="return_action">
	<input type="hidden" name="return_id">
{/if}
<input type="hidden" id="hdtxt_IsAdmin" value="{if $IS_ADMIN}1{else}0{/if}">	{* crmv@47567 *} {* crmv@181170 *}
<input type="hidden" name="focus_on_field"> {* crmv@198388 *}