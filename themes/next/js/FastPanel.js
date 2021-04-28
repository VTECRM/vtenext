/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@140887 crmv@166949 // crmv@194443

jQuery(document).ready(function() {
	
	VTE.PanelFactory = VTE.PanelFactory || {
		
		panelPrefix: 'VTpanel',
		
		panelId: 0,
		
		generate: function(type, params) {
			var me = this;
			
			var panel = null;
			
			if (type == 'fastPanel') {
				panel = me.generateFastPanel();
			} else if (type == 'detailPanel') {
				panel = me.generateDetailPanel();
			} else if (type == 'relatedPanel') {
				panel = me.generateRelatedPanel();
			} else {
				console.error('Unknown type');
			}
			
			if (panel) {
				var panelId = me.generateId();
				panel.id = panelId;
				
				var element = jQuery('<div>', {
					'id': panelId,
					'class': 'fastPanel',
				});
				
				jQuery('#mainContent').append(element);
			}
			
			return panel;
		},
		
		generateFastPanel: function() {
			return jQuery.extend({}, {}, VTE.FastPanel);
		},
		
		generateDetailPanel: function() {
			return jQuery.extend({}, {}, VTE.FastDetailPanel);
		},
		
		generateRelatedPanel: function() {
			return jQuery.extend({}, {}, VTE.FastRelatedPanel);
		},
		
		generateId: function() {
			var me = this,
				panelPrefix = me.panelPrefix;
			
			me.panelId++;
			
			return [panelPrefix, me.panelId].join('_');
		},
		
	};
	
	VTE.PanelManager = VTE.PanelManager || {
		
		panels: [],
		
		historyUrl: [],
		
		historyMaxSize: 15,

		zIndex: 1000,
		
		currentGenId: 0,

		maskActivePanel: function() {
			var me = this;
			
			var panel = me.getActivePanel();
			if (panel) panel.showBusy();
		},
		
		unmaskActivePanel: function() {
			var me = this;
			
			var panel = me.getActivePanel();
			if (panel) panel.hideBusy();
		},
		
		isActivePanelBusy: function() {
			var me = this;
			
			var panel = me.getActivePanel();
			if (panel) return panel.busy;
		},
		
		canAddPanel: function() {
			var me = this;
			return !(me.panels.length >= me.historyMaxSize);
		},
		
		canOpenNewPanel: function() {
			var me = this;
			
			if (!me.canAddPanel()) return false;
			if (me.isActivePanelBusy()) return false;
			
			return true;
		},
		
		addPanel: function(panel, replaceHistory) {
			var me = this,
				replaceHistory = replaceHistory || false;
			
			if (!me.canOpenNewPanel()) return;
			
			me.removePanel(panel);
			me.panels.push(panel);
			
			if (replaceHistory) {
				var url = panel.currentUrl;
				me.addHistoryUrl(url);
			}
		},
		
		removePanel: function(panel, replaceHistory) {
			var me = this,
				panels = me.panels,
				replaceHistory = replaceHistory || false;
			
			var newPanels = me.panels.filter(function(p) {
				if (p && p.id) {
					return p.id !== panel.id;
				}
			});
			
			me.panels = newPanels;
			
			if (replaceHistory) {
				var url = panel.currentUrl;
				me.removeHistoryUrl(url);
			}
		},
		
		addHistoryUrl: function(url) {
			var me = this,
				historyUrl = me.historyUrl;
			
			if (!history.replaceState) return;
			
			if (historyUrl.length === 0) {
				historyUrl.push(window.location.href);
			}
			
			historyUrl.push(url);
			
			history.replaceState(null, null, url);
		},
		
		removeHistoryUrl: function(url) {
			var me = this,
				historyUrl = me.historyUrl;
			
			if (!history.replaceState) return;
			
			historyUrl.reverse();
			
			var idx = 0;
			var found = false;
			
			jQuery.each(historyUrl, function(i, h) {
				if (h === url) {
					idx = i;
					found = true;
					return false;
				}
			});
			
			if (found) historyUrl.splice(idx, 1);
			
			historyUrl.reverse();
			
			var lastHistoryUrl = me.getLastHistoryUrl();
			history.replaceState(null, null, lastHistoryUrl);
		},
		
		getLastHistoryUrl: function() {
			var me = this,
				historyUrl = me.historyUrl;
			
			if (historyUrl.length > 0) {
				return historyUrl.slice(-1).pop();
			}
			
			return null;
		},
		
		closeActivePanel: function() {
			var me = this,
				panels = me.panels;
				
			var panel = me.getActivePanel();
			
			if (panel) {
				var closed = panel.forceClose();
				if (closed) {
					me.removePanel(panel);
				}
			}
		},
		
		getActivePanel: function() {
			var me = this,
				panels = me.panels;
			
			if (panels.length > 0) {
				return panels.slice(-1).pop();
			}
			
			return null;
		},

		editActivePanel: function() {
			var me = this;
			
			var panel = me.getActivePanel();
			if (panel) panel.isChanged = true;
		},
		
		arePanelsEdited: function() {
			var me = this,
				panels = me.panels;
			
			var editedPanels = panels.filter(function(p) {
				return p.isChanged;
			});
			
			if (editedPanels.length > 0) return true;
			
			return false;
		},

		resize: function() {
			var me = this,
				panels = me.panels;
		
			jQuery.each(panels, function(i, p) {
				if (p && p.id && p.isOpen) {
					var bkpAnimate = p.animate;
					p.animate = false;
					p.adjustPanelWidth(p.currentOptions);
					p.getPanel().css('z-index', me.zIndex+i);
					p.animate = bkpAnimate;
				}
			});
		},
		
		findZMax: function() {
			var me = this,
				panels = me.panels;
			
			var zindex = me.zIndex;
			
			jQuery.each(panels, function(i, p) {
				if (p && p.id && p.isOpen) {
					var pz = parseInt(p.getPanel().css('z-index'));
					if (pz > zindex) {
						zindex = pz;
					}
				}
			});
		
			return zindex;
		},
		
		createUniqueID: function() {
			var me = this;
			
			me.currentGenId++;
			
			return ['panel', me.currentGenId].join('_');
		},
		
	};

	VTE.FastPanel = VTE.FastPanel || {
	
		id: 'fastPanel',
		
		mask: true,
		
		visible: true, // crmv@163519
		
		animate: true,
		
		busy: false,
		
		isOpen: false,
		
		isSwitching: false,

		mode: 'full', // full, half, custom
		
		currentUrl: null,
		
		currentModule: null,
		
		currentOptions: {},
		
		direction: 'left',
		
		delay: 250,
		
		cache: {},
		
		modCommentsNewsHtmlCache: null,
		
		isCustomIconClicked: false,
		
		containerPrefix: 'VTcontainer',
		
		containers: [],
		
		replaceHistory: false,
		
		initialize: function(options) {
			var me = this,
				options = options || {};
				
			me.containers = [];
			me.addContainer('iframeCont', 'iframe');
			me.addContainer('ajaxCont', 'ajax');
			me.addContainer('searchCont', 'ajax');
			me.addContainer('menuCont', 'ajax');
		},
		
		showBusy: function() {
			var me = this;
			me.progress(me.id, 'light');
			me.busy = true;
		},
		
		hideBusy: function() {
			var me = this;
			me.hideprogress(me.id);
			me.busy = false;
		},
		
		getPanel: function() {
			var me = this;
			return jQuery('#' + me.id);
		},
		
		addContainer: function(id, type, params) {
			var me = this,
				panel = me.getPanel();
			
			var container = null;
			
			if (type == 'iframe') {
				var iframe = jQuery('<iframe>');
				
				container = jQuery('<div>', jQuery.extend({
					'class': 'iframeCont',
				}, params || {}));

				container.append(iframe);
			} else if (type == 'ajax') {
				container = jQuery('<div>', jQuery.extend({
					'class': 'ajaxCont',
				}, params || {}));
			} else {
				console.error('Unknown type');
				return;
			}
			
			var containerId = me.generateContainerId(id);
			container.attr('id', containerId);
			
			me.containers.push(container);
			
			panel.append(container);
		},
		
		generateContainerId: function(containerId) {
			var me = this,
				id = me.id,
				prefix = me.containerPrefix;
			
			return [prefix, id, containerId].join('_');
		},
		
		getContainer: function(id) {
			var me = this;
			
			var containerId = me.generateContainerId(id);
			
			var container = me.containers.filter(function(c) {
				var $c = jQuery(c);
				if ($c && $c.length > 0) {
					if ($c.attr('id') === containerId) {
						return true;
					}
				}
			}).pop();
			
			return container;
		},
		
		hideAllContainers: function(callback) {
			var me = this;
			
			if (me.containers.length > 0) {
				var countProcessed = 0;
				var containersLength = me.containers.length;
				
				jQuery.each(me.containers, function(i, container) {
					var $container = jQuery(container);
					if ($container && $container.length > 0) {
						if ($container.is(':visible')) {
							$container.hide();
							if ($container.hasClass('iframeCont')) {
								$container.find('iframe').attr('src', 'about:blank');
							}
							checkProcessed();
						} else {
							checkProcessed();
						}
					}
				});
				
				function checkProcessed() {
					countProcessed++;
					if (countProcessed === containersLength) {
						if (jQuery.isFunction(callback)) {
							callback();
						}
					}
				}
			} else {
				if (jQuery.isFunction(callback)) {
					callback();
				}
			}
		},
		
		renderContainer: function(id, content) {
			var me = this,
				container = me.getContainer(id);
			
			if (container.length > 0) {
				container.html(content);
			}
			
			return container;
		},
		
		getContainersByType: function(type) {
			var me = this;
			
			var containers = [];
			
			jQuery.each(me.containers, function(i, container) {
				var $container = jQuery(container);
				if ($container && $container.length > 0) {
					if ($container.is(type)) {
						containers.push(container);
					}
				}
			});
			
			return containers;
		},
		
		getCurrentOptions: function(options) {
			var me = this;
			
			return jQuery.extend({}, {
				'mode': me.mode,
				'delay': me.delay,
				'direction': me.direction, 
			}, options);
		},
		
		open: function(options, callback) {
			var me = this;

			if (!me.visible) {
				me.isOpen = true;
				VTE.PanelManager.addPanel(me, false);
				if (jQuery.isFunction(callback)) {
					callback();
				}
				return;
			}
			
			me.hideAllContainers(function() {
				me.adjustPanelWidth(options, completeAnimation);
			});
			
			function completeAnimation() {
				me.isOpen = true;

				if (me.mask) me.showMask();
				
				me.toggleState(options);
				
				if (jQuery.isFunction(callback)) {
					callback();
				}
			}
		},
		
		adjustPanelWidth: function(options, callback) {
			var me = this,
				fastPanel = me.getPanel(),
				direction = options.direction;
			
			var totalWidth = me.calculateWidth(options);
			
			if (direction == 'right') {
				var leftPanel = jQuery('#leftPanel');
				var leftWidth = parseInt(leftPanel.width());

				fastPanel.css('right', 'auto');
				fastPanel.css('left', leftWidth + 'px');
			} else {
				var rightPanel = jQuery('#rightPanel');
				var rightWidth = parseInt(rightPanel.width());

				fastPanel.css('left', 'auto');
				fastPanel.css('right', rightWidth + 'px');
			}
			
			fastPanel.css('z-index', VTE.PanelManager.findZMax()+1);
			
			if (me.animate) {
				fastPanel.velocity({
					'width': totalWidth,
				}, {
					easing: "easeOutElastic",
					duration: options.delay,
					complete: callback,
				});
			} else {
				fastPanel.css({
					'width': totalWidth,
				});
				if (jQuery.isFunction(callback)) {
					callback();
				}
			}
		},
		
		calculateWidth: function(options) {
			var me = this,
				lateralMenu = LateralMenu;
				mode = options.mode;
			
			var totalWidth = '';

			var leftSize = null;

			if (LateralMenu.isForced) {
				leftSize = lateralMenu.width;
			} else {
				leftSize = lateralMenu.minWidth;
			}
			
			var rightSize = parseInt(jQuery('#rightPanel').width());

			var wrapperWidth = parseInt(window.innerWidth);
			wrapperWidth = wrapperWidth - leftSize - rightSize;
			
			var unit = (/%/i.test(options.size) ? '%' : 'px');
			var panelSize = parseInt(options.size);
			
			if (mode === 'custom') {
				if (unit == '%') {
					totalWidth = parseInt((wrapperWidth * panelSize) / 100) + 'px';
				} else {
					totalWidth = panelSize;
				}
			} else {
				var percent;
				
				if (mode === 'full') {
					percent = 100.0;
				} else if (mode === 'half') {
					percent = 50.0;
				}
				
				totalWidth = parseInt((wrapperWidth * percent) / 100) + 'px';
			}
			
			if (wrapperWidth <= 850) {
				totalWidth = wrapperWidth + 'px';
			}
			
			return totalWidth;
		},
		
		showMask: function() {
			var me = this,
				panel = me.getPanel();
			
			var mask = panel.find('.maskFastPanel');
			
			if (mask.length < 1) {
				mask = me.createMask();
			}
			
			mask.fadeIn('fast');
		},
		
		createMask: function() {
			var me = this,
				panel = me.getPanel();
			
			var zmax = 0;
			panel.children().each(function() {
				var cur = parseInt(jQuery(this).css('zIndex'));
				zmax = cur > zmax ? jQuery(this).css('zIndex') : zmax;
		 	});
			
			var mask = jQuery('<div>', {
				'class': 'maskFastPanel',
				'css': {
					'background-color': 'rgba(0, 0, 0, 0.3)',
					'position': 'fixed',
					'top': '0',
					'left': '0',
					'width': '100%',
					'height': '100%',
					'z-index': (zmax-1),
				},
			});

			mask.on('click', function() { 
				me.forceClose();
			});
			
			panel.append(mask);

			return mask;
		},
		
		removeMask: function() {
			var me = this,
				panel = me.getPanel();
		
			var mask = panel.find('.maskFastPanel');
			mask.fadeOut('fast');
		},
		
		close: function(options, callback) {
			var me = this,
				options = options || {},
				fastPanel = me.getPanel();

			if (!me.visible) {
				me.isOpen = false;
				VTE.PanelManager.removePanel(me, false);
				if (jQuery.isFunction(callback)) {
					callback();
				}
				return;
			}
			
			if (me.mask) me.removeMask();
			
			var totalWidth = '0px';
			
			if (me.animate) {
				fastPanel.velocity({
					'width': totalWidth,
				}, {
					asing: "easeOutElastic",
					duration: options.delay,
					complete: completeAnimation,
				});
			} else {
				fastPanel.css({
					'width': totalWidth,
				});
				
				completeAnimation();
			}
				
			function completeAnimation() {
				me.hideAllContainers(function() {
					me.isOpen = false;
					
					me.toggleState(options);
					
					if (jQuery.isFunction(callback)) {
						callback();
					}
				});
			}
		},
		
		forceClose: function() {
			var me = this,
				options = me.currentOptions;
			
			if (!me.isOpen) return;
			
			options = jQuery.extend({}, options, {
				'forceClose': true,
			});
	
			return me.toggle(me.currentId, me.currentModule, me.currentUrl, {}, options);
		},
		
		toggle: function(id, module, url, params, options, callback) {
			var me = this,
				fastPanel = me.getPanel(),
				options = me.getCurrentOptions(options);
			
			me.preToggleHandler();
			
			Blockage.checkPanelBlocker(toggleChecked); // crmv@171115
			
			function toggleChecked() {
				if (me.isOpen && id != me.currentId && !options.forceClose) {
					me.isSwitching = true;
					me.close(me.currentOptions, function() {
						processToggle();
					});
				} else {
					processToggle();
				}
				
				function processToggle() {
					me.currentId = id;
					me.currentUrl = url;
					me.currentModule = module;
					me.currentOptions = options;
					
					me[me.isOpen ? 'close' : 'open'](options, function() {
						me.isSwitching = false;
						me.load(module, url, params, options, callback);
					});
				}
			}
		},
		
		preToggleHandler: function() {
			jQuery('[data-entitypreview="true"]').vtentitypreview('close');
			
			if (jQuery('#addEvent').length > 0) {
				hideFloatingDiv('addEvent');
			}
		},
		
		load: function(module, url, params, options, callback) {
			var me = this;
			
			if (url && url.length > 0 && me.isOpen) {
				if (options && options.iframe) {
					me.iframeLoad(module, url, params, options, callback);
				} else {
					me.ajaxCall(module, url, params, options, callback);
				}
			} else {
				if (jQuery.isFunction(callback)) {
					callback(false);
				}
			}
		},
		
		toggleState: function(options) {
			var me = this,
				options = options || {},
				fastPanel = me.getPanel(),
				isSwitchingPanels = me.isSwitching;

			if (!me.visible) return;
				
			if (me.isOpen) {
				me.disableScroll();
			} else {
				if (!isSwitchingPanels) {
					me.enableScroll();
				}
			}
			
			fastPanel.attr('data-minified', me.isOpen ? 'disabled' : 'enabled');
			
			if (options.target) {
				var target = jQuery(options.target);
				if (target.length > 0) {
					jQuery('[data-fastpanel]').removeClass('active');
					target[me.isOpen ? 'addClass' : 'removeClass']('active');
				}
			}
			
			var bkpReplaceHistory = me.replaceHistory;
			
			if (me.currentModule == 'Calendar') {
				me.replaceHistory = true;
			}
			
			me[me.isOpen ? 'toggleStateOpen' : 'toggleStateClose'](options);
			
			me.replaceHistory = bkpReplaceHistory;
		},
		
		toggleStateOpen: function(options) {
			var me = this,
				options = options || {};
			
			VTE.PanelManager.addPanel(me, me.replaceHistory);
		},
		
		toggleStateClose: function(options) {
			var me = this,
				options = options || {};
			
			VTE.PanelManager.removePanel(me, me.replaceHistory);
			
			var trackedRecord = CalendarTracking.getCurrentTrackedId();

			if (trackedRecord > 0 && me.currentModule == 'LBL_TRACK_MANAGER') {
				var record = parseInt(jQuery('input[name="record"]').val());
				if (record > 0) {
					CalendarTracking.reloadButtons(record);
				}
			}
			
			LateralMenu.isMenuOpen = false;
		},

		disableScroll: function() {
			var me = this;
			
			if (me.isScrollDisabled) return;
			
			if (window.bodyScrollLock) {
				var options = {
					reserveScrollBarGap: true
				};
				
				bodyScrollLock.disableBodyScroll(me.getPanel()[0], options);
				
				jQuery.each(me.containers, function(i, container) {
					bodyScrollLock.disableBodyScroll(container[0], options);
				});
				
				me.isScrollDisabled = true;
			}
		},

		enableScroll: function() {
			var me = this;
			
			if (!me.isScrollDisabled) return;
			
			if (window.bodyScrollLock) {
				bodyScrollLock.enableBodyScroll(me.getPanel()[0]);
				
				jQuery.each(me.containers, function(i, container) {
					bodyScrollLock.enableBodyScroll(container[0]);
				});
				
				me.isScrollDisabled = false;
			}
		},

		progress: function(target, color) {
			var me = this,
				color = color || 'dark',
				progressId = me.generateProgressId(target);
				
			var node = jQuery('#' + progressId);
			
			if (node.length < 1) {
				me.createProgressNode(target, color);
			}
			
			node.show();
		},
		
		hideprogress: function(target) {
			var me = this,
				progressId = me.generateProgressId(target);
			
			VteJS_DialogBox._removebyid(progressId);
		},
		
		createProgressNode: function(target, color) {
			var me = this,
				panel = me.getPanel(),
				progressId = me.generateProgressId(target),
				classes = me.getClassesByColor(color);

			var zmax = 0;
			panel.children().each(function() {
				var cur = parseInt(jQuery(this).css('zIndex'));
				zmax = cur > zmax ? jQuery(this).css('zIndex') : zmax;
			});
			
			var node = jQuery('<div>', {
				'id': progressId,
				'class': classes.layer,
				'css': {
					'position': 'absolute',
					'width': '100%',
					'height': '100%',
					'top': '0',
					'left': '0',
					'display': 'block',
					'zIndex': zmax
				},
			});
			
			jQuery('#' + target).append(node);
			
			var loaderTable = jQuery('<table>', {
				'align': 'center',
				'width': '100%',
				'height': '100%',
			});
			
			loaderTable.html('<tr><td class="big" align="center"><div class="' + classes.loader + '">Loading...</div></td></tr>');
			
			node.append(loaderTable);
			
			return node;
		},
		
		generateProgressId: function(target) {
			var me = this,
				progressId = 'fastpanel_dialogbox';
			
			if (target.length > 0) {
				progressId += '_' + target;
			}
			
			return progressId;
		},
		
		getClassesByColor: function(color) {
			var me = this;
			
			var classes = {'loader': 'loader', 'layer': 'veil_new'};
			
			if (color == 'light') {
				classes.loader = 'vteLoader';
				classes.layer = 'veil_light';
			} else {
				console.error('Unknown color');
			}
			
			return classes;
		},
		
		iframeLoad: function(module, url, params, options, callback) {
			var me = this,
				options = options || {},
				params = params || {},
				iframeCont = me.getContainer('iframeCont');
			
			if (me.busy) return;

			me.showBusy();
			
			// crmv@170412 crmv@200006
			SessionValidator.check({
				async: true
			}, sessionCheckCallback);

			function sessionCheckCallback(success, error) {
				if (!success) {
					me.hideBusy();
					SessionValidator.showLogin();
					if (jQuery.isFunction(callback)) {
						callback();
					}
				} else {
					var urlParams = url + '&' + jQuery.param(params);

					iframeCont.show();
					var iframe = iframeCont.find('iframe');
					
					iframe.attr('src', urlParams).one('load', function() {
						me.hideBusy();
						
						if (jQuery.isFunction(callback)) {
							callback();
						}
					});
				}
			}
			// crmv@170412e crmv@200006e
		},
		
		ajaxCall: function(module, url, params, options, callback, failure) {
			var me = this,
				params = params || {},
				options = options || {};
				
			if (me.busy) return;

			if (options.cache) {
				if (!me.cache[module]) me.cache[module] = { 'ts': 0 };
				
				// crmv@168537
				if (options.cacheTime > 0) {
					if (!me.checkCache(module, options.cacheTime)) {
						if (jQuery.isFunction(callback)) {
							callback(false);
						}
						return false;
					}
				} else {
					if (me.cache[module]['ts'] > 0) {
						if (jQuery.isFunction(callback)) {
							callback(false);
						}
						return false;
					}
				}
				// crmv@168537e
				
				me.cache[module]['ts'] = (new Date()).getTime();
			}

			me.showBusy();
			
			// crmv@170412 crmv@200006
			SessionValidator.check({
				async: true
			}, sessionCheckCallback);

			function sessionCheckCallback(success, error) {
				if (!success) {
					me.hideBusy();
					SessionValidator.showLogin();
					me.resetModuleCache(module);
					callback(false);
				} else {
					jQuery.ajax({
						url: url,
						method: 'GET',
						data: params,
						success: function(res) {
							me.hideBusy();
							if (res) {
								if (jQuery.isFunction(callback)) {
									callback(res);
								}
							} else {
								console.log('Invalid data returned from server: ' + res);
								if (jQuery.isFunction(failure)) {
									failure();
								}
							}
						},
						error: function() {
							me.hideBusy();
							console.log('Ajax error');
							if (jQuery.isFunction(failure)) {
								failure();
							}
						}
					});
				}
			}
			// crmv@170412e crmv@200006e
		},
		
		checkCache: function(module, cacheTime) {
			var me = this;
			
			if (!me.cache[module]) return true;
			
			var now = (new Date()).getTime();
			
			if (!((now - me.cache[module]['ts']) > cacheTime)) {
				return false;
			}
			
			return true;
		},

		resetModuleCache: function(module) {
			this.cache[module] = {};
		},
		
		showModuleHome: function(id, module, options) {
			var me = this,
				options = options || {},
				ajaxCont = me.getContainer('ajaxCont'),
				ajaxSearchCont = me.getContainer('searchCont'),
				menuCont = me.getContainer('menuCont');
				
			var url = null;
			var params = {};
			var callback = null;
			
			me.isCustomIconClicked = false;

			// TODO spostare tutto in sdk_menu_fixed gestendo in un campo json params, options, callback, etc.
				
			if (module == 'Messages' || module == 'Calendar' || module == 'Processes') {
				
				url = 'index.php?module='+module+'&action=index';
				if (module == 'Processes') url += '&viewname=Pending'; //crmv@183872
				
				params = {
					'hide_menus': true,
					'skip_vte_footer': true,
				};
				
				options['iframe'] = true;
				
			} else if (module == 'ModComments') {
				
				params = {};
				
				callback = function(res) {
					if (me.isOpen) {
						if (me.modCommentsNewsHtmlCache == null) {
							jQuery('#ModCommentsNews_Handle_Title').html('');
							jQuery('#ModCommentsNews_Handle').removeClass('level3Bg');
							jQuery('#ModCommentsNews_Handle').css('cursor', 'default');
							jQuery('#ModCommentsNews .closebutton').remove();
							me.modCommentsNewsHtmlCache = jQuery('#ModCommentsNews').html();
							jQuery('#ModCommentsNews').remove();
						}
						
						me.renderContainer('ajaxCont', me.modCommentsNewsHtmlCache).show();
						
						loadModCommentsNews(VTE.ModCommentsCommon.default_number_of_news);
						jQuery('#modcomments_search_text').val('');
						jQuery('#modcomments_search_text').blur();
						
						var mainHeight = parseInt(ajaxCont.outerHeight());
						var headerHeight = parseInt(ajaxCont.find('table').first().outerHeight());
						var totalHeight = (mainHeight - headerHeight - 8) + 'px';
						jQuery('#ModCommentsNews_iframe').height(totalHeight);
					}
				};
				
			} else if (module == 'ModNotifications') {
				
				var ajaxContainerId = ajaxCont.attr('id');
				url = 'index.php?module=ModNotifications&action=ModNotificationsAjax&file=ModNotificationsWidgetHandler&ajax=true&widget=DetailViewBlockCommentWidget&parentid=&criteria=20&target_frame='+ajaxContainerId; //crmv@174098
				params = {};
				
				callback = function(res) {
					me.renderContainer('ajaxCont', res).show();
					
					ajaxCont.find('.ModCommUnseen').click(function() {
	                     var container = jQuery(this).closest('table[id^=tbl]'), 
		                     id = container.find('.dataId').html(),
		                     imgSeen = container.find('.seenIcon'), 
		                     imgUnseen = container.find('.unseenIcon');
	                     
	                     NotificationsCommon.removeChange('ModNotifications', id);
	                     container.find('.ModCommUnseen').removeClass('ModCommUnseen');
	                     imgUnseen.hide();
	                     imgSeen.show();
	                 });
				};
				
			} else if (module == 'LastViewed' || module == 'QuickCreate') {
				
				url = 'index.php?module=Home&action=HomeAjax&file=Fast'+module;
				params = {};
				
				callback = function(res) {
					me.renderContainer('ajaxCont', res).show();
				};
				
			} else if (module == 'TodoList') {
				
				url = 'index.php?module=SDK&action=SDKAjax&file=src/Todos/GetTodosList';
				params = {};
				
				callback = function(res) {
					me.renderContainer('ajaxCont', res).show();
				};
				
			} else if (module == 'LBL_FAVORITES') {
				
				url = 'index.php?module=SDK&action=SDKAjax&file=src/Favorites/GetFavoritesList';
				params = {};
				
				callback = function(res) {
					me.renderContainer('ajaxCont', res).show();
				};
				
			} else if (module == 'MyNotes') {
				
				url = 'index.php?module=MyNotes&action=SimpleView';
				
				params = {
					'hide_menus': true,
					'skip_vte_footer': true,
				};
				
				options['mode'] = 'full';
				options['iframe'] = true;
				
			} else if (module == 'LBL_TRACK_MANAGER') {
				
				url = 'index.php?module=SDK&action=SDKAjax&file=src/CalendarTracking/TrackerManager';
				
				params = {
					'hide_menus': true,
					'skip_vte_footer': true,
				};
				
				options['iframe'] = true;
				
			} else if (module == 'GlobalSearch') {
				
				url = 'index.php?module=Home&action=HomeAjax&file=HeaderSearchMenu'; 
				if (jQuery('#search_url').length > 0) url += jQuery('#search_url').val(); //crmv@159559
				
				callback = function(res) {
					if (res !== false) {
						UnifiedSearchAreasObj.initialized = false;
						me.renderContainer('searchCont', res);
					}
					ajaxSearchCont.show();
					
					UnifiedSearchAreasObj.show(ajaxSearchCont, 'search');
					
					var value = jQuery('#unifiedsearchnew_query_string').val();
					jQuery('#unifiedsearchnew_query_string').val(value);
					jQuery('#unified_search_icn_canc').show(); 
					
					setTimeout(function() {
						jQuery('#unifiedsearchnew_query_string').focus();
					}, 100);
				};
				
				options['cache'] = true;
				options['cacheTime'] = 0; // crmv@168537
				
			} else if (module == 'EventList') {
				
				url = 'index.php?module=SDK&action=SDKAjax&file=src/Events/GetEventContainer';
				params = {};
				
				callback = function(res) {
					me.renderContainer('ajaxCont', res).show();
					
					getEventList(this);
				};
				
			} else if (module == 'AllMenu') {
				
				url = 'index.php?module=Home&action=HomeAjax&file=HeaderAllMenu';
				params = {};
				
				callback = function(res) {
					if (res !== false) {
						me.renderContainer('menuCont', res);
					}
					menuCont.show();
					
					setTimeout(function() {
						AllMenuObj.initialize();
						menuCont.find('ul.tabs').tabs();
						jQuery('#menu_search_text').focus();
					}, 100);
				};
				
				options['mode'] = 'custom';
				options['size'] = '60%';
				options['direction'] = 'right';
				
				options['cache'] = true;
				options['cacheTime'] = 0; // crmv@168537
				
			} else {
				
				// Fallback per icone personalizzate fixed
				
				var onclick = options['onclick'];
				eval(onclick);
				
				me.isCustomIconClicked = true;
				
				return false;
				
			}
	
			params['fastmode'] = 1;
			
			return me.toggle(id, module, url, params, options, callback);
		},
		
	};
	
	VTE.FastDetailPanel = VTE.FastDetailPanel || jQuery.extend({}, VTE.FastPanel, {

		isChanged: false,
		
		initialize: function(options) {
			var me = this;
				
			me.containers = [];
			me.addContainer('iframeCont', 'iframe');
			
			me.replaceHistory = true;
		},
		
		toggleState: function(options) {
			var me = this;
			
			VTE.FastPanel.toggleState.apply(me, [options]);
			
			me.checkChanged();
		},
		
		checkChanged: function() {
			var me = this;
			
			if (!me.isOpen && me.isChanged) {
				var activePanel = VTE.PanelManager.getActivePanel();
				
				if (activePanel) {
					if (me.currentModule == 'CalendarDetailView') {
						var containers = activePanel.getContainersByType('iframe');
						jQuery.each(containers, function(idx, container) {
							var $container = jQuery(container);
							var contents = $container.contents();
							
							if (contents.find('.todayButton').length > 0) {
								contents.find('.todayButton .btn').click();
							}
						});
					}
				} else {
					var listView = jQuery('#listView');
					
					if (listView.length > 0) {
						callSearch('Grid', '0');
					}
				}
				
				me.isChanged = false;
			}
		},

	});
	
	VTE.FastRelatedPanel = VTE.FastRelatedPanel || jQuery.extend({}, VTE.FastPanel, {

		initialize: function(options) {
			var me = this;
				
			me.containers = [];
			me.addContainer('iframeCont', 'iframe');
			
			me.replaceHistory = true;
		},
		
	});
	
	VTE.FastPanelManager = VTE.FastPanelManager || {
		
		mainInstance: null,
		
		menuUniqueID: null,
		
		init: function() {
			var me = this;
			
			if (!amIinPopup()) {
				var fastPanel = VTE.PanelFactory.generate('fastPanel');
				fastPanel.initialize();
				me.mainInstance = fastPanel;
				
				jQuery('[data-fastpanel]').each(function() {
					jQuery(this).attr('data-id', VTE.PanelManager.createUniqueID());
				});
				
				jQuery('[data-fastpanel]').off('click').on('click', function(e) {
					e.preventDefault();
					
					if (!VTE.PanelManager.canOpenNewPanel()) return;
					
					var target = jQuery(this);
					var id = target.attr('data-id');
					var panelModule = target.attr('data-module');
					
					fastPanel.showModuleHome(id, panelModule, {
						'target': target,
						'mode': target.attr('data-fastpanel'),
						'size': target.attr('data-fastsize'),
						'onclick': target.attr('data-onclick'),
					});
				});
				
				// resize handler
				if (jQuery.throttle) {
					jQuery(window).on('resize', jQuery.throttle(500, function() {
			    		VTE.PanelManager.resize();
					}));
				}
				
				me.menuUniqueID = VTE.PanelManager.createUniqueID();
				
				VTE.FastPanelManager.Communicator.init();
			}
			
			me.initializeEvents();
			
			jQuery(document).ajaxComplete(function() {
				me.initializeEvents();
			});
		},
		
		initializeEvents: function() {
			jQuery('[data-panelview="true"]').off('click').on('click', function(e) {
				if (e.shiftKey || e.ctrlKey || e.metaKey) return;
				
				e.preventDefault();
				
				var mode = jQuery(this).attr('data-panelview-mode');
				var module = jQuery(this).attr('data-module');
				showTogglePanel(this, jQuery(this).attr('href'), module, mode);
			});
			
			if (amIinPopup()) {
				jQuery('#backToList').off('click').on('click', function(e) {
					e.preventDefault();
					
					sendMessageFromPanel({
						name: 'closePanel'
					});
				});
				
				jQuery('a[error]').off('click').on('click', function(e) {
					e.preventDefault();
					
					sendMessageFromPanel({
						name: 'closePanel'
					});
				});
				
				if (window.CalendarPopup && jQuery('#wdCalendar').length > 0) {
					CalendarPopup.oldDetailClicked = CalendarPopup.detailClicked;
					CalendarPopup.detailClicked = function() {
						var me = this,
							record = me.record;
						
						if (!record) return;
						
						hideFloatingDiv('addEvent');
						
						var url = "index.php?module=Calendar&action=DetailView&record="+record;
						
						showTogglePanel(getObj('btnDetail'), url, 'CalendarDetailView', 'detail');
					};
				}
			}
		},
		
		showMenu: function() {
			var me = this,
				mainInstance = me.mainInstance;
			
			if (!mainInstance) return;
			
			LateralMenu.isMenuOpen = true;
			
			if (!LateralMenu.isForced) {
				LateralMenu.close(null, function() {
					mainInstance.showModuleHome(me.menuUniqueID, 'AllMenu', null);
				});
			} else {
				mainInstance.showModuleHome(me.menuUniqueID, 'AllMenu', null);
			}
		},
		
	};
	
	VTE.FastPanelManager.Communicator = VTE.FastPanelManager.Communicator || {
		
		init: function() {},
		
		sendMessage: function(message) {
			var me = this;
			me.processMessage(message);
		},
		
		processMessage: function(event) {
			var me = this,
				eventData = event;
			
			var eventName = null;
			
			if (eventData && eventData.name) {
				eventName = eventData.name;
			} else {
				eventName = eventData;
			}
			
			me.processReceivedMessage(eventName, eventData);
		},
		
		processReceivedMessage: function(eventName, eventData) {
			var me = this;
			
			if (eventName === 'closePanel') {
				me.processClosePanel();
			} else if (eventName === 'beforeSaveRecord') {
				me.processBeforeSaveRecord();
			} else if (eventName === 'recordEdited') {
				me.processRecordEdited();
			} else if (eventName === 'afterSaveRecord') {
				me.processAfterSaveRecord();
			} else if (eventName === 'formAction') {
				me.processFormAction(eventData);
			} else {
				console.error('Unknown event name');
			}
		},
		
		processClosePanel: function() {
			VTE.PanelManager.closeActivePanel();
		},
		
		processFormAction: function(eventData) {
			var action = eventData.action || '';
			if (action === 'Delete') {
				VTE.PanelManager.editActivePanel();
				VTE.PanelManager.closeActivePanel();
			}
		},
		
		processBeforeSaveRecord: function() {
			VTE.PanelManager.maskActivePanel();
		},
		
		processAfterSaveRecord: function() {
			VTE.PanelManager.unmaskActivePanel();
			VTE.PanelManager.editActivePanel();
		},
		
		processRecordEdited: function() {
			VTE.PanelManager.editActivePanel();
		},
		
	};
	
	VTE.FastPanelManager.init();
	
});

/**
 * Shortcut to VTE.FastPanel.Communicator.sendMessage
 */

function sendMessageFromPanel(message) {
	var scope = window.top;
	if (scope && scope.VTE && scope.VTE.FastPanelManager && scope.VTE.FastPanelManager.Communicator) {
		scope.VTE.FastPanelManager.Communicator.sendMessage(message);
	}
}

/**
 * Shortcut to VTE.FastPanel.toggle
 */

function showTogglePanel(elem, url, module, mode, params, options) {
	var scope = window.top;
	
	var type = null;
	
	if (mode === 'detail') {
		type = 'detailPanel';
	} else if (mode === 'related') {
		type = 'relatedPanel';
	} else {
		type = 'fastPanel';
	}
	
	params = jQuery.extend({
		'hide_menus': true,
		'skip_vte_footer': true,
		'fastmode': 1,
	}, params || {});
	
	options = jQuery.extend({
		'iframe': true, 
		'direction': 'right', 
		'mode': 'custom', 
		'size': '100%',
	}, options || {});

	if (!scope.VTE.PanelManager.canOpenNewPanel()) return;

	var $elem = scope.jQuery(elem);
	var elemId = $elem.attr('data-panelid');
		
	var panel = null;
		
	if (!elemId) {
		panel = scope.VTE.PanelFactory.generate(type);
		panel.initialize();
		elemId = panel.id;
		scope.jQuery('#' + elemId).data('panelInstance', panel);
		$elem.attr('data-panelid', elemId);
	} else {
		var $panel = scope.jQuery('#' + elemId);
		panel = $panel.data('panelInstance');
	}
		
	return panel.toggle(elemId, module, url, params, options);
}