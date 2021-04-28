/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@171581 */


window.VTE = window.VTE || {};

VTE.CSRF = VTE.CSRF || {
	
	inputName: '__csrf_token',
	token: null,
	
	/**
	 * Initialize the CSRF protection
	 */
	initialize: function(inputName, token) {
		var me = this;
		
		me.inputName = inputName;
		me.token = token;
		
		me.alterJQueryAjax();
		jQuery(document).ready(me.addTokenToForms.bind(me));
	},
	
	getCurrentToken: function() {
		return window.csrfMagicToken;
	},

	/**
	 * Add the csrf token to all forms in the page
	 * (but not future ones)
	 */
	addTokenToForms: function() {
		var me = this;
		
		var forms = document.getElementsByTagName('form');
		for (var i = 0; i < forms.length; ++i) {       
			var form = forms[i];
			if (form.method.toUpperCase() !== 'POST') continue;
			if (form.elements[me.inputName]) continue;
			var input = document.createElement('input');
			input.setAttribute('name',  me.inputName);
			input.setAttribute('value', me.token);
			input.setAttribute('type',  'hidden');
			form.append(input);
		}
	},
	
	/**
	 * Rewrite jQuery ajax to inject the csrf token
	 */
	alterJQueryAjax: function() {
		var me = this;
		
		if (window.jQuery) {
			jQuery.csrf_ajax = jQuery.ajax;
			jQuery.ajax = function(s) {
				var method = s.type || s.method;
				if (method && method.toUpperCase() == 'POST') {
					s = jQuery.extend(true, s, jQuery.extend(true, {}, jQuery.ajaxSettings, s));
					if (s.data && s.data instanceof FormData) {
						s.data.append(me.inputName, me.token);
					} else {
						if (s.data && s.processData && typeof s.data != "string") {
							s.data = jQuery.param(s.data);
						}
						// add the anti-csrf token
						if (me.inputName) {
							s.data = me.inputName + '=' + encodeURIComponent(me.token) + (s.data ? '&' + s.data : '');
						}
					}
				}
				return jQuery.csrf_ajax(s);
			}
		}
	}
	
};
