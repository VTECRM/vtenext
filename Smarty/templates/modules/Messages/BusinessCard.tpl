{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@192843 *}

{if $BUSINESS_CARD neq ''}
	<table cellspacing="0" cellpadding="2" width="100%" class="small">
		{if $BUSINESS_CARD.module eq 'Users'}
			{if $BUSINESS_CARD.title neq ''}
				<tr>
					<td>
						<i class="vteicon md-sm valgn-middle" title="{'Role'|getTranslatedString:'Users'}">bookmark</i>
					</td>
					<td>
						<b>{$BUSINESS_CARD.title}</b>
					</td>
				</tr>
			{/if}
		{elseif $BUSINESS_CARD.module eq 'Contacts'}
			{if $BUSINESS_CARD.accountname neq ''}
				<tr>
					<td colspan="2">
						<b><a href="javascript:;" onClick="preView('Accounts','{$BUSINESS_CARD.accountid}');">{$BUSINESS_CARD.accountname}</a></b>
					</td>
				</tr>
			{/if}
		{elseif $BUSINESS_CARD.module eq 'Accounts'}
			{if $BUSINESS_CARD.bill_city neq ''}
				<tr>
					<td colspan="2">
						{$BUSINESS_CARD.bill_city}
					</td>
				</tr>
			{/if}
		{elseif $BUSINESS_CARD.module eq 'Leads'}
			{if $BUSINESS_CARD.company neq ''}
				<tr>
					<td colspan="2">
						{$BUSINESS_CARD.company}
					</td>
				</tr>
			{/if}
			{if $BUSINESS_CARD.leadsource neq ''}
				<tr>
					<td>
						<i class="vteicon md-sm valgn-middle" title="{'Lead Source'|getTranslatedString:'Leads'}">input</i>
					</td>
					<td>
						{$BUSINESS_CARD.leadsource}
					</td>
				</tr>
			{/if}
		{elseif $BUSINESS_CARD.module eq 'Vendors'}
			{if $BUSINESS_CARD.website neq ''}
				<tr>
					<td colspan="2">
						<a href="http://{$BUSINESS_CARD.website}" target="_blank">{$BUSINESS_CARD.website}</a>
					</td>
				</tr>
			{/if}
		{/if}
		{if !empty($BUSINESS_CARD.phone)}
			<tr valign="top">
				<td width="15">
					<i class="vteicon md-sm valgn-middle" title="{'Phone'|getTranslatedString}">phone</i>
				</td>
				<td>
					{foreach item=phone from=$BUSINESS_CARD.phone}
						{foreach item=value from=$phone.value}
							{if ''|@get_use_asterisk eq 'true'}
								<a href='javascript:;' title="{$phone.label}" onclick='startCall("{$value}", "{$BUSINESS_CARD.id}")'>{$value}</a>
							{else}
								<span title="{$phone.label}">{$value}</span>
							{/if}
							<br />
						{/foreach}
					{/foreach}
				</td>
			</tr>
		{/if}
	</table>
{/if}