/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@43864 - functions used by the new Link/Create Popup */
/* crmv@82831 crmv@121616 */

// Object which contains methods used by the new Popup
var LPOP = {

	module: '',

	/*
	 * take some global vars from the parent window
	 */
	setGlobalVars: function() {
		var vars = ['userDateFormat', 'SDKValidate', 'alert_arr', 'decimal_separator','thousands_separator','decimals_num']; // crmv@196061
		for (var i=0; i<vars.length; ++i) {
			if (typeof window[vars[i]] == 'undefined' && typeof parent.window[vars[i]] != 'undefined') {
				window[vars[i]] = parent.window[vars[i]];
			}
		}
	},

	/*
	 * module: the calling module
	 * id: the calling crmid
	 * type: 'Events' or 'Task'
	 */
	//crmv@62447 crmv@68357
	openEventCreate: function(module, id, type, params) {
		if (type === undefined || type === null) type = 'Events';

		if (type == 'Events') {
			var params = jQuery.param(jQuery.extend(params || {}, {
				'module': 'Calendar',
				'action' : 'index',
				'skip_footer' : 'true',
				'hide_menus' : 'true',
				'related_add' : 'true',
				'related_add' : 'true',
				'related_id' : id,
				'activity_mode' : type,
				'from_module': module,
				'from_crmid' : id,
				'fast_save' : 'true',
			}));

			openPopup('index.php?'+params, "Popup", "width=750,height=602,menubar=no,toolbar=no,location=no,status=no,resizable=no,scrollbars=yes");
		}
		else{
			var params = jQuery.param(jQuery.extend(params || {}, {
				'module': 'Popup',
				'action' : 'PopupAjax',
				'file' : 'CreateEvent',
				'activity_mode' : type,
				'from_module': module,
				'from_crmid' : id,
			}));

			openPopup('index.php?'+params, "Popup", "width=750,height=602,menubar=no,toolbar=no,location=no,status=no,resizable=no,scrollbars=yes");
		}
	},

	openEventCreateSave: function(module, id, type,mode, params) {
		var me = this;
		if (type === undefined || type === null) type = 'Events';
		var params = jQuery.param(jQuery.extend(params || {}, {
			'module': 'Popup',
			'action' : 'PopupAjax',
			'file' : 'CreateEvent',
			'activity_mode' : type,
			'from_module': module,
			'from_crmid' : id,
		}));
		var datestart = new Date(jQuery('.fancybox-iframe').contents().find('#wdCalendar').contents().find('#bbit-cal-start').val());
		var dateend = new Date(jQuery('.fancybox-iframe').contents().find('#wdCalendar').contents().find('#bbit-cal-end').val());
		var h_start = datestart.getHours();
		var m_start = datestart.getMinutes();
		var h_end = dateend.getHours();
		var m_end = dateend.getMinutes();
		datestart = jQuery('.fancybox-iframe').contents().find('#wdCalendar').get(0).contentWindow.js2Php(datestart,jQuery('.fancybox-iframe').contents().find('#wdCalendar').get(0).contentWindow.crmv_date_format);
		dateend = jQuery('.fancybox-iframe').contents().find('#wdCalendar').get(0).contentWindow.js2Php(dateend,jQuery('.fancybox-iframe').contents().find('#wdCalendar').get(0).contentWindow.crmv_date_format);
		allday = jQuery('.fancybox-iframe').contents().find('#wdCalendar').contents().find('#bbit-cal-allday').val();
		var add_options = "&is_all_day_event="+allday+"&specify_date=true&datestart="+datestart+"&dateend="+dateend+"&h_start="+h_start+"&h_end="+h_end+"&m_start="+m_start+"&m_end="+m_end;
		if (mode == 'fast'){
			add_options+="&fast_save=true";
		}
		var invited_users = [];
		jQuery('.fancybox-iframe').contents().find('#wdCalendar').contents().find('div#filterUserCalendar span#assign_user input:checked').each(function(){
			if (!isNaN(jQuery(this).val())){
				invited_users.push(jQuery(this).val());
			}
		});
		add_options+="&invited_users="+escapeAll(JSON.stringify(invited_users));
		// crmv@138379
		closePopup(window);
		setTimeout(function() {
			openPopup('index.php?'+params+add_options, "Popup", "width=750,height=602,menubar=no,toolbar=no,location=no,status=no,resizable=no,scrollbars=yes");
		}, 500);
		// crmv@138379e
	},
	//crmv@62447e crmv@68357e

	showActivity: function() {
		VteJS_DialogBox.progress();
	},

	hideActivity: function() {
		VteJS_DialogBox.hideprogress();
	},

	saveEvent: function(callback) {
		var me = LPOP,
			parent_module = jQuery('#from_module').val(),
			parentid = jQuery('#from_crmid').val(),
			activityType = document.EditView.activity_mode.value,
			extraInputs = jQuery('#extraInputs').serialize();

		document.EditView.action.value='Save';

		me.setGlobalVars();

		if (activityType == 'Task') {
			// crmv@105416
			var validForm = maintask_check_form();
			validForm = validForm && formValidate();
			// crmv@105416e
		} else {
			var validForm = check_form();
		}

		if (validForm) {
			me.showActivity();
			jQuery.ajax({
				url: jQuery(document.EditView).attr('action'),
				data: jQuery(document.EditView).serialize() + (extraInputs.length > 0 ? '&'+extraInputs : ''),
				type: 'POST',
				complete: function() {
					me.hideActivity();
				},
				success: function(data) {
					if (!data) return alert(alert_arr.ERROR+': '+data);
					try {
						var ret = jQuery.parseJSON(data);
					} catch (e) {
						return alert(alert_arr.ERROR+': '+data);
					}

					var activityid = ret.activityid;
					if (!activityid) return alert(alert_arr.ERROR+': '+data);

					// call callback (don't close popup if return false)
					if (typeof callback == 'function') {
						var r = callback(ret);
						if (r === false) return
					}

					parent.reloadTurboLift(parent_module, parentid, 'Activities');	//crmv@52561
					me.close_popup(true, parent_module, parentid);
				},
				error: function() {
					alert(alert_arr.ERROR);
				}
			});
		}
	},

	/*
	 * Params:
	 * recordid 	: the starting record to link the selected entity
	 * mode			: a string indicating the operating mode
	 * extraParams	: object (key/values) passed in the request
	 * opener		: the window that opens the popup (current window if omitted)
	 * TODO: callback
	 */
	openPopup: function(module,id, mode, extraParams, opener) {

		if (!extraParams) extraParams = {};

		if (mode == 'addsender' || mode == 'addrecipient') {
			extraParams['show_only'] = 'create';
			extraParams['onlypeople'] = 'true';

		} else if (mode == 'onlycreate') {
			extraParams['show_only'] = 'create';

		} else if (mode == 'compose') {
			// get ids of recipients
			var idlist = [];
			jQuery('#autosuggest_to .addrBubble').each(function(index, item) {
				var parts = item.id.split('_');
				idlist.push(parts[1]);
			});
			extraParams['idlist'] = idlist.join(';');
		} else if (mode == 'linkrecord' && !id) {
			extraParams['show_only'] = 'link';
		}

		var params = {
			'module': 'Popup',
			'action' : 'PopupAjax',
			'file' : 'index',
			'from_module': module,
			'from_crmid' : id,
			'mode': mode,
		};

		// GLOBAL VAR: ugly trick to have a reference to the opener window
		popup_opener = opener || window;

		if (extraParams['file'] != undefined) {
			params['file'] = extraParams['file'];
		}

		if (extraParams && !jQuery.isEmptyObject(extraParams)) {
			jQuery.extend(params, extraParams);
		}
		openPopup('index.php?'+jQuery.param(params),"Popup","width=750,height=602,menubar=no,toolbar=no,location=no,status=no,resizable=no,scrollbars=yes");
	},

	// crmv@56603
	clickLinkModule: function(module, clickaction, relation_id) {
		var me = LPOP,
			file,
			action = (clickaction ? clickaction : jQuery('#default_link_action').val()),
			listCont = jQuery('#linkMsgListCont'),
			editCont = jQuery('#linkMsgEditCont');

		if (typeof(gVTModule) != 'undefined' && gVTModule != module) jQuery('#sdk_view_all').val('');	//crmv@48964
		gVTModule = module;

		// sset the class
		jQuery('#linkMsgModTab .linkMsgModTdSelected').removeClass('linkMsgModTdSelected');
		jQuery('#linkMsgModTab #linkMsgMod_'+module).addClass('linkMsgModTdSelected');

		if (action == 'create') {
			return me.showCreatePanel(0, module);
			//crmv@43942
		} else if (action == 'list') {
			file = 'LinkList';
		} else {
			file = action;
		}
		//crmv@43942e

		var params = {
			'module': 'Popup',
			'action' : 'PopupAjax',
			'file' : file,	//crmv@43942
			'mod' : module,
			'relation_id' : parseInt(relation_id),
		};

		me.showActivity();
		jQuery.ajax({
			url: 'index.php',
			type: 'POST',
			data: jQuery.param(params) + '&' + jQuery('#extraInputs').serialize(),
			complete: function() {
				me.hideActivity();
			},
			success: function(data) {
				listCont.hide();
				jQuery('#linkMsgDescrCont').hide();
				destroySlimscroll('linkMsgDetailCont');
				destroySlimscroll('linkMsgEditCont');
				jQuery('#linkMsgDetailCont').hide();
				editCont.hide();
				listCont.html(data).fadeIn('fast');
			}
		});
	},

	showCreatePanel: function(listid, mod, relation_id) {
		var me = LPOP,
			module = (mod ? mod : SLV.get_module(listid)),
			show_create_note = (mod ? 'yes' : 'no'),	//crmv@46678
			descrCont = jQuery('#linkMsgDescrCont'),
			listCont = jQuery('#linkMsgListCont'),
			editCont = jQuery('#linkMsgEditCont'),
			attachCont = jQuery('#linkMsgAttachCont');

		var params = {
			'module': 'Popup',
			'action' : 'PopupAjax',
			'file' : 'CreateForm',
			'mod' : module,
			'show_create_note' : show_create_note,	//crmv@46678
			'relation_id' : parseInt(relation_id),
		};

		me.showActivity();
		descrCont.hide();
		listCont.hide();
		jQuery.ajax({
			url: 'index.php',
			type: 'POST',
			data: jQuery.param(params) + '&' + jQuery('#extraInputs').serialize(),
			success: function(data) {
				editCont.height(jQuery('#linkMsgRightPaneTop').height());
				editCont.html(data);

				// set magic scrolling
				jQuery('#linkMsgEditCont').slimScroll({
					wheelStep: 10,
					height: editCont.height()+'px',
					width: '100%'
				});
				editCont.fadeIn('fast');
				attachCont.fadeIn('fast');
			},
			complete: function() {
				me.hideActivity();
			}
		});
	},
	// crmv@56603e

	select: function(listid, module, crmid, entityname) {
		var me = LPOP,
			parent_mod = jQuery('#parent_module').val(),
			callback_link = jQuery('#callback_link').val(),
			descrCont = jQuery('#linkMsgDescrCont'),
			listCont = jQuery('#linkMsgListCont'),
			detailCont = jQuery('#linkMsgDetailCont'),
			editCont = jQuery('#linkMsgEditCont'),
			attachCont = jQuery('#linkMsgAttachCont');

		var params = {
			'module': 'Popup',
			'action' : 'PopupAjax',
			'file' : 'DetailForm',
			'mod' : module,
			'record' : crmid,
		};

		me.showActivity();
		descrCont.hide();
		listCont.hide();
		jQuery.ajax({
			url: 'index.php',
			type: 'POST',
			data: jQuery.param(params) + '&' + jQuery('#extraInputs').serialize(),
			complete: function() {
				me.hideActivity();
			},
			success: function(data) {
				detailCont.height(jQuery('#linkMsgRightPaneTop').height());
				detailCont.html(data);

				// set magic scrolling
				jQuery('#linkMsgDetailCont').slimScroll({
					wheelStep: 10,
					height: detailCont.height()+'px',
					width: '100%'
				});
				detailCont.fadeIn('fast');
				attachCont.fadeIn('fast');
			}
		});
	},

	// crmid can be an array of ids
	link: function(module, crmid, entityname) {
		var me = LPOP,
			parent_module = jQuery('#from_module').val(),
			parentid = jQuery('#from_crmid').val(),
			mode = jQuery('#popup_mode').val();

		// crmv@202577
		var selectedTargets = null;

		if (parent_module === 'Campaigns' && module === 'Targets') {
			selectedTargets = crmid;
		}

		if (selectedTargets !== null) {
			jQuery.ajax({
				url: 'index.php?module=Targets&action=TargetsAjax&ajax=true&file=GetData',
				data: {
					'parent_id': parentid,
					'parent_module': parent_module,
					'ids': selectedTargets,
					'get_tm_total_count': '1',
				},
				type: 'GET',
				success: function(res) {
					if (res.length > 0) {
						var parts = res.split('###');
						var limit = Number(parts[1]);
						var count = Number(parts[0]);

						if (limit < count) {
							vtealert(alert_arr.LBL_MASS_CREATE_ENQUEUE_TELEMARKETING.replace('{max_records}', limit), function() {
								linkValidationCallback();
							});
						} else {
							linkValidationCallback();
						}
					} else {
						linkValidationCallback();
					}
				}
			});
		} else {
			linkValidationCallback();
		}
		// crmv@202577e

		function linkValidationCallback() {
			me.showActivity();
			if (mode == 'compose') {
				// TOODO!!!
				jQuery.ajax({
					url: 'index.php?module=Utilities&action=UtilitiesAjax&file=Card', // crmv@137471
					data: '&idlist='+crmid,
					type: 'POST',
					success: function(data, status, xhr) {
						//if (!data.match(/error/i)) {
						//alert(alert_arr.LBL_MESSAGE_LINKED);
						window.parent.jQuery('#ComposeLinks').append(data);
						if (crmid.length > 0) crmid = crmid.join('|');	//crmv@86304
						window.parent.jQuery('#relation').val(window.parent.jQuery('#relation').val()+'|'+crmid);
						me.close_popup(false);
						//}
					},
					complete: function() {
						me.hideActivity();
					}
				});
			} else {
				// crmv@43050
				linkModules(parent_module, parentid, module, crmid,
					{
						'mode' : mode
					},
					function(data) {
						if (!data.substr(0,100).match(/error/i)) {
							//alert(alert_arr.LBL_MESSAGE_LINKED);
							//crmv@55506
							if (parent_module == 'ModComments') {
								commentsLinkModule(module, crmid);
								//crmv@55506e
							} else if (parent_module == 'PBXManager') {
								parent.location.reload();
							} else if (parent_module == 'Messages') {
								var attList = getAttachmentsToLink();
								window.parent.jQuery('#flag_'+parentid+'_relations').show();
								if (mode == 'linkdocument') {
									window.parent.saveDocument(parentid,jQuery('#contentid').val(),crmid,module);
								} else if (attList.length > 0) {
									for (var i=0; i<attList.length; ++i) {
										window.parent.saveDocument(parentid,attList[i],crmid,module);
									}
								}
								//crmv@44775
								if (typeof(parent.messageMode) != 'undefined' && parent.messageMode == 'Detach') {
									parent.location.reload();
								} else {
									//crmv@44775e
									window.parent.selectRecord(parentid,false,true);
									parent.reloadTurboLift(parent_module, parentid, module);
								}
							} else {
								// other modules with turbolift
								parent.reloadTurboLift(parent_module, parentid, module);
							}
							me.hideActivity();
							me.close_popup(true, parent_module, parentid);
						} else {
							//alert(alert_arr.LBL_RECORD_SAVE_ERROR);
						}
					}
				);
				// crmv@43050e
			}
		}
	},

	linkSelected: function(listid) {
		var ids = SLV.add_selected(listid),
			module = SLV.get_module(listid);

		if (ids.length == 0) {
			alert(alert_arr.SELECT);
			return;
		}

		// crmv@200009
		var parent_module = jQuery('#from_module').val();
		if (parent_module == 'Targets') {
			jQuery.ajax({
				url: 'index.php?module=Targets&action=TargetsAjax&ajax=true&file=GetData&only_limit=1',
				cache: false,
				success: function(limit) {
					if (Number(limit) < ids.length) {
						vtealert(alert_arr.LBL_MASS_EDIT_ENQUEUE.replace('{max_records}', limit), function() {
							return LPOP.link(module, ids, '');
						});
					} else {
						return LPOP.link(module, ids, '');
					}
				}
			});
		} else {
			return LPOP.link(module, ids, '');
		}

		// crmv@200009e
	},

	//crmv@44609
	convert: function(module) {
		var me = LPOP;

		me.create(module, function(module, recordid) {
			var params = {
				'module': 'Popup',
				'action' : 'PopupAjax',
				'file' : 'Convert',
				'to_module' : module,
				'to_crmid' : recordid,
			};
			me.showActivity();
			jQuery.ajax({
				url: 'index.php',
				type: 'POST',
				async: false,
				data: jQuery.param(params) + '&' + jQuery('#extraInputs').serialize(),
				complete: function() {
					me.hideActivity();
				},
				success: function(data) {
					if (data == 'SUCCESS') {
						top.location.href = 'index.php?module='+module+'&action=DetailView&record='+recordid;
					} else {
						alert(alert_arr.ERROR+': '+data);
					}
				}
			});
			return false;
		});
	},
	//crmv@44609e

	create: function(module, callback, confirm) {
		var me = LPOP;
		if (typeof(confirm) == 'undefined') confirm = false;

		if (confirm) {
			vteconfirm(alert_arr.ARE_YOU_SURE, function(yes) {
				if (yes) LPOP._create(module, callback);
			});
		} else {
			LPOP._create(module, callback);
		}
	},
	_create: function(module, callback) {
		var me = LPOP,
			descrCont = jQuery('#linkMsgDescrCont'),
			listCont = jQuery('#linkMsgListCont'),
			editCont = jQuery('#linkMsgEditCont');

		me.setGlobalVars();

		document.EditView.action.value = 'Save';
		document.EditView.return_module.value = module;
		document.EditView.return_action.value = 'DetailView';

		//crmv@167928
		if(window.CKEDITOR){
			for ( instance in CKEDITOR.instances )
				CKEDITOR.instances[instance].updateElement();
		}
		//crmv@167928e

		jQuery('<input>').attr({
			type: 'hidden',
			name: 'am_I_in_popup',
			value: 'yes'
		}).appendTo('form[name="EditView"]');

		if (isInventoryModule(module)) {
			settotalnoofrows();calcTotal(); //crmv@72922
			var valid = validateInventory(module);
		} else {
			var valid = formValidate(document.EditView);
		}

		if (module == 'Accounts'){
			if (isdefined('external_code')){
				var ext = getObj('external_code').value;
				var exttype = getObj('external_code').type;
				if ( (trim(ext) != '') && (exttype != "hidden") ) {
					if (!AjaxDuplicateValidateEXT_CODE(module,'external_code','','editview'))
						valid = false;
				}
			}
		}

		if (!valid) return;

		me.showActivity();
		me.module = module;
		jQuery('form[name="EditView"]').ajaxSubmit( { success: me.successCreate, error: me.successCreate } );	//crmv@45933
	},

	successCreate: function(data, status, xhr, $form) {
		var me = LPOP;

		if (!data || status != 'success') {
			me.hideActivity();
			return alert(alert_arr.ERROR+': '+data);
		}

		// AARRRGHH! the only way to get the recordid is to parse all the response
		var searchStr = '<input type="hidden" name="record" value="',
			ipos = data.indexOf(searchStr);
		if (ipos == -1) {
			searchStr = '<input name="record" value="';
			ipos = data.indexOf(searchStr);
		}

		if (ipos > -1) {
			var recordInput = data.substr(ipos+searchStr.length, 10),
				matches = recordInput.match(/^[0-9]+/),
				recordid = matches ? matches[0] : 0;

			// call callback (don't close popup if return false)
			if (typeof callback == 'function') {
				var r = callback(me.module, recordid);
				if (r === false) return
			}

			if (recordid > 0) {
				// linka e fa documenti
				me.link(me.module, recordid);
			}

			//crmv@53056
			if (me.module == 'Timecards' && jQuery($form).find('input[name="newtc"]').prop('checked') == true) {
				var parent_module = jQuery('#from_module').val(),
					parentid = jQuery('#from_crmid').val();
				parent.LPOP.openPopup(parent_module, parentid, 'onlycreate', {'show_module':me.module});
			}
			//crmv@53056e
		}

		me.hideActivity();
	},

	create_cancel: function() {
		var me = LPOP,
			action = jQuery('#default_link_action').val(),
			descrCont = jQuery('#linkMsgDescrCont'),
			listCont = jQuery('#linkMsgListCont'),
			detailCont = jQuery('#linkMsgDetailCont'),
			editCont = jQuery('#linkMsgEditCont'),
			attachCont = jQuery('#linkMsgAttachCont');

		editCont.html('').hide();
		attachCont.hide();
		if (action == 'create') {
			//descrCont.show();
			me.close_popup(false);
		} else {
			destroySlimscroll('linkMsgDetailCont');
			destroySlimscroll('linkMsgEditCont');
			detailCont.html('').hide();
			listCont.height(jQuery('#linkMsgRightPaneTop').height()).show();
		}
	},

	close_popup: function(run_callback, module, recordid) {
		var mode = jQuery('#popup_mode').val(),
			callback_close = jQuery('#callback_close').val();

		if (run_callback && callback_close != '') {
			eval(callback_close+'(\''+mode+'\',\''+module+'\',\''+recordid+'\')');
		} else {
			closePopup();
		}
	}
}