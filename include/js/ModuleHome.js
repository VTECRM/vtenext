/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@83340 crmv@96155 crmv@98431 crmv@104259 crmv@105193 */
// crmv@205568 add container to report chooser

var ModuleHome = {
	
	dragLibrary: 'jQueryUI',	// crmv@192014 only jQueryUI supported
	
	blocksCache: {},
	
	reportData: null,
	
	editMode: false,
	
	listViewCounts: null,
	
	listViewCountsChanges: null,
	
	initialize: function(containerid) {
		var me = this;
		
		if (!document.getElementById(containerid)) {
			// no container, skip
			return;
		}

		// Scriptaculous is deprecated, use jQueryUI
		if (me.dragLibrary == 'Scriptaculous' && window.Sortable) {
			Sortable.create(
				containerid,
				{
					constraint: false,
					tag: 'div', 
					overlap: 'Horizontal',
					handle: 'headerrow',
					onUpdate: function() {
						me.saveSequence();
					}
				}
			);
		} else if (me.dragLibrary == 'jQueryUI') {
			jQuery('#'+containerid).sortable({
				handle: '.headerrow',
				items: '> div', // crmv@192014 
				forcePlaceholderSize: true, // crmv@192014 
				opacity: 0.75,
				revert: 200,
				update: function() {
					me.saveSequence();
				}
			});
		} else {
			console.log('Unable to initialize the sorting library');
		}
		
		// resize handler
		if (jQuery.throttle) {
			jQuery(window).on('resize', jQuery.throttle(500, me.onWindowResize).bind(me));
		} else {
			console.log('jQuery throttle plugin not found. The resize event won\'t be intercepted for performance reasons');
		}
	},
	
	onWindowResize: function() {
		var me = this;
		for (var blockid in me.blocksCache) {
			me.positionBlock.apply(me, me.blocksCache[blockid]);
		}
	},
	
	enterEditMode: function() {
		var me = this;
		
		if (me.editMode) return;
		
		// hide the buttons
		// TODO: add common class to the container
		if (window.current_theme === 'next') {
			jQuery('#moduleSettingsTd').hide();
		} else {
			jQuery('#moduleSettingsTd').closest('tr').find('>td').hide();
		}
		jQuery('#moduleSettingsResetTd').show();
		jQuery('#add_home_views').show();
		
		var cont = jQuery('#Buttons_List_HomeMod');
		cont.find('span[id^=pencil_]').show();
		
		VTE.ListViewCounts.show(); // crmv@151995
		me.listViewCounts = VTE.ListViewCounts.getValue();
		
		me.editMode = true;
	},
	
	leaveEditMode: function() {
		var me = this;
		
		if (!me.editMode) return;
		
		// show the buttons
		// TODO: add common class to the container
		if (window.current_theme === 'next') {
			jQuery('#moduleSettingsTd').show();
		} else {
			jQuery('#moduleSettingsTd').closest('tr').find('>td').show();
		}
		jQuery('#moduleSettingsResetTd').hide();
		jQuery('#add_home_views').hide();
		
		var cont = jQuery('#Buttons_List_HomeMod');
		cont.find('span[id^=pencil_]').hide();
		
		VTE.ListViewCounts.hide(); // crmv@151995
		me.checkListViewCounts();
		
		me.editMode = false;
	},
	
	// crmv@151995
	
	editListViewCounts: function(selectView, module, folderid) {
		var me = this;
		
		me.listViewCountsChanges = {
			selectView: selectView, 
			module: module, 
			folderid: folderid,
		};
	},
	
	checkListViewCounts: function() {
		var me = this;
		
		if (!me.listViewCountsChanges) return false;
		
		var currentListViewCount = VTE.ListViewCounts.getValue();
		if (currentListViewCount === me.listViewCounts) return false;
		
		var selectView = me.listViewCountsChanges.selectView;
		var module = me.listViewCountsChanges.module;
		var folderid = me.listViewCountsChanges.folderid;

		VTE.ListViewCounts.onShowMoreEntries(selectView, module, folderid);
		me.listViewCountsChanges = null;
		
		return true;
	},
	
	// crmv@151995e
	
	toggleEditMode: function() {
		var me = this;
		
		if (me.editMode) {
			return me.leaveEditMode();
		} else {
			return me.enterEditMode();
		}
	},
	
	saveSequence: function() {
		var me = this;
		var modhomeid = jQuery('#modhomeid').val();
		var blocks = [];
		
		jQuery('#MainMatrix .modblock').each(function(index, item) {
			var id = parseInt(item.id.replace('modblock_', ''));
			if (id > 0) {
				blocks.push(id);
			}
		});
		
		if (blocks.length == 0) return;
		var params = {
			modhomeid: modhomeid,
			blockids: blocks.join(':'),
		}
		
		me.ajaxRequest('savesequence', params);
	},
	
	changeView: function(modhomeid) {
		var me = this;
		var url = "index.php?module="+gVTModule+'&action=HomeView&modhomeid='+modhomeid;
		if (me.editMode) url += '&editmode=1';
		location.href = url;
	},
	
	addView: function() {
		showFloatingDiv('ModHomeAddView', null, {modal: true});
	},
	
	//crmv@102334
	addListView: function() {
		var me = this;
		
		me.ajaxRequest('cvlist', {}, null, function(data) {
			jQuery('#homecvid').html(data);
			showFloatingDiv('ModHomeAddListView', null, {modal: true});
		});
	},
	//crmv@102334e
	
	addReportView: function() {
		var me = this;
		
		me.ajaxRequest('reportlist', {}, null, function(data) {
			me.reportData = data;
			me.buildReportChooser(data, 'ModHomeAddViewReport');
			showFloatingDiv('ModHomeAddViewReport', null, {modal: true});
		});
	},
	
	buildReportChooser: function(data, container) {
		var me = this,
			target = jQuery('#'+container+' #reportChooserFolder');

		// TODO: Do it properly with css/tpl
		var html = '';
		jQuery.each(data, function(folderid, folder) {
			if (folder && folder.reports && folder.reports.length > 0) {
				html += '<div style="float:left;padding:10px;width:140px;height:140px;text-align:center"><div><img src="'+resourcever('listview_folder.png')+'" border="0" width="96" style="cursor:pointer" onclick="ModuleHome.clickReportFolder(this, '+folderid+', \''+container+'\');"/></div><div style="text-align:center">'+folder.foldername+' ('+folder.reports.length+')</div></div>';
			}
		});
		
		// clean the id
		jQuery('#chooserReportName').val('');
		jQuery('#chooserReportId').val('');
		jQuery('#'+container+' #reportChooserList').hide();
		
		target.html(html).show();
	},
	
	clickReportFolder: function(self, folderid, container) {
		var me = this,
			html = '',
			folder = me.reportData[folderid],
			list = folder.reports;

		// TODO: Do it properly with css/tpl
		html += '<div style="margin:8px;font-weight:bold"><img src="'+resourcever('folderback.png')+'" style="cursor:pointer" align="bottom" onclick="ModuleHome.clickReportBack(this, \''+container+'\');" alt="'+alert_arr.LBL_BACK+'" title="'+alert_arr.LBL_BACK+'" border="0"/>&nbsp;&nbsp;'+folder.foldername+(folder.description ? '<span style="color:#C0C0C0;font-style:italic"> - '+folder.description+'</span>' : '') + '</div>';
		html += '<table width="95%" style="margin:12px">';
		html += '<tr><td class="lvtCol">'+alert_arr.LBL_REPORT_NAME+'</td><td class="lvtCol">'+alert_arr.LBL_DESCRIPTION+'</td></tr>';
		jQuery.each(list, function(idx, report) {
			html += '<tr><td class="lvtColData"><a href="javascript:;" onclick="ModuleHome.clickReport(this, '+folderid+', '+report.reportid+', \''+container+'\');">'+report.reportname+'</a></td><td class="lvtColData">'+report.description+'</td></tr>';
		});
		html += "</table>";
			
		jQuery('#'+container+' #reportChooserFolder').hide();
		jQuery('#'+container+' #reportChooserList').html(html).show();
	},
	
	clickReportBack: function(self, container) {
		var me = this;
		
		jQuery('#'+container+' #reportChooserList').hide();
		jQuery('#'+container+' #reportChooserFolder').show();
	},
	
	clickReport: function(self, folderid, reportid, container) {
		var me = this,
			folder = me.reportData[folderid],
			list = folder.reports;
		
		jQuery.each(list, function(idx, report) {
			if (report.reportid == reportid) {
				var name = jQuery('#homeviewname2').val();
				jQuery('#chooserReportName').val(report.reportname);
				jQuery('#chooserReportId').val(report.reportid);
				//crmv@199319
				jQuery('#chooserEditReportName').val(report.reportname);
				jQuery('#chooserEditReportId').val(report.reportid);
				jQuery('#homevieweditname').val(report.reportname);
				//crmv@199319e
				if (!name) jQuery('#homeviewname2').val(report.reportname);
				return false;
			}
		});
		
		jQuery('#'+container+' #reportChooserList').hide();
		jQuery('#'+container+' #reportChooserFolder').show();
	},
	
	createView: function() {
		var me = this;
		var name = jQuery('#homeviewname').val();
		
		if (!name) {
			return vtealert(alert_arr.ENTER_VALID+' '+alert_arr.LBL_NAME);
		}
		
		me.ajaxRequest('addview', {viewname: name}, null, function(homeid) {
			if (homeid > 0) {
				var newloc = window.location.href.replace(/&modhomeid=[0-9]*/, '');
				var url = newloc.replace(/&editmode=[01]/, '') + '&modhomeid='+homeid;
				if (me.editMode) url += '&editmode=1';
				window.location.href = url;
			}
		});
	},

	doEdit: function() {
		var me = this;
		var name = jQuery('#homevieweditname').val();
		var chReportid = jQuery('#chooserEditReportId').val();
		var chCvid = jQuery('#homecvidedit').val();
		var modhomeidedit = jQuery("input[name=modhomeidedit]").val();


		if (!name) {
			return vtealert(alert_arr.ENTER_VALID+' '+alert_arr.LBL_NAME);
		}

		me.ajaxRequest('editview', {modhomeid: modhomeidedit, viewname: name, reportid: chReportid, cvid: chCvid}, null, function(homeid) {
			if (homeid > 0) {
				var newloc = window.location.href.replace(/&modhomeid=[0-9]*/, '');
				var url = newloc.replace(/&editmode=[01]/, '') + '&modhomeid='+homeid;
				if (me.editMode) url += '&editmode=1';
				window.location.href = url;
			}
		});
	},
	
	//crmv@102334
	createListView: function() {
		var me = this;
		var name = jQuery('#homeviewname3').val(),
			cvid = jQuery('#homecvid').val();
		
		if (!name) {
			return vtealert(alert_arr.ENTER_VALID+' '+alert_arr.LBL_NAME);
		}
		
		me.ajaxRequest('addview', {viewname: name, cvid: cvid}, null, function(homeid) {
			if (homeid > 0) {
				var newloc = window.location.href.replace(/&modhomeid=[0-9]*/, '');
				var url = newloc.replace(/&editmode=[01]/, '') + '&modhomeid='+homeid;
				if (me.editMode) url += '&editmode=1';
				window.location.href = url;
			}
		});
	},
	//crmv@102334e
	
	createReportView: function() {
		var me = this,
			name = jQuery('#homeviewname2').val(),
			reportid = jQuery('#chooserReportId').val();
		
		if (!name) {
			return vtealert(alert_arr.ENTER_VALID+' '+alert_arr.LBL_NAME);
		}
		
		if (!reportid) {
			return vtealert(alert_arr.ENTER_VALID+' Report');
		}
		
		me.ajaxRequest('addview', {viewname: name, reportid: reportid}, null, function(homeid) {
			if (homeid > 0) {
				var newloc = window.location.href.replace(/&modhomeid=[0-9]*/, '');
				var url = newloc.replace(/&editmode=[01]/, '') + '&modhomeid='+homeid;
				if (me.editMode) url += '&editmode=1';
				window.location.href = url;
			}
		});
	},
	
	removeView: function(modhomeid, name, ask) {
		var me = this,
			currentId = jQuery('#modhomeid').val();
		
		if (ask) {
			vteconfirm(alert_arr.ARE_YOU_SURE, function(yes) {
				if (yes) doRemove();
			});
		} else {
			doRemove();
		}
		
		function doRemove() {
			me.ajaxRequest('removeview', {modhomeid: modhomeid}, null, function() {
				if (modhomeid == currentId) {
					var newloc = window.location.href.replace(/&modhomeid=[0-9]*/, '');
					var url = newloc.replace(/&editmode=[01]/, '');
					if (me.editMode) url += '&editmode=1';
					window.location.href = url;
				} else {
					window.location.reload();
				}
			});
		}
	},

	//crmv@199319
	editView: function(modhomeid, name, reportid, cvid) {
		var me = this,
			currentId = jQuery('#modhomeid').val();

		jQuery("input[name=homevieweditname]").val(name);
		jQuery("input[name=modhomeidedit]").val(modhomeid);


		jQuery("#editViewReport").hide();
		jQuery("#editViewCV").hide();

		if(reportid !== '')
		{
			jQuery("#editViewReport").show();
			me.ajaxRequest('reportlist', {}, null, function(data) {
				me.reportData = data;
				me.buildReportChooser(data, 'ModHomeEditView');
				showFloatingDiv('ModHomeEditView', null, {modal: true});
				jQuery('#chooserEditReportName').val(name);
				jQuery('#chooserEditReportId').val(reportid);

				var folderid = null;
				jQuery.each(me.reportData, function (idx, ent){
					folderid = idx;
					var temp = me.reportData[folderid];
					var templist = temp.reports;

					jQuery.each(templist, function(idx, report) {
						if (report.reportid == reportid) {
							return false;
						}
					});
					return false;
				});

				if(folderid !== null)
				{
					folder = me.reportData[folderid];
					list = folder.reports;

					jQuery.each(list, function(idx, report) {
						if (report.reportid == reportid) {
							jQuery('#chooserEditReportName').val(report.reportname);
							jQuery('#chooserEditReportId').val(report.reportid);
							return false;
						}
					});
				}
			});
		}
		else if(cvid !== '')
		{
			me.ajaxRequest('cvlist', {}, null, function(data) {
				jQuery('#homecvidedit').html(data);
				showFloatingDiv('ModHomeEditView', null, {modal: true});
				jQuery("#editViewCV").show();
				jQuery('select[id="homecvidedit"]').find('option[value="' + cvid + '"]').attr("selected",true);
			});
		}
		else
		{
			showFloatingDiv('ModHomeEditView', null, {modal: true});
		}
	},
	//crmv@199319e
	
	showLoader: function(blockid) {
		if (jQuery('#toggle_' + blockid).length > 0) {
			jQuery('#toggle_' + blockid).hide();
		}
		jQuery('#refresh_'+blockid).html(jQuery('#modhome_loader').html());
		
	},
	
	hideLoader: function(blockid) {
		if (jQuery('#toggle_' + blockid).length > 0) {
			jQuery('#toggle_' + blockid).show();
		}
		jQuery('#refresh_'+blockid).html('');
	},
	
	ajaxRequest: function(action, params, options, success, failure) {
		var me = this,
			module = gVTModule;

		// default options
		options = jQuery.extend({}, {
			showBlockLoader: true,
			rawData: false,
		}, options || {});
		
		if (options.showBlockLoader && params.blockid) me.showLoader(params.blockid);
		jQuery.ajax({
			url: 'index.php?module='+module+'&action='+module+'Ajax&file=HomeAjax&ajxaction='+action,
			method: 'GET',
			data: params,
			success: function(res) {
				if (options.showBlockLoader && params.blockid) me.hideLoader(params.blockid);
				if (res) {
					if (options.rawData) {
						if (typeof success == 'function') success(res);
						return;
					}
					try {
						var data = JSON.parse(res);
						if (data.success) {
							if (typeof success == 'function') success(data.result);
						} else {
							console.log('Error in retrieving data from server: '+data.error);
							if (typeof failure == 'function') failure();
						}
					} catch(e) {
						console.log(e);
						console.log('Invalid data returned from server: '+res);
						if (typeof failure == 'function') failure();
					}
				} else {
					console.log('Invalid data returned from server: '+res);
					if (typeof failure == 'function') failure();
				}
			},
			error: function() {
				if (options.showBlockLoader && params.blockid) me.hideLoader(params.blockid);
				console.log('Ajax error');
				if (typeof failure == 'function') failure();
			}
		});
	},
	
	
	loadBlock: function(modhomeid, blockid, callback) {
		var me = this;
		
		var params = {
			modhomeid: modhomeid,
			blockid: blockid,
		};
		
		me.ajaxRequest('loadblock', params, null, function(result) {
			me.processAjaxBlock(modhomeid, blockid, result);
		}, function() {
			console.log('Error loading block #'+blockid);
		});

	},
	
	loadBlocks: function(modhomeid, blockids, callback, options) { // crmv@135195
		var me = this;
		
		// crmv@128133
		if (blockids.length == 0) {
			if (typeof callback == 'function') callback();
			return;
		}
		// crmv@128133e
		
		var blocklist = blockids.join(':');
		var params = {
			modhomeid: modhomeid,
			blockids: blocklist,
		};
		
		// crmv@135195
		options = options || {};
		if (options.reload) params.reload = 'true';
		// crmv@135195e
		
		me.ajaxRequest('loadblocks', params, null, function(result) {
			for (blockid in result) {
				if (result.hasOwnProperty(blockid)) {
					me.processAjaxBlock(modhomeid, blockid, result[blockid]);
				}
			}
			if (typeof callback == 'function') callback(); // crmv@135195
		}, function() {
			console.log('Error loading blocks');
		});
		
	},
	
	processAjaxBlock: function(modhomeid, blockid, content) {
		var me = this;
		
		jQuery('#blockcont_'+blockid).html(content);
	},
	
	removeBlock: function(modhomeid, blockid) {
		var me = this;
		var module = gVTModule;
		
		vteconfirm(alert_arr.ARE_YOU_SURE, function(yes) {
			if (yes) {
				me.ajaxRequest('removeblock', {modhomeid: modhomeid, blockid: blockid}, null, function(result) {
					jQuery('#modblock_'+blockid).remove();
				});
			}
		});
		
	},
	
	chooseNewBlock: function(modhomeid) {
		jQuery('#newblock_config_div').html('');
		jQuery('#newblock_select').val('');
		jQuery('#newblock_modhomeid').val(modhomeid);
		showFloatingDiv('ChooseNewBlock', null, {modal:true});
	},
	
	// crmv@102379
	addBlock: function(modhomeid, type) {
		var me = this;
		var params = {
			modhomeid: modhomeid,
			type: type,
		}
		
		if (type == 'Chart') {
			params.chartid = jQuery('#select_chart').val();
		}else if (type == 'QuickFilter') {
			params.cvid = jQuery('#select_qfilter').val();
		}else if (type == 'Filter') {
			params.cvid = jQuery('#select_filter').val();
		}else if (type == 'Processes') { // crmv@96233
			// not implemtented
		}else if (type == 'Wizards') {
			var wizids = jQuery('#select_wizards').val();
			if (!wizids || wizids.length == 0) return;
			params.wizards = JSON.stringify(wizids);
		} else {
			vtealert('Non implementato');
			return;
		}
		
		me.showLoader();
		me.ajaxRequest('addblock', params, null, function() {
			hideFloatingDiv('ChooseNewBlock');
			window.location.reload();
		});
	},
	// crmv@102379e
	
	loadNewBlockConfig: function() {
		var me = this,
			modhomeid = jQuery('#newblock_modhomeid').val(),
			type = jQuery('#newblock_select').val();
		
		if (!type) {
			jQuery('#newblock_config_div').html('');
			return;
		}
		
		jQuery('#newblock_config_div').html('');
		me.ajaxRequest('confignewblock', {type: type, modhomeid: modhomeid}, {rawData: true}, function(data) {
			jQuery('#newblock_config_div').html(data);
		});
	
	},
	
	positionBlock: function(blockid, type, size) {
		var me = this,
			layout = parseInt(jQuery('#blockcolumns').val()) || 4,
			spacing = 9,
			scale = 1,
			widgetWidth;

		me.blocksCache[blockid] = [blockid, type, size];

		var columns = Math.max(2, Math.min(layout, 4));
		size = Math.max(1, Math.min(size || 1, columns));

		switch(layout){
			case 2:
				widgetWidth = 49;
				break;
			case 3:
				widgetWidth = 32;
				break;
			case 4:
			default:
				widgetWidth = 24;
				break;
		}

		var mainX = parseInt(jQuery("#MainMatrix").width()); // crmv@97209
		var dx = ((mainX * widgetWidth * size) / 100 + (size-1) * spacing) * scale;

		jQuery('#modblock_'+blockid).width(dx);
	},
	
	clickRecord: function(slvid, module, crmid, entityname) {
		var url = 'index.php?module='+module+'&action=DetailView&record='+crmid;
		window.open(url, '_blank');
	}
	
}