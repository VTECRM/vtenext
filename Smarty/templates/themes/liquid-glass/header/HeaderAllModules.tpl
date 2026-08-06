{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

<table cellspacing="0" cellpadding="5" border="0" class="small" width="100%" id="OtherModuleListHeader">
	<tr>
		<td align="right">
			<div class="form-group moduleSearch">
				<input type="text" class="form-control searchBox" id="menu_search_text" placeholder="{$APP.LBL_SEARCH_MODULE}" onclick="AllMenuObj.clearMenuSearchText(this)" onblur="AllMenuObj.restoreMenuSearchDefaultText(this)" />
				<span class="cancelIcon">
					<i class="vteicon md-link md-sm" id="menu_search_icn_canc" style="display:none" title="Reset" onclick="AllMenuObj.cancelMenuSearchSearchText()">cancel</i>&nbsp;	
				</span>
				<span class="searchIcon">
					<i class="vteicon md-link" id="menu_search_icn_go" title="{$APP.LBL_FIND}" onclick="AllMenuObj.searchInMenu();">search</i>
				</span>
			</div>
		</td>
	</tr>
</table>

<table class="table" id="OtherModuleListContent">
	{assign var="count" value=0}
	{foreach item=info from=$OtherModuleList}
		{assign var="url" value="index.php?module="|cat:$info.name|cat:"&action=index"}
		{if $count eq 0}
			{assign var="div_open" value=true}
			<tr>
		{/if}
		
		{assign var="count" value=$count+1}
		{assign var="first_letter" value=$info.translabel|substr:0:1|strtoupper}
		
		<td>
			<div class="vcenter circle"><i class="icon-module icon-{$info.name|strtolower} md-sm" data-first-letter="{$first_letter}"></i></div>
			<div class="vcenter"><a href="{$url}" class="menu_entry">{$info.translabel}</a></div>
		</td>
		
		{if $count eq 3}
			</tr>
			{assign var="count" value=0}
			{assign var="div_open" value=false}
		{/if}
	{/foreach}
	{if $div_open eq true}
		</tr>
	{/if}
</table>