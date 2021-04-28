/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92034 */ 

/**
 * Useful class to log any JS error in the page, and log them in the server
 */
var JSLogger = {
	
	enabled: false,
	
	initialize: function() {
		var me = this;
		
		me.enabled = true;
		window.onerror = me.handleErrors.bind(me);
	},
	
	enable: function() {
		var me = this;
		me.enabled = true;
	},
	
	disable: function() {
		var me = this;
		me.enabled = false;
	},
	
	handleErrors: function(errorMsg, src, lineNumber, column, errorObj) {
		var me = this;
		
		// not enabled
		if (!me.enabled) return;
		
		// no jquery available for ajax request
		if (!window.jQuery || !jQuery.ajax) return;
		
		// ignore the cryptic "script error"
		if (!errorMsg || errorMsg.indexOf('Script error.') > -1) return;
		
		var postData = {
			error: errorMsg,
			url: window.location.href,
			source: src,
			line: lineNumber || -1,
			column: column || -1,
			trace: (errorObj && errorObj.stack ? errorObj.stack : ''),
			useragent: (window.navigator ? navigator.userAgent : ''),
		};
		
		jQuery.ajax({
			url: 'index.php?module=Utilities&action=UtilitiesAjax&file=JSLogger',
			type: 'POST',
			data: postData,
			async: true,
			// no handlers, I'm just logging silently
		});
	},
	
}

JSLogger.initialize();