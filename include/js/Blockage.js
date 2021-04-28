/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@140887 // crmv@171115

var Blockage = Blockage || {
	
	checks: [],
	blocked: false,
	panelBlockers: {},
	
	addCheck: function(check) {
		var me = this;
		
		if (typeof check == 'function') {
			me.checks.push(check);
		}
		
		me.blockNavigation(true);
		
		return true;
	},
	
	blockNavigation: function(blocked) {
		var me = this;
		
		me.blocked = blocked;
		
		if (me.blocked) {
			window.onbeforeunload = function(e) {
				return me.checkNavigation();
			}
		} else {
			window.onbeforeunload = null;
			me.checks = [];
		}
		
		return true;
	},
	
	checkNavigation: function() {
		var me = this,
			checks = me.checks;
		
		if (me.blocked) {
			for (var i = 0; i < checks.length; i++) {
				var check = checks[i];
				
				if (typeof check == 'function') {
					var message = check();

					if (message !== false) {
						return message;
					}
				}
			}
		}
		
		return;
	},
	
	releaseBlock: function() {
		var me = this;
		
		me.checks = [];
		me.blocked = false;
		window.onbeforeunload = null;
	},
	
	registerPanelBlocker: function(name, selectors, options) {
		var me = this,
			blockage = top.Blockage,
			panelBlockers = blockage.panelBlockers,
			options = options || {};
			
		var panelBlocker = null;
		panelBlocker = jQuery.extend({}, {}, Blockage.PanelBlocker);
		panelBlocker.init(name, selectors);
		panelBlockers[name] = panelBlocker;
		
		panelBlocker.start();
		
		// Initialize Blockage check for the top window
		// If the inputs inside the selectors variable are modified then the exit from the panel is blocked
		
		if (!blockage.isWindowBlocked) {
			blockage.addCheck(function() {
				for (var key in panelBlockers) {
					if (panelBlockers.hasOwnProperty(key)) {
						if (panelBlockers[key].windowEdited) {
							return alert_arr['confirm_exit_from_panel'];
						}
					}
				}
			});
			blockage.isWindowBlocked = true;
		}
		
		return panelBlocker;
	},
	
	checkPanelBlocker: function(callback) {
		var me = this,
			blockage = top.Blockage;
		
		var checkNav = blockage.checkNavigation();
		if (checkNav && checkNav.length > 0) {
			if (!me.checkNavActive) {
				vteconfirm(checkNav, function(y) {
					if (y) {
						blockage.releasePanelBlockers();
						callback();
					}
					me.checkNavActive = false;
				});
				me.checkNavActive = true;
			}
			return false;
		} else {
			callback();
		}
	},
	
	releasePanelBlocker: function(panelBlocker, options) {
		var me = this,
			blockage = top.Blockage,
			panelBlockers = blockage.panelBlockers,
			options = options || {};
		
		for (var key in panelBlockers) {
			if (panelBlockers.hasOwnProperty(key)) {
				if (panelBlockers[key] === panelBlocker) {
					panelBlockers[key].clear(options);
					return true;
				}
			}
		}
		
		return false;
	},
	
	releasePanelBlockers: function(options) {
		var me = this,
			blockage = top.Blockage,
			panelBlockers = blockage.panelBlockers,
			options = options || {};
		
		for (var key in panelBlockers) {
			if (panelBlockers.hasOwnProperty(key)) {
				panelBlockers[key].clear(options);
			}
		}
		
		return true;
	},
	
};

Blockage.PanelBlocker = Blockage.PanelBlocker || {
	
	name: null,
	
	selectors: null,
	
	checkChangesMatrix: {},
	
	windowEdited: false,
	
	init: function(name, selectors) {
		var me = this;
		
		me.name = name;
		
		selectors = selectors || [];
		me.selectors = selectors;
	},
	
	start: function() {
		var me = this;
			
		if (me.started) return;
		
		me.started = true;
		var counter = 0;

		me.selectors.forEach(function(selector) { // crmv@192033
			if (selector.length > 0) {
				jQuery(selector).each(function(idx, element) {
					me.clearInput(counter, element);
					me.initInput(counter, element);
					counter++;
				});
			}
		});
	},
	
	stop: function() {
		var me = this;
		
		if (me.started) {
			var counter = 0;
			me.selectors.forEach(function(selector) { // crmv@192033
				if (selector.length > 0) {
					jQuery(selector).each(function(idx, element) {
						me.clearInput(counter, element);
						counter++;
					});
				}
			});
			
			me.started = false;
		}
	},
	
	onInputEdit: function(event) {
		var me = this,
			inputElement = jQuery(event.currentTarget);
		
		me.inputEdit(inputElement);
	},
	
	initInput: function(idx, element) {
		var me = this,
			$element = jQuery(element);
		
		var inputEditFunction = jQuery.proxy(me.onInputEdit, me);
		$element.on('keyup.Blockage', inputEditFunction);
		$element.on('change.Blockage', inputEditFunction);
	},
	
	clearInput: function(idx, element) {
		var me = this,
			$element = jQuery(element);
		
		$element.data('Blockage_checkChangesId', idx);
		$element.data('Blockage_inputPrevValue', null);

		var inputEditFunction = jQuery.proxy(me.onInputEdit, me);
		$element.off('keyup.Blockage', inputEditFunction);
		$element.off('change.Blockage', inputEditFunction);
		
		me.checkChangesMatrix[idx] = false;
	},
	
	inputEdit: function(inputElement) {
		var me = this;
		
		if (!me.started) return;
		if (me.clearing) return;
		
		if (!inputElement.val().length) {
			var checkChangesIdx = inputElement.data('Blockage_checkChangesId');
			me.checkChangesMatrix[checkChangesIdx] = false;
			inputElement.data('Blockage_inputPrevValue', '');
		} else {
			var prevValue = inputElement.data('Blockage_inputPrevValue');
			if (!prevValue || prevValue === '') {
				var checkChangesIdx = inputElement.data('Blockage_checkChangesId');
				me.checkChangesMatrix[checkChangesIdx] = true;
				inputElement.data('Blockage_inputPrevValue', inputElement.val());
			}
		}
		
		var check = Object.values(me.checkChangesMatrix).reduce(function(a, b) {
			return a || b;
		}, false);
		
		me.windowEdited = check;
	},
	
	clear: function(options) {
		var me = this,
			options = options || {};
			
		if (me.clearing) return;
		me.clearing = true;
		
		me.stop();
		
		if (options.restart) me.start();
		
		me.windowEdited = false;
		me.clearing = false;
	},
	
};