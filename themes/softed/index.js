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
			var me = this;

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

	adjustComponents: function() {},
	
	initilizeEvents: function() {},
	
	overrideStdClass: function() {},

	// crmv@195963
	alterSlimScroll: function() {},

	alterCustomScrollbar: function() {},
	// crmv@195963e
	
};