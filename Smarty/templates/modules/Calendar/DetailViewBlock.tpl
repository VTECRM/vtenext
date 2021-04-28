{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *}

<table border="0" cellspacing="0" cellpadding="{if $OLD_STYLE eq true}2{else}5{/if}" width="100%" class="small">
	{* crmv@104568 *}
	{foreach item=detail from=$BLOCKS name='blocksIteration'}
		{assign var="header" value=$detail.label}
		{assign var="blockid" value=$detail.blockid}
	
	{* crmv@104568e *}
	{if $smarty.foreach.blocksIteration.iteration gt 1}
		<tr>{strip}<td><b>{$header}</b></td>{/strip}</tr>
	{/if}
		
	{assign var="fieldcount" value=0}
	{assign var="tr_state" value=0}
	{assign var="fieldstart" value=1}
	
	{foreach item=detail from=$detail.fields}
		{foreach key=label item=data from=$detail}
			{assign var=keyid value=$data.ui}
			{assign var=keyval value=$data.value}
			{assign var=keytblname value=$data.tablename}
			{assign var=keyfldname value=$data.fldname}
			{assign var=keyfldid value=$data.fldid}
			{assign var=keyoptions value=$data.options}
			{assign var=keysecid value=$data.secid}
			{assign var=keyseclink value=$data.link}
			{assign var=keycursymb value=$data.cursymb}
			{assign var=keysalut value=$data.salut}
			{assign var=keyaccess value=$data.notaccess}
			{assign var=keyadmin value=$data.isadmin}
			{assign var=keyreadonly value=$data.readonly}
			{assign var=display_type value=$data.displaytype}
			{assign var=keymandatory value=$data.mandatory}
			
			{if $keyreadonly eq 100}
				{* hidden *}
			{else}
			
			{assign var=fieldlength value=1}
			{if $keyid eq 19 || $keyid eq 69 || $keyid eq 210}
				{assign var=fieldlength value=2}
			{/if}
			{*  crmv@43764 *}
			{if $keyid eq 208 && $smarty.session.uitype208.$keyfldid.old_uitype eq '19'}
				{assign var=fieldlength value=2}
			{/if}
			{*  crmv@43764e *}
			{* crmv@OPER6288 *}
			{if $SUMMARY && $SHOW_KANBAN_BUTTONS eq 'true'}
				{assign var=fieldlength value=2}
			{/if}
			{* crmv@OPER6288e *}
				
			{if $keyfldname|in_array:$LONG_FIELDS}
				{assign var=fieldlength value=2}
			{/if}
				
		   	{if ($fieldcount eq 0 or $fieldstart eq 1) and $tr_state neq 1}	
		  		{if $fieldstart eq 1}
					{assign var="fieldstart" value=0}
				{/if}
		   		<tr style="height:25px" valign="top">
		   		{assign var="tr_state" value=1}
			{/if}
				
			{if $fieldlength eq 2 and $fieldcount neq 0}
				</tr>
				<tr style="height:25px">
				{assign var="tr_state" value=1}
				{assign var="fieldcount" value=0}
			{/if}
			{assign var="fieldcount" value=$fieldcount+1}
				
			{if $fieldlength eq 2}
				<td colspan="2" style="padding-top:5px">
			{else}
				<td width="50%" style="padding-top:5px">
			{/if}
				
			{if ($keyreadonly eq 99 or $EDIT_PERMISSION neq 'yes' or $display_type eq '2' or empty($DETAILVIEW_AJAX_EDIT))}
				{* readonly *}
				{assign var=READONLY value=true}
				{assign var="AJAXEDITTABLEPERM" value=false}
				{assign var="DIVCLASS" value="dvtCellInfoOff"}
			{else}
				{* visible and editable *}
				{assign var=READONLY value=false}
				{assign var="AJAXEDITTABLEPERM" value=true}
				{assign var="DIVCLASS" value="dvtCellInfo"}
			{/if}
				
			{* crmv@57221 *}
			{assign var="DIVCLASSOTHER" value=""}
			{if $OLD_STYLE eq true}
				{assign var="DIVCLASS" value=$DIVCLASS|cat:" dvtCellInfoOldStyle"}
				{assign var="DIVCLASSOTHER" value="dvtCellInfoOldStyle "}
			{/if}
			{* crmv@57221e *}
			{include file="DetailViewUI.tpl"}
				
			</td>
				
			{if $fieldlength eq 2}
				{assign var="fieldcount" value=$fieldcount+1}
			{/if}
					
		    {if $fieldcount eq 2}
				</tr>
				{assign var="fieldcount" value=0}
				{assign var="tr_state" value=0}
			{/if}
				
			{/if}
			{/foreach}
		{/foreach}
	{/foreach}
</table>