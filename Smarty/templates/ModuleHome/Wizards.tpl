{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@83340 crmv@96233 crmv@102379 *}

<div id="wizards_list_{$BLOCK.blockid}" class="wizards_list text-center">
{if count($WIZARDS) > 0}
	{foreach item=WIZ from=$WIZARDS}
		<button class="btn btn-lg btn-primary" onclick="{$WIZ.handler}">{$WIZ.title}</button>
	{/foreach}
</div>
{else}
	<p>{$APP.LBL_NO_AVAILABLE_WIZARDS}</p>
{/if}