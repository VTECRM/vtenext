{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@70304 restyled*}

{if $sdk_mode eq 'detail'}
	{* force readonly *}
	{assign var=READONLY value=true}
	{assign var="AJAXEDITTABLEPERM" value=false}
	{assign var="DIVCLASS" value="dvtCellInfoOff"}
	{include file="DetailViewUI.tpl" keyid=15}
{elseif $sdk_mode eq 'edit'}
	{if $readonly eq 99}
		{include file='DisplayFieldsReadonly.tpl' uitype=15}
	{elseif $readonly eq 100}
		{include file="DisplayFieldsHidden.tpl" uitype=15}
	{else}
		{assign var="uitype" value="15"}
		{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
		<div class="{$DIVCLASS}">
			<select name="{$fldname}" tabindex="{$vt_tab}" class="detailedViewTextBox" onchange="if(linkedListChek(this)) linkedListChainChange(this, '{$secondvalue}')"> {* crmv@30528 crmv@131239 crmv@143365 *}
			{foreach item=arr from=$fldvalue}
				{if $arr[0] eq $APP.LBL_NOT_ACCESSIBLE}
					<option value="{$arr[0]}" {$arr[2]}>{$arr[0]}</option>
				{else}
					<option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
				{/if}
			{foreachelse}
				<option value=""></option>
				<option value="" style='color: #777777' disabled>{$APP.LBL_NONE}</option>
			{/foreach}
			</select>
		</div>
		<script type="text/javascript">
			(function() {ldelim}
				var lastpl = document.getElementsByName("{$fldname}");
				if (lastpl.length > 0) linkedListChainChange(lastpl[0], '{$secondvalue}'); // crmv@30528 crmv@131239
			{rdelim})();
		</script>
	{/if}
{/if}