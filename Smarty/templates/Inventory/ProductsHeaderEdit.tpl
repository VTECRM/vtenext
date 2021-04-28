{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@42024 *}
<tr>
   	{if $MODULE neq 'PurchaseOrder'}
		<td class="text-nowrap" colspan="3">
	{else}
		<td class="text-nowrap" colspan="2">
	{/if}
		<div class="dvInnerHeader"><div class="dvInnerHeaderTitle">{$APP.LBL_ITEM_DETAILS}</div></div>
	</td>

	<td class="text-center text-nowrap" colspan="2">
		<input type="hidden" value="{$INV_CURRENCY_ID}" id="prev_selected_currency_id" />
		<div class="dvInnerHeader">
			<div class="dvInnerHeaderTitle">{$APP.LBL_CURRENCY}</div>&nbsp;&nbsp;
			<select class="detailedViewTextBox input-inline" id="inventory_currency" name="inventory_currency" onchange="updatePrices();">
			{foreach item=currency_details key=count from=$CURRENCIES_LIST}
				{if $currency_details.curid eq $INV_CURRENCY_ID}
					{assign var=currency_selected value="selected"}
				{else}
					{assign var=currency_selected value=""}
				{/if}
				<option value="{$currency_details.curid}" {$currency_selected}>{$currency_details.currencylabel|@getTranslatedCurrencyString} ({$currency_details.currencysymbol})</option>
			{/foreach}
			</select>
		</div>
	</td>

	<td class="text-center text-nowrap" colspan="2">
		<div class="dvInnerHeader">
			<div class="dvInnerHeaderTitle">{$APP.LBL_TAX_MODE}</div>&nbsp;&nbsp;
			{if $TAXTYPE eq 'group'}	{* crmv@50153 *}
				{assign var="group_selected" value="selected"}
			{else}
				{assign var="individual_selected" value="selected"}
			{/if}
			<select class="detailedViewTextBox input-inline" id="taxtype" name="taxtype" onchange="decideTaxDiv(); calcTotal();">
				<option value="individual" {$individual_selected}>{$APP.LBL_INDIVIDUAL}</option>
				<option value="group" {$group_selected}>{$APP.LBL_GROUP}</option> {* crmv@42024 *} {* crmv@50153 *}
			</select>
		</div>
	</td>
   </tr>

	<!-- Header for the Product Details -->
   <tr valign="top">
	<td width=5% valign="top" class="lvtCol" align="right"><b>{$APP.LBL_TOOLS}</b></td>
	<td width=40% class="lvtCol"><font color='red'>*</font><b>{$APP.LBL_ITEM_NAME}</b></td>
	{if $MODULE neq 'PurchaseOrder'}
		<td width=10% class="lvtCol"><b>{$APP.LBL_QTY_IN_STOCK}</b></td>
	{/if}
	<td width=10% class="lvtCol"><b>{$APP.LBL_QTY}</b></td>
	<td width=10% class="lvtCol" align="right"><b>{$APP.LBL_LIST_PRICE}</b></td>
	<td width=12% nowrap class="lvtCol" align="right"><b>{$APP.LBL_TOTAL}</b></td>
	<td width=13% valign="top" class="lvtCol" align="right"><b>{$APP.LBL_NET_PRICE}</b></td>
</tr>