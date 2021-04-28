{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@20176 *}	{* crmv@22659 *}
{if $MODULE == 'Accounts' || $MODULE == 'Quotes' || $MODULE == 'PurchaseOrder' || $MODULE == 'SalesOrder'|| $MODULE == 'Invoice'}
	{assign var="RIGHT_COPY" value=$APP.LBL_RCPY_ADDRESS}
	{assign var="LEFT_COPY" value=$APP.LBL_LCPY_ADDRESS}
	{assign var="TITLE_LEFT" value=$APP.LBL_BILLING_ADDRESS}
	{assign var="TITLE_RIGHT" value=$APP.LBL_SHIPPING_ADDRESS}
{elseif $MODULE == 'Contacts'}
	{assign var="RIGHT_COPY" value=$APP.LBL_CPY_OTHER_ADDRESS}
	{assign var="LEFT_COPY" value=$APP.LBL_CPY_MAILING_ADDRESS}
	{assign var="TITLE_LEFT" value=""}
	{assign var="TITLE_RIGHT" value=""}
{/if}
<td>
	<div class="dvInnerHeaderTitle">{$header}</div>
</td>
<td>
	<b>{$TITLE_LEFT}</b>
</td>
<td>
	{if $MODULE == 'Accounts' || $MODULE == 'Quotes' || $MODULE == 'PurchaseOrder' || $MODULE == 'SalesOrder'|| $MODULE == 'Invoice' || $MODULE == 'Contacts'}
		<a href="javascript:void(0);" title="{$RIGHT_COPY}">
			<i class="vteicon" onclick="return copyAddressLeft(EditView)">arrow_back</i>
		</a>
		<a href="javascript:void(0);" title="{$LEFT_COPY}">
			<i class="vteicon" onclick="return copyAddressRight(EditView)">arrow_forward</i>
		</a>
	{/if}
</td>
<td>
	<b>{$TITLE_RIGHT}</b>
</td>
{* crmv@20176e *}	{* crmv@22659e *}