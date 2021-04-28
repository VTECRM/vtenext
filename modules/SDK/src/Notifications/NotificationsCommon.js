/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@OPER5904 */
 
if (typeof(NotificationsCommon) == 'undefined') {
	var NotificationsCommon = {
		
		unseen : {'ModComments':0,'ModNotifications':0,'Todos':0,'Processes':0}, //crmv@183872
		diff_default : 2,
		return_top : 0,
		return_left : 20,
		return_top_min : 2,
		return_left_min : 5,
		
		hide : function(notificationDiv) {
			jQuery('#'+notificationDiv).hide();
		},
		setDivPosition : function(crmvWinMaxStatus,notificationDiv,notificationImg) {
			/*
			if (crmvWinMaxStatus == 'close') {
				var offset_top = NotificationsCommon.return_top_min;
				var offset_left = NotificationsCommon.return_left_min;
			} else {
				var offset_top = NotificationsCommon.return_top;
				var offset_left = NotificationsCommon.return_left;
			}
			var img_offset = jQuery('#'+notificationImg).position();
			jQuery('#'+notificationDiv).css('top',eval(img_offset.top-offset_top));
			jQuery('#'+notificationDiv).css('left',eval(img_offset.left+jQuery('#'+notificationImg).width()-offset_left));
			*/
		},
		showChangesFirst : function(notificationDiv,notificationImg,plugin,interval) { //crmv@82948
			if (typeof(Storage) !== "undefined") {
				
				VTELocalStorage.enablePropagation("notificationsChanges", function(event){
					NotificationsCommon.drawAllChanges(notificationDiv,notificationImg,event.newValue);
				});
				NotificationsCommon.invalidateSemaphore(interval); //crmv@82948

				var lastNotificationsUpdate = parseInt(VTELocalStorage.getItem("lastNotificationsUpdate")) || 0;
				var semaphore = VTELocalStorage.getItem("notificationsSemaphore");
				var changes = VTELocalStorage.getItem("notificationsChanges");
				
				// se non ho lo localStorage scarico
				if (!lastNotificationsUpdate) {
					if (semaphore != "on") {
						NotificationsCommon.showChangesAndStorage(notificationDiv,notificationImg,plugin);
					}
					return;
				}
				
				// disegno usando la localStorage
				NotificationsCommon.drawAllChanges(notificationDiv,notificationImg,changes);
				
				lastNotificationsUpdate = new Date(lastNotificationsUpdate);
				var nextUpdate = new Date(lastNotificationsUpdate);
				nextUpdate.setTime(lastNotificationsUpdate.getTime() + 6*1000);	//every 6 sec
			
				// se nextUpdate e' gia' passato scarico
				var now = new Date();
				if (now >= nextUpdate) {
					if (semaphore != "on") {
						NotificationsCommon.showChangesAndStorage(notificationDiv,notificationImg,plugin);
					}
					return;
				}
				
				// se nextUpdate non e' passato attendo e scarico
				var diff = nextUpdate.getTime() - now.getTime() + (Math.floor(Math.random()*9)+1)*100;
				setTimeout(
					function(){
						if (semaphore != "on") {
							checkLastNotificationsUpdate = parseInt(VTELocalStorage.getItem("lastNotificationsUpdate")) || 0;
							if (lastNotificationsUpdate.getTime() != checkLastNotificationsUpdate) {
								NotificationsCommon.drawAllChanges(notificationDiv,notificationImg,VTELocalStorage.getItem("notificationsChanges"));
							} else {
								NotificationsCommon.showChangesAndStorage(notificationDiv,notificationImg,plugin);
							}
						}
					}
				, diff);
			} else {
				return NotificationsCommon.showChanges(notificationDiv,notificationImg,plugin);
			}
		},
		//crmv@82948
		invalidateSemaphore : function(interval){
			var semaphore = VTELocalStorage.getItem("notificationsSemaphore");
			if (semaphore == "on") {
				var lastNotificationsUpdate = parseInt(VTELocalStorage.getItem("lastNotificationsUpdate")) || 0;
				var now = (new Date()).getTime();
				if (!lastNotificationsUpdate || (now - lastNotificationsUpdate) > interval*2) {
					VTELocalStorage.setItem("notificationsSemaphore","off");				
				}
			}			
		},
		//crmv@82948 e
		showChangesInterval : function(notificationDiv,notificationImg,plugin,interval) {
			interval = parseInt(interval) || 0;
			if (interval == 0) return;
			
			if (typeof(Storage) !== "undefined") {
				var interval = interval + (Math.floor(Math.random()*9)-4)*1000;
				setInterval(
					function(){
						NotificationsCommon.invalidateSemaphore(interval); //crmv@82948
						var semaphore = VTELocalStorage.getItem("notificationsSemaphore");
						if (semaphore != "on") {
							var lastNotificationsUpdate = parseInt(VTELocalStorage.getItem("lastNotificationsUpdate")) || 0;
							var now = (new Date()).getTime();

							if ((now - lastNotificationsUpdate) > interval) {
								NotificationsCommon.showChangesAndStorage(notificationDiv,notificationImg,plugin);
							} else {
								// TODO non serve con l'evento
								NotificationsCommon.drawAllChanges(notificationDiv,notificationImg,VTELocalStorage.getItem("notificationsChanges"));
							}
						}
					}
				, interval);
			} else {
				setInterval(function(){
					NotificationsCommon.showChanges(notificationDiv,notificationImg,plugin);
				}, interval);
			}
		},
		showChangesAndStorage : function(notificationDiv,notificationImg,plugin,notifyMe) { // crmv@187621
			// crmv@187621
			var me = this;
			if (typeof(notifyMe) == 'undefined') notifyMe = true;
			// crmv@187621e
			VTELocalStorage.setItem("notificationsSemaphore","on");
			NotificationsCommon.showChanges(notificationDiv,notificationImg,plugin,function(data){
				if (data === 'ERROR') {
					VTELocalStorage.setItem("notificationsSemaphore","off");
					return;
				}
			
				var changes = jQuery.parseJSON(data);
				var notificationsChanges = jQuery.parseJSON(VTELocalStorage.getItem("notificationsChanges")) || {};
				jQuery.each(changes, function(module, count) {
					// crmv@187621
					if (notifyMe && typeof(notificationsChanges[module]) != 'undefined' && parseInt(count) > parseInt(notificationsChanges[module])) {
						me.notifyMe(module, {'delta': count - notificationsChanges[module] });
					}
					// crmv@187621e
					notificationsChanges[module] = count;
				});
				data = JSON.stringify(notificationsChanges);
				
				VTELocalStorage.setItem("notificationsChanges",data);
				VTELocalStorage.setItem("lastNotificationsUpdate",(new Date()).getTime());
				VTELocalStorage.setItem("notificationsSemaphore","off");
			});
		},
		showChanges : function(notificationDiv,notificationImg,plugin,callback) {
			jQuery.ajax({
				type: 'POST',
				url: 'index.php?module=SDK&action=SDKAjax&file=src/Notifications/CheckChanges&plugin='+plugin,
				success: function(data){
					var isJson = true;
					try {
						jQuery.parseJSON(data);
					} catch(err) {
						isJson = false;
					}
					if (isJson) {
						NotificationsCommon.drawAllChanges(notificationDiv,notificationImg,data);
						if (typeof callback == "function") {
							callback(data);
						}
					} else {
						if (typeof callback == "function") callback('ERROR');
						return;
					}
				},
				error : function(xhr, ajaxOptions, thrownError) {
					if (typeof callback == "function") callback('ERROR');
				}
			});
		},
		drawAllChanges : function(notificationDiv,notificationImg,changes) {
			/*
			var plugins = changes.split('#');
			for(var i=0;i<plugins.length;i++) {
				var plugin_count = plugins[i].split(':');
				var module = plugin_count[0].replace("\r\n",""); //crmv@30648
				var count = plugin_count[1];
				if (count != NotificationsCommon.unseen[plugin_count[0]]) {
					NotificationsCommon.setDivPosition(get_cookie('crmvWinMaxStatus'),module+notificationDiv,module+notificationImg);
					NotificationsCommon.drawChanges(module+notificationDiv,module+notificationImg,count,module);
				}
			}
			*/
			var changes = jQuery.parseJSON(changes);
			jQuery.each(changes, function(module, count){
				module = module.replace("\r\n",""); //crmv@30648
				if (count != NotificationsCommon.unseen[module]) {
					NotificationsCommon.setDivPosition(get_cookie('crmvWinMaxStatus'),module+notificationDiv,module+notificationImg);
					NotificationsCommon.drawChanges(module+notificationDiv,module+notificationImg,count,module);
				}
			});
		},
		drawChangesAndStorage : function(notificationDiv,notificationImg,data,plugin) {
			var notificationsChanges = jQuery.parseJSON(VTELocalStorage.getItem("notificationsChanges"));
			notificationsChanges[plugin] = data;
			notificationsChanges = JSON.stringify(notificationsChanges);
			
			VTELocalStorage.setItem("notificationsChanges",notificationsChanges);		
			VTELocalStorage.setItem("lastNotificationsUpdate",(new Date()).getTime());
		
			NotificationsCommon.drawChanges(notificationDiv,notificationImg,data,plugin);
		},
		drawChanges : function(notificationDiv,notificationImg,data,plugin) {
			if (get_cookie('crmvWinMaxStatus') == 'close') {
				var offset_left = NotificationsCommon.return_left_min;
			} else {
				var offset_left = NotificationsCommon.return_left;
			}

			jQuery('#'+notificationDiv+'Count').html(data);
			NotificationsCommon.unseen[plugin] = data;

			if (data == 0 || data == '' || data == null) {
				jQuery('#'+notificationDiv).hide();
				jQuery('#'+notificationDiv+'Count').hide(); // crmv@82419
				if (jQuery('#'+notificationImg).parent().parent().parent().css('padding-right') != '') {
					jQuery('#'+notificationImg).parent().parent().parent().css('padding-right',NotificationsCommon.diff_default+'px');
				}
			} else {
				jQuery('#'+notificationDiv).show();
				jQuery('#'+notificationDiv+'Count').show(); // crmv@82419
				var diff_tmp = eval(jQuery('#'+notificationDiv).outerWidth(true)-offset_left);
				diff = eval(NotificationsCommon.diff_default+diff_tmp);
				jQuery('#'+notificationImg).parent().parent().parent().css('padding-right',diff+'px');
			}
		},
		removeChanges : function(plugin,mode,target) {
			if (plugin == 'ModComments') {
				if (target == undefined || target == '') {
					target = 'ModCommentsNews_iframe';
				}
				if (mode == 'News') {
					var unseen = jQuery('#'+target).contents().find('#unseen_ids').val();
				} else {
					var unseen = jQuery('#unseen_ids').val();
				}
				if (mode != 'News') {
					var oObj = document.getElementById('tblModCommentsDetailViewBlockCommentWidget');
					if(oObj.style.display != 'block') return;
				}
				if(unseen != '') {
					jQuery.ajax({
						dataType: 'html',
						url: 'index.php?module=SDK&action=SDKAjax&file=src/Notifications/DeleteChanges&plugin='+plugin+'&id='+unseen,
						success: function(data){
							var tmp = data.split('|##|');
							if (mode == 'News') {
								top.NotificationsCommon.drawChangesAndStorage(plugin+'CheckChangesDiv',plugin+'CheckChangesImg',tmp[1],plugin);
							} else {
								NotificationsCommon.drawChangesAndStorage(plugin+'CheckChangesDiv',plugin+'CheckChangesImg',tmp[1],plugin);
							}
							NotificationsCommon.setDivPosition(get_cookie('crmvWinMaxStatus'),'ModNotificationsCheckChangesDiv','ModNotificationsCheckChangesImg');
						}
					});
				}
			} else if (plugin == 'ModNotifications') {
				var unseen = jQuery('#ModNotificationsDetailViewBlockCommentWidget_unseen_ids').val();
				if(unseen != '') {
					jQuery.ajax({
						dataType: 'html',
						url: 'index.php?module=SDK&action=SDKAjax&file=src/Notifications/DeleteChanges&plugin='+plugin+'&id='+unseen,
						success: function(data){
							var tmp = data.split('|##|');
							NotificationsCommon.drawChangesAndStorage(plugin+'CheckChangesDiv',plugin+'CheckChangesImg',tmp[1],plugin);
						}
					});
				}
			}
		},
		//crmv@30850
		removeChange : function(plugin,id,callback) {
			if (plugin == 'ModNotifications') {
				jQuery.ajax({
					dataType: 'html',
					url: 'index.php?module=SDK&action=SDKAjax&file=src/Notifications/DeleteChanges&plugin='+plugin+'&id='+id,
					success: function(data){
						var tmp = data.split('|##|');
						NotificationsCommon.drawChangesAndStorage(plugin+'CheckChangesDiv',plugin+'CheckChangesImg',tmp[1],plugin);
						if (typeof(callback) == 'function') callback();
					}
				});
			}
		},
		//crmv@30850e
		// crmv@187621 crmv@187869
		openFastPanel: function(module) {
			jQuery('[data-module="'+module+'"]').click();
		},
		notifyMe: function(module, opt) {
			var me = this;
			if (['ModComments','Messages','Processes'].indexOf(module) == -1) return;
			
			if (Notification.permission === 'default') {
				Notification.requestPermission(function(result){
					if (result === 'denied') return;
					if (result === 'default') return;
					me.notifyMe(module);
				});
			} else if (Notification.permission === 'denied') {
				return;
			} else {
				var title = (opt['delta'] == 1) ? opt['delta']+' '+alert_arr['LBL_NOTIFICATION_TITLE_S_'+module] : opt['delta']+' '+alert_arr['LBL_NOTIFICATION_TITLE_P_'+module],
				onclick = function() {
					if (!document.hidden && jQuery('[data-module="'+module+'"]').length > 0)
						me.openFastPanel(module);
					else {
						if (module == 'ModComments')
							window.open('index.php?module=Home&action=index&fastpanel='+module,'_blank').focus();
						else
							window.open('index.php?module='+module+'&action=index','_blank').focus();
					}
				},
				options = {
					icon: 'themes/logos/VTENEXT_notification.png',
					body: alert_arr.LBL_NOTIFICATION_BODY,
				};
				var notification = new Notification(title, options);
				if (onclick != '') notification.onclick = onclick;
			}
		}
		// crmv@187621e crmv@187869e
	}
}