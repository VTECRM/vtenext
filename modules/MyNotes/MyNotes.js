/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@168573

window.VTE = window.VTE || {};

window.VTE.MyNotesWidget = window.VTE.MyNotesWidget || {

	busy: false,

	noteList: [],
	
	parentRecord: 0,
	
	maxSubjectLength: 50, // Default
	
	currentView: null,
	
	permissions: {},
	
	createViewOpen: false,
	
	labels: {},
	
	init: function() {
		var me = this;

		me.currentView = 'carousel';
		
		me.initEvents();
		me.initComponents();
		
		me.subjectFieldEdited = false;
	},
	
	initComponents: function() {
		var me = this;
		
		me.switchCreateButton('create');
		me.switchView(me.currentView);
	},
	
	initEvents: function() {
		var me = this;
		
		jQuery('.create-note').on('click', me.handleCreateNote);
		jQuery('.save-note').on('click', me.handleSaveNote);
		jQuery('.switch-carousel-button').on('click', me.handleSwitchCarouselButton);
		jQuery('.switch-list-button').on('click', me.handleSwitchListButton);
		jQuery('.close-create-view').on('click', me.handleCloseCreateView);
		
		jQuery('.notes-subject-field').on('keyup', me.handleSubjectField);
		jQuery('.notes-subject-field').on('change', me.handleSubjectField);
		
		jQuery('.notes-description-field').on('keyup', me.handleDescriptionField);
		jQuery('.notes-description-field').on('change', me.handleDescriptionField);
	},
	
	permRead: function() {
		return this.isPermitted('read');
	},
	
	permWrite: function() {
		return this.isPermitted('write');
	},
	
	permDelete: function() {
		return this.isPermitted('delete');
	},
	
	isPermitted: function(mode) {
		return this.permissions && this.permissions[mode];
	},
	
	handleCreateNote: function(e) {
		var me = window.VTE.MyNotesWidget,
			element = this,
			$element = jQuery(element);
		
		me.openCreateView();
	},
	
	handleSaveNote: function(e) {
		var me = window.VTE.MyNotesWidget,
			element = this,
			$element = jQuery(element);
		
		me.saveNote();
	},
	
	handleSwitchCarouselButton: function(e) {
		var me = window.VTE.MyNotesWidget,
			element = this,
			$element = jQuery(element);
		
		me.switchView('carousel');
	},
	
	handleSwitchListButton: function(e) {
		var me = window.VTE.MyNotesWidget,
			element = this,
			$element = jQuery(element);
		
		me.switchView('list');
	},
	
	handleCloseCreateView: function(e) {
		var me = window.VTE.MyNotesWidget,
			element = this,
			$element = jQuery(element);
		
		me.closeCreateView();
	},
	
	handleDeleteNote: function(e) {
		var me = window.VTE.MyNotesWidget,
			element = this,
			$element = jQuery(element);
		
		var noteElement = $element.closest('.note-element'),
			noteId = noteElement.data('noteId'),
			parentId = me.parentRecord;
		
		me.deleteNote(noteId, parentId);
	},
	
	handleLinkNote: function(e) {
		var me = window.VTE.MyNotesWidget,
			element = this,
			$element = jQuery(element);
		
		var noteElement = $element.closest('.note-element'),
			noteId = noteElement.data('noteId'),
			parentId = me.parentRecord;
		
		me.linkNote(noteId, parentId);
	},
	
	handleSubjectField: function(e) {
		var me = window.VTE.MyNotesWidget,
			element = this,
			$element = jQuery(element);
		
		if (e.type === 'keyup') {
			me.editSubjectField();
		} else if (e.type === 'change') {
			me.editSubjectField();
		}
	},
	
	handleDescriptionField: function(e) {
		var me = window.VTE.MyNotesWidget,
			element = this,
			$element = jQuery(element);
		
		if (e.type === 'keyup') {
			me.updateSubjectField();
		} else if (e.type === 'change') {
			me.updateSubjectField();
		}
	},
	
	handleNoteEdit: function(e) {
		var me = window.VTE.MyNotesWidget,
			element = this,
			$element = jQuery(element);
		
		var noteElement = $element.closest('.note-element'),
			noteId = noteElement.data('noteId'),
			parentId = me.parentRecord;
		
		me.editNote(noteId, parentId);
	},
	
	populateNotes: function() {
		var me = this,
			notes = me.noteList || {},
			carouselContainer = jQuery('.notes-slider .carousel-inner'),
			indicators = jQuery('.notes-slider .carousel-indicators'),
			listContainer = jQuery('.notes-list'),
			viewContainer = null;
			
		var populated = false;
		
		if (me.reload) {
			carouselContainer.empty();
			indicators.empty();
			listContainer.empty();
			me.reload = false;
		}

		if (notes.length > 0) {
			if (me.currentView === 'carousel') {
				viewContainer = carouselContainer;
			} else if (me.currentView === 'list') {
				viewContainer = listContainer;
			}
			
			var children = viewContainer.children();
			
			if (children.length === 0) {
				viewContainer.empty();
				
				if (viewContainer.length > 0) {
					jQuery.each(notes, function(idx, note) {
						var element = me.createNoteElement(note, idx);
						element.appendTo(viewContainer);
						
						if (me.currentView === 'carousel') {
							var indicator = me.createCarouselIndicator(idx);
							indicator.appendTo(indicators);
						}
					});
					
					jQuery('i[data-toggle="tooltip"]').tooltip({ animation: false });
				}
			}
			
			populated = true;
		}
		
		return populated;
	},
	
	createNoteElement: function(note, idx) {
		var me = this,
			tpl = jQuery('#note-element-tpl');
		
		var element = tpl.clone();
		
		element.data('noteId', note['record_id']);
		element.attr('note-id', note['record_id']);
		element.attr('id', 'note-' + note['record_id']);
		element.find('.note-element-title').text(note['subject']);
		element.find('.note-element-description').html(note['html_description']);
		element.find('.note-element-picture img').attr('src', note['assigned_user_avatar']);
		element.find('.note-element-picture img').attr('alt', note['assigned_user_formatted']);
		element.find('.note-element-picture img').attr('title', note['assigned_user_formatted']);
		element.find('.note-element-user').text(note['assigned_user_formatted']);
		element.find('.note-element-timestamp').text(note['created_timestamp_ago']);
		element.find('.note-element-timestamp').attr('title', note['created_timestamp']);
		
		if (note['editable']) {
			element.find('.note-element-title').on('dblclick', me.handleNoteEdit);
			element.find('.note-element-description').on('dblclick', me.handleNoteEdit);
			
			element.find('.edit-note').removeClass('hidden');
			element.find('.edit-note').on('click', me.handleNoteEdit);
			
			element.find('.link-note').removeClass('hidden');
		}
		
		if (note['deletable']) {
			element.find('.delete-note').removeClass('hidden');
			element.find('.delete-note').on('click', me.handleDeleteNote);
		}
		
		element.find('.link-note').on('click', me.handleLinkNote);
		
		element.show();
		
		if (me.currentView === 'carousel') {
			element = element.wrap('<div class="item"></div>').parent();
		}
		
		return element;
	},
	
	createCarouselIndicator: function(idx) {
		var me = this,
			carousel = jQuery('.notes-slider');
		
		var indicator = jQuery('<li></li>');
		
		indicator.on('click', function(e) {
			carousel.carousel(idx);
		});
		
		return indicator;
	},
	
	showBusy: function() {
		var me = this;
		me.busy = true;
		jQuery('.notes-loader').removeClass('hidden');
	},
	
	hideBusy: function() {
		var me = this;
		me.busy = false;
		jQuery('.notes-loader').addClass('hidden');
	},
	
	switchView: function(view) {
		var me = this;
		
		if (me.createViewOpen) return;
		
		me.currentView = view;
		
		if (view === 'list') {
			me.switchListView();
		} else if (view === 'carousel') {
			me.switchCarouselView();
		}
		
		me.switchActiveButton();
		me.updateNotesTitle();
	},
	
	switchListView: function() {
		var me = this,
			emptyList = jQuery('.empty-notes'),
			list = jQuery('.notes-list'),
			carousel = jQuery('.notes-slider');
		
		var populated = me.populateNotes();
		
		if (populated) {
			emptyList.addClass('hidden');
			carousel.addClass('hidden');
			list.removeClass('hidden');
		} else {
			emptyList.removeClass('hidden');
			carousel.addClass('hidden');
			list.addClass('hidden');
		}
	},
	
	switchCarouselView: function() {
		var me = this,
			emptyList = jQuery('.empty-notes'),
			list = jQuery('.notes-list'),
			carousel = jQuery('.notes-slider'),
			indicators = jQuery('.notes-slider .carousel-indicators');
		
		var populated = me.populateNotes();
		
		if (populated) {
			emptyList.addClass('hidden');
			carousel.removeClass('hidden');
			list.addClass('hidden');
			
			if (!me.carouselInitilized) {
				jQuery('.carousel').carousel({
					interval: false, 
				});
				
				jQuery('.carousel-control.left').on('click', function(e) {
					e.preventDefault();
					jQuery('.carousel').carousel('prev');
				});
				
				jQuery('.carousel-control.right').on('click', function(e) {
					e.preventDefault();
					jQuery('.carousel').carousel('next');
				});
				
				me.carouselInitilized = true;
			}
			
			var activeItem = carousel.find('.item.active');
			if (activeItem.length === 0) {
				carousel.find('.item').first().addClass('active');
				indicators.find('li').first().addClass('active');
				jQuery('.carousel').carousel(0);
			}
		} else {
			emptyList.removeClass('hidden');
			carousel.addClass('hidden');
			list.addClass('hidden');
		}
	},
	
	switchActiveButton: function() {
		var me = this,
			view = me.currentView,
			button = me.getSwitchButton(view);
		
		jQuery('.switch-view').removeClass('active');
		button.addClass('active');
	},
	
	updateNotesTitle: function() {
		var me = this,
			view = me.currentView,
			$title = jQuery('.notes-header-title .title');
		
		var title = null;
		
		if (view === 'list') {
			title = me.labels.list;
		} else if (view === 'carousel') {
			title = me.labels.slider;
		}
		
		if (title !== null) {
			$title.html(me.labels.notes + ' - ' + title);
		}
	},
	
	getSwitchButton: function(view) {
		var me = this;
		
		var button = null;
		
		if (view === 'list') {
			button = jQuery('.switch-list-button');
		} else if (view === 'carousel') {
			button = jQuery('.switch-carousel-button');
		}
		
		return button;
	},
	
	openCreateView: function() {
		var me = this;
		
		if (me.createViewOpen) return;
		
		me.createViewOpen = true;
		
		jQuery('.notes-create-view').removeClass('hidden');
		
		jQuery('.notes-create-view').animate({
			'top': '0px',
		}, 'fast', function() {
			jQuery('.notes-description-field').focus();
		});
		
		me.switchCreateButton('save');
	},
	
	closeCreateView: function() {
		var me = this;
		
		if (!me.createViewOpen) return;
		
		me.createViewOpen = false;
		
		jQuery('.notes-create-view').animate({
			'top': '100%',
		}, 'fast', function() {
			jQuery('.notes-create-view').addClass('hidden');
		});
		
		me.switchCreateButton('create');
		
		if (me.editMode) {
			me.resetFields();
			me.editMode = false;
		}
	},
	
	switchCreateButton: function(mode) {
		var me = this,
			permWrite = me.permWrite(),
			createButton = jQuery('.create-note'),
			saveButton = jQuery('.save-note');
		
		if (permWrite) {
			if (mode === 'create') {
				createButton.removeClass('hidden');
				saveButton.addClass('hidden');
			} else if (mode === 'save') {
				createButton.addClass('hidden');
				saveButton.removeClass('hidden');
			}
		} else {
			createButton.addClass('hidden');
			saveButton.addClass('hidden');
		}
	},
	
	saveNote: function() {
		var me = this,
			form = jQuery('#notes-create-form'),
			descriptionElem = jQuery('.notes-description-field'),
			description = descriptionElem.val();
		
		if (!description.length) {
			var fieldLabel = descriptionElem.attr('placeholder');
			vtealert(sprintf(alert_arr.CANNOT_BE_EMPTY, fieldLabel), function() {
				descriptionElem.focus();
			});
			return false;
		}
		
		if (me.busy) return;
		
		me.showBusy();

		if (!SessionValidator.check()) {
			SessionValidator.showLogin();
			return false;
		}

		var sdkValidate = SDKValidate(form.get(0));
		if (sdkValidate) {
			var sdkValidateResponse = eval('('+sdkValidate.responseText+')');
			if (!sdkValidateResponse['status']) {
				return false;
			}
		}
		
		var data = form.serializeArray();
		
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: data,
			dataType: 'json',
			success: function(res) {
				me.hideBusy();
				if (res && res.success !== 'true') {
					alert(alert_arr.ERROR);
				} else {
					me.reloadWidget();
				}
			},
			error: function() {
				me.hideBusy();
				alert(alert_arr.ERROR);
			},
		});
	},
	
	reloadWidget: function() {
		var me = this;
		
		me.syncNotes(function(res) {
			if (res && res.list) {
				me.closeCreateView();
				me.resetFields();
				
				me.noteList = res.list;
				me.reload = true;
				me.switchView(me.currentView);
			}
		});
	},
	
	syncNotes: function(callback) {
		var me = this;
		
		if (me.busy) return;
		
		me.showBusy();
		
		jQuery.ajax({
			url: 'index.php?module=MyNotes&action=MyNotesAjax&file=DetailViewAjax&ajxaction=LOADLIST&parent='+me.parentRecord,
			method: 'POST',
			dataType: 'json',
			success: function(res) {
				me.hideBusy();
				if (typeof callback === 'function') {
					callback(res);
				}
			},
			error: function() {
				me.hideBusy();
				alert(alert_arr.ERROR);
			},
		});
	},
	
	deleteNote: function(noteId, parentId) {
		var me = this;
		
		if (me.busy) return;
		
		vteconfirm(me.labels.delete_confirm_message, function(yes) {
			if (yes) {
				me.showBusy();
				
				jQuery.ajax({
					url: 'index.php?module=MyNotes&action=MyNotesAjax&action=Delete&record='+noteId+'&parent='+parentId+'&mode=DetailViewMyNotesWidget',
					method: 'POST',
					dataType: 'json',
					success: function(res) {
						me.hideBusy();
						if (res && res.success === 'true') {
							me.reloadWidget();
						}
					},
					error: function() {
						me.hideBusy();
						alert(alert_arr.ERROR);
					},
				});
			}
		});
		
		return false;
	},
	
	linkNote: function(noteId, parentId) {
		var me = this;
		
		LPOP.openPopup('MyNotes', noteId, 'onlycreate', {
			'callback_create': 'LPOP.convert'
		});
	},
	
	editNote: function(noteId, parentId) {
		var me = this,
			$subject = jQuery('.notes-subject-field'),
			$description = jQuery('.notes-description-field');
		
		var note = me.findNoteById(noteId);
		if (note === null) return false;
		
		me.editMode = true;
		
		me.openCreateView();
		
		$subject.val(note['subject']);
		me.subjectFieldEdited = true;
		
		$description.val(note['description']);
		
		jQuery('#notes-create-form').find('input[name="record"]').val(noteId);
		jQuery('#notes-create-form').find('input[name="mode"]').val('edit');
	},
	
	editSubjectField: function() {
		var me = this,
			$subject = jQuery('.notes-subject-field'),
			subject = $subject.val();
		
		me.subjectFieldEdited = true;
		
		if (subject.length === 0) {
			me.subjectFieldEdited = false;
		}
		
		if (subject.length > me.maxSubjectLength) {
			$subject.val(subject.substring(0, me.maxSubjectLength));
		}
	},
	
	updateSubjectField: function() {
		var me = this,
			$subject = jQuery('.notes-subject-field'),
			$description = jQuery('.notes-description-field'),
			description = $description.val();
		
		if (!me.subjectFieldEdited) {
			$subject.val(description.substring(0, me.maxSubjectLength));
		}
	},
	
	resetFields: function() {
		var me = this,
			$subject = jQuery('.notes-subject-field'),
			$description = jQuery('.notes-description-field');
		
		$subject.val('');
		me.subjectFieldEdited = false;
		
		$description.val('');
		
		jQuery('#notes-create-form').find('input[name="record"]').val('');
		jQuery('#notes-create-form').find('input[name="mode"]').val('');
	},
	
	findNoteById: function(noteId) {
		var me = this,
			notes = me.noteList;
		
		var record = null;
		
		jQuery.each(notes, function(idx, note) {
			if (note['record_id'] === noteId) {
				record = note;
				return false;
			}
		});
		
		return record;
	},
	
};

if (typeof(MyNotesSV) == 'undefined') {

	MyNotesSV = {
		
		select: function(listid,module,crmid,entityname) {
	
			if (window.itsonview) itsonview = false;	// crmv@180166
			jQuery('#ListViewContents [id^="row_"]').removeClass('lvtColDataHoverMessage');
			jQuery('#row_'+crmid).addClass('lvtColDataHoverMessage');
			
			MyNotesSV.detailView(module,crmid);
		},
		detailView: function(module,crmid) {
	
			jQuery('.editviewbutton').hide();
			VteJS_DialogBox.progress('DetailViewContents','light');
			
			jQuery.ajax({
				url: 'index.php?module='+module+'&action=DetailView&mode=SimpleView&record='+crmid,
				success: function(data){
					jQuery('#DetailViewContents').html(data);
					
					// crmv@104853 // crmv@172994
					var bodyH = parseInt(jQuery('body').height());
					var buttonsListH = parseInt(jQuery('#Buttons_List').outerHeight(true));
					
					//crmv@54072	crmv@55694
					var height = bodyH-buttonsListH-220;
					
					var description = jQuery('form[name="DetailView"] [name="description"]');
					description.parent().parent().height(height);
					description.parent().parent().css('overflow-y','auto');
					description.addClass('dvtCellInfoM');
					//crmv@54072e	crmv@55694e
					// crmv@104853e // crmv@172994e
					
					VteJS_DialogBox.hideprogress('DetailViewContents');
					jQuery('.detailviewbutton').show();
					
					MyNotesSV.registerPanelBlocker(); // crmv@171115
				}
			});
		},
		create: function(module) {
		
			jQuery('#ListViewContents [id^="row_"]').removeClass('lvtColDataHoverMessage');
		
			jQuery('.detailviewbutton').hide();
			VteJS_DialogBox.progress();
			
			jQuery.ajax({
				url: 'index.php?module='+module+'&action='+module+'Ajax&file=EditView&hide_button_list=1',
				success: function(data){
					jQuery('#DetailViewContents').html(data);
					
					jQuery('form[name="EditView"]').attr('onsubmit','');
					jQuery('form[name="EditView"]').submit(function() {
						jQuery('#saveNoteButton').click();
						return false;
					});
					
					// crmv@104853 // crmv@172994
					var bodyH = parseInt(jQuery('body').height());
					var buttonsListH = parseInt(jQuery('#Buttons_List').outerHeight(true));
					var emptyHeight = bodyH-buttonsListH-220;
					// crmv@104853e // crmv@172994e
					
					jQuery('form[name="EditView"] [name="description"]').height(emptyHeight);
					jQuery('form[name="EditView"] [name="description"]').focus();
					
					VteJS_DialogBox.hideprogress();
					jQuery('.editviewbutton').show();
					
					MyNotesSV.registerPanelBlocker(); // crmv@171115
				}
			});
		},
		// crmv@97430
		getListId: function() {
			var list = jQuery('#ListViewContents').find('div[id^=SLVContainer_]');
			if (list.length > 0) {
				return parseInt(list.get(0).id.replace('SLVContainer_', ''));
			}
			return null;
		},
		save: function(module) {
			var me = this;
			
			//crmv@91082
			if(!SessionValidator.check()) {
				SessionValidator.showLogin();
				return false;
			}
			//crmv@91082e
			
			var form = document.forms['EditView'];
			form.action.value='Save';
			
			var valid = formValidate(form);
			if (!valid) return;
			
			VteJS_DialogBox.progress();
			
			jQuery.ajax({
				url: jQuery(form).attr('action'),
				data: jQuery(form).serialize()+'&mode=SimpleView',
				dataType: 'json',
				type: 'POST',
				success: function(data, status, xhr) {
					VteJS_DialogBox.hideprogress();
					if (data['success'] != 'true') {
						alert(alert_arr.ERROR);
					} else {
						crmid = data['record'];
						MyNotesSV.detailView(module,crmid);
						
						var listid = me.getListId();
						if (listid > 0) {
							SLV.clear_search(listid);
							SLV.search(listid);
						}
					}
					
					me.registerPanelBlocker(); // crmv@171115
				},
				error: function() {
					VteJS_DialogBox.hideprogress();
					alert(alert_arr.ERROR);
				}
			});
		},
		delete: function(module,formname,action,confirmationMsg) {
			var me = this;
			
			if (confirm(confirmationMsg)) {
				
				var form = document.forms[formname];
				form.action.value=action;
			
				VteJS_DialogBox.progress();
				
				jQuery.ajax({
					url: jQuery(form).attr('action'),
					data: jQuery(form).serialize()+'&mode=SimpleView',
					type: 'POST',
					complete: function() {
						VteJS_DialogBox.hideprogress();
					},
					success: function(data, status, xhr) {
						jQuery('#DetailViewContents').html('');
						jQuery('.editviewbutton').hide();
						jQuery('.detailviewbutton').hide();
						
						var listid = me.getListId();
						if (listid > 0) {
							SLV.clear_search(listid);
							SLV.search(listid);
						}
					},
					error: function() {
						alert(alert_arr.ERROR);
					}
				});
			}
			return false;
		},
		// crmv@97430e
		
		// crmv@171115
		registerPanelBlocker: function() {
			var me = this;
			
			var selectors = [
				'#description', // Description field
				'#txtbox_Note', // Description field (DetailView)
			];
			
			VTE.registerPanelBlocker('MyNotes', selectors);
		},
		// crmv@171115e
		
	};
}

// crmv@192033
if (typeof(MyNotesDVW) == 'undefined') {

	MyNotesDVW = {
	
		load: function(id,parent) {
			jQuery("#vtbusy_info").show();
			//crmv@115268
			if (jQuery('#mynotes_mode').length > 0) var mynotes_mode = jQuery('#mynotes_mode').val(); else var mynotes_mode = 'DetailViewMyNotesWidget';
			window.parent.document.getElementById('frameDetailViewMyNotesWidget').src = 'index.php?module=MyNotes&action=DetailView&mode='+mynotes_mode+'&record='+id+'&parent='+parent;
			//crmv@115268e
		},
		create: function(parent) {
			jQuery("#vtbusy_info").show();
			window.parent.document.getElementById('frameDetailViewMyNotesWidget').src = 'index.php?module=MyNotes&action=MyNotesAjax&file=widgets/create&parent='+parent;
		},
		save: function(parent) {
			
			//crmv@91082
			if(!SessionValidator.check()) {
				SessionValidator.showLogin();
				return false;
			}
			//crmv@91082e
	
			var form = document.forms['EditView'];
			form.action.value='Save';
			
			var valid = formValidate(form);
			if (!valid) return;
			
			jQuery("#vtbusy_info").show();
			
			jQuery.ajax({
				url: jQuery(form).attr('action'),
				data: jQuery(form).serialize()+'&mode=DetailViewMyNotesWidget&parent='+parent,
				dataType: 'json',
				type: 'POST',
				success: function(data, status, xhr) {
					if (data['success'] != 'true') {
						jQuery("#vtbusy_info").hide();
						alert(alert_arr.ERROR);
					} else {
						MyNotesDVW.load(data['record'],data['parent']);
					}
				},
				error: function() {
					jQuery("#vtbusy_info").hide();
					alert(alert_arr.ERROR);
				}
			});
		},
		delete: function(id,parent,confirmationMsg) {
			if (confirm(confirmationMsg)) {
				jQuery("#vtbusy_info").show();
				jQuery.ajax({
					url: 'index.php?module=MyNotes&action=MyNotesAjax&action=Delete&record='+id+'&parent='+parent+'&mode=DetailViewMyNotesWidget',
					dataType: 'json',
					complete: function() {
						jQuery("#vtbusy_info").hide();
					},
					success: function(data, status, xhr) {
						if (data['success'] == 'true') {
							if (data['record'] == '' || data['record'] == null)
								MyNotesDVW.create(data['parent']);
							else
								MyNotesDVW.load(data['record'],data['parent']);
						}
					},
					error: function() {
						alert(alert_arr.ERROR);
					}
				});
			}
			return false;
		}
	};
}
// crmv@192033e