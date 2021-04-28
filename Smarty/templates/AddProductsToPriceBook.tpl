{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@111998 *}
<script type="text/javascript">
	var parenttab = "{$CATEGORY}";
	function getListViewEntries_js(module, url) {ldelim}
		getPBListViewEntries_js(module, url, {$PRICEBOOK_ID});
	{rdelim}
</script>
<script type="text/javascript" src="modules/PriceBooks/PriceBooks.js"></script>

<form name="addToPB" method="POST" id="addToPB">
	<input name="pricebook_id" type="hidden" value="{$PRICEBOOK_ID}">
	<input name="idlist" type="hidden">
	<input name="viewname" type="hidden">
	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}">
	<div id="ProductListContent">
		{include file="AddProductsToPriceBookContents.tpl"}
	</div>
</form>
