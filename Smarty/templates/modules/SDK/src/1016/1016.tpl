{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@104567 *}
{* crmv@190827 *}

{literal}
<style>
	.signature-img-wrapper {
		background-color: #ffffff;
	}
	.signature-img {
		height: 250px;
	}
</style>
{/literal}
 
{if $sdk_mode eq 'detail'}
	<table width="100%">
		<tr>
			{if empty($keyval)}
				<td>{'NO_SIGNATURE_IMAGE'|getTranslatedString:'HelpDesk'}</td>
			{else}
				{assign var=now value=$smarty.now}
				<td align="center" valign="center" class="signature-img-wrapper">
					<img class="img-responsive signature-img" src="{$keyval}?t={$now}" id="{$keyfldname}" />
				</td>
			{/if}
		</tr>
	</table>
{elseif $sdk_mode eq 'edit'}
	<table width="100%">
		<tr>
			{if empty($keyval)}
				<td>{'NO_SIGNATURE_IMAGE'|getTranslatedString:'HelpDesk'}</td>
			{else}
				{assign var=now value=$smarty.now}
				<td align="center" valign="center" class="signature-img-wrapper">
					<img class="img-responsive signature-img" src="{$fldvalue}?t={$now}" id="{$fldname}" />
				</td>
			{/if}
		</tr>
	</table>
	<input type="hidden" name="{$fldname}" value="{$fldvalue}" />
{/if}