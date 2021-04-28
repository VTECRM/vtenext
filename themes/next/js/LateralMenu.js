/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@140887 
// crmv@170652
// crmv@186472

var LateralMenu = LateralMenu || {
	
	busy: false,
	
	isOpen: false,
	
	isForced: false,
	
	isMenuOpen: false,
	
	leftPanel: '#leftPanel',
	
	direction: 'left',
	
	width: 220,
	
	minWidth: 60,
	
	showBusy: function() {
		var me = this;
		me.busy = true;
	},
	
	hideBusy: function() {
		var me = this;
		me.busy = false;
	},
	
	getPanel: function() {
		var me = this,
			$panel = jQuery(me.leftPanel);
		
		return $panel;
	},

	open: function(options, callback) {
		var me = this,
			panel = me.getPanel(),
			options = options || {};
			
		me.isOpen = true;
		
		panel.velocity("stop", true).velocity({
			 width: me.width + "px",
		}, {
			easing: "easeInSine",
			duration: 200,
		    complete: function(elements) {
		    	me.toggleState('open', options);
				if (jQuery.isFunction(callback)) {
					callback();
				}
		    }
		});
	},
	
	close: function(options, callback) {
		var me = this,
			panel = me.getPanel(),
			options = options || {};
			
		me.isOpen = false;
		
		panel.velocity("stop", true).velocity({
			 width: me.minWidth + "px",
		}, {
			easing: "easeInSine",
			duration: 200,
		    complete: function(elements) {
		    	me.toggleState('close', options);
				if (jQuery.isFunction(callback)) {
					callback();
				}
		    }
		});
	},
	
	toggle: function(options, callback) {
		var me = this,
			panel = me.getPanel();
		
		me[me.isOpen ? 'close' : 'open'](options, function() {
			if (jQuery.isFunction(callback)) {
				callback();
			}
		});
	},
	
	toggleState: function(state, options) {
		var me = this,
			options = options || {},
			panel = me.getPanel(),
			mainCont = jQuery('#mainContainer'),
			minified = panel.attr('data-minified') || 'enabled';
		
		if (state == 'open') {
			minified = 'disabled';
		} else if (state == 'close') {
			minified = 'enabled';
		} else {
			minified = minified === 'enabled' ? 'disabled' : 'enabled';
		}
		
		panel.attr('data-minified', minified);
		mainCont.attr('data-minified', minified);
	},
	
	clickToggle: function(options, callback) {
		var me = this,
			togglePin = jQuery('#leftPanel .togglePin');
		
		if (me.busy) return false;
		
		me.showBusy();
		
		me.isForced = !me.isForced;
		
		LateralMenu[me.isForced ? 'open' : 'close'](options, function() {
			me.lateralMenuToggled(callback);
			me.hideBusy();
		});
	},
	
	lateralMenuToggled: function(callback) {
		var me = this,
			togglePin = jQuery('#leftPanel .togglePin');
		
		if (me.isOpen) {
			togglePin.addClass('active');
			jQuery('body').addClass('left-menu-active');
		} else {
			togglePin.removeClass('active');
			jQuery('body').removeClass('left-menu-active');
		}
		
		var toggleState = null;
		
		if (me.isForced) {
			toggleState = 'disabled';
		} else {
			toggleState = 'enabled';
		}
		
		jQuery('#mainContainer').attr('data-toggled', toggleState);
		jQuery('.buttonsList.buttonsListFixed').attr('data-minified', toggleState);
		
		set_cookie('togglePin', toggleState);
		
		Theme.adjustComponents();
		VTE.PanelManager.resize();
		
		if (jQuery.isFunction(callback)) {
			callback();
		}
	},
	
	hoverMe: function(options, callback) {
		var me = this;
		
		if (me.isForced) return;
		if (me.isMenuOpen) return;

		if (me.openTimeout) {
			clearTimeout(me.openTimeout);
			me.openTimeout = null;
		}
		
		if (me.closeTimeout && me.isOpen) {
			clearTimeout(me.closeTimeout);
			me.closeTimeout = null;
		}
		
		me.openTimeout = setTimeout(function() {
			if (!me.isOpen) {
				me.toggle(options, callback);
			}
		}, 400);
	},
	
	hoverMeExit: function(options, callback) {
		var me = this;
		
		if (me.isForced) return;

		if (me.openTimeout && !me.isOpen) {
			clearTimeout(me.openTimeout);
			me.openTimeout = null;
		}
		
		if (me.closeTimeout) {
			clearTimeout(me.closeTimeout);
			me.closeTimeout = null;
		}
		
		me.closeTimeout = setTimeout(function() {
			if (me.isOpen) {
				me.toggle(options, callback);
			}
		}, 600);
	},
	
	init: function(options) {
		var me = this,
			options = options || {},
			panel = me.getPanel(),
			togglePin = jQuery('#leftPanel .togglePin');
			
		if (!panel) return;
		
		var openCallback = options.openCallback || function() {};
		var closeCallback = options.closeCallback || function() {};
		var toggleCallback = options.toggleCallback || function() {};
		var direction = options.direction || me.direction;
		
		panel.hover(
			jQuery.proxy(me.hoverMe, LateralMenu, options, openCallback), 
			jQuery.proxy(me.hoverMeExit, LateralMenu, options, closeCallback)
		);

		togglePin.click(jQuery.proxy(me.clickToggle, LateralMenu, options, toggleCallback));
		
		var toggleState = get_cookie('togglePin');
		if (toggleState === 'disabled') {
			me.isForced = true;
			jQuery('body').addClass('left-menu-active');
		}
		
		me.direction = direction;
		
		if (direction === 'left') {
			panel.attr('data-direction', 'left');
		} else if (direction === 'right') {
			panel.attr('data-direction', 'right');
		} else {
			panel.attr('data-direction', 'left');
		}
	},
	
};

jQuery(document).ready(function() {
	LateralMenu.init();
});