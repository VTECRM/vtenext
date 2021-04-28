/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@198024 */

function set_return(recordid, value) {
	var forfield = 'confproductid',
		formodule = 'Products';
	var r = vtlib_setvalue_from_popup(recordid,value,forfield);
	// now reload the magic block
	
	// use this hidden field to retrieve the blockid
	reload_variant_block(recordid, formodule, forfield);
	
	return r;
}

// if you change this function, remember to change it also in Products.js
function reload_variant_block(recordid, formodule, forfield) {
	if (window.parent !== window) {
		var context = window.parent;
	} else {
		var context = window;
	}
	var block = context.jQuery('#confprodinfo').closest('.editBlock'),
		blockid = block.attr('id').replace('block_', '');
	
	if (!blockid) return;
	
	if (recordid > 0) {
		// ok, show the block
		context.jQuery.ajax({
			url: 'index.php?module=ConfProducts&action=ConfProductsAjax&ajax=true&file=LoadBlock',
			method: 'POST',
			data: 'formodule='+formodule+'&forfield='+forfield+'&confproductid='+recordid,
			dataType: 'json',
			success: function(result) {
				if (result && result.success) {
					// remove old fields
					var hiddenField = context.jQuery('#confprodinfo').closest('tr');
					context.jQuery('#displayfields_'+blockid).get(0).innerHTML = hiddenField.html() + result.html; // don't execute scripts'
					context.jQuery('#displayfields_'+blockid).show();
					context.jQuery('.blockrow_'+blockid).show();
				}
			}
		});
	} else {
		// hide the block
		context.jQuery('#displayfields_'+blockid).hide();
		context.jQuery('.blockrow_'+blockid).hide();
	}
}