/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@150024 */

function loadCvListTargets(type,id) {
	var filter = jQuery('#'+type+"_cv_list").val();

	if (filter == '' || filter == 'None')  return false;
	
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php?module=Targets&action=TargetsAjax&file=LoadList&ajax=true&return_action=DetailView&return_id='+id+'&list_type='+type+'&cvid='+filter,
		method: 'GET',
		success: function(response) {
			// crmv@200009
			jQuery.ajax({
				url: 'index.php?module=Targets&action=TargetsAjax&file=GetData&ajax=true&return_action=DetailView&return_id='+id+'&list_type='+type+'&cvid='+filter,
				cache: false,
				success: function(response3) {
					var parts = response3.split('###');
					var limit = Number(parts[1]);
					var count = Number(parts[0]);

					if(limit < count)
					{
						vtealert(alert_arr.LBL_LOAD_RELATIONS_ENQUEUE.replace('{max_records}', limit), function() {

						});
					}
				}
			});
			// crmv@200009e
			jQuery('#status').hide();
			parent.reloadTurboLift('Targets', id, type);	//crmv@52414

		}
	});

}

//crmv@36539
function return_report_to_rl(id,name,field) {
	jQuery('#'+field).val(id);
	jQuery('#'+field+"_display").val(name);
	disableReferenceField(jQuery('#'+field+'_display')[0]);
}

function popupReport_rl(mode,module,title,field) {
	if (mode == 'edit') {
		var reportid = jQuery('#'+field).val();
		if (reportid == '') {
			return false;
		}
		var arg = 'index.php?module=Reports&action=ReportsAjax&file=EditReport&return_module=Targets:'+field+'&reportmodule='+module+'&reportname='+title+'&record='+reportid; // crmv@158088
	} else {
		var arg = 'index.php?module=Reports&action=ReportsAjax&file=EditReport&return_module=Targets:'+field+'&reportmodule='+module+'&reportname='+title; // crmv@158088
	}
	openPopup(arg);
}

function loadReportListTargets(reportid,targetid,relatedmodule) {
	
	if (reportid == '') return false;

	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php?module=Targets&action=TargetsAjax&file=LoadReport&ajax=true&return_action=DetailView&return_id='+targetid+'&reportid='+reportid+'&relatedmodule='+relatedmodule,
		method: 'GET',
		success: function(response) {
			// crmv@200009
			jQuery.ajax({
				url: 'index.php?module=Targets&action=TargetsAjax&file=GetData&ajax=true&return_action=DetailView&return_id='+targetid+'&reportid='+reportid+'&relatedmodule='+relatedmodule,
				cache: false,
				success: function(response3) {
					var parts = response3.split('###');
					var limit = Number(parts[1]);
					var count = Number(parts[0]);

					if(limit < count)
					{
						vtealert(alert_arr.LBL_LOAD_RELATIONS_ENQUEUE.replace('{max_records}', limit), function() {

						});
					}
				}
			});
			// crmv@200009e
			jQuery('#status').hide();
			parent.reloadTurboLift('Targets', targetid, relatedmodule);	//crmv@52414
		}
	});

}
//crmv@36539e


function showDynamicFilters(self) {
	changeTab(gVTModule, null, 'dynamicTargetsPanel', self);
}

function deleteDynamicFilter(targetid, type, objectid, formodule) {
	
	vteconfirm(alert_arr.ARE_YOU_SURE, function(yes) {
		if (yes) {
			jQuery.ajax({
				url: 'index.php?module=Targets&action=TargetsAjax&file=DynTargetActions&subaction=delfilter',
				method: 'POST',
				data: {
					targetid: targetid,
					type: type,
					objectid: objectid,
					formodule: formodule,
				},
				success: function(html) {
					if (html) {
						jQuery('#dynamicTargetsPanel').replaceWith(html);
						jQuery('#dynamicTargetsPanel').show();
					}
					
				}
			});
		}
	});
}