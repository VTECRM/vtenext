/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Homestuff = VTE.Homestuff || {
	
	busy: false,
	
	showBusy: function(sid) {
		var me = VTE.Homestuff;
		me.busy = true;

		if (jQuery('#toggle_' + sid).length > 0) {
			jQuery('#toggle_' + sid).hide();
		}
		
		jQuery('#refresh_' + sid).html(jQuery('#vtbusy_homeinfo').html());
	},
	
	hideBusy: function(sid) {
		var me = VTE.Homestuff;
		me.busy = false;
		
		if (jQuery('#toggle_' + sid).length > 0) {
			jQuery('#toggle_' + sid).show();
		}
		
		jQuery('#refresh_' + sid).html("");
	},
	
	/**
	 * this function is used to show hide the columns in the add widget div based on the option selected
	 * @param string typeName - the selected option
	 */
	chooseType: function(typeName) {
		jQuery('#status').show();
		jQuery('#stufftype_id').val(typeName);

		var typeLabel = alert_arr[typeName] || typeName;
		jQuery('#divHeader').html("<b>" + alert_arr.LBL_ADD + typeLabel + "</b>");

		if (typeName == 'Module') {
			jQuery('#moduleNameRow').show();
			jQuery('#moduleFilterRow').show();
			jQuery('#modulePrimeRow').show();
			jQuery('#showrow').show();
			jQuery('#rssRow').hide();
			jQuery('#dashNameRow').hide();
			jQuery('#dashTypeRow').hide();
			jQuery('#StuffTitleId').show();
			jQuery('#homeURLField').hide();
			jQuery('#chartRow').hide(); // crmv@30014
			VTE.Homestuff.setFilter(document.getElementById('selmodule_id'));
		} else if (typeName == 'RSS') {
			jQuery('#moduleNameRow').hide();
			jQuery('#moduleFilterRow').hide();
			jQuery('#modulePrimeRow').hide();
			jQuery('#showrow').show();
			jQuery('#rssRow').show();
			jQuery('#dashNameRow').hide();
			jQuery('#dashTypeRow').hide();
			jQuery('#StuffTitleId').show();
			jQuery('#status').hide();
			jQuery('#homeURLField').hide();
			jQuery('#chartRow').hide(); // crmv@30014
			VTE.Homestuff.showWidget();
		} else if (typeName == 'Default') {
			jQuery('#moduleNameRow').hide();
			jQuery('#moduleFilterRow').hide();
			jQuery('#modulePrimeRow').hide();
			jQuery('#showrow').hide();
			jQuery('#rssRow').hide();
			jQuery('#dashNameRow').hide();
			jQuery('#dashTypeRow').hide();
			jQuery('#StuffTitleId').hide();
			jQuery('#url_id').hide();
			jQuery('#chartRow').hide(); // crmv@30014
		} else if (typeName == 'URL') {
			jQuery('#moduleNameRow').hide();
			jQuery('#moduleFilterRow').hide();
			jQuery('#modulePrimeRow').hide();
			jQuery('#showrow').hide();
			jQuery('#rssRow').hide();
			jQuery('#dashNameRow').hide();
			jQuery('#dashTypeRow').hide();
			jQuery('#StuffTitleId').show();
			jQuery('#status').hide();
			jQuery('#homeURLField').show();
			jQuery('#chartRow').hide(); // crmv@30014
			VTE.Homestuff.showWidget();
		}
		// crmv@30014
		else if (typeName == 'Charts') {
			jQuery('#moduleNameRow').hide();
			jQuery('#moduleFilterRow').hide();
			jQuery('#modulePrimeRow').hide();
			jQuery('#showrow').hide();
			jQuery('#rssRow').hide();
			jQuery('#dashNameRow').hide();
			jQuery('#dashTypeRow').hide();
			jQuery('#StuffTitleId').show();
			jQuery('#homeURLField').hide();
			jQuery('#chartRow').show();
			jQuery.ajax({
				url: 'index.php',
				method: 'POST',
				data: 'module=Charts&action=ChartsAjax&file=GetHomeCharts&type=picklist',
				success: function(result) {
					jQuery('#selChartName').html(result);
					VTE.Homestuff.showWidget();
					jQuery('#status').hide();
				}
			});
		}
		// crmv@30014e
	},

	/**
	 * this function is used to set the filter list when the module name is changed
	 * @param string modName - the modula name for which you want the filter list
	 */
	setFilter: function(modName) {
		var modval = jQuery(modName).val() || "";
		jQuery('#savebtn').prop('disabled', true);
		if (modval.length > 0) {
			jQuery.ajax({
				url: 'index.php',
				method: 'POST',
				data: 'module=Home&action=HomeAjax&file=HomestuffAjax&modname=' + modval,
				success: function(result) {
					jQuery('#selModFilter_id').html(result);
					VTE.Homestuff.setPrimaryFld(document.getElementById('selFilterid'));
					VTE.Homestuff.showWidget();
					jQuery('#savebtn').prop('disabled', false);
					jQuery('#status').hide();
				}
			});
		}
	},

	/**
	 * this function is used to set the field list when the module name is changed
	 * @param string modName - the modula name for which you want the field list
	 */
	setPrimaryFld: function(Primeval) {
		var primecvid = jQuery(Primeval).val() || "";
		var fldmodule = jQuery('#selmodule_id').val();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Home&action=HomeAjax&file=HomestuffAjax&primecvid=' + primecvid + '&fieldmodname=' + fldmodule,
			success: function(result) {
				jQuery('#selModPrime_id').html(result);
			}
		});
	},

	/**
	 * this function displays the div for selecting the number of rows in a widget
	 * @param string sid - the id of the widget for which the div is being displayed
	 */
	showEditrow: function(sid) {
		jQuery('#editRowmodrss_' + sid).removeClass("hide_tab").addClass("show_tab");
	},

	/**
	 * this function is used to hide the div for selecting the number of rows in a widget
	 * @param string editRow - the id of the div
	 */
	cancelEntries: function(editRow) {
		jQuery('#' + editRow).removeClass("show_tab").addClass("hide_tab");
	},

	/**
	 * this function is used to save the maximum entries that a widget can display
	 * @param string selMaxName - the widget name
	 */
	saveEntries: function(selMaxName) {
		var me = VTE.Homestuff;
		var sidarr = selMaxName.split("_");
		var sid = sidarr[1];
		me.showBusy(sid);
		VTE.Homestuff.cancelEntries('editRowmodrss_' + sid);
		var showmax = jQuery('#' + selMaxName).val() || "";
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Home&action=HomeAjax&file=HomestuffAjax&showmaxval=' + showmax + '&sid=' + sid,
			success: function(result) {
				eval(result);
				me.hideBusy(sid);
			}
		});
	},

	//crmv@30014
	saveHomeChart: function(selSize) {
		var me = VTE.Homestuff;
		var sidarr = selSize.split("_");
		var sid = sidarr[1];
		me.showBusy(sid);
		VTE.Homestuff.cancelEntries('editRowmodrss_' + sid);
		var showmax = jQuery('#' + selSize).val() || "";
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Charts&action=ChartsAjax&file=SaveHomeChart&size=' + showmax + '&stuffid=' + sid,
			success: function(result) {
				location.reload();
			}
		});
	},
	//crmv@30014e

	/**
	 * this function is used to save the url of a widget
	 * @param string selurl
	 */
	saveEditurl: function(selurl) {
		var me = VTE.Homestuff;
		var sidarr = selurl.split("_");
		var sid = sidarr[1];
		me.showBusy(sid);
		VTE.Homestuff.cancelEntries('editRowmodrss_' + sid);
		var url = jQuery('#' + selurl).val() || "";
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Home&action=HomeAjax&file=HomestuffAjax&url=' + url + '&sid=' + sid,
			success: function(result) {
				eval(result);
				me.hideBusy(sid);
			}
		});
	},

	/**
	 * this function is used to delete widgets form the home page
	 * @param string sid - the stuffid of the widget
	 */
	DelStuff: function(sid) {
		if (confirm(alert_arr.SURE_TO_DELETE)) {
			jQuery.ajax({
				url: 'index.php',
				method: 'POST',
				data: 'module=Home&action=HomeAjax&file=HomestuffAjax&homestuffid=' + sid,
				success: function(result) {
					if (result.indexOf('SUCCESS') > -1) {
						var delchild = document.getElementById('stuff_' + sid);
						document.getElementById('MainMatrix').removeChild(delchild);
						jQuery('#seqSettings').hide();
						jQuery('#seqSettings').html('<table cellpadding="10" cellspacing="0" border="0" width="100%" class="vtResultPop small"><tr><td align="center">' + alert_arr.LBL_DELETED_SUCCESSFULLY + '</td></tr></table>');
						placeAtCenter(document.getElementById('seqSettings'));
						jQuery('#seqSettings').fadeIn(); // crmv@168103
						setTimeout(VTE.Homestuff.hideSeqSettings, 3000);
					} else {
						alert(alert_arr.ERROR_DELETING_TRY_AGAIN)
					}
				}
			});
		}
	},

	/**
	 * this function loads the newly added div to the home page
	 * @param string stuffid - the id of the newly created div
	 * @param string stufftype - the stuff type for the new div (for e.g. rss)
	 */
	loadAddedDiv: function(stuffid, stufftype, stuffsize) { // crmv@30014
		var gstuffId = stuffid;
		stuffsize = stuffsize || 0; // crmv@30014
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Home&action=HomeAjax&file=NewBlock&stuffid=' + stuffid + '&stufftype=' + stufftype,
			success: function(result) {
				jQuery('#MainMatrix').html(result + jQuery('#MainMatrix').html());
				VTE.Homestuff.positionDivInAccord('stuff_' + gstuffId, '', stufftype, stuffsize); // crmv@30014
				VTE.Homestuff.initHomePage();
				VTE.Homestuff.loadStuff(stuffid, stufftype);
				jQuery('#MainMatrix').show();
			}
		});
	},

	/**
	 * this function is used to reload a widgets' content based on its id and type
	 * @param string stuffid - the widget id
	 * @param string stufftype - the type of the widget
	 */
	loadStuff: function(stuffid, stufftype) {
		var me = VTE.Homestuff;
		me.showBusy(stuffid);
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Home&action=HomeAjax&file=HomeBlock&homestuffid=' + stuffid + '&blockstufftype=' + stufftype,
			success: function(result) {
				jQuery('#stuffcont_' + stuffid).html(result); // crmv@82770 - changed to support script tags
				me.hideBusy(stuffid);

				var anchorElement = jQuery('#a_' + stuffid);
				var moreValue = jQuery('#more_' + stuffid).val() || "";
				var cvidValue = jQuery('#cvid_' + stuffid).val() || "";

				if (stufftype == "Module" && moreValue.length > 0) {
					anchorElement.attr('href', "index.php?module=" + moreValue + "&action=ListView&viewname=" + cvidValue);
				} else if (stufftype == "Default" && anchorElement.length > 0) {
					if (moreValue.length > 0) {
						anchorElement.show();
						var url = "index.php?module=" + moreValue + "&action=index" + (jQuery('#search_qry_' + stuffid).val() || "");
						anchorElement.attr('href', url);
					} else {
						anchorElement.hide();
					}
				} else if (stufftype == "RSS") {
					anchorElement.attr('href', moreValue);
				} else if (stufftype == "Iframe") {
				}
			}
		});
	},

	loadAllWidgets: function(widgetInfoList, batchSize) {
		var me = VTE.Homestuff;
		var batchWidgetInfoList = [];
		var widgetInfo = {};
		for (var index = 0; index < widgetInfoList.length; ++index) {
			var widgetId = widgetInfoList[index].widgetId;
			var widgetType = widgetInfoList[index].widgetType;
			widgetInfo[widgetId] = widgetType;
			me.showBusy(widgetId);
			batchWidgetInfoList.push(widgetInfoList[index]);
			if (((index + 1) % batchSize == 0) || index + 1 == widgetInfoList.length) {
				jQuery.ajax({
					url: 'index.php?module=Home&action=HomeAjax&file=HomeWidgetBlockList',
					type: 'POST',
					data: '&widgetInfoList=' + JSON.stringify(batchWidgetInfoList),
					dataType: 'json',
					success: function(responseVal) {
						for (var widgetId in responseVal) {
							if (responseVal.hasOwnProperty(widgetId)) {
								jQuery('#stuffcont_' + widgetId).html(responseVal[widgetId]); // crmv@82770
								me.hideBusy(widgetId);

								var widgetType = widgetInfo[widgetId];
								var anchorElement = jQuery('#a_' + widgetId);
								var moreValue = jQuery('#more_' + widgetId).val() || "";
								var cvidValue = jQuery('#cvid_' + widgetId).val() || "";

								if (widgetType == "Module" && moreValue.length > 0) {
									anchorElement.attr('href', "index.php?module=" + moreValue + "&action=ListView&viewname=" + cvidValue);
								} else if (widgetType == "Default" && anchorElement.length > 0) {
									if (moreValue.length > 0) {
										anchorElement.show();
										var url = "index.php?module=" + moreValue + "&action=index" + (jQuery('#search_qry_' + widgetId).val() || "");
										anchorElement.attr('href', url);
									} else {
										anchorElement.hide();
									}
								} else if (widgetType == "RSS") {
									anchorElement.attr('href', moreValue);
								} else if (widgetType == "Iframe") {
								}
							}
						}
					}
				});
				batchWidgetInfoList = [];
			}
		}
	},

	/**
	 * this function validates the form for creating a new widget
	 */
	frmValidate: function() {
		if (trim(jQuery('#stufftitle_id').val()) == "") {
			alert(alert_arr.LBL_ENTER_WINDOW_TITLE);
			jQuery('#stufftitle_id').focus();
			return false;
		}
		if (jQuery('#stufftype_id').val() == "RSS") {
			if (jQuery('#txtRss_id').val() == "") {
				alert(alert_arr.LBL_ENTER_RSS_URL);
				jQuery('#txtRss_id').focus();
				return false;
			}
		}
		if (jQuery('#stufftype_id').val() == "URL") {
			if (jQuery('#url_id').val() == "") {
				alert(alert_arr.LBL_ENTER_URL);
				jQuery('#url_id').focus();
				return false;
			}
		}
		if (jQuery('#stufftype_id').val() == "Module") {
			var fieldval = new Array();
			var cnt = 0;
			selVal = document.Homestuff.PrimeFld;
			for (k = 0; k < selVal.options.length; k++) {
				if (selVal.options[k].selected) {
					fieldval[cnt] = selVal.options[k].value;
					cnt = cnt + 1;
				}
			}
			if (cnt > 2) {
				alert(alert_arr.LBL_SELECT_ONLY_FIELDS);
				selVal.focus();
				return false;
			} else {
				document.Homestuff.fldname.value = fieldval;
			}
		}
		var stufftype = jQuery('#stufftype_id').val();
		var stufftitle = jQuery('#stufftitle_id').val();
		jQuery('#stufftitle_id').val('');
		var selFiltername = '';
		var fldname = '';
		var selmodule = '';
		var maxentries = '';
		var txtRss = '';
		var seldashbd = '';
		var selchart = ''; // crmv@30014
		var seldashtype = '';
		var seldeftype = '';
		var txtURL = '';

		if (stufftype == "Module") {
			selFiltername = document.Homestuff.selFiltername[document.Homestuff.selFiltername.selectedIndex].value;
			fldname = fieldval;
			selmodule = jQuery('#selmodule_id').val();
			maxentries = jQuery('#maxentryid').val();
		} else if (stufftype == "RSS") {
			txtRss = jQuery('#txtRss_id').val();
			maxentries = jQuery('#maxentryid').val();
		} else if (stufftype == "URL") {
			txtURL = jQuery('#url_id').val();
		} else if (stufftype == "Charts") {
			selchart = jQuery('#selchart_id').val();
		} else if (stufftype == "Default") {
			seldeftype = document.Homestuff.seldeftype[document.Homestuff.seldeftype.selectedIndex].value;
		}

		var url = "stufftype=" + stufftype + "&stufftitle=" + stufftitle + "&selmodule=" + selmodule + "&maxentries=" + maxentries + "&selFiltername=" + selFiltername + "&fldname=" + encodeURIComponent(fldname) + "&txtRss=" + txtRss + "&seldashbd=" + seldashbd + "&seldashtype=" + seldashtype + "&seldeftype=" + seldeftype + '&txtURL=' + txtURL + '&selchart=' + selchart; // crmv@30014

		jQuery('#status').show();

		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Home&action=HomeAjax&file=Homestuff&' + url,
			success: function(result) {
				if (!result) {
					alert(alert_arr.LBL_ADD_HOME_WIDGET);
					jQuery('#status').hide();
					jQuery('#stufftitle_id').val('');
					jQuery('#txtRss_id').val('');
					return false;
				} else {
					VTE.Homestuff.hideWidget();
					jQuery('#status').hide();
					jQuery('#stufftitle_id').val('');
					jQuery('#txtRss_id').val('');
					eval(result);
				}
			}
		});
	},

	/**
	 * this function is used to hide the default widgets
	 * @param string sid - the id of the widget
	 */
	HideDefault: function(sid) {
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Home&action=HomeAjax&file=HomestuffAjax&stuffid=' + sid + "&act=hide",
			success: function(result) {
				if (result.indexOf('SUCCESS') > -1) {
					var delchild = document.getElementById('stuff_' + sid);
					document.getElementById('MainMatrix').removeChild(delchild);
					jQuery('#seqSettings').hide();
					jQuery('#seqSettings').html('<table cellpadding="10" cellspacing="0" border="0" width="100%" class="vtResultPop small"><tr><td align="center">' + alert_arr.LBL_WIDGET_HIDDEN + '.' + alert_arr.LBL_RESTORE_FROM_PREFERENCES + '.</td></tr></table>');
					placeAtCenter(document.getElementById('seqSettings'));
					jQuery('#seqSettings').fadeIn(); // crmv@168103
					setTimeout(VTE.Homestuff.hideSeqSettings, 3000);
				} else {
					alert(alert_arr.ERR_HIDING + '.' + alert_arr.MSG_TRY_AGAIN + '.');
				}
			}
		});
	},

	/**
	 * this function removes the widget dropdown window
	 */
	fnRemoveWindow: function() {
		var tagName = document.getElementById('addWidgetDropDown').style.display = 'none';
	},

	/**
	 * this function displays the widget dropdown window
	 */
	fnShowWindow: function() {
		var tagName = document.getElementById('addWidgetDropDown').style.display = 'block';
	},

	/**
	 * this function is used to postion the widgets on home on page resize
	 * @param string targetDiv - the id of the target widget
	 * @param string stufftitle - the title of the target widget
	 * @param string stufftype - the type of the target widget
	 */
	positionDivInAccord: function(targetDiv, stufftitle, stufftype, stuffsize) { // crmv@30014
		var layout = jQuery('#homeLayout').val(),
			spacing = 0.6,
			widgetWidth,
			dashWidth;

		// crmv@30014
		if (stuffsize == undefined || stuffsize == 0 || stuffsize == '') stuffsize = 1;
		var columns = Math.max(2, Math.min(parseInt(layout), 4));
		var stuffsize = Math.max(1, Math.min(stuffsize, columns));
		// crmv@30014e

		switch (layout) {
			case '2':
				widgetWidth = 49;
				break;
			case '3':
				widgetWidth = 31;
				break;
			case '4':
			default:
				widgetWidth = 24;
				break;
		}
		dashWidth = widgetWidth * 2 + spacing;
		urlwidth = 98.6;

		var dx = 0;
		var mainX = parseInt(document.getElementById("MainMatrix").style.width);

		//crmv@25314
		if (stufftitle != vtdashboard_defaultDashbaordWidgetTitle && stufftype != "DashBoard" && stufftype != "URL" && stufftype != "Iframe" && stufftype != "SDKIframe") { //crmv@25466
			dx = (mainX * widgetWidth * stuffsize) / 100 + (stuffsize - 1) * spacing;
		} else if (stufftitle == vtdashboard_defaultDashbaordWidgetTitle || stufftype == "Iframe") {//crmv@208472
			dx = mainX * dashWidth / 100;
		} else if (stufftype == "URL") {
			dx = mainX * urlwidth / 100;
		}
		//crmv@25314e
		//crmv@25466
		else if (stufftype == 'SDKIframe') {
			var widgetId = parseInt(targetDiv.substr(targetDiv.indexOf('_') + 1));
			if (widgetId > 0) {
				getSDKHomeIframe(widgetId, function(sdkdata) {
					var size = Math.max(1, Math.min(sdkdata.size, columns));
					dx = (mainX * widgetWidth * size) / 100 + (size - 1) * spacing;
					positionDivInAccordDx();
				});
			}
		}
		//crmv@25466e
		
		positionDivInAccordDx();

		function positionDivInAccordDx() {
			if (dx > 0) {
				document.getElementById(targetDiv).style.width = dx + "%";
			}
		}
	},

	/**
	 * this function hides the seqSettings div
	 */
	hideSeqSettings: function() {
		jQuery('#seqSettings').fadeOut(); // crmv@168103
	},

	//crmv@208472

	/**
	 * this function initializes the homepage
	 */
	initHomePage: function() {
		// crmv@192014
		jQuery('#MainMatrix').sortable({
			handle: '.headerrow',
			items: '> div',
			opacity: 0.75,
			forcePlaceholderSize: true,
			scroll: true,
			update: function(event, ui) {
				var matrixseqarr = [];
				jQuery('#MainMatrix > div').each(function(idx, item) {
					matrixseqarr.push(item.id.replace(/^stuff_/, ''));
				});
				VTE.Homestuff.BlockSorting(matrixseqarr);
			}
		});
		// crmv@192014e
	},

	/**
	 * this function is used to save the sorting order of elements when they are moved around on the home page
	 * @param array matrixseqarr - the array containing the sequence of the widgets
	 */
	BlockSorting: function(matrixseqarr) {
		var sequence = matrixseqarr.join("_");
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Home&action=HomeAjax&file=HomestuffAjax&matrixsequence=' + sequence,
			success: function(result) {
				jQuery('#seqSettings').html(result);
				placeAtCenter(document.getElementById('seqSettings'));
				jQuery('#seqSettings').fadeIn(); // crmv@168103
				setTimeout(VTE.Homestuff.hideSeqSettings, 3000);
			}
		});
	},

	/**
	 * this function checks if the current browser is IE or not
	 */
	isIE: function() {
		return navigator.userAgent.indexOf("MSIE") != -1;
	},

	/**
	 * this function takes a widget id and adds scrolling property to it
	 */
	addScrollBar: function(id) {
		jQuery('#stuff_' + id).css('overflowX', "scroll");
		jQuery('#stuff_' + id).css('overflowY', "scroll");
	},

	/**
	 * this function will display the node passed to it in the center of the screen
	 */
	showOptions: function(id) {
		if (typeof id === 'string') {
			var node = jQuery('#' + id);
		} else {
			var node = jQuery(id);
		}
		node.show();
		placeAtCenter(node.get(0));
	},

	/**
	 * this function will hide the node passed to it
	 */
	hideOptions: function(id) {
		if (typeof id === 'string') {
			var node = jQuery('#' + id);
		} else {
			var node = jQuery(id);
		}
		node.fadeOut(); // crmv@168103
	},

	/**
	 * this function will be used to save the layout option
	 */
	saveLayout: function() {
		jQuery('#status').show();
		VTE.Homestuff.hideOptions('changeLayoutDiv');
		var sel = jQuery('#layoutSelect');
		var layout = sel.val();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Home&action=HomeAjax&file=HomestuffAjax&layout=' + layout,
			success: function(result) {
				window.location.reload();
			}
		});
	},
	
	showWidget: function() {
		show('addWidgetsDiv');
		placeAtCenter(document.getElementById('addWidgetsDiv'));
	},

	hideWidget: function() {
		fnhide('addWidgetsDiv');
		jQuery('#stufftitle_id').val("");
	},
	
};

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function chooseType(typeName) {
	return VTE.callDeprecated('chooseType', VTE.Homestuff.chooseType, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function setFilter(modName) {
	return VTE.callDeprecated('setFilter', VTE.Homestuff.setFilter, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function setPrimaryFld(Primeval) {
	return VTE.callDeprecated('setPrimaryFld', VTE.Homestuff.setPrimaryFld, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function showEditrow(sid) {
	return VTE.callDeprecated('showEditrow', VTE.Homestuff.showEditrow, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function cancelEntries(editRow) {
	return VTE.callDeprecated('cancelEntries', VTE.Homestuff.cancelEntries, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function saveEntries(selMaxName) {
	return VTE.callDeprecated('saveEntries', VTE.Homestuff.saveEntries, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function saveHomeChart(selSize) {
	return VTE.callDeprecated('saveHomeChart', VTE.Homestuff.saveHomeChart, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function saveEditurl(selurl) {
	return VTE.callDeprecated('saveEditurl', VTE.Homestuff.saveEditurl, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function saveEditDash(dashRowId) {
	return VTE.callDeprecated('saveEditDash', VTE.Homestuff.saveEditDash, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function DelStuff(sid) {
	return VTE.callDeprecated('DelStuff', VTE.Homestuff.DelStuff, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function loadAddedDiv(stuffid, stufftype, stuffsize) {
	return VTE.callDeprecated('loadAddedDiv', VTE.Homestuff.loadAddedDiv, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function loadStuff(stuffid, stufftype) {
	return VTE.callDeprecated('loadStuff', VTE.Homestuff.loadStuff, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function loadAllWidgets(widgetInfoList, batchSize) {
	return VTE.callDeprecated('loadAllWidgets', VTE.Homestuff.loadAllWidgets, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function frmValidate() {
	return VTE.callDeprecated('frmValidate', VTE.Homestuff.frmValidate, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function HideDefault(sid) {
	return VTE.callDeprecated('HideDefault', VTE.Homestuff.HideDefault, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function fnRemoveWindow() {
	return VTE.callDeprecated('fnRemoveWindow', VTE.Homestuff.fnRemoveWindow, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function fnShowWindow() {
	return VTE.callDeprecated('fnShowWindow', VTE.Homestuff.fnShowWindow, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function positionDivInAccord(targetDiv, stufftitle, stufftype, stuffsize) {
	return VTE.callDeprecated('positionDivInAccord', VTE.Homestuff.positionDivInAccord, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function hideSeqSettings() {
	return VTE.callDeprecated('hideSeqSettings', VTE.Homestuff.hideSeqSettings, arguments);
}

//crmv@208472

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function initHomePage() {
	return VTE.callDeprecated('initHomePage', VTE.Homestuff.initHomePage, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function BlockSorting(matrixseqarr) {
	return VTE.callDeprecated('BlockSorting', VTE.Homestuff.BlockSorting, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function isIE() {
	return VTE.callDeprecated('isIE', VTE.Homestuff.isIE, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function addScrollBar(id) {
	return VTE.callDeprecated('addScrollBar', VTE.Homestuff.addScrollBar, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function showOptions(id) {
	return VTE.callDeprecated('showOptions', VTE.Homestuff.showOptions, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function hideOptions(id) {
	return VTE.callDeprecated('hideOptions', VTE.Homestuff.hideOptions, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Homestuff class.
 */

function saveLayout() {
	return VTE.callDeprecated('saveLayout', VTE.Homestuff.saveLayout, arguments);
}