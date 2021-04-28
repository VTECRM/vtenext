/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@192033 */

window.VTE = window.VTE || {};

VTE.ModCommentsCommon = VTE.ModCommentsCommon || {

	busy: false,
	default_number_of_news: 40,
	current_page: 1, // crmv@80503
	
	// crmv@199112
	mouseOverPublishBtn: function(self, previewMode) {
		jQuery('#ModComments_sub').width(jQuery(self).outerWidth());
		jQuery('#ModComments_sub').css('left', jQuery(self)[previewMode ? 'position' : 'offset']().left); 
		jQuery('#ModComments_sub').css('top', jQuery(self)[previewMode ? 'position' : 'offset']().top + jQuery(self).outerHeight()); 
		jQuery('#ModComments_sub').show();
		jQuery('#ModComments_sub')[0].style.zIndex = findZMax()+1;
		if (previewMode) {
			jQuery('#ModCommentsBottomWhiteSpace').show();
		}
	},
	
	mouseOutPublishBtn: function(self) {
		jQuery('#ModComments_sub').hide();
	},
	// crmv@199112e

	showBusy: function(indicator, mask, scope) {
		var me = this,
			mask = mask || false,
			scope = scope || window;
		
		me.busy = true;
		if (mask) VteJS_DialogBox.block();
		// crmv@197996
		if (scope.jQuery) {
			if (typeof indicator === 'string') {
				scope.jQuery('#' + indicator).show();
			} else {
				scope.jQuery(indicator).show();
			}
		}
		// crmv@197996e
	},

	hideBusy: function(indicator, scope) {
		var me = this,
			mask = mask || true,
			scope = scope || window;
		
		me.busy = false;
		VteJS_DialogBox.unblock();
		// crmv@197996
		if (scope.jQuery) {
			if (typeof indicator === 'string') {
				scope.jQuery('#' + indicator).hide();
			} else {
				scope.jQuery(indicator).hide();
			}
		}
		// crmv@197996e
	},

	// crmv@43050
	checkComment: function(mode, domkeyid, visibility) {
		var textBoxFieldVal = jQuery('#txtbox_' + domkeyid).val();
		if (mode == 'new' || mode == 'addUsers') {
			if (mode == 'new' && (textBoxFieldVal == '' || textBoxFieldVal == default_text)) {
				return false;
			}
			// crmv@43448 - removed debug
			if (jQuery('input[name="ModCommentsMethod"]').length > 0) { // new mode
				visibility = jQuery('input[name="ModCommentsMethod"]:checked').val() || visibility; // crmv@43448
			}

			if (visibility != 'All' && visibility != 'Users') {
				alert('Scegli a chi scrivere');
				return false;
			} else if (visibility == 'Users') {
				var tmp = getObj('ModCommentsUsers_idlist').value;
				tmp = tmp.replace(/\|/g, "");

				if (tmp == '') {
					alert(alert_arr.SELECT_ATLEAST_ONE_USER);
					return false;
				}
			}
		} else if (mode == 'reply') {
			if (textBoxFieldVal == '' || textBoxFieldVal == default_reply_text) {
				return false;
			}
		} else if (mode == 'composeEmail') {
			if (textBoxFieldVal != '' && textBoxFieldVal != default_text) {
				if (jQuery('input[name="ModCommentsMethod"]').length > 0) { // new mode
					visibility = jQuery('input[name="ModCommentsMethod"]:checked').val();
				}
				if (visibility != 'All' && visibility != 'Users') {
					alert('Scegli a chi scrivere');
					return false;
				} else if (visibility == 'Users') {
					var tmp = getObj('ModCommentsUsers_idlist').value;
					tmp = tmp.replace(/\|/g, "");
					if (tmp == '') {
						alert(alert_arr.SELECT_ATLEAST_ONE_USER);
						return false;
					}
				}
			}
		}
		return true;
	},
	// crmv@43050e
	
	addComment: function(domkeyid, parentid, visibility, indicator) {
		var me = this;
		
		if (me.busy) return false;
		
		//crmv@91082
		if (!SessionValidator.check()) {
			SessionValidator.showLogin();
			return false;
		}
		//crmv@91082e

		// crmv@43050 - added add users
		var commentid = jQuery('#ModComments_addCommentId').val();
		if (commentid) return VTE.ModCommentsCommon.addUsers(domkeyid, indicator, commentid);
		// crmv@43050e

		if (VTE.ModCommentsCommon.checkComment('new', domkeyid, visibility) == false) {
			return;
		}
		var textBoxField = document.getElementById('txtbox_' + domkeyid);
		var contentWrapDOM = document.getElementById('contentwrap_' + domkeyid);

		// crmv@43448 try to retrieve parent id from the popup
		if (!parentid) {
			parentid = jQuery('#ModCommentsParentId').val();
		}
		// crmv@43448e
		
		//crmv@179773
		function ajaxCall(parent_permissions) {
			if (typeof(parent_permissions) == 'undefined') parent_permissions = '';

			var url = 'module=ModComments&action=ModCommentsAjax&file=DetailViewAjax&ajax=true&ajxaction=WIDGETADDCOMMENT&parentid=' + encodeURIComponent(parentid);
			url += '&parent_permissions=' + parent_permissions + '&comment=' + encodeURIComponent(textBoxField.value) + '&visibility=' + visibility;
			if (visibility == 'Users') {
				url += '&users_comm=' + encodeURIComponent(getObj('ModCommentsUsers_idlist').value);
			}
	
			me.showBusy(indicator, true);
	
			jQuery.ajax({
				url: 'index.php?' + url,
				type: 'POST',
				dataType: 'html',
				success: function(data) {
					me.hideBusy(indicator);
	
					var responseTextTrimmed = trim(data);
					if (responseTextTrimmed.substring(0, 10) == ':#:SUCCESS') {
						contentWrapDOM.innerHTML = responseTextTrimmed.substring(10) + contentWrapDOM.innerHTML;
						textBoxField.className = 'detailedViewTextBox detailedViewModCommTextBox';
						textBoxField.value = default_text;
						jQuery('#saveButtonRow_' + domkeyid).hide();
						if (jQuery('input[name="ModCommentsMethod"]').length > 0) { // new mode
							jQuery('#saveOptionsRow_' + domkeyid).hide();
							jQuery('input[name="ModCommentsMethod"]').removeAttr("checked");
						}
						if (visibility == 'Users') {
							if (jQuery('input[name="ModCommentsMethod"]').length > 0) { // new mode
								jQuery('#ModCommentsUsers_' + domkeyid).hide();
							} else {
								jQuery('#ModCommentsUsers').hide();
							}
							removeAllUsers();
						}
						// crmv@43448
						var container = jQuery('#editareaModComm');
						container.find('.commentAddLink').show();
						container.find('#ModCommentsParentId').val('');
						container.find('#ModCommentsNewRelatedLabel').hide();
						container.find('#ModCommentsNewRelatedName').html('').hide();
						// crmv@43448e
					} else {
						alert(top.alert_arr.OPERATION_DENIED);
					}
					
					me.registerPanelBlocker(); // crmv@171115
				}
			});
		}
		
		if (parentid == '') {
			ajaxCall();
		} else {
			// check parentid permissions for target users
			me.checkAddComment(indicator, parentid, {'visibility': visibility, 'users_comm': getObj('ModCommentsUsers_idlist').value}, function(parent_permissions){
				ajaxCall(parent_permissions);
			});
		}
		//crmv@179773e
	},
	
	// crmv@43050
	addUsers: function(domkeyid, indicator, commentid) {
		var me = this;
		
		if (me.busy) return false;
		
		if (VTE.ModCommentsCommon.checkComment('addUsers', domkeyid, 'Users') == false) {
			return;
		}
		
		//crmv@179773
		var userids = jQuery('#ModCommentsUsers_idlist').val();
		if (!userids) return;
		
		function ajaxCall(parent_permissions) {
			if (typeof(parent_permissions) == 'undefined') parent_permissions = '';
			
			var contentWrapDOM = jQuery('#tblModCommentsDetailViewBlockCommentWidget_' + commentid),
				url = 'index.php?module=ModComments&action=ModCommentsAjax&file=DetailViewAjax&ajax=true&ajxaction=WIDGETADDUSERS';
	
			url += '&parent_permissions=' + parent_permissions + '&commentid=' + encodeURIComponent(commentid) + '&users_comm=' + encodeURIComponent(userids);
	
			me.showBusy(indicator, true);
	
			jQuery.ajax({
				'url': url,
				type: 'POST',
				dataType: 'html',
				success: function(data) {
					me.hideBusy(indicator);
	
					var responseTextTrimmed = trim(data);
					if (responseTextTrimmed.substring(0, 10) == ':#:SUCCESS') {
						contentWrapDOM.replaceWith(responseTextTrimmed.substring(10));
	
						if (jQuery('input[name="ModCommentsMethod"]').length > 0) { // new mode
							jQuery('input[name="ModCommentsMethod"]').removeAttr("checked");
							jQuery('#ModCommentsUsers_' + domkeyid).hide();
						} else {
							jQuery('#ModCommentsUsers').hide();
						}
						removeAllUsers();
						jQuery('#ModCommentsUsers2').hide(); // crmv@43448
	
					} else {
						alert(top.alert_arr.OPERATION_DENIED);
					}
				}
			});
		}
		
		var parentid = '';
		if (jQuery('#relatedTo_'+domkeyid+'_'+commentid).length > 0) parentid = jQuery('#relatedTo_'+domkeyid+'_'+commentid).val();
		if (parentid == '') {
			ajaxCall();
		} else {
			// check parentid permissions for target users
			me.checkAddComment(indicator, parentid, {'visibility': 'Users', 'users_comm': userids}, function(parent_permissions){
				ajaxCall(parent_permissions);
			});
		}
		//crmv@179773e
	},
	
	//crmv@179773
	checkAddComment: function(indicator, parentid, options, callback) {
		var me = this,
			url = 'module=ModComments&action=ModCommentsAjax&file=DetailViewAjax&ajax=true&ajxaction=WIDGETCHECKADDCOMMENT&parentid=' + encodeURIComponent(parentid);
		
		options = jQuery.extend({
			commentid: '',
			visibility: '',
			users_comm: '',
		}, options);
		
		if (options.commentid != '') url += '&commentid=' + options.commentid;
		if (options.visibility != '') url += '&visibility=' + options.visibility;
		if (options.visibility == 'Users') url += '&users_comm=' + encodeURIComponent(options.users_comm);

		me.showBusy(indicator, true);
		
		jQuery.ajax({
			url: 'index.php?' + url,
			type: 'POST',
			dataType: 'html',
			success: function(data) {
				me.hideBusy(indicator);
				
				var responseTextTrimmed = trim(data);
				if (responseTextTrimmed.substring(0, 10) == ':#:SUCCESS') {
					if (typeof callback == 'function') callback();
				} else if (responseTextTrimmed.substring(0, 20) == ':#:CONFIRMPERMISSION') {
					var response = responseTextTrimmed.split(':#:');
					top.vteconfirm(response[2], function(yes){
						if (yes) {
							if (typeof callback == 'function') callback(top.jQuery('#ModComments_parent_permissions').val());
						}
					}, {html: true});
				} else {
					top.vtealert(top.alert_arr.OPERATION_DENIED);
				}
			}
		});
	},
	setParentPermissions: function(domkeyid, indicator, commentid, parent_permissions, callback) {
		var me = this,
			url = 'module=ModComments&action=ModCommentsAjax&file=DetailViewAjax&ajax=true&ajxaction=WIDGETSETPARENTPERMISSIONS&commentid=' + commentid + '&parent_permissions=' + parent_permissions;
		
		me.showBusy(indicator, true);
		
		jQuery.ajax({
			url: 'index.php?' + url,
			type: 'POST',
			dataType: 'html',
			success: function(data) {
				me.hideBusy(indicator);
				
				var responseTextTrimmed = trim(data);
				if (responseTextTrimmed.substring(0, 10) == ':#:SUCCESS') {
					if (typeof callback == 'function') callback();
				} else {
					top.vtealert(top.alert_arr.OPERATION_DENIED);
				}
			}
		});
	},
	previewParentPermissions: function(target, parent_permissions) {
		jQuery('#'+target).html(top.alert_arr['LBL_CONFIRM_SHARE_PARENT_HELP_'+parent_permissions]);
	},
	//crmv@179773e
	
	// crmv@43448
	reloadComment: function(domkeyid, indicator, commentid, setunread) {
		var me = this;
		
		if (me.busy) return false;
		
		var contentWrapDOM = jQuery('#tblModCommentsDetailViewBlockCommentWidget_' + commentid),
			url = 'index.php?module=ModComments&action=ModCommentsAjax&file=DetailViewAjax&ajax=true&ajxaction=WIDGETGETCOMMENT';

		url += '&commentid=' + encodeURIComponent(commentid);
		if (setunread !== undefined && setunread !== null && setunread !== '') {
			url += '&setasunread=' + setunread;
		}

		//crmv@59626
		if (jQuery('[name="record"]').val() == undefined) {
			url += '&criteria=News';
		}
		if (jQuery('#contentShowFull' + domkeyid + '_' + commentid).length > 0 && jQuery('#contentShowFull' + domkeyid + '_' + commentid).css('display') != 'none') {
			url += '&show_preview=yes';
		}
		//crmv@59626e

		me.showBusy(indicator, true);

		jQuery.ajax({
			'url': url,
			type: 'POST',
			dataType: 'html',
			async: false,
			success: function(data) {
				me.hideBusy(indicator);

				var responseTextTrimmed = trim(data);
				if (responseTextTrimmed.substring(0, 10) == ':#:SUCCESS') {
					contentWrapDOM.replaceWith(responseTextTrimmed.substring(10));

					if (jQuery('input[name="ModCommentsMethod"]').length > 0) { // new mode
						jQuery('input[name="ModCommentsMethod"]').removeAttr("checked");
						jQuery('#ModCommentsUsers_' + domkeyid).hide();
					} else {
						jQuery('#ModCommentsUsers').hide();
					}

					if (top.NotificationsCommon) {
						top.NotificationsCommon.showChangesAndStorage('CheckChangesDiv', 'CheckChangesImg', 'ModComments', false); //crmv@OPER5904 crmv@187621
					}
				} else {
					alert(top.alert_arr.OPERATION_DENIED);
				}
			}
		});
	},
	// crmv@43050e
	
	setAsUnread: function(domkeyid, commentid, indicator) {
		return this.reloadComment(domkeyid, indicator, commentid, 1);
	},
	
	setAsRead: function(domkeyid, commentid, indicator) {
		return this.reloadComment(domkeyid, indicator, commentid, 0);
	},
	// crmv@43448e
	
	//crmv@59626 crmv@98825
	checkAndSetAsRead: function(obj, domkeyid, commentid, indicator) {
		if (jQuery(obj).hasClass('ModCommUnseen')) { // only if I click in these divs
			return this.setAsRead(domkeyid, commentid, indicator);
		}
	},
	//crmv@59626e crmv@98825e
	
	reloadContentWithFiltering: function(widget, parentid, criteria, targetdomid, indicator, searchkey) { //crmv@31301
		var me = this;
		
		if (me.busy) return false;
		
		me.showBusy(indicator);
		
		var url = 'module=ModComments&action=ModCommentsAjax&file=ModCommentsWidgetHandler&ajax=true';
		url += '&widget=' + encodeURIComponent(widget) + '&parentid=' + encodeURIComponent(parentid);
		url += '&criteria=' + encodeURIComponent(criteria);
		url += '&searchkey=' + encodeURIComponent(searchkey); //crmv@31301

		if (criteria.indexOf('News') >= 0) {
			/* crmv@59626
			if (indicator.indexOf('refresh_')>=0) {
				jQuery('#'+indicator).html(jQuery('#vtbusy_homeinfo').html());
			}
			jQuery('#'+targetdomid).load(function(){
				NotificationsCommon.removeChanges('ModComments','News',targetdomid);
				if (indicator.indexOf('refresh_')>=0) {
					jQuery('#'+indicator).html('');
				}
			}); */
			url += '&target_frame=' + targetdomid;
			url += '&indicator=' + indicator;
			jQuery('#' + targetdomid).attr('src', 'index.php?' + url);
			
			me.hideBusy(indicator);
			
			return;
		}

		jQuery.ajax({
			url: 'index.php?' + url,
			type: 'POST',
			dataType: 'html',
			success: function(data) {
				me.hideBusy(indicator);
				
				//crmv@16903
				if (jQuery('#'+targetdomid).length > 0) {
					jQuery('#'+targetdomid).html(data);
					if (jQuery('#'+targetdomid)[0].style.display != "block")
						showHideStatus('tblModCommentsDetailViewBlockCommentWidget', 'aidModCommentsDetailViewBlockCommentWidget', 'themes/softed/images/');
				}
				//crmv@16903e
				//NotificationsCommon.removeChanges('ModComments','DetailView');	//crmv@59626
			}
		});
	},
	
	//crmv@80503
	// this function must be called from inside the iframe
	appendContentWithFiltering: function(widget, parentid, criteria, targetdomid, indicator, searchkey) { //crmv@31301
		var me = this,
			page = parseInt(criteria.replace(/[^0-9]/g, ''));
		
		if (me.busy) return false;
		
		me.showBusy(indicator, false, top);

		var url = 'module=ModComments&action=ModCommentsAjax&file=ModCommentsWidgetHandler&ajax=true';
		url += '&widget=' + encodeURIComponent(widget) + '&parentid=' + encodeURIComponent(parentid);
		url += '&criteria=' + encodeURIComponent(criteria);
		url += '&searchkey=' + encodeURIComponent(searchkey); //crmv@31301

		var uikey = jQuery('#uikey').val();
		var cont = jQuery('#contentwrap_' + uikey);

		// get the last child
		if (cont.length > 0) {
			var lastchild = cont.find('input[id^=comment' + uikey + '_lastchild]:last');
			if (lastchild.length > 0) {
				url += "&lastchildid=" + parseInt(lastchild.val());
			}
			var lastseen = cont.find('input[id^=comment' + uikey + '_seen]:last');
			if (lastseen.length > 0) {
				url += "&lastseen=" + (lastseen.val() == 'true' ? '1' : '0');
			}
		}

		jQuery.ajax({
			url: 'index.php?' + url,
			type: 'POST',
			dataType: 'html',
			success: function(data) {
				me.hideBusy(indicator, top);

				if (cont.length > 0) {
					cont.append(data);
					me.current_page = page;
					// update the counter
					var total = parseInt(jQuery('#comments_counter_total_' + uikey).text());
					var newmax = page * me.default_number_of_news;
					jQuery('#comments_counter_to_' + uikey).text(Math.min(total, newmax));
					if (newmax >= total) {
						// hide the "load more" link
						jQuery('#comments_counter_link_' + uikey).hide();
					}
				}
			}
		});
	},
	//crmv@80503e
	
	addReply: function(domkeyid, parentid, parent_comment, indicator) {
		var me = this;
		
		if (me.busy) return false;
		
		if (VTE.ModCommentsCommon.checkComment('reply', domkeyid) == false) {
			return;
		}

		//crmv@91082
		if (!SessionValidator.check()) {
			SessionValidator.showLogin();
			return false;
		}
		//crmv@91082e

		var textBoxField = document.getElementById('txtbox_' + domkeyid);
		var contentWrapDOM = document.getElementById('contentwrap_' + domkeyid);

		var url = 'module=ModComments&action=ModCommentsAjax&file=DetailViewAjax&ajax=true&ajxaction=WIDGETADDREPLY&parentid=' + encodeURIComponent(parentid);
		url += '&comment=' + encodeURIComponent(textBoxField.value);
		url += '&parent_comment=' + encodeURIComponent(parent_comment);

		me.showBusy(indicator, true);

		jQuery.ajax({
			url: 'index.php?' + url,
			type: 'POST',
			dataType: 'html',
			success: function(data) {
				me.hideBusy(indicator);

				var responseTextTrimmed = trim(data);
				if (responseTextTrimmed.substring(0, 10) == ':#:SUCCESS') {
					//crmv@59626
					/*
					contentWrapDOM.innerHTML += responseTextTrimmed.substring(10);
					textBoxField.className = 'detailedViewTextBox detailedViewModCommTextBox';
					textBoxField.value = default_reply_text;
					jQuery('#saveButtonRow_'+domkeyid).hide();
					*/
					VTE.ModCommentsCommon.setAsRead(domkeyid, parent_comment, indicator);
					//crmv@59626e
				} else {
					alert(top.alert_arr.OPERATION_DENIED);
				}
				
				me.registerPanelBlocker(); // crmv@171115
			}
		});
	},
	
	deleteComment: function(domkeyid, id, indicator) {
		var me = this;
		
		if (me.busy) return false;
		
		var tblDOM = document.getElementById('tbl' + domkeyid);

		var url = 'module=ModComments&action=ModCommentsAjax&file=DetailViewAjax&ajax=true&ajxaction=WIDGETDELETECOMMENT&id=' + encodeURIComponent(id);

		me.showBusy(indicator, true);

		jQuery.ajax({
			url: 'index.php?' + url,
			type: 'POST',
			dataType: 'html',
			success: function(data) {
				me.hideBusy(indicator);

				var responseTextTrimmed = trim(data);
				if (responseTextTrimmed.substring(0, 10) == ':#:SUCCESS') {
					jQuery(tblDOM).remove();
				} else {
					alert(top.alert_arr.OPERATION_DENIED);
				}
			}
		});
	},
	
	//crmv@59626
	showFullContent: function(id, seen, domkeyid, commentid, indicator) {
		/*
		var replyid = id.split('_');
		if (replyid != commentid) {
			jQuery('#contentShowFull'+commentid).show();
		}
		*/
		if (seen == false) VTE.ModCommentsCommon.setAsRead(domkeyid, commentid, indicator);

		jQuery('#contentSmall' + id).hide();
		jQuery('#contentFull' + id).show();
		jQuery('#contentShowFull' + id).hide();
	},
	//crmv@59626e
	
	// crmv@171115
	registerPanelBlocker: function() {
		var me = this;
		
		var selectors = [
			'*[id^="txtbox_ModCommentsDetailViewBlockCommentWidget_"]', // Reply inputs
			'#txtbox_ModCommentsDetailViewBlockCommentWidget', // Start new talk input
		];
		
		VTE.registerPanelBlocker('ModComments', selectors);
	},
	// crmv@171115e
	
};

var ModCommentsCommon = ModCommentsCommon || {

	/**
	 * @deprecated
	 * This function has been moved to VTE.ModCommentsCommon class.
	 */
	
	checkComment: function(mode, domkeyid, visibility) {
		return VTE.callDeprecated('checkComment', VTE.ModCommentsCommon.checkComment, arguments, VTE.ModCommentsCommon);
	},
	
	/**
	 * @deprecated
	 * This function has been moved to VTE.ModCommentsCommon class.
	 */
	
	addComment: function(domkeyid, parentid, visibility, indicator) {
		return VTE.callDeprecated('addComment', VTE.ModCommentsCommon.addComment, arguments, VTE.ModCommentsCommon);
	},
	
	/**
	 * @deprecated
	 * This function has been moved to VTE.ModCommentsCommon class.
	 */
	
	addUsers: function(domkeyid, indicator, commentid) {
		return VTE.callDeprecated('addUsers', VTE.ModCommentsCommon.addUsers, arguments, VTE.ModCommentsCommon);
	},
	
	/**
	 * @deprecated
	 * This function has been moved to VTE.ModCommentsCommon class.
	 */
	
	reloadComment: function(domkeyid, indicator, commentid, setunread) {
		return VTE.callDeprecated('reloadComment', VTE.ModCommentsCommon.reloadComment, arguments, VTE.ModCommentsCommon);
	},
	
	/**
	 * @deprecated
	 * This function has been moved to VTE.ModCommentsCommon class.
	 */
	
	setAsUnread: function(domkeyid, commentid, indicator) {
		return VTE.callDeprecated('setAsUnread', VTE.ModCommentsCommon.setAsUnread, arguments, VTE.ModCommentsCommon);
	},
	
	/**
	 * @deprecated
	 * This function has been moved to VTE.ModCommentsCommon class.
	 */
	
	setAsRead: function(domkeyid, commentid, indicator) {
		return VTE.callDeprecated('setAsRead', VTE.ModCommentsCommon.setAsRead, arguments, VTE.ModCommentsCommon);
	},
	
	/**
	 * @deprecated
	 * This function has been moved to VTE.ModCommentsCommon class.
	 */
	
	checkAndSetAsRead: function(obj, domkeyid, commentid, indicator) {
		return VTE.callDeprecated('checkAndSetAsRead', VTE.ModCommentsCommon.checkAndSetAsRead, arguments, VTE.ModCommentsCommon);
	},
	
	/**
	 * @deprecated
	 * This function has been moved to VTE.ModCommentsCommon class.
	 */
	
	reloadContentWithFiltering: function(widget, parentid, criteria, targetdomid, indicator, searchkey) {
		return VTE.callDeprecated('reloadContentWithFiltering', VTE.ModCommentsCommon.reloadContentWithFiltering, arguments, VTE.ModCommentsCommon);
	},
	
	/**
	 * @deprecated
	 * This function has been moved to VTE.ModCommentsCommon class.
	 */
	
	appendContentWithFiltering: function(widget, parentid, criteria, targetdomid, indicator, searchkey) {
		return VTE.callDeprecated('appendContentWithFiltering', VTE.ModCommentsCommon.appendContentWithFiltering, arguments, VTE.ModCommentsCommon);
	},
	
	/**
	 * @deprecated
	 * This function has been moved to VTE.ModCommentsCommon class.
	 */
	
	addReply: function(domkeyid, parentid, parent_comment, indicator) {
		return VTE.callDeprecated('addReply', VTE.ModCommentsCommon.addReply, arguments, VTE.ModCommentsCommon);
	},
	
	/**
	 * @deprecated
	 * This function has been moved to VTE.ModCommentsCommon class.
	 */
	
	deleteComment: function(domkeyid, id, indicator) {
		return VTE.callDeprecated('deleteComment', VTE.ModCommentsCommon.deleteComment, arguments, VTE.ModCommentsCommon);
	},
	
	/**
	 * @deprecated
	 * This function has been moved to VTE.ModCommentsCommon class.
	 */
	
	showFullContent: function(id, seen, domkeyid, commentid, indicator) {
		return VTE.callDeprecated('showFullContent', VTE.ModCommentsCommon.showFullContent, arguments, VTE.ModCommentsCommon);
	},
	
};

function onModCommTextBoxFocus(obj,domkeyid,mode) {
	var def_text = default_text;
	if (mode == 'reply')
		def_text = default_reply_text;

	if (jQuery('#'+obj).val() == def_text) {
		jQuery('#'+obj)[0].className='detailedViewTextBoxOn detailedViewModCommTextBoxOn';
		jQuery('#'+obj).val('');
		jQuery('#saveButtonRow_'+domkeyid).show();
		if (mode != 'reply' && jQuery('#saveOptionsRow_'+domkeyid).length > 0) {
			jQuery('#saveOptionsRow_'+domkeyid).show();
		}
	}
}

function onModCommTextBoxBlur(obj,domkeyid,mode) {
	var def_text = default_text;
	if (mode == 'reply')
		def_text = default_reply_text;

	if (jQuery('#'+obj).val() == '') {
		jQuery('#'+obj)[0].className='detailedViewTextBox detailedViewModCommTextBoxOn';
		jQuery('#'+obj).val(def_text);
	}
}

function showAllReplies(id) {
	jQuery('#contentwrap_'+id).find('.tbl_ModCommReplies').each(function(){
		jQuery(this).show();
	});
	jQuery('#showAll'+id).hide();
}

function displayRecipientsInfo(obj,info) {
	var info = eval(decodeURIComponent(info));

	var olayer = document.getElementById('ModCommentsUsers_info');
	if(!olayer) {
		var olayer = document.createElement("div");
		olayer.id = "ModCommentsUsers_info";
		olayer.className = 'small';
		olayer.style.zIndex = findZMax()+1;
		olayer.style.padding = '4px';
		olayer.style.position = "absolute";
		document.body.appendChild(olayer);

		domnode = document.getElementById('ModCommentsUsers_info');
		jQuery(domnode).on('mouseover', function() { jQuery('#ModCommentsUsers_info').show(); });
		jQuery(domnode).on('mouseout', function() { jQuery('#ModCommentsUsers_info').hide(); });
	} else {
		olayer.innerHTML = '';
	}
	fnvshobj(obj,'ModCommentsUsers_info');
	// crmv@43448 - fix positioning error
	var parentPos = jQuery(obj).offset();
	jQuery(olayer).css({
		'left': parentPos.left,
		'top': parentPos.top,
	});
	// crmv@43448e

	for (item=0; item<info.length; item++) {
		var tmp = info[item];
		var span = '<span id="ModCommentsUsers_info_'+tmp.value+'" class="addrBubble">'
					+'<table cellpadding="3" cellspacing="0" class="small">'
					+'<tr valign="top">'
					+	'<td><img src="'+tmp.img+'" class="userAvatar" /></td>'
					+	'<td>'+tmp.name+'</td>'
					+'</tr>'
					+'</table>'
					+'</span>';
		olayer.innerHTML = olayer.innerHTML+span;
	}
}

function getModCommentsNews(obj) {
	
	//crmv@91082
	if(!SessionValidator.check()) {
		SessionValidator.showLogin();
		return false;
	}
	//crmv@91082e
	
	showFloatingDiv('ModCommentsNews', null, {modal:false, center:true, removeOnMaskClick:false}); // crmv@103908
	
	// fix the positioning!
	var el = jQuery('#ModCommentsNews').get(0);
	if (el) placeAtCenter(el, true);
	
	loadModCommentsNews(VTE.ModCommentsCommon.default_number_of_news);
	jQuery('#modcomments_search_text').val('');
	jQuery('#modcomments_search_text').blur();
}

function loadModCommentsNews(num,target,indicator,searchkey) { //crmv@31301
	if (target == undefined || target == '') {
		target = 'ModCommentsNews_iframe';
	}
	if (indicator == undefined || indicator == '') {
		indicator = 'indicatorModCommentsNews';
	}
	//crmv@31301
	if (searchkey == undefined || searchkey == '') {
		searchkey = '';
	}
	//crmv@31301e
	VTE.ModCommentsCommon.reloadContentWithFiltering('DetailViewBlockCommentWidget', '', 'Last'+num+'News', target, indicator, searchkey); //crmv@31301
}

//crmv@80503
function loadModCommentsPage(num,target,indicator,searchkey) {
	if (target == undefined || target == '') {
		target = 'ModCommentsNews_iframe';
	}
	if (indicator == undefined || indicator == '') {
		indicator = 'indicatorModCommentsNews';
	}
	//crmv@31301
	if (searchkey == undefined || searchkey == '') {
		searchkey = '';
	}
	//crmv@31301e
	
	
	var cpage = VTE.ModCommentsCommon.current_page,
		rowsPerPage = VTE.ModCommentsCommon.default_number_of_news;
		//page = Math.ceil(num/rowsPerPage);
	
	VTE.ModCommentsCommon.appendContentWithFiltering('DetailViewBlockCommentWidget', '', 'Page'+(cpage+1)+'News', target, indicator, searchkey); //crmv@31301
}
//crmv@80503e

function clearTextModComments(elem, prefix) {
	var jelem = jQuery(elem);
	var rest = jQuery.data(elem, 'restored');
	if (rest == undefined || rest == true) {
		jelem.val('');
		jQuery('#'+prefix+'_icn_canc').show();
		jQuery.data(elem, 'restored', false);
		jQuery('#'+prefix+'_text').focus();
	}
}

function restoreDefaultTextModComments(elem, deftext, prefix) {
	var jelem = jQuery(elem);
	if (jelem.val() == '') {
		jelem.val(deftext);
		jQuery('#'+prefix+'_icn_canc').hide();
		jQuery.data(elem, 'restored', true);
	}
}

function cancelSearchTextModComments(deftext, prefix, target, indicator) {
	jQuery('#'+prefix+'_text').val('');
	jQuery('#'+prefix+'_icn_canc').hide();
	restoreDefaultTextModComments(document.getElementById(prefix+'_text'), deftext);
	loadModCommentsNews(eval(jQuery('#'+target).contents().find('#max_number_of_news').val()),target,indicator);
}

function launchModCommentsSearch(e,prefix) {
	if (e.keyCode == 13) {
        jQuery('#'+prefix+'_icn_go').click();
    }
}