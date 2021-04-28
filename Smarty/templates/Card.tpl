{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@3085m crmv@3086m crmv@105588 *}

<div {if !empty($CARDID)}id="{$CARDID}"{/if} style="padding-top:5px;{if !empty($DISPLAY)}display:{$DISPLAY};{/if}" {if !empty($CARDONCLICK)}onClick="{$CARDONCLICK}"{/if}>
	<table cellspacing="0" cellpadding="0" width="100%" class="previewEntity" {if empty($CARDONCLICK)}style="cursor:auto;"{/if}>
		<tr>
			<td class="cardLabel" align="left">
				{if !empty($PREFIX)}
					<span class="gray vcenter">{$PREFIX}</span>
				{/if}
				<span class="vcenter">{$CARDNAME}</span>
			</td>
			<td class="cardIcon" align="right">
				{* crmv@98866 *}
				{assign var="moduleLower" value=$CARDMODULE|strtolower}
				{assign var="firstLetter" value=$CARDMODULE_LBL|substr:0:1|strtoupper}
				<div class="vcenter">{$CARDMODULE_LBL}</div>
				<div class="vcenter">
					<i class="icon-module icon-{$moduleLower}" data-first-letter="{$firstLetter}"></i>
				</div>
				{* crmv@98866e *}
			</td>
		</tr>
		{if !empty($CARDDETAILS)}
			<tr>
				<td colspan="2" class="cardContent">
					<table class="table borderless">
						{foreach key=fieldname item=detail from=$CARDDETAILS}
							<tr>
								<td>
									<span class="fieldLabel">{$detail.label}</span>
									&nbsp;
									<span class="fieldValue">{$detail.value}</span>
								</td>
							</tr>
						{/foreach}
					</table>
				</td>
			</tr>
		{/if}
	</table>
</div>