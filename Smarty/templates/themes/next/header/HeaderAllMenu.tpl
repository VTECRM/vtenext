{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

<div class="col-sm-12">
	<ul class="tabs tabs-fixed-width" id="menuTabs">
		<li class="tab"><a href="#OtherModuleListTabContent">{$APP.LBL_MODULES}</a></li>
		<li class="tab"><a href="#AllMenuAreaTabContent">{$APP.LBL_AREAS}</a></li>
	</ul>
</div>

<div id="OtherModuleListTabContent" class="col-sm-12">
	{include file="header/HeaderAllModules.tpl"}
</div>

<div id="AllMenuAreaTabContent" class="col-sm-12">
	{include file="modules/Area/Menu.tpl" UNIFIED_SEARCH_AREAS_CLASS=" "}
</div>