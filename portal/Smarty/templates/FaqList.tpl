{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<div class="row col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin-top:50px;">
			<form name="Submit" method="POST" action="index.php" style="float:right">
				<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> <!--  crmv@171581 -->
				<input type=hidden name="fun" value="search">
				<input type=hidden name="module" value="Faq">
				<input type=hidden name="action" value="index">
<div style="position:relative">
				<input type="text" name="search_text" class="form-control" value="{$SEARCHTEXT}" style="padding-right: 25px;">&nbsp;in&nbsp;
<i class="material-icons" style="position:absolute; position: absolute;right: 0;top: 5px;">search</i>
</div>
				{$SEARCHCOMBO}
				<input class= "crmbutton small cancel" type="submit" name="search" onclick="form.fun.value='search'" value="{'LBL_SEARCH'|getTranslatedString}">
			</form>
</div>

<h1 class="page-header">{'LBL_KNOWLEDGE_BASE'|getTranslatedString}</h1>


{if $FAQARRAY neq ''}
<div class="row col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin:50px 0px;">
	{if $CATEGORYARRAY neq ''}
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
			<select  class="form-control"> <!-- onchange="getList(this, 'ProjectPlan');" --> 
				{foreach from=$CATEGORYARRAY item=CATEGORYARRAYVALUE}
 					<option value="{$CATEGORYARRAYVALUE}">{$CATEGORYARRAYVALUE}</option>
				{/foreach}
			</select>
		</div>
	{/if}
		
	{if $PRODUCTARRAY neq ''}
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
			<select class="form-control" onchange="getResult()" id="productlist">
				{foreach from=$PRODUCTARRAY item=PRODUCTARRAYVALUE}
 					<option value="{$PRODUCTARRAYVALUE.productid}">{$PRODUCTARRAYVALUE.productname}</option>
				{/foreach}
			</select>
		</div>
	{/if}
</div>
{else}
	{'LBL_KNOWLEDGE_BASE'|getTranslatedString} {'LBL_NOT_AVAILABLE'|getTranslatedString} {* crmv@173153 *}
{/if}


<div class="row col-lg-12 col-md-12 col-sm-12 col-xs-12">
	{foreach from=$FAQDISPLAY item=valuefaq}
		{$valuefaq}
	{/foreach}
</div>

<script>
{literal}
function getResult(){
	var e = document.getElementById("productlist");
	var productid = e.options[e.selectedIndex].value;
	location.href='index.php?module=Faq&action=index&fun=faqs&productid='+productid;
}
{/literal}
</script>