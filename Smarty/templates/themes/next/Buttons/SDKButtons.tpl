{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{*
	Arguments:
	$MODULE: The module name
	$REQUEST_ACTION: The action name
*}

{assign var=CONTESTUAL_BUTTONS value=$SDK->getMenuRawButton('contestual', $MODULE)}
	
{foreach from=$CONTESTUAL_BUTTONS item=button}
	{assign var=button_id value=$button.id}
	{assign var=button_module value=$button.module}
	{assign var=button_title value=$button.title}
	{assign var=button_onclick value=$button.onclick}
	{assign var=button_image value=$button.image}
	
	<li>
		<a href="javascript:;">
			{if $button.is_image}
				<img data-toggle="tooltip" data-placement="bottom" src="{$button_image}" alt="{$button_title|getTranslatedString:$module}" title="{$button_title|getTranslatedString:$module}" onclick="{$button_onclick}" style="cursor:pointer;">
			{else}
				<button type="button" class="crmbutton with-icon save crmbutton-nav" onclick="{$button_onclick}">
					<i class="vteicon md-link">{$button_image}</i>
					{$button_title|getTranslatedString:$module}
				</button>
			{/if}
		</a>
	</li>
{/foreach}

{assign var=CONTESTUAL_BUTTONS value=$SDK->getMenuRawButton('contestual', $MODULE, $REQUEST_ACTION)}
	
{foreach from=$CONTESTUAL_BUTTONS item=button}
	{assign var=button_id value=$button.id}
	{assign var=button_module value=$button.module}
	{assign var=button_title value=$button.title}
	{assign var=button_onclick value=$button.onclick}
	{assign var=button_image value=$button.image}
	
	<li>
		<a href="javascript:;">
			{if $button.is_image}
				<img data-toggle="tooltip" data-placement="bottom" src="{$button_image}" alt="{$button_title|getTranslatedString:$module}" title="{$button_title|getTranslatedString:$module}" onclick="{$button_onclick}" style="cursor:pointer;">
			{else}
				<button type="button" class="crmbutton with-icon save crmbutton-nav" onclick="{$button_onclick}">
					<i class="vteicon md-link">{$button_image}</i>
					{$button_title|getTranslatedString:$module}
				</button>
			{/if}
		</a>
	</li>
{/foreach}