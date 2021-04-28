{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

{if !empty($QCMODULE)}

<ul id="quickModules" class="vte-collection with-header">
	
	<li class="collection-header"><h4>{'LBL_QUICK_CREATE'|getTranslatedString}</h4></li>
	
	{foreach from=$QCMODULE item=detail name=qcmodule}
		{assign var="moduleName" value=$detail.1}
		{assign var="moduleNameLower" value=$moduleName|strtolower}
		{assign var="moduleFirstLetter" value=$moduleName|substr:0:1|strtoupper}

		<li class="collection-item avatar">
			<div class="circle">
				<i class="icon-module icon-{$moduleNameLower} nohover" data-first-letter="{$moduleFirstLetter}"></i>
			</div>
			<div class="main-title"><a href="#" onclick="NewQCreate('{$detail.1}');">{$detail.0}</a></div>
		</li>
	{/foreach}
	
</ul>

{else}

<div class="vte-collection-empty">
	<div class="collection-item">
		<div class="circle">
			<i class="vteicon nohover">flash_on</i>
		</div>
		<h4 class="title">{"LBL_NO_QUICKCREATED"|getTranslatedString}</h4>
	</div>
</div>
	
{/if}