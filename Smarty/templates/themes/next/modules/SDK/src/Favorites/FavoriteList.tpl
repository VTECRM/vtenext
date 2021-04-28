{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *} {* crmv@172169 *}

{if !empty($FAV_LIST)}
<ul id="favoriteList" class="vte-collection with-header">
	<li class="collection-header">
		<h4 class="collection-title">{"LBL_FAVORITES"|getTranslatedString}</h4>
		<button id="favorites_button" type="button" class="crmbutton edit collection-btn" onclick="get_more_favorites();" style="">
			{"LBL_ALL"|getTranslatedString}
		</button>
	</li>
	{foreach from=$FAV_LIST item=fav name="fav"}
		{assign var="module" value=$fav.module}
		{assign var="crmid" value=$fav.crmid}
		{assign var="name" value=$fav.name}
		{assign var="moduleNameLower" value=$module|strtolower}
		
		{assign var="entityType" value="SINGLE_"|cat:$module|getTranslatedString:$module}
		{if empty($entityType) || $entityType eq "SINGLE_"|cat:$module}
			{assign var="entityType" value=$module|getTranslatedString:$module}
		{/if}
		
		{assign var="moduleFirstLetter" value=$entityType|substr:0:1|strtoupper}
		
		<li class="collection-item avatar">
			<div class="circle">
				<i class="icon-module icon-{$moduleNameLower} nohover" data-first-letter="{$moduleFirstLetter}"></i>
			</div>
			<div class="title">
				<a href="index.php?module={$module}&action=DetailView&record={$crmid}">{$name}</a>
			</div>
			<p>{$entityType}</p>
		</li>
	{/foreach}
</ul>
{else}
	<div class="vte-collection-empty">
		<div class="collection-item">
			<div class="circle">
				<i class="vteicon nohover">star</i>
			</div>
			<h4 class="title">{"LBL_NO_FAVORITES"|getTranslatedString}</h4>
		</div>
	</div>
{/if}