{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

<div id="moduleListContainer">
	<ul class="moduleList">
		{foreach item=info from=$VisibleModuleList}
			{assign var="label" value=$info.name|@getTranslatedString:$info.name}
			{assign var="url" value="index.php?module="|cat:$info.name|cat:"&amp;action=index"}
			{assign var="moduleName" value=$info.name|strtolower}
			{assign var="moduleFirstLetter" value=$label|substr:0:1|strtoupper}
			
			{if $info.name eq 'Messages' || $info.name eq 'Calendar'}
				{continue} {* crmv@181170 *}
			{/if}
			
			{assign var="class" value=""}
			{if $info.name eq $MODULE_NAME} 
				{assign var="class" value="active"}
			{/if}
			
			<li class="{$class}">
				<a href="{$url}">
					<div class="row">
						<div class="col-xs-2 vcenter">
							<i class="icon-module icon-{$moduleName}" data-first-letter="{$moduleFirstLetter}"></i>
						</div><!-- 
						 --><div class="col-xs-10 vcenter">
							<span class="moduleText">{$label}</span>
						</div>
					</div>
				</a>
			</li>
		{/foreach}
		
		{if !empty($LAST_MODULE_VISITED) && $LAST_MODULE_VISITED neq 'Messages' && $LAST_MODULE_VISITED neq 'Calendar'}
			{assign var="moduleName" value=$LAST_MODULE_VISITED|strtolower}
			{assign var="label" value=$LAST_MODULE_VISITED|@getTranslatedString:$LAST_MODULE_VISITED}
			{assign var="moduleFirstLetter" value=$label|substr:0:1|strtoupper}
			
			{assign var="class" value=""}
			{if $LAST_MODULE_VISITED eq $MODULE_NAME} 
				{assign var="class" value="active"}
			{/if}
			
			<li class="{$class}">
				<a href="index.php?module={$LAST_MODULE_VISITED}&amp;action=index">
					<div class="row">
						<div class="col-xs-2 vcenter">
							<i class="icon-module icon-{$moduleName}" data-first-letter="{$moduleFirstLetter}"></i>
						</div><!-- 
						 --><div class="col-xs-10 vcenter">
							<span class="moduleText">{$label}</span>
						</div>
					</div>
				</a>
			</li>
		{/if}
	</ul>
</div>

<ul class="menuList">
	<li onclick="VTE.FastPanelManager.showMenu();">
		<a>
			<div class="row">
				<div class="col-xs-2 vcenter">
					<i class="vteicon">reorder</i>
				</div><!-- 
				 --><div class="col-xs-10 vcenter">
					<span class="moduleText">{'LBL_MENU_TABS_NAME'|getTranslatedString:'Settings'}</span>
				</div>
			</div>
		</a>
	</li>
</ul>