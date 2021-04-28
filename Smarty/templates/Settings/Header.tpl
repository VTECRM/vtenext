{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
<script type="text/javascript" src="include/js/smoothscroll.js"></script>

{if !empty($RESOURCES)}
	{if isset($RESOURCES.css) && is_array($RESOURCES.css)}
		{foreach from=$RESOURCES.css item=res}
			{if is_array($res)}
				{assign var=link_href value=$res.href}
			{else}
				{assign var=link_href value=$res}
			{/if}
			<link rel="stylesheet" type="text/css" href="{$link_href|resourcever}">
		{/foreach}
	{/if}
	{if isset($RESOURCES.js) && is_array($RESOURCES.js)}
		{foreach from=$RESOURCES.js item=res}
			{if is_array($res)}
				{assign var=script_src value=$res.src}
				{assign var=script_async value=$res.async}
			{else}
				{assign var=script_src value=$res}
			{/if}
			<script type="text/javascript" src="{$script_src|resourcever}"></script>
		{/foreach}
	{/if}
{/if}

{include file='Buttons_List.tpl'}
{include file='SetMenu.tpl'}

{assign var=SETTINGS_HEADING value=$SETTINGS_HEADING|default:""}

<table border="0" cellpadding="5" cellspacing="0" width="100%">
	<tr>
		<td rowspan="2" valign="top" width="50">
			<img src="{$HEADER_IMG}" alt="{$HEADER_IMG_ALT}" title="{$HEADER_IMG_TITLE}" height="48" width="48">
		</td>
		<td valign="middle">
			{if empty($SETTINGS_HEADING)}
				<div style="margin-left:10px;">
					<h4 class="title" style="margin-bottom:0px;">
						<strong>{'LBL_SETTINGS'|getTranslatedString:$MODULE} &gt; {$HEADER_TITLE}</strong>
					</h4>
					{if !empty($HEADER_DESC)}
						<p class="text-muted">{$HEADER_DESC}</p>
					{/if}
				</div>
			{else}
				<div style="margin-left:10px;">
					<h4 class="title" style="margin-bottom:0px;">
						<strong>{$SETTINGS_HEADING}</strong>
					</h4>
					{if !empty($HEADER_DESC)}
						<p class="text-muted">{$HEADER_DESC}</p>
					{/if}
				</div>
			{/if}
		</td>
	</tr>
</table>