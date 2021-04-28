/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

function set_return(product_id, product_name) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	form.parent_name.value = product_name;
	form.parent_id.value = product_id;
	disableReferenceField(form.parent_name,form.parent_id,form.parent_id_mass_edit_check);	//crmv@29190
}

//crmv@104562
if (typeof(GanttScript) == 'undefined') {
	GanttScript = {
		ge: false,
		holidays: '',
		hideTab: function() {
			jQuery('body').css('overflow','visible');
			jQuery('#GanttTab').hide();
		},
		showTab: function(module,record,path) {
			var me = this;
			
			jQuery('#turboLiftContainer').hide();
			jQuery('#DetailViewWidgets').hide();
			jQuery('body').css('overflow','hidden');
			jQuery('#GanttTab').show();
			
			if (jQuery('#workSpaceGantt').html() == '') {
			
				// first calculate holidays
				jQuery.ajax({
					url: 'index.php?module=ProjectPlan&action=ProjectPlanAjax&file=DetailViewAjax&ajxaction=GETGANTTHOLIDAYS&recordid='+record,
					type: 'post',
					success: function(holidays) {
						me.holidays = holidays;
			
						//load templates
						jQuery("#ganttemplates").loadTemplates();
						
						// here starts gantt initialization
						me.ge = new GanttMaster();
						me.ge.resourceUrl = "modules/ProjectPlan/thirdparty/jQueryGantt/res/";

						var workSpace = jQuery("#workSpaceGantt");
						workSpace.css({
							height: jQuery(window).height() - workSpace.offset().top - 30,
						});

						VteJS_DialogBox.progress("GanttTab");
						me.ge.init(workSpace);
						me.loadGanttFromServer(record, function () {
							VteJS_DialogBox.hideprogress("GanttTab");
						});

						jQuery(window)
						.resize(function () {
							workSpace.css({
								height: jQuery(window).height() - workSpace.offset().top - 30,
							});
							workSpace.trigger("resize.gantt");
						})
						.oneTime(150, "resize", function () {
							jQuery(this).trigger("resize");
						});
					}
				});
			}
		},
		loadGanttFromServer: function(record,callback) {
			var me = this;
			jQuery.ajax({
				url: 'index.php?module=ProjectPlan&action=ProjectPlanAjax&file=DetailViewAjax&ajxaction=GETGANTTJSON&recordid='+record,
				type: 'post',
				dataType: 'json',
				success: function(ret) {
					me.ge.loadProject(ret);
					me.ge.checkpoint(); //empty the undo stack
					if (typeof callback == 'function') callback();
				}
			});
		}
	}
}
//crmv@104562e