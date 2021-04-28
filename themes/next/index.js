/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* JS code for the theme */
/* crmv@82419 */
/* crmv@96023 */

jQuery(document).ready(function() {

	// TODO: move these in the json file
	var themeOptions = {
		replaceAlerts: true,
		useTooltips: true,
	};

	// crmv@114693
	if (jQuery.material) {
		// add ripple to some other classes
		jQuery.material.options.withRipples += ",.crmbutton:not(.withoutripple)";
	
		// initialize basic controls
		jQuery.material.init();
	}
	// crmv@114693
	
	// tooltips
	if (themeOptions.useTooltips && jQuery.fn.tooltip) {
		jQuery('img[data-toggle="tooltip"]').tooltip({animation: false});
		jQuery('span[data-toggle="tooltip"]').tooltip({animation: false});
		jQuery('i[data-toggle="tooltip"]').tooltip({animation: false});
	}
	
	// crmv@157124
	if (jQuery.fn.vtentitypreview) {
		jQuery('[data-entitypreview="true"]').vtentitypreview();
	}
	// crmv@157124e
	
	jQuery(document).ajaxComplete(function() {
		if (themeOptions && themeOptions.useTooltips && jQuery.fn.tooltip) {
			jQuery('img[data-toggle="tooltip"]').tooltip({animation: false});
			jQuery('span[data-toggle="tooltip"]').tooltip({animation: false});
			jQuery('i[data-toggle="tooltip"]').tooltip({animation: false});
		}
		
		// crmv@157124
		if (jQuery.fn.vtentitypreview) {
			jQuery('[data-entitypreview="true"]').vtentitypreview();
		}
		// crmv@157124e
	});
	
	// Fix for tooltips disappearing when prototype.js is loaded
	// See https://github.com/twbs/bootstrap/issues/6921
	if (themeOptions.useTooltips && window.Prototype && Prototype.BrowserFeatures.ElementExtensions) {
		var pluginsToDisable = ['collapse', 'dropdown', 'modal', 'tooltip', 'popover'];
		var disablePrototypeJS = function(method, pluginsToDisable) {
			var handler = function(event) {
				event.target[method] = undefined;
				setTimeout(function() {
					delete event.target[method];
				}, 0);
			};
			pluginsToDisable.each(function (plugin) {
				jQuery(window).on(method + '.bs.' + plugin, handler);
			});
		};
		
		disablePrototypeJS('show', pluginsToDisable);
		disablePrototypeJS('hide', pluginsToDisable);
	}

	// crmv@99315 crmv@125629 crmv@191935
	if (themeOptions.replaceAlerts) {
		// replace alert boxes (but expose the original method)
		if (!window.origAlert) window.origAlert = window.alert;

		window.alert = window.vtealert = function(text, cb, options) {
			var me = this,
				text = text || "";

			options = jQuery.extend({}, {
				showOkButton: false,
				html: false,
				autoclose: false,
			}, options || {});
			
			jQuery('#alert-dialog .modal-footer')[options.showOkButton ? 'removeClass' : 'addClass']('hidden');
			jQuery('#alert-dialog-content')[options.html ? 'html' : 'text'](text);
			jQuery('#alert-dialog').modal();

			if (options.autoclose > 0) {
				var autocloseTimeout = options.autoclose*1000;
				var modalTimeout = setTimeout(function() {
					options.autoclose = false;
					jQuery('#alert-dialog').click();
		        }, autocloseTimeout);
		        jQuery('#alert-dialog .modal-content').one('mouseenter', function() {
					if (options.autoclose > 0) {
						window.clearTimeout(modalTimeout);
						options.autoclose = false;
						me.vtealert(text, cb, options);
					}
				});
			}

			// remove modal closing handler
			jQuery('#alert-dialog').off('hidden.bs.modal');

			// register new modal closing handler
			if (typeof cb == 'function') {
				jQuery('#alert-dialog').on('hidden.bs.modal', function() {
					if (options.autoclose > 0 && modalTimeout) {
						window.clearTimeout(modalTimeout);
						options.autoclose = false;
					}
					// call the callback
					cb();
				});
			}

			if (typeof window.findZMax == 'function') {
				var zIndex = findZMax();
				// crmv@176893
				var bsModal = jQuery('#alert-dialog').data('bs.modal');
				if (bsModal && bsModal.$backdrop) {
					bsModal.$backdrop.css('z-index', zIndex+1);
				}
				// crmv@176893e
				jQuery('#alert-dialog').css('z-index', zIndex+2);
			}
		}
	
		// replace confirm boxes (another function is needed)
		window.vteconfirm = function(text, cb, options) {
			text = text || "";
			
			options = jQuery.extend({}, {
				html: false,
				//crmv@150751
				btn_exit: false,
				btn_exit_label: '',
				btn_cancel_label: '',
				btn_ok_label: '',
				width: '',
				//crmv@150751e
			}, options || {});
			
			function cbanswer() {
				// remove the button handler
				jQuery(this).off('click');

				if (typeof cb == 'function') {
					// remove the modal closing handler to avoid calling the callback twice
					jQuery('#confirm-dialog').off('hidden.bs.modal');
					// close the modal
					jQuery('#confirm-dialog').modal('hide');
					// call the callback
					//crmv@180014
					var msg = jQuery(this).attr('msg') || '';
					cb(jQuery(this).hasClass('btn-ok'), msg);
					//crmv@180014e
				}
			}

			jQuery('#confirm-dialog-content')[options.html ? 'html' : 'text'](text);
			jQuery('#confirm-dialog').modal();

			// remove the modal closing handler
			jQuery('#confirm-dialog').off('hidden.bs.modal');

			// register the new modal closing handler
			if (typeof cb == 'function') {
				jQuery('#confirm-dialog').on('hidden.bs.modal', function() {
					// call the callback
					cb(false, '');
				});
			}

			// register the buttons handlers
			jQuery('#confirm-dialog').find('button.btn-exit').off('click').on('click', cbanswer);
			jQuery('#confirm-dialog').find('button.btn-cancel').off('click').on('click', cbanswer);
			jQuery('#confirm-dialog').find('button.btn-ok').off('click').on('click', cbanswer);

			//crmv@150751
			jQuery('#confirm-dialog').find('button.btn-exit').hide();

			var modalWidth = options.width || '';
			jQuery('#confirm-dialog').find('.modal-dialog').css('width', modalWidth);

			if (options.btn_exit) jQuery('#confirm-dialog').find('button.btn-exit').show();
			if (options.btn_exit_label != '') jQuery('#confirm-dialog').find('button.btn-exit').text(options.btn_exit_label);
			if (options.btn_cancel_label != '') jQuery('#confirm-dialog').find('button.btn-cancel').text(options.btn_cancel_label);
			if (options.btn_ok_label != '') jQuery('#confirm-dialog').find('button.btn-ok').text(options.btn_ok_label);
			//crmv@150751e

			if (typeof window.findZMax == 'function') {
				var zIndex = findZMax();
				// crmv@176893
				var bsModal = jQuery('#confirm-dialog').data('bs.modal');
				if (bsModal && bsModal.$backdrop) {
					bsModal.$backdrop.css('z-index', zIndex+1);
				}
				// crmv@176893e
				jQuery('#confirm-dialog').css('z-index', zIndex+2);
		   }
		}
	} else {
		// replace alert boxes (but expose the original method)
		if (!window.origAlert) window.origAlert = window.alert;
		window.alert = function(text, cb) {
			window.origAlert(text);
			// call callback
			if (typeof cb == 'function') cb();
		}
	}
	// crmv@99315e crmv@125629e crmv@191935e
	
	// crmv@98866
	jQuery(document).off('click.tab.data-api');
	jQuery(document).on('click.tab.data-api', '[data-toggle="tab"]', function (e, params) {
	    e.preventDefault();
	    var parentTab = jQuery(this).closest('ul.nav-tabs');
	    
	    var tab = jQuery(jQuery(this).attr('href'));
	    var activate = !tab.hasClass('active');
	    var content = jQuery(parentTab.attr('data-content'));
	    
	    if (activate || (!activate && params && params.forceLoad)) {
	    	content.find('div.tab-pane.active').removeClass('active');
	    	parentTab.find('li.active').removeClass('active');
		    jQuery(this).tab('show');
		    parentTab.trigger('tabclick', params);
	    }
	});
	// crmv@98866 end
	
	if (jQuery && jQuery.fancybox) {
		var loadingExtension = {
			oldShowLoading: jQuery.fancybox.showLoading,
			oldHideLoading: jQuery.fancybox.hideLoading,
			showLoading: function() {
				D = jQuery(document);
				F = jQuery.fancybox;
				
				F.hideLoading();
				VteJS_DialogBox.progress();

				D.bind('keydown.loading', function(e) {
					if ((e.which || e.keyCode) === 27) {
						e.preventDefault();
						F.cancel();
					}
				});

				F.trigger('onLoading');
			},
			hideLoading: function() {
				jQuery(document).unbind('.loading');
				VteJS_DialogBox.hideprogress();
			}
		};
		jQuery.extend(jQuery.fancybox, loadingExtension);
	}

	var themeConfig = window.theme_config || {};
	Theme.initialize(themeConfig);
});

var Theme = Theme || {
	
	config: {},
	isLoading: false,

	showPageStatistics: false,
	pageLoadStartDate: null,
	pageLoadEndDate: null,
	pageLoadTime: 0,

	initialize: function(config) {
		this.config = config || {};

		this.initilizeEvents();
		this.overrideStdClass();
		// crmv@195963
		this.alterSlimScroll();
		this.alterCustomScrollbar();
		// crmv@195963e
	},

	getProperty: function(prop) {
		return this.config[prop];
	},
	
	showLoadingMask: function() {
		var me = this;
		
		me.isLoading = true;

		me.startLoadStatistics();
		
		Theme.checkInIFrame();
		
		if (parent && parent.VTE && parent.VTE.PanelManager) {
			parent.VTE.PanelManager.maskActivePanel();
		}
	},
	
	hideLoadingMask: function() {
		var me = this;
		
		me.isLoading = false;
		
		Theme.initializeMenu();
		Theme.checkInIFrame();
		
		if (parent && parent.VTE && parent.VTE.PanelManager) {
			parent.VTE.PanelManager.unmaskActivePanel();
		}

		me.endLoadStatistics();
	},

	startLoadStatistics: function() {
		var me = this;

		if (!me.showPageStatistics) return;

		me.pageLoadStartDate = new Date();
	},

	endLoadStatistics: function() {
		var me = this;

		if (!me.showPageStatistics) return;

		me.pageLoadEndDate = new Date();
		me.pageLoadTime = parseInt((me.pageLoadEndDate - me.pageLoadStartDate));

		console.log("Page rendered in " + me.pageLoadTime + " milliseconds");
	},
	
	checkLoginPageBackgroundImage: function() {
		var me = this,
			$loginBackground = jQuery('#loginBackground'),
			backgroundColor = $loginBackground.data('color'),
			backgroundImage = $loginBackground.data('image');

		if (backgroundColor.length > 0) {
			$loginBackground.css('background-color', backgroundColor);
        }
		
        if (backgroundImage.length > 0) {
        	$loginBackground.css('background-image', 'url(' + backgroundImage + ')');
        }
    },
    
    adjustComponents: function() {
		var me = this,
			lateralMenu = LateralMenu;
			wrapper = jQuery('#mainContainer');
			
		var isInIFrame = me.isAppInIFrame();
		var toggleState = get_cookie('togglePin');
		
		var totalWidth = parseInt(wrapper.width() || 0);

		var navbars = jQuery('.buttonsList.buttonsListFixed');
		
		if (isInIFrame) {
			navbars.css('left', '0px');
			navbars.css('width', '100%');
		} else {
			navbars.width(totalWidth);
		}
		
		if (!isInIFrame) {
			// Adjust the CKEditor size when it is maximized
			if (jQuery('.cke_maximized').length > 0) {
				var ckwindow = jQuery('.cke_maximized');
				
				setTimeout(function() {
					ckwindow.css('left', leftSize);
					ckwindow.css('width', totalWidth);
				}, 100);
			}
		}

		// crmv@205899
		if (window.Turbolift) {
			Turbolift.resize();
		}
		// crmv@205899e

		// crmv@199229
		if (jQuery('.dvHeaderText').length > 0) {
			var recordInline = parseInt(jQuery('.dvHeaderText').data('recordInline'));
			var maxWidth = (75 * (totalWidth - parseInt(jQuery('.dvHeaderRight').width() || 0))) / 100;
			jQuery('.recordTitleName').css('max-width', maxWidth + 'px');

			if (recordInline === 1) {
				if (maxWidth < 300) {
					jQuery('.dvHeaderText').removeClass('dvHeaderTextInline').addClass('dvHeaderTextMultiLine');
				} else {
					jQuery('.dvHeaderText').removeClass('dvHeaderTextMultiLine').addClass('dvHeaderTextInline');
				}
			}
		}
		// crmv@199229e
	},
    
	initializeMenu: function() {
		var me = this,
			module = window.gVTModule || '',
			settingsList = jQuery('.settingsList');

		if (module == 'Settings' || settingsList.length > 0) {
			setTimeout(function() {
				me.initializeSettingsMenu();
			}, 100);
		} else {
			setTimeout(function() {
				me.initializeModulesMenu();
			}, 100);
		}
	},
	
	initializeModulesMenu: function() {
		var leftMenuHeight = parseInt(visibleHeight(jQuery('body').get(0)) - parseInt(jQuery('.vteLeftHeader').height()) - parseInt(jQuery('.menuList').height()));
		
		jQuery('#moduleListContainer').slimScroll({destroy: true});

		var scrollTo = 0;

		var activeItem = jQuery('.moduleList > .active');
		if (activeItem.length > 0) {
			scrollTo = parseInt(activeItem.position().top - 50);
		}
		
		jQuery('#moduleListContainer').slimScroll({
			wheelStep: 10,
			height: leftMenuHeight + 'px'
		}).slimScroll({
			scrollTo: scrollTo
		});
		
		jQuery('#moduleListContainer').parent().find('.slimScrollBar').hide();
	},
	
	initializeSettingsMenu: function() {
		var leftMenuHeight = parseInt(visibleHeight(jQuery('body').get(0))) - parseInt(jQuery('#vteHeader').height() + 75);

		jQuery('.settingsList').slimScroll({destroy: true});

		var scrollTo = 0;

		var activeItem = jQuery('.settingsList > li.subMenu > ul > li.active');
		if (activeItem.length > 0) {
			scrollTo = parseInt(activeItem.offset().top) - 50;
		}
		
		jQuery('.settingsList').slimScroll({
			wheelStep: 10,
			height: leftMenuHeight + 'px'
		}).slimScroll({
			scrollTo: scrollTo
		});

		jQuery('.settingsList').parent().find('.slimScrollBar').hide();
	},
	
	checkInIFrame: function() {
		var isInIFrame = this.isAppInIFrame();
		
		if (isInIFrame) {
			jQuery('#leftPanel').hide();
			jQuery('#rightPanel').hide();
			
			jQuery('#mainContainer').css('padding', '0px');
			jQuery('#mainContent').css('padding-bottom', '0px');
			
			if (jQuery('#turboLiftContainerDiv').length > 0) {
				jQuery('#turboLiftContainerDiv').css('right', '30px');
			}
		} else {
			jQuery('#leftPanel').show();
			jQuery('#rightPanel').show();
			
			if (jQuery('#turboLiftContainerDiv').length > 0) {
				jQuery('#turboLiftContainerDiv').css('right', '90px');
			}
		}
		
		Theme.adjustComponents();
	},
	
	isAppInIFrame: function() {
		return parent && parent.window && parent.window.VTE && parent.window != window;
	},
	
	initilizeEvents: function() {
		var me = this;
		
		jQuery(window).resize(function(e) {
			Theme.initializeMenu();
			Theme.adjustComponents();
		});
		
		if (jQuery('#btnScrollTop').length > 0) {
			jQuery(window).scroll(function() {
				var offset = 250;
				var duration = 300;
				
				if (jQuery(this).scrollTop() > offset) {
					jQuery('#btnScrollTop').fadeIn(duration);
				} else {
					jQuery('#btnScrollTop').fadeOut(duration);
				}
			});
			
			jQuery('#btnScrollTop').click(function(e) {
				e.preventDefault();
				
				jQuery('html, body').animate({ scrollTop: 0 }, 300);
				
				return false;
			});
		}
		
		jQuery('body').on('click', '[data-action]', function(e) {
	        e.preventDefault();
	        var $this = jQuery(this), 
	        	action = jQuery(this).data('action');
	        
	        if (action === 'submenu-toggle') {
	    		$this.next().slideToggle(300),
	            $this.parent().toggleClass('toggled');
	        }
        });
		
		jQuery('.dropdown-menu:not(.dropdown-autoclose)').click(function(e) {
			e.stopPropagation();
		});
		
		jQuery('.profile').click(function(e) {
			e.preventDefault();
			jQuery(this).closest('.profileWrapper').toggleClass('active');
		});
		
		jQuery(document).mouseup(function(e) {
			if (!jQuery(e.target).is('.profileWrapper') && jQuery('.profileWrapper').has(e.target).length === 0) {
				jQuery('.profileWrapper').removeClass('active');
			}
		});
		
		if (jQuery('#listView').length > 0 || jQuery('#KanbanViewContents').length > 0) {
			function initilizeListViewEvents() {
				setupSelectPicker('viewname', {
					liveSearch: true, 
					right: true,
				});
				
				setupSelectPicker('lv_user_id', {
					liveSearch: true,
					right: true,
				});
			}
			
			initilizeListViewEvents();
			
			jQuery(document).ajaxComplete(function() {
				initilizeListViewEvents();
				// crmv@173746
				var otherButton = jQuery('.otherButton');
				var otherMenuList = otherButton.find('.dropdown-menu');
				if (otherMenuList.length > 0 && otherMenuList.children().length < 1) {
					otherButton.hide();
				}
				// crmv@173746e	
			});
		}
		
		var otherButton = jQuery('.otherButton');
		var otherMenuList = otherButton.find('.dropdown-menu');
		if (otherMenuList.length > 0 && otherMenuList.children().length < 1) {
			otherButton.hide();
		}
		
		if (window.CKEDITOR) {
			CKEDITOR.on('instanceReady', function(ev) {
				ev.editor.on('maximize', function(evt) {
					Theme.adjustComponents();
				});  
				ev.editor.on('minimize', function(evt) {
					Theme.adjustComponents();
				});
			});
		}
	},
	
	overrideStdClass: function() {
		
		var oldShowFloatingDiv = window.showFloatingDiv;
		
		window.showFloatingDiv = function(divid, obj, options) {
			// set default options
			options = jQuery.extend({}, {
				draggable: true,			// true to enable the dragging. Define an element with id: mainid_Handle
				modal: true,				// if modal, the dialog blocks the page
				removeOnMaskClick: true,	// if true, the modal dialog can be closed by clicking on the mask
				center: !obj,				// center the dialog in the page (default to true if not passed an id)
				relative: !!obj,			// align the div next to the passed object (default to true if passed an id)
			}, options || {});
			
			oldShowFloatingDiv.apply(window, [divid, obj, options]);
		}

		// crmv@198701
		var oldGetViewPortCenter = window.getViewPortCenter;

		window.getViewPortCenter = function(fixed) {
			var center = oldGetViewPortCenter.apply(window, arguments);

			var totalWidth = parseInt(jQuery('#mainContainer').width() || 0) / 2;

			var width = totalWidth;
			var height = center.y;

			return { x: width, y: height };
		}
		// crmv@198701e
		
		if (window.CalendarTracking) {
			CalendarTracking.runningTrackClicked = function() {
				var scope = amIinPopup() ? parent : window;
				scope.jQuery('[data-module="LBL_TRACK_MANAGER"]').click();
			};
		}
		
		if (window.ReportTable) {
			var oldInitMainTable = ReportTable.initMainTable;
			
			ReportTable.initMainTable = function(params, options) {
				oldInitMainTable.apply(ReportTable, [params, options]);
				
				jQuery(window).on('scroll', function() {
					jQuery('.fixedHeader-floating').css('zIndex', 100);
				});
			};
		}
		
		if (window.VteJS_DialogBox) {
			VteJS_DialogBox._olayer = function(toggle) {
				var olayerid = "__vtejs_dialogbox_olayer__";
				VteJS_DialogBox._removebyid(olayerid);

				if (typeof(toggle) == 'undefined' || !toggle) return;

				var olayer = jQuery('#' + olayerid);
				
				if (olayer.length < 1) {
					olayer = jQuery('<div>', {
						'id': olayerid,
						'class': 'small veil_new',
						'css': {
							'zIndex': findZMax()+1,
							'width': '100%',
						},
					});
					
					var target = null;
					
					var targets = ['#mainContent', 'body'];
					jQuery.each(targets, function(k, v) {
						if (jQuery(v).length > 0) {
							target = jQuery(v);
							return false;
						}
					});
					
					target.append(olayer);
				}
				
				olayer[toggle ? 'show' : 'hide']();
				
				return olayer.get(0);
			};
			
			VteJS_DialogBox.block = function(target, opacity) {
				target = target || null;
				
				var olayernode = null;
				
				if (!target) {
					olayernode = VteJS_DialogBox._olayer(true);
				} else {
					olayernode = VteJS_DialogBox._olayerTarget(target, true);
				}
				
				//var opacity = 0.3;
				//olayernode.style.opacity = opacity;
			};
			
			var oldDialogBoxHideProgress = VteJS_DialogBox.hideprogress;
			
			VteJS_DialogBox.hideprogress = function(target) {
				if (target == 'DetailViewContents') {
					jQuery('#status').hide();
				} else {
					oldDialogBoxHideProgress.apply(VteJS_DialogBox, [target]);
				}
			};
			
			var oldDialogBoxProgress = VteJS_DialogBox.progress;
			
			VteJS_DialogBox.progress = function(target, color) {
				if (target == 'DetailViewContents') {
					jQuery('#status').show();
				} else {
					oldDialogBoxProgress.apply(VteJS_DialogBox, [target, color]);
				}
			};
		}
		
		if (window.VTE.PDFMakerActions) {
			
			VTE.PDFMakerActions = VTE.override(VTE.PDFMakerActions, {

				showBusy: function() {
					var me = this;
					me.busy = true;
					jQuery("#vtbusy_info").show();
				},
				
				hideBusy: function() {
					var me = this;
					me.busy = false;
					
					jQuery("#vtbusy_info").hide();
					jQuery('.dropdown.open').removeClass('open');
				},
				
				getPDFListViewPopup2: function(srcButt, module) {
					if (document.getElementById("PDFListViewDiv") == undefined) {
						var newdiv = document.createElement('div');
						newdiv.setAttribute('id', 'PDFListViewDiv');
						jQuery('#mainContent').append(newdiv);
					}
					//crmv@17889
					var select_options = get_real_selected_ids(module);
					if (select_options.substr('0', '1') == ";")
						select_options = select_options.substr('1');
					//crmv@17889e
					var x = select_options.split(";");
					var count = x.length;
					//crmv@27096
					count = count - 1;
					if (count < 1)
					//crmv@27096e
					{
						alert(alert_arr.SELECT);
						return false;
					}
					jQuery('#status').show();
					
					jQuery.ajax({
						url: 'index.php',
						method: 'POST',
						data: "module=PDFMaker&return_module=" + module + "&action=PDFMakerAjax&file=listviewSelect", //crmv@27096
						success: function(result) {
							getObj('PDFListViewDiv').innerHTML = result;
							showFloatingDiv('PDFListViewDivCont');
							jQuery('.dropdown.open').removeClass('open');
							jQuery('#status').hide();
						}
					});
				},
				
			});
			
		}
		
		if (window.UnifiedSearchAreasObj) {
			UnifiedSearchAreasObj.show = function(currObj, type) {
				UnifiedSearchAreasObj.type = type;
				
				if (UnifiedSearchAreasObj.type == 'search') {
					
					if (UnifiedSearchAreasObj.initialized) return;
					
					jQuery('#UnifiedSearchAreasUnifiedRow').show();
					jQuery('#UnifiedSearchAreasUnifiedRowInput').show();
					
					if (jQuery('#UnifiedSearchAreasUnifiedRow1_Cont').length > 0) {
						jQuery('#UnifiedSearchAreasUnifiedRow1 td').first().html(jQuery('#UnifiedSearchAreasUnifiedRow1_Cont').html());
						jQuery('#UnifiedSearchAreasUnifiedRow1_Cont').remove();
						jQuery('#UnifiedSearchAreasUnifiedRow1').show();
						populateAdvancedSearchForm(advanced_search_params); //crmv@159559
					}
					
					jQuery(currObj).html(jQuery('#UnifiedSearchAreas').html());
					
					UnifiedSearchAreasObj.initialized = true;
					
				} else {
					var olayernode = VteJS_DialogBox._olayer(true);
					olayernode.style.opacity = '0';
					
					if (UnifiedSearchAreasObj.type == 'search') {
						jQuery('#UnifiedSearchAreasUnifiedRow').show();
					} else {
						jQuery('#UnifiedSearchAreasUnifiedRow').hide();
					}
					
					fnDropDown(currObj,UnifiedSearchAreasObj.id);
					
					if (UnifiedSearchAreasObj.type == 'search') {
						jQuery('#'+UnifiedSearchAreasObj.id).css('left','');
						jQuery('#'+UnifiedSearchAreasObj.id).css('right','0px');
					}
					
					document.getElementById(UnifiedSearchAreasObj.id).style.zIndex = findZMax()+1;
					
					jQuery('#'+UnifiedSearchAreasObj.id).appendTo(document.body);
					
					jQuery('#__vtejs_dialogbox_olayer__').click(function(){
						UnifiedSearchAreasObj.hide();
					});
				}
			};
		}

		// crmv@198701 removed placeAtCenter

		if (window.ModNotificationsCommon) {
			ModNotificationsCommon.loadModNotifications = function(num, target, indicator) {
				if (target == undefined || target == '') {
					if (VTE && VTE.FastPanel) {
						var container = VTE.FastPanelManager.mainInstance.getContainer('ajaxCont');
						if (container && container.length > 0) {
							target = container.attr('id');
						}
						//crmv@174098
						if ((target == undefined || target == '') && jQuery('#ModNotificationsDetailViewBlockCommentWidget_unseen_ids').length > 0) {
							target = jQuery('#ModNotificationsDetailViewBlockCommentWidget_unseen_ids').parent('.ajaxCont').attr('id');
						}
						//crmv@174098e
					}
				}
				if (indicator == undefined || indicator == '') {
					indicator = 'indicator'+ModNotificationsCommon.divId;
				}
				ModNotificationsCommon.reloadContentWithFiltering('DetailViewBlockCommentWidget', '', num, target, indicator);
			};
		}
			
		jQuery('#CalendarAddButton').show();
			
		window.fnAddEvent = function(obj, CurrObj, start_date, end_date, start_hr, start_min, start_fmt, end_hr, end_min, end_fmt, viewOption, subtab, eventlist, view_filter, offsetTop, date_format) {
			var str = jQuery("#wdCalendar").contents().find("#txtdatetimeshow_hidden").val();
			if (!str) return;
			
			var dropdownId = 'fnAddEvent';
			var $dropdown = jQuery('#' + dropdownId);
			
			if ($dropdown.length > 0) return;
			
			$dropdown = jQuery('<div>', {
				'id': dropdownId,
				'class': 'dropdown',
			});
			
			var $toggle = jQuery(obj);
			var $toggleClone = $toggle.clone();
			
			var $dropdownToggle = $toggleClone;
			$dropdownToggle.addClass('dropdown-toggle').attr('data-toggle', 'dropdown');
			$dropdownToggle.attr('onmouseover', '');
			$dropdownToggle.attr('onmouseout', '');
			$dropdown.append($dropdownToggle);
			
			$dropdownMenu = jQuery('<ul>').addClass('dropdown-menu');
			
			var currentDate = new Date();

			str = str.split('-');
		  	var start = str[0].split(' ');
		  	start[1] = monthToNum(start[1]).toString();
		  	var startDate = new Date(start[2],start[1]-1,start[0]);

			date_format = date_format.replace('yyyy','Y');
			date_format = date_format.replace('mm','m');
			date_format = date_format.replace('dd','d');

			if (currentDate < startDate) {
				if (start[0].length < 2) {
					start[0] = '0' + start[0];
				}
				if (start[1].length < 2) {
					start[1] = '0' + start[1];
				}
				if (date_format == 'd-m-Y') {
					start_date = start[0] + '-' + start[1] + '-' + start[2];
				}
				else if (date_format == 'm-d-Y') {
					start_date = start[1] + '-' + start[0] + '-' + start[2];
				}
				else if (date_format == 'Y-m-d') {
					start_date = start[2] + '-' + start[1] + '-' + start[0];
				}
				end_date = start_date;
			}
			
			var params = {
				startdate: start_date,
				enddate: end_date,
				starthr: start_hr,
				startmin: start_min,
				startfmt: start_fmt,
				endhr: end_hr,
				endmin: end_min,
				endfmt: end_fmt,
				viewOption: viewOption,
				subtab: subtab,
				view_filter: view_filter,
				is_all_day_event: '0',
				calWhat: '',
				calDescription: '',
				calLocation: '',
				forceLoad: true,
			};
			
			var $dropdownMenuOption = null, eventParams = null, todoParams = null;
			
			eventlist = eventlist.split(';');
			
			for (var i = 0, len = eventlist.length; i < len; i++) {
				var eventname = eventlist[i];

				eventParams = jQuery.extend({}, params, {
					'argg1': 'addEvent',
					'type': eventname,
					'disableTodo': true,
					'disableEvent': false,
				});
				
				var eventCode = eventname.toLowerCase().replace(/[\W_]+/g, "_");
				$dropdownMenuOption = createDropdownOption(alert_arr[eventCode], eventParams);
				$dropdownMenu.append($dropdownMenuOption);
			}
			
			var todoParams = jQuery.extend({}, params, {
				'argg1': 'createTodo',
				'type': 'todo',
				'disableTodo': false,
				'disableEvent': true,
			});
			
			$dropdownMenuOption = createDropdownOption(alert_arr['LBL_ADDTODO'], todoParams);
			$dropdownMenu.append($dropdownMenuOption);
			
			function createDropdownOption(eventname, params) {
				var $dropdownMenuOption = jQuery('<li>');
				
				var $dropdownMenuOptionAnch = jQuery('<a>');
				$dropdownMenuOptionAnch.text(eventname).attr('href', '#');
				
				$dropdownMenuOption.append($dropdownMenuOptionAnch);
				
				$dropdownMenuOptionAnch.on('click', function(e) {
					e.preventDefault();

					var tabType = params['type'] == 'todo' ? 'todo-tab' : 'event-tab';
					showFloatingDiv('addEvent', null, { modal: true });
					jQuery('a[href="#' + tabType + '"]').trigger('click.tab.data-api', params);
				});
				
				return $dropdownMenuOption;
			}
			
			$toggle.hide();
			
			$dropdown.append($dropdownMenu);
			
			jQuery($toggle).after($dropdown);
				
			return true;
		};
				
		window.clearText = function(elem, canc_elem_id) {
			if (typeof (canc_elem_id) != 'undefined') {
				var canc_elem = jQuery('#' + canc_elem_id);
			} else {
				var canc_elem = jQuery('#basic_search_icn_canc');
			}
			var jelem = jQuery(elem);
			var rest = jQuery.data(elem, 'restored');
			if (rest == undefined || rest == true) {
				jelem.val('');
				canc_elem.show();
				jQuery.data(elem, 'restored', false);
			}
		};

		window.restoreDefaultText = function(elem, deftext, canc_elem_id) {
			if (typeof (canc_elem_id) != 'undefined') {
				var canc_elem = jQuery('#' + canc_elem_id);
			} else {
				var canc_elem = jQuery('#basic_search_icn_canc');
			}
			var jelem = jQuery(elem);
			if (jelem.val() == '') {
				canc_elem.hide();
				jQuery.data(elem, 'restored', true);
				if (basic_search_submitted == true) {
					(jQuery('#basicSearch1').length > 0) ? jQuery('#basicSearch1').submit() : jQuery('#basicSearch').submit(); //crmv@159559
					basic_search_submitted = false;
				}
				jelem.val(deftext);
			}
		};
		
		window.cancelSearchText = function(deftext, elem_id, canc_elem_id) {
			if (typeof (elem_id) == 'undefined') {
				var elem_id = 'basic_search_text';
			}
			jQuery('#' + elem_id).val('');
			restoreDefaultText(document.getElementById(elem_id), deftext, canc_elem_id); //crmv@159559
			var gridParams = {};
			gridParams = getSearchParams(gridParams, 'Grid');
			if (gridParams['GridSearchCnt'] > 0) {
				basic_search_submitted = false;
			}
		};
		
		window.resetListSearch = function(searchtype, folderid, reload) {

			if (reload == undefined || reload == '') {
				reload = 'auto';
			}

			if (searchtype == 'Basic' || searchtype == 'BasicGlobalSearch') {

				if (reload == 'yes') {
					basic_search_submitted = true;
				} else if (reload == 'no') {
					basic_search_submitted = false;
				}

				if (searchtype == 'Basic') {
					jQuery('#basic_search_icn_canc').click();
				} else {
					jQuery('#unified_search_icn_canc').click();
				}

			} else if (searchtype == 'Advanced') {

				if (jQuery('#adSrc').length == 0) {
					return;
				}

				var tableName = document.getElementById('adSrc');
				var prev = tableName.rows.length;
				if (prev > 1) {
					for (var i = 1; i < prev; i++) {
						delRow();
					}
				}
				
				jQuery('#adSrc #Fields0').val(jQuery('#adSrc #Fields0 option:first').val());
				jQuery('#adSrc #Condition0').val(jQuery('#adSrc #Condition0 option:first').val());
				jQuery('#adSrc #Srch_value0').val('');
				advancedSearchOpenClose('close');

				if (reload == 'yes') {
					advance_search_submitted = true;
				} else if (reload == 'no') {
					advance_search_submitted = false;
				}
				
				if (advance_search_submitted) {
					jQuery('#search_url').val('');
					if (jQuery('#viewname').length > 0) {
						jQuery('#viewname').change();
					} else {
						(jQuery('#basicSearch1').length > 0) ? jQuery('#basicSearch1').submit() : jQuery('#basicSearch').submit(); //crmv@159559
					}
					advance_search_submitted = false;
				}
				
			} else if (searchtype == 'Grid') {

				jQuery.each(gridsearch, function(i, fieldname) {
					jQuery('#gridSrc [name="gs_' + fieldname + '"]').val('');
					// for select input
					if (jQuery('#gridSrc [name="gs_' + fieldname + 'Str"]').length > 0) {
						jQuery('#gridSrc [name="gs_' + fieldname + 'Str"]').val('');
						jQuery('input:checkbox[id^="' + fieldname + '"]').each(function(i, o) {
							jQuery(o).prop('checked', false);
						});
						jQuery('#' + fieldname + 'GridSelect').hide();
						jQuery('#' + fieldname + 'GridInput').show();
					}
				});
				
				if (reload == 'yes') {
					grid_search_submitted = true;
				} else if (reload == 'no') {
					grid_search_submitted = false;
				}
				
				if (grid_search_submitted) {
					callSearch('Grid', folderid);
					grid_search_submitted = false;
				}

			}
		};

		window.getSearchParams = function(extraParams, searchtype) {

			if (searchtype == 'Basic' || searchtype == 'BasicGlobalSearch') {

				extraParams['searchtype'] = 'BasicSearch';
				if (searchtype == 'BasicGlobalSearch') {
					extraParams['search_text'] = encodeURIComponent(jQuery('#unifiedsearchnew_query_string').val());
				} else {
					extraParams['search_text'] = encodeURIComponent(jQuery('#basic_search_text').val());
				}

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
					extraParams['Fields' + jj] = sfld_name[sfld_name.selectedIndex].value;
					extraParams['Condition' + jj] = scndn_name[scndn_name.selectedIndex].value;
					extraParams['Srch_value' + jj] = encodeURIComponent(srchvalue_name.value);
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

				if (typeof (gridsearch) != 'undefined') {
					eval(jQuery('#gridsearch_script').html());
					var ii = 0;
					jQuery.each(gridsearch, function(i, fieldname) {
						var value = jQuery('#gridSrc [name="gs_' + fieldname + '"]').val();
						if (typeof value != 'undefined' && value != '') {
							extraParams['GridFields' + ii] = fieldname;
							extraParams['GridCondition' + ii] = 'c';
							extraParams['GridSrch_value' + ii] = encodeURIComponent(value);
							ii++;
						}
					});
					if (ii > 0) {
						extraParams['GridSearchCnt'] = ii;
					}
				}

			} else if (searchtype == 'Area' || searchtype == 'AreaGlobalSearch') {

				extraParams['searchtype'] = 'BasicSearch';
				if (searchtype == 'AreaGlobalSearch') {
					extraParams['search_text'] = encodeURIComponent(jQuery('#unifiedsearchnew_query_string').val());
				} else {
					extraParams['search_text'] = encodeURIComponent(jQuery('#basic_search_text').val());
				}
			}
			
			return extraParams;
		};
		
		// crmv@164368
		
		// crmv@172169
		window.get_more_favorites = function() {
			var me = this;
			
			if (me.isLoadingFavorites) return;
			me.isLoadingFavorites = true;
			
			VTE.PanelManager.maskActivePanel();

			jQuery.ajax({
				url: 'index.php',
				method: 'POST',
				data: 'module=SDK&action=SDKAjax&file=src/Favorites/GetFavoritesList&mode=all&fastmode=1',
				dataType: 'html',
				success: function(res) {
					me.isLoadingFavorites = false;
					
					var list = jQuery(res).closest('#favoriteList');
		        	jQuery('#favoriteList').html(list.html());
		        	
		        	VTE.PanelManager.unmaskActivePanel();
				},
			});
		};
		// crmv@172169e
		
	},

	// crmv@195963
	alterSlimScroll: function() {
		var me = this;
		
		if (window.jQuery && window.jQuery.fn.slimScroll) {
			jQuery.fn.orig_slimScroll = jQuery.fn.slimScroll;
			jQuery.fn.slimScroll = function(options) {
				if (typeof options === "object") {
					var s = {};
					if (window.current_user && window.current_user.dark_mode) {
						s['color'] = '#fff';
						s['railColor'] = '#fff';
					}
					options = jQuery.extend(true, options, s);
				}
				return jQuery.fn.orig_slimScroll.apply(this, arguments);
			}
		}
	},

	alterCustomScrollbar: function() {
		var me = this;
		
		if (window.jQuery && window.jQuery.fn.mCustomScrollbar) {
			jQuery.fn.orig_mCustomScrollbar = jQuery.fn.mCustomScrollbar;
			jQuery.fn.mCustomScrollbar = function(method) {
				if (typeof method === "object") {
					var s = {};
					if (window.current_user && window.current_user.dark_mode) {
						s['theme'] = 'light-thick';
					}
					method = jQuery.extend(true, method, s);
				}
				return jQuery.fn.orig_mCustomScrollbar.apply(this, arguments);
			}
		}
	},
	// crmv@195963e
	
};

function visibleWidth(element) {
	var $el = jQuery(element);
	if ($el.length < 1) return 0;
	
	var scrollLeft = jQuery(window).scrollLeft();
	var scrollRight = scrollLeft + jQuery(window).width();
   
    var elLeft = $el.offset().left;
    var elRight = elLeft + $el.outerWidth();
    var visibleLeft = elLeft < scrollLeft ? scrollLeft : elLeft;
	var visibleRight = elRight > scrollRight ? scrollRight : elRight;
	
	return (visibleRight - visibleLeft);
}

function visibleHeight(element) {
	var $el = jQuery(element);
	if ($el.length < 1) return 0;
	
	var scrollTop = jQuery(window).scrollTop();
	var scrollBot = scrollTop + jQuery(window).height();
   
    var elTop = $el.offset().top;
    var elBottom = elTop + $el.outerHeight();
    var visibleTop = elTop < scrollTop ? scrollTop : elTop;
	var visibleBottom = elBottom > scrollBot ? scrollBot : elBottom;
	
	return (visibleBottom - visibleTop);
}