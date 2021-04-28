/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@150266 */

var ListRoles = ListRoles || {
	
	hideAll: false,
	parentId: "",
	parentName: "",
	childId: "NULL",
	childName: "NULL",
	
	displayCoords: function(event) {
		var move_Element = document.getElementById('Drag_content').style;
		if(!event) {
			move_Element.left = e.pageX +'px' ;
			move_Element.top = e.pageY+10 + 'px';	
		} else {
			move_Element.left = event.clientX +'px' ;
			move_Element.top = event.clientY+10 + 'px';	
		}
	},
	
	fnRevert: function(e) {
		var me = this;
		if(e.button == 2) {
			document.getElementById('Drag_content').style.display = 'none';
			me.hideAll = false;
			me.parentId = "Head";
			me.parentName = "DEPARTMENTS";
			me.childId ="NULL";
			me.childName = "NULL";
		}
	},
	
	get_parent_ID: function(obj,currObj) {
		var me = this,
			leftSide = findPosX(obj),
			topSide = findPosY(obj),
			move_Element = document.getElementById('Drag_content');
		
		me.childName = document.getElementById(currObj).innerHTML;
		me.childId = currObj;
		move_Element.innerHTML = me.childName;
		move_Element.style.left = leftSide + 15 + 'px';
		move_Element.style.top = topSide + 15+ 'px';
		move_Element.style.display = 'block';
		me.hideAll = true;	
	},
	
	// crmv@192033
	put_child_ID: function(currObj) {
		var me = this,
			move_Element = document.getElementById('Drag_content');
		
		me.parentName  = jQuery('#'+currObj).html();
		me.parentId = currObj;
		move_Element.style.display = 'none';
		me.hideAll = false;	
		if(me.childId == "NULL") {
			me.parentId = me.parentId.replace(/user_/gi,'');
			window.location.href="index.php?module=Settings&action=RoleDetailView&parenttab=Settings&roleid="+me.parentId;
		} else {
			me.childId = me.childId.replace(/user_/gi,'');
			me.parentId = me.parentId.replace(/user_/gi,'');
			jQuery.ajax({
				url: 'index.php',
				method: 'POST',
				data: 'module=Users&action=UsersAjax&file=RoleDragDrop&ajax=true&parentId='+me.parentId+'&childId='+me.childId,
				success: function(result) {
					if (result != alert_arr.ROLE_DRAG_ERR_MSG) {
						jQuery('#RoleTreeFull').html(result);
						me.hideAll = false;
						me.parentId = "";
						me.parentName = "";
						me.childId ="NULL";
						me.childName = "NULL";
					} else {
						alert(result);
					}
				}
			});
		}
	},
	// crmv@192033e

	fnVisible: function(Obj) {
		var me = this;
		if(!me.hideAll) {
			jQuery('#'+Obj).css('visibility', 'visible');
		}
	},

	fnInVisible: function(Obj) {
		jQuery('#'+Obj).css('visibility', 'hidden');
	},

	showhide: function(argg,imgId) {
		var harray=argg.split(",");
		var harrlen = harray.length;	

		for(var i=0; i<harrlen; i++) {
			var x=document.getElementById(harray[i]).style;
			if (x.display=="none") {
				x.display="block";
				jQuery('#'+imgId).text('indeterminate_check_box');
			} else {
				x.display="none";
				jQuery('#'+imgId).text('add_box');
			}
		}
	},
	
	ajaxCall: function(service, params, options, callback) {
		var me = this;
			
		params = jQuery.extend({}, {
			displayVersion: false,
			versionContainer: 'listRolesVersion',
		}, params || {});
		
		options = jQuery.extend({}, options || {});
		
		var url = 'index.php?module=Settings&action=SettingsAjax&file=ListRolesAjax&ajax=true&parenttab=Settings&sub_mode='+service;
		
		jQuery('#status').show();
		jQuery.ajax({
			url: url,
			method: 'POST',
			data: params,
			success: function(response) {
				if (params.displayVersion && params.versionContainer) {
					jQuery("#"+params.versionContainer).html(response);
				}
				jQuery('#status').hide();
				if (typeof callback == 'function') callback(response);
			}
		});
	},
	
	closeVersion: function(callback) {
		var me = this;
		me.ajaxCall('closeVersion', {displayVersion: true}, {}, function(response){
			if (typeof callback == 'function') callback();
		});
	},
	
	exportVersion: function() {
		var me = this,
			module = jQuery('input[name=fld_module]').val(),
			url = 'index.php?module=Settings&action=SettingsAjax&file=ListRolesAjax&sub_mode=exportVersion';
		
		me.ajaxCall('checkExportVersion', {}, {}, function(response){
			if (response != '') alert(response);
			else location.href = url;
		});
	}
}