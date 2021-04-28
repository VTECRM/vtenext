/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@192033 */

window.VTE = window.VTE || {
	
	// crmv@168103
	/**
	 * Simply log a deprecated function/method
	 */
	logDeprecated: function(name, msg) {
		if (!msg) msg = 'Please, review your code and update the function call';
		console.warn('[VTE] Calling deprecated function '+name+'. '+msg);
	},
	
	/**
	 * Calls a function emitting a warning about the deprecation
	 */
	callDeprecated: function(name, fn, args, scope) {
		var me = this;
		
		if (typeof fn !== 'function') {
			console.error('[VTE] Tried to call an undefined deprecated function: '+name);
			return;
		}
		me.logDeprecated(name);
		return fn.apply(scope || me, args || []);
	},
	
	/**
	 * Return a new function wich display a deprecation message when invoked
	 * @param Callable/String fn The function to deprecate (can be a reference, or a string)
	 * @param String [msg] An optional string to display
	 */
	deprecateFn: function(fn, msg) {
		var me = this;
		
		if (typeof fn === 'function') {
			// ok, deprecate function
			var name = fn.name;
		} else if (typeof fn === 'string') {
			var name = fn;
			if (fn.indexOf('.') >= 0) {
				// go down from window to find the function instance
				var pcs = fn.split('.');
				var i = 0,
					obj = window,
					lastobj = null;
				for (var i=0; i<pcs.length; ++i) {
					obj = obj[pcs[i]];
					if (!obj) throw Error('Function '+fn+' not found');
					lastobj = obj;
				}
				fn = lastobj;
			} else {
				fn = window[fn];
			}
		} else {
			throw Error('Only functions can be deprecated');
		}
		if (!fn) throw Error('Unable to deprecate '+name);
		
		return function() {
			me.logDeprecated(name, msg)
			fn.apply(this, arguments);
		}
	},
	// crmv@168103e
	
	// crmv@147414
	
	/**
	 * @experimental
	 * Extends a simple JS object with the provided one. Inside the new methods it's then possible
	 * to use this.callParent() to call the parent method
	 *
	 */
	extend: function(object, newobject) {
		var extended = Object.create(object, Object.getOwnPropertyDescriptors(newobject));
		
		// attach the function name inside each function
		for (var prop in extended) {
			if (extended.hasOwnProperty(prop) && typeof extended[prop] === 'function') {
				extended[prop]._methodName = prop;
			}
		}
		
		if (! ('callParent' in extended)) {
			Object.defineProperty(extended, "callParent", {
				 get: function get() {
					var caller = get.caller,
						name = caller._methodName,
						found = this[name] === caller,
						proto = this;

					// search in the chain of prototypes for a matching function
					while (proto = Object.getPrototypeOf(proto)) {
						if (!proto[name]) {
							break;
						} else if (proto[name] === caller) {
							found = true;
						} else if (found) {
							return proto[name];
						}
					}

					if (!found) throw "No parent method found";
				}
			});
		}
		
		return extended;
		
	},
	
	/**
	 * @experimental
	 * Replace an existing JS object with the provided one. Inside the new methods it's possible
	 * to use this.callOverridden() to call the original method.
	 */
	override: function(object, newobject) {

		// clone the original object
		var extended = jQuery.extend({}, object);
		// override properties
		jQuery.extend(object, newobject);
		// and save the old object
		object._overridden = extended;
		
		// attach the function name inside each function
		for (var prop in object) {
			if (object.hasOwnProperty(prop) && typeof object[prop] === 'function') {
				object[prop]._methodName = prop;
			}
		}
		
		if (!object.hasOwnProperty('callOverridden')) {
			Object.defineProperty(object, "callOverridden", {
				 get: function get() {
					var caller = get.caller,
						name = caller._methodName;
					var proto = this;
					
					// find the correct proto
					// it's a kind of magic here :)
					do {
						if (proto[name] === caller) break;
					} while (proto = Object.getPrototypeOf(proto));

					if (!(name in proto._overridden)) throw "No overridden method found";
					return proto._overridden[name];
				}
			});
		}
		
		return object;
	},
	// crmv@147414e
	
	// crmv@150748 crmv@162674
	/**
	 * Replace the standard POST save with an ajax save and optionally compress the form
	 * Data param is not used yet
	 */
	submitForm: function(self, options, data, callback, callbackError) {
		var me = this;

		// set default options
		options = jQuery.extend({
			ajaxSave: true,
			showMask: true,
			compress: false,
			compressLevel: 4,
		}, options);

		// do the standard submit (doesn't support compression)
		if (!options.ajaxSave) return true;
		
		if (options.compress && !window.pako) {
			options.compress = false;
			console.warn("Pako library not found, the form won't be compressed.");
		}
		
		function processSuccess(response) {
			if (options.showMask) VteJS_DialogBox.unblock();
			// parse answer
			if (response && response.success == true) {
				if (response.redirect) {
					location.href = response.redirect;
				} else {
					if (typeof callback == 'function') callback(response);
				}
			} else if (response && response.error) {
				vtealert(alert_arr.LBL_ERROR_SAVING+': '+response.error)
				if (typeof callbackError == 'function') callbackError();
			} else {
				vtealert(alert_arr.LBL_ERROR_SAVING);
				if (typeof callbackError == 'function') callbackError();
			}
		}
		
		function processError() {
			if (options.showMask) VteJS_DialogBox.unblock();
			vtealert(alert_arr.LBL_ERROR_SAVING);
			if (typeof callbackError == 'function') callbackError();
		}
		
		
		var form = jQuery(self).closest('form');
		
		if (!options.compress) {
			// use the jquery form plugin
			if (options.showMask) VteJS_DialogBox.block();
			form.ajaxSubmit({
				data: {
					responseFormat: 'json',
				},
				dataType: 'json',
				success: processSuccess,
				error: processError,
			});
			return false;
		}
		
		// can be serialized url-like, but this has the max_input_vars limit on server side
		//var payload = form.serialize();
		
		var data = new FormData();
		
		//crmv@164181
		if(window.CKEDITOR){
			for (instance in CKEDITOR.instances) {
				CKEDITOR.instances[instance].updateElement();
			}
		}
		//crmv@164181e
		
		// so let's serialize in json
		var payload = {};
		jQuery.each(form.serializeArray(), function() {
			var name = this.name.replace("[]","");	// crmv@155087
			if (payload.hasOwnProperty(name)) {
				if (!payload[name].push) {
                    payload[name] = [payload[name]];
                }
                payload[name].push(this.value || '');
			} else {
				payload[name] = this.value || '';
			}
		});
		payload = JSON.stringify(payload);
		
		// now add files
		form.find('input[type=file]').each(function() {
			if (this.files && this.files.length > 0) {
				var name = this.name.replace("[]","");
				for (var i=0; i<this.files.length; ++i) {
					data.append(name+(this.files.length > 1 ? '[]' : ''), this.files[i], this.files[i].name);
				}
			}
		});
		
		// compress
		payload = pako.gzip(payload, {
			level: options.compressLevel
		});
		// convert to binary blob
		payload = new Blob([payload], {type: 'application/octet-stream'});
		
		data.append('compressedData', 'true');
		data.append('compressFormat', 'gzip');
		data.append('serializeFormat', 'json'); // "serialize" or "json"
		data.append('responseFormat', 'json');
		data.append('payload', payload, 'payload'); // uploaded as file
		
		// these are not needed, but simplify debugging
		if (form[0].module) {
			data.append('module', form[0].module.value);
		}
		if (form[0].action) {
			data.append('action', form[0].action.value);
		}
		if (form[0].record) {
			data.append('record', form[0].record.value);
		}
		
		if (options.showMask) VteJS_DialogBox.block();
		jQuery.ajax({
			url: form.attr('action'),
			method: 'POST',
			data: data,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: processSuccess,
			error: processError,
		});
		
		// abort the standard submit
		return false;
	},
	
	/**
	 * Shortcut to VTE.submitForm with compression enabled
	 * Data param is not used yet
	 */
	submitCompressedForm: function(self, options, data) {
		options = jQuery.extend(options || {}, {
			compress: true
		});
		return this.submitForm(self, options);
	},
	// crmv@150748e crmv@162674e
	
	/**
	 * Generate a standard modal by passing the id of the element, the title and the content
	 */
	showModal: function(divid, title, contents, options) {
		// set default options
		options = jQuery.extend({}, {
			position: '', 						// '' as default, top, left, bottom, right
			width: null,
			height: null,
			large: false,
			backdrop: true,						// boolean or the string "static", true - dark overlay, false - no overlay (transparent), If you specify the value "static", it is not possible to close the modal when clicking outside of it 
			keyboard: false,					// boolean, true - the modal can be closed with Esc, false - the modal cannot be closed with Esc
			events: {							// modal default events
				'show.bs.modal': null,
				'shown.bs.modal': null,
				'hide.bs.modal': null,
				'hidden.bs.modal': null,
			},
			showHeader: (title && title.length > 0),
			showFooter: false,
			buttons: null, 						// [{ id: '...', cls: 'btn-primary', dismissable: false, value: '...', handler: function() { ... } }]
			maxHeight: null, // crmv@166522
			transparent: false, // crmv@170412
		}, options || {});
		
		var $el = jQuery('#'+divid);
		if ($el.length <= 0) return;
		
		var el = $el.get(0);
		
		if (!jQuery.fn.modal) {
			console.warn("Modal plugin not found.");
			return false;
		}
		
		$el.off(); // turn off all events
		
		var opts = {
			backdrop: options.backdrop,
			keyboard: options.keyboard,
		};
		
		$el.addClass('modal').addClass('fade');
		$el.removeClass('top right bottom left');
		
		if (options.position && options.position.length > 0) {
			$el.addClass(options.position);
		}
		
		var dialogClass = 'modal-dialog' + (options.large ? ' modal-lg' : '');
		var html = '<div class="' + dialogClass + '"><div class="modal-content">';
		
		if (options.showHeader) {
			html += '<div class="modal-header">';
			html += '<button type="button" class="close" data-dismiss="modal">&times;</button>';
			if (title && title.length > 0) {
				html += '<h4 class="modal-title">'+title+'</h4>';
			}
			html += '</div>';
		}
		
		html += '<div class="modal-body">';
		if (contents && contents.length > 0) html += contents;
		html += '</div>';
		
		var buttons = [];
		
		if (options.showFooter) {
			html += '<div class="modal-footer">';
			
			if (options.buttons && options.buttons.length) {
				jQuery.each(options.buttons, function(k, b) {
					var $button = jQuery('<button>', {
						'id': b.id,
						'class': 'btn' + (b.cls.length > 0 ? ' '+b.cls : ''),
						'html': b.value,
					});
					
					if (b.handler && typeof b.handler === 'function') {
						$button.on('click', b.handler);
					}
					
					if (b.dismissable) {
						$button.attr('data-dismiss', 'modal');
					}
					
					buttons.push($button);
				});
			}
		}
		
		html += '</div></div>';
		
		$el.html(html);
		
		if (options.width) {
			$el.find('.modal-dialog').css('width', options.width);
		}
		
		if (options.height) {
			$el.find('.modal-dialog').css('height', options.height);
		}
		
		// crmv@166522
		if (options.maxHeight && options.maxHeight > 0) {
			$el.find('.modal-content').css('overflow', 'hidden');
			$el.find('.modal-body').css({'max-height': options.maxHeight + 'px', 'overflow-y': 'auto'});
		}
		// crmv@166522e

		// crmv@170412
		if (options.transparent) {
			$el.find('.modal-content').addClass('modal-content-transparent');
		}
		// crmv@170412e
		
		if (buttons.length > 0) {
			jQuery.each(buttons, function(k, b) {
				$el.find('.modal-footer').append(b);
			});
		}
		
		if (options.events && typeof options.events['show.bs.modal'] === 'function') {
			$el.on('show.bs.modal', options.events['show.bs.modal']);
		}
		
		if (options.events && typeof options.events['shown.bs.modal'] === 'function') {
			$el.on('shown.bs.modal', options.events['shown.bs.modal']);
		}
		
		if (options.events && typeof options.events['hide.bs.modal'] === 'function') {
			$el.on('hide.bs.modal', options.events['hide.bs.modal']);
		}
		
		if (options.events && typeof options.events['hidden.bs.modal'] === 'function') {
			$el.on('hidden.bs.modal', options.events['hidden.bs.modal']);
		}
		
		return $el.modal(opts).modal('show');
	},

	/**
	 * Hide a modal by passing the id of the element
	 */
	hideModal: function(divid) {
		var $el = jQuery('#'+divid);
		if ($el.length <= 0) return;
		
		$el.modal('hide');
	},
	
	// crmv@171115
	
	/**
	 * Alias for Blockage.registerPanelBlocker
	 * Registers a new panel blocker
	 * @param {Array} selectors The selectors argument contains the inputs that are used to detect the changes
	 * @param {Object} options The options argument is not used yet
	 */
	registerPanelBlocker: function(name, selectors, options) {
		var me = this;
		
		var panelBlocker = Blockage.registerPanelBlocker(name, selectors, options);
		me.currentPanelBlocker = panelBlocker;
	},
	
	/**
	 * Alias for Blockage.checkPanelBlocker
	 * Checks if the panel blockers registered are edited and asks for exiting
	 * @param {Function} callback The function is called after the dialog confirm otherwise nothing happens
	 */
	checkPanelBlocker: function(callback) {
		var me = this;
		
		Blockage.checkPanelBlocker(callback);
	},
	
	/**
	 * Clears and turns off the current panel blocker
	 * @param {Object} options Options for the release:
	 * @param {Boolean} [options.restart=false] If true, panel blocker restart listening
	 */
	releasePanelBlocker: function(options) {
		var me = this;
		
		Blockage.releasePanelBlocker(me.currentPanelBlocker, options);
	},
	
	// crmv@171115e

	showLoader: function(loader) {
		if (jQuery.isPlainObject(loader)) {
			if (loader.showLoader && typeof loader.showLoader === 'function') {
				loader.showLoader();
			}
		} else {
			jQuery('#status').show();
		}
	},
	
	hideLoader: function(loader) {
		if (jQuery.isPlainObject(loader)) {
			if (loader.hideLoader && typeof loader.hideLoader === 'function') {
				loader.hideLoader();
			}
		} else {
			jQuery('#status').hide();
		}
	},
	
	ajaxCall: function(url, params, options, success, failure) {
		var me = window.VTE,
			mtime = (new Date()).getTime();
		
		params = params || {};
		options = options || {};
		
		// default options
		options = jQuery.extend({}, {
			showLoader: true,
			beSilent: true,		// don't show any message box to the user
			rawResponse: false,	// return the raw result
			ajaxOptions: {},	// options for the ajax request
		}, options || {});
		
		var paramsString = null;
		
		if (jQuery.isPlainObject(params)) {
			paramsString = jQuery.param(params);
		} else {
			paramsString = params;
		}
		
		paramsString += '&mtime=' + mtime;
		
		var showLoader = options.showLoader,
			beSilent = options.beSilent;
		
		if (showLoader) me.showLoader(showLoader);
		
		var ajaxParams = {
			url: url,
			method: 'GET',
			data: paramsString,
			success: function(res) {
				if (showLoader) me.hideLoader(showLoader);
				if (res) {
					if (options.rawResponse) {
						if (typeof success == 'function') success(res);
						return;
					}
					try {
						var data = JSON.parse(res);
						if (data.success) {
							if (typeof success == 'function') success(data.result);
						} else {
							console.log('Error in retrieving data from server: ' + data.error);
							if (typeof failure == 'function') failure('RESPONSE_ERROR', data);
						}
					} catch(e) {
						console.log(e);
						console.log('Invalid data returned from server: ' + res);
						if (!beSilent) alert('Invalid data returned from server');
						if (typeof failure == 'function') failure('JSON_FAIL', res.responseText, res);
					}
				} else {
					console.log('Invalid data returned from server: ' + res);
					if (typeof failure == 'function') failure('INVALID_DATA', res.responseText, res);
				}
			},
			error: function(xhr) {
				if (showLoader) me.hideLoader();
				console.log('Ajax error');
				if (!beSilent) alert('Ajax error');
				if (typeof failure == 'function') failure('AJAX_FAIL', null, xhr);
			},
		};
		
		return jQuery.ajax(jQuery.extend({}, ajaxParams, options.ajaxOptions || {}));
	},
	
};

// crmv@181170
VTE.EditView = VTE.EditView || {
	
	initializeDisplayFields: function() {
		jQuery(".dvtCellInfo, .dvtCellInfoM").each(function(i, o) {
			if (!jQuery(o).data('dvtCellInfo')) {
				var currentClass = jQuery(o).attr('class');
				var classes = currentClass.split(' ');
				var count = classes.length;
				jQuery(o).focusin(function() {
					if (count == 1) {
						jQuery(o).attr('class', currentClass + 'On');
					} else {
						for (var i = 0; i < count; i++) {
							if (classes[i] == 'dvtCellInfo') classes[i] = 'dvtCellInfoOn';
						}
						jQuery(o).attr('class', classes.join(' '));
					}
				}).focusout(function() {
					if (count == 1) {
						jQuery(o).attr('class', currentClass);
					} else {
						for (var i = 0; i < count; i++) {
							if (classes[i] == 'dvtCellInfoOn') classes[i] = 'dvtCellInfo';
						}
						jQuery(o).attr('class', classes.join(' '));
					}
				});
				jQuery(o).data('dvtCellInfo', true);
			}
		});
	},
	
};
// crmv@181170e

//crmv@3560m
if (Function.prototype.name === undefined && Object.defineProperty !== undefined) {
	Object.defineProperty(Function.prototype, 'name', {
		get: function() {
			var funcNameRegex = /function\s([^(]{1,})\(/;
			var results = (funcNameRegex).exec((this).toString());
			return (results && results.length > 1) ? results[1].trim() : "";
		},
		set: function(value) {}
	});
			
}
//crmv@3560me

//Utility Functions

// crmv@82831 crmv@99315
if (!window.vtealert) {
	window.vtealert = function(text, cb) {
		alert(text);
		if (typeof cb === 'function') cb();
	}
}

if (!window.vteconfirm) {
	window.vteconfirm = function(text, cb) {
		var r = confirm(text);
		if (typeof cb === 'function') cb(r);
		return r;
	}
}
// crmv@82831e crmv@99315e

//crmv@42024
// check if a valid override exists
function checkJSOverride(fargs) {
	var oname = fargs.callee.name + '_override';
	return (typeof window[oname] === 'function' && window[oname].apply);
}

// call the override, provided its existance
function callJSOverride(fargs) {
	var oname = fargs.callee.name + '_override';
	return window[oname].apply(this, fargs);
}

// crmv@65067
// check if a valid extension exists
function checkJSExtension(fargs) {
	var oname = fargs.callee.name + '_extension';
	return (typeof window[oname] === 'function' && window[oname].apply);
}

// call the extension, provided its existance
function callJSExtension(fargs) {
	var oname = fargs.callee.name + '_extension';
	return window[oname].apply(this, fargs);
}
// crmv@65067e

// called at the beginning, to set global JS variables
// pass an object with name:value or a json encoded object
function setGlobalVars(variables) {
	if (checkJSOverride(arguments)) return callJSOverride(arguments);
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	if (!variables) return;

	if (typeof variables == 'string') {
		try {
			variables = JSON.parse(variables);
		} catch (e) {
			return;
		}
	}

	// SET GLOBAL VARS
	for (k in variables) {
		if (variables.hasOwnProperty(k)) {
			window[k] = variables[k];
		}
	}
}
//crmv@42024e

//crmv@43864 - check if the module is one of the inventory
function isInventoryModule(module) {
	return (window['inventory_modules'] && window.inventory_modules.indexOf(module) > -1);
}
//crmv@43864e

//crmv@29463
function c_toggleAssignType(currType){
	if (currType=="U")
	{
		document.getElementById("c_assign_user").style.display="block";
		document.getElementById("c_assign_team").style.display="none";
	}
	else
	{
		document.getElementById("c_assign_user").style.display="none";
		document.getElementById("c_assign_team").style.display="block";
	}
}
//crmv@29463e

var gValidationCall='';

if (document.all)

    var browser_ie=true

else if (document.layers)

    var browser_nn4=true

else if (document.layers || (!document.all && document.getElementById))

    var browser_nn6=true

var gBrowserAgent = navigator.userAgent.toLowerCase();

function hideSelect()
{
        var oselect_array = document.getElementsByTagName('SELECT');
        for(var i=0;i<oselect_array.length;i++)
        {
                oselect_array[i].style.display = 'none';
        }
}

function showSelect()
{
        var oselect_array = document.getElementsByTagName('SELECT');
        for(var i=0;i<oselect_array.length;i++)
        {
                oselect_array[i].style.display = 'block';
        }
}

function getObj(n,d) {

	var p,i,x;

	if(!d) {
		d=document;
	}

	if(n != undefined) {
		// crmv@21048m
		if((p=n.indexOf("?"))>0&&parent.frames.length) {
			d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);
		}
		// crmv@21048me
	}

	if(d.getElementById) {
		x=d.getElementById(n);
		// IE7 was returning form element with name = n (if there was multiple instance)
		// But not firefox, so we are making a double check
		if(x && x.id != n) x = false;
	}

	for(i=0;!x && i<d.forms.length;i++) {
		x=d.forms[i][n];
	}

	for(i=0; !x && d.layers && i<d.layers.length;i++) {
		x=getObj(n,d.layers[i].document);
	}

	if(!x && !(x=d[n]) && d.all) {
		x=d.all[n];
	}

	if(typeof x == 'string') {
		x=null;
	}

	if(x == null && !n.match(/\[\]$/)){
		x=getObj(n+'[]',d);
	}

	return x;
}

function getOpenerObj(n) {
	//crmv@21048m
    return getObj(n,parent.document)
	//crmv@21048m e
}


function findPosX(obj) {
    var curleft = 0;

    if (document.getElementById || document.all) {
        while (obj.offsetParent) {
            curleft += obj.offsetLeft;
            obj = obj.offsetParent;
        }
	} else if (document.layers) {
        curleft += obj.x;
    }

    return curleft;
}


// crmv@116907
function findPosY(obj) {
    var curtop = 0;

    if (document.getElementById || document.all) {
		var subscroll = true;
        while (obj.offsetParent) {
            curtop += obj.offsetTop;
            obj = obj.offsetParent;
			if (obj && obj.style && obj.style.position == 'fixed') subscroll = false;
        }
        if (subscroll) {
			curtop -= jQuery(document).scrollTop();
		}
    } else if (document.layers) {
        curtop += obj.y;
    }

    return curtop;
}
// crmv@116907e


function clearTextSelection() {

    if (browser_ie) document.selection.empty();

    else if (browser_nn4 || browser_nn6) window.getSelection().removeAllRanges();

}

// Setting cookies
function set_cookie ( name, value, exp_y, exp_m, exp_d, path, domain, secure )
{
  var cookie_string = name + "=" + escape ( value );

  if (exp_y) //delete_cookie(name)
  {
    var expires = new Date ( exp_y, exp_m, exp_d );
    cookie_string += "; expires=" + expires.toGMTString();
  }

  if (path) cookie_string += "; path=" + escape ( path );
  if (domain) cookie_string += "; domain=" + escape ( domain );
  if (secure) cookie_string += "; secure";

  document.cookie = cookie_string;
}

// Retrieving cookies
function get_cookie(cookie_name)
{
  var results = document.cookie.match(cookie_name + '=(.*?)(;|$)');
  if (results) return (unescape(results[1]));
  else return null;
}

// Delete cookies
function delete_cookie( cookie_name )
{
  var cookie_date = new Date ( );  // current date & time
  cookie_date.setTime ( cookie_date.getTime() - 1 );
  document.cookie = cookie_name += "=; expires=" + cookie_date.toGMTString();
}
//End of Utility Functions

function emptyCheck(fldName,fldLabel, fldType) {
    var currObj=getObj(fldName)
    if (fldType=="text") {
		if (currObj.value.replace(/^\s+/g, '').replace(/\s+$/g, '').length==0) {
			alert(sprintf(alert_arr.CANNOT_BE_EMPTY, fldLabel), function(){
				try {
					currObj.focus()
				} catch(error) {
					// Fix for IE: If element or its wrapper around it is hidden, setting focus will fail
					// So using the try { } catch(error) { }
				}
			});
           	return false
        }
        else
        	return true
    }
    //crmv@add checkbox
    else if (fldType=="checkbox") {
        if (currObj.checked == false) {
        	alert(fldLabel+alert_arr.MUST_BE_CHECKED, function(){
            	currObj.focus()
            });
            return false
        } else
        	return true
    }
    //crmv@add checkbox end
	//crmv@10621
    else if((fldType == "textarea") && (typeof(CKEDITOR)!=='undefined' && typeof(CKEDITOR.instances[fldName]) !== 'undefined')) {
		var textObj = CKEDITOR.instances[fldName];
		var textValue = textObj.getData();
		if (trim(textValue) == '' || trim(textValue) == '<br>') {
		   	alert(sprintf(alert_arr.CANNOT_BE_NONE, fldLabel));
			return false;
		} else
        	return true;
	}
	//crmv@45428
	else if (trim(currObj.value) == '' && isdefined('assigned_group_id') && fldName == 'assigned_user_id'){
		var currObj=getObj('assigned_group_id')
		if (trim(currObj.value) == '') {
			alert(sprintf(alert_arr.CANNOT_BE_NONE, fldLabel));
    		return false;
  		} else
			return true;
	}	
	//crmv@45428e
	else{
		if (trim(currObj.value) == '') {
			alert(sprintf(alert_arr.CANNOT_BE_NONE, fldLabel), function(){
            	currObj.focus();
            });
    		return false;
  		} else
			return true;
	}
	//crmv@10621 e
}

function patternValidate(fldName,fldLabel,type) {
    var currObj=getObj(fldName);
    if (type.toUpperCase()=="YAHOO") //Email ID validation
    {
        //yahoo Id validation
        var re=new RegExp(/^[a-z0-9]([a-z0-9_\-\.]*)@([y][a][h][o][o])(\.[a-z]{2,3}(\.[a-z]{2}){0,2})$/);
    }
    if (type.toUpperCase()=="EMAIL") //Email ID validation
    {
		// crmv@161924 - implements RFC 5322 (sections 3.2.3 and 3.4.1) and RFC 5321
		var re=new RegExp(/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/);
		// crmv@161924e
    }

    if (type.toUpperCase()=="DATE") {//DATE validation
		//YMD
		//var reg1 = /^\d{2}(\-|\/|\.)\d{1,2}\1\d{1,2}$/ //2 digit year
		//var re = /^\d{4}(\-|\/|\.)\d{1,2}\1\d{1,2}$/ //4 digit year

		//MYD
		//var reg1 = /^\d{1,2}(\-|\/|\.)\d{2}\1\d{1,2}$/
		//var reg2 = /^\d{1,2}(\-|\/|\.)\d{4}\1\d{1,2}$/

	   //DMY
		//var reg1 = /^\d{1,2}(\-|\/|\.)\d{1,2}\1\d{2}$/
		//var reg2 = /^\d{1,2}(\-|\/|\.)\d{1,2}\1\d{4}$/

		switch (userDateFormat) {
			case "yyyy-mm-dd" :
								var re = /^\d{4}(\-|\/|\.)\d{1,2}\1\d{1,2}$/
								break;
			case "mm-dd-yyyy" :
			case "dd-mm-yyyy" :
								var re = /^\d{1,2}(\-|\/|\.)\d{1,2}\1\d{4}$/
		}
	}

	if (type.toUpperCase()=="TIME") {//TIME validation
		var re = /^\d{1,2}\:\d{1,2}$/
	}
	//Asha: Remove spaces on either side of a Email id before validating
	if (type.toUpperCase()=="EMAIL" || type.toUpperCase() == "DATE") currObj.value = trim(currObj.value);
	if (!re.test(currObj.value)) {
		alert(alert_arr.ENTER_VALID + fldLabel  + " ("+type+")", function(){
			try {
				currObj.focus()
			} catch(error) {
				// Fix for IE: If element or its wrapper around it is hidden, setting focus will fail
				// So using the try { } catch(error) { }
			}
		});
		return false
	}
	else return true
}

function splitDateVal(dateval) {
	var datesep;
	var dateelements = new Array(3);

	if (dateval.indexOf("-")>=0) datesep="-"
	else if (dateval.indexOf(".")>=0) datesep="."
	else if (dateval.indexOf("/")>=0) datesep="/"
	//crmv@add some cases
	switch (userDateFormat) {
		case "yyyy-mm-dd" :
		case "yyyy.mm.dd" :
		case "yyyy/mm/dd" :
							dateelements[0]=dateval.substr(dateval.lastIndexOf(datesep)+1,dateval.length) //dd
							dateelements[1]=dateval.substring(dateval.indexOf(datesep)+1,dateval.lastIndexOf(datesep)) //mm
							dateelements[2]=dateval.substring(0,dateval.indexOf(datesep)) //yyyyy
							break;
		case "mm-dd-yyyy" :
		case "mm.dd.yyyy" :
		case "mm/dd/yyyy" :
							dateelements[0]=dateval.substring(dateval.indexOf(datesep)+1,dateval.lastIndexOf(datesep))
							dateelements[1]=dateval.substring(0,dateval.indexOf(datesep))
							dateelements[2]=dateval.substr(dateval.lastIndexOf(datesep)+1,dateval.length)
							break;
		case "dd-mm-yyyy" :
		case "dd.mm.yyyy" :
		case "dd/mm/yyyy" :
							dateelements[0]=dateval.substring(0,dateval.indexOf(datesep))
							dateelements[1]=dateval.substring(dateval.indexOf(datesep)+1,dateval.lastIndexOf(datesep))
							dateelements[2]=dateval.substr(dateval.lastIndexOf(datesep)+1,dateval.length)
	}
	//crmv@add some cases end
	return dateelements;
}

function compareDates(date1,fldLabel1,date2,fldLabel2,type) {
    var ret=true
	//crmv@59091
    switch (type) {
		case 'L'    : if (date1>=date2) {//DATE1 VALUE LESS THAN DATE2
						var err = sprintf(alert_arr.DATE_SHOULDBE_LESS, fldLabel1, fldLabel2);
						alert(err);
						ret=false;
					}
                    break;
        case 'LE'   : if (date1>date2) {//DATE1 VALUE LESS THAN OR EQUAL TO DATE2
						var err = sprintf(alert_arr.DATE_SHOULDBE_LESS_EQUAL, fldLabel1, fldLabel2);
						alert(err);
						ret=false;
                    }
                    break;
        case 'E'    : if (date1!=date2) {//DATE1 VALUE EQUAL TO DATE
						var err = sprintf(alert_arr.DATE_SHOULDBE_EQUAL, fldLabel1, fldLabel2);
						alert(err);
						ret=false;
                    }
                    break;
        case 'G'    : if (date1<=date2) {//DATE1 VALUE GREATER THAN DATE2
						var err = sprintf(alert_arr.DATE_SHOULDBE_GREATER, fldLabel1, fldLabel2);
						alert(err);
						ret=false;
                    }
                    break;
        case 'GE'    : if (date1<date2) {//DATE1 VALUE GREATER THAN OR EQUAL TO DATE2
						var err = sprintf(alert_arr.DATE_SHOULDBE_GREATER_EQUAL, fldLabel1, fldLabel2);
						alert(err);
						ret=false;
                    }
                    break;
    }
	//crmv@59091e
    if (ret==false) return false
    else return true
}

function dateTimeValidate(dateFldName,timeFldName,fldLabel,type) {
	if(patternValidate(dateFldName,fldLabel,"DATE")==false)
		return false;
	dateval=getObj(dateFldName).value.replace(/^\s+/g, '').replace(/\s+$/g, '')

	var dateelements=splitDateVal(dateval)

	dd=dateelements[0]
	mm=dateelements[1]
	yyyy=dateelements[2]

	if (dd<1 || dd>31 || mm<1 || mm>12 || yyyy<1 || yyyy<1000) {
		alert(alert_arr.ENTER_VALID+fldLabel, function(){
			try { getObj(dateFldName).focus() } catch(error) { }
		});
		return false
	}

	if ((mm==2) && (dd>29)) {//checking of no. of days in february month
		alert(alert_arr.ENTER_VALID+fldLabel, function(){
			try { getObj(dateFldName).focus() } catch(error) { }
		});
		return false
	}

	if ((mm==2) && (dd>28) && ((yyyy%4)!=0)) {//leap year checking
		alert(alert_arr.ENTER_VALID+fldLabel, function(){
			try { getObj(dateFldName).focus() } catch(error) { }
		});
		return false
	}

    switch (parseInt(mm)) {
        case 2 :
        case 4 :
        case 6 :
        case 9 :
        case 11 :	if (dd>30) {
						alert(alert_arr.ENTER_VALID+fldLabel, function(){
							try { getObj(dateFldName).focus() } catch(error) { }
						});
						return false
					}
    }

    if (patternValidate(timeFldName,fldLabel,"TIME")==false)
        return false

    var timeval=getObj(timeFldName).value.replace(/^\s+/g, '').replace(/\s+$/g, '')
    var hourval=parseInt(timeval.substring(0,timeval.indexOf(":")))
    var minval=parseInt(timeval.substring(timeval.indexOf(":")+1,timeval.length))
    var currObj=getObj(timeFldName)

	if (hourval>23 || minval>59) {
		alert(alert_arr.ENTER_VALID+fldLabel, function(){
			try { currObj.focus() } catch(error) { }
		});
		return false
	}

    var currdate=new Date()
    var chkdate=new Date()

    chkdate.setYear(yyyy)
    chkdate.setMonth(mm-1)
    chkdate.setDate(dd)
    chkdate.setHours(hourval)
    chkdate.setMinutes(minval)

	if (type!="OTH") {
		if (!compareDates(chkdate,fldLabel,currdate,"current date & time",type)) {
			try { getObj(dateFldName).focus() } catch(error) { }
			return false
		} else return true;
	} else return true;
}

function dateTimeComparison(dateFldName1,timeFldName1,fldLabel1,dateFldName2,timeFldName2,fldLabel2,type) {
    var dateval1=getObj(dateFldName1).value.replace(/^\s+/g, '').replace(/\s+$/g, '')
    var dateval2=getObj(dateFldName2).value.replace(/^\s+/g, '').replace(/\s+$/g, '')

    var dateelements1=splitDateVal(dateval1)
    var dateelements2=splitDateVal(dateval2)

    dd1=dateelements1[0]
    mm1=dateelements1[1]
    yyyy1=dateelements1[2]

    dd2=dateelements2[0]
    mm2=dateelements2[1]
    yyyy2=dateelements2[2]

    var timeval1=getObj(timeFldName1).value.replace(/^\s+/g, '').replace(/\s+$/g, '')
    var timeval2=getObj(timeFldName2).value.replace(/^\s+/g, '').replace(/\s+$/g, '')

    var hh1=timeval1.substring(0,timeval1.indexOf(":"))
    var min1=timeval1.substring(timeval1.indexOf(":")+1,timeval1.length)

    var hh2=timeval2.substring(0,timeval2.indexOf(":"))
    var min2=timeval2.substring(timeval2.indexOf(":")+1,timeval2.length)

    var date1=new Date()
    var date2=new Date()

    date1.setYear(yyyy1)
    date1.setMonth(mm1-1)
    date1.setDate(dd1)
    date1.setHours(hh1)
    date1.setMinutes(min1)

    date2.setYear(yyyy2)
    date2.setMonth(mm2-1)
    date2.setDate(dd2)
    date2.setHours(hh2)
    date2.setMinutes(min2)

	if (type!="OTH") {
		if (!compareDates(date1,fldLabel1,date2,fldLabel2,type)) {
			try { getObj(dateFldName1).focus() } catch(error) { }
			return false
		} else return true;
	} else return true;
}

function dateValidate(fldName,fldLabel,type) {
    if(patternValidate(fldName,fldLabel,"DATE")==false)
        return false;
    dateval=getObj(fldName).value.replace(/^\s+/g, '').replace(/\s+$/g, '')

    var dateelements=splitDateVal(dateval)

    dd=dateelements[0]
    mm=dateelements[1]
    yyyy=dateelements[2]

	if (dd<1 || dd>31 || mm<1 || mm>12 || yyyy<1 || yyyy<1000) {
		alert(alert_arr.ENTER_VALID+fldLabel, function(){
			try { getObj(fldName).focus() } catch(error) { }
		});
		return false
	}

	if ((mm==2) && (dd>29)) {//checking of no. of days in february month
		alert(alert_arr.ENTER_VALID+fldLabel, function(){
			try { getObj(fldName).focus() } catch(error) { }
		});
		return false
	}

	if ((mm==2) && (dd>28) && ((yyyy%4)!=0)) {//leap year checking
		alert(alert_arr.ENTER_VALID+fldLabel, function(){
			try { getObj(fldName).focus() } catch(error) { }
		});
		return false
	}

    switch (parseInt(mm)) {
        case 2 :
        case 4 :
        case 6 :
        case 9 :
		case 11 :	if (dd>30) {
						alert(alert_arr.ENTER_VALID+fldLabel, function(){
							try { getObj(fldName).focus() } catch(error) { }
						});
						return false
					}
    }

    var currdate=new Date()
    var chkdate=new Date()

    chkdate.setYear(yyyy)
    chkdate.setMonth(mm-1)
    chkdate.setDate(dd)

	if (type!="OTH") {
		if (!compareDates(chkdate,fldLabel,currdate,"current date",type)) {
			try { getObj(fldName).focus() } catch(error) { }
			return false
		} else return true;
	} else return true;
}

//crmv@166700
function dateComparison(fldName1,fldLabel1,fldName2,fldLabel2,type) {
	if (fldName1 == 'now') {
		var date1=new Date();
	} else {
		var dateval1=getObj(fldName1).value.replace(/^\s+/g, '').replace(/\s+$/g, '');
		var dateelements1=splitDateVal(dateval1);
		
		dd1=dateelements1[0];
		mm1=dateelements1[1];
		yyyy1=dateelements1[2];

		var date1=new Date(yyyy1,mm1-1,dd1);
	}
	
	if (fldName2 == 'now') {
		var date2=new Date()
	} else {
		var dateval2=getObj(fldName2).value.replace(/^\s+/g, '').replace(/\s+$/g, '');
		var dateelements2=splitDateVal(dateval2);
		
		dd2=dateelements2[0];
		mm2=dateelements2[1];
		yyyy2=dateelements2[2];
		
		var date2=new Date(yyyy2,mm2-1,dd2);
	}

	if (type!="OTH") {
		if (!compareDates(date1,fldLabel1,date2,fldLabel2,type)) {
			try { getObj(fldName1).focus(); } catch(error) { }
			return false;
		} else return true;
	} else return true;
}
//crmv@166700e

function timeValidate(fldName,fldLabel,type) {
    if (patternValidate(fldName,fldLabel,"TIME")==false)
        return false

    var timeval=getObj(fldName).value.replace(/^\s+/g, '').replace(/\s+$/g, '')
    var hourval=parseInt(timeval.substring(0,timeval.indexOf(":")))
    var minval=parseInt(timeval.substring(timeval.indexOf(":")+1,timeval.length))
    var currObj=getObj(fldName)

	if (hourval>23 || minval>59) {
		alert(alert_arr.ENTER_VALID+fldLabel, function(){
			try { currObj.focus() } catch(error) { }
		});
		return false
	}

    var currtime=new Date()
    var chktime=new Date()

    chktime.setHours(hourval)
    chktime.setMinutes(minval)

	if (type!="OTH") {
		if (!compareDates(chktime,fldLabel,currtime,"current time",type)) {
			try { getObj(fldName).focus() } catch(error) { }
			return false
		} else return true;
	} else return true
}

function timeComparison(fldName1,fldLabel1,fldName2,fldLabel2,type) {
    var timeval1=getObj(fldName1).value.replace(/^\s+/g, '').replace(/\s+$/g, '')
    var timeval2=getObj(fldName2).value.replace(/^\s+/g, '').replace(/\s+$/g, '')

    var hh1=timeval1.substring(0,timeval1.indexOf(":"))
    var min1=timeval1.substring(timeval1.indexOf(":")+1,timeval1.length)

    var hh2=timeval2.substring(0,timeval2.indexOf(":"))
    var min2=timeval2.substring(timeval2.indexOf(":")+1,timeval2.length)

    var time1=new Date()
    var time2=new Date()

    //added to fix the ticket #5028
    if(fldName1 == "time_end" && (getObj("due_date") && getObj("date_start")))
    {
        var due_date=getObj("due_date").value.replace(/^\s+/g, '').replace(/\s+$/g, '')
        var start_date=getObj("date_start").value.replace(/^\s+/g, '').replace(/\s+$/g, '')
        dateval1 = splitDateVal(due_date);
        dateval2 = splitDateVal(start_date);

        dd1 = dateval1[0];
        mm1 = dateval1[1];
        yyyy1 = dateval1[2];

        dd2 = dateval2[0];
        mm2 = dateval2[1];
        yyyy2 = dateval2[2];

        time1.setYear(yyyy1)
        time1.setMonth(mm1-1)
        time1.setDate(dd1)

        time2.setYear(yyyy2)
        time2.setMonth(mm2-1)
        time2.setDate(dd2)

    }
    //end

    time1.setHours(hh1)
    time1.setMinutes(min1)

    time2.setHours(hh2)
    time2.setMinutes(min2)
	if (type!="OTH") {
		if (!compareDates(time1,fldLabel1,time2,fldLabel2,type)) {
			try { getObj(fldName1).focus() } catch(error) { }
			return false
		} else return true;
	} else return true;
}


// crmv@100905
function numValidate(fldName,fldLabel,format,neg, uitype, val) { // crmv@83877
	if (typeof val == 'undefined') {
		val = getObj(fldName).value.replace(/^\s+/g, '').replace(/\s+$/g, '');
	}
	// crmv@100905e
   
	// crmv@83877
	uitype = parseInt(uitype);
	if (uitype == 7 || uitype == 9 || uitype == 71 || uitype == 72) { // crmv@92112
		if (!validateUserNumber(val)) {
			alert(alert_arr.INVALID+fldLabel, function(){
				try { 
					getObj(fldName).focus(); 
				} catch (error) { }
			});
			return false;
		}
		//convert to the float format for the standard check
		val = parseUserNumber(val) + "";
	}
	// crmv@83877e
   
   if (format!="any") {
       if (isNaN(val)) {
           var invalid=true
       } else {
           var format=format.split(",")
           var splitval=val.split(".")
           if (neg==true) {
               if (splitval[0].indexOf("-")>=0) {
                   if (splitval[0].length-1>format[0])
                       invalid=true
               } else {
                   if (splitval[0].length>format[0])
                       invalid=true
               }
           } else {
               if (val<0)
                   invalid=true
           else if (format[0]==2 && splitval[0]==100 && (!splitval[1] || splitval[1]==0))
           invalid=false
               else if (splitval[0].length>format[0])
                   invalid=true
           }
                      if (splitval[1])
               if (splitval[1].length>format[1])
                   invalid=true
		}
		if (invalid==true) {
           alert(alert_arr.INVALID+fldLabel, function(){
           	try { getObj(fldName).focus() } catch(error) { }
           });
           return false
		} else return true
   } else {
       // changes made -- to fix the ticket#3272
       var splitval=val.split(".")
       var arr_len = splitval.length;
           var len = 0;
       if(fldName == "probability" || fldName == "commissionrate")
           {
                   if(arr_len > 1)
                           len = splitval[1].length;
                   if(isNaN(val))
                   {
                        alert(alert_arr.INVALID+fldLabel, function(){
                        	try { getObj(fldName).focus() } catch(error) { }
                        });
                        return false
                   }
                   else if(splitval[0] > 100 || len > 3 || (splitval[0] >= 100 && splitval[1] > 0))
                   {
                        alert( fldLabel + alert_arr.EXCEEDS_MAX);
                        return false;
                   }
           }
       else if(splitval[0]>18446744073709551615)
           {
                   alert( fldLabel + alert_arr.EXCEEDS_MAX);
                   return false;
           }


       if (neg==true)
           var re=/^(-|)(\d)*(\.)?\d+(\.\d\d*)*$/
       else
       var re=/^(\d)*(\.)?\d+(\.\d\d*)*$/
   }

    //for precision check. ie.number must contains only one "."
    var dotcount=0;
    for (var i = 0; i < val.length; i++)
    {
          if (val.charAt(i) == ".")
             dotcount++;
    }

	if(dotcount>1)
	{
		alert(alert_arr.INVALID+fldLabel, function(){
			try { getObj(fldName).focus() } catch(error) { }
		});
		return false;
	}

	if (!re.test(val)) {
       alert(alert_arr.INVALID+fldLabel, function(){
		try { getObj(fldName).focus() } catch(error) { }
       });
       return false
   } else return true
}

// crmv@100905
function intValidate(fldName,fldLabel, uitype, val) { // crmv@83877
	if (typeof val == 'undefined') {
		val = getObj(fldName).value.replace(/^\s+/g, '').replace(/\s+$/g, '');
	}
	// crmv@100905e
	
	// crmv@83877
	uitype = parseInt(uitype);
	if (uitype == 7 || uitype == 9 || uitype == 71 || uitype == 72) { // crmv@92112
		if (!validateUserNumber(val)) {
			alert(alert_arr.INVALID+fldLabel, function(){
				try { getObj(fldName).focus(); } catch (error) { }
			});
			return false;
		}
		//convert to the float format for the standard check
		val = parseUserNumber(val) + "";
	}
	// crmv@83877e
	
	if (isNaN(val) || (val.indexOf(".")!=-1 && fldName != 'potential_amount' && fldName != 'list_price')) {
		alert(alert_arr.INVALID+fldLabel, function(){
			try { getObj(fldName).focus(); } catch(error) { }
		});
		return false;
	} else if((fldName != 'employees' || fldName != 'noofemployees') && (val < -2147483648 || val > 2147483647)) {
		alert(fldLabel +alert_arr.OUT_OF_RANGE);
		return false;
	} else if((fldName == 'employees' || fldName != 'noofemployees') && (val < 0 || val > 2147483647)) {
		alert(fldLabel +alert_arr.OUT_OF_RANGE);
		return false;
	} else {
		return true;
	}
}

function numConstComp(fldName,fldLabel,type,constval) {
    var val=parseFloat(getObj(fldName).value.replace(/^\s+/g, '').replace(/\s+$/g, ''));
    constval=parseFloat(constval);
    var ret=true;
    var err_callback = function(){
    	try { getObj(fldName).focus() } catch(error) { }
    };
	//crmv@59091
    switch (type) {
        case "L"  : if (val>=constval) {
						var err = sprintf(alert_arr.SHOULDBE_LESS_1, fldLabel, constval);
						alert(err, err_callback);
						ret=false;
                    }
                    break;
        case "LE" : if (val>constval) {
						var err = sprintf(alert_arr.SHOULDBE_LESS_EQUAL_1, fldLabel, constval);
						alert(err, err_callback);
						ret=false;
                    }
                    break;
        case "E"  :	if (val!=constval) {
						var err = sprintf(alert_arr.SHOULDBE_EQUAL_1, fldLabel, constval);
						alert(err, err_callback);
						ret=false;
					}
					break;
        case "NE" : if (val==constval) {
						var err = sprintf(alert_arr.SHOULDNOTBE_EQUAL_1, fldLabel, constval);
						alert(err, err_callback);
						ret=false;
                    }
                    break;
        case "G"  : if (val<=constval) {
						var err = sprintf(alert_arr.SHOULDBE_GREATER_1, fldLabel, constval);
						alert(err, err_callback);
						ret=false;
                    }
                    break;
        case "GE" : if (val<constval) {
						var err = sprintf(alert_arr.SHOULDBE_GREATER_EQUAL_1, fldLabel, constval);
						alert(err, err_callback);
						ret=false;
                    }
                    break;
    }
	//crmv@59091e
	return ret;
}

/* To get only filename from a given complete file path */
function getFileNameOnly(filename) {
  var onlyfilename = filename;
  // Normalize the path (to make sure we use the same path separator)
  var filename_normalized = filename.replace(/\\/g, '/');
  if(filename_normalized.lastIndexOf("/") != -1) {
    onlyfilename = filename_normalized.substring(filename_normalized.lastIndexOf("/") + 1);
  }
  return onlyfilename;
}

/* Function to validate the filename */
function validateFilename(form_ele) {
        if (form_ele.value == '') return true;
        var value = getFileNameOnly(form_ele.value);

        // Color highlighting logic
        var err_bg_color = "#FFAA22";

        if (typeof(form_ele.bgcolor) == "undefined") {
                form_ele.bgcolor = form_ele.style.backgroundColor;
        }

        // Validation starts here
        var valid = true;

        /* Filename length is constrained to 255 at database level */
        if (value.length > 255) {
                alert(alert_arr.LBL_FILENAME_LENGTH_EXCEED_ERR);
                valid = false;
        }

        if (!valid) {
                form_ele.style.backgroundColor = err_bg_color;
                return false;
        }
        form_ele.style.backgroundColor = form_ele.bgcolor;
        form_ele.form[form_ele.name + '_hidden'].value = value;
        return true;
}
//crmv@sdk-18501
function formValidate(form){
	return doformValidation('',form);
}
//crmv@sdk-18501 e
function massEditFormValidate(){
	return doformValidation('mass_edit');
}

/* crmv@59091 */
function doformValidation(edit_type,form) {	//crmv@sdk-18501
	
	//crmv@91082
	if(!SessionValidator.check()) {
		SessionValidator.showLogin();
		return false;
	}
	//crmv@91082e
	
	if (checkJSOverride(arguments)) return callJSOverride(arguments);
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	//Validation for Portal User
	//crmv@fix portal
	if(gVTModule == 'Contacts' && gValidationCall != 'tabchange' && isdefined('existing_portal') && isdefined('portal'))
	{
		//if existing portal value = 0, portal checkbox = checked, ( email field is not available OR  email is empty ) then we should not allow -- OR --
		//if existing portal value = 1, portal checkbox = checked, ( email field is available     AND email is empty ) then we should not allow
		if(edit_type=='')
		{
			if((getObj('existing_portal').value == 0 && getObj('portal').checked && (getObj('email') == null || trim(getObj('email').value) == '')) ||
			    getObj('existing_portal').value == 1 && getObj('portal').checked && getObj('email') != null && trim(getObj('email').value) == '')
			{
				alert(alert_arr.PORTAL_PROVIDE_EMAILID);
				return false;
			}
		}
		else
		{
			if(getObj('portal') != null && getObj('portal').checked && getObj('portal_mass_edit_check').checked && (getObj('email') == null || trim(getObj('email').value) == '' || getObj('email_mass_edit_check').checked==false))
			{
				alert(alert_arr.PORTAL_PROVIDE_EMAILID);
				return false;
			}
			if((getObj('email') != null && trim(getObj('email').value) == '' && getObj('email_mass_edit_check').checked) && !(getObj('portal').checked==false && getObj('portal_mass_edit_check').checked))
			{
				alert(alert_arr.EMAIL_CHECK_MSG);
				return false;
			}
		}
	}
	//crmv@fix portal end
	if(gVTModule == 'SalesOrder') {
		if(edit_type == 'mass_edit') {
			if (getObj('enable_recurring_mass_edit_check') != null
				&& getObj('enable_recurring_mass_edit_check').checked
				&& getObj('enable_recurring') != null) {
					if(getObj('enable_recurring').checked && (getObj('recurring_frequency') == null
						|| trim(getObj('recurring_frequency').value) == '--None--' || getObj('recurring_frequency_mass_edit_check').checked==false)) {
						alert(alert_arr.RECURRING_FREQUENCY_NOT_PROVIDED);
						return false;
					}
					if(getObj('enable_recurring').checked == false && getObj('recurring_frequency_mass_edit_check').checked
						&& getObj('recurring_frequency') != null && trim(getObj('recurring_frequency').value) !=  '--None--') {
						alert(alert_arr.RECURRING_FREQNECY_NOT_ENABLED);
						return false;
					}
			}
		} else if(getObj('enable_recurring') != null && getObj('enable_recurring').checked) {
			if(getObj('recurring_frequency') == null || getObj('recurring_frequency').value == '--None--') {
				alert(alert_arr.RECURRING_FREQUENCY_NOT_PROVIDED);
				return false;
			}
			var start_period = getObj('start_period');
			var end_period = getObj('end_period');
			if (trim(start_period.value) == '' || trim(end_period.value) == '') {
				alert(alert_arr.START_PERIOD_END_PERIOD_CANNOT_BE_EMPTY);
        		return false;
      		}
		}
	}
    for (var i=0; i<fieldname.length; i++) {
		if(edit_type == 'mass_edit') {
			if(fieldname[i]!='salutationtype')
			var obj = getObj(fieldname[i]+"_mass_edit_check");
			if(obj == null || obj.checked == false) continue;
		}
        if(getObj(fieldname[i]) != null)
        {
            var type=fielddatatype[i].split("~")
			if (type[1]=="M") {
				if (!emptyCheck(fieldname[i],fieldlabel[i],getObj(fieldname[i]).type)) {
					// TODO crmv@45755
					//var fieldDiv = jQuery('[name="'+fieldname[i]+'"]').parents('.dvtCellInfo,.dvtCellInfoOn');
					//fieldDiv.attr('class','dvtCellInfoM');
					return false;
				}
			}
            switch (type[0]) {
                case "O"  : break;
                case "V"  :
                	//crmv@add textlength check
                	if (type[2] && type[3]){
	            		if (!lengthComparison(fieldname[i],fieldlabel[i],type[2],type[3]))
	            			return false;
                	};
                	//crmv@add textlength check end
                	break;
                case "C"  : break;
                case "DT" :
                    if (getObj(fieldname[i]) != null && getObj(fieldname[i]).value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
                    {
                        if (type[1]=="M")
							if (!emptyCheck(fieldname[i],fieldlabel[i],getObj(type[2]).type)) // crmv@77878
                                return false

                                    if(typeof(type[3])=="undefined") var currdatechk="OTH"
                                    else var currdatechk=type[3]

                                        if (!dateTimeValidate(fieldname[i],type[2],fieldlabel[i],currdatechk))
                                            return false
                                                if (type[4]) {
                                                    if (!dateTimeComparison(fieldname[i],type[2],fieldlabel[i],type[5],type[6],type[4]))
                                                        return false

                                                }
                    }
                break;
                case "D"  :
                    if (getObj(fieldname[i]) != null && getObj(fieldname[i]).value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
                    {
                        if(typeof(type[2])=="undefined") var currdatechk="OTH"
                        else var currdatechk=type[2]
                        if (!dateValidate(fieldname[i],fieldlabel[i],currdatechk))
                        	return false
  						if (type[3]) {
							if(gVTModule == 'SalesOrder' && fieldname[i] == 'end_period'
								&& (getObj('enable_recurring') == null || getObj('enable_recurring').checked == false)) {
								continue;
							}
							var otherFieldIdx = fieldname.indexOf(type[4]);
							if (otherFieldIdx != -1) var otherFieldLabel = fieldlabel[otherFieldIdx]; else var otherFieldLabel = type[5];
							if (!dateComparison(fieldname[i],fieldlabel[i],type[4],otherFieldLabel,type[3]))
								return false
						}
                    }
                break;
                case "T"  :
                    if (getObj(fieldname[i]) != null && getObj(fieldname[i]).value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
                    {
                        if(typeof(type[2])=="undefined") var currtimechk="OTH"
                        else var currtimechk=type[2]

                            if (!timeValidate(fieldname[i],fieldlabel[i],currtimechk))
                                return false
                                    if (type[3]) {
										var otherFieldIdx = fieldname.indexOf(type[4]);
										if (otherFieldIdx != -1) var otherFieldLabel = fieldlabel[otherFieldIdx]; else var otherFieldLabel = type[5];
                                        if (!timeComparison(fieldname[i],fieldlabel[i],type[4],otherFieldLabel,type[3]))
                                            return false
                                    }
                    }
                break;
                case "I"  :
                    if (getObj(fieldname[i]) != null && getObj(fieldname[i]).value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
                    {
                        if (getObj(fieldname[i]).value.length!=0)
                        {
                            if (!intValidate(fieldname[i],fieldlabel[i],fielduitype[i])) // crmv@83877
                                return false
                                    if (type[2]) {
                                        if (!numConstComp(fieldname[i],fieldlabel[i],type[2],type[3]))
                                            return false
                                    }
                        }
                    }
                break;
                case "N"  :
                    case "NN" :
                    if (getObj(fieldname[i]) != null && getObj(fieldname[i]).value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
                    {
						// crmv@83877
                        if (getObj(fieldname[i]).value.length!=0) {
                            if (typeof(type[2])=="undefined")
								var numformat="any";
                            else
								var numformat=type[2];
                            if (type[0]=="NN") {
                                if (!numValidate(fieldname[i],fieldlabel[i],numformat,true, fielduitype[i])) {
									return false;
								}
							} else if (!numValidate(fieldname[i],fieldlabel[i],numformat,false, fielduitype[i])) {
                                return false;
							}
                            if (type[3]) {
                                if (!numConstComp(fieldname[i],fieldlabel[i],type[3],type[4]))
                                    return false;
                            }
                        }
                        // crmv@83877e
                    }
                break;
                case "E"  :
                    if (getObj(fieldname[i]) != null && getObj(fieldname[i]).value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
                    {
                        if (getObj(fieldname[i]).value.length!=0)
                        {
                            var etype = "EMAIL"
                            if(fieldname[i] == "yahooid" || fieldname[i] == "yahoo_id")
                            {
                                etype = "YAHOO";
                            }
                            if (!patternValidate(fieldname[i],fieldlabel[i],etype))
                                return false;
                        }
                    }
                break;
                //crmv@vtc
                case "PIVA" :
                // controllo il campo Partita Iva
                	if (getObj(fieldname[i]) != null && getObj(fieldname[i]).value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
                    {
                		res = getFile('index.php?module=Utilities&action=UtilitiesAjax&file=CheckPiva&piva='+getObj(fieldname[i]).value);
						if (res == "false") {
							alert ('Partita IVA non valida!');
							getObj(fieldname[i]).focus();
							return false;
						}
                    }
                break;
                case "CF" :
                // controllo il campo Codice Fiscale
                	if (getObj(fieldname[i]) != null && getObj(fieldname[i]).value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
                	{
                		res = getFile('index.php?module=Utilities&action=UtilitiesAjax&file=CheckCF&cf='+getObj(fieldname[i]).value);
                		if (res == "false") {
                			alert ('Codice Fiscale non valido!');
                			getObj(fieldname[i]).focus();
                			return false;
                		}
                	}
            	break;
                //crmv@vtc end
            }
            //start Birth day date validation
            if(fieldname[i] == "birthday" && getObj(fieldname[i]).value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0 )
            {
                var now =new Date()
                var currtimechk="OTH"
                var datelabel = fieldlabel[i]
                var datefield = fieldname[i]
                var datevalue =getObj(datefield).value.replace(/^\s+/g, '').replace(/\s+$/g, '')
                            if (!dateValidate(fieldname[i],fieldlabel[i],currdatechk))
                {
                            getObj(datefield).focus()
                                return false
                }
                else
                {
                    datearr=splitDateVal(datevalue);
                    dd=datearr[0]
                    mm=datearr[1]
                    yyyy=datearr[2]
                    var datecheck = new Date()
                        datecheck.setYear(yyyy)
                        datecheck.setMonth(mm-1)
                        datecheck.setDate(dd)
                            if (!compareDates(datecheck,datelabel,now,"Current Date","L"))
                    {
                                    getObj(datefield).focus()
                                    return false
                            }
                }
            }
            //End Birth day
            //crmv@161564
            if (fielduitype[i] == '29') {
    			var fileInput = jQuery('input[name="'+fieldname[i]+'"]')[0];
    			if (fileInput && fileInput.files && fileInput.files.length > 0) {
    				var fileSize = 0;
    				for (var j=0; j<fileInput.files.length; j++) {
    					fileSize = fileSize + fileInput.files[j].size;
    				}
    				if (window.max_upload_size && fileSize > max_upload_size) {
    					alert(alert_arr.LBL_FILESIZE_EXCEEDS_MAX_UPLOAD_SIZE);
    					return false;
    				}
    			}
            }
            //crmv@161564e
        }

    }
    if(gVTModule == 'Contacts')
    {
        if(getObj('imagename'))
        {
        	if(getObj('imagename').value != '')
        	{
				var image_arr = new Array();
				var image_arr = (getObj('imagename').value).split(".");
				var count = (image_arr.length)-1;
				var image_ext = image_arr[count].toLowerCase();
				if(image_ext ==  "jpeg" || image_ext ==  "png" || image_ext ==  "jpg" || image_ext ==  "pjpeg" || image_ext ==  "x-png" || image_ext ==  "gif")
				{
			        return true;
				}
				else
				{
			        alert(alert_arr.LBL_WRONG_IMAGE_TYPE);
			        return false;
				}
        	}
        }
        //ds@5e
    }

    //added to check Start Date & Time,if Activity Status is Planned.//start
	for (var j=0; j<fieldname.length; j++)
    {
        if(getObj(fieldname[j]) != null)
        {
            if(fieldname[j] == "date_start" || fieldname[j] == "task_date_start" )
            {
                var datelabel = fieldlabel[j]
                var datefield = fieldname[j]
                var startdatevalue = getObj(datefield).value.replace(/^\s+/g, '').replace(/\s+$/g, '')
            }
            if(fieldname[j] == "time_start" || fieldname[j] == "task_time_start")
            {
                var timelabel = fieldlabel[j]
                var timefield = fieldname[j]
                var timeval=getObj(timefield).value.replace(/^\s+/g, '').replace(/\s+$/g, '')
            }
            if(fieldname[j] == "eventstatus" || fieldname[j] == "taskstatus")
            {
                var statusvalue = getObj(fieldname[j]).value.replace(/^\s+/g, '').replace(/\s+$/g, '')
                var statuslabel = fieldlabel[j++]
            }
        }
    }
    if(statusvalue == "Planned" && startdatevalue) // crmv@101312
	{
		var dateelements=splitDateVal(startdatevalue)
		
		var hourval=parseInt(timeval.substring(0,timeval.indexOf(":")))
		var minval=parseInt(timeval.substring(timeval.indexOf(":")+1,timeval.length))
		
		dd=dateelements[0]
		mm=dateelements[1]
		yyyy=dateelements[2]
		
		var chkdate=new Date()
		chkdate.setYear(yyyy)
		chkdate.setMonth(mm-1)
		chkdate.setDate(dd)
		chkdate.setMinutes(minval)
		chkdate.setHours(hourval)
	}//end

	 // We need to enforce fileupload for internal type
	 if(gVTModule == 'Documents') {
		var fileloc = getObj('filelocationtype');
	 	if (fileloc && (fileloc.value == 'I' || fileloc.value == 'B')) {	//crmv@fix
			// crmv@109856
			var filename_hidden = getObj('filename_hidden');
			if(filename_hidden && filename_hidden.value == '') {
				alert(alert_arr.LBL_PLEASE_SELECT_FILE_TO_UPLOAD);
				return false;
			}
			// crmv@97013 
			var fileInput = jQuery('input[name=filename]')[0];
			if (fileInput && fileInput.files && fileInput.files.length > 0) {
				var fileSize = fileInput.files[0].size;
				if (window.max_upload_size && fileSize > max_upload_size) {
					alert(alert_arr.LBL_FILESIZE_EXCEEDS_MAX_UPLOAD_SIZE);
					return false;
				}
			}
			// crmv@97013e crmv@109856e
		}
	 }
	//crmv@sdk-18501
	sdkValidate = SDKValidate(form);
	if (sdkValidate) {
		sdkValidateResponse = eval('('+sdkValidate.responseText+')');
		if (!sdkValidateResponse['status']) {
			return false;
		}
	}
	//crmv@sdk-18501 e
    if (!AjaxDuplicateValidate(gVTModule,form)) return false;	//crmv@7231 crmv@25101
    if (gVTModule == 'Contacts' && !portalDuplicateValidate(form)) return false; //crmv@157490
    
 	return true;
}
//crmv@save	crmv@93990 crmv@115268
function SubmitForm(Addform,id,module,ajaxForm,callback,confirm){
	if (typeof(ajaxForm) != 'boolean') var ajaxForm = false;
	if (typeof(confirm) != 'boolean') var confirm = false;

	if (formValidate(Addform)){	//crmv@sdk-18501
		//crmv@19653
		if(module == 'Accounts'){
	 		if (isdefined('external_code')){
	 			var ext=getObj('external_code').value
	 			var exttype=getObj('external_code').type
	 			if ( (trim(ext) != '') && (exttype != "hidden") ) {
	 				if (!AjaxDuplicateValidateEXT_CODE(module,'external_code','','editview'))
	 					return false;
	 			}
	 		}
	 	}
	 	//crmv@19653e
	 	if (confirm) {
		 	vteconfirm(alert_arr.ARE_YOU_SURE, function(yes) {
				if (yes) SubmitForm2();
			});
		} else {
			SubmitForm2();
		}
	 	function SubmitForm2() {
			if (module == 'Accounts' && id != '')
				checkAddress(Addform,id);
			else {
				// crmv@162674
				var r = Addform.onsubmit(); // crmv@97096
				if (r === false) return;
				
				// crmv@166949
				if (window.sendMessageFromPanel) {
					sendMessageFromPanel({ name: 'beforeSaveRecord' });
				}
				// crmv@166949e
				
				if (ajaxForm) {
					//crmv@185786
					var fieldinfo = {};
					jQuery.each(window.fieldname || [], function(i,name){
						fieldinfo[name.replace('[]','')] = {'label':fieldlabel[i],'datatype':fielddatatype[i],'uitype':fielduitype[i],'type':fieldwstype[i]};
					});
					var form = new FormData();
					jQuery.each(jQuery(Addform).serializeArray(), function(){
						//crmv@166678
						if (this.name.indexOf('[]') > -1) {
							if (typeof(form[this.name.replace('[]','')]) == 'undefined') form[this.name.replace('[]','')] = [];
							form.append(this.name.replace('[]',''),this.value);
						} else {
							form.append(this.name,this.value);
						}
						//crmv@166678e
					});
					// append file input to form
					jQuery.each(fieldinfo,function(fname,finfo){
						if (finfo['type'] == 'file') {
							for (var x = 0; x < jQuery('[name="'+fname+'[]"]').prop('files').length; x++) {
								form.append(fname+'[]', jQuery('[name="'+fname+'[]"]').prop('files')[x]);
							}
						}
					});
					jQuery.ajax({
						url: 'index.php',
						type: 'POST',
						data: form,
						cache: false,
				        contentType: false,
				        processData: false,
						success: function(data) {
							// crmv@166949
							if (window.sendMessageFromPanel) {
								sendMessageFromPanel({ name: 'afterSaveRecord' });
							}
							// crmv@166949e
							if (typeof(callback) != 'undefined') callback();
						}
					});
					//crmv@185786e
				} else {
					Addform.submit();
					// crmv@166949
					if (window.sendMessageFromPanel) {
						sendMessageFromPanel({ name: 'afterSaveRecord' });
					}
					// crmv@166949e
				}
				// crmv@162674e
			}
		}
	}
}
//crmv@save end	crmv@93990e crmv@115268e
//crmv@ajax duplicate
function AjaxDuplicateValidate(module,form)
{
	if (typeof(form) == 'undefined')
		form = 'EditView';
	else
		form = form.name;
	oform = document.forms[form];
	//crmv@23984
	//crmv@26280
	if (typeof(merge_user_fields) != 'undefined' && merge_user_fields != undefined && merge_user_fields[module] != undefined) {
		fieldvalues = merge_user_fields[module];
	} else {
	    var url = "module=Utilities&action=UtilitiesAjax&file=CheckDuplicate&formodule="+module+"&action_ajax=get_merge_fields";
		var res = getFile('index.php?'+url);
		res = eval('('+res+ ')');
		if (res['success']){
			fieldvalues = res['fieldvalues'];
		}
		else{
			return true;
		}
	}
	var count=fieldvalues.length;
	//crmv@26280e
	if (count == 0){
		return true;
	}
	for(i=0;i<count;i++){
		if (isdefined(fieldvalues[i]['fieldname'])){
			if (fieldvalues[i]['uitype'] == 56 || fieldvalues[i]['uitype'] == 156){
				if (getObj(fieldvalues[i]['fieldname']).checked == true){
					fieldvalues[i]['value'] = 1;
				}
				else{
					fieldvalues[i]['value'] = 0;
				}
			}
			else if (fieldvalues[i]['uitype'] == 33) {
				//crmv@84969
				var selvalues = [];
				jQuery("select[name='"+fieldvalues[i]['fieldname']+"[]']").find('option:selected').each(function(i, selected){
					selvalues[i] = jQuery(selected).val();
				});
				//crmv@84969e
				fieldvalues[i]['value'] = selvalues.join(" |##| ");
			}
			else{
				fieldvalues[i]['value']=getObj(fieldvalues[i]['fieldname']).value
			}
		}
		else{
			delete fieldvalues[i];
		}
	}
	count=fieldvalues.length;
	if (count == 0 || JSON.stringify(fieldvalues) == '[null]') {	//crmv@57187
		return true;
	}
	var record = '';
	if (isdefined('record')){
		record = getObj('record').value;
	}
	//crmv@24240
    var url = "module=Utilities&action=UtilitiesAjax&file=CheckDuplicate&formodule="+module+"&action_ajax=control_duplicate&fieldvalues="+escapeAll(JSON.stringify(fieldvalues))+"&record="+record;
    //crmv@24240e
    var res = getFile('index.php?'+url);
    res = eval('('+res+ ')');
	if(res['success'] == true){
		msg = alert_arr.EXISTING_RECORD;
		for (var data in res['data']){
			msg+="\n"+ data +": "+ res['data'][data];
		}
		//crmv@19438
		if (oform.name == 'ConvertLead')
		msg+="\n"+ alert_arr.EXISTING_SAVE_CONVERTLEAD;
		//crmv@19438e
		msg+="\n"+ alert_arr.EXISTING_SAVE;
		if (!confirm(msg)){
			return false;
		}
	}
	//crmv@23984e
    return true;
}
//crmv@ajax duplicate end
function clearId(fldName) {

    var currObj=getObj(fldName)

    currObj.value=""

}

function showCalc(fldName) {
    var currObj=getObj(fldName)
    openPopUp("calcWin",currObj,"/crm/Calc.do?currFld="+fldName,"Calc",170,220,"menubar=no,toolbar=no,location=no,status=no,scrollbars=no,resizable=yes")
}

function showLookUp(fldName,fldId,fldLabel,searchmodule,hostName,serverPort,username) {
    var currObj=getObj(fldName)

    //var fldValue=currObj.value.replace(/^\s+/g, '').replace(/\s+$/g, '')

    //need to pass the name of the system in which the server is running so that even when the search is invoked from another system, the url will remain the same

    openPopUp("lookUpWin",currObj,"/crm/Search.do?searchmodule="+searchmodule+"&fldName="+fldName+"&fldId="+fldId+"&fldLabel="+fldLabel+"&fldValue=&user="+username,"LookUp",500,400,"menubar=no,toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes")
}

function openPopUp(winInst,currObj,baseURL,winName,width,height,features) {

    var left=parseInt(findPosX(currObj))
    var top=parseInt(findPosY(currObj))

    if (window.navigator.appName!="Opera") top+=parseInt(currObj.offsetHeight)
    else top+=(parseInt(currObj.offsetHeight)*2)+10

    if (browser_ie)    {
        top+=window.screenTop-document.body.scrollTop
        left-=document.body.scrollLeft
        if (top+height+30>window.screen.height)
            top=findPosY(currObj)+window.screenTop-height-30 //30 is a constant to avoid positioning issue
        if (left+width>window.screen.width)
            left=findPosX(currObj)+window.screenLeft-width
    } else if (browser_nn4 || browser_nn6) {
        top+=(scrY-pgeY)
        left+=(scrX-pgeX)
        if (top+height+30>window.screen.height)
            top=findPosY(currObj)+(scrY-pgeY)-height-30
        if (left+width>window.screen.width)
            left=findPosX(currObj)+(scrX-pgeX)-width
    }

    features="width="+width+",height="+height+",top="+top+",left="+left+";"+features
    eval(winInst+'=openPopup("'+baseURL+'","'+winName+'","'+features+'","auto")');//crmv@21048m
}

var scrX=0,scrY=0,pgeX=0,pgeY=0;

if (browser_nn4 || browser_nn6) {
    document.addEventListener("click",popUpListener,true)
}

function popUpListener(ev) {
    if (browser_nn4 || browser_nn6) {
        scrX=ev.screenX
        scrY=ev.screenY
        pgeX=ev.pageX
        pgeY=ev.pageY
    }
}

function toggleSelect(state,relCheckName) {
    if (getObj(relCheckName)) {
        if (typeof(getObj(relCheckName).length)=="undefined") {
            getObj(relCheckName).checked=state
        } else {
            for (var i=0;i<getObj(relCheckName).length;i++)
                getObj(relCheckName)[i].checked=state
        }
    }
}

function toggleSelectAll(relCheckName,selectAllName) {
    if (typeof(getObj(relCheckName).length)=="undefined") {
        getObj(selectAllName).checked=getObj(relCheckName).checked
    } else {
        var atleastOneFalse=false;
        for (var i=0;i<getObj(relCheckName).length;i++) {
            if (getObj(relCheckName)[i].checked==false) {
                atleastOneFalse=true
                break;
            }
        }
        getObj(selectAllName).checked=!atleastOneFalse
    }
}
//added for show/hide 10July
function expandCont(bn)
{
    var leftTab = document.getElementById(bn);
           leftTab.style.display = (leftTab.style.display == "block")?"none":"block";
           img = document.getElementById("img_"+bn);
          img.src=(img.src.indexOf("images/toggle1.gif")!=-1)?resourcever("toggle2.gif"):resourcever("toggle1.gif");
          set_cookie_gen(bn,leftTab.style.display)

}

function setExpandCollapse_gen()
{
    var x = leftpanelistarray.length;
    for (i = 0 ; i < x ; i++)
    {
        var listObj=getObj(leftpanelistarray[i])
        var tgImageObj=getObj("img_"+leftpanelistarray[i])
        var status = get_cookie_gen(leftpanelistarray[i])

        if (status == "block") {
            listObj.style.display="block";
            tgImageObj.src=resourcever("toggle2.gif");
        } else if(status == "none") {
            listObj.style.display="none";
            tgImageObj.src=resourcever("toggle1.gif");
        }
    }
}

function toggleDiv(id) {

    var listTableObj=getObj(id)

    if (listTableObj.style.display=="block")
    {
        listTableObj.style.display="none"
    }else{
        listTableObj.style.display="block"
    }
    //set_cookie(id,listTableObj.style.display)
}

//Setting cookies
function set_cookie_gen ( name, value, exp_y, exp_m, exp_d, path, domain, secure )
{
  var cookie_string = name + "=" + escape ( value );

  if ( exp_y )
  {
    var expires = new Date ( exp_y, exp_m, exp_d );
    cookie_string += "; expires=" + expires.toGMTString();
  }

  if ( path )
        cookie_string += "; path=" + escape ( path );

  if ( domain )
        cookie_string += "; domain=" + escape ( domain );

  if ( secure )
        cookie_string += "; secure";

  document.cookie = cookie_string;
}

// Retrieving cookies
function get_cookie_gen ( cookie_name )
{
  var results = document.cookie.match ( cookie_name + '=(.*?)(;|$)' );

  if ( results )
    return ( unescape ( results[1] ) );
  else
    return null;
}

// Delete cookies
function delete_cookie_gen ( cookie_name )
{
  var cookie_date = new Date ( );  // current date & time
  cookie_date.setTime ( cookie_date.getTime() - 1 );
  document.cookie = cookie_name += "=; expires=" + cookie_date.toGMTString();
}
//end added for show/hide 10July

/** This is Javascript Function which is used to toogle between
  * assigntype user and group/team select options while assigning owner to entity.
  */
//crmv@92272 crmv@106856 crmv@160843
function toggleAssignType(currType, assign_user_div, assign_team_div, assign_other_div)
{
	var other_input = jQuery("#"+assign_other_div).children('input').attr('id');
	
	if (currType=="U")
	{
		jQuery("#"+assign_user_div).show();
		jQuery("#"+assign_team_div).hide();
		jQuery("#"+assign_other_div).hide();
		
		jQuery('.editoptions[fieldname="'+other_input+'"]').hide();
		jQuery('#advanced_field_assignment_button_assigned_user_id').hide();
	}
	else if (currType=="T")
	{
		jQuery("#"+assign_user_div).hide();
		jQuery("#"+assign_team_div).show();
		jQuery("#"+assign_other_div).hide();
		
		jQuery('.editoptions[fieldname="'+other_input+'"]').hide();
		jQuery('#advanced_field_assignment_button_assigned_user_id').hide();
	}
	else if (currType=="O")
	{
		jQuery("#"+assign_user_div).hide();
		jQuery("#"+assign_team_div).hide();
		jQuery("#"+assign_other_div).show();
		
		jQuery("#"+other_input).show();
		jQuery("#"+other_input).val('');
		jQuery('.editoptions[fieldname="'+other_input+'"]').show();
		jQuery('.editoptions[fieldname="'+other_input+'"]').find('.populateField').val('');
		jQuery('#advanced_field_assignment_button_assigned_user_id').hide();
	}
	else if (currType=="A")
	{
		jQuery("#"+assign_user_div).hide();
		jQuery("#"+assign_team_div).hide();
		jQuery("#"+assign_other_div).show();
		
		jQuery("#"+other_input).hide();
		jQuery("#"+other_input).val('advanced_field_assignment');
		jQuery('.editoptions[fieldname="'+other_input+'"]').hide();
		jQuery('#advanced_field_assignment_button_assigned_user_id').show();
		ActionTaskScript.showSdkParamsInput(jQuery('#'+other_input),'assigned_user_id');	//crmv@113527
	}
}
//crmv@92272e crmv@106856e crmv@160843e
//to display type of address for google map
function showLocateMapMenu()
    {
            getObj("dropDownMenu").style.display="block"
            getObj("dropDownMenu").style.left=findPosX(getObj("locateMap"))
            getObj("dropDownMenu").style.top=findPosY(getObj("locateMap"))+getObj("locateMap").offsetHeight
    }


function hideLocateMapMenu(ev)
    {
            if (browser_ie)
                    currElement=window.event.srcElement
            else if (browser_nn4 || browser_nn6)
                    currElement=ev.target

            if (currElement.id!="locateMap")
                    if (getObj("dropDownMenu").style.display=="block")
                            getObj("dropDownMenu").style.display="none"
    }
/*
* javascript function to display the div tag
* @param divId :: div tag ID
*/
function show(divId)
{
    if(getObj(divId))
    {
        var id = document.getElementById(divId);

        id.style.display = 'inline';
    }
}

/*
* javascript function to display the div tag
* @param divId :: div tag ID
*/
function showBlock(divId)
{
    var id = document.getElementById(divId);
    if (id) id.style.display = 'block'; // crmv@160733
}


/*
* javascript function to hide the div tag
* @param divId :: div tag ID
*/
function hide(divId) {
    var id = document.getElementById(divId);
    id.style.display = 'none';
}

function fnhide(divId) {
    var id = document.getElementById(divId);
    if (id) id.style.display = 'none';
}

function fnLoadValues(obj1,obj2,SelTab,unSelTab,moduletype,module){


	var oform = document.forms['EditView'];
   oform.action.value='Save';
   //global variable to check the validation calling function to avoid validating when tab change
   gValidationCall = 'tabchange';

	/*var tabName1 = document.getElementById(obj1);
	var tabName2 = document.getElementById(obj2);
	var tagName1 = document.getElementById(SelTab);
	var tagName2 = document.getElementById(unSelTab);
	if(tabName1.className == "dvtUnSelectedCell")
		tabName1.className = "dvtSelectedCell";
	if(tabName2.className == "dvtSelectedCell")
		tabName2.className = "dvtUnSelectedCell";

	tagName1.style.display='block';
	tagName2.style.display='none';*/
	gValidationCall = 'tabchange';

  // if((moduletype == 'inventory' && validateInventory(module)) ||(moduletype == 'normal') && formValidate())
  // if(formValidate())
  // {
	   var tabName1 = document.getElementById(obj1);

	   var tabName2 = document.getElementById(obj2);

	   var tagName1 = document.getElementById(SelTab);

	   var tagName2 = document.getElementById(unSelTab);

	   if(tabName1.className == "dvtUnSelectedCell")

		   tabName1.className = "dvtSelectedCell";

	   if(tabName2.className == "dvtSelectedCell")

		   tabName2.className = "dvtUnSelectedCell";
	   tagName1.style.display='block';

	   tagName2.style.display='none';
  // }

   gValidationCall = '';
}

function fnCopy(source,design){

   document.getElementById(source).value=document.getElementById(design).value;

   document.getElementById(source).disabled=true;

}

function fnClear(source){

   document.getElementById(source).value=" ";

   document.getElementById(source).disabled=false;

}

function fnCpy(){

   var tagName=document.getElementById("cpy");

   if(tagName.checked==true){
       fnCopy("shipaddress","address");

       fnCopy("shippobox","pobox");

       fnCopy("shipcity","city");

       fnCopy("shipcode","code");

       fnCopy("shipstate","state");

       fnCopy("shipcountry","country");

   }

   else{

       fnClear("shipaddress");

       fnClear("shippobox");

       fnClear("shipcity");

       fnClear("shipcode");

       fnClear("shipstate");

       fnClear("shipcountry");

   }

}
function fnDown(obj){
        var tagName = document.getElementById(obj);
        var tabName = document.getElementById("one");
        if(tagName.style.display == 'none'){
                tagName.style.display = 'block';
                tabName.style.display = 'block';
        }
        else{
                tabName.style.display = 'none';
                tagName.style.display = 'none';
        }
}

/*
* javascript function to add field rows
* @param option_values :: List of Field names
*/
var count = 0;
//crmv@16312
function fnAddSrch()
{
    var tableName = document.getElementById('adSrc');
    var prev = tableName.rows.length;
    var count = prev;
    var row = tableName.insertRow(prev);
	// why this?
    /*
	if(count%2)
        row.className = "dvtCellLabel";
    else
        row.className = "dvtCellInfo";
	*/
    var fieldObject = document.getElementById("Fields0");
    var conditionObject = document.getElementById("Condition0");
    var searchValueObject = document.getElementById("Srch_value0");
	//crmv@18221
	var searchValueObject = document.getElementById("Srch_value0");
    var andFieldsObject = document.getElementById("andFields0");
    //crmv@18221 end
	var columnone = document.createElement('td');
	var colone = fieldObject.cloneNode(true);
	colone.setAttribute('id','Fields'+count);
	colone.setAttribute('name','Fields'+count);
	colone.setAttribute('value','');
	colone.style.display = 'initial';
	jQuery(colone).removeData();
	jQuery.each(jQuery(colone).data(), function (i) {
		jQuery(colone).removeData(i);
		jQuery(colone).removeAttr('data-'+i);
	});
	colone.onchange = function() {
		updatefOptions(colone, 'Condition'+count);
	}
	var divone = document.createElement('div');
	divone.className = "dvtCellInfo";
	divone.appendChild(colone);
	columnone.appendChild(divone);
	row.appendChild(columnone);

	var columntwo = document.createElement('td');
	var coltwo = conditionObject.cloneNode(true);
	coltwo.setAttribute('id','Condition'+count);
	coltwo.setAttribute('name','Condition'+count);
	coltwo.setAttribute('value','');
	coltwo.style.display = 'initial';
	jQuery(coltwo).removeData();
	jQuery.each(jQuery(coltwo).data(), function (i) {
		jQuery(coltwo).removeData(i);
		jQuery(coltwo).removeAttr('data-'+i);
	});
	var divtwo = document.createElement('div');
	divtwo.className = "dvtCellInfo";
	divtwo.appendChild(coltwo);
	columntwo.appendChild(divtwo);
	row.appendChild(columntwo);

	var columnthree = document.createElement('td');
	var colthree = searchValueObject.cloneNode(true);
	colthree.setAttribute('id','Srch_value'+count);
	colthree.setAttribute('name','Srch_value'+count);
	colthree.setAttribute('value','');
	colthree.value = '';
	// crmv@171261
	colthree.onkeypress = onAdvSearchFieldKeyPress.bind(this);
	// crmv@171261e
	var divthree = document.createElement('div');
	divthree.className = "dvtCellInfo";
	divthree.appendChild(colthree);
	columnthree.appendChild(divthree);
	row.appendChild(columnthree);

	//crmv@18221
	var columnfour = document.createElement('td');
	var colfour = andFieldsObject.cloneNode(true);
	colfour.setAttribute('id','andFields'+count);
	colfour.setAttribute('name','andFields'+count);
	colfour.setAttribute('value','');
	colfour.value = '';
	columnfour.appendChild(colfour);
	row.appendChild(columnfour);
	updatefOptions(colone, 'Condition'+count);
	updatefOptionsAll(false);
	//crmv@18221 end
}
//crmv@16312 end
function totalnoofrows()
{
    var tableName = document.getElementById('adSrc');
    jQuery('#basic_search_cnt').val(tableName.rows.length); // crmv@31245
}

/*
* javascript function to delete field rows in advance search
* @param void :: void
*/
function delRow()
{

    var tableName = document.getElementById('adSrc');

    var prev = tableName.rows.length;

    if(prev > 1)

    document.getElementById('adSrc').deleteRow(prev-1);

}

function fnVis(obj){

   var profTag = document.getElementById("prof");

   var moreTag = document.getElementById("more");

   var addrTag = document.getElementById("addr");


   if(obj == 'prof'){

       document.getElementById('mnuTab').style.display = 'block';

       document.getElementById('mnuTab1').style.display = 'none';

       document.getElementById('mnuTab2').style.display = 'none';

       profTag.className = 'dvtSelectedCell';

       moreTag.className = 'dvtUnSelectedCell';

       addrTag.className = 'dvtUnSelectedCell';

   }


   else if(obj == 'more'){

       document.getElementById('mnuTab1').style.display = 'block';

       document.getElementById('mnuTab').style.display = 'none';

       document.getElementById('mnuTab2').style.display = 'none';

       moreTag.className = 'dvtSelectedCell';

       profTag.className = 'dvtUnSelectedCell';

       addrTag.className = 'dvtUnSelectedCell';

   }


   else if(obj == 'addr'){

       document.getElementById('mnuTab2').style.display = 'block';

       document.getElementById('mnuTab').style.display = 'none';

       document.getElementById('mnuTab1').style.display = 'none';

       addrTag.className = 'dvtSelectedCell';

       profTag.className = 'dvtUnSelectedCell';

       moreTag.className = 'dvtUnSelectedCell';

   }

}

// crmv@94525
function showFloatingDiv(divid, obj, options) {
	
	// set default options
	options = jQuery.extend({}, {
		draggable: true,			// true to enable the dragging. Define an element with id: mainid_Handle
		modal: false,				// if modal, the dialog blocks the page
		removeOnMaskClick: true,	// if true, the modal dialog can be closed by clicking on the mask
		center: !obj,				// center the dialog in the page (default to true if not passed an id)
		relative: !!obj,			// align the div next to the passed object (default to true if passed an id)
	}, options || {});
	
	var $el = jQuery('#'+divid);
	if ($el.length <= 0) return;
	
	var el = $el.get(0);
	
	if (options.draggable && window.Drag && (!el.root || !el.root.onDragStart)) {
		var Handle = document.getElementById(divid+"_Handle");
		// crmv@192014
		if (Handle) {
			$el.draggable({
				handle: Handle
			});
		} else {
			$el.draggable();
		}
		// crmv@192014e
	}
	
	if (options.modal) {
		var overlay = VteJS_DialogBox.block(undefined, 0.0);
		if (options.removeOnMaskClick) {
			jQuery('#__vtejs_dialogbox_olayer__').click(function(){
				hideFloatingDiv(divid);
			});
		}
	}
	$el.css({
		'z-index': findZMax()+1,
		'visibility': 'visible',
	}).show();
	
	if (options.center) {
		placeAtCenter($el.get(0));
	} else if (options.relative && obj) {
		$el.position({
			my: "left top",		// div
			at: "left top",		// obj
			of: obj,
			collision: "flip"	//crmv@146434
		});
	}
}

function hideFloatingDiv(divid) {
	Blockage.checkPanelBlocker(hideChecked); // crmv@171115
	
	function hideChecked() {
		VteJS_DialogBox.unblock();
		fninvsh(divid);
	}
}
// crmv@94525e

function fnvsh(obj,Lay){
    var tagName = document.getElementById(Lay);
    var leftSide = findPosX(obj);
    var topSide = findPosY(obj);
    tagName.style.left= leftSide + 175 + 'px';
    tagName.style.top= topSide + 'px';
    tagName.style.visibility = 'visible';
}

function fnvshobj(obj,Lay){
    var tagName = document.getElementById(Lay);
    var leftSide = findPosX(obj);//-30; //crmv@26807
    var topSide = findPosY(obj);//+30; //crmv@26807
    var widthM = jQuery(tagName).width(); // crmv@29686
    //crmv@30356
    if(isMobile()) {
    	topSide = topSide + 65;
    	leftSide = 20;
	}
    //crmv@30356e
    if(Lay == 'editdiv') {
    //crmv@ds47
    //window should open more left
        leftSide = leftSide - 625;
        topSide = topSide - 125;
    //crmv@ds47 end
    } else if(Lay == 'transferdiv') {
        leftSide = leftSide - 10;
		topSide = topSide;
    }
    var IE = document.all?true:false;
    if(IE) {
		if(jQuery("#repposition1").length > 0) {
    		if(topSide > 1200) {
				topSide = topSide-250;
   			}
		}
	}
    var getVal = eval(leftSide) + eval(widthM);
    if(getVal  > document.body.clientWidth ){
        leftSide = eval(leftSide) - eval(widthM);
        tagName.style.left = leftSide + 34 + 'px';
    } else
        tagName.style.left= leftSide + 'px';
    tagName.style.top= topSide + 'px';
    tagName.style.display = 'block';
    tagName.style.visibility = "visible";
    tagName.style.zIndex = findZMax()+1;	//crmv@26986
}

//crmv@ds2  add new function for INFO/DESCRIPTION POPUP
function fnvshobj2(obj,Lay){
    var tagName = document.getElementById(Lay);
    var leftSide = findPosX(obj);
    var topSide = findPosY(obj);

    leftSide = leftSide * 1 + 25;
    topSide = topSide *1 - 90;
    var maxW = tagName.style.width;
    var widthM = maxW.substring(0,maxW.length-2);

    tagName.style.left= leftSide + 'px';
    tagName.style.top= topSide + 'px';
    tagName.style.display = 'block';
    tagName.style.visibility = "visible";
}
//crmv@ds2 end

function posLay(obj,Lay){
    var tagName = document.getElementById(Lay);
    var leftSide = findPosX(obj);
    var topSide = findPosY(obj);
    var maxW = tagName.style.width;
    var widthM = maxW.substring(0,maxW.length-2);
    var getVal = eval(leftSide) + eval(widthM);
    if(getVal  > document.body.clientWidth ){
        leftSide = eval(leftSide) - eval(widthM);
        tagName.style.left = leftSide + 'px';
    }
    else
        tagName.style.left= leftSide + 'px';
    tagName.style.top= topSide + 'px';
}

function fninvsh(Lay){
    var tagName = document.getElementById(Lay);
    tagName.style.visibility = 'hidden';
    tagName.style.display = 'none';
}

function fnvshNrm(Lay){
    var tagName = document.getElementById(Lay);
    tagName.style.visibility = 'visible';
    tagName.style.display = 'block';
}

function cancelForm(frm)
{
        window.history.back();
}

function trim(str)
{
	if (str != undefined) {
	    var s = str.replace(/\s+$/,'');
	    s = s.replace(/^\s+/,'');
	    return s;
	}
}

function clear_form(form)
{
    for (j = 0; j < form.elements.length; j++)
    {
        if (form.elements[j].type == 'text' || form.elements[j].type == 'select-one')
        {
            form.elements[j].value = '';
        }
    }
}

function ActivateCheckBox()
{
        var map = document.getElementById("saved_map_checkbox");
        var source = document.getElementById("saved_source");

        if(map.checked == true)
        {
                source.disabled = false;
        }
        else
        {
                source.disabled = true;
        }
}

function addOnloadEvent(fnc){
  if ( typeof window.addEventListener != "undefined" )
    window.addEventListener( "load", fnc, false );
  else if ( typeof window.attachEvent != "undefined" ) {
    window.attachEvent( "onload", fnc );
  }
  else {
    if ( window.onload != null ) {
      var oldOnload = window.onload;
      window.onload = function ( e ) {
        oldOnload( e );
        window[fnc]();
      };
    }
    else
      window.onload = fnc;
  }
}

function fnHide_Event(obj){
	document.getElementById(obj).style.visibility = 'hidden';
}

function InternalMailer(record_id,field_id,field_name,par_module,type) {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067
	
	var url;
	switch(type) {
		case 'record_id':
		        url = 'index.php?module=Emails&action=EmailsAjax&internal_mailer=true&type='+type+'&field_id='+field_id+'&rec_id='+record_id+'&pid='+record_id+'&fieldname='+field_name+'&file=EditView&par_module='+par_module;//query string field_id added for listview-compose email issue // crmv@101840
		break;
		case 'email_addy':
		        url = 'index.php?module=Emails&action=EmailsAjax&internal_mailer=true&type='+type+'&email_addy='+record_id+'&file=EditView';
		break;
	}
	//crmv@31197
	//var opts = "menubar=no,toolbar=no,location=no,status=no,resizable=yes,scrollbars=yes";
	//openPopUp('xComposeEmail',this,url,'createemailWin',830,662,opts);
	window.open(url,'_blank');
	//crmv@31197e
}

function OpenCompose(id,mode,openpopup,path)	//crmv@25472	//crmv@31197
{
    switch(mode)
    {
        case 'create':
            url = 'index.php?module=Emails&action=EmailsAjax&file=EditView';
            break;
        case 'reply':
        case 'reply_all':
        case 'forward':
        case 'draft':
            url = 'index.php?module=Emails&action=EmailsAjax&file=EditView&message_mode='+mode+'&message='+id;
            break;
		case 'Invoice':
            url = 'index.php?module=Emails&action=EmailsAjax&file=EditView&attachment='+mode+'_'+id+'.pdf';
			break;
		case 'PurchaseOrder':
            url = 'index.php?module=Emails&action=EmailsAjax&file=EditView&attachment='+mode+'_'+id+'.pdf';
			break;
		case 'SalesOrder':
            url = 'index.php?module=Emails&action=EmailsAjax&file=EditView&attachment='+mode+'_'+id+'.pdf';
			break;
		case 'Quote':
            url = 'index.php?module=Emails&action=EmailsAjax&file=EditView&attachment='+mode+'_'+id+'.pdf';
			break;
		case 'Documents':
            url = 'index.php?module=Emails&action=EmailsAjax&file=EditView&attachment='+id+'&rec='+document.DetailView.record.value+'&pid='+document.DetailView.record.value;	//crmv@31691	crmv@78538
			break;
		//crmv@43147
		case 'share':
			url = 'index.php?module=Emails&action=EmailsAjax&file=EditView&mode=share&record='+id+'&pid='+id;	//crmv@78538
			break;
		//crmv@43147e
    }
    //crmv@31197
    if (path != undefined && path != '') {
    	url = path+url;
    }
    //crmv@31197e
    if (typeof(current_account) != 'undefined' && current_account != undefined && current_account != '' && current_account != 'all') {
    	url += '&account='+current_account;
    }
    //crmv@25472
    if (openpopup == 'no') {
    	window.location = url+'&cancel_button=history';	//crmv@26512
   	} else {
   		//crmv@31197
    	//openPopUp('xComposeEmail',this,url,'createemailWin',820,689,'menubar=no,toolbar=no,location=no,status=no,resizable=no,scrollbars=yes');
    	window.open(url,'_blank');
    	//crmv@31197e
   	}
   	//crmv@25472e
}

//Function added for Mass select in Popup - Philip
//crmv@selectall fix
function SelectAll(mod,parmod) {
	//crmv@26807 crmv@26961
	var strHtml = '';
	var start = 0;
	var end = 0;
	
	//crmv@72993 - use the saved functions, instead of using the ones only in this page
	var funcs = (window.JSON ? JSON.parse(jQuery('#popup_select_actions').val() || '{}') || {} : null);
	if (funcs !== null && !jQuery.isEmptyObject(funcs)) {
		jQuery.each(funcs, function(checkId, fn) {
			eval(fn);
		});
		closePopup();
	//crmv@72993e
	// remove old unused code (was problematic on calendar)
	} else {
		//crmv@26921
		var idstring = get_real_selected_ids(mod);
		if (idstring.substr('0','1')==";")
			idstring = idstring.substr('1');
		var idarr = idstring.split(';');
		var count = idarr.length;
		var count = xx = count-1;
		if (idstring == "" || idstring == ";" || idstring == 'null')
		{
			alert(alert_arr.SELECT);
			return false;
		} else {
		//crmv@26921e
	//crmv@26807e
			//crmv@17001
		    if (parmod == 'Calendar' && mod == 'Contacts') {
		    	var namestr = '';
		    	for (var i=0;i<count;i++){
		    		if (trim(idarr[i]) != ''){
	                    if (isdefined('calendarCont'+idarr[i])){
	                    	var str=document.getElementById('calendarCont'+idarr[i]).innerHTML+"\n";
	                    }
	                    else {
	                    	var str=idarr[i]+"\n";
	                    }
		    			namestr +=str;
		    		}
		    	}
		    }
		    else if (parmod != 'Emails') //crmv@22366
		    {
		    	//crmv@21048m
	            var module = parent.document.getElementById('RLreturn_module').value
	            var entity_id = parent.document.getElementById('RLparent_id').value
	            var parenttab = parent.document.getElementById('parenttab').value
	            //crmv@21048m e
		    }
			//crmv@17001e
		    if(confirm(alert_arr.ADD_CONFIRMATION+xx+alert_arr.RECORDS)){
		        if (parmod == 'Calendar' && mod == 'Contacts')	//crmv@17001
	            {
			        //this blcok has been modified to provide delete option for contact in Calendar
			        idval = parent.document.EditView.contactidlist.value;//crmv@21048m
			        if(idval != '')
			        {
			            var avalIds = new Array();
			            avalIds = idstring.split(';');

			            var selectedIds = new Array();
			            selectedIds = idval.split(';');

			            for(i=0; i < (avalIds.length-1); i++)
			            {
			            	if (trim(avalIds[i]) == '') continue;
			                var rowFound=false;
			                for(k=0; k < selectedIds.length; k++)
			                {
			                    if (selectedIds[k]==avalIds[i])
			                    {
			                        rowFound=true;
			                        break;
			                    }

			                }
			                if(rowFound != true)
			                {
			                    idval = idval+';'+avalIds[i];
			                    parent.document.EditView.contactidlist.value = idval;//crmv@21048m
			                    if (isdefined('calendarCont'+avalIds[i])){
			                    	var str=document.getElementById('calendarCont'+avalIds[i]).innerHTML;
			                    }
			                    else {
			                    	var str=avalIds[i];
			                    }
			                    parent.addOption(avalIds[i],str);//crmv@21048m
			                }
			            }
			        }
			        else
			        {
			        	parent.document.EditView.contactidlist.value = idstring;//crmv@21048m
			        	var temp = new Array();
			        	temp = namestr.split('\n');
			        	var tempids = new Array();
			        	tempids = idstring.split(';');

			        	for(k=0; k < temp.length; k++)
			        	{
			        		if (trim(tempids[k]) == '') continue;
			        		parent.addOption(tempids[k],temp[k]);//crmv@21048m
			        	}
			        }
			        //end
	            }
		        //crmv@21048m
		        else
		        {
		        	parent.location.href="index.php?module="+module+"&parentid="+entity_id+"&action=updateRelations&destination_module="+mod+"&idlist="+idstring+"&parenttab="+parenttab;
		        }
		        //crmv@21048m e
		        closePopup();//crmv@22366
		    }
		} //crmv@26921
	}
}
//crmv@selectall fix end

var bSaf = (navigator.userAgent.indexOf('Safari') != -1);
var bOpera = (navigator.userAgent.indexOf('Opera') != -1);
var bMoz = (navigator.appName == 'Netscape');
function execJS(node) {
    var st = node.getElementsByTagName('SCRIPT');
    var strExec;
    for(var i=0;i<st.length; i++) {
      if (bSaf) {
        strExec = st[i].innerHTML;
      }
      else if (bOpera) {
        strExec = st[i].text;
      }
      else if (bMoz) {
        strExec = st[i].textContent;
      }
      else {
        strExec = st[i].text;
      }
      try {
        eval(strExec);
      } catch(e) {
        alert(e);
      }
    }
}

//Function added for getting the Tab Selected Values (Standard/Advanced Filters) for Custom View - Ahmed
//crmv@31775
function fnLoadCvValues(obj1,obj2,obj3,obj4,SelTab,unSelTab,unSelTab2,unSelTab3){

	var tabName1 = document.getElementById(obj1);
	var tagName1 = document.getElementById(SelTab);
	if(tabName1.className == "dvtUnSelectedCell") {
		tabName1.className = "dvtSelectedCell";
	}
	tagName1.style.display='block';

	if (obj2 != '' && isdefined(obj2) && isdefined(unSelTab)) {	//crmv@122071
		var tabName2 = document.getElementById(obj2);
		var tagName2 = document.getElementById(unSelTab);
		if(tabName2.className == "dvtSelectedCell") {
			tabName2.className = "dvtUnSelectedCell";
		}
		tagName2.style.display='none';
	}
	if (obj3 != '' && isdefined(obj3) && isdefined(unSelTab2)) { //crmv@33978
		var tabName3 = document.getElementById(obj3);
		var tagName3 = document.getElementById(unSelTab2);
		if(tabName3.className == "dvtSelectedCell") {
			tabName3.className = "dvtUnSelectedCell";
		}
		tagName3.style.display='none';
	}
	//crmv@OPER6288
	if (obj4 != '' && isdefined(obj4) && isdefined(unSelTab3)) {
		var tabName4 = document.getElementById(obj4);
		var tagName4 = document.getElementById(unSelTab3);
		if(tabName4.className == "dvtSelectedCell") {
			tabName4.className = "dvtUnSelectedCell";
		}
		tagName4.style.display='none';
	}
	//crmv@OPER6288e
}
//crmv@31775e

//crmv@59091 crmv@203081
function checkDivPosition(obj, showby, relto) {
	var $obj = jQuery(obj);
	var $showby = jQuery(showby);
	var $relto = jQuery(relto);

	if ($relto.length === 0) {
		if (Theme.getProperty('lateral_left_menu')) {
			$relto = jQuery('#mainContent');
		} else {
			$relto = jQuery('body');
		}
	}

	if ($obj.length > 0 && $showby.length > 0 && $relto.length > 0) {
		var parentPos = $relto.offset();
		var showByOffset = showby.offsetLeft;
		var showByLeft = $showby.position().left - showByOffset;
		var showByTop = $showby.position().top;
		var objWidth = $obj.outerWidth();
		var objHeight = $obj.outerHeight();
		var rightLimit = showByLeft + objWidth + parentPos.left;
		var bottomLimit = showByTop + objHeight + parentPos.top;

		var overflowX = (rightLimit) - jQuery(window).width();
		var overflowY = (bottomLimit) - jQuery(window).height() - jQuery(document).scrollTop();
		
		if (overflowX > 0) {
			var extraWidth = 0;
			extraWidth += jQuery(document).scrollTop() > 0 ? 19 : 0;
			
			if (Theme.getProperty('lateral_right_menu')) {
				extraWidth += jQuery('#rightPanel').outerWidth();
			}

			$obj.css('left', (showByLeft-overflowX-extraWidth)+"px");
		}

		if (overflowY > 0) {
			$obj.css('top', (showByTop-(overflowY))+"px");
		}
	}

	$obj.show();
}
//crmv@59091e crmv@203081e

// Drop Dwon Menu
function fnDropDown(obj,Lay,offsetTop){//crmv@22259
    var tagName = document.getElementById(Lay);
	if (!tagName) return;
    var leftSide = findPosX(obj);
    var topSide = findPosY(obj);
    var widthM = jQuery(tagName).width();
    var getVal = eval(leftSide) + eval(widthM);
	var browser = navigator.userAgent.toLowerCase();
	//crmv@22952
    if(getVal > document.body.clientWidth){
    	var diff = getVal - document.body.clientWidth;
        tagName.style.left = leftSide - diff + 'px';
    } else {
		tagName.style.left= leftSide + 'px';
	}
	//crmv@22259
	if (typeof offsetTop == 'undefined') {
		var offsetTop = 0;
	}
	//crmv@22259e
	//crmv@120023
	var barHeight = jQuery(obj).height() || 0;
	//crmv@22622
	topSide += barHeight + offsetTop;
	tagName.style.top = topSide + 'px'; //crmv@20253 //crmv@22259 //crmv@18592
	tagName.style.display = 'block';
	//crmv@22952e crmv@120023e
	tagName.style.zIndex = findZMax() + 1;
}

function fnShowDrop(obj){
    document.getElementById(obj).style.display = 'block';
}

function fnHideDrop(obj){
    document.getElementById(obj).style.display = 'none';
}

function showOverAll(currObj,id) {
	var olayernode = VteJS_DialogBox._olayer(true);
	olayernode.style.opacity = '0';

	var barHeight = jQuery('#vte_main_menu').height() || 0;
	fnDropDown(currObj,id); //crmv@120023
	document.getElementById(id).style.zIndex = findZMax()+1;
	jQuery('#'+id).appendTo(document.body);
	
	jQuery('#__vtejs_dialogbox_olayer__').click(function(){
		releaseOverAll(id);
	});
}
function releaseOverAll(id) {
	if (jQuery('#'+id).length == 0) return; 
	fnHideDrop(id);
	VteJS_DialogBox.unblock();
	jQuery('#__vtejs_dialogbox_olayer__').remove();
}

function getCalendarPopup(imageid,fieldid,dateformat){
	Calendar.setup ({
		inputField : fieldid, ifFormat : dateformat, showsTime : false, button : imageid, singleClick : true, step : 1
	});
}

/**to get SelectContacts Popup
check->to check select options enable or disable
*type->to differentiate from task
*frmName->form name*/

function selectContact(check,type,frmName,autocomplete)	//crmv@29190
{
	//crmv@21048m	//crmv@29190
	var record = document.getElementsByName("record")[0].value;
	if(jQuery("#single_accountid").length > 0)
	{
		var potential_id = '';
		if(jQuery("#potential_id").length > 0)
			potential_id = frmName.potential_id.value;
		account_id = frmName.account_id.value;
		if(potential_id != '')
		{
			record_id = potential_id;
			module_string = "&parent_module=Potentials";
		}
		else
		{
			record_id = account_id;
			module_string = "&parent_module=Accounts";
		}
		if(record_id == '' || autocomplete == 'yes')	//crmv@29190
			return "module=Contacts&action=Popup&html=Popup_picker&popuptype=specific&form=EditView";
		else
			return "module=Contacts&action=Popup&html=Popup_picker&popuptype=specific&form=EditView"+module_string+"&relmod_id="+record_id;
	}
	else if(jQuery("#parentid").length > 0 && type != 'task')
	{
		if(getObj("parent_type")){
			rel_parent_module = frmName.parent_type.value;
			record_id = frmName.parent_id.value;
			module = rel_parent_module.split("&");
			if(record_id != '' && module[0] == "Leads")
			{
				return "module=Contacts&action=Popup&html=Popup_picker&return_module=Calendar&select=enable&popuptype=detailview&form=EditView&form_submit=false"; //crmv@175761
			}
			else
			{
				if(check == 'true')
					search_string = "&return_module=Calendar&select=enable&popuptype=detailview&form_submit=false";
				else
					search_string="&popuptype=specific";
				if(record_id == '' || autocomplete == 'yes')	//crmv@29190
					return "module=Contacts&action=Popup&html=Popup_picker&form=EditView"+search_string;
				else
					return "module=Contacts&action=Popup&html=Popup_picker&form=EditView"+search_string+"&relmod_id="+record_id+"&parent_module="+module[0];
			}
		}else{
			return "module=Contacts&action=Popup&html=Popup_picker&return_module=Calendar&select=enable&popuptype=detailview&form=EditView&form_submit=false";
		}
	}
	else if(jQuery("#contact_name").length > 0 && type == 'task')
	{
		var formName = frmName.name;
		var task_recordid = '';
		if(formName == 'EditView')
		{
			if(jQuery("#parent_type").length > 0)
			{
				task_parent_module = frmName.parent_type.value;
				task_recordid = frmName.parent_id.value;
				task_module = task_parent_module.split("&");
				popuptype="&popuptype=specific";
			}
		}
		else
		{
			if(jQuery("#task_parent_type").length > 0)
			{
				task_parent_module = frmName.task_parent_type.value;
				task_recordid = frmName.task_parent_id.value;
				task_module = task_parent_module.split("&");
				popuptype="&popuptype=toDospecific";
			}
		}
		if(task_recordid != '' && task_module[0] == "Leads" )
		{
			//crmv@31556
			var formName = frmName.name;
   			return "module=Contacts&action=Popup&html=Popup_picker&popuptype=specific&form="+formName;
   			//crmv@31556e
		}
		else
		{
			//crmv@23220
			if(task_recordid == '' || autocomplete == 'yes')	//crmv@29190
				return "module=Contacts&action=Popup&html=Popup_picker&popuptype=specific&form="+formName;
			else
				return "module=Contacts&action=Popup&html=Popup_picker"+popuptype+"&form="+formName+"&task_relmod_id="+task_recordid+"&task_parent_module="+task_module[0];
		}
	}
	else
	{
		var formName = frmName.name;
		return "module=Contacts&action=Popup&html=Popup_picker&popuptype=specific&form="+formName;
	}
	//crmv@ds28 workflow
	if (jQuery("#get_users_list").length > 0){
		var formName = frmName.name;
		return "module=Users&action=Popup&html=Popup_picker&popuptype=specific&form="+formName;
	}
	//crmv@ds28 end
	//crmv@23220 end
	//crmv@21048me	//crmv@29190e
}
//to get Select Potential Popup
function selectPotential()
{
	// To support both B2B and B2C model
	var record_id = '';
	var parent_module = '';
	var acc_element = document.EditView.account_id;
	var cnt_element = document.EditView.contact_id;
	if (acc_element != null) {
		record_id= acc_element.value;
		parent_module = 'Accounts';
	} else if (cnt_element != null) {
		record_id= cnt_element.value;
		parent_module = 'Contacts';
	}
	//crmv@21048m	//crmv@29190
	if(record_id != '')
		var options = "&relmod_id="+record_id+"&parent_module="+parent_module;
	else
		var options = '';
	return "module=Potentials&action=Popup&html=Popup_picker&popuptype=specific_potential_account_address&form=EditView"+options;
	//crmv@21048me	//crmv@29190e
}
//to select Quote Popup
function selectQuote()
{
	// To support both B2B and B2C model
	var record_id = '';
	var parent_module = '';
	var acc_element = document.EditView.account_id;
	var cnt_element = document.EditView.contact_id;
	if (acc_element != null) {
		record_id= acc_element.value;
		parent_module = 'Accounts';
	} else if (cnt_element != null) {
		record_id= cnt_element.value;
		parent_module = 'Contacts';
	}
	//crmv@21048m	//crmv@29190
	if(record_id != '')
		var options = "&relmod_id="+record_id+"&parent_module="+parent_module;
	else
		var options = '';
	return "module=Quotes&action=Popup&html=Popup_picker&popuptype=specific&form=EditView"+options;
	//crmv@21048me	//crmv@29190e
}
//to get select SalesOrder Popup
function selectSalesOrder()
{
	// To support both B2B and B2C model
	var record_id = '';
	var parent_module = '';
	var acc_element = document.EditView.account_id;
	var cnt_element = document.EditView.contact_id;
	if (acc_element != null) {
		record_id= acc_element.value;
		parent_module = 'Accounts';
	} else if (cnt_element != null) {
		record_id= cnt_element.value;
		parent_module = 'Contacts';
	}
	//crmv@21048m	//crmv@29190
	if(record_id != '')
		var options = "&relmod_id="+record_id+"&parent_module="+parent_module;
	else
		var options = '';
	return "module=SalesOrder&action=Popup&html=Popup_picker&popuptype=specific&form=EditView"+options;
	//crmv@21048me	//crmv@29190e
}

function checkEmailid(parent_module,emailid,yahooid)
 {
       var check = true;
       if(emailid == '' && yahooid == '')
       {
               alert(alert_arr.LBL_THIS+parent_module+alert_arr.DOESNOT_HAVE_MAILIDS);
               check=false;
       }
       return check;
 }

function calQCduedatetime()
{
        var datefmt = document.QcEditView.dateFormat.value;
        var type = document.QcEditView.activitytype.value;
        var dateval1=getObj('date_start').value.replace(/^\s+/g, '').replace(/\s+$/g, '');
        var dateelements1=splitDateVal(dateval1);
        dd1=parseInt(dateelements1[0],10);
        mm1=dateelements1[1];
        yyyy1=dateelements1[2];
        var date1=new Date();
        date1.setYear(yyyy1);
        date1.setMonth(mm1-1,dd1+1);
        var yy = date1.getFullYear();
        var mm = date1.getMonth() + 1;
        var dd = date1.getDate();
        var date = document.QcEditView.date_start.value;
        var starttime = document.QcEditView.time_start.value;
        if (!timeValidate('time_start',' Start Date & Time','OTH'))
                return false;
        var timearr = starttime.split(":");
        var hour = parseInt(timearr[0],10);
        var min = parseInt(timearr[1],10);
        dd = _2digit(dd);
        mm = _2digit(mm);
        var tempdate = yy+'-'+mm+'-'+dd;
        if(datefmt == '%d-%m-%Y')
                var tempdate = dd+'-'+mm+'-'+yy;
        else if(datefmt == '%m-%d-%Y')
                var tempdate = mm+'-'+dd+'-'+yy;
        if(type == 'Meeting')
        {
                hour = hour + 1;
                if(hour == 24)
                {
                        hour = 0;
                        date =  tempdate;
                }
                hour = _2digit(hour);
        min = _2digit(min);
                document.QcEditView.due_date.value = date;
                document.QcEditView.time_end.value = hour+':'+min;
        }
        if(type == 'Call')
        {
                if(min >= 55)
                {
                        min = min%55;
                        hour = hour + 1;
                }else min = min + 5;
                if(hour == 24)
                {
                        hour = 0;
                        date =  tempdate;
                }
                hour = _2digit(hour);
        min = _2digit(min);
                document.QcEditView.due_date.value = date;
                document.QcEditView.time_end.value = hour+':'+min;
        }

}

function _2digit( no ){
        if(no < 10) return "0" + no;
        else return "" + no;
}

//crmv@15157 crmv@144123 crmv@152978
function confirmdelete(url,module) {
	// get the return module and action
	var params = {};
	url.replace(/^.*\?/, '').split('&').forEach(function(el) {
		if (el) {
			var part = el.split('=');
			params[part[0]] = part[1];
		}
	});
	var isRelated = (params.return_action == 'DetailView' && params.return_module != module);
	var alert_str = alert_arr.ARE_YOU_SURE;
	
	if (!isRelated) {
		if (module == 'Accounts') {
			alert_str = alert_arr.DELETE_ACCOUNT;
		} else if (module == 'Contacts') {
			alert_str = alert_arr.DELETE_CONTACT;
		}
	}
	
	vteconfirm(alert_str, function(yes) {
		if (yes) {
			document.location.href=url;
		}
	});
}
//crmv@15157e crmv@144123e crmv@152978e

//function modified to apply the patch ref : Ticket #4065
function valid(c,type)
{
    if(type == 'name')
    {
        return (((c >= 'a') && (c <= 'z')) ||((c >= 'A') && (c <= 'Z')) ||((c >= '0') && (c <= '9')) || (c == '.') || (c == '_') || (c == '-') || (c == '@') );
    }
    else if(type == 'namespace')
    {
        return (((c >= 'a') && (c <= 'z')) ||((c >= 'A') && (c <= 'Z')) ||((c >= '0') && (c <= '9')) || (c == '.')||(c==' ') || (c == '_') || (c == '-') );
    }
}
//end

function CharValidation(s,type)
{
    for (var i = 0; i < s.length; i++)
    {
        if (!valid(s.charAt(i),type))
        {
            return false;
        }
    }
    return true;
}

// crmv@104568
/**
 * Focus a field specified by id, name, dom object or jquery object
 */
function focusField(field) {
	if (typeof field == 'string') {
		// it's a fieldname
		var obj = getObj(field);
		if (obj) obj = jQuery(obj);
	} else {
		var obj = jQuery(field);
	}
	
	if (obj && obj.length > 0) {
		if (window.panelBlocks) {
			// there are panels, check if the field is in the current one, otherwise change panel
			var fieldpanel = getPanelidForField(field),
				panelid = getCurrentPanelid();
			if (fieldpanel > 0 && panelid > 0 && fieldpanel != panelid) {
				// ok, change!
				changeTab(gVTModule, null, fieldpanel);
			}
		}
		try {
			obj.focus();
		} catch(e) {
			// ignore focus errors
		}
	}
}

function getPanelidForField(field) {
	if (typeof field == 'string') {
		// it's a fieldname
		var obj = getObj(field);
		if (obj) obj = jQuery(obj);
	} else {
		var obj = jQuery(field);
	}
	
	if (obj && obj.length > 0 && window.panelBlocks) {
		var cont = obj.closest('div.editBlock');
		if (cont.length == 0) {
			cont = obj.closest('div.detailBlock');
		}
		if (cont.length > 0) {
			// found the block container!
			var blockid = parseInt(cont.attr('id').replace('block_', ''));
			if (blockid > 0) {
				for (panelid in panelBlocks) {
					if (panelBlocks[panelid].blockids.indexOf(blockid) >= 0) {
						return panelid;
					}
				}
			}
		}
	}
	return null;
}

function getPanelidForRelation(relationid) {
	relationid = parseInt(relationid);
	
	for (panelid in panelBlocks) {
		if (panelBlocks[panelid].relatedids.indexOf(relationid) >= 0) {
			return panelid;
		}
	}
	
	return null;
}

function getCurrentPanelid() {
	
	// crmv@140129
	if ('currentPanelId' in window) {
		return currentPanelId;
	}
	// crmv@140129e
	
	var cont = jQuery('#EditViewTabs');
	
	if (cont.length == 0) {
		cont = jQuery('#DetailViewTabs');
	}
	
	if (cont.length > 0) {
		var sel = cont.find('td.dvtSelectedCell');
		if (sel.length > 0) {
			var panelid = parseInt(sel.data('panelid'));
			return panelid;
		}
	}
}
// crmv@104568e


/** Check Upload file is in specified format(extension).
  * @param fldname -- name of the file field
  * @param fldLabel -- Lable of the file field
  * @param filter -- List of file extensions to allow. each extension must be seperated with a | sybmol.
  * Example: upload_filter("imagename","Image", "jpg|gif|bmp|png")
  * @returns true -- if the extension is IN  specified extension.
  * @returns false -- if the extension is NOT IN specified extension.
  *
  * NOTE: If this field is mandatory,  please call emptyCheck() function before calling this function.
 */

function upload_filter(fldName, filter)
{
	var currObj=getObj(fldName)
	if(currObj.value !="")
	{
		var file=currObj.value;
		var type=file.split(".");
		var valid_extn=filter.split("|");

		if(valid_extn.indexOf(type[type.length-1]) == -1)
		{
			alert(alert_arr.PLS_SELECT_VALID_FILE+valid_extn, function(){
				try {
					currObj.focus()
				} catch(error) {
					// Fix for IE: If element or its wrapper around it is hidden, setting focus will fail
					// So using the try { } catch(error) { }
				}
			});
		 	return false;
		}
	}
	return true

}

function validateUrl(name)
{
    var Url = getObj(name);
    var wProtocol;

    var oRegex = new Object();
    oRegex.UriProtocol = new RegExp('');
    oRegex.UriProtocol.compile( '^(((http|https|ftp|news):\/\/)|mailto:)', 'gi' );
    oRegex.UrlOnChangeProtocol = new RegExp('') ;
    oRegex.UrlOnChangeProtocol.compile( '^(http|https|ftp|news)://(?=.)', 'gi' );

    wUrl = Url.value;
    wProtocol=oRegex.UrlOnChangeProtocol.exec( wUrl ) ;
    if ( wProtocol )
    {
        wUrl = wUrl.substr( wProtocol[0].length );
        Url.value = wUrl;
    }
}

// crmv@80653
function validateGenericUrl(element, name) {
	var allowProtocols = ['http', 'https', 'ftp', 'ftps', 'news', 'sftp'],
		re = new RegExp('^('+allowProtocols.join('|')+'):\/\/', 'i'),
		url = jQuery(element).val();
	
	if (!url) return;
	if (!url.match(re)) {
		// clean unknown protocol
		url = url.replace(/^[a-z]+:\/\//i, '');
		// put default http
		url = 'http://' + url;
		jQuery(element).val(url);
	}
	
	return url;
}
// crmv@80653e


function LTrim( value )
{

        var re = /\s*((\S+\s*)*)/;
        return value.replace(re, "$1");

}

function selectedRecords(module,category)
{
    var idstring = get_real_selected_ids(module);
    if(idstring != '')
            window.location.href="index.php?module="+module+"&action=ExportRecords&parenttab="+category+"&idstring="+idstring;
    else
            window.location.href="index.php?module="+module+"&action=ExportRecords&parenttab="+category;
    return false;
}

function record_export(module,category,exform,idstring)
{
    var searchType = document.getElementsByName('search_type');
    var exportData = document.getElementsByName('export_data');
    for(var i=0;i<2;i++){
        if(searchType[i].checked == true)
            var sel_type = searchType[i].value;
    }
    for(var i=0;i<3;i++){
        if(exportData[i].checked == true)
            var exp_type = exportData[i].value;
    }
    
    jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module="+module+"&action=ExportAjax&export_record=true&search_type="+sel_type+"&export_data="+exp_type+"&idstring="+idstring,
		success: function(result) {
			if(result == 'NOT_SEARCH_WITHSEARCH_ALL') {
				jQuery('#not_search').show();
				jQuery('#not_search').html("<font color='red'><b>"+alert_arr.LBL_NOTSEARCH_WITHSEARCH_ALL+" "+module+"</b></font>");
				setTimeout(hideErrorMsg1,6000);
				exform.submit();
			} else if (result == 'NOT_SEARCH_WITHSEARCH_CURRENTPAGE') {
				jQuery('#not_search').show();
				jQuery('#not_search').html("<font color='red'><b>"+alert_arr.LBL_NOTSEARCH_WITHSEARCH_CURRENTPAGE+" "+module+"</b></font>");
				setTimeout(hideErrorMsg1,7000);
				exform.submit();
			} else if(result == 'NO_DATA_SELECTED') {
				jQuery('#not_search').show();
				jQuery('#not_search').html("<font color='red'><b>"+alert_arr.LBL_NO_DATA_SELECTED+"</b></font>");
				setTimeout(hideErrorMsg1,3000);
			} else if(result == 'SEARCH_WITHOUTSEARCH_ALL') {
				if(confirm(alert_arr.LBL_SEARCH_WITHOUTSEARCH_ALL)) {
					exform.submit();
				}
			} else if(result == 'SEARCH_WITHOUTSEARCH_CURRENTPAGE') {
				if(confirm(alert_arr.LBL_SEARCH_WITHOUTSEARCH_CURRENTPAGE)) {
					exform.submit();
				}
			} else {
				exform.submit();
			}
		}
	});
}


function hideErrorMsg1() {
        jQuery('#not_search').hide();
}

// Replace the % sign with %25 to make sure the AJAX url is going wel.
function escapeAll(tagValue)
{
        //return escape(tagValue.replace(/%/g, '%25'));
    if(default_charset.toLowerCase() == 'utf-8')
            return encodeURIComponent(tagValue.replace(/%/g, '%25'));
    else
        return escape(tagValue.replace(/%/g, '%25'));
}

function removeHTMLFormatting(str) {
        str = str.replace(/<([^<>]*)>/g, " ");
        str = str.replace(/&nbsp;/g, " ");
        return str;
}
function get_converted_html(str)
{
        var temp = str.toLowerCase();
        if(temp.indexOf('<') != '-1' || temp.indexOf('>') != '-1')
        {
                str = str.replace(/</g,'&lt;');
                str = str.replace(/>/g,'&gt;');
        }
    if( temp.match(/(script).*(\/script)/))
        {
                str = str.replace(/&/g,'&amp;');
        }
        else if(temp.indexOf('&') != '-1')
        {
                str = str.replace(/&/g,'&amp;');
    }
    return str;
}
//To select the select all check box(if all the items are selected) when the form loads.
function default_togglestate(obj_id,elementId)
{
	var all_state=true;
	var groupElements = document.getElementsByName(obj_id);
	for (var i=0;i<groupElements.length;i++) {
		var state=groupElements[i].checked;
		if (state == false)
		{
			all_state=false;
			break;
		}
	}
	if(typeof elementId=='undefined'){
		elementId = 'selectall';
	}
	if(getObj(elementId)) {
		getObj(elementId).checked=all_state;
	}
}

//for select  multiple check box in multiple pages for Campaigns related list:

function rel_check_object(sel_id,module)
{
        var selected;
        var select_global=new Array();
        var cookie_val=get_cookie(module+"_all");
        if(cookie_val == null)
                selected=sel_id.value+";";
        else
                selected=trim(cookie_val);
        select_global=selected.split(";");
        var box_value=sel_id.checked;
        var id= sel_id.value;
        var duplicate=select_global.indexOf(id);
        var size=select_global.length-1;
        var result="";
        //crmv@ds47
        if(box_value == true)
        {
                if(duplicate == "-1")
                {
                        select_global[size]=id;
                }

                size=select_global.length-1;
                var i=0;
                for(i=0;i<=size;i++)
                {
                        if(trim(select_global[i])!='')	//crmv@19139
                                result=select_global[i]+";"+result;
                }
                rel_default_togglestate(module);

        }
        else
        {
                if(duplicate != "-1")

            select_global.splice(duplicate,1)

                size=select_global.length-1;
                var i=0;
                for(i=size;i>=0;i--)
                {
                        if(trim(select_global[i])!='')	//crmv@19139
                                result=select_global[i]+";"+result;
                }
                        getObj(module+"_selectall").checked=false;

        }
        //crmv@ds47 end
        set_cookie(module+"_all",result);
}

//Function to select all the items in the current page for Campaigns related list:.
function rel_toggleSelect(state,relCheckName,module) {
        if (getObj(relCheckName)) {
                if (typeof(getObj(relCheckName).length)=="undefined") {
                        getObj(relCheckName).checked=state
                } else
                {
                        for (var i=0;i<getObj(relCheckName).length;i++)
                        {
                                getObj(relCheckName)[i].checked=state
                                        rel_check_object(getObj(relCheckName)[i],module)
                        }
                }
        }
}
//To select the select all check box(if all the items are selected) when the form loads for Campaigns related list:.
function rel_default_togglestate(module)
{
	var all_state=true;
	var groupElements = document.getElementsByName(module+"_selected_id");
	if(typeof(groupElements) == 'undefined') return;

	for (var i=0;i<groupElements.length;i++) {
		var state=groupElements[i].checked;
		if (state == false)
		{
			all_state=false;
			break;
		}
	}
	if(getObj(module+"_selectall")) {
		getObj(module+"_selectall").checked=all_state;
	}
}
//To clear all the checked items in all the pages for Campaigns related list:
function clear_checked_all(module)
{
	var cookie_val=get_cookie(module+"_all");
	if(cookie_val != null)
		delete_cookie(module+"_all");
	//Uncheck all the boxes in current page..
	var obj = document.getElementsByName(module+"_selected_id");
	if (obj) {
		for (var i=0;i<obj.length;i++) {
			obj[i].checked=false;
		}
	}
	if(getObj(module+"_selectall")) {
		getObj(module+"_selectall").checked=false;
	}
}
//groupParentElementId is added as there are multiple groups in Documents listview.
function toggleSelect_ListView(state,relCheckName,groupParentElementId) {
    var obj = document.getElementsByName(relCheckName);
	if (obj) {
        for (var i=0;i<obj.length;i++) {
          	obj[i].checked=state;
			if(typeof(check_object) == 'function') {
				// This function is defined in ListView.js (check for existence)
				check_object(obj[i],groupParentElementId);
			}
        }
    }
}
//crmv@fix listview
function toggleSelect_ListView2(state,relCheckName)
{

   	if (getObj(relCheckName))
    {
    		if (typeof(getObj(relCheckName).length)=="undefined")
        {
    			getObj(relCheckName).checked=state

    			updateIdlist(state,getObj(relCheckName).value)

    		} else {
      			for (var i=0;i<getObj(relCheckName).length;i++)
      			{
      			  obj_check = getObj(relCheckName)[i];

              obj_check.checked = state;

      				updateIdlist(state,obj_check.value)
            }
    		}
  	}
}

function updateIdlist(obj_checked,obj_value)
{
    idstring = document.getElementById('idlist').value;
    if (idstring == "") idstring = ";";

    if (obj_checked == true)
    {
       idstring = idstring.replace(";" + obj_value + ";", ";");
       document.getElementById('idlist').value = idstring + obj_value + ";";
    }
    else
    {
       newidstring = idstring.replace(";" + obj_value + ";", ";");
       document.getElementById('idlist').value = newidstring;
    }
}
//crmv@fix listview end
function gotourl(url)
{
                document.location.href=url;
}

//crmv@ds2  add new funciton for INFO/DESCRIPTION POPUP
function showInfoWindow(thiss,entity_id,title)
{
    document.getElementById('wlastcontactLV_title').innerHTML = "<b>" + title + "</b>";
    document.getElementById('wlastcontactLV_content').innerHTML = "<img src='Image/ajax-loader.gif'>";

    var url = "module=Accounts&action=AccountsAjax&file=Save&lc_check=true&last_contact="+entity_id;
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: url,
		success: function(result) {
			jQuery('#wlastcontactLV_content').html(result);
		}
	})

    fnvshobj2(thiss,'wlastcontactLV');
}

function hideInfoWindow()
{
    document.getElementById('wlastcontactLV_title').innerHTML = "";
    document.getElementById('wlastcontactLV_content').innerHTML = "";
    fninvsh('wlastcontactLV');
}
//crmv@ds2end

//crmv@7231	//crmv@19653
function AjaxDuplicateValidateEXT_CODE(module,fieldname,fieldvalue,mode)
{
	if (mode != 'ajax')
		var fieldvalue = getObj(fieldname).value;
	var crmId=getObj('record').value;
	var url = "module="+module+"&action="+module+"Ajax&file=Save&"+fieldname+"="+fieldvalue+"&EXT_CODE=true&record="+crmId;
	str = getFile('index.php?'+url);
	if ( (str!="false") && (str!="duplicate") && (str!="owner")) {
		if (confirm(alert_arr.LBL_ALERT_EXT_CODE)){
					var url = "module="+module+"&action=Save&MergeCode=true&idEXT="+str+"&idCRM="+crmId;	//crmv@26320
					strss = getFile('index.php?'+url);
					if (strss=="true") {
						alert (alert_arr.LBL_ALERT_EXT_CODE_COMMIT);
						document.location.href="index.php?module="+module+"&action=DetailView&record="+str+"&parenttab=Marketing";
					}
					else alert (alert_arr.LBL_ALERT_EXT_CODE_FAIL);
		}
		else {
			if (mode != 'ajax') oform.external_code.value='';
			return false;
		}
	}
	else {
		if (str=="duplicate") {
			alert(alert_arr.LBL_ALERT_EXT_CODE_DUPLICATE);
			if (mode != 'ajax') oform.external_code.value='';
			return false;
		}
		else if (str=="owner") {
			alert(alert_arr.LBL_ALERT_EXT_CODE_NO_PERMISSION)
			if (mode != 'ajax') oform.external_code.value='';
			return false;
		}
		else if (str=="false") {
			if (confirm(alert_arr.LBL_ALERT_EXT_CODE_NOTFOUND_SAVE)){
				if (mode != 'ajax') oform.action.value='Save';
				return true;
			}
			else {
				if (mode != 'ajax') oform.external_code.value='';
				return false;
			}
		}
	}
}
//crmv@19653e
//crmv@7216
function InternalFax(record_id,field_id,field_name,par_module,type) {
        var url;
        switch(type) {
                case 'record_id':
                        url = 'index.php?module=Fax&action=FaxAjax&internal_mailer=true&type='+type+'&field_id='+field_id+'&rec_id='+record_id+'&fieldname='+field_name+'&file=EditView&par_module='+par_module;//query string field_id added for listview-compose email issue
                break;
                case 'email_addy':
                        url = 'index.php?module=Fax&action=FaxAjax&internal_mailer=true&type='+type+'&email_addy='+record_id+'&file=EditView';
                break;

        }

        var opts = "menubar=no,toolbar=no,location=no,status=no,resizable=yes,scrollbars=yes";
        openPopUp('xComposeFax',this,url,'createfaxWin',830,362,opts);
}
function ShowFax(id)
{
	url = 'index.php?module=Fax&action=FaxAjax&file=DetailView&record='+id;
	openPopUp('xComposeFax',this,url,'createfaxWin',830,362,'menubar=no,toolbar=no,location=no,status=no,resizable=no,scrollbars=yes');
}
function OpenComposeFax(id,mode)
{
    switch(mode)
    {
        case 'edit':
            url = 'index.php?module=Fax&action=FaxAjax&file=EditView&record='+id;
            break;
        case 'create':
            url = 'index.php?module=Fax&action=FaxAjax&file=EditView';
            break;
        case 'forward':
            url = 'index.php?module=Fax&action=FaxAjax&file=EditView&record='+id+'&forward=true';
            break;
        case 'Invoice':
			url = 'index.php?module=Fax&action=FaxAjax&file=EditView&attachment='+mode+'.pdf';
            break;
    }
    openPopUp('xComposeFax',this,url,'createfaxWin',830,362,'menubar=no,toolbar=no,location=no,status=no,resizable=no,scrollbars=yes');
}
//crmv@7216e

//crmv@7217
function OpenComposeSms(id,mode)
{
    switch(mode)
    {
        case 'edit':
            url = 'index.php?module=Sms&action=SmsAjax&file=EditView&record='+id;
            break;
        case 'create':
            url = 'index.php?module=Sms&action=SmsAjax&file=EditView';
            break;
        case 'forward':
            url = 'index.php?module=Sms&action=SmsAjax&file=EditView&record='+id+'&forward=true';
            break;
    }
    openPopUp('xComposeSms',this,url,'createsmsWin',830,540,'menubar=no,toolbar=no,location=no,status=no,resizable=no,scrollbars=yes');
}
//crmv@7217e
//crmv@8719
//added for finding duplicates
function movefields()
{
	availListObj=getObj("availlist")
	selectedColumnsObj=getObj("selectedCol")
	for (i=0;i<selectedColumnsObj.length;i++)
	{

		selectedColumnsObj.options[i].selected=false
	}

	movefieldsStep1();
}

function movefieldsStep1()
{

	availListObj=getObj("availlist")
	selectedColumnsObj=getObj("selectedCol")
	document.getElementById("selectedCol").style.width="164px";
	var count=0;
	for(i=0;i<availListObj.length;i++)
	{
			if (availListObj.options[i].selected==true)
			{
				count++;
			}

	}
	var total_fields=count+selectedColumnsObj.length;
	if (total_fields >4 )
	{
		alert(alert_arr.MAX_RECORDS)
			return false
	}
	if (availListObj.options.selectedIndex > -1)
	{
		for (i=0;i<availListObj.length;i++)
		{
			if (availListObj.options[i].selected==true)
			{
				var rowFound=false;
				for (j=0;j<selectedColumnsObj.length;j++)
				{
					selectedColumnsObj.options[j].value==availListObj.options[i].value;
					if (selectedColumnsObj.options[j].value==availListObj.options[i].value)
					{
						var rowFound=true;
						var existingObj=selectedColumnsObj.options[j];
						break;
					}
				}

				if (rowFound!=true)
				{
					var newColObj=document.createElement("OPTION")
					newColObj.value=availListObj.options[i].value
					if (browser_ie) newColObj.innerText=availListObj.options[i].innerText
					else if (browser_nn4 || browser_nn6) newColObj.text=availListObj.options[i].text
					selectedColumnsObj.appendChild(newColObj)
					newColObj.selected=true
				}
				else
				{
					existingObj.selected=true
				}
				availListObj.options[i].selected=false
				movefieldsStep1();
			}
		}
	}
}

function selectedColClick(oSel)
{
	if (oSel.selectedIndex == -1 || oSel.options[oSel.selectedIndex].disabled == true)
	{
		alert(alert_arr.NOT_ALLOWED_TO_EDIT);
		oSel.options[oSel.selectedIndex].selected = false;
	}
}

function delFields()
{
	selectedColumnsObj=getObj("selectedCol");
	selected_tab = jQuery("#dupmod").val();
	if (selectedColumnsObj.options.selectedIndex > -1)
	{
		for (i=0;i < selectedColumnsObj.options.length;i++)
		{
			if(selectedColumnsObj.options[i].selected == true)
			{
				if(selected_tab == 4)
				{
					if(selectedColumnsObj.options[i].innerHTML == "Last Name")
					{
						alert(alert_arr.DEL_MANDATORY);
						del = false;
						return false;
					}
					else
						del = true;

				}
				else if(selected_tab == 7)
				{
					if(selectedColumnsObj.options[i].innerHTML == "Last Name" || selectedColumnsObj.options[i].innerHTML == "Company")
					{
						alert(alert_arr.DEL_MANDATORY);
						del = false;
						return false;
					}
					else
						del = true;
				}
				else if(selected_tab == 6)
				{
					if(selectedColumnsObj.options[i].innerHTML == "Account Name")
					{
						alert(alert_arr.DEL_MANDATORY);
						del = false;
						return false;
					}
					else
						del = true;
				}
				else if(selected_tab == 14)
				{
					if(selectedColumnsObj.options[i].innerHTML == "Product Name")
					{
						alert(alert_arr.DEL_MANDATORY);
						del = false;
						return false;
					}
					else
						del = true;
				}
				if(del == true)
				{
					selectedColumnsObj.remove(i);
					delFields();
				}
			}
		}
	}
}

function moveFieldUp()
{
	selectedColumnsObj=getObj("selectedCol")
	var currpos=selectedColumnsObj.options.selectedIndex
	var tempdisabled= false;
	for (i=0;i<selectedColumnsObj.length;i++)
	{
		if(i != currpos)
			selectedColumnsObj.options[i].selected=false
	}
	if (currpos>0)
	{
		var prevpos=selectedColumnsObj.options.selectedIndex-1

		if (browser_ie)
		{
			temp=selectedColumnsObj.options[prevpos].innerText
			tempdisabled = selectedColumnsObj.options[prevpos].disabled;
			selectedColumnsObj.options[prevpos].innerText=selectedColumnsObj.options[currpos].innerText
			selectedColumnsObj.options[prevpos].disabled = false;
			selectedColumnsObj.options[currpos].innerText=temp
			selectedColumnsObj.options[currpos].disabled = tempdisabled;
		}
		else if (browser_nn4 || browser_nn6)
		{
			temp=selectedColumnsObj.options[prevpos].text
			tempdisabled = selectedColumnsObj.options[prevpos].disabled;
			selectedColumnsObj.options[prevpos].text=selectedColumnsObj.options[currpos].text
			selectedColumnsObj.options[prevpos].disabled = false;
			selectedColumnsObj.options[currpos].text=temp
			selectedColumnsObj.options[currpos].disabled = tempdisabled;
		}
		temp=selectedColumnsObj.options[prevpos].value
		selectedColumnsObj.options[prevpos].value=selectedColumnsObj.options[currpos].value
		selectedColumnsObj.options[currpos].value=temp
		selectedColumnsObj.options[prevpos].selected=true
		selectedColumnsObj.options[currpos].selected=false
		}

}

function moveFieldDown()
{
	selectedColumnsObj=getObj("selectedCol")
	var currpos=selectedColumnsObj.options.selectedIndex
	var tempdisabled= false;
	for (i=0;i<selectedColumnsObj.length;i++)
	{
		if(i != currpos)
			selectedColumnsObj.options[i].selected=false
	}
	if (currpos<selectedColumnsObj.options.length-1)
	{
		var nextpos=selectedColumnsObj.options.selectedIndex+1

		if (browser_ie)
		{
			temp=selectedColumnsObj.options[nextpos].innerText
			tempdisabled = selectedColumnsObj.options[nextpos].disabled;
			selectedColumnsObj.options[nextpos].innerText=selectedColumnsObj.options[currpos].innerText
			selectedColumnsObj.options[nextpos].disabled = false;
			selectedColumnsObj.options[nextpos];

			selectedColumnsObj.options[currpos].innerText=temp
			selectedColumnsObj.options[currpos].disabled = tempdisabled;
		}
		else if (browser_nn4 || browser_nn6)
		{
			temp=selectedColumnsObj.options[nextpos].text
			tempdisabled = selectedColumnsObj.options[nextpos].disabled;
			selectedColumnsObj.options[nextpos].text=selectedColumnsObj.options[currpos].text
			selectedColumnsObj.options[nextpos].disabled = false;
			selectedColumnsObj.options[nextpos];
			selectedColumnsObj.options[currpos].text=temp
			selectedColumnsObj.options[currpos].disabled = tempdisabled;
		}
		temp=selectedColumnsObj.options[nextpos].value
		selectedColumnsObj.options[nextpos].value=selectedColumnsObj.options[currpos].value
		selectedColumnsObj.options[currpos].value=temp

		selectedColumnsObj.options[nextpos].selected=true
		selectedColumnsObj.options[currpos].selected=false
	}
}

function lastImport(module,req_module)
{
	var module_name= module;
	var parent_tab= document.getElementById('parenttab').value;
	if(module == '')
	{
		return false;
	}
	else

		//alert("index.php?module="+module_name+"&action=lastImport&req_mod="+req_module+"&parenttab="+parent_tab);
		openPopup("index.php?module="+module_name+"&action=lastImport&req_mod="+req_module+"&parenttab="+parent_tab,"lastImport","width=750,height=602,menubar=no,toolbar=no,location=no,status=no,resizable=no,scrollbars=yes");//crmv@21048m
}

function merge_fields(selectedNames,module,parent_tab)
{

		var select_options=document.getElementsByName(selectedNames);
		var x= select_options.length;
		var req_module=module;
		var num_group=jQuery("#group_count").html();
		var pass_url="";
		var flag=0;
		//var i=0;
		var xx = 0;
		for(i = 0; i < x ; i++)
		{
			if(select_options[i].checked)
			{
				pass_url = pass_url+select_options[i].value +","
				xx++
			}
		}
		var tmp = 0
		if ( xx != 0)
		{

			if(xx > 3)
			{
				alert(alert_arr.MAX_THREE)
					return false;
			}
			if(xx > 0)
			{
				for(j=0;j<num_group;j++)
				{
					flag = 0
					var group_options=document.getElementsByName("group"+j);
					for(i = 0; i < group_options.length ; i++)
						{
							if(group_options[i].checked)
							{
								flag++
							}
						}
					if(flag > 0)
					tmp++;
				}
				if (tmp > 1)
				{
				alert(alert_arr.SAME_GROUPS)
				return false;
				}
				if(xx <2)
				{
					alert(alert_arr.ATLEAST_TWO)
					return false;
				}

			}

			openPopup("index.php?module="+req_module+"&action=ProcessDuplicates&mergemode=mergefields&passurl="+pass_url+"&parenttab="+parent_tab,"Merge","width=750,height=602,menubar=no,toolbar=no,location=no,status=no,resizable=no,scrollbars=yes");//crmv@21048m
		}
		else
		{
			alert(alert_arr.ATLEAST_TWO);
			return false;
		}
}

function delete_fields(module)
{
	var select_options=document.getElementsByName('del');
	var x=select_options.length;
	var xx=0;
	url_rec="";

	for(var i=0;i<x;i++)
	{
		if(select_options[i].checked)
		{
		url_rec=url_rec+select_options[i].value +","
		xx++
		}
	}
	if(jQuery("#current_action").length > 0)
		cur_action = jQuery("#current_action").html();
	if (xx == 0)
	{
	    alert(alert_arr.SELECT);
	    return false;
	}
	if(module=="Accounts") {
		if (xx == 1) var alert_str = sprintf(alert_arr.DELETE_ACCOUNT, xx);
		else var alert_str = sprintf(alert_arr.DELETE_ACCOUNTS, xx);
	} else {
		if (xx == 1) var alert_str = sprintf(alert_arr.DELETE_RECORD, xx);
		else var alert_str = sprintf(alert_arr.DELETE_RECORDS, xx);
	}
	if(confirm(alert_str))
	{
		jQuery('#status').show();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: "module="+module+"&action="+module+"Ajax&file=FindDuplicateRecords&del_rec=true&ajax=true&return_module="+module+"&idlist="+url_rec+"&current_action="+cur_action+"&"+dup_start,
			success: function(result) {
				jQuery('#status').hide();
				jQuery("#duplicate_ajax").html(result);
			}
		});
	}
	else
		return false;
}


function validate_merge(module)
{
	var check_var=false;
	var check_lead1=false;
	var check_lead2=false;

	var select_parent=document.getElementsByName('record');
	var len = select_parent.length;
	for(var i=0;i<len;i++)
	{
		if(select_parent[i].checked)
		{
			var check_parentvar=true;
		}
	}
	if (check_parentvar!=true)
	{
		alert(alert_arr.Select_one_record_as_parent_record);
		return false;
	}
	return true;
}

function select_All(fieldnames,cnt,module)
{
	var new_arr = Array();
	new_arr = fieldnames.split(",");
	var len=new_arr.length;
	for(i=0;i<len;i++)
	{
		var fld_names=new_arr[i]
		var value=document.getElementsByName(fld_names)
		var fld_len=document.getElementsByName(fld_names).length;
		for(j=0;j<fld_len;j++)
		{
			value[cnt].checked='true'
			//	alert(value[j].checked)
		}

	}
}

function selectAllDel(state,checkedName)
{
		var selectedOptions=document.getElementsByName(checkedName);
		var length=document.getElementsByName(checkedName).length;
		if(typeof(length) == 'undefined')
		{
			return false;
		}
		for(var i=0;i<length;i++)
		{
			selectedOptions[i].checked=state;
		}
}

function selectDel(ThisName,CheckAllName)
	{
		var ThisNameOptions=document.getElementsByName(ThisName);
		var CheckAllNameOptions=document.getElementsByName(CheckAllName);
		var len1=document.getElementsByName(ThisName).length;
		var flag = true;
		if (typeof(document.getElementsByName(ThisName).length)=="undefined")
	       	{
			flag=true;
		}
	       	else
		{
			for (var j=0;j<len1;j++)
			{
				if (ThisNameOptions[j].checked==false)
		       		{
					flag=false
					break;
				}
			}
		}
		CheckAllNameOptions[0].checked=flag
}

// Added for page navigation in duplicate-listview
var dup_start = "";
function getDuplicateListViewEntries_js(module,url)
{
	dup_start = url;
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module="+module+"&action="+module+"Ajax&file=FindDuplicateRecords&ajax=true&"+dup_start,
		success: function(result) {
			jQuery('#status').hide();
			jQuery("#duplicate_ajax").html(result);
		}
	});
}

function getUnifiedSearchEntries_js(module,url){
   var qryStr = document.getElementsByName('search_criteria')[0].value;
   jQuery('#status').show();
   var recordCount = document.getElementById(module+'RecordCount').value;
   
   jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module=Home&action=HomeAjax&file=UnifiedSearchAjax&ajax=true&"+url+'&smodule='+module+'&query_string='+qryStr+'&display=true&recordCount='+recordCount, //crmv@187493
		success: function(result) {
			jQuery('#status').hide();
			jQuery('#global_list_'+module).html(result);
		}
	});
}
//crmv@8719e

//crmv@vtc
function dldCntIncrease(fileid) {
	 jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=DocumentsAjax&mode=ajax&file=SaveFile&module=Documents&file_id='+fileid+"&act=updateDldCnt",
	});
}

function getFile(url) {
  if (window.XMLHttpRequest) {
    var AJAX=new XMLHttpRequest();
  } else {
    var AJAX=new ActiveXObject("Microsoft.XMLHTTP");
  }
  if (AJAX) {
     AJAX.open("GET", url, false);
     AJAX.send(null);
     return AJAX.responseText;
  } else {
     return false;
  }
}
//crmv@vtc end
function isdefined(variable)
{
    return (getObj(variable) == null)?  false: true;
}
/**
* this function accepts a node and puts it at the center of the screen
* @param object node - the dom object which you want to set in the center
*/
function placeAtCenter(node, fixed){
	var centerPixel = getViewPortCenter(fixed);
	if (fixed) {
		node.style.position = "fixed";
	} else {
		node.style.position = "absolute";
	}
	var point = getDimension(node);
	var topvalue = (centerPixel.y - point.y/2) ;
	var rightvalue = (centerPixel.x - point.x/2);

	//to ensure that values will not be negative
	if(topvalue<0) topvalue = 0;
	if(rightvalue < 0) rightvalue = 0;

	node.style.top = topvalue + "px";
	node.style.left =rightvalue + "px";
}

/**
* this function gets the dimension of a node
* @param node - the node whose dimension you want
* @return height and width in array format
*/
function getDimension(node){
	var ht = node.offsetHeight;
	var wdth = node.offsetWidth;
	var nodeChildren = node.getElementsByTagName("*");
	var noOfChildren = nodeChildren.length;
	for(var index =0;index<noOfChildren;++index){
		ht = Math.max(nodeChildren[index].offsetHeight, ht);
		wdth = Math.max(nodeChildren[index].offsetWidth,wdth);
	}
	return {x: wdth,y: ht};
}

/**
* this function returns the center co-ordinates of the viewport as an array
*/
function getViewPortCenter(fixed){
	var height;
	var width;
	
	if (fixed) {
		// ignore the scrolling
		width = Math.max(document.documentElement.clientWidth, window.innerWidth || 0) / 2 ;
		height = Math.max(document.documentElement.clientHeight, window.innerHeight || 0) / 2;
	} else if(typeof window.pageXOffset != "undefined"){
		height = window.innerHeight/2;
		width = window.innerWidth/2;
		height +=window.pageYOffset;
		width +=window.pageXOffset;
	}else if(document.documentElement && typeof document.documentElement.scrollTop != "undefined"){
		height = document.documentElement.clientHeight/2;
		width = document.documentElement.clientWidth/2;
		height += document.documentElement.scrollTop;
		width += document.documentElement.scrollLeft;
	}else if(document.body && typeof document.body.clientWidth != "undefined"){
		height = window.screen.availHeight/2;
		width = window.screen.availWidth/2;
		height += document.body.clientHeight;
		width += document.body.clientWidth;
	}
	return {x: width,y: height};
}

/**
* this function accepts a number and displays a div stating that there is an outgoing call
* then it calls the number
* @param number - the number to be called
*/
function startCall(number, recordid){
	if (checkJSOverride(arguments)) return callJSOverride(arguments);	//crmv@OPER4323
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	div = document.getElementById('OutgoingCall').innerHTML;
	outgoingPopup = _defPopup();
	outgoingPopup.content = div;
	outgoingPopup.displayPopup(outgoingPopup.content);

	//var ASTERISK_DIV_TIMEOUT = 6000;
	 jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=PBXManagerAjax&mode=ajax&file=StartCall&ajax=true&module=PBXManager&number='+encodeURIComponent(number)+'&recordid='+recordid, // crmv@115325
		success: function(result) {
			if(result == ''){
				//successfully called
			}else{
				alert(result);
			}
		}
	});
}

function submitFormForActionWithConfirmation(formName, action, confirmationMsg) {
	if (confirm(confirmationMsg)) {
		return submitFormForAction(formName, action);
	}
	return false;
}

function submitFormForAction(formName, action) {
	var form = document.forms[formName];
	if (!form) return false;
	
	form.action.value = action;
	
	jQuery(form).submit();
	
	// crmv@166949
	if (window.sendMessageFromPanel) {
		sendMessageFromPanel({name: 'formAction', action: action});
	}
	// crmv@166949e
	
	return true;
}

/** Javascript dialog box utility functions **/
VteJS_DialogBox = {//crmv@207829
	_olayer : function(toggle) {
		var olayerid = "__vtejs_dialogbox_olayer__";
		VteJS_DialogBox._removebyid(olayerid);

		if(typeof(toggle) == 'undefined' || !toggle) return;

		var olayer = document.getElementById(olayerid);
		if(!olayer) {
			olayer = document.createElement("div");
			olayer.id = olayerid;
			olayer.className = "veil_new";
			olayer.style.zIndex = findZMax();//(new Date()).getTime();	//crmv@26491
			// In case zIndex goes to negative side!
			if(olayer.style.zIndex < 0) olayer.style.zIndex *= -1;
			if (browser_ie) {
				olayer.style.height = document.body.offsetHeight + (document.body.scrollHeight - document.body.offsetHeight) + "px";
			} else if (browser_nn4 || browser_nn6) {
				olayer.style.height = jQuery(document).height() + "px"; // crmv@43864
			}
			olayer.style.width = "100%";
			document.body.appendChild(olayer);
			/*
			var closeimg = document.createElement("img");
			closeimg.src = 'themes/images/close.gif';
			closeimg.alt = 'X';
			closeimg.style.right= '10px';
			closeimg.style.top  = '5px';
			closeimg.style.position = 'absolute';
			closeimg.style.cursor = 'pointer';
			closeimg.onclick = VteJS_DialogBox.unblock;
			olayer.appendChild(closeimg);
			*/
		}
		if(olayer) {
			if(toggle) olayer.style.display = "block";
			else olayer.style.display = "none";
		}
		return olayer;
	},
	//crmv@3085m
	_olayerTarget : function(target,toggle) {
		var olayerid = "__vtejs_dialogbox_olayer__"+target+"__";
		VteJS_DialogBox._removebyid(olayerid);

		if(typeof(toggle) == 'undefined' || !toggle) return;

		var olayer = document.getElementById(olayerid);
		if(!olayer) {
			olayer = document.createElement("div");
			olayer.id = olayerid;
			olayer.className = "veil_new";
			olayer.style.zIndex = jQuery('#'+target).zIndex();
			// In case zIndex goes to negative side!
			if(olayer.style.zIndex < 0) olayer.style.zIndex *= -1;
			document.body.appendChild(olayer);
			
			jQuery('#'+olayerid).height(jQuery('#'+target).height());
			jQuery('#'+olayerid).width(jQuery('#'+target).width());
			var top = jQuery('#'+target).offset().top;
			var left = jQuery('#'+target).offset().left;
			if (jQuery('#'+target).css('padding') != '' && !isNaN(jQuery('#'+target).css('padding'))) {
				top = top + parseInt(jQuery('#'+target).css('padding').replace('px',''));
				left = left + parseInt(jQuery('#'+target).css('padding').replace('px',''));
			}
			jQuery('#'+olayerid).css('top',top);
			jQuery('#'+olayerid).css('left',left);
		}
		if(olayer) {
			if(toggle) olayer.style.display = "block";
			else olayer.style.display = "none";
		}
		return olayer;
	},
	//crmv@3085me
	_removebyid : function(id) {
		if(isdefined(id)) jQuery('#'+id).remove(); //crmv@115327
	},
	//crmv@3085m
	unblock : function(target) {
		if(typeof(target)=='undefined') {
			VteJS_DialogBox._olayer(false);
		} else {
			VteJS_DialogBox._olayerTarget(target,false);
		}
	},
	block : function(target,opacity) {
		if(typeof(opacity)=='undefined') opacity = '0.3';
		if(typeof(target)=='undefined') {
			var olayernode = VteJS_DialogBox._olayer(true);
		} else {
			var olayernode = VteJS_DialogBox._olayerTarget(target,true);
		}
		//olayernode.style.opacity = opacity;
	},
	//crmv@3085me
	hideprogress : function(target) {
		if(typeof(target)=='undefined') {
			var prgbxid = "__vtejs_dialogbox_progress_id__";
		} else {
			var prgbxid = "__vtejs_dialogbox_progress_id__"+target+"__";
		}
		if(isdefined(prgbxid)) document.getElementById(prgbxid).style.display = 'none';	//crmv@144275	//VteJS_DialogBox._removebyid(prgbxid);
	},
	progress : function(target,color) {
		if(typeof(target)=='undefined') {
			var prgbxid = "__vtejs_dialogbox_progress_id__";
		} else {
			var prgbxid = "__vtejs_dialogbox_progress_id__"+target+"__";
		}
		if(typeof(color)=='undefined') var color = 'dark';
		if (color == 'light') {
			var loaderClass = 'vteLoader';
			var layerClass = 'veil_light';
		} else {
			var loaderClass = 'loader';
			var layerClass = 'veil_new';
		}		
		var prgnode = document.getElementById(prgbxid);
		if(!prgnode) {
			prgnode = document.createElement("div");
			prgnode.id = prgbxid;
			prgnode.className = layerClass;
			prgnode.style.position = 'fixed';
			prgnode.style.width = '100%';
			prgnode.style.height = '100%';
			prgnode.style.top = '0';
			prgnode.style.left = '0';
			prgnode.style.display = 'block';
			prgnode.style.zIndex = findZMax();
			if(prgnode.style.zIndex < 0) prgnode.style.zIndex *= -1;	// In case zIndex goes to negative side!
			document.body.appendChild(prgnode);
			if(typeof(target)!='undefined') {
				jQuery('#'+prgbxid).height(jQuery('#'+target).height());
				jQuery('#'+prgbxid).width(jQuery('#'+target).width());
				var top = jQuery('#'+target).offset().top;
				var left = jQuery('#'+target).offset().left;
				if (jQuery('#'+target).css('padding') != '' && !isNaN(jQuery('#'+target).css('padding'))) {
					top = top + parseInt(jQuery('#'+target).css('padding').replace('px',''));
					left = left + parseInt(jQuery('#'+target).css('padding').replace('px',''));
				}
				jQuery('#'+prgbxid).css('top',top);
				jQuery('#'+prgbxid).css('left',left);
			}
			prgnode.innerHTML =
			'<table border="0" cellpadding="0" cellspacing="0" align="center" style="vertical-align:middle;width:100%;height:100%;">'+
			'<tr><td class="big" align="center">'+
			'<div class="'+loaderClass+'">Loading...</div>'+
			'</td></tr></table>';
		}
		if(prgnode) prgnode.style.display = 'block';
	},
	hideconfirm : function() {
		VteJS_DialogBox._olayer(false);
		VteJS_DialogBox._removebyid('__vtejs_dialogbox_alert_boxid__');
	},
	confirm : function(msg, onyescode) {
		VteJS_DialogBox._olayer(true);

		var dlgbxid = "__vtejs_dialogbox_alert_boxid__";
		var dlgbxnode = document.getElementById(dlgbxid);
		if(!dlgbxnode) {
			dlgbxnode = document.createElement("div");
			dlgbxnode.style.display = 'none';
			dlgbxnode.className = 'veil_new';
			dlgbxnode.id = dlgbxid;
			dlgbxnode.style.zIndex = findZMax();
			if(dlgbxnode.style.zIndex < 0) dlgbxnode.style.zIndex *= -1;	// In case zIndex goes to negative side!
			dlgbxnode.innerHTML =
			'<table cellspacing="0" cellpadding="18" border="0" class="options small">' +
			'<tbody>' +
				'<tr>' +
				'<td nowrap="" align="center" style="color: rgb(255, 255, 255); font-size: 15px;">' +
				'<b>'+ msg + '</b></td>' +
				'</tr>' +
				'<tr>' +
				'<td align="center">' +
				'<input type="button" style="text-transform: capitalize;" onclick="jQuery(\'#'+ dlgbxid + '\').hide();VteJS_DialogBox._olayer(false);VteJS_DialogBox._confirm_handler();" value="'+ alert_arr.YES + '"/>' +
				'<input type="button" style="text-transform: capitalize;" onclick="jQuery(\'#'+ dlgbxid + '\').hide();VteJS_DialogBox._olayer(false)" value="' + alert_arr.NO + '"/>' +
				'</td>'+
				'</tr>' +
			'</tbody>' +
			'</table>';
			document.body.appendChild(dlgbxnode);
		}
		if(typeof(onyescode) == 'undefined') onyescode = '';
		dlgbxnode._onyescode = onyescode;
		if(dlgbxnode) dlgbxnode.style.display = 'block';
	},
	_confirm_handler : function() {
		var dlgbxid = "__vtejs_dialogbox_alert_boxid__";
		var dlgbxnode = document.getElementById(dlgbxid);
		if(dlgbxnode) {
			if(typeof(dlgbxnode._onyescode) != 'undefined' && dlgbxnode._onyescode != '') {
				eval(dlgbxnode._onyescode);
			}
		}
	},
	//crmv@48501
	notify : function(msg, interval) {
		if(typeof(interval) == 'undefined') interval = 2;
		var notbxid = "__vtejs_dialogbox_notification_id__";
		var notnode = document.getElementById(notbxid);
		if (!notnode) {
			notnode = document.createElement("div");
			notnode.id = notbxid;
			notnode.style.className = 'notTbl';
			notnode.style.position = 'absolute';
			notnode.style.top = '40px';
			notnode.style.minWidth = '300px';
			notnode.style.maxWidth = '800px';
			notnode.style.display = 'block';
			notnode.style.zIndex = findZMax();
			if(notnode.style.zIndex < 0) notnode.style.zIndex *= -1;	// In case zIndex goes to negative side!

			document.body.appendChild(notnode);

			notnode.innerHTML =
			'<table border="0" cellpadding="10" cellspacing="0" align="center" class="notTbl">'+
			'<tr><td class="small" align="center" id="'+notbxid+'msg__"></td></tr></table>'+
			'<div class="closebutton" onClick="VteJS_DialogBox.hidenotify();"></div>';
		}
		if (notnode) {
			if (interval > 0) {
				jQuery(notnode).find('.closebutton').hide();
			} else {
				jQuery(notnode).find('.closebutton').show();
			}
			
			notnode.style.display = 'block';
			document.getElementById(notbxid+'msg__').innerHTML = msg;
			
			var centerPixel = getViewPortCenter();
			var point = getDimension(notnode);
			var rightvalue = (centerPixel.x - point.x/2);
		
			//to ensure that values will not be negative
			if(rightvalue < 0) rightvalue = 0;
		
			notnode.style.left = rightvalue + "px";
		}
		if (interval > 0) {
			var timeout = setTimeout(function(){
	            jQuery(notnode).fadeOut("slow");
	        }, interval*1000);
	        jQuery(notnode).find('.notTbl').mouseenter(function(){
			    clearTimeout(timeout);
			    jQuery(notnode).fadeIn("slow");
			}).mouseleave(function(){
				timeout = setTimeout(function(){
		            jQuery(notnode).fadeOut("slow");
		        }, interval*1000);
			});
		}
	},
	hidenotify : function() {
		document.getElementById('__vtejs_dialogbox_notification_id__').style.display = 'none';
	}
	//crmv@48501e
}
//crmv@picklistmultiplanguage
function resetpicklist(field){
	rm_all_opt(field);
	add_opt(field,alert_arr.LBL_PLEASE_SELECT,'');
	getObj(field).value = '';
}
function rm_all_opt(field)
{
	var elSel;
	elSel = getObj(field);
  var i;
  for (i = elSel.length - 1; i>=0; i--) {
      elSel.remove(i);
  }
}

function add_opt(field,text,value)
{
  var elOptNew = document.createElement('option');
  elOptNew.text = text;
  elOptNew.value = value;
  var elSel = getObj(field);

  try {
    elSel.add(elOptNew, null); // standards compliant; doesn't work in IE
  }
  catch(ex) {
    elSel.add(elOptNew); // IE only
  }
}
//crmv@picklistmultiplanguage end
//crmv@add textlength check
function lengthComparison(fldName,fldLabel,type,constval) {
	var val = jQuery('[name="'+fldName+'"]').val().replace(/^\s+/g, '').replace(/\s+$/g, '').length; // crmv@192646
	constval=parseFloat(constval);
	var ret=true;
	var err_callback = function(){
		jQuery('[name="'+fldName+'"]').focus(); // crmv@192646
    };
	//crmv@59091
	var lengthLabel = alert_arr.LENGTH;
	lengthLabel = lengthLabel.charAt(0).toUpperCase() + lengthLabel.slice(1);
	switch (type) {
		case "L"  : if (val>=constval) {
			var err = sprintf(alert_arr.LENGTH_SHOULDBE_LESS, lengthLabel, fldLabel, constval, alert_arr.CHARACTER);
			alert(err, err_callback);
			ret=false;
		}
		break;
		case "LE" : if (val>constval) {
			var err = sprintf(alert_arr.LENGTH_SHOULDBE_LESS_EQUAL, lengthLabel, fldLabel, constval, alert_arr.CHARACTER);
			alert(err, err_callback);
			ret=false;
		}
		break;
		case "E"  : if (val!=constval) {
			var err = sprintf(alert_arr.LENGTH_SHOULDBE_EQUAL, lengthLabel, fldLabel, constval, alert_arr.CHARACTER);
			alert(err, err_callback);
			ret=false;
		}
		break;
		case "NE" : if (val==constval) {
			var err = sprintf(alert_arr.LENGTH_SHOULDNOTBE_EQUAL, lengthLabel, fldLabel, constval, alert_arr.CHARACTER);
			alert(err, err_callback);
			ret=false;
		}
		break;
		case "G"  : if (val<=constval) {
			var err = sprintf(alert_arr.LENGTH_SHOULDBE_GREATER, lengthLabel, fldLabel, constval, alert_arr.CHARACTER);
			alert(err, err_callback);
			ret=false;
		}
		break;
		case "GE" : if (val<constval) {
			var err = sprintf(alert_arr.LENGTH_SHOULDBE_GREATER_EQUAL, lengthLabel, fldLabel, constval, alert_arr.CHARACTER);
			alert(err, err_callback);
			ret=false;
		}
		break;
	}
	//crmv@59091e
	return ret;
}
//crmv@add textlength check end
/******************************************************************************/
/* Activity reminder Customization: Setup Callback */
function ActivityReminderProgressIndicator(show) {
	if(show) jQuery('#status').show();
	else jQuery('#status').hide();
}

function ActivityReminderSetupCallback(cbmodule, cbrecord) {
	if(cbmodule && cbrecord) {
		ActivityReminderProgressIndicator(true);
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: "module=Calendar&action=CalendarAjax&ajax=true&file=ActivityReminderSetupCallbackAjax&cbmodule="+
				encodeURIComponent(cbmodule) + "&cbrecord=" + encodeURIComponent(cbrecord),
			success: function(result) {
				jQuery("#ActivityReminder_callbacksetupdiv").html(result);
				ActivityReminderProgressIndicator(false);
			}
		});
	}
}

function ActivityReminderSetupCallbackSave(form) {
	var cbmodule = form.cbmodule.value;
	var cbrecord = form.cbrecord.value;
	var cbaction = form.cbaction.value;

	var cbdate   = form.cbdate.value;
	var cbtime   = form.cbhour.value + ":" + form.cbmin.value;

	if(cbmodule && cbrecord) {
		ActivityReminderProgressIndicator(true);
		
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: "module=Calendar&action=CalendarAjax&ajax=true&file=ActivityReminderSetupCallbackAjax" +
				"&cbaction=" + encodeURIComponent(cbaction) +
				"&cbmodule="+ encodeURIComponent(cbmodule) +
				"&cbrecord=" + encodeURIComponent(cbrecord) +
				"&cbdate=" + encodeURIComponent(cbdate) +
				"&cbtime=" + encodeURIComponent(cbtime),
			success: function(result) {
				ActivityReminderSetupCallbackSaveProcess(result);
			}
		});

	}
}
function ActivityReminderSetupCallbackSaveProcess(message) {
	ActivityReminderProgressIndicator(false);
	jQuery('#ActivityReminder_callbacksetupdiv_lay').hide();
}

function ActivityReminderPostponeCallback(cbmodule, cbrecord, cbreminderid) {
	if(cbmodule && cbrecord) {
		ActivityReminderProgressIndicator(true);
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: "module=Calendar&action=CalendarAjax&ajax=true&file=ActivityReminderSetupCallbackAjax&cbaction=POSTPONE&cbmodule="+
				encodeURIComponent(cbmodule) + "&cbrecord=" + encodeURIComponent(cbrecord) + "&cbreminderid=" + encodeURIComponent(cbreminderid),
			success: function(result) {
				ActivityReminderPostponeCallbackProcess(result);
			}
		});
	}
}
function ActivityReminderCloseCallback(cbmodule, cbrecord, cbreminderid) {
	if(cbmodule && cbrecord) {

		ActivityReminderProgressIndicator(true);
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: "module=Calendar&action=CalendarAjax&ajax=true&file=ActivityReminderSetupCallbackAjax&cbaction=CLOSE&cbmodule="+
			encodeURIComponent(cbmodule) + "&cbrecord=" + encodeURIComponent(cbrecord) + "&cbreminderid=" + encodeURIComponent(cbreminderid),
			success: function(result) {
				ActivityReminderPostponeCallbackProcess(result);
			}
		});
	}
}

// crmv@185423
function ActivityReminderCloseActivity(record, popupid) {
	ActivityReminderProgressIndicator(true);

	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "action=Save&module=Calendar&record="+record+"&change_status=true&eventstatus=Held",
		success: function(result) {
			ActivityReminderRemovePopupDOM(popupid);
			ActivityReminderProgressIndicator(false);
		}
	});	
}
// crmv@185423e

// crmv@196511
function ActivityReminderCloseTask(record, popupid) {
	ActivityReminderProgressIndicator(true);

	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "action=Save&module=Calendar&record="+record+"&change_status=true&status=Completed",
		success: function(result) {
			ActivityReminderRemovePopupDOM(popupid);
			ActivityReminderProgressIndicator(false);
		}
	});
}
// crmv@196511e

// crmv@98866
function ActivityReminderPostponeAll() {
	var params = [];
	jQuery('div[id^=ActivityReminder_][class=reminder-popup]').each(function(index, element) {
		var $element = jQuery(element);
		var popupid = $element.attr('id');
		// crmv@136431
		var module = jQuery('input[name="' + popupid + '_module"]').val();
		var record = jQuery('input[name="' + popupid + '_record"]').val();
		var reminderid = jQuery('input[name="' + popupid + '_reminderid"]').val();
		// crmv@136431e
		params.push({'module': module, 'record': record, 'reminderid': reminderid});
	});
	if (!jQuery.isEmptyObject(params)) {
		ActivityReminderProgressIndicator(true);
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: "module=Calendar&action=CalendarAjax&ajax=true&file=ActivityReminderSetupCallbackAjax&cbaction=POSTPONE&cbparams=" + encodeURIComponent(JSON.stringify(params)),
			success: function(result) {
				ActivityReminderPostponeCallbackProcess(result);
				jQuery('div[id^=ActivityReminder_][class=reminder-popup]').each(function(index, element) {
					var $element = jQuery(element);
					var popupid = $element.attr('id');
					
					ActivityReminderRemovePopupDOM(popupid);
				});
				ActivityReminder_callback = document.getElementById("ActivityRemindercallback");
				ActivityReminder_callback.style.display = 'none';
			}
		});
	}
}

function ActivityReminderCloseAll() {
	var params = [];
	jQuery('div[id^=ActivityReminder_][class=reminder-popup]').each(function(index, element) {
		var $element = jQuery(element);
		var popupid = $element.attr('id');
		// crmv@136431
		var module = jQuery('input[name="' + popupid + '_module"]').val();
		var record = jQuery('input[name="' + popupid + '_record"]').val();
		var reminderid = jQuery('input[name="' + popupid + '_reminderid"]').val();
		// crmv@136431e
		params.push({'module': module, 'record': record, 'reminderid': reminderid});
	});
	if (!jQuery.isEmptyObject(params)) {
		ActivityReminderProgressIndicator(true);
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: "module=Calendar&action=CalendarAjax&ajax=true&file=ActivityReminderSetupCallbackAjax&cbaction=CLOSE&cbparams=" + encodeURIComponent(JSON.stringify(params)),
			success: function(result) {
				ActivityReminderPostponeCallbackProcess(result);
				jQuery('div[id^=ActivityReminder_][class=reminder-popup]').each(function(index, element) {
					var $element = jQuery(element);
					var popupid = $element.attr('id');
					
					ActivityReminderCallbackReset(0, popupid);
					ActivityReminderRemovePopupDOM(popupid);
				});
				ActivityReminder_callback = document.getElementById("ActivityRemindercallback");
				ActivityReminder_callback.style.display = 'none';
			}
		});
	}
}
// crmv@98866 end

function ActivityReminderPostponeCallbackProcess(message) {
	ActivityReminderProgressIndicator(false);
}
//crmv@OPER5904
function ActivityReminderRemovePopupDOM(id,propagate) {
	if(typeof(propagate) == 'undefined') propagate = true;
	jQuery('#'+id).remove();
	if (propagate) {
		VTELocalStorage.setItem("activityReminderRemove",id);
		VTELocalStorage.removeItem("activityReminderRemove");
	}
	// crmv@98866
	var selector = jQuery('div[id^=ActivityReminder_][class=reminder-popup]');
	if (selector.length < 1) {
		ActivityReminder_callback = document.getElementById("ActivityRemindercallback");
		ActivityReminder_callback.style.display = 'none';
		// crmv@104512
		if (window.oldWindowDocumentTitle) {
			var title = window.oldWindowDocumentTitle;
			updateBrowserTitle(title);
			delete window.oldWindowDocumentTitle;
		}
		// crmv@104512e
	}
	// crmv@98866 end
}
//crmv@OPER5904e
/* END */

/* ActivityReminder Customization: Pool Callback */
var ActivityReminder_regcallback_timer;

var ActivityReminder_callback_delay = 40 * 1000; // Milli Seconds
var ActivityReminder_autohide = false; // If the popup should auto hide after callback_delay?

var ActivityReminder_popup_maxheight = 75;

var ActivityReminder_callback;
var ActivityReminder_timer;
var ActivityReminder_progressive_height = 2; // px
var ActivityReminder_popup_onscreen = 2 * 1000; // Milli Seconds (should be less than ActivityReminder_callback_delay)

var ActivityReminder_callback_win_uniqueids = new Object();

//crmv@OPER5904
var ActivityReminderCallbackInterval;
function ActivityReminderCallbackInit() {
	VTELocalStorage.enablePropagation("activityReminderMessage", function(event){
		if (event.newValue != '') {
			ActivityReminderCallbackProcess(event.newValue);
		}
	});
	VTELocalStorage.enablePropagation("activityReminderRemove", function(event){
		if (event.newValue != '') {
			ActivityReminderRemovePopupDOM(event.newValue,false);
		}
	});
}
function ActivityReminderCallback(interval) {
	if(typeof(Ajax) == 'undefined') {
		return;
	}
	if(ActivityReminder_regcallback_timer) {
		window.clearTimeout(ActivityReminder_regcallback_timer);
		ActivityReminder_regcallback_timer = null;
	}
	if(typeof(interval) != 'undefined') ActivityReminderCallbackInterval = interval;
	if(typeof(ActivityReminderCallbackInterval) != 'undefined') {
		var now = (new Date()).getTime();
		var activityReminderLastCheck = parseInt(VTELocalStorage.getItem("activityReminderLastCheck")) || 0;
		if ((now - activityReminderLastCheck) <= (ActivityReminderCallbackInterval * 1000)) {
			// already done by other tab
			ActivityReminderCallbackProcess('');
			return;
		}
	}
	jQuery.ajax({
		url: 'index.php?module=Calendar&action=CalendarAjax&file=ActivityReminderCallbackAjax&ajax=true',
		type: 'POST',
		success: function(data) {
			VTELocalStorage.setItem("activityReminderLastCheck",(new Date()).getTime());
			if(data == '' || trim(data).indexOf('<script') == 0) {} else {
				VTELocalStorage.setItem("activityReminderMessage",data);
				VTELocalStorage.removeItem("activityReminderMessage");
			}
			ActivityReminderCallbackProcess(data);
		}
	});
}
//crmv@OPER5904e
function ActivityReminderCallbackProcess(message) {
	ActivityReminder_callback = document.getElementById("ActivityRemindercallback");
	ActivityReminder_callback_content = document.getElementById("ActivityRemindercallback-content"); // crmv@98866
	if(ActivityReminder_callback == null) return;
	// ActivityReminder_callback.style.display = 'block';
	if (message == null) message = '';	//crmv@OPER5904

	var winuniqueid = 'ActivityReminder_callback_win_' + (new Date()).getTime();
	if(ActivityReminder_callback_win_uniqueids[winuniqueid]) {
		winuniqueid += "-" + (new Date()).getTime();
	}
	ActivityReminder_callback_win_uniqueids[winuniqueid] = true;

	var ActivityReminder_callback_win = document.createElement("span");
	ActivityReminder_callback_win.id  = winuniqueid;
	ActivityReminder_callback_content.appendChild(ActivityReminder_callback_win); // crmv@98866

	if (message != '') jQuery(ActivityReminder_callback_win).html(message);	//crmv@OPER5904
	ActivityReminder_callback_win.style.height = "0px";
	ActivityReminder_callback_win.style.display = "";

	var ActivityReminder_Newdelay_response_node = 'activityreminder_callback_interval_';
	if (jQuery('#'+ActivityReminder_Newdelay_response_node).length > 0) {
		var ActivityReminder_Newdelay_response_value = parseInt(jQuery('#'+ActivityReminder_Newdelay_response_node).text());
		if(ActivityReminder_Newdelay_response_value > 0) {
			ActivityReminder_callback_delay = ActivityReminder_Newdelay_response_value;
		}
		// We don't need the no any longer, it will be sent from server for next Popup
		jQuery('#'+ActivityReminder_Newdelay_response_node).remove();
	}
	if(message == '' || trim(message).indexOf('<script') == 0) {
		// We got only new dealay value but no popup information, let us remove the callback win created
		jQuery(ActivityReminder_callback_win.id).remove();
		ActivityReminder_callback_win = false;
		message = '';
	} else {
		// crmv@98866
		ActivityReminder_callback.style.display = 'block';
		ActivityReminder_callback.style.zIndex = findZMax()+1; 
		// crmv@98866 end
	}

	if(message != "") ActivityReminderCallbackRollout(ActivityReminder_popup_maxheight, ActivityReminder_callback_win);
	else { ActivityReminderCallbackReset(0, ActivityReminder_callback_win); }
	
	//crmv@OPER5904 check duplicates
	if(message != "") {
		jQuery(ActivityReminder_callback_win).children('div').each(function(){
			if (jQuery('div#'+jQuery(this).attr('id')).length > 1) {
				jQuery(this).remove();
			}
		});
	}
	//crmv@OPER5904e
}
function ActivityReminderCallbackRollout(z, ActivityReminder_callback_win) {
	if (typeof(ActivityReminder_callback_win) == 'string') {
		ActivityReminder_callback_win = jQuery('#'+ActivityReminder_callback_win).get(0);
	} else {
		ActivityReminder_callback_win = jQuery(ActivityReminder_callback_win).get(0);
	}

	if (ActivityReminder_timer) { window.clearTimeout(ActivityReminder_timer); }
	if (ActivityReminder_callback_win && parseInt(ActivityReminder_callback_win.style.height) < z) {
		ActivityReminder_callback_win.style.height = parseInt(ActivityReminder_callback_win.style.height) + ActivityReminder_progressive_height + "px";
		ActivityReminder_timer = setTimeout("ActivityReminderCallbackRollout(" + z + ",'" + ActivityReminder_callback_win.id + "')", 1);
	} else {
		ActivityReminder_callback_win.style.height = z + "px";
		if(ActivityReminder_autohide) ActivityReminder_timer = setTimeout("ActivityReminderCallbackRollin(1,'" + ActivityReminder_callback_win.id + "')", ActivityReminder_popup_onscreen);
		else ActivityReminderRegisterCallback(ActivityReminder_callback_delay);
	}
}
function ActivityReminderCallbackRollin(z, ActivityReminder_callback_win) {
	if (typeof(ActivityReminder_callback_win) == 'string') {
		ActivityReminder_callback_win = jQuery('#'+ActivityReminder_callback_win).get(0);
	} else {
		ActivityReminder_callback_win = jQuery(ActivityReminder_callback_win).get(0);
	}

	if (ActivityReminder_timer) { window.clearTimeout(ActivityReminder_timer); }
	if (parseInt(ActivityReminder_callback_win.style.height) > z) {
		ActivityReminder_callback_win.style.height = parseInt(ActivityReminder_callback_win.style.height) - ActivityReminder_progressive_height + "px";
		ActivityReminder_timer = setTimeout("ActivityReminderCallbackRollin(" + z + ",'" + ActivityReminder_callback_win.id + "')", 1);
	} else {
		ActivityReminderCallbackReset(z, ActivityReminder_callback_win);
	}
}
function ActivityReminderCallbackReset(z, ActivityReminder_callback_win) {
	if (typeof(ActivityReminder_callback_win) == 'string') {
		ActivityReminder_callback_win = jQuery('#'+ActivityReminder_callback_win).get(0);
	} else {
		ActivityReminder_callback_win = jQuery(ActivityReminder_callback_win).get(0);
	}

	if(ActivityReminder_callback_win) {
		ActivityReminder_callback_win.style.height = z + "px";
		ActivityReminder_callback_win.style.display = "none";
	}
	if(ActivityReminder_timer) {
		window.clearTimeout(ActivityReminder_timer);
		ActivityReminder_timer = null;
	}
	ActivityReminderRegisterCallback(ActivityReminder_callback_delay);
}
function ActivityReminderRegisterCallback(timeout) {
	if(timeout == null) timeout = 1;
	if(ActivityReminder_regcallback_timer == null) {
		ActivityReminder_regcallback_timer = setTimeout("ActivityReminderCallback()", timeout);
	}
}

// Function to display the element with id given by showid and hide the element with id given by hideid
function toggleShowHide(showid, hideid)
{
	var show_ele = document.getElementById(showid);
	var hide_ele = document.getElementById(hideid);
	if(show_ele != null)
		show_ele.style.display = "inline";
	if(hide_ele != null)
		hide_ele.style.display = "none";
}
// Refactored APIs from DisplayFiels.tpl
function fnshowHide(currObj,txtObj) {
	if(currObj.checked == true)
		document.getElementById(txtObj).style.visibility = 'visible';
	else
		document.getElementById(txtObj).style.visibility = 'hidden';
}

function fntaxValidation(txtObj) {
	if (!numValidate(txtObj,"Tax","any", false, 9)) // crmv@118512
		document.getElementById(txtObj).value = 0;
}

//crmv@98748
function fnpriceValidation(txtObj) {		
	val = jQuery('#'+txtObj).val();
	if(!validateUserNumber(val)){
		jQuery('#'+txtObj).val(0);
	}
}
//crmv@98748e

function delimage(id) {
	jQuery.ajax({
		url: 'index.php',
		type: 'POST',
		data: 'module=Contacts&action=ContactsAjax&file=DelImage&recordid='+id,
		success: function(result) {
			if(result.indexOf("SUCCESS")>-1)
				jQuery("#replaceimage").html(alert_arr.LBL_IMAGE_DELETED);
			else
				alert(alert_arr.ERROR_WHILE_EDITING);
		}
	});
}

function delUserImage(id) {
	jQuery.ajax({
		url: 'index.php',
		type: 'POST',
		data: 'module=Users&action=UsersAjax&file=Save&deleteImage=true&recordid='+id,
		success: function(result) {
			if(result.indexOf("SUCCESS")>-1)
				jQuery("#replaceimage").html(alert_arr.LBL_IMAGE_DELETED);
			else
				alert(alert_arr.ERROR_WHILE_EDITING);
		}
	});
}

// Function to enable/disable related elements based on whether the current object is checked or not
function fnenableDisable(currObj,enableId) {
	var disable_flag = true;
	if(currObj.checked == true)
		disable_flag = false;

	document.getElementById('curname'+enableId).disabled = disable_flag;
	document.getElementById('cur_reset'+enableId).disabled = disable_flag;
	document.getElementById('base_currency'+enableId).disabled = disable_flag;
}

// Update current value with current value of base currency and the conversion rate
function updateCurrencyValue(currObj,txtObj,base_curid,conv_rate) {
	var unit_price = jQuery('#'+base_curid).val();
	//if(currObj.checked == true)
	//{
		document.getElementById(txtObj).value = unit_price * conv_rate;
	//}
}

// Synchronize between Unit price and Base currency value.
function updateUnitPrice(from_cur_id, to_cur_id) {
    var from_ele = document.getElementById(from_cur_id);
    if (from_ele == null) return;

    var to_ele = document.getElementById(to_cur_id);
    if (to_ele == null) return;

    to_ele.value = from_ele.value;
}

// Update hidden base currency value, everytime the base currency value is changed in multi-currency UI
function updateBaseCurrencyValue() {
    var cur_list = document.getElementsByName('base_currency_input');
    if (cur_list == null) return;

    var base_currency_ele = document.getElementById('base_currency');
    if (base_currency_ele == null) return;

    for(var i=0; i<cur_list.length; i++) {
		var cur_ele = cur_list[i];
		if (cur_ele != null && cur_ele.checked == true)
    		base_currency_ele.value = cur_ele.value;
	}
}
// END
//crmv@9434
function query_change_state_motivation(fieldLabel,module,uitype,tableName,fieldName,crmId,tagValue){

	var obj = null;
	var div_obj = getObj("change_"+fieldName+"_div");
	if(div_obj) {
		obj = getObj("change_status_fieldlabel");
		if(obj) obj.value = fieldLabel;
		obj = getObj("change_status_module");
		if(obj) obj.value = module;
		obj = getObj("change_status_uitype");
		if(obj) obj.value = uitype;
		obj = getObj("change_status_tablename");
		if(obj) obj.value = tableName;
		obj = getObj("change_status_fieldname");
		if(obj) obj.value = fieldName;
		obj = getObj("change_status_crmid");
		if(obj) obj.value = crmId;
		obj = getObj("change_status_tagvalue");
		if(obj) obj.value = tagValue;

		var div_2_obj = document.getElementById('change_to_state_'+fieldName+'_div');
		if(div_2_obj) div_2_obj.innerHTML = alert_arr.LBL_STATUS_CHANGING+"\""+fieldLabel+"\" "+alert_arr.LBL_STATUS_CHANGING_MOTIVATION;

		div_obj.style.display = "inline";
		div_obj.style.visible = true;
		div_obj.style.position = "absolute"; // crmv@77249
		div_obj.style.left="-100px"; // crmv@77249
	}

}
function hide_question(div_name) {
	var div_obj = getObj(div_name);
	if(div_obj) {
		div_obj.style.display = "none";
		div_obj.style.visible = true;
	}
}
function change_state() {
	//crmv@123550
	VteJS_DialogBox.block();
	jQuery('#status').show();
	//crmv@123550e

	var fieldLabel = null;
	var module = null;
	var uitype = null;
	var tableName = null;
	var fieldName = null;
	var crmId = null;
	var tagValue = null;
	var motivation = null;

	var obj = null;
	obj = getObj("change_status_fieldlabel");
	if(obj) fieldLabel = obj.value;

	obj = getObj("change_status_module");
	if(obj) module = obj.value;

	obj = getObj("change_status_uitype");
	if(obj) uitype = obj.value;

	obj = getObj("change_status_tablename");
	if(obj) tableName = obj.value;

	obj = getObj("change_status_fieldname");
	if(obj) fieldName = obj.value;

	obj = getObj("change_status_crmid");
	if(obj) crmId = obj.value;

	obj = getObj("change_status_tagvalue");
	if(obj) tagValue = obj.value;

	obj = div_obj = getObj("motivation_"+fieldName);
	if(obj) motivation = obj.value;

	var data = "file=DetailViewAjax&module=" + module + "&action=" + module + "Ajax&record=" + crmId+"&recordid=" + crmId ;
	data = data + "&fldName=" + fieldName + "&fieldValue=" + escapeAll(tagValue) + "&ajxaction=DETAILVIEW&motivation="+escape(motivation);
	
	jQuery.ajax({
		url: 'index.php',
		type: 'POST',
		data: data,
		success: function(result) {
			if(result.indexOf(":#:FAILURE")>-1) {
				//crmv@123550
				VteJS_DialogBox.unblock();
				jQuery('#status').hide();
				//crmv@123550e
				alert(alert_arr.ERROR_WHILE_EDITING);
			} else if(result.indexOf(":#:SUCCESS")>-1) {
				document.location.reload();
			}
		}
	});
}
//crmv@9434  end

//crmv@18170
function SubmitQCForm(module,form) {
	if (getFormValidate()) {
		if (AjaxDuplicateValidate(module,form)) {
			return true;
		}
	}
	return false;
}
//crmv@18170e

//crmv@18592
function calculateButtonsList3() {
	jQuery('#Buttons_List_3').html(jQuery('#Buttons_List_3_Container').html());
	jQuery('#Buttons_List_3_Container').remove(); //crmv@24604
	jQuery('#Buttons_List_3').show();
	jQuery('#vte_menu_white').height(jQuery('#vte_menu').height()+5);
	//jQuery('#vte_menu').css('overflow', 'hidden'); // crmv@113339 - safari bug, safe to remove
}
//crmv@18592e

// crmv@150069
function recalcFixedMenu() {
	var element = null;
	
	if (jQuery('#Buttons_List_4').length > 0) {
		element = jQuery('#Buttons_List_4');
	}
	
	setTimeout(function(){
		element.css('top', jQuery('#vte_menu').height());
	}, 200);
	
	jQuery(window).resize(function() {
		element.css('top', jQuery('#vte_menu').height());
	});
}
//crmv@150069e

//crmv@21048m
function findZMax() {
	var zmax = 0;
	jQuery('body,div,table,span,iframe').each(function() {	//crmv@144275
    	var cur = parseInt(jQuery(this).css('zIndex'));
    	zmax = cur > zmax ? jQuery(this).css('zIndex') : zmax;
 	});
 	return eval(zmax);	//crmv@30406
}

function searchValue(search, separator, str) {
	if ((str) && str.indexOf(search + "=") > -1) {
		var fromIndex = str.indexOf(search + "=");
		var searchLen = (search + "=").length;
		var toIndex = str.indexOf(separator, fromIndex + searchLen);
		var searchValue = str.substring(fromIndex + searchLen, toIndex);
	}
	else {
		var searchValue = -1;
	}
	return searchValue;
}

function openPopup(link,title,options,scroll,newWidth,newHeight,topframe,spinner,sessionValidatorCheck) { //crmv@22055 crmv@182677
	if (typeof(sessionValidatorCheck) == 'undefined') var sessionValidatorCheck = true; //crmv@182677
	
	var newIdAppend = searchValue('module', '&', link);
	var newId = 'openPopup' + '_' + newIdAppend;
	
	// crmv@91082
	if (sessionValidatorCheck && !SessionValidator.check()){ //crmv@182677
		SessionValidator.showLogin();
		return false;
	}
	// crmv@91082e

	// crmv@82419 - allow automatic percentage
	if (!newWidth){
		newWidth = '100%';
	} else if (newWidth < 100) {
		newWidth = newWidth+'%';
	}
	if (!newHeight){
		newHeight = '100%';
	} else if (newHeight < 100) {
		newHeight = newHeight+'%';
	}
	// crmv@82419e

	//crmv@22022
	if (scroll != 'no' && scroll != 'yes') {
		scroll = 'auto';
	}
	//crmv@22022e

	if (topframe == 'top') {
		var newjQuery = top.jQuery;
	} else {
 		var newjQuery = jQuery;
	}
	
	//crmv@106856
	var margin = 20;
	if (top.jQuery('.fancybox-wrap:visible').length > 0) {
		margin = 0;
	}
	//crmv@106856e

	//crmv@29875
	var popcont = newjQuery("#popupContainer");
	var newjid = popcont.find('#'+newId);
	//crmv@62414
	if (newjid.length > 0) {
		newjid.remove();
		newjid.length = 0;
	}
	//crmv@62414e
	if (newjid.length == 0) {
		popcont.append('<a id="' + newId + '" href="'+link+'">fancybox</a>');
		newjid = popcont.find('#'+newId);
		newjid.fancybox({
			'width'    : newWidth,
			'height'   : newHeight,
			'autoDimensions': false,
			'autoScale'   : false,
			'fitToView'   : false,
			'autoSize'    : false,
			'transitionIn'  : 'none',
			'transitionOut'  : 'none',
			'type'    : 'iframe',
			'centerOnScroll' : true,
			'showCloseButton' : true,
			'scrolling'   : 'auto',
			'overlayOpacity' : 0.75,
			'padding'   : 0,
			'margin'   : margin,	//crmv@106856
			'live': false,
			'enableEscapeButton' : false	//crmv@59626
			//'speedIn'   : 1000
		});
	} else {
		newjid.attr('href', link);
	}
	newjid.click();
	var maxzindex = findZMax();

	// crmv@98819
	newjQuery("div.fancybox-overlay").css('z-index', maxzindex+1);
	newjQuery("div.fancybox-wrap").css('z-index', maxzindex+2);
	//crmv@22055
	if (spinner != 'nospinner') {
		newjQuery.fancybox.showLoading();	// crmv@82419
		newjQuery(".fancybox-loading").css('z-index', maxzindex+3);
	}
	//crmv@22055e
	//crmv@29875e
	//crmv@53696
	if (newjQuery("a.fancybox-close").length > 0) {
		newjQuery("a.fancybox-close").css('z-index', maxzindex+4);
	} else if (newjQuery(".closebutton").length > 0) {
		newjQuery(".closebutton").css('z-index', maxzindex+4);
	}
	//crmv@53696e crmv@98819e
}

function loadedPopup(topframe) {
	if (topframe == 'top') {
		newjQuery = top.jQuery;
	}
	else if (topframe == 'parent.parent') {
		newjQuery = parent.parent.jQuery;
	}
	else {
		newjQuery = parent.jQuery;
	}
	newjQuery("#fancybox-loading").each(function(i) {
		newjQuery(this).fadeOut();
	});
}

function closePopup(scope) {
	scope = scope || window.parent;
	
	// crmv@184993
	// changes not done from the user don't trigger jquery events, so 
	// conditionals are not triggered when changing uitype 10.
	// Do a dirty trick here to trigger the blur event anyway
	var matches;
	if (matches = window.location.href.match(/&forfield=([^&]*)/)) {
		var popupField = matches[1];
		scope.jQuery('#'+popupField+'_display').trigger('blur');
	}
	// crmv@184993e

	scope.jQuery.fancybox.close();
}
//crmv@21048m e

//crmv@21996	//crmv@22622
function setCookie(c_name,value) {
	var c_value=escape(value);
	document.cookie=c_name + "=" + c_value;
}

function getCookie(c_name) {
	var i,x,y,ARRcookies=document.cookie.split(";");
	for (i=0;i<ARRcookies.length;i++) {
		x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
		y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
		x=x.replace(/^\s+|\s+$/g,"");
		if (x==c_name) {
			return unescape(y);
		}
	}
}
//crmv@21996e	//crmv@22622e

//crmv@25620
function updateBrowserTitle(title) {
	var tmp = '';
	if (title != '') {
		tmp = title;
		if (window.browser_title && browser_title != '') {
			tmp += ' - '+browser_title;
		}
		tmp = tmp.replace(/&amp;/g, "&")
				.replace(/&#039;/g, "'")
				.replace(/&quot;/g, '"')
				.replace(/&lt;/g, '<')
				.replace(/&gt;/g, '>')
				.replace(/&agrave;/g, "a")
				.replace(/&egrave;/g, "e")
				.replace(/&eacute;/g, "e")
				.replace(/&igrave;/g, "i")
				.replace(/&ograve;/g, "o")
				.replace(/&ugrave;/g, "u");
		document.title = tmp;
	}
}
//crmv@25620e

//crmv@26961	crmv@62447 
function linkInviteesTableEditView(entity_id,strVal,parentId,linkedMod) {
	if (parent.jQuery('div#addEventInviteUI').contents().find('#' + entity_id + '_' + linkedMod + '_dest').length < 1) {
		strHtlm = '<tr id="' + entity_id + '_' + linkedMod + '_dest' + '" onclick="checkTr(this.id)">' +
						'<td align="center" style="display:none;"><input type="checkbox" value="' + entity_id + '_' + linkedMod + '"></td>' +
						'<td nowrap align="left" class="parent_name" style="width:100%">' + strVal + '</td>' +
					'</tr>';
		parent.jQuery('#selectedTable').append(strHtlm);
	}
}
//crmv@26961e	crmv@62447e

//crmv@26986
function get_more_favorites() {
	jQuery.ajax({
		url: 'index.php?module=SDK&action=SDKAjax&file=src/Favorites/GetFavoritesList&mode=all',
		type: 'GET',
		success: function(result) {
			jQuery('#favorites_button').hide();
			jQuery('#favorites_div').height(jQuery('#favorites_list').height());
	        jQuery('#favorites_div').css('overflow-y','auto');
	        jQuery('#favorites_div').css('overflow-x','hidden');
	        jQuery('#favorites_list').html(result);
		}
	});
}
//crmv@26986e

//crmv@32429
function getFavoriteList() {
	if (trim(jQuery('#favorites_list').html()) == '') {
		jQuery('#indicatorFavorites').show();
		jQuery.ajax({
			url: 'index.php?module=SDK&action=SDKAjax&file=src/Favorites/GetFavoritesList',
			type: 'GET',
			success: function(result) {
				jQuery('#indicatorFavorites').hide();
				jQuery('#favorites_list').html(result);
			}
		});
	}
}
function getLastViewedList() {
	if (trim(jQuery('#lastviewed_list').html()) == '') {
		jQuery('#indicatorTracker').show();
		jQuery.ajax({
			url: 'index.php?module=Home&action=HomeAjax&file=LastViewed',
			type: 'GET',
			success: function(result) {
				jQuery('#indicatorTracker').hide();
				jQuery('#lastviewed_list').html(result);
			}
		});
	}
}
//crmv@32429e

//crmv@28295	//crmv@30009
function getTodoList() {
	jQuery('#indicatorTodos').show();	//crmv@32429
	jQuery('#todos_list input:checkbox').attr("disabled", true);
	jQuery.ajax({
		url: 'index.php?module=SDK&action=SDKAjax&file=src/Todos/GetTodosList',
		type: 'GET',
		success: function(result) {
			jQuery('#indicatorTodos').hide();	//crmv@32429
	       	jQuery('#todos_button').show();
	       	// crmv@36871
	       	jQuery('#todo_btn_date').click();
	       	jQuery('#todos_div').html(result);
	       	// crmv@36871e
		}
	});
}

function closeTodo(id,checked) {
	if (checked) {
		var status = 'Completed';
	} else {
		var status = 'Not Started';
	}
	jQuery('#todo_'+id).attr("disabled", true);
	jQuery('#todo2_'+id).attr("disabled", true); // crmv@36871
	jQuery.ajax({
		url: 'index.php',
		type: 'POST',
		data: "action=Save&module=Calendar&record="+id+"&change_status=true&status="+status+'&ajaxCalendar=closeTodo',
		success: function(result) {
			NotificationsCommon.drawChangesAndStorage('TodosCheckChangesDiv','TodosCheckChangesImg',result,'Todos');	//crmv@OPER5904
			// crmv@36871
			jQuery('#todos_list_row_'+id).fadeOut('fast', function() {jQuery(this).hide();} );
			var container_id = jQuery('#todos_list_row_'+id).parent().attr('id');
			if (jQuery('#'+container_id+' tr').length <= 2) {
				jQuery('#'+container_id+'_toggle').fadeOut('fast', function() {jQuery(this).hide();} );
			}
			jQuery('#todos2_list_row_'+id).fadeOut('fast', function() {jQuery(this).hide();} );
			container_id = jQuery('#todos2_list_row_'+id).parent().attr('id');
			if (jQuery('#'+container_id+' tr').length <= 1) {
				jQuery('#'+container_id+'_toggle').fadeOut('fast', function() {jQuery(this).hide();} );
			}
			// crmv@36871e
		}
	});
}

// crmv@175394
function get_more_todos() {
	jQuery.ajax({
		url: 'index.php?module=SDK&action=SDKAjax&file=src/Todos/GetTodosList&mode=all',
		method: 'GET',
		success: function(response) {
			if (jQuery('#todos_list').length > 0) {
				// next theme
				jQuery('#todos_list').parent().html(response);
			} else {
				// softed theme
				jQuery('#todos_div').height(jQuery('#todos_list').height());
				jQuery('#todos_div').css('overflow-y','auto');
				jQuery('#todos_div').css('overflow-x','hidden');
				jQuery('#todos_div').html(response); // crmv@36871
			}
			jQuery('#todos_button').hide();
        }
	});
}
// crmv@175394e

function toggleTodoPeriod(id) {
	var div = id;
	var img = '#'+id+'_img';
	if(getObj(div).style.display != "block"){
		getObj(div).style.display = "block";
        jQuery(img).attr("src", resourcever('close_details.png'));
	}else{
		getObj(div).style.display = "none";
        jQuery(img).attr("src", resourcever('open_details.png'));
	}
}
//crmv@28295e	//crmv@30009e
//crmv@36871
function todoShowByDate() {
	jQuery('#todo_btn_date').addClass('todobtn_active');
	jQuery('#todo_btn_duration').removeClass('todobtn_active');
	jQuery('#divTodo_bydate').show();
	jQuery('#divTodo_byduration').hide();
	jQuery('#todos_list').show();
	jQuery('#todos_list_duration').hide();
}

function todoShowByDuration() {
	jQuery('#todo_btn_duration').addClass('todobtn_active');
	jQuery('#todo_btn_date').removeClass('todobtn_active');
	jQuery('#divTodo_bydate').hide();
	jQuery('#divTodo_byduration').show();
	jQuery('#todos_list').hide();
	jQuery('#todos_list_duration').show();
}
//crmv@36871e
//crmv@29190
function getReturnFormName() {
	if( (jQuery('#qcform').css('display') != undefined && jQuery('#qcform').css('display') != 'none')
		|| (parent.jQuery('#qcform').css('display') != undefined && parent.jQuery('#qcform').css('display') != 'none')
	) {
		var formName = 'QcEditView';
	// crmv@106578
	} else if (document.createTodo && document.createTodo.tagName == 'FORM') {
		var formName = 'createTodo';
	// crmv@106578e
	} else {
		var formName = 'EditView';
	}
	return formName;
}
function getReturnForm(formName) {
	if (formName == 'QcEditView' && jQuery('#qcform').css('display') == 'none' && parent.jQuery('#qcform').css('display') != 'none') {
		var form = parent.document.forms[formName];
	} else if (document.forms[formName] != undefined) {
		var form = document.forms[formName];
	} else {
		var form = parent.document.forms[formName];	//crmv@21048m
	}
	return form;
}
function loadFileJs(file) {
	if (typeof(script_included) == 'undefined') {
		jQuery.getScript(file, function(data){eval(data);});
	} else if (jQuery.inArray(file,script_included) < 0) {
		jQuery.getScript(file, function(data){eval(data);});
		script_included.push(file);
	}
}
function enableAdvancedFunction(form) {
	if (form.id == 'massedit_form' || form.id == 'customview_form') {
		return false;
	} else {
		return true;
	}
}
//crmv@29190e

// link moduleFrom to moduleTo using the standard updateRelation
function linkModules(moduleFrom, recordFrom, moduleTo, recordTo, extraParams, callback) {
	if (recordTo.length && typeof recordTo != 'string') recordTo = recordTo.join(';');

	var params = {
		'module' : moduleFrom,
		'action' : moduleFrom+'Ajax',
		'file' : 'updateRelations',
		'parentid' : recordFrom,
		'destination_module' : moduleTo,
		'idlist' : recordTo,
		'no_redirect' : 'true',
	};

	jQuery.ajax({
		'url': 'index.php?' + jQuery.param(params),
		'type': 'POST',
		'data': ( extraParams && !jQuery.isEmptyObject(extraParams) ? '&' + jQuery.param(extraParams) : '' ),
		success: function(data) {
			if (typeof callback == 'function') return callback(data, 'success');
		},
		error: function() {
			if (typeof callback == 'function') return callback('error');
		}

	})

}
//crmv@43050e

//crmv@30356
function isMobile() {
	if (navigator.userAgent.match(/Android/i)
		|| navigator.userAgent.match(/webOS/i)
		|| navigator.userAgent.match(/iPhone/i)
		|| navigator.userAgent.match(/iPad/i)
		|| navigator.userAgent.match(/iPod/i)
		|| navigator.userAgent.match(/BlackBerry/i)
	){
		return true;
	} else {
		return false
	}
}
//crmv@30356e
//crmv@30828
function loadContentGantt(image) {
	var string = '<img src="'+image+'" />'
	jQuery('#div_gantt').width(jQuery('#div_gantt').parent().width());
	getObj('div_gantt').innerHTML = string;
}
//crmv@30828e
//crmv@31126
function convertOptionsToJSONArray(objName,targetObjName) {
	var obj = getObj(objName); //fix
	var arr = [];
	if(typeof(obj) != 'undefined') {
		for (i=0; i<obj.options.length; ++i) {
			arr.push(obj.options[i].value);
		}
	}
	if(targetObjName != 'undefined') {
		var targetObj = getObj(targetObjName); //fix
		if(typeof(targetObj) != 'undefined') targetObj.value = JSON.stringify(arr);
	}
	return arr;
}
function copySelectedOptions(source, destination) {

	var srcObj = jQuery('#'+source).get(0);
	var destObj = jQuery('#'+destination).get(0);

	if(typeof(srcObj) == 'undefined' || typeof(destObj) == 'undefined') return;

	for (i=0;i<srcObj.length;i++) {
		if (srcObj.options[i].selected==true) {
			var rowFound=false;
			var existingObj=null;
			for (j=0;j<destObj.length;j++) {
				if (destObj.options[j].value==srcObj.options[i].value) {
					rowFound=true
					existingObj=destObj.options[j]
					break
				}
			}

			if (rowFound!=true) {
				var newColObj=document.createElement("OPTION")
				newColObj.value=srcObj.options[i].value
				if (browser_ie) newColObj.innerText=srcObj.options[i].innerText
				else if (browser_nn4 || browser_nn6) newColObj.text=srcObj.options[i].text
				destObj.appendChild(newColObj)
				srcObj.options[i].selected=false
				newColObj.selected=true
				rowFound=false
			} else {
				if(existingObj != null) existingObj.selected=true
			}
		}
	}
}

function removeSelectedOptions(objName) {
	var obj = getObj(objName);
	if(obj == null || typeof(obj) == 'undefined') return;

	for (i=obj.options.length-1;i>=0;i--) {
		if (obj.options[i].selected == true) {
			obj.options[i] = null;
		}
	}
}
//crmv@31126e
//crmv@32091
function cleanArray(actual){
	var newArray = new Array();
	for(var i = 0; i<actual.length; i++){
		var value = actual[i].trim();
		if (value && value != undefined && value != '') {
			newArray.push(actual[i]);
		}
	}
	return newArray;
}
//crmv@32091e

//crmv@43147
function openShareRecord(record, type) {
	OpenCompose(record, 'share');
}
//crmv@43147e

//crmv@43864
function destroySlimscroll(objectId) {
	jQuery("#"+objectId).parent('.slimScrollDiv').replaceWith(jQuery("#"+objectId));
}
//crmv@43864e

function amIinPopup() {
	return parent != window;
}

// crmv@192033 - removed functions

// QuickCreare functions - start
//crmv@31197
function NewQCreate(module){
	if(module != 'none'){
		var urlstr = '';
		if(module == 'Events'){
			module = 'Calendar';
			urlstr += '&activity_mode=Events';
		}else if(module == 'Calendar'){
			module = 'Calendar';
			urlstr += '&activity_mode=Task';
		}
		window.open('index.php?module='+module+'&action=EditView'+urlstr+'&close_window=yes','_blank');	//crmv@56945
	}
}
//crmv@31197e
function QCreate(module,ajaxForm,ajaxSuccessFunction,otherpar){	//crmv@29506
	//var module = qcoptions.options[qcoptions.options.selectedIndex].value;
	if(module != 'none'){
		jQuery('#status').show();
		var urlstr = '';
		if(module == 'Events'){
			module = 'Calendar';
			urlstr += '&activity_mode=Events';
		}else if(module == 'Calendar'){
			module = 'Calendar';
			urlstr += '&activity_mode=Task';
		}
		//crmv@29506
		if (otherpar != undefined && otherpar != null && otherpar != '') {
			urlstr += otherpar;
		}
		//crmv@29506e
		jQuery.ajax({
			url: 'index.php',
			type: 'POST',
			data: 'module='+module+'&action='+module+'Ajax&file=QuickCreate'+urlstr,
			success: function(result) {
				jQuery('#status').hide();
					
				VTE.showModal('qcform', '', result, {
					large: true,
				});
					
				//crmv@29506
				if (ajaxForm == 'ajaxForm') {
					var options = {
						beforeSubmit:	'',  					// pre-submit callback
						success: eval(ajaxSuccessFunction),  	// post-submit callback
						dataType: 'json'						// 'xml', 'script', or 'json' (expected server response type)
					};
					jQuery('#QcEditView').ajaxForm(options);
				}
				//crmv@29506e
			}
		});
		
	} else {
		VTE.hideModal('qcform');
	}
}

function getFormValidate(divValidate) {
  if (checkJSOverride(arguments)) return callJSOverride(arguments);
  if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

  var st = document.getElementById('qcvalidate');
  eval(st.innerHTML);
  for (var i=0; i<qcfieldname.length; i++) {
		var curr_fieldname = qcfieldname[i];
		if(window.document.QcEditView[curr_fieldname] != null)
		{
			var type=qcfielddatatype[i].split("~")
			var input_type = window.document.QcEditView[curr_fieldname].type;
			if (type[1]=="M") {
					if (!qcemptyCheck(curr_fieldname,qcfieldlabel[i],input_type))
						return false
				}
			switch (type[0]) {
				case "O"  : break;
				case "V"  : break;
				case "C"  : break;
				case "DT" :
					if (window.document.QcEditView[curr_fieldname] != null && window.document.QcEditView[curr_fieldname].value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
					{
						if (type[1]=="M")
							if (!qcemptyCheck(type[2],qcfieldlabel[i],getObj(type[2]).type))
								return false
						if(typeof(type[3])=="undefined") var currdatechk="OTH"
						else var currdatechk=type[3]

						if (!qcdateTimeValidate(curr_fieldname,type[2],qcfieldlabel[i],currdatechk))
							return false
						if (type[4]) {
							if (!dateTimeComparison(curr_fieldname,type[2],qcfieldlabel[i],type[5],type[6],type[4]))
								return false

						}
					}
				break;
				case "D"  :
					if (window.document.QcEditView[curr_fieldname] != null && window.document.QcEditView[curr_fieldname].value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
					{
						if(typeof(type[2])=="undefined") var currdatechk="OTH"
						else var currdatechk=type[2]

							if (!qcdateValidate(curr_fieldname,qcfieldlabel[i],currdatechk))
								return false
									if (type[3]) {
										if (!qcdateComparison(curr_fieldname,qcfieldlabel[i],type[4],type[5],type[3]))
											return false
									}
					}
				break;
				case "T"  :
					if (window.document.QcEditView[curr_fieldname] != null && window.document.QcEditView[curr_fieldname].value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
					{
						if(typeof(type[2])=="undefined") var currtimechk="OTH"
						else var currtimechk=type[2]

							if (!timeValidate(curr_fieldname,qcfieldlabel[i],currtimechk))
								return false
									if (type[3]) {
										if (!timeComparison(curr_fieldname,qcfieldlabel[i],type[4],type[5],type[3]))
											return false
									}
					}
				break;
				case "I"  :
					if (window.document.QcEditView[curr_fieldname] != null && window.document.QcEditView[curr_fieldname].value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
					{
						if (window.document.QcEditView[curr_fieldname].value.length!=0)
						{
							if (!qcintValidate(curr_fieldname,qcfieldlabel[i]))
								return false
							if (type[2]) {
								if (!qcnumConstComp(curr_fieldname,qcfieldlabel[i],type[2],type[3]))
									return false
							}
						}
					}
				break;
				case "N"  :
					case "NN" :
					if (window.document.QcEditView[curr_fieldname] != null && window.document.QcEditView[curr_fieldname].value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
					{
						if (window.document.QcEditView[curr_fieldname].value.length!=0)
						{
							if (typeof(type[2])=="undefined") var numformat="any"
							else var numformat=type[2]

								if (type[0]=="NN") {

									if (!numValidate(curr_fieldname,qcfieldlabel[i],numformat,true))
										return false
								} else {
									if (!numValidate(curr_fieldname,qcfieldlabel[i],numformat))
										return false
								}
							if (type[3]) {
								if (!numConstComp(curr_fieldname,qcfieldlabel[i],type[3],type[4]))
									return false
							}
						}
					}
				break;
				case "E"  :
					if (window.document.QcEditView[curr_fieldname] != null && window.document.QcEditView[curr_fieldname].value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
					{
						if (window.document.QcEditView[curr_fieldname].value.length!=0)
						{
							var etype = "EMAIL"
								if (!qcpatternValidate(curr_fieldname,qcfieldlabel[i],etype))
									return false
						}
					}
				break;
			}
		}
	}
	//added to check Start Date & Time,if Activity Status is Planned.//start
	for (var j=0; j<qcfieldname.length; j++)
	{
		curr_fieldname = qcfieldname[j];
		if(window.document.QcEditView[curr_fieldname] != null)
		{
			if(qcfieldname[j] == "date_start")
			{
				var datelabel = qcfieldlabel[j]
					var datefield = qcfieldname[j]
					var startdatevalue = window.document.QcEditView[datefield].value.replace(/^\s+/g, '').replace(/\s+$/g, '')
			}
			if(qcfieldname[j] == "time_start")
			{
				var timelabel = qcfieldlabel[j]
					var timefield = qcfieldname[j]
					var timeval=window.document.QcEditView[timefield].value.replace(/^\s+/g, '').replace(/\s+$/g, '')
			}
			if(qcfieldname[j] == "eventstatus" || qcfieldname[j] == "taskstatus")
			{
				var statusvalue = window.document.QcEditView[curr_fieldname].options[window.document.QcEditView[curr_fieldname].selectedIndex].value.replace(/^\s+/g, '').replace(/\s+$/g, '')
				var statuslabel = qcfieldlabel[j++]
			}
		}
	}
	if(statusvalue == "Planned")
	{
		var dateelements=splitDateVal(startdatevalue)
		var hourval=parseInt(timeval.substring(0,timeval.indexOf(":")))
		var minval=parseInt(timeval.substring(timeval.indexOf(":")+1,timeval.length))
		var dd=dateelements[0]
		var mm=dateelements[1]
		var yyyy=dateelements[2]

		var chkdate=new Date()
		chkdate.setYear(yyyy)
		chkdate.setMonth(mm-1)
		chkdate.setDate(dd)
		chkdate.setMinutes(minval)
		chkdate.setHours(hourval)
	}//end
	//crmv@sdk-18501	//crmv@sdk-26260
	sdkValidate = SDKValidate(window.document.QcEditView);
	if (sdkValidate) {
		sdkValidateResponse = eval('('+sdkValidate.responseText+')');
		if (!sdkValidateResponse['status']) {
			return false;
		}
	}
	//crmv@sdk-18501e	//crmv@sdk-26260e
	return true;
}
// QuickCreare functions - end

function fetch_clock() {
	jQuery.ajax({
		url: 'index.php?module=Utilities&action=UtilitiesAjax&file=Clock',
		method: 'GET',
		success: function(result) {
			jQuery("#clock_cont").html(result);
			jQuery("#clock_cont").css('zIndex', findZMax()+1);//crmv@21048m
		}
	});
}

function fetch_calc() {
	jQuery.ajax({
		url: 'index.php?module=Utilities&action=UtilitiesAjax&file=Calculator',
		method: 'GET',
		success: function(result) {
			jQuery("#calculator_cont").html(result);
		}
	});
}

function sprintf() {
	//  discuss at: http://phpjs.org/functions/sprintf/
	// original by: Ash Searle (http://hexmen.com/blog/)
	// improved by: Michael White (http://getsprink.com)
	// improved by: Jack
	// improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// improved by: Dj
	// improved by: Allidylls
	//    input by: Paulo Freitas
	//    input by: Brett Zamir (http://brett-zamir.me)
	//   example 1: sprintf("%01.2f", 123.1);
	//   returns 1: 123.10
	//   example 2: sprintf("[%10s]", 'monkey');
	//   returns 2: '[    monkey]'
	//   example 3: sprintf("[%'#10s]", 'monkey');
	//   returns 3: '[####monkey]'
	//   example 4: sprintf("%d", 123456789012345);
	//   returns 4: '123456789012345'
	//   example 5: sprintf('%-03s', 'E');
	//   returns 5: 'E00'

	var regex = /%%|%(\d+\$)?([-+\'#0 ]*)(\*\d+\$|\*|\d+)?(\.(\*\d+\$|\*|\d+))?([scboxXuideEfFgG])/g;
	var a = arguments;
	var i = 0;
	var format = a[i++];

	// pad()
	var pad = function(str, len, chr, leftJustify) {
		if (!chr) {
			chr = ' ';
		}
		var padding = (str.length >= len) ? '' : new Array(1 + len - str.length >>> 0)
		.join(chr);
		return leftJustify ? str + padding : padding + str;
	};

	// justify()
	var justify = function(value, prefix, leftJustify, minWidth, zeroPad, customPadChar) {
		var diff = minWidth - value.length;
		if (diff > 0) {
			if (leftJustify || !zeroPad) {
				value = pad(value, minWidth, customPadChar, leftJustify);
			} else {
				value = value.slice(0, prefix.length) + pad('', diff, '0', true) + value.slice(prefix.length);
			}
		}
		return value;
	};

	// formatBaseX()
	var formatBaseX = function(value, base, prefix, leftJustify, minWidth, precision, zeroPad) {
		// Note: casts negative numbers to positive ones
		var number = value >>> 0;
		prefix = prefix && number && {
      '2': '0b',
      '8': '0',
      '16': '0x'
		}[base] || '';
		value = prefix + pad(number.toString(base), precision || 0, '0', false);
		return justify(value, prefix, leftJustify, minWidth, zeroPad);
	};

	// formatString()
	var formatString = function(value, leftJustify, minWidth, precision, zeroPad, customPadChar) {
		if (precision != null) {
			value = value.slice(0, precision);
		}
		return justify(value, '', leftJustify, minWidth, zeroPad, customPadChar);
	};

	// doFormat()
	var doFormat = function(substring, valueIndex, flags, minWidth, _, precision, type) {
		var number, prefix, method, textTransform, value;

		if (substring === '%%') {
			return '%';
		}

		// parse flags
		var leftJustify = false;
		var positivePrefix = '';
		var zeroPad = false;
		var prefixBaseX = false;
		var customPadChar = ' ';
		var flagsl = flags.length;
		for (var j = 0; flags && j < flagsl; j++) {
			switch (flags.charAt(j)) {
				case ' ':
					positivePrefix = ' ';
					break;
				case '+':
					positivePrefix = '+';
					break;
				case '-':
					leftJustify = true;
					break;
				case "'":
					customPadChar = flags.charAt(j + 1);
					break;
				case '0':
					zeroPad = true;
					customPadChar = '0';
					break;
				case '#':
					prefixBaseX = true;
					break;
			}
		}

		// parameters may be null, undefined, empty-string or real valued
		// we want to ignore null, undefined and empty-string values
		if (!minWidth) {
			minWidth = 0;
		} else if (minWidth === '*') {
			minWidth = +a[i++];
		} else if (minWidth.charAt(0) == '*') {
			minWidth = +a[minWidth.slice(1, -1)];
		} else {
			minWidth = +minWidth;
		}

		// Note: undocumented perl feature:
		if (minWidth < 0) {
			minWidth = -minWidth;
			leftJustify = true;
		}

		if (!isFinite(minWidth)) {
			throw new Error('sprintf: (minimum-)width must be finite');
		}

		if (!precision) {
			precision = 'fFeE'.indexOf(type) > -1 ? 6 : (type === 'd') ? 0 : undefined;
		} else if (precision === '*') {
			precision = +a[i++];
		} else if (precision.charAt(0) == '*') {
			precision = +a[precision.slice(1, -1)];
		} else {
			precision = +precision;
		}

		// grab value using valueIndex if required?
		value = valueIndex ? a[valueIndex.slice(0, -1)] : a[i++];

		switch (type) {
			case 's':
				return formatString(String(value), leftJustify, minWidth, precision, zeroPad, customPadChar);
			case 'c':
				return formatString(String.fromCharCode(+value), leftJustify, minWidth, precision, zeroPad);
			case 'b':
				return formatBaseX(value, 2, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
			case 'o':
				return formatBaseX(value, 8, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
			case 'x':
				return formatBaseX(value, 16, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
			case 'X':
				return formatBaseX(value, 16, prefixBaseX, leftJustify, minWidth, precision, zeroPad)
				.toUpperCase();
			case 'u':
				return formatBaseX(value, 10, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
			case 'i':
			case 'd':
				number = +value || 0;
				number = Math.round(number - number % 1); // Plain Math.round doesn't just truncate
				prefix = number < 0 ? '-' : positivePrefix;
				value = prefix + pad(String(Math.abs(number)), precision, '0', false);
				return justify(value, prefix, leftJustify, minWidth, zeroPad);
			case 'e':
			case 'E':
			case 'f': // Should handle locales (as per setlocale)
			case 'F':
			case 'g':
			case 'G':
				number = +value;
				prefix = number < 0 ? '-' : positivePrefix;
				method = ['toExponential', 'toFixed', 'toPrecision']['efg'.indexOf(type.toLowerCase())];
				textTransform = ['toString', 'toUpperCase']['eEfFgG'.indexOf(type) % 2];
				value = prefix + Math.abs(number)[method](precision);
				return justify(value, prefix, leftJustify, minWidth, zeroPad)[textTransform]();
			default:
				return substring;
		}
	};

	return format.replace(regex, doFormat);
}

//crmv@55030
function removePageSelection() {
	if (window.getSelection) {
		if (window.getSelection().empty) {  // Chrome
			window.getSelection().empty();
		} else if (window.getSelection().removeAllRanges) {  // Firefox
			window.getSelection().removeAllRanges();
		}
	} else if (document.selection) {  // IE?
		document.selection.empty();
	}
}
//crmv@55030e

//crmv@59626
function bindButtons(doc) {
	jQuery(document).keyup(function(e) {
		if (doc == undefined) bindButton(e.keyCode);
		else doc.bindButton(e.keyCode);
	});
	// crmv@104776
	jQuery(document).mouseup(function(e) {
		if (doc == undefined) bindMouseUp(e);
		else doc.bindMouseUp(e);
	});
	// crmv@104776e
}
function bindButton(code) {
	if (code == 27) {	// EscapeButton
		var zmax = 0;
		var zmaxObj;
		jQuery('.fancybox-overlay:visible, .fancybox-wrap:visible, .crmvDiv:visible, .calAddEvent:visible').each(function(){
			var cur = parseInt(jQuery(this).css('zIndex'));
			if (isNaN(cur)) cur = 1;	//crmv@92272
    		if (cur > zmax) {
    			zmax = cur;
    			zmaxObj = jQuery(this);
    		}
		});
		//crmv@103781
		if (jQuery('#ModCommentsNews:visible').length > 0) {
			if (jQuery('#ModCommentsNews_iframe').contents().find('.fancybox-wrap:visible, .crmvDiv:visible').length > 0) {
				var zmax = 0;
				var zmaxObj;
			}
			jQuery('#ModCommentsNews_iframe').contents().find('.fancybox-wrap:visible, .crmvDiv:visible').each(function(){
				var cur = parseInt(jQuery(this).css('zIndex'));
				if (isNaN(cur)) cur = 1;	//crmv@92272
	    		if (cur > zmax) {
	    			zmax = cur;
	    			zmaxObj = jQuery(this);
	    		}
			});
		}
		//crmv@103781e
		// crmv@124066
		if (zmaxObj != undefined) {
			if (zmaxObj.hasClass('fancybox-overlay') || zmaxObj.hasClass('fancybox-wrap')) {
				// old popup
				var btn = zmaxObj.find('.fancybox-close');
			} else {
				// new popup
				var btn = zmaxObj.find('.closebutton');
			}
			if (btn && btn.length > 0 && checkClosePopup(zmaxObj)) {
				btn.click();
			}
		} else {
			// crmv@166949
			if (window.sendMessageFromPanel) { 
				sendMessageFromPanel({ name: 'closePanel' });
			}
			// crmv@166949e
		}
		// crmv@124066e
	}
}

// crmv@124066
function checkClosePopup($obj) {
	var askConfirm = false;
	
	if ($obj && $obj.length > 0) {
		var id = $obj.get(0).id,
			$area = null;
		if (id == 'ModCommentsNews') {
			var $area = jQuery('#ModCommentsNews_iframe').contents().find('textarea');
		} else if (id == 'trackerPopup') {
			var $area = $obj.find('textarea');
		}
		// add here more cases if needed
		if ($area && $area.length > 0 && $area.val() != '' && $area.val() != $area[0].defaultValue) askConfirm = true;
	}
	if (askConfirm) {
		return confirm(alert_arr.LBL_CONFIRM_CLOSE_POPUP);
	}
	return true;
}
// crmv@124066e

// crmv@104776
function bindMouseUp(e) {
	var miniCalCont = jQuery('#BBIT_DP_CONTAINER');
	if (miniCalCont.length > 0 || window.frames['wdCalendar']) {
		if (miniCalCont.length < 1 && wdCalendar && wdCalendar.jQuery) {
			miniCalCont = wdCalendar.jQuery('#BBIT_DP_CONTAINER');
		}
		if (miniCalCont.length > 0) {
			if (!miniCalCont.is(e.target) && miniCalCont.has(e.target).length === 0) {
				miniCalCont.hide();
			}
		}
	}
}
// crmv@104776e

//crmv@59626e

function roundValueFloat(number, decimals) { // Arguments: number to round, number of decimal places
	if (decimals == undefined) decimals = decimals_num || 2;
	var multiplier = Math.pow(10, decimals),
		bias =  Math.pow(10, -(decimals+2)),
		newnumber = Math.round(((parseFloat(number)+bias) * multiplier)) / multiplier;
	// the old one is not reliable due to internal float representation and browser implementations.
	// Examples:
	// 0.35.toFixed(1) = 0.3
	// 0.45.toFixed(1) = 0.5
	//var newnumber = new Number(number+'').toFixed(parseInt(decimals));
	return newnumber;
}

function roundValue(number, decimals) { // Arguments: number to round, number of decimal places
	if (decimals == undefined) decimals = decimals_num || 2;
	return roundValueFloat(number, decimals).toFixed(decimals);
}

// crmv@83877
// parse a number in user format (using decimal/thousand separator)
// returns a float number
// returns 0 in case of error
function parseUserNumber(rawNumber) {
	/* algo
	 * 1. if no decimal or thousands separator, convert it straight
	 * 2. if contains thousand sep, remove it
	 * 3. if contains dec separator, convert it to "."
	 * 4. use parsefloat
	 * 5. round up to working precision
	 */
	if (typeof rawNumber === 'undefined' || rawNumber === null) return 0.0;
	if (typeof rawNumber == 'number') return rawNumber;

	rawNumber = trim(rawNumber);

	if (
		(decimal_separator == '' || rawNumber.indexOf(decimal_separator) == -1) &&
		(thousands_separator == '' || rawNumber.indexOf(thousands_separator) == -1))
	{
		return parseFloat(rawNumber);
	}

	// remove thousands and convert decimal point
	// crmv@135258
	if (thousands_separator != '') {
		var ths_re = new RegExp(thousands_separator.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"), 'g');
		rawNumber = rawNumber.replace(ths_re, '');
	}
	// crmv@135258e
	if (decimal_separator != '') rawNumber = rawNumber.replace(decimal_separator, '.');

	// round the number to the desired precision
	var outNumber = Number(parseFloat(rawNumber).toFixed(decimals_num));

	return outNumber;
}

// format a float number in the user format
// number must be a float
// return '0.00' in case of NaN or the formatted string otherwise
function formatUserNumber(n) {
	/* algo:
	 * 1. round up to output precision and convert to string
	 * 2. convert the decimal separator to the desired symbol
	 * 3. split thousands in the right way
	 */
	if (isNaN(n) || n === null || n === '') n = 0.00;
	var isNegative = (n < 0);

	number_parts = roundValue(Math.abs(n)).split('.');
	if (number_parts[0].length > 3) {
		var reversed = number_parts[0].split("").reverse().join(""),
			thousands = reversed.match(/.{1,3}/g).join(thousands_separator),
			thousands = thousands.split('').reverse().join('');
		number_parts[0] = thousands;
	}
	n = (isNegative ? '-' : '') + number_parts.join(decimal_separator);
	return n;
}

// crmv@83878
// consider the empty string as valid
// you can pass an optional decimal and thousand separator,
// otherwise the current user's ones will be used
function validateUserNumber(n, dsep, tsep) {
	if (typeof n == 'undefined' || n === null) return false;
	if (n === '' || typeof n == 'number') return true

	if (typeof dsep == 'undefined' || dsep === null) dsep = decimal_separator;
	if (typeof tsep == 'undefined' || tsep === null) tsep = thousands_separator;
		
	var hasTS = (n.indexOf(tsep) > -1),
		ds = (dsep == '.' ? '\\.' : dsep),
		ts = (tsep == '.' ? '\\.' : tsep),
		// strict compliance
		regexp = new RegExp('^-?[0-9]{1,3}('+ts+'[0-9]{3})*('+ds+'[0-9]+)?$', ''),
		// without thousands
		regexp_noTS = new RegExp('^-?[0-9]+('+ds+'[0-9]+)?$', '');

	return !!n.match(hasTS ? regexp : regexp_noTS); // crmv@118320
}
// crmv@83878e
// crmv@83877e

//crmv@83228 crmv@101506 crmv@104566 crmv@104975 crmv@105937
function changeTab(module, crmid, tabname, self, mode, goto) {
	var panelid = parseInt(tabname);
	
	if (typeof mode == 'undefined' || mode === null) {
		// try to autodetect
		if (jQuery('#EditViewTabs').length > 0) {
			mode = 'edit';
		} else {
			mode = 'detail';
		}
	}
	
	// hide sharkpanel (it's custom)
	jQuery('#potPanelMainDiv').hide();
	jQuery('#dynamicTargetsPanel').hide(); // crmv@150024
	
	if (panelid > 0) {
		// standard tab
		goToPanelTab(module, crmid, panelid, self, mode, goto);
	} else {
		// tab with name (extra tab)
		goToNamedTab(module, crmid, tabname, self, mode, goto);
	}

	if (window.Theme) Theme.adjustComponents();
}

function goToPanelTab(module, crmid, panelid, self, mode, goto) {
	var contId = (mode == 'detail' ? 'DetailViewTabs' : 'EditViewTabs');
	
	if (!window.panelBlocks) {
		console.error('Missing panelBlocks variable');
		return;
	}
	var showPanel = panelBlocks[panelid],
		showBlocks = showPanel ? showPanel.blockids : [],
		classname = (mode == 'detail' ? 'detailBlock' : 'editBlock'),
		classSelector = '.' + classname;

	jQuery('div.'+classname).each(function(idx, el) {
		var bid_str = el.id.replace('block_', '');
		var bid = parseInt(bid_str);
		if (showBlocks.indexOf(bid) >= 0 || showBlocks.indexOf(bid_str) >= 0) {
			// show it!
			jQuery(el).show();
		} else {
			// hide it!
			jQuery(el).hide();
		}
	});
	jQuery('#'+contId+' .dvtSelectedCell').removeClass('dvtSelectedCell').addClass('dvtUnSelectedCell');
	if (!self) {
		self = jQuery('#'+contId+' td[data-panelid='+panelid+']');
	}
	jQuery(self).removeClass('dvtUnSelectedCell').addClass('dvtSelectedCell');
	
	// show bocks and related divs
	jQuery('.detailTabsMainDiv').hide();
	jQuery('#DetailViewBlocks').show();
	if (jQuery('#turboLiftContainer').attr('hide_turbolift') != 'yes') jQuery('#turboLiftContainer').show();	//crmv@151688
	jQuery('#DynamicRelatedList').show();
	jQuery('#DetailViewWidgets').show();
	jQuery('#calendarExtraTable').show(); // crmv@107341
	// products block is handled like a block, but it's in a different div
	jQuery('#proTab').closest(classSelector).show(); 
	jQuery('#finalProTab').closest(classSelector).show();
	
	//crmv@104562
	if (window.HistoryTabScript) HistoryTabScript.hideTab();
	if (window.GanttScript) GanttScript.hideTab();
	if (window.ProcessScript) ProcessScript.hideTab();
	//crmv@104562e
	if (window.StatisticsScript) StatisticsScript.hideTab(); //crmv@152532
	
	window.currentPanelId = panelid;
	
	// align the related
	if (mode == 'detail' && typeof window.alignTabRelated == 'function') {
		alignTabRelated(panelid, goto);
	}
}

function goToNamedTab(module, crmid, tabname, self, mode, goto) {
	var contId = (mode == 'detail' ? 'DetailViewTabs' : 'EditViewTabs'),
		classname = (mode == 'detail' ? 'detailBlock' : 'editBlock'),
		classSelector = '.' + classname;
	
	if (tabname == 'DetailViewBlocks') {
		jQuery('.detailTabsMainDiv').hide();
	} else {
		jQuery('#DetailViewBlocks').hide();
		jQuery('#DetailViewWidgets').hide();
		jQuery('#turboLiftContainer').hide();
		jQuery('#DynamicRelatedList').hide();
		jQuery('#proTab').closest(classSelector).hide();
		jQuery('#finalProTab').closest(classSelector).hide();
		jQuery('#calendarExtraTable').hide(); // crmv@107341
		jQuery('.detailTabsMainDiv').show();
		jQuery('#'+tabname).show();
	}
	if (tabname == 'DetailViewBlocks') {
		jQuery('#proTab').closest(classSelector).show();
		jQuery('#finalProTab').closest(classSelector).show();
		jQuery('#calendarExtraTable').show(); // crmv@107341
		if (jQuery('#turboLiftContainer').attr('hide_turbolift') != 'yes') jQuery('#turboLiftContainer').show();	//crmv@151688
		jQuery('#DetailViewWidgets').show();
	}
	else if (tabname == 'detailCharts' && window.VTECharts) VTECharts.refreshAll();
	
	//crmv@176621
	if (window.ProcessScript) {
		if (tabname == 'ProcessGraph') {
			if (jQuery('#ProcessGraph').length > 0) {
				ProcessScript.showTab(module,crmid);
			} else {
				jQuery('#status').show();
				jQuery.ajax({
					'url': 'index.php?module=Processes&action=ProcessesAjax&file=DetailViewAjax&ajxaction=SHOWGRAPHTAB&rel_module='+module+'&record='+crmid,
					'type': 'POST',
					success: function(data) {
						if (data.indexOf('id="ProcessGraph"') > 0)
							jQuery('#DetailExtraBlock').append(data);
						else {
							jQuery('#DetailExtraBlock').append('<div id="ProcessGraph" class="detailTabsMainDiv vte-card" style="display:none"></div>');
							jQuery('#ProcessGraph').html(data);
						}
						jQuery('#status').hide();
						ProcessScript.showTab(module,crmid);
					}
				});
			}
		}
		else ProcessScript.hideTab();
	}
	//crmv@176621e
	// crmv@188364
	if (window.ProcessHistoryScript) {
		if (tabname == 'ProcessHistory') {
			if (jQuery('#ProcessHistory').length > 0) {
				ProcessHistoryScript.showTab(module,crmid);
			} else {
				jQuery('#status').show();
				jQuery.ajax({
					'url': 'index.php?module=Processes&action=ProcessesAjax&file=DetailViewAjax&ajxaction=SHOWHISTORYTAB&rel_module='+module+'&record='+crmid,
					'type': 'POST',
					success: function(data) {
						if (data.indexOf('id="ProcessHistory"') > 0)
							jQuery('#DetailExtraBlock').append(data);
						else {
							jQuery('#DetailExtraBlock').append('<div id="ProcessHistory" class="detailTabsMainDiv vte-card" style="display:none"></div>');
							jQuery('#ProcessHistory').html(data);
						}
						jQuery('#status').hide();
						ProcessHistoryScript.showTab(module,crmid);
					}
				});
			}
		}
		else ProcessHistoryScript.hideTab();
	}
	// crmv@188364e
	if (window.HistoryTabScript) {
		if (tabname == 'HistoryTab') HistoryTabScript.showTab(module,crmid);
		else HistoryTabScript.hideTab();
	}
	//crmv@104562
	if (window.GanttScript) {
		if (tabname == 'Gantt') GanttScript.showTab(module,crmid);
		else GanttScript.hideTab();
	}
	//crmv@104562e
	//crmv@152532
	if (window.StatisticsScript) {
		if (tabname == 'Statistics') StatisticsScript.showTab(module,crmid);
		else StatisticsScript.hideTab();
	}
	//crmv@152532e
	
	jQuery('#'+contId+' .dvtSelectedCell').removeClass('dvtSelectedCell').addClass('dvtUnSelectedCell');
	jQuery(self).removeClass('dvtUnSelectedCell').addClass('dvtSelectedCell');
	jQuery('#'+tabname).show();
}

function changeDetailTab(module, crmid, tabname, self, goto) {
	return changeTab(module, crmid, tabname, self, 'detail', goto);
}

function changeEditTab(module, crmid, tabname, self, goto) {
	return changeTab(module, crmid, tabname, self, 'edit', goto);
}

//crmv@83228e crmv@101506e crmv@104566e crmv@104975e crmv@105937e


//crmv@90004
function showPencil(folderid,state){
	jQuery('#pencil_'+folderid).stop(true);
	if(state == 1){
 		jQuery('#pencil_'+folderid).fadeIn('fast');
	}else if(state == 2){
		jQuery('#pencil_'+folderid).fadeOut('fast');
	}else{
		jQuery('#pencil_'+folderid).show();
	}
}
function folder_edit(obj,Lay,module,id,mode,filecount){
	mode = mode || '';
	jQuery('#status').show();

	if (mode == 'save') {
		foldername = document.getElementById('foldername').value;
		description = document.getElementById('folderdesc').value;
		id = document.getElementById('folderid').value;
		filecount = document.getElementById('filecount').value;
			
		jQuery.ajax({
			url: 'index.php?module='+module+'&action='+module+'Ajax&file=FolderEdit',
			type: 'post',
			data: {
				'folderid': id,
				'mode': mode,
				'foldername': foldername,
				'description': description
			},
			dataType: 'json',
			success: function(response) {
				var lview_folder_span = 'lview_folder_span_'+id;
				var lview_folder_desc = 'lview_folder_desc_'+id;
				
				var new_foldername = response.foldername+" ("+filecount+")";
				
				jQuery('.'+lview_folder_span).html(new_foldername);
				jQuery('.'+lview_folder_desc).html(response.description);
				
				hideFloatingDiv('lview_folder_edit');
				jQuery('#status').hide();
			}
		});
	} else {
		jQuery.ajax({
			url: 'index.php?module='+module+'&action='+module+'Ajax&file=FolderEdit&mode=script',
			type: 'post',
			data: {'folderid':id},
			dataType: 'json',
			success: function(response) {
				document.getElementById("folderid").value = id;
				document.getElementById("foldername").value = response.foldername;
				document.getElementById("folderdesc").value = response.description;
				document.getElementById("filecount").value = filecount;
				showFloatingDiv('lview_folder_edit');
				jQuery('#status').hide();
			}
		});
	}
}
//crmv@90004e
// crmv@100585 crmv@128159
function setupDatePicker(fieldid, options) {
	
	// default options
	options = jQuery.extend({}, {
		//trigger: null
		date: true,
		time: false,
		date_format: '',
		language: 'en_us',
		weekstart: (window.current_user && ('weekstart' in current_user) ? current_user.weekstart : 1), // crmv@150808
	}, options || {});
	
	if (!jQuery.fn.bootstrapMaterialDatePicker) {
		console.log('Material DatePicker not loaded. Unable to initialize datepicker');
		return;
	}
	
	if (typeof fieldid == 'string') {
		// fieldid may contain spaces, so I can't use jquery "#id" selector
		var field = document.getElementById(fieldid);
		if (!field) return;
		var $field = jQuery(field);
	} else if (fieldid instanceof jQuery) {
		var $field = fieldid;
	} else {
		// unknwown type for fieldid
		console.log('The specified field is not supported');
		return;
	}
	
	var dpopts = {
		date: options.date,
		time: options.time,
		format: options.date_format, 
		lang: options.language,
		weekStart: options.weekstart, // crmv@150808
	};
	
	if (options.trigger) {
		// the picker is shown on the trigger click
		dpopts.triggerEvent = 'showpicker';
		
		if (typeof options.trigger == 'string') {
			var $trigger = jQuery('#'+options.trigger);
		} else if (options.trigger instanceof jQuery) {
			var $trigger = options.trigger;
		} else {
			console.log('The specified trigger is not supported');
			return;
		}

		$trigger.click(function() {
			$field.trigger('showpicker');
		});
	} else {
		// the picker is shown when focusing the input, the default
	}

	$field.bootstrapMaterialDatePicker(dpopts);
}
// crmv@100585e crmv@128159e

// crmv@152057
function setupSelectPicker(fieldid, options) {
	
	// default options
	options = jQuery.extend({}, {
		size: 6,
		width: 'fit',
		liveSearch: false,
		searchMinOptions: 6,
		right: false,
	}, options || {});
	
	if (!jQuery.fn.selectpicker) {
		console.log('Bootstrap SelectPicker not loaded. Unable to initialize selectpicker');
		return;
	}
	
	if (typeof fieldid == 'string') {
		// fieldid may contain spaces, so I can't use jquery "#id" selector
		var field = document.getElementById(fieldid);
		if (!field) return;
		var $field = jQuery(field);
	} else if (fieldid instanceof jQuery) {
		var $field = fieldid;
	} else {
		// unknwown type for fieldid
		console.log('The specified field is not supported');
		return;
	}
	
	var liveSearch = false;
	
	if (options.liveSearch) {
		var selectOptions = $field.find('option').length;
		if (selectOptions >= options.searchMinOptions) {
			liveSearch = true;
		}
	}
	
	var dpopts = {
		size: options.size,
		width: options.width,
		liveSearch: liveSearch,
		dropdownAlignRight: options.right,
		styleBase: 'crmbutton',
		style: '',
	};
	
	$field.removeClass('detailedViewTextBox');
	$field.selectpicker(dpopts);
}
// crmv@152057e

// crmv@140887
function resourcever(file, options) {
	// default options
	options = jQuery.extend({}, {
	}, options || {});
	
	if (window['js_resource_version'] && window['js_resource_version'][file]) {
		return js_resource_version[file];
	}
	
	return file;
}
// crmv@140887e

//crmv@98484
var AlertNotifications = {
	alert: function(id,userid,callback,callback_params,callback_this) {
		var me = this;
		if (typeof(callback_this) == 'undefined') var callbackThis = this; else var callbackThis = callback_this;
		me.isSeen(id,userid,function(isseen){
			if (isseen == 'no') {
				me.getLabel(id,function(label){
					alert(label,function(){
						me.setSeen(id,userid,function(){
							if (typeof callback == 'function') callback.apply(callbackThis,callback_params);
						});
					});
				});
			} else {
				if (typeof callback == 'function') callback.apply(callbackThis,callback_params);
			}
		});
	},
	getLabel: function(id,callback) {
		this.call({'mode':'getlabel','id':id},callback);
	},
	isSeen: function(id,userid,callback) {
		this.call({'mode':'isseen','id':id,'userid':userid},callback);
	},
	setSeen: function(id,userid,callback) {
		this.call({'mode':'setseen','id':id,'userid':userid},callback);
	},
	call: function(data,callback) {
		jQuery.ajax({
			url: 'index.php?module=Utilities&action=UtilitiesAjax&file=AlertNotificationsAjax',
			type: 'post',
			data: data,
			success: function(response) {
				if (typeof callback == 'function') return callback(response);
			}
		});
	}
};
//crmv@98484e

//crmv@131239
//function to set date in user format
function getDisplayDate(value) {
 if(typeof(userDateFormat) == 'undefined') var userDateFormat = 'dd-mm-yyyy';
 var date_value = value.split(' ');
 var date_arr = date_value[0].split('-');
 var y = date_arr[0];
 var m = date_arr[1];
 var d = date_arr[2];
 
 // check if value is in db format
 if (y.length != 4) return value;
 
 var display_date = date_value[0];
	if (userDateFormat == 'dd-mm-yyyy') {
 	display_date = d+'-'+m+'-'+y;
 } else if (userDateFormat == 'mm-dd-yyyy') {
 	display_date = m+'-'+d+'-'+y;
 } else if (userDateFormat == 'yyyy-mm-dd') {
 	display_date = y+'-'+m+'-'+d;
 }
	if(typeof(date_value[1]) != 'undefined') display_date = display_date+' '+date_value[1];
 return display_date;
}
//function to set date compatible to database (yyyy-mm-dd)
function getValidDBInsertDateValue(value) {
	var date_arr = value.split('-');
 
 // check if is already in db format
 if (date_arr[0].length == 4) return value;
 
	if (userDateFormat == 'dd-mm-yyyy') {
	    var d = date_arr[0];
	    var m = date_arr[1];
	    var y = date_arr[2];
 } else if (userDateFormat == 'mm-dd-yyyy') {
 	var m = date_arr[0];
	    var d = date_arr[1];
	    var y = date_arr[2];
 } else if (userDateFormat == 'yyyy-mm-dd') {
 	var y = date_arr[0];
	    var m = date_arr[1];
	    var d = date_arr[2];
 }
	if(y == '' && m == '' && d == '') {
		var insert_date = '';
	} else {
		var insert_date = y+'-'+m+'-'+d;
	}
 return insert_date;
}
//crmv@131239e

//crmv@128694
function set_return_todo(product_id, product_name) {
	//crmv@29190
	var formName = getReturnFormName();
	if (formName != 'QcEditView') {
		formName = 'createTodo';
	}
	var form = getReturnForm(formName);
	//crmv@29190e
	form.parent_name.value = product_name;
	form.parent_id.value = product_id;
	disableReferenceField(form.parent_name,form.parent_id,form.parent_id_mass_edit_check);	//crmv@29190
}
//crmv@128694e

//crmv@111926
function countFormVars(formid,inputid) {
	// get the form
	var form = document.getElementById(formid);
	if (form) {
		// count the number of vars and populate the counter field
		var varcount = jQuery(form).find(':input').length;
		jQuery('#'+inputid).val(varcount);
		return true;
	}
	return false;
}
//crmv@111926e

//crmv@157490
function portalDuplicateValidate(form) {
	var record = jQuery(form).find('[name="record"]').val(),
		portal = jQuery(form).find('[name="portal"]:checked').val(),
		email = jQuery(form).find('[name="email"]').val();
	if (!record) record = '';
	(typeof(portal) == 'undefined') ? portal = 'off' : portal = 'on';

	if (portal == 'off') return true;
	
	var data = getFile('index.php?module=Contacts&action=ContactsAjax&file=DetailViewAjax&ajxaction=CHECKPORTALDUPLICATES&record='+record+'&email='+email);
	if (data == 'NOT_DUPLICATED') {
		return true;
	} else if (data == 'DUPLICATED') {
		alert(alert_arr.LBL_FIND_PORTAL_DUPLICATES);
		return false;
	} else {
		alert(alert_arr.LBL_ERROR_PORTAL_DUPLICATES);
		return false;
	}
}
//crmv@157490e

//crmv@160843 crmv@191206
function changePicklistType(obj,fieldname) {
	var type = jQuery(obj).val();
	if (type == 'v') {
		jQuery('#other_'+fieldname).val('');
		if (jQuery('#'+fieldname).length > 0) jQuery('#'+fieldname).parent('div').show();
		else jQuery('[name="'+fieldname+'"]').parent('div').show();
		jQuery('#div_other_'+fieldname).hide();
		jQuery('.editoptions[fieldname="other_'+fieldname+'"]').hide();
		jQuery('#advanced_field_assignment_button_'+fieldname).hide();
		
	} else if (type == 'o') {
		jQuery('#other_'+fieldname).val('');
		if (jQuery('#'+fieldname).length > 0) jQuery('#'+fieldname).parent('div').hide();
		else jQuery('[name="'+fieldname+'"]').parent('div').hide();
		jQuery('#div_other_'+fieldname).show();
		jQuery('.editoptions[fieldname="other_'+fieldname+'"]').show();
		jQuery('#advanced_field_assignment_button_'+fieldname).hide();
		
	} else if (type == 'A') {
		jQuery('#other_'+fieldname).val('advanced_field_assignment');
		if (jQuery('#'+fieldname).length > 0) jQuery('#'+fieldname).parent('div').hide();
		else jQuery('[name="'+fieldname+'"]').parent('div').hide();
		jQuery('#div_other_'+fieldname).hide();
		jQuery('.editoptions[fieldname="other_'+fieldname+'"]').hide();
		jQuery('#advanced_field_assignment_button_'+fieldname).show();
		ActionTaskScript.showSdkParamsInput(jQuery('#other_'+fieldname),fieldname);	//crmv@113527
	}
}
//crmv@160843e crmv@191206e

//crmv@171832
function saveEditViewChangeLogEtag(module,record) {
	if (jQuery('#editview_etag').length > 0 && jQuery('#editview_etag').val() != '') return; //crmv@175737 use the current etag
	
 	var url = 'index.php?module=ChangeLog&action=ChangeLogAjax&file=SaveEditViewEtag&module_req='+module+'&record_req='+record;
 	var form = jQuery('form[name=EditView]');
 	var fields = new Object;
	jQuery.each(form.serializeArray(), function(i, field) {
		fields[field.name] = {'value':field.value,'type':jQuery('form[name=EditView] [name="'+field.name+'"]').prop('tagName')}; //crmv@175849
	});
	jQuery.each(form.find(':checkbox'), function(i, field) {
		fields[field.name] = {'value':jQuery(field).prop('checked') ? "on" : "off",'type':jQuery('form[name=EditView] [name="'+field.name+'"]').prop('tagName')}; //crmv@175849
	});
	//crmv@178347
	jQuery.each(form.find('select[multiple]'), function(i, field) {
		var name = field.name.replace("[]","");
		var value = jQuery(field).val();
		if (value == null) value = [];
		fields[name] = {'value':value,'type':jQuery('form[name=EditView] [name="'+field.name+'"]').prop('tagName')};
		delete fields[field.name];
	});
	//crmv@178347e
	var postData = {
		fields: JSON.stringify(fields)
	};					
	jQuery.ajax({
		type: 'POST',
		url: url,
		dataType: 'html',
		data: postData,
		async: true,
		success: function(result){
			try {
				data = JSON.parse(result);
				if (data['success']){
					jQuery('#editview_etag').val(data['etag']); //crmv@175737
				}						
			} catch (e) {
				//do nothing
			}
		}
	});		
}
//crmv@171832e

// crmv@202301
/**
 * Change the fields in a time interval selection
 */
function changeDateRangePicklist(type, fldstart, fldend, triggerstart, triggerend, datesList) {
	
	if (!triggerstart) triggerstart = "jscal_trigger_date_start";
	if (!triggerend) triggerend = "jscal_trigger_date_end";
											
	if (!datesList) datesList = window.time_intervals;
	if (!datesList) return
	
	var field1 = jQuery('input[name='+(fldstart || "startdate")+']').get(0);
	var field2 = jQuery('input[name='+(fldend || "enddate")+']').get(0);
	
	if (!field1 || !field2) return;
	
	// hide/show cal buttons
	if (type != "custom") {
		field1.readOnly=true;
		field2.readOnly=true;
		jQuery("#"+triggerstart).css("visibility", "hidden");
		jQuery("#"+triggerend).css("visibility", "hidden");
	} else {
		field1.readOnly=false;
		field2.readOnly=false;
		jQuery("#"+triggerstart).css("visibility", "visible");
		jQuery("#"+triggerend).css("visibility", "visible");
	}
	
	var interval = datesList[type];
	if (interval) {
		field1.value = interval.from_display;
		field2.value = interval.to_display;
	} else {
		field1.value = '';
		field2.value = '';
	}
	
}
// crmv@202301e