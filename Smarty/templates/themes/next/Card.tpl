{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@152802 *}

{if $TURBOLIFTCARD eq true}
<div class="card previewEntity turbolift-card" style="{if !empty($CARDONCLICK)}cursor: pointer;{/if}" {if !empty($CARDID)}id="{$CARDID}"{/if} style="{if !empty($DISPLAY)}display:{$DISPLAY};{/if}" {if !empty($CARDONCLICK)}onclick="{$CARDONCLICK}"{/if}>
{else}
<div class="card previewEntity" style="width:auto; {if !empty($CARDONCLICK)}cursor: pointer;{/if}" {if !empty($CARDID)}id="{$CARDID}"{/if} style="{if !empty($DISPLAY)}display:{$DISPLAY};{/if}" {if !empty($CARDONCLICK)}onClick="{$CARDONCLICK}"{/if}> {* crmv@160359 *}
{/if}
    <div class="card-body">
        <h4 class="card-title">
			{if !empty($PREFIX)}
				<span class="gray vcenter">{$PREFIX}</span>
			{/if}
			{* crmv@98866 *}
			{assign var="moduleLower" value=$CARDMODULE|strtolower}
			{assign var="firstLetter" value=$CARDMODULE_LBL|substr:0:1|strtoupper}
			<div class="vcenter">
				{if $TURBOLIFTCARD neq true}<a class="goPanelGoGo" href="{$CARDLINK}">{/if} {* crmv@176751 *}
				<i class="icon-module icon-{$moduleLower}" data-first-letter="{$firstLetter}" data-toggle="tooltip" data-placement="top" data-original-title="{$CARDMODULE_LBL}"></i>
				{if $TURBOLIFTCARD neq true}</a>{/if}
			</div>
			{* crmv@98866e *}
			<span class="vcenter">
				{if $TURBOLIFTCARD neq true}<a class="goPanelGoGo" href="{$CARDLINK}">{/if} {* crmv@176751 *}
				{$CARDNAME}
				{if $TURBOLIFTCARD neq true}</a>{/if}
			</span>
			{if $TURBOLIFTCARD neq true}
				<div class="vcenter" style="float:right">
					<a href="{$CARDLINK}" target="_blank"><i class="vteicon md-sm md-text">open_in_new</i></a> {* crmv@176751 *}
				</div>
			{/if}
		</h4>
        {if !empty($CARDDETAILS)}
        	<p class="card-text">
				{foreach key=fieldname item=detail from=$CARDDETAILS}
					<span class="fieldLabel">{$detail.label}</span>
					&nbsp;
					<span class="fieldValue">{$detail.value}</span>
					<br>
				{/foreach}
			</p>
        {/if}
    </div>
</div>