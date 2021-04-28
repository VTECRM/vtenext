{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@42024 *}
<table width="100%" border="0" cellpadding="5" cellspacing="0" class="small" id="tax_table{$row_no}">
	<tr>
		<td id="tax_div_title{$row_no}" class="level3Bg" colspan="3" align="left" nowrap><b>{$APP.LABEL_SET_TAX_FOR}: {$data.$totalAfterDiscount|formatUserNumber}</b></td>
	</tr>

{foreach key=tax_row_no item=tax_data from=$data.taxes}

	{assign var="taxname" value=$tax_data.taxname|cat:"_percentage"|cat:$row_no}
	{* crmv@202324 *}
    {assign var="tmp_tax_row_no" value=$tax_row_no+1}
    {assign var="tax_id_name" value="hidden_tax"|cat:$tmp_tax_row_no|cat:"_percentage"|cat:$row_no}
    {* crmv@202324e *}
	{assign var="taxlabel" value=$tax_data.taxlabel|cat:"_percentage"|cat:$row_no}
	{assign var="popup_tax_rowname" value="popup_tax_row"|cat:$row_no|cat:"_"|cat:$tax_row_no}

	<tr>
		<td align="left" class="lineOnTop">
			<input type="text" class="detailedViewTextBox input-inline" size="5" name="{$taxname}" id="{$taxname}" value="{$tax_data.percentage|formatUserNumber}" onChange="calcCurrentTax('{$taxname}',{$row_no},{$tax_row_no});calcTotal();">&nbsp;% {*//crmv@43358*}
			<input type="hidden" id="{$tax_id_name}" value="{$taxname}">
		</td>
		<td align="center" class="lineOnTop">{$tax_data.taxlabel}</td>
		<td align="right" class="lineOnTop">
			<input type="text" class="detailedViewTextBox input-inline" size="6" name="{$popup_tax_rowname}" id="{$popup_tax_rowname}" value="{if $tax_data.taxtotal neq ''}{$tax_data.taxtotal|formatUserNumber}{else}{0.0|formatUserNumber}{/if}" readonly>
		</td>
	</tr>
{/foreach}

</table>

{if !is_array($data.taxes) || count($data.taxes) eq 0} {* crmv@167234 *}
	<div align="left" class="lineOnTop" width="100%">{$MOD.LBL_NO_TAXES_ASSOCIATED}</div>
{/if}

<input type="hidden" id="hdnTaxTotal{$row_no}" name="hdnTaxTotal{$row_no}" value="{if $data.$taxTotal neq ''}{$data.$taxTotal}{else}0{/if}">

<div class="closebutton" onClick="fnHidePopDiv('tax_div{$row_no}')"></div>