/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@192033 */

window.VTE = window.VTE || {};

VTE.ListViewCounts = VTE.ListViewCounts || {
	
	busy: false,
	
	selector: '.listViewCounts',
	
	show: function() {
		var me = this;
		jQuery(me.selector).show();
	},
	
	hide: function() {
		var me = this;
		jQuery(me.selector).hide();
	},
	
	showBusy: function() {
		var me = this;
		
		me.busy = true;
		jQuery("#status").show();
	},
	
	hideBusy: function() {
		var me = this;
		
		me.busy = false;
		jQuery("#status").hide();
	},
	
	getValue: function() {
		var me = this;
		
		var counts = 0;
		
		jQuery(me.selector).each(function() {
			var input = jQuery(this).find('select[name="counts"]');
			if (input.length > 0) { 
				counts = input.val();
				return false;
			}
		});
		
		return counts;
	},
	
	onShowMoreEntries: function(selectView, module, folderid) {
		var me = this;
		
		me.showMoreEntries(selectView, module, folderid, function(res, postbody) {
			result = res.split('&#&#&#');
			
			jQuery("#ListViewContents").html(result[2]);
			
			if (result[1] != '') {
				alert(result[1]);
			}
			if (module != 'Users' && module != 'Import' && module != 'Notes') {
				update_navigation_values(postbody);
				jQuery('#basicsearchcolumns').html('');
			}
		});
	},
	
	onShowMoreEntries_popup: function(selectView, module) {
		var me = this;
		
		me.showMoreEntries_popup(selectView, module, function(res, urlstring) {
			jQuery("#ListViewContents").html(res);
			update_navigation_values(urlstring);

			setListHeight();
		});
	},
	
	showMoreEntries: function(selectView, module, folderid, success, failure) {
		var me = this;
		
		if (me.busy) return false;
		
		me.showBusy();
		
		if (ajaxcall_list) {
			ajaxcall_list.abort();
		}
		if (ajaxcall_count) {
			ajaxcall_count.abort();
		}
		
		var viewCounts = selectView.options[selectView.options.selectedIndex].value;
		var viewid = getviewId();
		
		var urlstring = '';
		
		if (isdefined('search_url')) {
			urlstring = jQuery('#search_url').val();
		}
		if (isdefined('selected_ids')) {
			urlstring += "&selected_ids=" + document.getElementById('selected_ids').value;
		}
		if (isdefined('all_ids')) {
			urlstring += "&all_ids=" + document.getElementById('all_ids').value;
		}
		
		var modulename = '';
		if (isdefined('modulename')) {
			modulename = document.getElementById('modulename').value;
		}

		var postbody = "module=" + module + "&modulename=" + modulename + "&action=" + module + "Ajax&file=ListView&start=1&ajax=true&changecount=true" + urlstring + "&counts=" + viewCounts;
		if (folderid != undefined && folderid != '') postbody += '&folderid=' + folderid;
		
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: postbody,
			success: function(res) {
				me.hideBusy();
				if (typeof success == 'function') success(res, postbody);
			},
			error: function() {
				me.hideBusy();
				console.log('Ajax error');
				if (typeof failure == 'function') failure();
			}
		});
	},

	showMoreEntries_popup: function(selectView, module, success, failure) {
		var me = this;
		
		if (me.busy) return false;
		
		me.showBusy();
		
		if (ajaxcall_list) {
			ajaxcall_list.abort();
		}
		if (ajaxcall_count) {
			ajaxcall_count.abort();
		}
		
		var viewCounts = selectView.options[selectView.options.selectedIndex].value;
		var viewid = getviewId();
		
		var popuptype = jQuery('#popup_type').val();
		var act_tab = jQuery('#maintab').val();
		
		var urlstring = '';
		urlstring += '&popuptype=' + popuptype;
		urlstring += '&maintab=' + act_tab;
		urlstring += '&query=true&file=Popup&module=' + module + '&action=' + module + 'Ajax&ajax=true&changecount=true&counts=' + viewCounts;
		urlstring += gethiddenelements();
		urlstring += "&start=1";
		
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: urlstring,
			success: function(res) {
				me.hideBusy();
				if (typeof success == 'function') success(res, urlstring);
			},
			error: function() {
				me.hideBusy();
				console.log('Ajax error');
				if (typeof failure == 'function') failure();
			}
		});
	},

};

/**
 * @deprecated
 * This function has been moved to VTE.ListViewCounts class.
 */

function showMoreEntries(selectView, module, folderid) {
	return VTE.callDeprecated('showMoreEntries', VTE.ListViewCounts.onShowMoreEntries, arguments, VTE.ListViewCounts);
}

/**
 * @deprecated
 * This function has been moved to VTE.ListViewEntries class.
 */

function showMoreEntries_popup(selectView, module) {
	return VTE.callDeprecated('showMoreEntries_popup', VTE.ListViewCounts.onShowMoreEntries_popup, arguments, VTE.ListViewCounts);
}

/* crmv@82831 */

//crmv@add ajax control
var ajaxcall_list = null;
var ajaxcall_count = null;
var basic_search_submitted = false;
var advance_search_submitted = false;
var grid_search_submitted = false;	//crmv@3084m

/* crmv@30967 */
var typeofdata = new Array();
typeofdata['E'] = ['c','e','n','s','ew','k'];	//crmv@48693
typeofdata['V'] = ['c','e','n','s','ew','k'];	//crmv@48693
typeofdata['N'] = ['e','n','l','g','m','h'];
typeofdata['NN'] = ['e','n','l','g','m','h'];
typeofdata['T'] = ['e','n','l','g','m','h'];
typeofdata['I'] = ['e','n','l','g','m','h'];
typeofdata['C'] = ['e','n'];
typeofdata['DT'] = ['e','n','l','g','m','h'];
typeofdata['D'] = ['e','n','l','g','m','h'];
var fLabels = new Array();
if (typeof(alert_arr) !== 'undefined') {
	fLabels['e'] = alert_arr.EQUALS;
	fLabels['n'] = alert_arr.NOT_EQUALS_TO;
	fLabels['s'] = alert_arr.STARTS_WITH;
	fLabels['ew'] = alert_arr.ENDS_WITH;
	fLabels['c'] = alert_arr.CONTAINS;
	fLabels['k'] = alert_arr.DOES_NOT_CONTAINS;
	fLabels['l'] = alert_arr.LESS_THAN;
	fLabels['g'] = alert_arr.GREATER_THAN;
	fLabels['m'] = alert_arr.LESS_OR_EQUALS;
	fLabels['h'] = alert_arr.GREATER_OR_EQUALS;
}
/* crmv@30967e */

//crmv@add ajax control end
// MassEdit Feature
function massedit_togglediv(curTabId,total){

   for(var i=0;i<total;i++){
	jQuery('#massedit_div'+i).hide();
	tagName1 = jQuery('#tab'+i).get(0);
	tagName1.className = 'dvtUnSelectedCell';
   }

   jQuery('#massedit_div'+curTabId).show();
   tagName1 = jQuery('#tab'+curTabId).get(0);
   tagName1.className = 'dvtSelectedCell';
}

function massedit_initOnChangeHandlers() {
	if (checkJSOverride(arguments)) return callJSOverride(arguments);
	
	//crmv@62661
	jQuery('form#massedit_form :input').bind('change onchange',function(e){
		var inputName = jQuery(this).attr('name').replace("[]", "");
		jQuery('form#massedit_form  #'+inputName+'_mass_edit_check').prop('checked',true);
	});
	/*
	var form = document.getElementById('massedit_form');
	// Setup change handlers for input boxes
	var inputs = form.getElementsByTagName('input');
	for(var index = 0; index < inputs.length; ++index) {
		var massedit_input = inputs[index];
		// TODO Onchange on readonly and hidden fields are to be handled later.
		massedit_input.onchange = function() {
			var checkbox = document.getElementById(this.name + '_mass_edit_check');
			if(checkbox) checkbox.checked = true;
		}
	}
	// Setup change handlers for select boxes
	var selects = form.getElementsByTagName('select');
	for(var index = 0; index < selects.length; ++index) {
		var massedit_select = selects[index];
		if (massedit_select.name == "assigntype" || massedit_select.name == "parent_type" ) continue;	//crmv@34104 //crmv@37430
		massedit_select.onchange = function() {
			var checkbox = document.getElementById(this.name + '_mass_edit_check');
			if(checkbox) checkbox.checked = true;
		}
	}
	*/
	//crmv@62661 e
}
//crmv@fix mass_edit
function mass_edit(obj,divid,module,parenttab) {
	var idstring = get_real_selected_ids(module);
	if (idstring.substr('0','1')==";")
		idstring = idstring.substr('1');
	var idarr = idstring.split(';');
	var count = idarr.length;
	var xx = count-1;
	if (idstring == "" || idstring == ";" || idstring == 'null')
	{
		vtealert(alert_arr.SELECT);
		return false;
	}
	else {
		//crmv@27096 crmv@91571
		var doEnqueue = false,
			enqueue = getFile("index.php?module="+encodeURIComponent(module)+"&action="+encodeURIComponent(module+'Ajax')+"&parenttab="+encodeURIComponent(parenttab)+"&file=MassEdit&mode=ajax&check_count=true");
		enqueue = enqueue.split('###');
		// crmv@132901
		if (enqueue[0] == 'enqueue') {
			doEnqueue = true;
			vtealert(alert_arr.LBL_MASS_EDIT_ENQUEUE.replace('{max_records}', enqueue[1]), function() {
				mass_edit_formload(idstring,module,parenttab,doEnqueue);
				showFloatingDiv(divid,obj);
			});
			return;
		} else {
			mass_edit_formload(idstring,module,parenttab,doEnqueue);
		}
		//crmv@27096e crmv@91571e crmv@132901e
	}
	showFloatingDiv(divid,obj);
}
//crmv@fix mass_edit end
function mass_edit_formload(idstring,module,parenttab,enqueue) {	//crmv@27096 crmv@91571
	if(typeof(parenttab) == 'undefined') parenttab = '';
	jQuery('#status').show();
	jQuery.ajax({
		url: "index.php?module="+encodeURIComponent(module)+"&action="+encodeURIComponent(module+'Ajax')+"&parenttab="+encodeURIComponent(parenttab)+"&file=MassEdit&mode=ajax&enqueue="+(enqueue ? 'true' : 'false'),	//crmv@27096 crmv@91571
		method: 'POST',
		success: function(result) {
			jQuery('#status').hide();
            jQuery("#massedit_form_div").html(result);
			jQuery("#massedit_form input[name=massedit_module]").val(module);
			//crmv@29190
			// crmv@82831 - add a little delay to have time for the DOM to be ready
			setTimeout(function() {
				if (jQuery("#massedit_javascript").length > 0 && window.mass_fieldname) {
					// Updating global variables
					fieldname = mass_fieldname;
					fieldlabel = mass_fieldlabel;
					fielddatatype = mass_fielddatatype;
					fielduitype = mass_fielduitype; // crmv@83877
					count = mass_count;
				}
            }, 10);
			// crmv@29190e crmv@82831e
		}
	});
}

function mass_edit_fieldchange(selectBox) {
	var oldSelectedIndex = selectBox.oldSelectedIndex;
	var selectedIndex = selectBox.selectedIndex;

	jQuery('#massedit_field'+oldSelectedIndex).hide();
	jQuery('#massedit_field'+selectedIndex).show();

	selectBox.oldSelectedIndex = selectedIndex;
}

function mass_edit_save(){
	var masseditform = jQuery("#massedit_form").get(0);
	var module = masseditform["massedit_module"].value;
	var viewid = document.getElementById("viewname").options[document.getElementById("viewname").options.selectedIndex].value;
	var searchurl = document.getElementById("search_url").value;

	var urlstring =
		"module="+encodeURIComponent(module)+"&action="+encodeURIComponent(module+'Ajax')+
		"&return_module="+encodeURIComponent(module)+"&return_action=ListView"+
		"&mode=ajax&file=MassEditSave&viewname=" + viewid ;//+"&"+ searchurl;

	fninvsh("massedit");
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: urlstring,
		success: function(result) {
			jQuery('#status').hide();
			result = result.split("&#&#&#");
			jQuery("#ListViewContents").html(result[2]);
			if (result[1] != "") {
				alert(result[1]);
			}
			jQuery("#basicsearchcolumns").html("");
		}
	});
}

function ajax_mass_edit() {
	jQuery('#status').show();

	var masseditform = jQuery("#massedit_form").get(0);
	var module = masseditform["massedit_module"].value;

	var viewid = document.getElementById("viewname").options[document.getElementById("viewname").options.selectedIndex].value;
	var idstring = masseditform["massedit_recordids"].value;
	var searchurl = document.getElementById("search_url").value;
	var tplstart = "&";
	if (gstart != "") { tplstart = tplstart + gstart; }

	var masseditfield = masseditform['massedit_field'].value;
	var masseditvalue = masseditform['massedit_value_'+masseditfield].value;

	var urlstring =
		"module="+encodeURIComponent(module)+"&action="+encodeURIComponent(module+'Ajax')+
		"&return_module="+encodeURIComponent(module)+
		"&mode=ajax&file=MassEditSave&viewname=" + viewid +
		"&massedit_field=" + encodeURIComponent(masseditfield) +
		"&massedit_value=" + encodeURIComponent(masseditvalue) +
	   	"&idlist=" + idstring + searchurl;

	fninvsh("massedit");

	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: urlstring,
		success: function(result) {
			jQuery('#status').hide();
			result = result.split("&#&#&#");
			jQuery("#ListViewContents").html(result[2]);
			if (result[1] != "") {
				alert(result[1]);
			}
			jQuery("#basicsearchcolumns").html("");
		}
	});
}

// END

function change(obj,divid) {
	//crmv@7216
	var select_options  =  document.getElementsByName('selected_id');
	var x = select_options.length;
	var viewid =getviewId();
	var idstring = "";
	var xx = 0;
	for (var i = 0; i < x ; i++) {
		if(select_options[i].checked) {
			idstring = select_options[i].value +";"+idstring;
			xx++;
		}
	}
	var str = idstring.substr(1,(idstring.length-2));
	var idarr = str.split(";");
	var xx = idarr.length;
	//crmv@7216e
	
	if (xx != 0 && idstring !="" && idstring !=";" && idstring != 'null') {
		document.getElementById('selected_ids').value=idstring;
	} else {
		alert(alert_arr.SELECT);
		return false;
	}
	fnvshobj(obj,divid);
}

function getviewId()
{
        if(isdefined("viewname"))
        {
                var oViewname = document.getElementById("viewname");
                var viewid = oViewname.options[oViewname.selectedIndex].value;
        }
        else
        {
                var viewid ='';
        }
        return viewid;
}
var gstart='';
//crmv@fix massdelete
//crmv@30967
function massDelete(module) {

	var idstring = get_real_selected_ids(module);
	if (idstring.substr('0', '1') == ";")
		idstring = idstring.substr('1');
	var idarr = idstring.split(';');
	var count = idarr.length;
	var xx = count - 1;
	var viewid = getviewId();
	if (idstring == "" || idstring == ";" || idstring == 'null') {
		vtealert(alert_arr.SELECT);
		return false;
	} else {
		if (module == "Accounts") {
			if (xx == 1) var alert_str = sprintf(alert_arr.DELETE_ACCOUNT, xx);
			else var alert_str = sprintf(alert_arr.DELETE_ACCOUNTS, xx);
		// crmv@144123
		} else if (module == "Contacts") {
			if (xx == 1) var alert_str = sprintf(alert_arr.DELETE_CONTACT, xx);
			else var alert_str = sprintf(alert_arr.DELETE_CONTACTS, xx);
		// crmv@144123e
		} else if (module == "Vendors") {
			if (xx == 1) var alert_str = sprintf(alert_arr.DELETE_VENDOR, xx);
			else var alert_str = sprintf(alert_arr.DELETE_VENDORS, xx);
		} else {
			if (xx == 1) var alert_str = sprintf(alert_arr.DELETE_RECORD, xx);
			else var alert_str = sprintf(alert_arr.DELETE_RECORDS, xx);
		}
		vteconfirm(alert_str, function(yes) {
			if (yes) {
				//crmv@159559
				var urlstring = '';
				if (isdefined('search_url')) urlstring = jQuery('#search_url').val();
				var postbody = "module=Users&action=massdelete&return_module="+module+"&"+gstart+"&viewname="+viewid+urlstring; // crmv@27096
				var postbody2 = "module="+module+"&action="+module+"Ajax&file=ListView&ajax=true&"+gstart+"&viewname="+viewid+urlstring;
				//crmv@159559e
				jQuery('#status').show();
				jQuery.ajax({
					url: 'index.php',
					method: 'POST',
					data: postbody,
					success: function(result) {
						jQuery('#status').hide();
						result = result.split('&#&#&#');
						jQuery("#ListViewContents").html(result[2]);
						if (result[1] != '')
							vtealert(result[1]);

						jQuery('#basicsearchcolumns').html('');
						update_navigation_values(postbody2);
					}
				});
			}
		});
	}
}
//crmv@30967e
//crmv@fix massdelete end

//crmv@customview fix
function showDefaultCustomView(selectView,module,parenttab,folderid,file,modhomeid) // crmv@30967 crmv@141557
{
	//crmv@91082
	if(!SessionValidator.check()) {
		SessionValidator.showLogin();
		return false;
	}
	//crmv@91082e
	
	jQuery('#status').show();
	if (ajaxcall_list){
		ajaxcall_list.abort();
	}
	if (ajaxcall_count){
		ajaxcall_count.abort();
	}
	//crmv@7634
	var userid_url = ""
	var userid_obj = getObj("lv_user_id");
	if(userid_obj != null) {
		//crmv@29682
		if (navigator.appName == 'Microsoft Internet Explorer') {
			if (typeof(userid_obj.options) != 'undefined') {
				userid_url = "&lv_user_id="+userid_obj.options[userid_obj.options.selectedIndex].value;
			}else {
				userid_url = "&lv_user_id="+userid_obj.item(0).options[userid_obj.item(0).options.selectedIndex].value;
			}
		} else {
			userid_url = "&lv_user_id="+userid_obj.options[userid_obj.options.selectedIndex].value;
		}
		//crmv@29682e
	}
	override_orderby="";
	if(selectView == null)
		selectView = getObj("viewname")
	else
		override_orderby="&override_orderby=true";
	//crmv@7634e

	//crmv@OPER6288
	var viewName = selectView.options[selectView.options.selectedIndex].value;
	if (typeof(file) == "undefined" || file == '') var file = 'ListView';	//crmv@146666
	postbody="module="+module+"&action="+module+"Ajax&file="+file+"&ajax=true&changecustomview=true&start=1&viewname="+viewName+"&parenttab="+parenttab+userid_url+override_orderby+'&modhomeid='+modhomeid; //crmv@7634 crmv@141557
	if (folderid != undefined && folderid != '') postbody += '&folderid='+folderid; // crmv@30967

	// crmv@31245 crmv@43835 crmv@144880
	/*if(isdefined('basic_search_text')) {
		var searchrest = jQuery.data(document.getElementById('basic_search_text'), 'restored');
		var searchval = jQuery('#basic_search_text').val();
		if (searchrest == false && searchval != '') {
			postbody += '&searchtype=BasicSearch&search_field=&query=true&search_text='+encodeURIComponent(searchval);
		}
	} else */if(isdefined('search_url')) {
		postbody += jQuery('#search_url').val();
	}
	// crmv@31245e crmv@43835e crmv@144880e crmv@OPER6288e
	
	if (isdefined('append_url')) {
		postbody += jQuery('#append_url').val();
	}

	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: postbody,
		success: function(result) {
			jQuery('#status').hide();
			result = result.split('&#&#&#');
			//crmv@OPER6288
			if (file == 'KanbanView') {
				jQuery("#KanbanViewContents").html(result[2]);
				if(result[1] != '')
					alert(result[1]);
				// crmv@168361
				// fix filter menu
				jQuery('#customViewEdit').before(jQuery('#customViewEdit_Ajax'));
				jQuery('#customViewEdit').remove();
				jQuery('#customViewEdit_Ajax').attr('id', 'customViewEdit').show();
				// crmv@168361e
			} else {
				jQuery("#ListViewContents").html(result[2]);
				if(result[1] != '')
					alert(result[1]);
				//crmv@31245
				jQuery('basicsearchcolumns').html('');
				//crmv@31245e
				update_navigation_values(postbody);
				jQuery('#Buttons_List_3_Container').html(''); //crmv@24604
				ModNotificationsCommon.setFollowImgCV(viewName);	//crmv@29617
			}
			//crmv@OPER6288e
		}
	});
}
//crmv@customview fix end

function showDefaultCustomView_popup(selectView,module,parenttab)
{
	jQuery('#status').show();
	if (ajaxcall_list){
		ajaxcall_list.abort();
	}
	if (ajaxcall_count){
		ajaxcall_count.abort();
	}
	popupSearchType = '';	//crmv@44854
	//crmv@7634
	if(selectView == null) selectView = getObj("viewname");
	//crmv@7634e
	if(isdefined('search_url'))
    	var urlstring = jQuery('#search_url').val();
    else
    	var urlstring = '';
	
	var viewName = selectView.options[selectView.options.selectedIndex].value;
	
	urlstring += '&popuptype='+jQuery('#popup_type').val();
	urlstring += '&maintab='+jQuery('#maintab').val();
	urlstring += '&query=true&file=Popup&module='+module+'&action='+module+'Ajax&ajax=true&viewname='+viewName+'&changecustomview=true&start=1';
	urlstring += gethiddenelements();
	
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: urlstring,
		success: function(result) {
			jQuery('#status').hide();
			jQuery("#ListViewContents").html(result); // crmv@107661
			// don't reload the navigation, it's done in the tpl
			setListHeight(); //crmv@21048m
		}
	});
}
//crmv@add customview popup end

//crmv@74154
function adjustGridValues(urlstring){
	if(urlstring == '') return urlstring;
	
	var returnurlstring = '';
	
	var vars = urlstring.split('&');
	for(var i=0;i<vars.length;i++){
		var pair = vars[i].split("=");
		if(pair[0] == '' && typeof pair[1] == 'undefined') continue;
		
		if(pair[0].indexOf('GridSrch_value') > -1){
			pair[1] = encodeURIComponent(pair[1]);
		}
		
		returnurlstring += pair[0] + "=" + pair[1] + "&";
	}
	
	if (returnurlstring.length > 0){
		returnurlstring = "&"+returnurlstring.substring(0, returnurlstring.length-1); //chop off last "&"
	}
	
	return returnurlstring;
}
//crmv@74154e

//crmv@10759 / fix listview	//crmv@2963m
function getListViewEntries_js(module,url,async,callback) {	//crmv@48471
	
	//crmv@91082
	if(!SessionValidator.check()) {
		SessionValidator.showLogin();
		return false;
	}
	//crmv@91082e
	
	if (async == undefined) async = true;
	if (ajaxcall_list){
		ajaxcall_list.abort();
	}
	if (ajaxcall_count){
		ajaxcall_count.abort();
	}
    var viewid =getviewId();
    jQuery('#status').show();
	//crmv@74154
    if(isdefined('search_url')){
            urlstring = jQuery('#search_url').val();
			urlstring = adjustGridValues(urlstring);
	}
	//crmv@74154e
    else
            urlstring = '';
    if (isdefined('selected_ids'))
    	urlstring += "&selected_ids=" + document.getElementById('selected_ids').value;
    if (isdefined('all_ids'))
    	urlstring += "&all_ids=" + document.getElementById('all_ids').value;
    if (isdefined('modulename'))
    	var modulename=document.getElementById('modulename').value;
    else
    	modulename = '';
    gstart = url;
    postbody = "module="+module+"&modulename="+modulename+"&action="+module+"Ajax&file=ListView&ajax=true&"+url+urlstring;
    
    jQuery.ajax({
		url: 'index.php',
		type: 'POST',
		dataType: 'html',
		data: postbody,
		async: async,
		success: function(data){		
			jQuery('#status').hide();
            result = data.split('&#&#&#');
            document.getElementById("ListViewContents").innerHTML = result[2]; // crmv@194166
            if(result[1] != '')
				alert(result[1]);
            if (isdefined("basicsearchcolumns"))
            	jQuery('#basicsearchcolumns').html('');
            if (jQuery('#import_flag').val() == 1)
           		update_navigation_values(postbody);
           	//crmv@48471
            if (callback != undefined) {
            	callback(module,result);
            }
            //crmv@48471e
		}
	});
}
function update_navigation_values(url,module,async,callback) {	//crmv@48471
	if (async == undefined) async = true;
	jQuery('#status').show();
	//crmv@27924
	if(url.indexOf('index.php?')>=0){
  		var url_split = url.split('index.php?');
  		var module_var = '';
  		var action_var = '';
  		var url_vars = url_split[1].split('&');
  		for (i=0; i<url_vars.length; i++) {
  			if (url_vars[i].indexOf('module=') != -1) {
				var url_tmp = url_vars[i].split('=');
				if (url_tmp[0] == 'module') {
					module_var = url_tmp[1];
				}
			} else if (url_vars[i].indexOf('action=') != -1) {
				var url_tmp = url_vars[i].split('=');
				if (url_tmp[0] == 'action') {
					action_var = url_tmp[1];
				}
			}
		}
  		url_split[1] = url_split[1].replace('action='+action_var,'action='+module_var+'Ajax&file='+action_var);
  		url_post = url_split[1]+"&calc_nav=true";
 	} else {
  		url_post = url+"&calc_nav=true";
 	}
 	if (module != undefined && url.indexOf("module")<0){
  		url_post = "module="+module+"&action="+module+"Ajax&file=ListView&calc_nav=true";
 	}
 	//crmv@27924e
    if (isdefined('modulename'))
    	var modulename=document.getElementById('modulename').value;
    else
    	modulename = '';
    url_post+="&modulename="+modulename;

    jQuery.ajax({
		url: 'index.php',
		type: 'POST',
		dataType: 'html',
		data: url_post,
		async: async,
		success: function(data){
			var result = data.split('&#&#&#');
            var res_arr = eval ('('+result[1]+')');
            if (isdefined("nav_buttons"))
            	jQuery("#nav_buttons").html(res_arr['nav_array']);
            if (isdefined("rec_string"))
            	jQuery("#rec_string").html(res_arr['rec_string']);
            if (isdefined("nav_buttons2"))
            	jQuery("#nav_buttons2").html(res_arr['nav_array']);
            if (isdefined("rec_string2"))
            	jQuery("#rec_string2").html(res_arr['rec_string']);
            if (res_arr['permitted']){
             if (isdefined("select_all_button_top"))
             	jQuery("#select_all_button_top").show();
         	if (isdefined("select_all_button_bottom"))
         		jQuery("#select_all_button_bottom").show();
        	}
        	//crmv@29617
        	if (res_arr['reload_notification_count']) {
        		NotificationsCommon.showChangesAndStorage('CheckChangesDiv','CheckChangesImg','ModNotifications');	//crmv@OPER5904
        	}
        	//crmv@29617e
        	if (isdefined("rec_string3"))
            	jQuery("#rec_string3").html(res_arr['rec_string3']);
            jQuery('#status').hide();
            //crmv@48471
            if (callback != undefined) {
            	callback(module,result);
            }
            //crmv@48471e
		}
	});
}
//crmv@10759 e
//crmv@2963me

function update_selected_ids(checked,entityid,form)
{
    var idstring = form.selected_ids.value;
    if (idstring == "") idstring = ";";
    var all_ids = form.all_ids.value;
    if (all_ids == 1){
    	if (checked == true)
    		checked = false;
    	else
    		checked = true;
    }
    if (checked == true)
    {
    	form.selected_ids.value = idstring + entityid + ";";
    }
    else
    {
      form.selectall.checked = false;
      form.selected_ids.value = idstring.replace(entityid + ";", '');
    }
}

// crmv@72993
function update_invitees_actions(checked,entityid,form) {
	var fnname = null;
	var fnprepend = '';
	var fn = null;
	
	// json not supported
	if (!window.JSON) return;

	// get popup module
	var mod = jQuery('form[name=basicSearch] input[name=module]').val();
	if (!mod) {
		console.log('No module found');
		return;
	}
	
	// add or remove it to the list
	var funcs = JSON.parse(jQuery('#popup_select_actions').val() || '{}') || {};
	
	// if dechecked, remove from the list
	if (!checked) {
		delete funcs[entityid];
		jQuery('#popup_select_actions').val(JSON.stringify(funcs));
		return;
	}
	
	// crmv@118184
	fnname = 'addInvitee';
	fnprepend = 'parent.'; // crmv@172956
	// crmv@118184e
	
	// nothing to link
	if (!fnname) {
		console.log('No return function found');
		return;
	}

	var cbox = jQuery('input[name=selected_id][id='+entityid+']').first();
	if (cbox.length > 0) {
		// crmv@118184
		// find non empty a tags with onclick handlers
		cbox.closest('tr').find('a[onclick]').each(function(index, item) {
			var oclick = jQuery(item).attr('onclick');
			var start = oclick.indexOf(fnname);
			if (start >= 0) {
				fn = fnprepend + oclick.substring(start);
				fn = fn.replace('closePopup();', '').replace(/&quot;/g, '"');
			}
		});
		// crmv@118184e
	} else {
		console.log('No valid checkbox found');
	}
	
	if (fn) {
		// add to the array
		funcs[entityid] = fn;
		jQuery('#popup_select_actions').val(JSON.stringify(funcs));
	} else {
		console.log('No return function selected');
	}
	
}
// crmv@72993e

function select_all_page(state,form)
{
	//crmv@208173
	if(form.selected_id === undefined){
		return;
	}
	//crmv@208173e

	if (typeof(form.selected_id.length)=="undefined"){
		if (form.selected_id.checked != state){
			form.selected_id.checked = state;
			update_selected_ids(state,form.selected_id.value,form)
		}
    }
	else {
	    for (var i=0;i<form.selected_id.length;i++){
	        obj_check = form.selected_id[i];
	        if (obj_check.checked != state){
		        obj_check.checked = state;
		        update_selected_ids(state,obj_check.value,form)
	        }
	    }
    }
}
//crmv@fix listview end
//for multiselect check box in list view:

function check_object(sel_id,groupParentElementId)
{
        var select_global=new Array();
        var selected=trim(document.getElementById("allselectedboxes").value);
        select_global=selected.split(";");
        var box_value=sel_id.checked;
        var id= sel_id.value;
        var duplicate=select_global.indexOf(id);
        var size=select_global.length-1;
		var result="";
        //alert("size: "+size);
        //alert("Box_value: "+box_value);
        //alert("Duplicate: "+duplicate);
        if(box_value == true)
        {
                if(duplicate == "-1")
                {
                        select_global[size]=id;
                }

                size=select_global.length-1;
                var i=0;
                for(i=0;i<=size;i++)
                {
                        if(trim(select_global[i])!='')
                                result=select_global[i]+";"+result;
                }
                default_togglestate(sel_id.name,groupParentElementId);
        }
        else
        {
                if(duplicate != "-1")
                        select_global.splice(duplicate,1)

                size=select_global.length-1;
                var i=0;
                for(i=size;i>=0;i--)
                {
                        if(trim(select_global[i])!='')
                                result=select_global[i]+";"+result;
                }
          //      getObj("selectall").checked=false
                default_togglestate(sel_id.name,groupParentElementId);
        }

        document.getElementById("allselectedboxes").value=result;
        //alert("Result: "+result);
}
function update_selected_checkbox()
{
        var all=document.getElementById('current_page_boxes').value;
        var tocheck=document.getElementById('allselectedboxes').value;
        var allsplit=new Array();
        allsplit=all.split(";");

        var selsplit=new Array();
        selsplit=tocheck.split(";");

        var n=selsplit.length;
        for(var i=0;i<n;i++)
        {
                if(allsplit.indexOf(selsplit[i]) != "-1")
                        document.getElementById(selsplit[i]).checked='true';
        }
}

//Function to Set the status as Approve/Deny for Public access by Admin
function ChangeCustomViewStatus(viewid,now_status,changed_status,module,parenttab)
{
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=CustomView&action=CustomViewAjax&file=ChangeStatus&dmodule='+module+'&record='+viewid+'&status='+changed_status,
		success: function(result) {
			var responseVal=result;
			if(responseVal.indexOf(':#:FAILURE') > -1) {
				alert('Failed');
			} else if(responseVal.indexOf(':#:SUCCESS') > -1) {
				var values = responseVal.split(':#:');
				var module_name = values[2];
				var customview_ele = jQuery('#viewname').get[0];
				showDefaultCustomView(customview_ele, module_name, parenttab);
			} else {
				jQuery('#ListViewContents').html(responseVal);
			}
			jQuery('#status').hide();
		}
	});
}

function VT_disableFormSubmit(evt) {
	var evt = (evt) ? evt : ((event) ? event : null);
	var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
	if ((evt.keyCode == 13) && (node.type=='text')) {
		node.onchange();
		return false;
	}
	return true;
}
var statusPopupTimer = null;
function closeStatusPopup(elementid)
{
	statusPopupTimer = setTimeout("document.getElementById('" + elementid + "').style.display = 'none';", 50);
}

function updateCampaignRelationStatus(relatedmodule, campaignid, crmid, campaignrelstatusid, campaignrelstatus)
{
	jQuery("#vtbusy_info").show();
	document.getElementById('campaignstatus_popup_' + crmid).style.display = 'none';
	var data = "action=updateRelationsAjax&module=Campaigns&relatedmodule=" + relatedmodule + "&campaignid=" + campaignid + "&crmid=" + crmid + "&campaignrelstatusid=" + campaignrelstatusid;
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: data,
		success: function(result) {
			if(result.indexOf(":#:FAILURE")>-1) {
				alert(alert_arr.ERROR_WHILE_EDITING);
			} else if(result.indexOf(":#:SUCCESS")>-1) {
				document.getElementById('campaignstatus_' + crmid).innerHTML = campaignrelstatus;
				jQuery("#vtbusy_info").hide();
			}
		}
	});
}

function loadCvList(type,id) {
	// DEPRECATED, never used!
}

//crmv@add select all	//crmv@20065
function get_real_selected_ids(module){
	//crmv@21048m
	/*if (module == 'Documents') {
		allids = document.getElementById('allids').value;
		selected_ids_obj = 'allselectedboxes';
	}
	else {*/
		allids = document.getElementById('all_ids').value;
		selected_ids_obj = 'selected_ids';
	//}
	//crmv@21048m e
	ret_value = '';
	if (allids == 1){
	    jQuery('#status').show();
	    urlstring="&calc_nav=true&get_all_ids=true";
    	selected_ids = document.getElementById(selected_ids_obj).value.replace(/;/g,",");
		if (selected_ids == "" || selected_ids == ","){
		}
		else{
			if (selected_ids.substr('0','1')==","){
				selected_ids = selected_ids.substr('1');
			}
			urlstring+="&ids_to_jump="+selected_ids;
		}
		if (module == 'RecycleBin')
			urlstring+="&selected_module="+document.getElementById('selected_module').value;
    	postbody = "index.php?module="+module+"&action="+module+"Ajax&file=ListView&ajax=true&"+urlstring;
		res = getFile(postbody);
		res_ = res.split("&#&#&#");
		res_real = res_[1];
		if (module == 'RecycleBin')
			res_real = res;
		res_arr = eval ('('+res_real+')');
		if (res_arr['all_ids']){
			ret_value = res_arr['all_ids'];
		}
		jQuery('#status').hide();
	}
	else {
		ret_value = document.getElementById(selected_ids_obj).value;
		//if (module == 'Documents' && ret_value != '') ret_value = ';'+ret_value; //crmv@21048m
		//crmv@27096
		jQuery('#status').show();
		jQuery.ajax({
			url: 'index.php',
			async: false,
			method: 'POST',
			data: "module=Utilities&action=UtilitiesAjax&file=ListViewCheckSave&selected_module="+module+"&selected_ids="+ret_value,
		});
		jQuery('#status').hide();
		//crmv@27096e
	}
	return ret_value;
}
//crmv@add select all end	//crmv@20065e

/* crmv@30967 */
//moved here
function trimfValues(value) {
 var string_array;
 string_array = value.split(":");
 return string_array[4];
}
//crmv@128159
function updatefOptions(sel, opSelName) {
	var split = opSelName.split('Condition');
	var index = split[1];
	var selObj = document.getElementById(opSelName);
	var fieldtype = null;
	var currOption = selObj.options[selObj.selectedIndex];
	var currField = sel.options[sel.selectedIndex];
	currField.value = currField.value.replace(/\\'/g, '');
	var fld = currField.value.split(":");
	var tod = fld[4];
	var uitype = fld[5];
	label = getcondition(false);
	if (tod == 'D' || tod == 'DT') {
		jQuery("#and" + sel.id).html("");
		jQuery("#and" + sel.id).html("<em old='(yyyy-mm-dd)'>(" + jQuery("#user_dateformat").val() + ")</em>&nbsp;" + label);
	} else if (
		(tod == 'I' && (fld[1] == 'time_start' || fld[1] == 'time_end')) ||
		(tod == 'T' && (fld[1] == 'time_start' || fld[1] == 'time_end')) ||
		uitype == 73
	) {
		jQuery("#and" + sel.id).html("hh:mm&nbsp;" + label);
	} else if (tod == 'T') {
		jQuery("#and" + sel.id).html("");
		jQuery("#and" + sel.id).html("<em old='(yyyy-mm-dd)'>(" + jQuery("#user_dateformat").val() + " hh:mm:ss)</em>&nbsp;" + label);
	} else if (tod == 'C') {
		jQuery("#and" + sel.id).html("( Yes / No )&nbsp;" + label);
	} else {
		jQuery("#and" + sel.id).html("&nbsp;" + label);
	}
	//crmv@48693
	if (gVTModule == 'Messages' && fld[0] == 'mdate') {
		if (typeofdata['T'].indexOf('custom') == -1) {
			typeofdata['T'].push('today');
			typeofdata['T'].push('yesterday');
			typeofdata['T'].push('thisweek');
			typeofdata['T'].push('lastweek');
			typeofdata['T'].push('thismonth');
			typeofdata['T'].push('lastmonth');
			typeofdata['T'].push('last60days');
			typeofdata['T'].push('last90days');
			typeofdata['T'].push('custom');
		}
		fLabels['custom'] = alert_arr.LBL_ADVSEARCH_DATE_CUSTOM;
		fLabels['yesterday'] = alert_arr.LBL_ADVSEARCH_DATE_YESTARDAY;
		fLabels['today'] = alert_arr.LBL_ADVSEARCH_DATE_TODAY;
		fLabels['lastweek'] = alert_arr.LBL_ADVSEARCH_DATE_LASTWEEK;
		fLabels['thisweek'] = alert_arr.LBL_ADVSEARCH_DATE_THISWEEK;
		fLabels['lastmonth'] = alert_arr.LBL_ADVSEARCH_DATE_LASTMONTH;
		fLabels['thismonth'] = alert_arr.LBL_ADVSEARCH_DATE_THISMONTH;
		fLabels['last60days'] = alert_arr.LBL_ADVSEARCH_DATE_LAST60DAYS;
		fLabels['last90days'] = alert_arr.LBL_ADVSEARCH_DATE_LAST90DAYS;
	}
	//crmv@48693e
	if (currField.value != null && currField.value.length != 0) {
		fieldtype = trimfValues(currField.value);
		fieldtype = fieldtype.replace(/\\'/g, '');
		ops = typeofdata[fieldtype];
		var off = 0;
		if (ops != null) {

			var nMaxVal = selObj.length;
			for (nLoop = 0; nLoop < nMaxVal; nLoop++) {
				selObj.remove(0);
			}
			// selObj.options[0] = new Option ('None', '');
			// if (currField.value == '') {
			// selObj.options[0].selected = true;
			// }
			for ( var i = 0; i < ops.length; i++) {
				var label = fLabels[ops[i]];
				if (label == null)
					continue;
				var option = new Option(fLabels[ops[i]], ops[i]);
				selObj.options[i] = option;
				if (currOption != null && currOption.value == option.value) {
					option.selected = true;
				}
			}
		}
	} else {
		var nMaxVal = selObj.length;
		for (nLoop = 0; nLoop < nMaxVal; nLoop++) {
			selObj.remove(0);
		}
		selObj.options[0] = new Option('None', '');
		if (currField.value == '') {
			selObj.options[0].selected = true;
		}
	}
	//crmv@48693
	selObj.onchange='';
	if (gVTModule == 'Messages' && fld[0] == 'mdate') {
		selObj.onchange = function() {
			updatefOptions(document.getElementById('Fields'+index), 'Condition'+index);
		}
		var customDateValues = ['custom','yesterday','today','lastweek','thisweek','lastmonth','thismonth','last60days','last90days'];
		if (customDateValues.indexOf(currOption.value) > -1) {
			jQuery("#and" + sel.id).html("&nbsp;" + getcondition(false));
			jQuery('#Srch_value'+index).hide();
			if (jQuery('#customIntervalDates'+index).length > 0) {
				jQuery('#customIntervalDates'+index).show();
			} else {
				// crmv@190519
				// TODO: clean up the code
				jQuery('#Srch_value'+index).parent().append(
					'<div id="customIntervalDates'+index+'">'+
					'<div style="display:inline-block;padding:5px 10px;">'+
					'<div class="dvtCellLabel" style="display:inline-block;margin-right:5px;">'+alert_arr.LBL_ADVSEARCH_STARTDATE+'</div>'+
					'<div class="dvtCellInfo" style="display:inline-block;">'+
					'<input name="startdate'+index+'" id="jscal_field_date_start'+index+'" type="text" size="10" class="detailedViewTextBox input-inline" value="" onChange="setAdvSearchIntervalDateValue('+index+');">'+
					'<i class="vteicon md-link md-text" id="jscal_trigger_date_start'+index+'">event</i>'+
					'<font size="1"><em old="(yyyy-mm-dd)">('+getObj('user_dateformat').value+')</em></font>'+
					'</div>'+
					'</div>'+
					'<div style="display:inline-block;padding:5px 10px;">'+
					'<div class="dvtCellLabel" style="display:inline-block;margin-right:5px;">'+alert_arr.LBL_ADVSEARCH_ENDDATE+'</div>'+
					'<div class="dvtCellInfo" style="display:inline-block;">'+
					'<input name="enddate'+index+'" id="jscal_field_date_end'+index+'" type="text" size="10" class="detailedViewTextBox input-inline" value="" onChange="setAdvSearchIntervalDateValue('+index+');">'+
					'<i class="vteicon md-link md-text" id="jscal_trigger_date_end'+index+'">event</i>'+
					'<font size="1"><em old="(yyyy-mm-dd)">('+getObj('user_dateformat').value+')</em></font>'+
					'</div>'+
					'</div>'+
					'</div>'
				);
				setupDatePicker('jscal_field_date_start'+index, {
					trigger: 'jscal_trigger_date_start'+index,
					date_format: getObj('user_dateformat').value.toUpperCase(),
					language: current_language || 'en_us',
				});
				setupDatePicker('jscal_field_date_end'+index, {
					trigger: 'jscal_trigger_date_end'+index,
					date_format: getObj('user_dateformat').value.toUpperCase(),
					language: current_language || 'en_us',
				});
				// crmv@190519e
			}
			showADvSearchDateRange(index,currOption.value);
		} else {
			jQuery('#customIntervalDates'+index).hide();
			jQuery('#Srch_value'+index).show();
			jQuery('#Srch_value'+index).val('');
		}
	} else {
		jQuery('#customIntervalDates'+index).hide();
		jQuery('#Srch_value'+index).show();
		jQuery('#Srch_value'+index).val('');
	}
	//crmv@48693e
}
//crmv@128159e

//crmv@48693
function setAdvSearchIntervalDateValue(index) {
	jQuery('#Srch_value'+index).val(jQuery('#jscal_field_date_start'+index).val()+'|##|'+jQuery('#jscal_field_date_end'+index).val());
}
//crmv@48693e

function getcondition(mode){
	if (mode == false){
		mode = jQuery("input[name=matchtype]:checked").val(); // crmv@82419
	}

	if (mode == 'all')
		return alert_arr.LBL_AND;
	else
		return alert_arr.LBL_OR;
}

function checkgroup() {
	if(jQuery("#group_checkbox").is(':checked')) {
		document.change_ownerform_name.lead_group_owner.style.display = "block";
		document.change_ownerform_name.lead_owner.style.display = "none";
	} else {
		document.change_ownerform_name.lead_owner.style.display = "block";
		document.change_ownerform_name.lead_group_owner.style.display = "none";
	}
}
//crmv@128159
function updatefOptionsAll(mode) {
	label = getcondition(mode);
	var table = document.getElementById('adSrc');
	if (table == undefined) return;
	var customDateValues = ['custom','yesterday','today','lastweek','thisweek','lastmonth','thismonth','last60days','last90days'];	//crmv@48693
	for (i = 0; i < table.rows.length; i++) {
		var selObj = getObj('Fields' + i);
		var currField = selObj.options[selObj.selectedIndex];
		currField.value = currField.value.replace(/\\'/g, '');
		var fld = currField.value.split(":");
		if (fld[4] == 'D' || fld[4] == 'DT') {
			jQuery("#andFields" + i).html("");
			jQuery("#andFields" + i).html("<em old='(yyyy-mm-dd)'>("+ jQuery("#user_dateformat").val() + ")</em>&nbsp;" + label);
		} else if (
			(fld[4] == 'I' && (fld[1] == 'time_start' || fld[1] == 'time_end')) ||
			(fld[4] == 'T' && (fld[1] == 'time_start' || fld[1] == 'time_end')) ||
			fld[5] == 73
		) {
			jQuery("#andFields" + i).html("hh:mm&nbsp;" + label);
		} else if (fld[4] == 'T' && customDateValues.indexOf(getObj('Condition'+i).options[getObj('Condition'+i).selectedIndex].value) == -1) {	//crmv@48693
			jQuery("#andFields" + i).html("");
			jQuery("#andFields" + i).html("<em old='(yyyy-mm-dd)'>("+ jQuery("#user_dateformat").val() + " hh:mm:ss)</em>&nbsp;" + label);
		} else if (fld[4] == 'C') {
			jQuery("#andFields" + i).html("( Yes / No )&nbsp;" + label);
		} else {
			jQuery("#andFields" + i).html("&nbsp;" + label);
		}
	}
}
//crmv@128159e
// crmv@31245 crmv@150593
function callSearch(searchtype, folderid, callback) {

	if (gVTModule == undefined || gVTModule == '') return;
	if (gVTModule == 'Messages') {
		if (ajax_enable == false) return false;	
		ajax_enable = false;
	}
	
	//crmv@91082
	if(!SessionValidator.check()) {
		SessionValidator.showLogin();
		return false;
	}
	//crmv@91082e

	if (ajaxcall_list) {
		ajaxcall_list.abort();
	}
	if (ajaxcall_count) {
		ajaxcall_count.abort();
	}
	jQuery('#status').show();
	
	if (document.getElementById("all_ids") != null && document.getElementById("all_ids").value == 1) unselectAllIds();	//crmv@43893
	
	var p_tab = jQuery('[name="parenttab"]');	//crmv@148128
	var postbody = {
		'module' : gVTModule,
		'action' : gVTModule+'Ajax',
		'file' : 'ListView',
		'ajax' : 'true',
		'query' : 'true',
		'search' : 'true',
		'parenttab' : p_tab.length > 0 ? p_tab.val() : ''	//crmv@148128
	};
	if (document.massdelete) postbody['idlist'] = document.massdelete.selected_ids.value;
	if (folderid != undefined && folderid != '' && folderid > 0) postbody['folderid'] = folderid; // crmv@30967
	//crmv@2963m
	if (gVTModule == 'Messages') {
		postbody['folder'] = document.massdelete.folder.value;
		postbody['thread'] = document.massdelete.thread.value;
		postbody['account'] = document.massdelete.account.value;
	}
	//crmv@2963me

	var extraParams = {}
	if (searchtype == 'Basic' || searchtype == 'BasicGlobalSearch') {	//crmv@148128
	
		resetListSearch('Advanced',folderid,'no');
		basic_search_submitted = true; // crmv@31245
		
		extraParams = getSearchParams(extraParams,searchtype);
		extraParams = getSearchParams(extraParams,'Grid');	// keep the grid search
		
	} else if (searchtype == 'Advanced') {
	
		resetListSearch('Basic',folderid,'no');
		resetListSearch('BasicGlobalSearch',folderid,'no');	//crmv@148128
		advance_search_submitted = true;

		extraParams = getSearchParams(extraParams,searchtype);
		extraParams = getSearchParams(extraParams,'Grid');	// keep the grid search
		
	} else if (searchtype == 'Grid' || searchtype == 'AreaGlobalSearch') {	//crmv@148128
	
		grid_search_submitted = true;
		extraParams = getSearchParams(extraParams,searchtype);
		// keep the basic/advance search
		if (basic_search_submitted) {
			//crmv@159559 crmv@163519
			if (jQuery('#basic_search_text').length > 0)
				extraParams = getSearchParams(extraParams,'Basic');
			else {
				if (jQuery('#unifiedsearchnew_query_string').length == 0) {
					// if search panel is not loaded load it in background
					var url = 'index.php?module=Home&action=HomeAjax&file=HeaderSearchMenu'; 
					if (jQuery('#search_url').length > 0) url += jQuery('#search_url').val(); //crmv@159559
					jQuery.ajax({
						url: url,
						type: 'POST',
						async: false,
						success: function(res){
							VTE.FastPanelManager.mainInstance.renderContainer('searchCont', '');
							// do getSearchParams outside because this ajax call is async
						}
					});
				}
				extraParams = getSearchParams(extraParams,'BasicGlobalSearch');
			}
			//crmv@159559e crmv@163519e
		} else if (advance_search_submitted) {
			extraParams = getSearchParams(extraParams,'Advanced');
		}
	
	//crmv@43942
	} else if (searchtype == 'Area') {
		
		basic_search_submitted = true;
		postbody['file'] = 'index';
		postbody['query'] = jQuery('#basic_search_query').val();
		postbody['area'] = jQuery('#basic_search_area').val();
		extraParams = getSearchParams(extraParams,searchtype);
	//crmv@43942e
	}
	
	if (!('viewname' in postbody)) postbody['viewname'] = getviewId();	//crmv@144880
	
	var postbody = jQuery.param(postbody);
	
	if (isdefined('append_url')) {
		postbody += jQuery('#append_url').val();
	}
	
	if (extraParams && !jQuery.isEmptyObject(extraParams)) var extraParams = '&'+jQuery.param(extraParams);
	else var extraParams = '';
	jQuery.ajax({
		'url': 'index.php?' + postbody,
		'type': 'POST',
		'data': extraParams,
		success: function(data) {
			jQuery('#status').hide();
			var result = data.split('&#&#&#');
			jQuery("#ListViewContents").html(result[2]); // crmv@104119
			if (result[1] != '')
				alert(result[1]);
			if (searchtype != 'Area' && searchtype != 'AreaGlobalSearch') {	//crmv@43942 crmv@148128
				jQuery('#basicsearchcolumns').html('');
				//crmv@2963m crmv@103872
				if (gVTModule == 'Messages') {
					ajax_enable = true;
					setmCustomScrollbar('#ListViewContents');
					//crmv@OPER8279 if no result in search auto search in next step
					if (jQuery('#nav_buttons').find('#count_results_search_intervals').val() == 0 && jQuery('#nav_buttons').find('#navigation_search').val() == 1) {
		           		continueMessagesSearch();
		           	}
		           	//crmv@OPER8279
					if (callback != undefined) callback(result);
					return;
				}
				//crmv@2963me crmv@103872e
				//update_navigation_values(postbody+extraParams); //crmv@163519 already done in ListViewEntries.tpl
				if (callback != undefined) callback(result);	// TODO callback nella update_navigation_values
			}
		}
	})
	return false;
}
//crmv@150593e

function getSearchParams(extraParams,searchtype) {
	
	if (searchtype == 'Basic') {
	
		extraParams['searchtype'] = 'BasicSearch';
		extraParams['search_text'] = encodeURIComponent(jQuery('#basic_search_text').val());
		
	} else if (searchtype == 'Advanced') {
	
		var no_rows = jQuery('#basic_search_cnt').val();
		for (jj = 0; jj < no_rows; jj++) {
			var sfld_name = getObj("Fields" + jj);
			var scndn_name = getObj("Condition" + jj);
			var srchvalue_name = getObj("Srch_value" + jj);
			var currOption = scndn_name.options[scndn_name.selectedIndex];
			var currField = sfld_name.options[sfld_name.selectedIndex];
			currField.value = currField.value.replace(/\\'/g, '');
			var fld = currField.value.split(":");
			var convert_fields = new Array();
			if (fld[4] == 'D' || (fld[4] == 'T' && fld[1] != 'time_start' && fld[1] != 'time_end') || fld[4] == 'DT') {
				convert_fields.push(jj);
			}
			extraParams['Fields'+jj] = sfld_name[sfld_name.selectedIndex].value;
			extraParams['Condition'+jj] = scndn_name[scndn_name.selectedIndex].value;
			extraParams['Srch_value'+jj] = encodeURIComponent(srchvalue_name.value);
		}
		for (i = 0; i < getObj("matchtype").length; i++) {
			if (getObj("matchtype")[i].checked == true)
				extraParams['matchtype'] = getObj("matchtype")[i].value;
		}
		if (convert_fields.length > 0) {
			var fields_to_convert;
			for (i = 0; i < convert_fields.length; i++) {
				fields_to_convert += convert_fields[i] + ';';
			}
			extraParams['fields_to_convert'] = fields_to_convert;
		}
		extraParams['searchtype'] = 'advance';
		extraParams['search_cnt'] = no_rows;
		
	} else if (searchtype == 'Grid') {

		if (jQuery('#gridsearch_script').length > 0) eval(jQuery('#gridsearch_script').html()); // crmv@116251
		if (typeof(gridsearch) != 'undefined') { 
			var ii = 0;
			jQuery.each( gridsearch, function(i,fieldname) {
				var value = jQuery('#gridSrc [name="gs_'+fieldname+'"]').val();
				if (typeof value != 'undefined' && value != '') { // crmv@104114
					extraParams['GridFields'+ii] = fieldname;
					extraParams['GridCondition'+ii] = 'c';
					extraParams['GridSrch_value'+ii] = encodeURIComponent(value);
					ii++;
				}
			});
			if (ii > 0) extraParams['GridSearchCnt'] = ii;
		}

	//crmv@43942
	} else if (searchtype == 'Area') {
	
		extraParams['searchtype'] = 'BasicSearch';
		extraParams['search_text'] = encodeURIComponent(jQuery('#basic_search_text').val());
	//crmv@43942e
	}
	return extraParams;
}
//crmv@31245e

//crmv@3084m
function resetListSearch(searchtype,folderid,reload) {

	if (reload == undefined || reload == '') reload = 'auto';
	
	if (searchtype == 'Basic') {
	
		if (reload == 'yes') basic_search_submitted = true;
		else if (reload == 'no') basic_search_submitted = false;
		
		jQuery('#basic_search_icn_canc').click();
		
	} else if (searchtype == 'Advanced') {
	
		if (jQuery('#adSrc').length == 0) return;	//crmv@55194
	
		var tableName = document.getElementById('adSrc');
		var prev = tableName.rows.length;
		if (prev > 1) {
			for (var i=1;i<prev;i++) {
				delRow();
			}
		}

		jQuery('#adSrc #Fields0').val(jQuery('#adSrc #Fields0 option:first').val());
		jQuery('#adSrc #Condition0').val(jQuery('#adSrc #Condition0 option:first').val());
		jQuery('#adSrc #Srch_value0').val('');
		advancedSearchOpenClose('close');	// crmv@105588
		
		if (reload == 'yes') advance_search_submitted = true;
		else if (reload == 'no') advance_search_submitted = false;
		if (advance_search_submitted) {
			jQuery('#search_url').val('');
			if (jQuery('#viewname').length > 0) jQuery('#viewname').change(); else jQuery('#basicSearch').submit();	//crmv@77815
			advance_search_submitted = false;
		}
	} else if (searchtype == 'Grid') {
	
		jQuery.each( gridsearch, function(i,fieldname) {
			jQuery('#gridSrc [name="gs_'+fieldname+'"]').val('');
			// for select input
			if (jQuery('#gridSrc [name="gs_'+fieldname+'Str"]').length > 0) {
				jQuery('#gridSrc [name="gs_'+fieldname+'Str"]').val('');
				jQuery('input:checkbox[id^="'+fieldname+'"]').each(function(i,o){
					jQuery(o).prop('checked',false);
				});
				jQuery('#'+fieldname+'GridSelect').hide();
				jQuery('#'+fieldname+'GridInput').show();
			}
		});
		if (reload == 'yes') grid_search_submitted = true;
		else if (reload == 'no') grid_search_submitted = false;
		if (grid_search_submitted) {
			callSearch('Grid',folderid);
			grid_search_submitted = false;
		}
		
	}
}

function callGridSearch(event,type,folderid) {
	if (type == 'select') {
		callSearch('Grid',folderid);
	} else if (type == 'input') {
		if (event.which == 13){
			callSearch('Grid',folderid);
		}
	}
}

function gridSelectToggle(obj,c,field) {
	var checked = jQuery(obj).prop('checked');
	jQuery('input:checkbox[id^="'+c+'"]').each(function(i,o){
		if (jQuery(o).attr('id') == c+'All') {
			return;
		}
		if (checked) {
			jQuery(o).prop('checked',true);
		} else {
			jQuery(o).prop('checked',false);
		}
		gridSelectValue(o,field)
	});
}

function gridSelectValue(o,field) {
	var value = jQuery(o).val();
	if (jQuery('#'+field).val() == '') {
		var arr = new Array();
	} else {
		var arr = jQuery('#'+field).val().split('|##|');
	}
	if (jQuery(o).prop('checked') == true) {
		if (arr.indexOf(value) == -1) {
			arr.push(value);
		}
	} else {
		for (var key in arr) {
		    if (arr[key] == value) {
		        arr.splice(key, 1);
		    }
		}
	}
	jQuery('#'+field).val(arr.join('|##|'));
}
//crmv@3084me

// crmv@31245 - removed stuff

//----------

function lviewfold_showTooltip(folderid) {
	if (lviewFolder.disabled == true) return; // crmv@30976
	jQuery('#lviewfold_tooltip_'+folderid).show();
	lviewFolder.hidden = false;
}

function lviewfold_hideTooltip(folderid) {
	if (lviewFolder.disabled == true) return; // crmv@30976
	jQuery('#lviewfold_tooltip_'+folderid).hide();
	lviewFolder.hidden = true;
}

function lviewfold_moveTooltip(folderid) {
	if (!lviewFolder.hidden) {
		var newx, newy;
		var ttip = jQuery('#lviewfold_tooltip_'+folderid);
		tw = ttip.width();
		th = ttip.height();
		dw = jQuery(document).width();
		dh = jQuery(document).height();
		dx = dy = 10;
		if (lviewFolder.x + dx + tw > dw) {
			newx = dw - tw;
		} else {
			newx = lviewFolder.x+dx;
		}
		if (lviewFolder.y + dy + th > dh) {
			newy = dh - th;
		} else {
			newy = lviewFolder.y+dy;
		}
		ttip.css({'left':newx, 'top':newy});
	}
}

function lviewfold_add() {

	var baseurl = 'index.php?module=Utilities&action=UtilitiesAjax&file=FolderHandler';
	var formdata = jQuery('#lview_folder_addform').serialize();

	jQuery('#status').show();
	jQuery.ajax({
		type: 'POST',
		url: baseurl,
		data: formdata,
		success: function(data, tstatus) {
			if (data.substr(0, 7) == 'ERROR::') {
				jQuery('#status').hide();
				window.alert(data.substr(7));
			} else {
				location.reload();
			}
		}
	});
}

function lviewfold_del() {
	var checklist = jQuery('#lview_table_cont span[id^=lview_folder_checkspan]');
	if (checklist.length == 0) return window.alert(alert_arr.LBL_NO_EMPTY_FOLDERS);
	jQuery('#lviewfolder_button_del').hide();
	jQuery('#lviewfolder_button_add').hide();
	jQuery('#lviewfolder_button_list').hide();
	jQuery('#lviewfolder_button_del_cancel').show();
	jQuery('#lviewfolder_button_del_save').show();
	checklist.show();
	// crmv@30976 - ingrigisce le altre cartelle
	lviewFolder.disabled = true;
	jQuery('#lview_table_cont .lview_folder_td[data-deletable="0"]').css({opacity: 0.5});
	// crmv@30976e
}

function lviewfold_del_cancel() {
	jQuery('#lviewfolder_button_del').show();
	jQuery('#lviewfolder_button_add').show();
	jQuery('#lviewfolder_button_list').show();
	jQuery('#lviewfolder_button_del_cancel').hide();
	jQuery('#lviewfolder_button_del_save').hide();
	jQuery('#lview_table_cont span[id^=lview_folder_checkspan]').hide();
	// crmv@30976
	lviewFolder.disabled = false;
	jQuery('#lview_table_cont .lview_folder_td').css({opacity: 1});
	// crmv@30976e
}

function lviewfold_del_save(module) {
	var delids = [];
	jQuery('#lview_table_cont input[type=checkbox]:checked').each(function (idx, el) {
		delids.push(parseInt(el.id.replace('lvidefold_check_', '')));
	});

	if (delids.length == 0) return window.alert(alert_arr.LBL_SELECT_DEL_FOLDER);

	var baseurl = 'index.php?module=Utilities&action=UtilitiesAjax&file=FolderHandler&subaction=del';
	var formdata = 'folderids='+delids.join(',')+'&formodule='+module;

	jQuery('#status').show();
	jQuery.ajax({
		type: 'POST',
		url: baseurl,
		data: formdata,
		success: function(data, tstatus) {
			if (data.substr(0, 7) == 'ERROR::') {
				jQuery('#status').hide();
				window.alert(data.substr(7));
			} else {
				location.reload();
			}
		}
	});
}
/* crmv@30967e */

//crmv@31245	//crmv@42846
function clearText(elem) {
	var jelem = jQuery(elem);
	var rest = jQuery.data(elem, 'restored');
	if (rest == undefined || rest == true) {
		jelem.val('');
		jQuery('#basic_search_icn_canc').show();
		jQuery.data(elem, 'restored', false);
	}
}
function restoreDefaultText(elem, deftext) {
	var jelem = jQuery(elem);
	if (jelem.val() == '') {
		jQuery('#basic_search_icn_canc').hide();
		jQuery.data(elem, 'restored', true);
		if (basic_search_submitted == true) {
			jQuery('#basicSearch').submit();
			basic_search_submitted = false;
		}
		jelem.val(deftext);
	}
}
function cancelSearchText(deftext) {
	jQuery('#basic_search_text').val('');
	restoreDefaultText(document.getElementById('basic_search_text'), deftext);
	var gridParams = {};
	gridParams = getSearchParams(gridParams,'Grid');
	if (gridParams['GridSearchCnt'] > 0) {
		basic_search_submitted = false;
	}
}
//crmv@31245e	//crmv@42846e

// crmv@105588
function advancedSearchOpenClose(mode) {
	if (typeof(mode) == 'undefined') var mode = 'auto';
	var $iconElement = jQuery('#adv_search_icn_go');
	if (mode == 'auto') {
		var open = jQuery('#advSearch:visible').length > 0;
		var materialIcon = open ? 'keyboard_arrow_down' : 'keyboard_arrow_up';
		$iconElement.html(materialIcon);
		jQuery('#advSearch').slideToggle('fast');
	} else if (mode == 'open') {
		$iconElement.html('keyboard_arrow_up');
		jQuery('#advSearch').slideDown('fast');
	} else if (mode == 'close') {
		$iconElement.html('keyboard_arrow_down');
		jQuery('#advSearch').slideUp('fast');
	}
}
//crmv@105588e

//crmv@159559
function populateAdvancedSearchForm(advanced_search_params) {
	if (!jQuery.isEmptyObject(advanced_search_params)) {
		setTimeout(function() {
			if (advanced_search_params.rows && advanced_search_params.rows.length > 0) {
				resetListSearch('Advanced', null, 'no');
				jQuery.each(advanced_search_params.rows, function(i, row) {
					if (i > 0) fnAddSrch();
					jQuery('#Fields'+i).val(jQuery('#Fields'+i+' option[value^="'+row['Fields']+'"]').val());
					jQuery('#Condition'+i).val(row['Condition']);
					jQuery('#Srch_value'+i).val(row['Srch_value']);
				});
			}
			if (advanced_search_params.matchtype) {
				jQuery('#matchtype_'+advanced_search_params.matchtype).prop("checked", true);
				updatefOptionsAll(advanced_search_params.matchtype);
			}
			advancedSearchOpenClose('auto');
			advance_search_submitted = true;
		}, 100);
	}
}
//crmv@159559e

// crmv@171261
function onAdvSearchFieldKeyPress(e) {
	if ((e.keyCode == 13)) {
		e.preventDefault();
		jQuery('#adv-searchnow-btn').click();
		return false;
	}
}
// crmv@171261e