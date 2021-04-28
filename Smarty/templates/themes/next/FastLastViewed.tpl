{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

{assign var=TITLE value=$TITLE|default:"LBL_LAST_VIEWED"|getTranslatedString}
{assign var=COLLECTION_CLASS value=$COLLECTION_CLASS|default:"vte-collection"}
{assign var=ITEM_CLASS value=$ITEM_CLASS|default:""}

{if !empty($TITLE)}
	{assign var=COLLECTION_CLASS value=$COLLECTION_CLASS|cat:" with-header"|trim}
{/if} 

{if !empty($SELECT_FUNC)}
	{assign var=ITEM_CLASS value="pointer "|cat:$ITEM_CLASS|trim}
{/if}

{if !empty($HISTORY)}

	<ul id="lastViewed" class="{$COLLECTION_CLASS}">
		
		{if !empty($TITLE)}
			<li class="collection-header"><h4>{$TITLE}</h4></li>
		{/if}
		
		{foreach from=$HISTORY item=histo name="histo"}
			{assign var="crmid" value=$histo.crmid}
			{assign var="moduleType" value=$histo.module_type}
			{assign var="itemSummary" value=$histo.item_summary}
			{assign var="moduleName" value=$histo.module_name}
			{assign var="moduleNameLower" value=$moduleName|strtolower}
			{assign var="moduleFirstLetter" value=$moduleName|substr:0:1|strtoupper}
			
			{assign var="entityType" value="SINGLE_"|cat:$moduleType|getTranslatedString:$moduleName}
			{if empty($entityType) || $entityType eq "SINGLE_"|cat:$moduleType}
				{assign var="entityType" value=$moduleType|getTranslatedString:$moduleName}
			{/if}
			
			<li class="collection-item avatar {$ITEM_CLASS}"{if !empty($SELECT_FUNC)} onclick="{$SELECT_FUNC}('{$moduleName}', {$crmid}, '{$itemSummary|addslashes}')"{/if}>
				<div class="circle">
					<i class="icon-module icon-{$moduleNameLower} nohover" data-first-letter="{$moduleFirstLetter}"></i>
				</div>
				<div class="title">
					{if !empty($SELECT_FUNC)}
						{$itemSummary}
					{else}
						<a href="index.php?module={$moduleName}&action=DetailView&record={$crmid}">
							{$itemSummary}
						</a>
					{/if}
				</div>
				<p>{$entityType}</p>{* crmv@168546 *}
			</li>
		{/foreach}
		
	</ul>

{else}

	<div class="vte-collection-empty">
		<div class="collection-item">
			<div class="circle">
				<i class="vteicon nohover">list</i>
			</div>
			<h4 class="title">{"LBL_NO_LASTVIEWED"|getTranslatedString}</h4>
		</div>
	</div>

{/if}