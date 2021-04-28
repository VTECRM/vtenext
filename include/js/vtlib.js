/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@168103 */

/**
 * Generic uitype popup selection handler
 */
function vtlib_setvalue_from_popup(recordid,value,target_fieldname) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	if (form) {
	//crmv@29190e
		var domnode_id = form.elements[target_fieldname];
		var domnode_display = form.elements[target_fieldname+'_display'];
		if (typeof(domnode_display) == 'undefined' && target_fieldname == 'parent_id') domnode_display = form.elements['parent_name'];
		if (domnode_id) domnode_id.value = recordid;
		if (domnode_display) domnode_display.value = value.replace(/&amp;/g, '&');
		parent.disableReferenceField(domnode_display,domnode_id,form.elements[target_fieldname+'_mass_edit_check']);	//crmv@29190 crmv@112297
		return true;
	} else {
		return false;
	}
}

/**
 * Show the vte field help if available.
 */
function vtlib_field_help_show(basenode, fldname, helpcontent) { // crmv@191909
	var domnode = document.getElementById('vtlib_fieldhelp_div');

	// crmv@191909
	if (typeof(helpcontent) == 'undefined' || helpcontent === null) {
		if(typeof(fieldhelpinfo) == 'undefined') return;
		helpcontent = fieldhelpinfo[fldname];
		if(typeof(helpcontent) == 'undefined') return;
	}
	// crmv@191909e

	if(!domnode) {
		domnode = document.createElement('div');
		domnode.id = 'vtlib_fieldhelp_div';
		domnode.className = 'small layerPopup'; // crmv@27520
		domnode.style.position = 'absolute';
		domnode.style.width = '150px';
		domnode.style.padding = '4px';
		domnode.style.fontWeight = 'normal';
		document.body.appendChild(domnode);	

		jQuery(domnode).on('mouseover', function() {
			jQuery('#vtlib_fieldhelp_div').show(); 
		});
		jQuery(domnode).on('mouseout', function() {
			jQuery('#vtlib_fieldhelp_div').hide(); 
		});
	} else {
		jQuery('#vtlib_fieldhelp_div').show();
	}
	domnode.innerHTML = helpcontent;
	
	// crmv@171132
	var pos = jQuery(basenode).offset(); // crmv@191909
    jQuery(domnode).css({
		top: pos.top+'px',
		left: pos.left+'px',
	}).show();
    // crmv@171132e
}


/**
 * Listview Javascript Event handlers API
 * 
 * Example: 
 * vtlib_listview.register('cell.onmouseover', function(evtparams, moreparams) { console.log(evtparams); }, [10,20]);
 * vtlib_listview.register('cell.onmouseout', function(evtparams) {console.log(evtparams); });
 */
var vtlib_listview = {
	/**
	 * Callback function handlers that needs to be triggered for an event
	 * 
	 * _handlers = {
	 *     'event1' : [ [handlerfn11, handlerfn11_moreparams], [handlerfn2, handlerfn12_moreparams] ],
	 *     'event2' : [ [handlerfn21, handlerfn21_moreparams], [handlerfn2, handlerfn22_moreparams] ]
	 * }
	 */
	_handlers : {},
		
	/**
	 * Register handler function for the event
	 */
	register : function(evttype, handler, callback_params) {
		if(typeof(callback_params) == 'undefined') callback_params = false;
		if(typeof(vtlib_listview._handlers[evttype]) == 'undefined') {
			vtlib_listview._handlers[evttype] = [];
		}
		// Event handlerinfo is an array having (function, optional_more_parameters)
		vtlib_listview._handlers[evttype].push([handler, callback_params]);
	},

	/**
	 * Invoke handler function based on event type
	 */
	invoke_handler : function(evttype, event_params) {
		var evthandlers = vtlib_listview._handlers[evttype];
		if(typeof(evthandlers) == 'undefined') return;
		for(var index = 0; index < evthandlers.length; ++index) {
			var evthandlerinfo = evthandlers[index];
			// Event handlerinfo is an array having (function, optional_more_parameters)
			var evthandlerfn = evthandlerinfo[0];
			if(typeof(evthandlerfn) == 'function') {
				evthandlerfn(event_params, evthandlerinfo[1]);
			}
		}
	},
	
	/**
	 * Trigger handler function for the event
	 */
	trigger  : function(evttype, node) {
		if(evttype == 'cell.onmouseover' || evttype == 'cell.onmouseout') {
			// Catch hold of DOM element which has meta inforamtion.
			var innerNodes = node.getElementsByTagName('span');		
			if(typeof(innerNodes) != 'undefined') {
				var cellhandler = false;
				for(var index = 0; index < innerNodes.length; ++index) {
					var innerNodeAttrs = innerNodes[index].attributes;
					if(typeof(innerNodeAttrs) != 'undefined' && typeof(innerNodeAttrs.type) != 'undefined' && innerNodeAttrs['type'].nodeValue == 'vtlib_metainfo') {
						cellhandler = innerNodes[index];
						break;
					}
				}
				if(cellhandler == false) return;
				var event_params = {
					'event'  : evttype,
					'domnode': node,
					'module' : cellhandler.attributes['vtmodule'].nodeValue,
					'fieldname': cellhandler.attributes['vtfieldname'].nodeValue,
					'recordid': cellhandler.attributes['vtrecordid'].nodeValue
				}
				vtlib_listview.invoke_handler(evttype, event_params);
			}
		} 
	}
}
/** END **/

/** 
 * DetailView widget loader API
 */
function vtlib_loadDetailViewWidget(urldata, target, indicator, callback) {	//crmv@52912

	if (typeof(target) == 'undefined') {
		target = false;
	} else {
		target = document.getElementById(target);
	}
	
	if (typeof(indicator) == 'undefined') {
		indicator = false;
	} else {
		indicator = document.getElementById(indicator);
	}
	
	if (target && target.style.display != 'none') {
		return false;
	} else {
		jQuery(target).show();
	}
	
	if (indicator) jQuery(indicator).show();

	//crmv@31360
	jQuery.ajax({
		url: 'index.php?'+urldata,
		type: 'POST',
		dataType: 'html',
		success: function(data){
        	if (target) {
        		jQuery(target).html(data);
        		if (indicator) jQuery(indicator).hide();
				if (typeof callback == "function") callback();	//crmv@52912
        	}
		}
	});
	//crmv@31360e	
	
	return false; // To stop event propogation
}