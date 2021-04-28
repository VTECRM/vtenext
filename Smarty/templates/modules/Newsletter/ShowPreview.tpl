{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@38592 *}

{assign var=fields value=$FOCUS.column_fields}

<div id="mailHeader">
	<table class="vtetable vtetable-props">
		<tbody>
			<tr>
				<td class="cellLabel">{"Subject"|getTranslatedString:'Messages'}:</td>
				<td class="cellText">{$TEMPLATEINFO.subject}</td>
			</tr>
			<tr>
				<td class="cellLabel">{"From"|getTranslatedString:'Messages'}:</td>
				<td class="cellText">{$NEWSLETTERINFO.from_address}</td>
			</tr>
			{if $TO_ADDRESS neq ''}
				<tr>
					<td class="cellLabel">{"To"|getTranslatedString:'Messages'}:</td>
					<td class="cellText">{if $TO_NAME neq ''}"{$TO_NAME}" {/if}&lt;{$TO_ADDRESS}&gt;</td>
				</tr>
			{/if}
		</table>
	</tbody>
</div>

<div id="messageBodyId" class="template-preview-body">
	{$TEMPLATEINFO.body}
</div>