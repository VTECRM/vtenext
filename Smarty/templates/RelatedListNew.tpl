{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
<script language="JavaScript" type="text/javascript" src="{"modules/PriceBooks/PriceBooks.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/ListView.js"|resourcever}"></script> {* crmv@104568 *}

{literal}
<script type="text/javascript">
	function editProductListPrice(id,pbid,price,module) {
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'action='+module+'Ajax&file=EditListPrice&return_action=DetailView&return_module=PriceBooks&module='+module+'&record='+id+'&pricebook_id='+pbid+'&listprice='+price,
			success: function(result) {
				jQuery("#status").hide();
				jQuery("#editlistprice").html(result);
			}
		});
	}

	function gotoUpdateListPrice(id,pbid,proid,module) {
		jQuery("#status").show();
		jQuery("#roleLay").hide();
		var listprice = jQuery("#list_price").val();

		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module='+module+'&action='+module+'Ajax&file=UpdateListPrice&ajax=true&return_action=CallRelatedList&return_module=PriceBooks&record='+id+'&pricebook_id='+pbid+'&product_id='+proid+'&list_price='+listprice,
			success: function(result) {
				jQuery("#status").hide();
				reloadTurboLift('PriceBooks', id, module);
			}
		});
	}
</script>
{/literal}

<table border="0" cellspacing="0" cellpadding="0" width="100%">
	<tr><td>
		<div id="RLContents">
			{include file='RelatedListContents.tpl'}
		</div>
	</td></tr>
</table>