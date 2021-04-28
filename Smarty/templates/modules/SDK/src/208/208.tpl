{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@37679 crmv@43764 crmv@54072 *}

{assign var=fieldinfo value=$smarty.session.uitype208.$keyfldid}
{assign var=expired value=$fieldinfo.expired}

{if $fieldinfo.old_uitype eq '19' || $fieldinfo.old_uitype eq '21'}
	{assign var=istextbox value=true}
{else}
	{assign var=istextbox value=false}
{/if}

{if $fieldinfo.permitted neq true}
	{assign var=keyreadonly value=99}
	{assign var=readonly value=99}
	{assign var=expired value=false}
	{assign var=AJAXEDITTABLEPERM value=false}
{/if}

{if $sdk_mode eq 'detail'}
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div id="uitype208_td_{$keyfldid}">
		{if $expired}
			<div class="{$DIVCLASS}" id="uitype208_loader_{$keyfldid}" style="display:none">
				{include file="LoadingIndicator.tpl"}
			</div>
			<div class="{$DIVCLASS}" id="uitype208_pwdcont_{$keyfldid}">
				<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr>
				<td width="100%"><input class="detailedViewTextBox" size="18" type="password" value="" name="uitype208_pwd_{$keyfldid}" id="uitype208_pwd_{$keyfldid}" autocomplete="off" placeholder="Password" onkeyup="uitype208Keyup(event, '{$ID}', '{$keyfldid}')"/></td>
				<td nowrap><a href="javascript:;" onclick="uitype208ShowField('{$ID}', '{$keyfldid}')">OK</a></td>
				</tr></table>
			</div>
			{assign var=keyval value=""}
			{assign var=uitype208show value=false}
		{else}
			{assign var=uitype208show value=true}
		{/if}
		<div class="{$DIVCLASS} detailCellInfo" name="uitype208_cont_{$keyfldid}" {if $uitype208show eq false}style="display:none"{/if} {if $AJAXEDITTABLEPERM}ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}>
			<span id="dtlview_{$label}">{$keyval|nl2br}</span>
			<div id="editarea_{$label}" style="display:none;">
				{if $istextbox}
					<textarea id="txtbox_{$label}" name="{$keyfldname}"  class=detailedViewTextBox onFocus="this.className='detailedViewTextBoxOn'"onBlur="this.className='detailedViewTextBox'" cols="90" rows="8">{$keyval|replace:"<br>":"\n"}</textarea>
				{else}
					<input id="txtbox_{$label}" name="{$keyfldname}" class="detailedViewTextBox" onFocus="this.className='detailedViewTextBoxOn'" onBlur="this.className='detailedViewTextBox'" type="text" maxlength='100' value="{$keyval}" />
				{/if}
			</div>
		</div>
	</div>
{elseif $sdk_mode eq 'edit'}
	{if $readonly eq 100}
		<input type="hidden" name="{$fldname}" id="uitype208_input_{$keyfldid}" tabindex="{$vt_tab}" value="" tabindex="{$vt_tab}" class="detailedViewTextBox">
	{elseif $readonly eq 99}
		{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
		<div class="{$DIVCLASS}" id="uitype208_td_{$keyfldid}">
			{if $expired}
				<div id="uitype208_loader_{$keyfldid}" style="display:none">
					{include file="LoadingIndicator.tpl"}
				</div>
				<div id="uitype208_pwdcont_{$keyfldid}">
					<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr>
					<td width="100%"><input class="detailedViewTextBox" size="18" type="password" value="" name="uitype208_pwd_{$keyfldid}" id="uitype208_pwd_{$keyfldid}" autocomplete="off" placeholder="Password" onkeyup="uitype208Keyup(event, '{$ID}', '{$keyfldid}')"/></td>
					<td nowrap><a href="javascript:;" onclick="uitype208EditField('{$ID}', '{$keyfldid}')">OK</a></td>
					</tr></table>
				</div>
				{assign var=fldvalue value=""}
				{assign var=uitype208show value=false}
			{else}
				{assign var=uitype208show value=true}
			{/if}
			<div name="uitype208_cont_{$keyfldid}" id="uitype208_cont_{$keyfldid}" style="{if $uitype208show eq false}display:none{/if}">
				<input type="hidden" name="{$fldname}" id="uitype208_input_{$keyfldid}" tabindex="{$vt_tab}" value="{$fldvalue|strip_tags}" tabindex="{$vt_tab}" class=detailedViewTextBox >
				<span>{$fldvalue}</span>
			</div>
		</div>
	{else}
		{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
		<div class="{$DIVCLASS}" id="uitype208_td_{$keyfldid}">
			{if $expired}
				<div id="uitype208_loader_{$keyfldid}" style="display:none">
					{include file="LoadingIndicator.tpl"}
				</div>
				<div id="uitype208_pwdcont_{$keyfldid}">
					<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr>
					<td width="100%"><input class="detailedViewTextBox" size="18" type="password" value="" name="uitype208_pwd_{$keyfldid}" id="uitype208_pwd_{$keyfldid}" autocomplete="off" placeholder="Password" onkeyup="uitype208Keyup(event, '{$ID}', '{$keyfldid}')"/></td>
					<td nowrap><a href="javascript:;" onclick="uitype208EditField('{$ID}', '{$keyfldid}')">OK</a></td>
					</tr></table>
				</div>
				{assign var=fldvalue value=""}
				{assign var=uitype208show value=false}
			{else}
				{assign var=uitype208show value=true}
			{/if}
			<div name="uitype208_cont_{$keyfldid}" id="uitype208_cont_{$keyfldid}" style="{if $uitype208show eq false}display:none{/if}">
				{if $istextbox}
					<textarea id="uitype208_input_{$keyfldid}" name="{$fldname}" class="detailedViewTextBox" tabindex="{$vt_tab}" onFocus="this.className='detailedViewTextBoxOn'" onBlur="this.className='detailedViewTextBox'" cols="{$cols}" rows="8">{$fldvalue}</textarea>
				{else}
					<input id="uitype208_input_{$keyfldid}" name="{$fldname}" class="detailedViewTextBox" tabindex="{$vt_tab}" onFocus="this.className='detailedViewTextBoxOn'" onBlur="this.className='detailedViewTextBox'" type="text" value="{$fldvalue}" />
				{/if}
			</div>
		</div>
	{/if}
{/if}