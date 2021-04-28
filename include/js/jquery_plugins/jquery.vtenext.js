
// crmv@157124

(function($, window, document, undefined) {

	var pluginName = 'vtentitypreview';

	var preview_cards = {};

	var timeOpen = 800;
	var timeClose = 400;
	var timeoutOpen = null;
	var timeoutClose = null;
	var canOpen = true;

	function VTEntityPreview(element, options) {
		this.el = element;
		
		this.$el = $(element);
		
		this.options = $.extend({}, $.fn[pluginName].defaults, options);
		
		this.data = this.$el.data();
		
		this.init();
	}

	VTEntityPreview.prototype = {

		init: function() {
			var me = this;
			
			me.module = me.data['module'];
			me.record = me.data['record'];
			
			if (me.module == 'Users') return; // crmv@180994
			
			me.$el.on('mouseenter.' + pluginName, function() {
				if (canOpen) {
					timeoutOpen = setTimeout(function() {
						me._show(me.el, me.module, me.record);
					}, timeOpen);
				}
			}).on('mouseleave.' + pluginName, function() {
				canOpen = true;
				if (timeoutOpen) clearTimeout(timeoutOpen);
			});
		},

		destroy: function() {
			this.$el.off('.' + pluginName);

			this.$el.removeData();
		},
		
		_show: function(obj, module, record) {
			var me = this;
			
			if (typeof(preview_cards[record]) == 'undefined' || !preview_cards[record]) {
				preview_cards[record] = true;
				if (jQuery('#preView' + record).length == 0) {
					jQuery.ajax({
						url: 'index.php?module=Utilities&action=UtilitiesAjax&file=Card&idlist=' + record,
						success: function(data) {
							jQuery('body').append(data);
							me._showCard(obj, module, record);
						}
					});
				} else {
					me._showCard(obj, module, record);
				}
			}
		},
		
		_showCard: function(obj, module, record) {
			var me = this;
			
			jQuery('#preView' + record).hide();
			jQuery('#preView' + record).css('position', 'absolute');
			jQuery('#preView' + record).css('z-index', findZMax() + 1);
			jQuery('#preView' + record).css('top', jQuery(obj).offset().top + jQuery(obj).height());
			// check div position
			var left = jQuery(obj).offset().left + jQuery(obj).width();
			if ((left + jQuery(obj).width()) > document.body.offsetWidth)
				jQuery('#preView' + record).css('right', 0);
			else
				jQuery('#preView' + record).css('left', left);

			if (!jQuery('#preViewMask' + record).length) {
				var mask = jQuery('<div>', {
					'id': 'preViewMask' + record,
					'class': 'veil_new',
					'css': {
						'display': 'none',
						'position': 'fixed',
						'opacity': '0',
						'width': '100%',
						'z-index': (findZMax() - 1),
					},
				});

				jQuery('body').append(mask);

				jQuery('#preViewMask' + record).click(function() {
					canOpen = false;
					me._hide(obj, module, record, function () {
						canOpen = true;
					});
				});
				
				jQuery('#preView' + record).on('mouseenter.' + pluginName, function() {
					canOpen = false;
					if (timeoutClose) clearTimeout(timeoutClose);
				}).on('mouseleave.' + pluginName, function() {
					canOpen = false;
					timeoutClose = setTimeout(function() {
						me._hide(me.el, me.module, me.record, function () {
							canOpen = true;
						});
					}, timeClose);
				});
			}
				
			jQuery('#preView' + record).show('fold', {}, 500, function() {});
			jQuery('#preViewMask' + record).show();
		},
		
		_hide: function(obj, module, record, callback) {
			preview_cards[record] = false;
			jQuery('#preView' + record).hide('fold', {}, 500, function () {
				if (typeof callback == "function") callback();
			});
			jQuery('#preViewMask' + record).hide();
		},
		
		close: function() {
			if (timeoutOpen) clearTimeout(timeoutOpen);
			this._hide(this.el, this.module, this.record);
		}

	};

	$.fn[pluginName] = function(options) {
		var args = arguments;

		if (options === undefined || typeof options === 'object') {
			return this.each(function() {
				if (!$.data(this, 'plugin_' + pluginName)) {
					$.data(this, 'plugin_' + pluginName, new VTEntityPreview(this, options));
				}
			});
		} else if (typeof options === 'string' && options[0] !== '_' && options !== 'init') {
			if (Array.prototype.slice.call(args, 1).length == 0 && $.inArray(options, $.fn[pluginName].getters) != -1) {
				var instance = $.data(this[0], 'plugin_' + pluginName);
				return instance[options].apply(instance, Array.prototype.slice.call(args, 1));
			} else {
				return this.each(function() {
					var instance = $.data(this, 'plugin_' + pluginName);
					if (instance instanceof VTEntityPreview && typeof instance[options] === 'function') {
						instance[options].apply(instance, Array.prototype.slice.call(args, 1));
					}
				});
			}
		}
	};

	$.fn[pluginName].getters = [];

	$.fn[pluginName].defaults = {};

})(jQuery, window, document);
