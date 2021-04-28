{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}
{* crmv@187403 *}

<div id="leftPanel" data-minified="{$MENU_TOGGLE_STATE}">
	<div class="vteLeftHeader">
		<div class="brandLogo">
			<img class="img-responsive headerLogo" src="{$LOGOHEADER}" />
		</div>
		
		<span class="toogleMenu">
			<img class="toggleImg" src="{$LOGOTOGGLE}" />
			<i class="togglePin vteicon2 fa-thumb-tack md-link {if $MENU_TOGGLE_STATE eq 'disabled'}active{/if}"></i>
		</span>
	</div>
	
	{if $MODULE_NAME eq 'Settings' || $CATEGORY eq 'Settings' || $MODULE_NAME eq 'com_workflow'}
		{include file="header/MenuSettings.tpl"}
	{else}
		{include file="header/MenuModules.tpl"}
	{/if}
</div>