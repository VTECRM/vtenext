/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
loadFileJs('modules/Products/multifile.js');
loadFileJs('include/js/Merge.js');

function updateListPrice(unitprice,fieldname, oSelect)
{
	if(oSelect.checked == true)
	{
		document.getElementById(fieldname).style.visibility = 'visible';
		document.getElementById(fieldname).value = unitprice;
	}else
	{
		document.getElementById(fieldname).style.visibility = 'hidden';
	}
}

function check4null(form)
{
  var isError = false;
  var errorMessage = "";
  if (trim(form.productname.value) =='') {
			 isError = true;
			 errorMessage += "\n Product Name";
			 form.productname.focus();
  }

  if (isError == true) {
			 alert(alert_arr.MISSING_REQUIRED_FIELDS + errorMessage);
			 return false;
  }
  return true;
}

function set_return(product_id, product_name) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	form.parent_name.value = product_name;
	form.parent_id.value = product_id;
	disableReferenceField(form.parent_name,form.parent_id,form.parent_id_mass_edit_check);	//crmv@29190
}
function set_return_specific(product_id, product_name) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	form.product_name.value = product_name;
	form.product_id.value = product_id;
	disableReferenceField(form.product_name,form.product_id,form.product_id_mass_edit_check);	//crmv@29190
}

function add_data_to_relatedlist(entity_id,recordid) {
	opener.document.location.href="index.php?module={RETURN_MODULE}&action=updateRelations&smodule={SMODULE}&destination_module=Products&entityid="+entity_id+"&parentid="+recordid;
}

function set_return_inventory(product_id,product_name,unitprice,taxstr,curr_row,desc) {
	//crmv@21048
	parent.document.EditView.elements["productName"+curr_row].value = product_name;
	parent.document.EditView.elements["hdnProductId"+curr_row].value = product_id;
	disableReferenceField(parent.document.EditView.elements["productName"+curr_row]);	//crmv@29190
	parent.document.EditView.elements["listPrice"+curr_row].value = unitprice;
	parent.document.EditView.elements["comment"+curr_row].value = desc;

	var tax_array = new Array();
	var tax_details = new Array();
	tax_array = taxstr.split(',');
	for(var i=0;i<tax_array.length;i++)
	{
		tax_details = tax_array[i].split('=');
	}
	
	parent.document.EditView.elements["qty"+curr_row].focus()
	//crmv@21048e
	
	parent.loadTaxes_Ajax(curr_row);	//crmv@55228
}

function set_return_inventory_po(product_id,product_name,unitprice,taxstr,curr_row,desc) {
	//crmv@21048
	parent.document.EditView.elements["productName"+curr_row].value = product_name;
	parent.document.EditView.elements["hdnProductId"+curr_row].value = product_id;
	disableReferenceField(parent.document.EditView.elements["productName"+curr_row]);	//crmv@29190
	parent.document.EditView.elements["listPrice"+curr_row].value = unitprice;
	parent.document.EditView.elements["comment"+curr_row].value = desc;
	//getOpenerObj("unitPrice"+curr_row).innerHTML = unitprice;
	
	var tax_array = new Array();
	var tax_details = new Array();
	tax_array = taxstr.split(',');
	for(var i=0;i<tax_array.length;i++)
	{
		tax_details = tax_array[i].split('=');
	}
	
	parent.document.EditView.elements["qty"+curr_row].focus()
	//crmv@21048e
	
	parent.loadTaxes_Ajax(curr_row);	//crmv@55228
}

function InventorySelectAllServices(mod,z,image_pth)
{
    if(document.selectall.selected_id != undefined)
    {
		var x = document.selectall.selected_id.length;
		var y=0;
		idstring = "";
		namestr = "";
		var action_str="";
		if ( x == undefined) {
			if (document.selectall.selected_id.checked) {
				idstring = document.selectall.selected_id.value;
				c = document.selectall.selected_id.value;
				var prod_array = JSON.parse(document.getElementById('popup_product_'+c).attributes['vt_prod_arr'].nodeValue); // crmv@192033
				var prod_id = prod_array['entityid'];
				var prod_name = prod_array['prodname'];
				var unit_price = prod_array['unitprice'];
				var taxstring = prod_array['taxstring'];
				var desc = prod_array['desc'];
				var row_id = prod_array['rowid'];
				set_return_inventory(prod_id,prod_name,unit_price,taxstring,parseInt(row_id),desc);
				y=1;
			} else {
				alert(alert_arr.SELECT);
				return false;
			}
		} else {
			y=0;
			for(i = 0; i < x ; i++) {
				if(document.selectall.selected_id[i].checked) {
					idstring = document.selectall.selected_id[i].value+";"+idstring;
					c = document.selectall.selected_id[i].value;
					var prod_array = JSON.parse(document.getElementById('popup_product_'+c).attributes['vt_prod_arr'].nodeValue); // crmv@192033
					var prod_id = prod_array['entityid'];
					var prod_name = prod_array['prodname'];
					var unit_price = prod_array['unitprice'];
					var taxstring = prod_array['taxstring'];
					var desc = prod_array['desc'];
					if(y>0) {
						var row_id = parent.fnAddProductRow(mod,image_pth);//crmv@21048
					} else {
						var row_id = prod_array['rowid'];
					}	
							
					set_return_inventory(prod_id,prod_name,unit_price,taxstring,parseInt(row_id),desc);
					y=y+1;
				}
			}
		}
		if (y != 0) {
			document.selectall.idlist.value=idstring;
			return true;
		} else {
			alert(alert_arr.SELECT);
			return false;
		}
    }
}

function getImageListBody() {
	if (browser_ie) {
		var ImageListBody=getObj("ImageList")
	} else if (browser_nn4 || browser_nn6) {
		if (getObj("ImageList").childNodes.item(0).tagName=="TABLE") {
			var ImageListBody=getObj("ImageList")
		} else {
			var ImageListBody=getObj("ImageList")
		}
	}
	return ImageListBody;
}

function servicePickList(currObj,module, row_no) {
	var trObj=currObj.parentNode.parentNode
	
	var rowId = row_no;
	var currentRowId = parseInt(currObj.id.match(/([0-9]+)$/)[1]);
	
	// If we have mismatching rowId and currentRowId, it is due swapping of rows
	if(rowId != currentRowId) {
		rowId = currentRowId;
	}

	var currencyid = document.getElementById("inventory_currency").value;

	popuptype = 'inventory_service';
	var record_id = '';
    if(document.getElementsByName("account_id").length != 0)
    	record_id= document.EditView.account_id.value;
    if(record_id != '')
    	openPopup("index.php?module=Services&action=Popup&html=Popup_picker&select=enable&form=HelpDeskEditView&popuptype="+popuptype+"&curr_row="+rowId+"&relmod_id="+record_id+"&parent_module=Accounts&return_module="+module+"&currencyid="+currencyid,"productWin","width=640,height=600,resizable=0,scrollbars=0,status=1,top=150,left=200");//crmv@21048
    else
		openPopup("index.php?module=Services&action=Popup&html=Popup_picker&select=enable&form=HelpDeskEditView&popuptype="+popuptype+"&curr_row="+rowId+"&return_module="+module+"&currencyid="+currencyid,"productWin","width=640,height=600,resizable=0,scrollbars=0,status=1,top=150,left=200");//crmv@21048
}