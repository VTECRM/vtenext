/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@48159 crmv@48471 crmv@62140 crmv@62821 crmv@OPER8279 crmv@131944 */
/* crmv@192033 */

function fetch(timeout, onlynews) {
	if (timeout == undefined) timeout = 0;
	if (onlynews == undefined) onlynews = 'no';
	setTimeout(function(){
	
		if (ajax_enable == false) return false;
		
		//crmv@150593 crmv@174984
		if (timeout == 0 && basic_search_submitted == true) {
			// if I click the fetch button and there is a search submitted, reset the search before the fetch using a callback
			resetListSearch('Basic','','no');
			callSearch('Basic','',function(result){
				fetchReal();
			});
		} else if (timeout > 0 && basic_search_submitted == true) {
			// if it's running a search, skip the automatic fetch
			return false;
		} else {
			fetchReal();
		}
		//crmv@150593e crmv@174984e
		
		function fetchReal() {
			ajax_enable = false;
			
			jQuery('#fetchImg').hide();
			jQuery('#fetchImgLoader').show();
			
			jQuery.ajax({
			   url: 'index.php?module=Messages&action=MessagesAjax&file=Fetch&account='+current_account+'&folder='+encodeURIComponent(current_folder)+'&only_news='+onlynews, // crmv@180065
				type: 'post',
				success: function(data) {
					//TODO RELOAD_AND_FETCH
					if (data.indexOf('RELOAD')>=0) {
						getListViewEntries_js('Messages','start=1&account='+current_account+'&folder='+encodeURIComponent(current_folder),true,callbackListViewEntriesFetch); // crmv@180065
					//crmv@125629
					} else if (timeout == 0 && data.indexOf('error page message')>=0) {	// only on click on fetch button
						vtealert(data, null, {'html':true});
						jQuery('#fetchImgLoader').hide();
						jQuery('#fetchImg').show();
						ajax_enable = true;
					//crmv@125629e
					} else if (jQuery('#fetchImgLoader').is(':visible')) {
						//crmv@47654
						if (timeout == 0) {	// if I click the fetch button force the refresh
							getListViewEntries_js('Messages','start=1&account='+current_account+'&folder='+encodeURIComponent(current_folder),true,callbackListViewEntriesFetch); // crmv@180065
						} else {
						//crmv@47654e
							jQuery('#fetchImgLoader').hide();
							jQuery('#fetchImg').show();
							ajax_enable = true;
						}
					}
				}
			});
		}
	},timeout);
	
	fninvsh('mode2Folder');
}

//crmv@48471
function callbackListViewEntriesFetch(module,result) {
	setmCustomScrollbar('#ListViewContents');
	if (jQuery('#imap_navigation').val() == '1') {
		// do not update navigation because is already loaded
	} else {
		update_navigation_values(window.location.href+'&account='+current_account+'&folder='+encodeURIComponent(current_folder)+'&reload_counts=yes','Messages',true,updateCounts); // crmv@180065
	}
	jQuery('#fetchImgLoader').hide();
	jQuery('#fetchImg').show();
	ajax_enable = true;
}

function updateCounts(module,result) {
	if (result[2] != '') {
		jQuery('#Folders').html(result[2]);
	}
	if (result[3] != '') {
		jQuery('#Accounts').html(result[3]);
	}
	if (jQuery('#fetchImgLoader').is(':visible')) {
		jQuery('#fetchImgLoader').hide();
		jQuery('#fetchImg').show();
	}
	//crmv@169525
	if (top.jQuery('#MessagesCheckChangesImg').length > 0) {
		NotificationsCommon.showChanges('MessagesCheckChangesDiv','MessagesCheckChangesImg','Messages',function(data){
			var counts = jQuery.parseJSON(data);
			NotificationsCommon.drawChangesAndStorage('MessagesCheckChangesDiv','MessagesCheckChangesImg',counts['Messages'],'Messages');
		});
	}
	//crmv@169525e
}
//crmv@48471e

function reloadFolders() {
	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php?module=Messages&action=MessagesAjax&file=Folders&account='+current_account,
		dataType: 'html',
		async: false,
		success: function(data){
			jQuery('#Folders').html(data);
			jQuery("#status").hide();
		}
	});
}

//crmv@204525
function checkAttachment(urlParam, name, tagid, mode)
{
	jQuery.ajax({
		url: urlParam + '&checkOnly=1',
		success: function(data){
			if(data == -1)
			{
				if(mode === 'compose')
				{
					jQuery('#' + tagid).hide();
					vtealert(alert_arr.LBL_ATTACHMENT_NOT_EXIST.replace('{name}', name), function() {
					});
				}
				else if(mode === 'detailview')
				{
					jQuery('#' + tagid).attr('onclick', 'vtealert(alert_arr.LBL_ATTACHMENT_DELETED)').attr("href", "javascript:;");
				}
			}
		}
	});
}
//crmv@204525e

function changeButtons(view,mode,fastinbox,skip_eval_buttons) {

	if (mode == undefined) var mode = '';
	if (fastinbox == undefined || fastinbox == '') var fastinbox = false;
	if (skip_eval_buttons == undefined || skip_eval_buttons == '') var skip_eval_buttons = false;
	
	jQuery('#Messages_Buttons_List').children().hide();
	
	if (skip_eval_buttons == false) {
		if (view == 'ListView') {
			if (current_account == 'all' && (current_folder != 'INBOX' && current_folder != 'Sent' && current_folder != 'Spam')) jQuery('#Buttons_List_3_ListView #editfolder').hide(); // crmv@192843
			else jQuery('#Buttons_List_3_ListView #editfolder').show();
			
			jQuery('#Buttons_List_3_ListView #go2accounts').hide();
			jQuery('#Buttons_List_3_ListView #go2folders').hide();
			if (isMultiAccount && fastinbox) {
				jQuery('#Buttons_List_3_ListView #go2accounts').show();
			} else {
				jQuery('#Buttons_List_3_ListView #go2folders').show();
			}
		} else if (view == 'Thread') {
			if (current_account == 'all' && (current_folder != 'INBOX' && current_folder != 'Sent' && current_folder != 'Spam')) jQuery('#Buttons_List_3_ListView #editfolder').hide(); // crmv@192843
			else jQuery('#Buttons_List_3_Thread #editthread').show();
		
			jQuery('#Buttons_List_3_Thread #go2inbox').hide();
			jQuery('#Buttons_List_3_Thread #go2folder').hide();
			if (isMultiAccount && fastinbox) {
				jQuery('#Buttons_List_3_Thread #go2inbox').show();
			} else {
				jQuery('#Buttons_List_3_Thread #go2folder').show();
			}
		}
	}
	if (mode != '') mode = '_'+mode;
	jQuery('#Buttons_List_3_'+view+mode).show();
}

function changeLeftView(view,dim_folder,dim_list,name,label) {
	if (view == 'accounts') {
		resetListSearch('Basic','','no');	//crmv@150593
		jQuery('#Accounts').show();
		jQuery('#Folders').hide();
		jQuery('#ListViewContents').hide();
		jQuery('#Accounts').parent().css('width',dim_list);
		jQuery('#Folders').parent().css('width',dim_folder);
		jQuery('#ListViewContents').css('width',dim_folder);
		jQuery('#Accounts').slimScroll({ scrollTo: '0px' });
		
		changeButtons('Accounts');
		current_account = 'all'; // crmv@186880
		
	} else if (view == 'folders') {
		if (jQuery('#basic_search_icn_canc').css('display') != 'none') current_folder = '';	//crmv@42846 forzo per ricaricare la lista
		resetListSearch('Basic','','no');	//crmv@150593
		jQuery('#Accounts').hide();
		jQuery('#ListViewContents').hide();
		jQuery('#Accounts').parent().css('width',dim_folder);
		jQuery('#Folders').parent().css('width',dim_list);
		jQuery('#ListViewContents').css('width',dim_folder);
		jQuery('#Folders').slimScroll({ scrollTo: '0px' });
		
		changeButtons('Folders');
		
		if (name != undefined && current_account != name) {
			jQuery('#Folders').html('');
			jQuery('#Folders').show();
			current_account = name;
			current_folder = '';	//forzo per ricaricare la lista
			reloadFolders();
		} else {
			jQuery('#Folders').show();
		}
	} else if (view == 'list') {
		jQuery('#Accounts').hide();
		jQuery('#Folders').hide();
		jQuery('#Accounts').parent().css('width',dim_folder);
		jQuery('#Folders').parent().css('width',dim_folder);
		jQuery('#ListViewContents').css('width',dim_list);
		
		var iWasInThread = iAmInThread;	//crmv@121301
		if (iAmInThread)
			changeButtons('ListView','','',true);
		else
			changeButtons('ListView');
		
		if (current_folder != name) {
			jQuery('#ListViewContents').html('');
			jQuery('#ListViewContents').show();
			current_folder = name;
			//crmv@48159	crmv@79192
			if (current_folder == 'Shared' || current_folder == 'Links' || current_folder == 'Flagged') {
				var list_button_perm = {'empty_button':false,'unseen_button':false,'seen_button':false,'move_button':false,'trash_button':false};
			} else if (current_folder == specialFolders['INBOX'] || current_folder == specialFolders['Sent']) {
				var list_button_perm = {'empty_button':false,'unseen_button':true,'seen_button':true,'move_button':true,'trash_button':true};
			} else {
				var list_button_perm = {'empty_button':true,'unseen_button':true,'seen_button':true,'move_button':true,'trash_button':true};
			}
			jQuery.each(list_button_perm,function(k,v){
				if (v) jQuery('#'+k).show(); else jQuery('#'+k).hide();
			});
			//crmv@48159e	crmv@79192e
			jQuery('#ListViewContents').css('visibility','hidden');	//non svuotare il contenuto perche' viene letto l'input search_url
			if (iAmInThread == false)
				jQuery('#rec_string').parent().html('<div class="listMessageTitle" title="'+label+'">'+label+'</div><span id="rec_string" style="display:none"></span><span id="rec_string3"></span>');
			var search = '';
			if (jQuery('#basic_search_icn_canc').css('display') != 'none') {
				search = '&search=true&query=true&search_field=&searchtype=BasicSearch&search_text='+encodeURIComponent(jQuery('#basic_search_text').val());	//crmv@121301
			}
			getListViewEntries_js('Messages','account='+current_account+'&folder='+encodeURIComponent(name)+search,true,function(module,ajaxresult){	//crmv@121301 crmv@180065
				jQuery('#ListViewContents').css('visibility','visible');
				setmCustomScrollbar('#ListViewContents');
				if (jQuery('#imap_navigation').val() == '1' || iWasInThread) {	//crmv@121301
					// do not update navigation because is already loaded
				} else {
					update_navigation_values(window.location.href+'&account='+current_account+'&folder='+encodeURIComponent(name),'Messages'); // crmv@180065
				}
			});
		} else {
			jQuery('#ListViewContents').show();
		}
	}
	fninvsh('mode2Folder');
}

function editViewList(show,skip_update_flag,force_reload) {
	if (show) {
		resetDetailViewButtons();
		list_status = 'edit';
		jQuery('.lvtColDataHoverMessage').removeClass('lvtColDataHoverMessage');	//crmv@48159
		jQuery('#Buttons_List_3_ListView_Edit .listMessageTitle').html(jQuery('#Buttons_List_3_ListView .listMessageTitle').html());
		changeButtons('ListView','Edit','',true);
		jQuery('input:checkbox[name="selected_id"]').show();
	} else {
		unselectAllIds();
		list_status = 'view';
		selectRecord(current_record,skip_update_flag,force_reload);
		if (jQuery('#Button_List_Detail').html() == '') {
			reloadDetailViewButtons(current_record);
		}
		changeButtons('ListView','','',true);
		jQuery('input:checkbox[name="selected_id"]').hide();
	}
	fninvsh('mode2Folder');
}

function editViewThread(show,skip_update_flag,force_reload) {
	if (show) {
		resetDetailViewButtons();
		list_status = 'edit';
		jQuery('#row_'+last_thread_clicked).removeClass('lvtColDataHoverMessage');
		changeButtons('Thread','Edit','',true);
		jQuery('input:checkbox[name="selected_id"]').show();
	} else {
		unselectAllIds();
		list_status = 'view';
		selectRecord(current_record,skip_update_flag,force_reload);
		if (jQuery('#Button_List_Detail').html() == '') {
			reloadDetailViewButtons(current_record);
		}
		changeButtons('Thread','','',true);
		jQuery('input:checkbox[name="selected_id"]').hide();
	}
	fninvsh('mode2Folder');
}

// crmv@62394
function populateTurbolift(recordid, force) {
	force = force || false;
	var doCall = false;
	if (jQuery("#flag_"+recordid+"_relations").length == 0) {
		doCall = true;
	} else if (jQuery("#flag_"+recordid+"_relations").length > 0 && jQuery("#flag_"+recordid+"_relations").css('display') == 'block') {
		doCall = true;
	}
	if (doCall || force) {
		jQuery.ajax({
			url: 'index.php?module=Messages&action=MessagesAjax&file=Turbolift&record='+recordid,
			dataType: 'html',
			success: function(data) {
				jQuery('#TurboliftContentRelations').html(data);
			},
			error: function() {
				jQuery('#TurboliftContentRelations').html('Network error');
			}
		});
	} else {
		jQuery('#TurboliftContentRelations').html('');
	}
}
// crmv@62394e

function selectRecord(id,skip_update_flag,force_reload,skip_reload_turbolift,async) {
	if (id == '') return false;
	if (skip_update_flag != true) skip_update_flag = false;
	if (force_reload != true) force_reload = false;
	if (skip_reload_turbolift != true) skip_reload_turbolift = false;
	if (async == undefined) async = true;

	if (list_status == 'edit') {

		if (jQuery("input:checkbox#"+id).prop('checked')) {
			jQuery('#row_'+id).removeClass('lvtColDataHoverMessage');
			jQuery("input:checkbox#"+id).prop('checked',false);
		} else {
			jQuery('#row_'+id).addClass('lvtColDataHoverMessage');
			jQuery("input:checkbox#"+id).prop('checked',true);
		}
		jQuery("input:checkbox#"+id).triggerHandler('click');

	} else {
		// if (ajax_enable == false) return false; // crmv@140887
		// ajax_enable = false; // crmv@140887

		jQuery('#ListViewContents [id^="row_"]').removeClass('lvtColDataHoverMessage');
		jQuery('#row_'+id).addClass('lvtColDataHoverMessage');

		if (force_reload || current_record != id) {
			//crmv@159110
			if (typeof(jQuery('#DetailViewContents').slimScroll) == 'function') {
				// crmv@192614
				if (typeof(messageMode) != 'undefined' && messageMode == 'Detach') {}
				else jQuery('#DetailViewContents').slimScroll({ scrollTo: '0px' });
				// crmv@192614e
				cleanDiv('DetailViewContents');
			}
			if (skip_reload_turbolift == false) {
				cleanDiv('TurboliftContentRelations');
			}
			var params = "&account="+current_account+"&folder="+encodeURIComponent(current_folder); // crmv@180065
			if (typeof(current_record) != 'undefined') {
				var old_record = current_record;	// crmv@62394
				current_record = id;
			}
			//crmv@159110e
			// crmv@192614
			var mode = 'ListView';
			if (typeof(messageMode) != 'undefined' && messageMode != '') mode = messageMode;
			// crmv@192614e
			jQuery.ajax({
				url: 'index.php?module=Messages&action=MessagesAjax&file=DetailView&mode='+mode+'&record='+id+params, // crmv@192614
				dataType: 'html',
				async: async,
				success: function(data){
					//crmv@159110
					if (jQuery('#DetailViewContents').length == 0) {
						location.reload();
						return;
					}
					//crmv@159110e
					// crmv@187406
					var width = (parseInt(jQuery('#DetailViewContents').width() || 0) - 50) + 'px';
					jQuery('#DetailViewContents').html(data);
					jQuery('#DetailViewContentDescr').css('width', width);
					// crmv@187406e
					jQuery('#TurboliftButtons').show();
					// crmv@62394 - reload tracking status
					if (window.CalendarTracking) {
						var curShown = parseInt(CalendarTracking.getCurrentShownId()),
							tracked = parseInt(CalendarTracking.getCurrentTrackedId()),
							reloadTracking = !tracked || (old_record == tracked || id == tracked);
						if (reloadTracking) CalendarTracking.reloadButtons(id);
					}
					// crmv@62394e
					if (skip_reload_turbolift == false) {
						populateTurbolift(id);
					}
					if (skip_update_flag == false && jQuery('#flag_'+id+'_unseen').css('display') == 'block') {
						//flag(id,'seen',1);
						jQuery('#flag_'+id+'_unseen').hide();
						update_navigation_values("module=Messages&action=MessagesAjax&file=ListView&ajax=true&account="+current_account+"&folder="+encodeURIComponent(current_folder)+'&reload_counts=yes','Messages',true,updateCounts);	//crmv@48471 crmv@180065
					}
					// ajax_enable = true; // crmv@140887
					VteJS_DialogBox.hideprogress('DetailViewContents');
				}
			});
			fninvsh('mode2Folder');
		} else {
			// ajax_enable = true; // crmv@140887
		}
	}
}

function getLuckyMessage(folder,id) {
	return getFile('index.php?module=Messages&action=MessagesAjax&file=LuckyMessage&account='+current_account+'&folder='+folder+'&record='+id);
}

function selectLuckyMessage(folder,id) {
	var record = getLuckyMessage(folder,id);
	if (record != '') selectRecord(record);
}

function showHeaderDetails(type) {
	if (type == 'small') {
		jQuery('#header_detail_large').hide();
		jQuery('#header_detail_small').show();
		jQuery('#header_detail_large_link').show();
		jQuery('#header_detail_small_link').hide();
	} else if (type == 'large') {
		jQuery('#header_detail_small').hide();
		jQuery('#header_detail_large').show();
		jQuery('#header_detail_large_link').hide();
		jQuery('#header_detail_small_link').show();
	}
}

function resetDetailViewButtons() {
	jQuery('#Button_List_Detail').html('');
}

function resetDetailView() {
	jQuery('#DetailViewContents').html('');
	resetDetailViewButtons();
}

function flag(id,flag,value) {
	var mainPage = self;
	if (messageMode == 'Detach') mainPage = window.opener;
	
	jQuery("#status").show();
	var postbody = "module=Messages&action=MessagesAjax&file=Flag&record="+id+"&flag="+flag+"&value="+value;
	jQuery.ajax({
		url: 'index.php',
		method : 'POST',
		data: postbody,
		success: function(result) {
			jQuery("#status").hide();
			if (result == 'SUCCESS') {
				if (flag == 'delete') {
					mainPage.jQuery('#row_'+id).remove();
					mainPage.resetDetailView();
					mainPage.selectLuckyMessage(current_folder,id);
					mainPage.reloadFolders(); // crmv@187622
					if (messageMode == 'Detach') window.close();
				} else {
					if (flag == 'seen') {
						if (value == 0) {
							mainPage.jQuery('#flag_'+id+'_unseen').show();
						} else {
							mainPage.jQuery('#flag_'+id+'_unseen').hide();
						}
					} else if (flag == 'flagged') {
						if (value == 1) {
							mainPage.jQuery('#flag_'+id+'_flagged').show();
						} else {
							mainPage.jQuery('#flag_'+id+'_flagged').hide();
						}
					}
					reloadDetailViewButtons(id);
				}
				mainPage.update_navigation_values("module=Messages&action=MessagesAjax&file=ListView&ajax=true&account="+current_account+"&folder="+encodeURIComponent(current_folder)+'&reload_counts=yes','Messages',true,updateCounts);	//crmv@48471 crmv@180065
			}
		}
	});
}

function reloadDetailViewButtons(id) {
	var mainPage = self;
	if (messageMode == 'Detach') mainPage = window.opener;
	
	var postbody = "module=Messages&action=MessagesAjax&file=DetailViewButtons&record="+id+"&folder="+encodeURIComponent(current_folder);	//crmv@79192 crmv@180065
	jQuery.ajax({
		url: 'index.php',
		method : 'POST',
		data: postbody,
		success: function(result) {
			jQuery('#Button_List_Detail').html(result);
			if (messageMode == 'Detach') {
				mainPage.jQuery('#Button_List_Detail').html(result);
			}
		}
	});
}

function massFlag(action) {
	var module = 'Messages';
	var idstring = get_real_selected_ids(module);
	if (idstring.substr('0', '1') == ";")
		idstring = idstring.substr('1');
	var idarr = idstring.split(';');
	var count = idarr.length;
	var xx = count - 1;
	var viewid = getviewId();
	if (idstring == "" || idstring == ";" || idstring == 'null') {
		alert(alert_arr.SELECT);
		return false;
	} else {
		if (action == 'Delete') {
			var postbody = "module=Users&action=massdelete&return_module="+module+"&"+gstart+"&viewname="+viewid;
		} else {
			var postbody = "module="+module+"&action="+module+"Ajax&file=MassFlag&massaction="+action+"&"+gstart+"&viewname="+viewid;
		}
		postbody += "&account="+current_account+"&folder="+encodeURIComponent(current_folder)+"&thread="+getObj('thread').value; // crmv@180065

		var postbody2 = "module="+module+"&action="+module+"Ajax&file=ListView&ajax=true&"+gstart+"&viewname="+viewid;
		postbody2 += "&account="+current_account+"&folder="+encodeURIComponent(current_folder)+"&thread="+getObj('thread').value; // crmv@180065

		VteJS_DialogBox.block();
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php',
			method : 'POST',
			data: postbody,
			success: function(result) {
				VteJS_DialogBox.unblock();
				jQuery("#status").hide();

				result = result.split('&#&#&#');
				jQuery("#ListViewContents").html(result[2]);
				if (result[1] != '')
					alert(result[1]);
				jQuery('#basicsearchcolumns').html('');
				setmCustomScrollbar('#ListViewContents');

				if (action == 'Delete') {
					current_record = getLuckyMessage(current_folder,'');
				}

				if (iAmInThread)
					editViewThread(false,true,true);
				else
					editViewList(false,true,true);
				
				update_navigation_values(postbody2+'&reload_counts=yes','Messages',true,updateCounts);	//crmv@48471
			}
		});
	}
}

function preView(module,id) {
	if (ajax_enable == false) return false;
	ajax_enable = false;

	if (id == preview_id && preview_current_record != '') {
		// crmv@192614
		if (typeof(messageMode) != 'undefined' && messageMode == 'Detach') {
			location.reload();
			return;
		}
		// crmv@192614e
		ajax_enable = true;

		if (jQuery('#preView'+id+' .previewEntitySelected').length > 0) var preViewTbl = jQuery('#preView'+id+' .previewEntitySelected'); else var preViewTbl = jQuery('#preView'+id+'.previewEntitySelected');	//crmv@152802 support all themes
		preViewTbl.removeClass('previewEntitySelected');
		preViewTbl.addClass('previewEntity');
		selectRecord(preview_current_record,false,true,true);
		preview_current_record = '';
	} else {
		jQuery('.previewEntitySelected').addClass('previewEntity');
		jQuery('.previewEntitySelected').removeClass('previewEntitySelected');
		if (jQuery('#preView'+id+' .previewEntity').length > 0) var preViewTbl = jQuery('#preView'+id+' .previewEntity'); else var preViewTbl = jQuery('#preView'+id+'.previewEntity');	//crmv@152802 support all themes
		preViewTbl.removeClass('previewEntity');
		preViewTbl.addClass('previewEntitySelected');

		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php?module='+module+'&action='+module+'Ajax&file=PreView&record='+id+'&return_module=Messages&return_id='+current_record,
			dataType: 'html',
			success: function(data){
				jQuery("#status").hide();
				preview_id = id;
				if (current_record != '') {
					preview_current_record = current_record;
					current_record = '';
				}
				resetDetailViewButtons();
				// crmv@192614
				if (typeof(messageMode) != 'undefined' && messageMode == 'Detach') {}
				else jQuery('#DetailViewContents').slimScroll({ scrollTo: '0px' });
				// crmv@192614e
				jQuery('#DetailViewContents').html(data);

				ajax_enable = true;
			}
		});
	}
}

function MoveDisplay(obj,mode,id,folder) {
	if (folder == '' || folder == 'undefined' || folder == undefined) {
		var folder = current_folder;
	}
	if (mode == 'mass') {
		var idstring = get_real_selected_ids('Messages');
		if (idstring.substr('0', '1') == ";")
			idstring = idstring.substr('1');
		var idarr = idstring.split(';');
		var count = idarr.length;
		var xx = count - 1;
		if (idstring == "" || idstring == ";" || idstring == 'null') {
			alert(alert_arr.SELECT);
			return false;
		}
	}
	jQuery('#mode2Folder_list').html('');
	fnvshobj(obj,'mode2Folder');
	jQuery('#indicatorMode2Folder').show();
	var url = 'index.php?module=Messages&action=MessagesAjax&file=Move&mode='+mode+'&view=display&account='+current_account+'&current_folder='+encodeURIComponent(folder); // crmv@180065
	if (mode == 'single') {
		url += '&record='+id;
	}
	jQuery.ajax({
		url: url,
		dataType: 'html',
		success: function(data){
			jQuery('#indicatorMode2Folder').hide();
			jQuery('#mode2Folder_list').html(data);
		}
	});
}

function Move(folder,id) {
	var mainPage = self;
	if (messageMode == 'Detach') mainPage = window.opener;
	
	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php?module=Messages&action=MessagesAjax&file=Move&folder='+folder+'&mode=single&record='+id+'&account='+current_account+'&current_folder='+encodeURIComponent(current_folder)+'&thread='+mainPage.getObj('thread').value, // crmv@180065
		dataType: 'html',
		success: function(data){
			jQuery("#status").hide();
			mainPage.jQuery('#TurboliftContentRelations').html('');
			mainPage.current_record = '';
			mainPage.jQuery('#row_'+id).remove();
			mainPage.resetDetailView();
			mainPage.selectLuckyMessage(current_folder,id);

			var viewid = mainPage.getviewId();
			var postbody2 = "module=Messages&action=MessagesAjax&file=ListView&ajax=true&"+mainPage.gstart+"&viewname="+viewid;
			mainPage.update_navigation_values(postbody2+'&reload_counts=yes','Messages',false,updateCounts);	//crmv@48471
			fninvsh('mode2Folder');
			
			if (messageMode == 'Detach') window.close();
		}
	});
}

function massMove(folder) {
	VteJS_DialogBox.block();
	jQuery.ajax({
		url: 'index.php?module=Messages&action=MessagesAjax&file=Move&folder='+folder+'&mode=mass&account='+current_account+'&current_folder='+encodeURIComponent(current_folder)+'&thread='+getObj('thread').value, // crmv@180065
		dataType: 'html',
		success: function(data){
			VteJS_DialogBox.unblock();
			jQuery('#TurboliftContentRelations').html('');
			resetDetailView();
			current_record = getLuckyMessage(current_folder,'');

			result = data.split('&#&#&#');
			jQuery("#ListViewContents").html(result[2]);
			if (result[1] != '')
				alert(result[1]);
			jQuery('#basicsearchcolumns').html('');
			setmCustomScrollbar('#ListViewContents');

			if (iAmInThread)
				editViewThread(false,true,true);
			else
				editViewList(false,true,true);

			var viewid = getviewId();
			var postbody2 = "module=Messages&action=MessagesAjax&file=ListView&ajax=true&"+gstart+"&viewname="+viewid;
			update_navigation_values(postbody2+'&reload_counts=yes','Messages',true,updateCounts);	//crmv@48471
			fninvsh('mode2Folder');
		}
	});
}

function selectThread(id,father,count,label) {
	if (list_status == 'edit') {
		return false;
	}
	if (ajax_enable == false) return false;
	ajax_enable = false;

	iAmInThread = true;
	last_thread_clicked = id;
	jQuery('#ListViewContents [id^="row_"]').removeClass('lvtColDataHoverMessage');
	jQuery('#row_'+id).addClass('lvtColDataHoverMessage');

	var folder_label = jQuery('#Buttons_List_3_ListView .listMessageTitle').html();
	//jQuery('#ListViewContents').hide();	//non svuotare il contenuto perche viene letto l'input search_url
	
	changeButtons('Thread');
	jQuery('#Buttons_List_3_Thread .threadMessageTitle').html(count+' '+label);
	jQuery('#Buttons_List_3_Thread #go2folder').val('< '+folder_label);
	jQuery('#Buttons_List_3_Thread #go2inbox').val('< '+folder_label);
	jQuery('#Buttons_List_3_Thread #go2folder i').attr('data-original-title',folder_label);	// for theme next
	jQuery('#Buttons_List_3_Thread #go2inbox i').attr('data-original-title',folder_label);	// for theme next
	
	//crmv@121301
	if (basic_search_submitted) {
		jQuery('#Buttons_List_3_Thread #go2folder').attr('basic_search_text',jQuery('#basic_search_text').val());
		basic_search_submitted = false;
		jQuery('#basic_search_icn_canc').click();
		jQuery('#search_url').val('');
	}
	
	getListViewEntries_js('Messages','start=1&account='+current_account+'&folder='+encodeURIComponent(current_folder)+'&thread='+father,true,function(module,ajaxresult){ // crmv@180065
		jQuery('#ListViewContents').show();
		setmCustomScrollbar('#ListViewContents');
		//update_navigation_values(window.location.href+'&account='+current_account+'&folder='+current_folder+'&thread='+father,'Messages');
		ajax_enable = true;
		selectRecord(id);
	});
	//crmv@121301e
}

function returnToFolder(dim_folder,dim_list) {
	var folder = current_folder;
	current_folder = '';	//forzo per ricaricare la lista
	
	//crmv@121301
	if (typeof(jQuery('#Buttons_List_3_Thread #go2folder').attr('basic_search_text')) != 'undefined') {
		jQuery('#basic_search_text').val(jQuery('#Buttons_List_3_Thread #go2folder').attr('basic_search_text'));
		jQuery('#Buttons_List_3_Thread #go2folder').removeAttr('basic_search_text')
		basic_search_submitted = true;
		jQuery('#basic_search_icn_canc').show();
	}
	//crmv@121301e
	
	changeLeftView('list',dim_folder,dim_list,folder,jQuery('#Buttons_List_3_Thread #go2folder').val().replace('< ',''));

	jQuery('#ListViewContents [id^="row_"]').removeClass('lvtColDataHoverMessage');
	jQuery('#row_'+last_thread_clicked).addClass('lvtColDataHoverMessage');
	current_record = last_thread_clicked;
	iAmInThread = false;
}

function returnToINBOXFolder(dim_folder,dim_list) {
	var folder = current_folder;
	current_folder = '';	//forzo per ricaricare la lista
	changeLeftView('list',dim_folder,dim_list,folder,jQuery('#Buttons_List_3_Thread #go2inbox').val().replace('< ',''));

	jQuery('#ListViewContents [id^="row_"]').removeClass('lvtColDataHoverMessage');
	jQuery('#row_'+last_thread_clicked).addClass('lvtColDataHoverMessage');
	current_record = last_thread_clicked;
	iAmInThread = false;
}

function emptyFolder() {
	if (confirm(alert_arr.CONFIRM_EMPTY_FOLDER)) {	//crmv@79544
		VteJS_DialogBox.block();
		jQuery.ajax({
			url: 'index.php?module=Messages&action=MessagesAjax&file=EmptyFolder&account='+current_account+'&folder='+encodeURIComponent(current_folder), // crmv@180065
			dataType: 'html',
			success: function(data){
				VteJS_DialogBox.unblock();
				editViewList(false);
				resetDetailView();
				jQuery('#TurboliftContentRelations').html('');
				jQuery('#ListViewContents').html('');
				current_record = '';
				current_folder = '';
				reloadFolders();
				fninvsh('mode2Folder');
				jQuery('#go2folders').click();
			}
		});
	}
}

function saveEventCallback(data) {
	var activityid = data.activityid,
		module = 'Calendar',
		parentid = jQuery('#from_crmid').val(),
		mode = jQuery('#popup_mode').val();

	LPOP.showActivity();
	if (mode == 'compose') {
		jQuery.ajax({
			url: 'index.php?module=Utilities&action=UtilitiesAjax&file=Card', // crmv@137471
			data: '&idlist='+activityid,
			type: 'POST',
			complete: function() {
				LPOP.hideActivity();
			},
			success: function(data, status, xhr) {
				if (!data.match(/error/i)) {
					//alert(alert_arr.LBL_MESSAGE_LINKED);
					window.parent.jQuery('#ComposeLinks').append(data);
					window.parent.jQuery('#relation').val(window.parent.jQuery('#relation').val()+'|'+activityid);

					// close popup
					closePopup();
				}
			}
		});
	} else {
		// crmv@43050
		linkModules('Messages', parentid, module, activityid,
			{
				'mode' : mode
			},
			function(data) {
				if (!data.match(/error/i)) {
					//alert(alert_arr.LBL_MESSAGE_LINKED);
					var attList = getAttachmentsToLink();

					window.parent.jQuery('#flag_'+parentid+'_relations').show();
					if (mode == 'linkdocument') {
						window.parent.saveDocument(parentid,jQuery('#contentid').val(),activityid,module);
					} else if (attList.length > 0) {
						for (var i=0; i<attList.length; ++i) {
							window.parent.saveDocument(parentid,attList[i],activityid,module);
						}
					}
					window.parent.selectRecord(parentid,false,true);
					LPOP.hideActivity();
					// close popup
					closePopup();
				} else {
					//alert(alert_arr.LBL_RECORD_SAVE_ERROR);
				}
			}
		);
		// crmv@43050e
	}
	return false;
}

//get list of contentids to attach
function getAttachmentsToLink() {
	var globalCheck = jQuery('#popupMsgAttachMainCheck').is(':checked'),
		list = [];
	if (globalCheck) {
		jQuery('#popupAttachDiv input[id^=msgattach_]:checked').each(function(index, item) {
			list.push(item.id.replace('msgattach_', ''));
		});
	}
	return list;
}

function messagesChangeAttach() {
	var checked = jQuery('#popupMsgAttachMainCheck').is(':checked');
	jQuery('#popupAttachDiv .popupMsgAttachCheck').prop('checked', (checked ? "checked" : false));
}

function messagesChangeSingleAtt(item) {
	if (item.checked) {
		jQuery('#popupMsgAttachMainCheck').prop('checked', 'checked');
	}
}
//crmv@42752e

//crmv@48159
function appendFetch() {
	if (ajax_enable == false) return false;
	ajax_enable = false;
	jQuery("#status").show();
	jQuery("#ListViewContents").mCustomScrollbar("disable");

	update_navigation_values(window.location.href+'&account='+current_account+'&folder='+encodeURIComponent(current_folder),'Messages',false,function(module,result){ // crmv@180065
		if (jQuery("#appendNextListViewEntries").length > 0) {							// se c'e' un'altra pagina scorro
			jQuery('#appendNextListViewEntries').click();
		} else {																		// altrimenti resto fermo in fondo pagina
			jQuery('#indicatorAppend').hide();
			jQuery("#ListViewContents").mCustomScrollbar("update");
			jQuery("#ListViewContents").mCustomScrollbar("scrollTo",'-10');
		}
		reloadFolders();
		jQuery("#status").hide();
	
		ajax_enable = true;
		fninvsh('mode2Folder');
	});
}
//crmv@48159e

function appendListViewEntries_js(module,url,async)
{
	if (ajax_enable == false) return false;
	ajax_enable = false;
	
	jQuery('#nav_buttons').html('');
	jQuery('#indicatorAppend').show();
	jQuery('#appendNextListViewEntries').remove();

	var listdiv = jQuery("#ListViewContents");
	listdiv.mCustomScrollbar("scrollTo", "bottom");
	listdiv.mCustomScrollbar("disable");

	if (ajaxcall_list){
		ajaxcall_list.abort();
	}
	if (ajaxcall_count){
		ajaxcall_count.abort();
	}
    var viewid =getviewId();
    
	var urlstring = '';
	//var searching = false;
    if(isdefined('search_url')) {
		urlstring += jQuery('#search_url').val();
    }
    if (isdefined('selected_ids'))
    	urlstring += "&selected_ids=" + document.getElementById('selected_ids').value;
    if (isdefined('all_ids'))
    	urlstring += "&all_ids=" + document.getElementById('all_ids').value;
    if (isdefined('modulename'))
    	var modulename=document.getElementById('modulename').value;
    else
    	modulename = '';
    gstart = url;
    postbody = "module="+module+"&modulename="+modulename+"&action="+module+"Ajax&file=ListView&ajax=true&appendlist=yes&"+url+urlstring;
    if (async == undefined) {
    	async = true;
    }
    jQuery.ajax({
		url: 'index.php',
		type: 'POST',
		dataType: 'html',
		data: postbody,
		async: async,
		success: function(data){
			result = data.split('@#@#@#');
			jQuery("#nav_buttons").html(result[1]);
			//crmv@OPER8279
			if (result[2] != '') {
				alert(result[2]);
			} else {
				jQuery("#MessagesRowList").append(result[3]);
				jQuery("#ListViewContents").mCustomScrollbar("update");
			}
            //crmv@OPER8279e
           	jQuery('#indicatorAppend').hide();
           	//crmv@48307
           	if (list_status == 'edit') {
           		jQuery('input:checkbox[name="selected_id"]').show();
           	}
           	//crmv@48307e
           	
           	ajax_enable = true;
           	
           	// if no result in search auto search in next step
           	if (jQuery('#nav_buttons').find('#count_results_search_intervals').val() == 0 && jQuery('#nav_buttons').find('#navigation_search').val() == 1) {	//crmv@150593
           		continueMessagesSearch();
           	} else {
				jQuery('#adv-searchnow-btn').removeProp('disabled');   // crmv@183056
			}
		}
	});
}

function continueMessagesSearch() {
	if (jQuery('#button_continue_search').length == 0) return false;

	jQuery('#adv-searchnow-btn').prop('disabled','disabled');   // crmv@183056
	jQuery('#button_continue_search').prop('disabled','disabled');
	setTimeout(function(){
		jQuery('#button_continue_search').removeProp('disabled');
		jQuery('#button_continue_search').click();
	}, 1000);	// 1 sec
}

function saveDocument(record,contentid,linkto,linkto_module,reload_record) {
	jQuery("#status").show();
	VteJS_DialogBox.block();
	var params = 'module=Messages&action=MessagesAjax&file=SaveDocument&record='+record+'&contentid='+contentid;
	if (linkto != '' && linkto != 'undefined' && linkto != undefined) {
		params += '&linkto='+linkto+'&linkto_module='+linkto_module;
	}
	jQuery.ajax({
		url: 'index.php',
		type: 'POST',
		//async: false,	// crmv@42752 why??
		data: params,
		success: function(data, status, xhr) {
			if (data.indexOf('SUCCESS')>=0) {
				jQuery('#flag_'+record+'_relations').show();
				if (reload_record == 'yes') {
					selectRecord(record,false,true);
				}
			} else {
				//alert(alert_arr.LBL_RECORD_SAVE_ERROR);
			}
		},
		error: function() {
			//alert(alert_arr.LBL_RECORD_SAVE_ERROR);
		},
		complete: function() {
			jQuery("#status").hide();
			VteJS_DialogBox.unblock();
		},
	});
}

// crmv@42752
function saveDocumentAndLink(record,contentid) {
	LPOP.openPopup('Messages', record, 'linkdocument', {'contentid':contentid}); // crmv@43864
}
//crmv@42752e

function editViewFolders(show) {
	jQuery('input:radio[name="selected_folder"]:checked').prop('checked',false);
	if (show) {
		folders_status = 'edit';
		changeButtons('Folders','Edit','',true);
		jQuery('input:radio[name="selected_folder"]').show();
	} else {
		folders_status = 'view';
		changeButtons('Folders','','',true);
		jQuery('input:radio[name="selected_folder"]').hide();
	}
}

function folderAction(obj,action) {
	var selected_folder = jQuery('input:radio[name="selected_folder"]:checked').val();
	if (action != 'create') {
		if (selected_folder == '' || selected_folder == 'undefined' || selected_folder == undefined) {
			alert(alert_arr.LBL_SELECT_DEL_FOLDER);
			return false;
		}
	}
	switch(action) {
		case 'seen':
	  		var value = 0;
	  	case 'unseen':
	  		jQuery("#status").show();
		  	var value = 1;
		    jQuery.ajax({
				url: 'index.php?module=Messages&action=MessagesAjax&file=FolderFlag&faction='+action+'&account='+current_account+'&folder='+selected_folder,
				dataType: 'html',
				success: function(data) {
					jQuery("#status").hide();
					current_folder = '';	//forzo per ricaricare la lista
					editViewFolders(false);
					reloadFolders();
				}
			});
	  	break;
		case 'move':
	    	MoveDisplay(obj,'folders','',selected_folder);
	  	break;
	  	case 'create':
	    	CreateFolderDisplay(obj,selected_folder);
	  	break;
	}
}

function selectFolder(dim_folder,dim_list,foldername,folderlabel,selector) {
	if (folders_status == 'edit') {
		fninvsh('mode2Folder');
		if (jQuery("input:radio[id='check_"+selector+"']").prop('checked')) {
			jQuery("input:radio[id='check_"+selector+"']").prop('checked',false);
		} else {
			jQuery("input:radio[id='check_"+selector+"']").prop('checked',true);
		}
	} else {
		changeLeftView('list',dim_folder,dim_list,foldername,folderlabel);
		if (foldername != specialFolders['INBOX']) fetch(5000, 'yes');	//crmv@48471 //crmv@62821
	}
}

function selectINBOXFolder(dim_folder,dim_list,account,foldername,folderlabel,selector) {
	var isAccount = (parseInt(account) || 0) > 0; // crmv@192333
	if (current_account != account || !isAccount){
		current_folder = '';
		current_account = account;
	}
	selectFolder(dim_folder,dim_list,foldername,folderlabel,selector);
	changeButtons('ListView','',true);
}

function selectAccount(view,dim_folder,dim_list,name,label) {
	changeLeftView(view,dim_folder,dim_list,name,label);
}

function folderMove(folder) {
	jQuery("#status").show();
	var selected_folder = jQuery('input:radio[name="selected_folder"]:checked').val();
	jQuery.ajax({
		url: 'index.php?module=Messages&action=MessagesAjax&file=Move&folder='+folder+'&mode=folders&account='+current_account+'&current_folder='+encodeURIComponent(selected_folder), // crmv@180065
		dataType: 'html',
		success: function(data){
			jQuery("#status").hide();
			if (data.indexOf('FAILED')>=0) {
				alert(alert_arr.NOT_PERMITTED);
			} else if (data.indexOf('SUCCESS')>=0) {
				current_folder = '';	//forzo per ricaricare la lista
				editViewFolders(false);
				reloadFolders();
				fninvsh('mode2Folder');
			}
		}
	});
}

function CreateFolderDisplay(obj,selected_folder) {
	jQuery('#createFolder_list').html('');
	fnvshobj(obj,'createFolder');
	jQuery('#indicatorcreateFolder').show();
	var url = 'index.php?module=Messages&action=MessagesAjax&file=Move&view=create&mode=folders&account='+current_account+'&current_folder='+encodeURIComponent(selected_folder); // crmv@180065
	jQuery.ajax({
		url: url,
		dataType: 'html',
		success: function(data){
			jQuery('#indicatorcreateFolder').hide();
			jQuery('#createFolder_list').html(data);
			jQuery('#createFolder_list #foldername').focus();
			if (jQuery('input:radio[name="selected_folder_create"]:checked').length  == 0) {
				jQuery("input:radio[id='check_create_"+encodeURIComponent('/')+"']").prop('checked',true);
			}
		}
	});
}

function selectCreateFolder(foldername,selector) {
	if (jQuery("input:radio[id='check_create_"+selector+"']").prop('checked')) {
		jQuery("input:radio[id='check_create_"+selector+"']").prop('checked',false);
	} else {
		jQuery("input:radio[id='check_create_"+selector+"']").prop('checked',true);
	}
}

function CreateFolder() {
	if (!emptyCheck('foldername',jQuery.trim(alert_arr.LBL_FOLDER),getObj('foldername').type)) {
		return false;
	}
	var foldername = jQuery('#createFolder_list #foldername').val();
	var selected_folder = jQuery('input:radio[name="selected_folder_create"]:checked').val();
	if (selected_folder == '' || selected_folder == 'undefined' || selected_folder == undefined) {
		alert(alert_arr.LBL_SELECT_DEL_FOLDER);
		return false;
	}
	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php?module=Messages&action=MessagesAjax&file=Move&folder='+foldername+'&mode=create&account='+current_account+'&current_folder='+encodeURIComponent(selected_folder), // crmv@180065
		dataType: 'html',
		success: function(data){
			jQuery("#status").hide();
			if (data.indexOf('FAILED')>=0) {
				alert(alert_arr.NOT_PERMITTED);
			} else if (data.indexOf('SUCCESS')>=0) {
				current_folder = '';	//forzo per ricaricare la lista
				editViewFolders(false);
				reloadFolders();
				fninvsh('createFolder');
			}
		}
	});
}

//crmv@42801
function printPreview(id) {
	openPopup('index.php?module=Messages&action=MessagesAjax&file=Print&record='+id,document.title,"width=800,height=600","auto",800,600,"top",'nospinner');
}

function printMessage(id) {
	parent.frames[1].focus();
	parent.frames[1].print();
}

function downloadMessage(id) {
	location.href = 'index.php?module=Messages&action=MessagesAjax&file=DetailView&mode=Download&record='+id;
}
//crmv@42801e

//crmv@44775	crmv@44788
function detachMessage(id) {
	var params = "&account="+current_account+"&folder="+encodeURIComponent(current_folder); // crmv@180065
	var url = 'index.php?module=Messages&action=MessagesAjax&file=DetailView&mode=Detach&record='+id+params;
	window.open(url,'_blank');
}
//crmv@44775e	crmv@44788e

function cleanDiv(id) {
	jQuery('#'+id).html('');
	VteJS_DialogBox.progress(id,'light');
}

//crmv@59094 crmv@159110
function fetchBody(id) {
	if (ajax_enable == false) return false;
	ajax_enable = false;
	if (typeof(jQuery('#DetailViewContents').slimScroll) == 'function') {
		jQuery('#DetailViewContents').slimScroll({ scrollTo: '0px' });
		cleanDiv('DetailViewContents');
	}
	jQuery.ajax({
		url: 'index.php?module=Messages&action=MessagesAjax&file=FetchBody&record='+id,
		dataType: 'html',
		async: true,
		success: function(data){
			if (data.indexOf('SUCCESS::')>=0) {
				ajax_enable = true;
				selectRecord(id,true,true,true);

				var tmp = data.split('::');
				if (tmp[1] == 'ATTACHMENTS') {
					jQuery('#flag_'+id+'_attachments').show();
				}
			} else {
				alert(alert_arr.NOT_PERMITTED);
			}
			VteJS_DialogBox.hideprogress('DetailViewContents');
		}
	});
	fninvsh('mode2Folder');
}
//crmv@59094e crmv@159110e

//crmv@OPER8279
function fetchMessage(account, folder, xuid) {
	if (ajax_enable == false) return false;
	ajax_enable = false;

	jQuery('#DetailViewContents').slimScroll({ scrollTo: '0px' });
	cleanDiv('DetailViewContents');
	
	jQuery('#ListViewContents [id^="row_"]').removeClass('lvtColDataHoverMessage');
	jQuery('#row_xuid_'+xuid).addClass('lvtColDataHoverMessage');

	jQuery.ajax({
		url: 'index.php?module=Messages&action=MessagesAjax&file=DetailViewAjax&ajxaction=FETCHMESSAGE&account='+account+'&folder='+encodeURIComponent(folder)+'&xuid='+xuid, // crmv@180065
		dataType: 'html',
		success: function(data){
			if (data.indexOf('SUCCESS::')>=0) {
				ajax_enable = true;
				var tmp = data.split('::');
				reloadRow(tmp[1], xuid, function(){
					selectRecord(tmp[1]);
				});
				//VteJS_DialogBox.hideprogress('DetailViewContents');
			}
		}
	});
	fninvsh('mode2Folder');
}
function reloadRow(record,xuid,callback) {
	jQuery.ajax({
		url: 'index.php?module=Messages&action=MessagesAjax&file=DetailViewAjax&ajxaction=GETROW&record='+record,
		dataType: 'html',
		success: function(data){
			jQuery('#row_xuid_'+xuid).replaceWith(data);
			if (typeof callback == 'function') callback();
		}
	});
}
//crmv@OPER8279e

//crmv@62340
function ViewEML(messageid,contentid) {
	var params = "&account="+current_account+"&folder=Links";
	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php?module=Messages&action=MessagesAjax&file=ParseEML&record='+messageid+'&contentid='+contentid+params,
		dataType: 'json',
		success: function(data){
			jQuery("#status").hide();
			if (data.success == false) {
				alert(alert_arr.NOT_PERMITTED);
			} else {
				var eml_messageid = data.messageid;
				var url = 'index.php?module=Messages&action=MessagesAjax&file=DetailView&mode=Detach&record='+eml_messageid+params;
				window.open(url,'_blank');
			}
		}
	});
}
//crmv@62340e

//crmv@62414
function ViewDocument(messageid,contentid) {
	var params = "&account="+current_account+"&folder="+current_folder;
	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php?module=Messages&action=MessagesAjax&file=ViewDocument&record='+messageid+'&contentid='+contentid+params,
		dataType: 'json',
		async: false,
		success: function(data){
			jQuery("#status").hide();
			if (data.success == false) {
				alert(alert_arr.NOT_PERMITTED);
			} else {
				var savepath = data.savepath;
				var url = 'index.php?module=Messages&action=MessagesAjax&file=src/ViewerJS/index&requestedfile='+savepath;
				window.open(url,'_blank');
			}
		}
	});
}

function ViewImage(messageid,contentid) {
	var params = "&account="+current_account+"&folder="+current_folder;
	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php?module=Messages&action=MessagesAjax&file=ViewImage&record='+messageid+'&contentid='+contentid+params,
		dataType: 'json',
		async: false,
		success: function(data){
			jQuery("#status").hide();
			if (data.success == false) {
				alert(alert_arr.NOT_PERMITTED);
			} else {
				var savepath = data.savepath;
				var width = data.width + 20; //add some px
				var height = data.height + 20; //add some px
				var pagewidth = window.innerWidth || document.body.clientWidth;
				var pageheight = window.innerHeight || document.body.clientHeight;
				if(width > pagewidth || height > pageheight){
					width = false;
					height = false;
				}
				var url = 'index.php?module=Messages&action=MessagesAjax&file=ImageViewer&requestedfile='+savepath;
				openPopup(url,document.title,"width=800,height=600","auto",width,height,"",'nospinner'); // crmv@166949
			}
		}
	});
}
//crmv@62414e

//crmv@187622
function sendNow(id) {
	var mainPage = self;
	if (messageMode == 'Detach') mainPage = window.opener;
	
	jQuery.ajax({
		url: 'index.php?module=Emails&action=EmailsAjax&file=ScheduleSendingAjax&ajxaction=SENDNOW&record='+id,
		success: function(data){
			var result = data.split('::');
			if (result[0] == 'SUCCESS') {
				vtealert(result[1],null,{"autoclose":2});
				
				mainPage.jQuery('#row_'+id).remove();
				mainPage.resetDetailView();
				mainPage.selectLuckyMessage(current_folder,id);
				if (messageMode == 'Detach') window.close();
				
				mainPage.reloadFolders();
			}
		}
	});
}
// crmv@187622e

// crmv@68357
var Messages_iCal = {
	
	busy: false,
	
	showBusy: function() {
		this.busy = true;
		ajax_enable = false;
		// crmv@184713
		if (jQuery("#status").length > 0) {
			jQuery("#status").show();
		} else {
			VteJS_DialogBox.progress();
		}
		// crmv@184713e
	},
	
	hideBusy: function() {
		this.busy = false;
		ajax_enable = true;
		// crmv@184713
		if (jQuery("#status").length > 0) {
			jQuery("#status").hide();
		} else {
			VteJS_DialogBox.hideprogress();
		}
		// crmv@184713e
	},
	
	ajaxCall: function(action, params, options, callback) {
		var me = this;
		
		// return if busy
		if (me.busy) return;
		if (window.ajax_enable && !ajax_enable) return; // crmv@184713
		
		options = jQuery.extend({
			// default values
			jsonData: true,
			callbackOnError: false,
		}, options || {});
		
		params = params || {};
		
		var url = "index.php?module=Messages&action=MessagesAjax&file=IcalAjax&subaction="+action;
		me.showBusy();
		jQuery.ajax({
			url: url,
			type: 'POST',
			async: true,
			data: params,
			success: function(data) {
				me.hideBusy();
				//if (options.hidePopupMessage) me.hidePopupMessage();
				if (options.jsonData) {
					// data should be json with a success property
					try {
						data = JSON.parse(data);
					} catch (e) {
						data = null;
					}
					if (data && data.success) {
						if (typeof callback == 'function') callback(data);
					} else if (data && data.error) {
						alert(data.error);
					} else {
						console.log('Unknown error');
						console.log(data);
					}
				} else {
					if (typeof callback == 'function') callback(data);
				}
			},
			error: function() {
				me.hideBusy();
				if (options.callbackOnError) {
					if (typeof callback == 'function') callback();
				}
			}
		});
		
	},
	
	replyYes: function(messageid, icalid) {
		var me = this;
		var activityid = parseInt(jQuery('#ical_'+icalid+'_activityid').val());
		
		me.ajaxCall('ReplyYes', {
			messageid: messageid,
			icalid: icalid,
		}, null, function(data) {
			if (data && data.result && data.result.activityid > 0) {
				jQuery('#ical_'+icalid+'_activityid').val(data.result.activityid);
			}
			jQuery('#ical_'+icalid+'_button_yes').addClass('green');
			jQuery('#ical_'+icalid+'_button_no').removeClass('red');
			jQuery('#ical_'+icalid+'_button_preview').hide();
			alert(alert_arr.ANSWER_SENT);
			populateTurbolift(messageid, true);
		});
	},
	
	replyNo: function(messageid, icalid) {
		var me = this;
		var activityid = parseInt(jQuery('#ical_'+icalid+'_activityid').val());
		
		var params = {
			messageid: messageid,
			icalid: icalid,
		}
		if (activityid > 0) {
			if (confirm(alert_arr.CONFIRM_LINKED_EVENT_DELETION)) {
				params['del_event'] = 1;
			}
		}
		
		me.ajaxCall('ReplyNo', params, null, function() {
			jQuery('#ical_'+icalid+'_button_yes').removeClass('green');
			jQuery('#ical_'+icalid+'_button_no').addClass('red');
			alert(alert_arr.ANSWER_SENT);
			if (params['del_event']) {
				populateTurbolift(messageid, true);
				jQuery('#ical_'+icalid+'_activityid').val(0);
			}
		});
	},
	
	// crmv@81126
	preview: function(messageid, icalid, activityid, is_update) { // crmv@189405
		var me = this;
		LPOP.openEventCreate('Messages', messageid, 'Events', {useical: true, icalid: icalid, activityid: activityid, is_update: !!is_update}); // crmv@189405
	},
	// crmv@81126e
	
	// crmv@184713
	previewReplyYes: function(messageid, icalid) {
		var me = this;
		if (parent.Messages_iCal) {
			closePopup();
			parent.Messages_iCal.replyYes(messageid, icalid);
		}
	},

	previewReplyNo: function(messageid, icalid) {
		var me = this;
		if (parent.Messages_iCal) {
			closePopup();
			parent.Messages_iCal.replyNo(messageid, icalid);
		}
	}
	// crmv@184713e
}
// crmv@68357e