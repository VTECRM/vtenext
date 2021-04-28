{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@3085m crmv@3086m crmv@57221 *}

<script language="JavaScript" type="text/javascript" src="{"include/js/RelatedList.js"|resourcever}"></script>

{* crmv@64719 *}
{if $OLD_STYLE eq true}
	{include file='CustomLinks.tpl' CUSTOM_LINK_TYPE="DETAILVIEWBASIC"}
	{include file='CustomLinks.tpl' CUSTOM_LINK_TYPE="DETAILVIEW"}
{/if}
{* crmv@64719e *}

<div id="turboLiftRelationsContainer">
	{if $OLD_STYLE eq true}
		{include file="TurboliftRelationsOldStyle.tpl"}
	{else}
		{include file="TurboliftRelations.tpl"}
	{/if}
</div>

{if $OLD_STYLE eq true}
	{include file="TurboliftUp.tpl"}
{/if}