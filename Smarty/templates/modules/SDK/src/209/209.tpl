{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{if $sdk_mode eq 'detail'}
	{if $keyreadonly neq 100}
		{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
		<div class="{$DIVCLASS}">
			{if $keyval neq ''}
				<a href="{$keyval}" target="_blank"><img src="{$keyval}" style="max-width:300px" border="0"></a>
			{/if}
		</div>
	{/if}
{elseif $sdk_mode eq 'edit'}
	{if $readonly eq 99}
		{include file='DisplayFieldsReadonly.tpl' uitype=1}
	{elseif $readonly eq 100}
		{include file="DisplayFieldsHidden.tpl" uitype=1}
	{else}
		{include file="EditViewUI.tpl" uitype=1}
	{/if}
	{if $readonly neq 100 && $fldvalue neq ''}
		{* crmv@167371 *}
		{assign var="id_img" value="img_$fldname"}
		<script src="modules/SDK/src/209/src/wheelzoom.js"></script>
		<script src="modules/SDK/src/209/209Utils.js"></script>
		<img id="{$id_img}" class="img_zoom" src="{$fldvalue}" style="max-width:400px" border="0">
		<div class="block_option" style="display: inline-block;width: 50px;text-align: center;">
			<i data-toggle="tooltip" data-placement="top" class="vteicon" onclick="Utils209.doZoom('in','{$id_img}');" style="cursor:pointer; font-size:35px;">zoom_in</i>
			<i data-toggle="tooltip" data-placement="top" class="vteicon" onclick="Utils209.doZoom('out','{$id_img}');" style="cursor:pointer; font-size:35px;">zoom_out</i>
			<a href="{$fldvalue}" target="_blank"><i class="vteicon md-link" style="font-size:30px;">launch</i></a>
		</div>
		{* crmv@167371e *}
	{/if}
{/if}