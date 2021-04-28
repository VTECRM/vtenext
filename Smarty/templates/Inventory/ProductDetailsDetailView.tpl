{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@42024 *}
<div id="block_{$PRODBLOCKINFO.blockid}" class="vte-card detailBlock" style="{if $PANELID != $PRODBLOCKINFO.panelid}display:none{/if}"> {* crmv@104568 *}
<table width="100%" border="0" align="center" cellpadding="5" cellspacing="0" class="crmTable" id="proTab">
	<tr>
		<td class="text-nowrap" colspan="{$COLSPAN}"><div class="dvInnerHeader"><div class="dvInnerHeaderTitle">{$APP.LBL_ITEM_DETAILS}</div></div></td>
		<td class="text-center text-nowrap" colspan="2"><div class="dvInnerHeader"><div class="dvInnerHeaderTitle">{$APP.LBL_CURRENCY}:</div> {$CURRENCY_NAME} ({$CURRENCY_SYMBOL})</div></td>
		<td class="text-center text-nowrap" colspan="2"><div class="dvInnerHeader"><div class="dvInnerHeaderTitle">{$APP.LBL_TAX_MODE}:</div> {$APP.$TAXTYPE}</div></td>
	</tr>
	<tr>
		<td width=40% class="lvtCol"><font color="red">*</font> <b>{$APP.LBL_ITEM_NAME}</b></td>
		{if $MODULE neq 'PurchaseOrder'}
			<td width=10% class="lvtCol"><b>{$APP.LBL_QTY_IN_STOCK}</b></td>
		{/if}
		<td width=10% class="lvtCol"><b>{$APP.LBL_QTY}</b></td>
		<td width=10% class="lvtCol" align="right"><b>{$APP.LBL_LIST_PRICE}</b></td>
		<td width=12% nowrap class="lvtCol" align="right"><b>{$APP.LBL_TOTAL}</b></td>
		<td width=13% valign="top" class="lvtCol" align="right"><b>{$APP.LBL_NET_PRICE}</b></td>
	</tr>

	{foreach key=i item=PROD from=$PRODUCT_DETAILS}
	{assign var=PRODUCTID value="hdnProductId$i"}
	{assign var=PRODUCTID value=$PROD.$PRODUCTID}
	{assign var=entityType value="entityType$i"}
	{assign var=entityType value=$PROD.$entityType}

	{assign var=netPrice value="netPrice$i"}
	{assign var=hdnProductcode value="hdnProductcode$i"}
	{assign var=productName value="productName$i"}
	{assign var=subprod_names value="subprod_names$i"}
	{assign var=productDescription value="productDescription$i"}
	{assign var=comment value="comment$i"}
	{assign var=qtyInStock value="qtyInStock$i"}
	{assign var=qty value="qty$i"}
	{assign var=listPrice value="listPrice$i"}
	{assign var=productTotal value="productTotal$i"}
	{assign var=discountTotal value="discountTotal$i"}
	{assign var=totalAfterDiscount value="totalAfterDiscount$i"}
	{assign var=taxTotal value="taxTotal$i"}
	{assign var=discountInfoMessage value="discountInfoMessage$i"}
	{assign var=taxesInfoMessage value="taxesInfoMessage$i"}
	{assign var=margin value="margin$i"} {* crmv@44323 *}

	<tr valign="top">
		<td class="crmTableRow small lineOnTop">
			<span class="text-muted">{$PROD.$hdnProductcode}</span>
			<br>
			<a href="index.php?module={$entityType}&action=DetailView&record={$PRODUCTID}">{$PROD.$productName}</a>
			{if $PROD.$subprod_names neq ''}
				<br>
				<span class="text-muted">{$PROD.$subprod_names}</span>
			{/if}
			&nbsp;&nbsp;
			{if $entityType eq 'Services' && $MODULE eq 'SalesOrder'}
				{assign var=modstr value='SINGLE_ServiceContracts'|getTranslatedString:'ServiceContracts'}
				<a href="index.php?module=ServiceContracts&action=EditView&service_id={$PRODUCTID}&return_module={$MODULE}&return_id={$ID}&sorder_id={$ID}&sc_related_to={$ACCOUNTID}">	{* crmv@55225 *}
					<img border="0" src="{'handshake.gif'|resourcever}" title="{$APP.LBL_ADD_ITEM} {$modstr}" style="cursor: pointer;" align="absmiddle" />&nbsp; {$modstr[0]}
				</a>
			{/if}
			{if $entityType eq 'Products' && $MODULE eq 'SalesOrder'}
				{assign var=modstr value='SINGLE_Assets'|getTranslatedString:'Assets'}
				<a href="index.php?module=Assets&action=EditView&product={$PRODUCTID}&return_module={$MODULE}&return_id={$ID}&sorderid={$ID}&account={$ACCOUNTID}">
					<img border="0" src="{'handshake.gif'|resourcever}" title="{$APP.LBL_ADD_ITEM} {$modstr}" style="cursor: pointer;" align="absmiddle" />&nbsp; {$modstr[0]}
				</a>
			{/if}
			<br>
			<span class="text-muted">{$PROD.$productDescription}</span>
			<br>
			<span class="text-muted">{$PROD.$comment}</span>
		</td>

		{if $MODULE neq 'PurchaseOrder'}
			<td class="crmTableRow small lineOnTop">{$PROD.$qtyInStock|formatUserNumber}</td>
		{/if}

		<td class="crmTableRow small lineOnTop">{$PROD.$qty|formatUserNumber}</td>

		<td class="crmTableRow small lineOnTop" align="right">
			<table width="100%" border="0" cellpadding="5" cellspacing="0">
				<tr>
					<td align="right">{$PROD.$listPrice|formatUserNumber}</td>
				</tr>
				<tr>
					<td align="right">(-)&nbsp;<b><a href="javascript:void(0);" onclick="alert('{$PROD.$discountInfoMessage}');">{$APP.LBL_DISCOUNT} : </a></b></td>
				</tr>
				<tr>
					<td align="right" nowrap>{$APP.LBL_TOTAL_AFTER_DISCOUNT} : </td>
				</tr>
				{if $TAXTYPE eq 'individual'}
				<tr>
					<td align="right" nowrap>(+)&nbsp;<b><a href="javascript:void(0);" onclick="alert('{$PROD.$taxesInfoMessage}');">{$APP.LBL_TAX} : </a></b></td>
				</tr>
				{/if}
				{if $entityType eq 'Products'}
				<tr>
					<td align="right" nowrap>{$APP.LBL_MARGIN} :</td> {* crmv@44323 *}
				</tr>
				{/if}
			</table>
		</td>

		<td class="crmTableRow small lineOnTop" align="right">
			<table width="100%" border="0" cellpadding="5" cellspacing="0">
				<tr><td align="right">{$PROD.$productTotal|formatUserNumber}</td></tr>
				<tr><td align="right">{$PROD.$discountTotal|formatUserNumber}</td></tr>
				<tr><td align="right" nowrap>{$PROD.$totalAfterDiscount|formatUserNumber}</td></tr>
				{if $TAXTYPE eq 'individual'}
				<tr><td align="right" nowrap>{$PROD.$taxTotal|formatUserNumber}</td></tr>
				{/if}
				{if $entityType eq 'Products'}
				<tr><td align="right" nowrap>{if $PROD.$margin neq ''}{$PROD.$margin*100|round}%{else}0%{/if}</td></tr> {* crmv@44323 *}
				{/if}
			</table>
		</td>

		<td class="crmTableRow small lineOnTop" valign="bottom" align="right">{$PROD.$netPrice|formatUserNumber}</td>
	</tr>
	{/foreach}

</table>

{* ----- TOTALS ----- *}

<table width="100%" border="0" cellspacing="0" cellpadding="5" class="crmTable" id="finalProTab">
	<tr>
		<td width="88%" class="crmTableRow small" align="right"><b>{$APP.LBL_NET_TOTAL}</td>
		<td width="12%" class="crmTableRow small" align="right"><b>{$FINAL_DETAILS.hdnSubTotal|formatUserNumber}</b></td>
	</tr>
	<tr>
		<td align="right" class="crmTableRow small lineOnTop">(-)&nbsp;<b><a href="javascript:void(0);" onclick="alert('{$FINAL_DETAILS.discountInfoMessage}')" >{$APP.LBL_DISCOUNT}</a></b></td>
		<td align="right" class="crmTableRow small lineOnTop">{$FINAL_DETAILS.discountTotal_final|formatUserNumber}</td>
	</tr>
	{if $TAXTYPE eq 'group'}
	<tr>
		<td align="right" class="crmTableRow small">(+)&nbsp;<b><a href="javascript:;" onclick="alert('{$FINAL_DETAILS.taxesInfoMessage}');">{$APP.LBL_TAX}</a></b></td>
		<td align="right" class="crmTableRow small">{$FINAL_DETAILS.tax_totalamount|formatUserNumber}</td>
	</tr>
	{/if}
	<tr>
		<td align="right" class="crmTableRow small">(+)&nbsp;<b>{$APP.LBL_SHIPPING_AND_HANDLING_CHARGES}</b></td>
		<td align="right" class="crmTableRow small">{$FINAL_DETAILS.shipping_handling_charge|formatUserNumber}</td>
	</tr>
	<tr>
		<td align="right" class="crmTableRow small">(+)&nbsp;<b><a href="javascript:;" onclick="alert('{$FINAL_DETAILS.shtaxesInfoMessage}')">{$APP.LBL_TAX_FOR_SHIPPING_AND_HANDLING}</a></b></td>
		<td align="right" class="crmTableRow small">{$FINAL_DETAILS.shtax_totalamount|formatUserNumber}</td>
	</tr>
	<tr>
		<td align="right" class="crmTableRow small">&nbsp;<b>{$APP.LBL_ADJUSTMENT}</b></td>
		<td align="right" class="crmTableRow small">{$FINAL_DETAILS.adjustment|formatUserNumber}</td>
	</tr>
	<tr>
		<td align="right" class="crmTableRow small lineOnTop"><b>{$APP.LBL_GRAND_TOTAL}</b></td>
		<td align="right" class="crmTableRow small lineOnTop">{$FINAL_DETAILS.grandTotal|formatUserNumber}</td>
	</tr>
</table>

</div> {* crmv@104568 *}