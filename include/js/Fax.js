/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

function Fax(module,oButton) {
	
	var allids = get_real_selected_ids(module).replace(/;/g,",");
	
	if (allids == "" || allids == ",") {
		alert(alert_arr.SELECT);
		return false;
	}
	
	if (allids.substr('0','1')==",") {
		allids = allids.substr('1');
	}

	sendfax(module,allids,oButton);
}

function set_return_fax(entity_id,fax_id,parentname,faxadd,perm){
	if(perm == 0 || perm == 3)
	{		
			alert(alert_arr.LBL_DONT_HAVE_FAX_PERMISSION);
			return false;
	}
	else
	{
	if(faxadd != '')
	{
		window.opener.document.EditView.parent_id.value = window.opener.document.EditView.parent_id.value+entity_id+'@'+fax_id+'|';
		window.opener.document.EditView.parent_name.value = window.opener.document.EditView.parent_name.value+parentname+'<'+faxadd+'>,';
		window.opener.document.EditView.hidden_toid.value = faxadd+','+window.opener.document.EditView.hidden_toid.value;
		window.close();
	}else
	{
		alert('"'+parentname+alert_arr.DOESNOT_HAVE_AN_FAXID);
		return false;
	}
	}
}	


function validate_sendfax(idlist,module) {
	var j=0;
	var chk_fax = document.SendFax.elements.length;
	var oFsendfax = document.SendFax.elements
	var fax_type = new Array();
	
	for(var i=0 ;i < chk_fax ;i++)
	{
		if(oFsendfax[i].type != 'button')
		{
			if(oFsendfax[i].checked != false)
			{
				fax_type [j++]= oFsendfax[i].value;
			}
		}
	}
	
	if(fax_type != '')
	{
		var field_lists = fax_type.join(':');
		var url= 'index.php?module=Fax&action=FaxAjax&pmodule='+module+'&file=EditView&sendfax=true&field_lists='+field_lists+'&idlist='+idlist;	//crmv@27096 crmv@55198
		openPopUp('xComposeFax',this,url,'createfaxWin',820,389,'menubar=no,toolbar=no,location=no,status=no,resizable=no');
		fninvsh('roleLayFax');
		return true;
	}
	else
	{
		alert(alert_arr.SELECT_FAXID);
	}
}

// crmv@192033
function sendfax(module,idstrings,oButton) {
	jQuery.ajax({
		url: "index.php?module=Fax&return_module="+module+"&action=FaxAjax&file=faxSelect",
		method: 'POST',
		data: "idlist="+idstrings,
		success: function(result) {
			if(result == "Fax Ids not permitted" || result == "No Fax Ids") {
				var url= 'index.php?module=Fax&action=FaxAjax&pmodule='+module+'&file=EditView&sendfax=true';
				openPopUp('xComposeFax',this,url,'createfaxWin',820,389,'menubar=no,toolbar=no,location=no,status=no,resizable=no');
			} else {
				jQuery('#sendfax_cont').html(result);
				showFloatingDiv('roleLayFax');
			}	
		}
	});
}
// crmv@192033e

function rel_Fax(module,oButton,relmod){
	var select_options='';
	var allids='';
	var cookie_val=get_cookie(relmod+"_all");
	if(cookie_val != null)
		select_options=cookie_val;
	//Added to remove the semi colen ';' at the end of the string.done to avoid error.
	var x = select_options.split(";");
	var viewid ='';
	var count=x.length
		var idstring = "";
	select_options=select_options.slice(0,(select_options.length-1));

	if (count > 1)
	{
		idstring=select_options.replace(/;/g,':')
			allids=idstring;
	}
	else
	{
		alert(alert_arr.SELECT);
		return false;
	}
	sendfax(relmod,allids,oButton);
}