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

	// add ripple to some other classes
	jQuery.material.options.withRipples += ",.crmbutton:not(.withoutripple)";
	
	// initialize basic controls
	jQuery.material.init();
	
	// tooltips
	if (themeOptions.useTooltips) {
		jQuery('img[data-toggle="tooltip"]').tooltip({animation: false});
		jQuery('span[data-toggle="tooltip"]').tooltip({animation: false});
		jQuery('i[data-toggle="tooltip"]').tooltip({animation: false});
	}
	
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

	if (themeOptions.replaceAlerts) {
		// replace alert boxes (but expose the original method)
		if (!window.origAlert) window.origAlert = window.alert;
		window.alert = function(text, cb) {
			jQuery('#alert-dialog-content').text(text);
			jQuery('#alert-dialog').modal();
			// remove handler
			jQuery('#alert-dialog').off('hidden.bs.modal');
			// call callback
			if (typeof cb == 'function') {
				jQuery('#alert-dialog').on('hidden.bs.modal', function(event){
					cb();
				});
			}
		}
	
		// replace confirm boxes (another function is needed)
		window.vteconfirm = function(text, cb) {
			function cbanswer() {
				// remove handler
				jQuery(this).off('click');
				// call callback
				if (typeof cb == 'function') cb(jQuery(this).hasClass('btn-ok'));
			}
			jQuery('#confirm-dialog-content').text(text);
			jQuery('#confirm-dialog').modal();
			jQuery('#confirm-dialog').find('button.btn-cancel').off('click').on('click', null, cbanswer);
			jQuery('#confirm-dialog').find('button.btn-ok').off('click').on('click', null, cbanswer);
			
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
	
});