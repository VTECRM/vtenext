/* crmv@192033 */
/* 
 * Simulate prototype's selector $ to provide basic compatibility with existing code
 */

window.$ = function(sel) {
	VTE.logDeprecated('$', 'Prototype is deprecated, please use jQuery');
	if (typeof sel === 'string') {
		return document.getElementById(sel);
	}
	return sel;
}

window.Ajax = {
	
	Request: function(url, opts) {
		
		VTE.logDeprecated('Ajax.Request', 'Ajax.Request (from Prototype) is deprecated, please use jQuery.ajax');
		
		opts = opts || {};
		
		return jQuery.ajax({
			url: url,
			data: opts.postBody,
			method: opts.method || 'POST',
			async: (typeof(opts.asynchronous) !=  'undefined' ? opts.asynchronous : true),
			contentType: opts.contentType || 'application/x-www-form-urlencoded; charset=UTF-8',
			success: function(data, status, xhr) {
				if (typeof opts.onSuccess == 'function') {
					opts.onSuccess.call(this, xhr);
				}
			},
			error: function(data, status, xhr) {
				if (typeof opts.onFailure == 'function') {
					opts.onFailure.call(this, xhr);
				}
			},
			complete: function(xhr, status) {
				if (typeof opts.onComplete == 'function') {
					opts.onComplete.call(this, xhr);
				}
			},
		});
	}
}
	
