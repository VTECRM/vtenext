/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@168103  crmv@207829 */

/* if you want to deprecate non core functions, do it here */

VtigerJS_DialogBox = {
	unblock : function(target) {
		VTE.logDeprecated('VtigerJS_DialogBox.unblock', 'Please use VteJS_DialogBox.unblock');
		VteJS_DialogBox.unblock(target);
	},
	block : function(target,opacity) {
		VTE.logDeprecated('VtigerJS_DialogBox.block', 'Please use VteJS_DialogBox.block');
		VteJS_DialogBox.block(target, opacity);
	},
	hideprogress : function(target) {
		VTE.logDeprecated('VtigerJS_DialogBox.hideprogress', 'Please use VteJS_DialogBox.hideprogress');
		VteJS_DialogBox.hideprogress(target);
	},
	progress : function(target,color) {
		VTE.logDeprecated('VtigerJS_DialogBox.progress', 'Please use VteJS_DialogBox.progress');
		VteJS_DialogBox.progress(target, color);
	},
	hideconfirm : function() {
		VTE.logDeprecated('VtigerJS_DialogBox.hideconfirm', 'Please use VteJS_DialogBox.hideconfirm');
		VteJS_DialogBox.hideconfirm();
	},
	confirm : function(msg, onyescode) {
		VTE.logDeprecated('VtigerJS_DialogBox.confirm', 'Please use VteJS_DialogBox.confirm');
		VteJS_DialogBox.confirm(msg, onyescode);
	},
	notify : function(msg, interval) {
		VTE.logDeprecated('VtigerJS_DialogBox.notify', 'Please use VteJS_DialogBox.notify');
		VteJS_DialogBox.notify(msg, interval);
	},
	hidenotify : function() {
		VTE.logDeprecated('VtigerJS_DialogBox.hidenotify', 'Please use VteJS_DialogBox.hidenotify');
		VteJS_DialogBox.hidenotify();
	}
};

(function() {
	// deprecate scriptaculous effects
	
	if (window.Effect) {
		Effect.Fade = VTE.deprecateFn('Effect.Fade', 'Please use jQuery.fadeOut');
		Effect.Appear = VTE.deprecateFn('Effect.Appear', 'Please use jQuery.fadeIn');
		Effect.Puff = VTE.deprecateFn('Effect.Puff', 'Please use jQuery.fadeOut');
		Effect.Grow = VTE.deprecateFn('Effect.Grow');
	}
	
	// crmv@192014
	// deprecate Drag library since jQuery.draggable can do the same
	if (window.Drag) {
		Drag.init = VTE.deprecateFn('Drag.init', 'Please use jQuery.draggable');
	} else {
		// polyfill for old code calling Drag.init, will be removed in the future
		window.Drag = {
			init: function(handle, root) {
				VTE.logDeprecated('Drag.init', 'Please use jQuery.draggable, this is only a limited polyfill');
				jQuery(root).draggable({handle: handle});
			}
		}
	}
	
	// deprecate Sortable library since jQuery.sortable can do the same
	if (window.Sortable) {
		Sortable.create= VTE.deprecateFn('Sortable.create', 'Please use jQuery.sortable');
	} else {
		// polyfill for old code calling Drag.init, will be removed in the future
		window.Sortable = {
			create: function(elid, opts) {
				VTE.logDeprecated('Sortable.create', 'Please use jQuery.draggable, this is only a limited polyfill');
				jQuery('#'+elid).sortable({
					items: '> ' + (opts.tag || '*'),
					handle: opts.handle ? '.' + opts.handle : false,
					update: opts.onUpdate
				});
			},
			serialize: function(sel) {
				var list = jQuery('#'+sel).sortable('toArray').map(function(id) {
					return sel+'[]=' + id.replace(/^stuff_/, '');
				});
				return list.join('&');
			}
		}
	}
	// crmv@192014e
})();