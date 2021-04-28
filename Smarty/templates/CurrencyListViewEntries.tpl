{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

*}

<table width="100%" cellpadding="5" cellspacing="0" class="listTable">
	<tr>
		<td class="colHeader small" width="3%">#</td>
		<td class="colHeader small" width="9%">{$MOD.LBL_CURRENCY_TOOL}</td>
		<td class="colHeader small" width="23%">{$MOD.LBL_CURRENCY_NAME}</td>
		<td class="colHeader small" width="20%">{$MOD.LBL_CURRENCY_CODE}</td>
		<td class="colHeader small" width="10%">{$MOD.LBL_CURRENCY_SYMBOL}</td>
		<td class="colHeader small" width="20%">{$MOD.LBL_CURRENCY_CRATE}</td>
		<td class="colHeader small" width="15%">{$MOD.LBL_CURRENCY_STATUS}</td>
	</tr>
	{foreach item=currencyvalues name=currlist key=id from=$CURRENCY_LIST}
		<tr>
			<td nowrap class="listTableRow small" valign="top">{$smarty.foreach.currlist.iteration}</td>
			<td nowrap class="listTableRow small" valign="top">{$currencyvalues.tool}</td>
			<td nowrap class="listTableRow small" valign="top">
				<b>{$currencyvalues.name|@getTranslatedCurrencyString}</b></td>
			<td nowrap class="listTableRow small" valign="top">{$currencyvalues.code}</td>
			<td nowrap class="listTableRow small" valign="top">{$currencyvalues.symbol}</td>
			<td nowrap class="listTableRow small" valign="top">{$currencyvalues.crate}</td>
			{if $currencyvalues.status eq 'Active'}
				<td nowrap class="listTableRow small active" valign="top">{$currencyvalues.status}</td>
			{else}
				<td nowrap class="listTableRow small inactive" valign="top">{$currencyvalues.status}</td>
			{/if}
		</tr>
	{/foreach}
</table>

