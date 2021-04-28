{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@155145 *}
<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
<tr>
	<td class="big"><strong>{$CMOD.LBL_FPOFV_CONTITIONS_LIST}</strong></td>
	<td class="small" align=right>&nbsp;</td>
</tr>
</table>

<table border=0 cellspacing=0 cellpadding=5 width=100% class="listTableTopButtons">
<tr>
	<td width="85%"></td>
	<td align="right" width="5%" id="conditionalsVersion" nowrap>
		{include file='modules/Conditionals/Version.tpl'}
	</td>
	<td width="10%" class=small align=right>
		<form action="index.php" method="post" name="EditView" id="form">
		<input type='hidden' name='module' value='Conditionals'>
		<input type='hidden' name='action' value='EditView'>
		<input type='hidden' name='return_action' value='index'>
		<input type='hidden' name='return_module' value='Conditionals'> 
		<input type='hidden' name='parenttab' value='Settings'>
		<input title="{$CMOD.LBL_NEW_FPOFV_BUTTON_TITLE}" accessyKey="{$CMOD.LBL_NEW_FPOFV_BUTTON_KEY}" type="submit" name="button" value="{$CMOD.LBL_NEW_FPOFV_BUTTON_LABEL}" class="crmButton create small">
		</form>
	</td>
</tr>

{if $ERROR_MSG neq ''}
<tr>
	{$ERROR_MSG}
</tr>
{/if}
</table>

<table border=0 cellspacing=0 cellpadding=5 width=100% class="listTable">
<tr>
	<td class="colHeader small" valign=top>#</td>
	{if $LIST_HEADER.1 neq ''}<td class="colHeader small" valign=top>{$LIST_HEADER.1}</td>{/if}	
	{if $LIST_HEADER.2 neq ''}<td class="colHeader small" valign=top>{$LIST_HEADER.2}</td>{/if}
	{if $LIST_HEADER.3 neq ''}<td class="colHeader small" valign=top>{$LIST_HEADER.3}</td>{/if}
	{if $LIST_HEADER.4 neq ''}<td class="colHeader small" valign=top>{$LIST_HEADER.4}</td>{/if}
	{if $LIST_HEADER.5 neq ''}<td class="colHeader small" valign=top>{$LIST_HEADER.5}</td>{/if}
	{if $LIST_HEADER.6 neq ''}<td class="colHeader small" valign=top>{$LIST_HEADER.6}</td>{/if}
	{if $LIST_HEADER.7 neq ''}<td class="colHeader small" valign=top>{$LIST_HEADER.7}</td>{/if}
	{if $LIST_HEADER.8 neq ''}<td class="colHeader small" valign=top>{$LIST_HEADER.8}</td>{/if}
	{if $LIST_HEADER.9 neq ''}<td class="colHeader small" valign=top>{$LIST_HEADER.9}</td>{/if}
	{if $LIST_HEADER.10 neq ''}<td class="colHeader small" valign=top>{$LIST_HEADER.10}</td>{/if}
	{if $LIST_HEADER.11 neq ''}<td class="colHeader small" valign=top>{$LIST_HEADER.11}</td>{/if}
</tr>
	{foreach name=workflowlist item=listvalues key=userid from=$LIST_ENTRIES}
<tr>
	<td class="listTableRow small" valign=top>{$smarty.foreach.workflowlist.iteration}</td>
	{if $LIST_HEADER.1 neq ''}<td class="listTableRow small" valign=top>{$listvalues.1}&nbsp;</td>{/if}	
	{if $LIST_HEADER.2 neq ''}<td class="listTableRow small" valign=top>{$listvalues.2}&nbsp;</td>{/if}
	{if $LIST_HEADER.3 neq ''}<td class="listTableRow small" valign=top>{$listvalues.3}&nbsp;</td>{/if}
	{if $LIST_HEADER.4 neq ''}<td class="listTableRow small" valign=top>{$listvalues.4}&nbsp;</td>{/if}
	{if $LIST_HEADER.5 neq ''}<td class="listTableRow small" valign=top>{$listvalues.5}&nbsp;</td>{/if}
	{if $LIST_HEADER.6 neq ''}<td class="listTableRow small" valign=top>{$listvalues.6}&nbsp;</td>{/if}
	{if $LIST_HEADER.7 neq ''}<td class="listTableRow small" valign=top>{$listvalues.7}&nbsp;</td>{/if}
	{if $LIST_HEADER.8 neq ''}<td class="listTableRow small" valign=top>{$listvalues.8}&nbsp;</td>{/if}
	{if $LIST_HEADER.9 neq ''}<td class="listTableRow small" valign=top>{$listvalues.9}&nbsp;</td>{/if}
	{if $LIST_HEADER.10 neq ''}<td class="listTableRow small" valign=top>{$listvalues.12}&nbsp;</td>{/if}
	{if $LIST_HEADER.11 neq ''}<td class="listTableRow small" valign=top>{$listvalues.13}&nbsp;</td>{/if}
</tr>
	{/foreach}
</table>
{include file='Settings/ScrollTop.tpl'}