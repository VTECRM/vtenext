{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@80653 *} 
{if $sdk_mode eq 'detail'}
	{if $keyreadonly eq 99}
		{include file="DetailViewUI.tpl" keyid="17"} {* crmv@188803 *}
	{else}
		{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
		<div class="{$DIVCLASS}" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
			<span id="dtlview_{$label}"><a href="{$keyval}" target="_blank">{$keyval}</a></span>
			<div id="editarea_{$label}" style="display:none;">
				<input class="detailedViewTextBox" onblur="validateGenericUrl(this, '{$keyfldname}');" onpaste="var me = this; setTimeout(function() {ldelim} validateGenericUrl(me, '{$keyfldname}');{rdelim}, 100); return true;" type="text" id="txtbox_{$label}" name="{$keyfldname}" maxlength='200' value="{$keyval}" />
			</div>
		</div>
	{/if}
{elseif $sdk_mode eq 'edit'}
	{if $readonly eq 99}
		{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
		<div class="{$DIVCLASS}">
			<input type="hidden" name="{$fldname}" tabindex="{$vt_tab}" value="{$fldvalue}" tabindex="{$vt_tab}" class="detailedViewTextBox">
			{if $fldvalue neq ''}
				<a href="{$fldvalue}" target="_blank">{$fldvalue}</a>
			{/if}
		</div>
	{elseif $readonly eq 100}
		<input type="hidden" name="{$fldname}" id="{$fldname}" value="{$fldvalue}">
	{else}
		{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
		<div class="{$DIVCLASS}">
			<input type="text" tabindex="{$vt_tab}" name="{$fldname}" id="{$fldname}" value="{$fldvalue}" class="detailedViewTextBox" onblur="validateGenericUrl(this, '{$keyfldname}');" onpaste="var me = this; setTimeout(function() {ldelim} validateGenericUrl(me, '{$keyfldname}');{rdelim}, 100); return true;" />
		</div>
	{/if}
{/if}