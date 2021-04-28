/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@176893

window.DropAreaForm = window.DropAreaForm || {
		
	initialize: function() {
		this.initializeEvents();	
	},
	
	initializeEvents: function() {
		var me = this;
		
		var availableFolders = me.availableFolders;
		
		jQuery('#doc_folder')
		.focus(function() {
			var term = this.value;
			if (term.length == 0 || this.value == DropAreaForm.emptyString) {
				this.placeholder = '';
			}
		})
		.blur(function() {
			var term = this.value;
			if (term.length == 0) {
				this.placeholder = DropAreaForm.emptyString;
			}
		})
		.autocomplete({
			minLength: 0,
			source: availableFolders,
			appendTo: jQuery('.droparea-form'),
			open: function() {
				if (window.findZMax) {
					var zmax = findZMax()+1;
					jQuery(this).autocomplete('widget').css('z-index', zmax);
				}
				return false;
			},
			select: function(event, ui) {
				DropAreaForm.setDocFolder(ui.item.id, ui.item.label);
				DropAreaForm.enableAutocompleteField('doc_folder', false);
				return false;
			}
		});
		
		me.setDocFolder(availableFolders[0]['id'], availableFolders[0]['value']);
		me.enableAutocompleteField('doc_folder', false);
		
		jQuery('#list-doc-folders').on('click', function() {
			DropAreaForm.toggleAutocompleteList('doc_folder');
		});
		
		jQuery('#reset-doc-folders').on('click', function() {
			DropAreaForm.resetAutocompleteField('doc_folder');
		});
		
		jQuery('#add-doc-folder').on('click', function() {
			DropAreaForm.toggleAutocompleteField('doc_folder', false);
		});
		
		jQuery('#add-doc-folder').tooltip();
		
		jQuery('.add-folder-menu > .add-folder').on('click', function(e) {
			e.stopPropagation();
		});
		
		jQuery('.add-folder-dropdown').on('show.bs.dropdown', function() {
			setTimeout(function() {
				jQuery('.add-folder-dropdown').find('input').first().focus();
			}, 100);
		});
		
		jQuery('.save-add-folder').on('click', function() {
			DropAreaForm.saveFolderHandler();
		});
		
		var availableUsers = me.availableUsers;
		var availableGroups = me.availableGroups;
		
		jQuery('#assigned_user')
		.focus(function() {
			var term = this.value;
			if (term.length == 0 || this.value == DropAreaForm.emptyString) {
				this.placeholder = '';
			}
		})
		.blur(function() {
			var term = this.value;
			if (term.length == 0) {
				this.placeholder = DropAreaForm.emptyString;
			}
		})
		.autocomplete({
			minLength: 0,
			source: availableUsers,
			appendTo: jQuery('.droparea-form'),
			open: function() {
				if (window.findZMax) {
					var zmax = findZMax()+1;
					jQuery(this).autocomplete('widget').css('z-index', zmax);
				}
				return false;
			},
			select: function(event, ui) {
				DropAreaForm.setAssignee(ui.item.id, ui.item.label);
				DropAreaForm.enableAutocompleteField('assigned_user', false);
				return false;
			}
		});
		
		if (window.current_user) {
			var currentUser = availableUsers.filter(function(u) {
				return u.id === current_user.id;
			});
			if (currentUser.length > 0) {
				me.setAssignee(currentUser[0].id, currentUser[0].value);
				me.enableAutocompleteField('assigned_user', false);
			}
		}
		
		jQuery('#list-assignee').on('click', function() {
			DropAreaForm.toggleAutocompleteList('assigned_user');
		});
		
		jQuery('#reset-assignee').on('click', function() {
			DropAreaForm.resetAutocompleteField('assigned_user');
		});
		
		jQuery('.assignee-type .dropdown-item').on('click', DropAreaForm.changeAssigneeTypeHandler);
	},
	
	reloadFolders: function() {
		var url = 'index.php?module=Documents&action=DocumentsAjax&file=DropAreaFormAjax';
		
		var payload = {
			'subaction': 'get_folders',
		};
		
		jQuery('#add-doc-folder').addClass('hidden');
		jQuery('#add-doc-folder-loader').removeClass('hidden');
		
		jQuery.ajax({
			url: url,
			data: payload,
			type: 'GET',
			dataType: 'json',
			success: function(res) {
				jQuery('#add-doc-folder').removeClass('hidden');
				jQuery('#add-doc-folder-loader').addClass('hidden');
				if (res && res.success) {
					jQuery('#doc_folder').autocomplete('option', 'source', res.data);
				} else {
					console.log('Ajax error: ' + res.error);
				}
			},
			error: function() {
				jQuery('#add-doc-folder').removeClass('hidden');
				jQuery('#add-doc-folder-loader').addClass('hidden');
				console.log('Ajax error');
			}
		});
	},
	
	saveFolderHandler: function() {
		DropAreaForm.saveFolder();
	},
	
	saveFolder: function() {
		var me = this;
		var url = 'index.php?module=Documents&action=DocumentsAjax&file=DropAreaFormAjax';
		
		var formData = {};
		jQuery.each(jQuery('#saveDocumentForm').serializeArray(), function() {
			var name = this.name.replace("[]", "");
			if (formData.hasOwnProperty(name)) {
				if (!formData[name].push) {
					formData[name] = [formData[name]];
                }
				formData[name].push(this.value || '');
			} else {
				formData[name] = this.value || '';
			}
		});
		
		var payload = {
			'new_folder_name': formData['new_folder_name'],
			'new_folder_desc': formData['new_folder_desc'],
			'subaction': 'add_folder',
		};
		
		jQuery('.add-folder-loader').removeClass('hidden');

		jQuery.ajax({
			type: 'POST',
			url: url,
			data: payload,
			dataType: 'json',
			success: function(res) {
				jQuery('.add-folder-loader').addClass('hidden');
				if (res && res.success) {
					me.setDocFolder(res.folderid, res.foldername);
					me.enableAutocompleteField('doc_folder', false);

					jQuery("#new_folder_name").val('');
					jQuery("#new_folder_desc").val('');
					
					jQuery('.add-folder-dropdown').removeClass('open');
					jQuery('.add-folder-dropdown').find('[data-toggle="dropdown"]').attr('aria-expanded', 'false');
					
					me.reloadFolders();
				} else {
					console.log('Ajax error: ' + res.error);
				}
			},
			error: function() {
				jQuery('.add-folder-loader').addClass('hidden');
				console.log('Ajax error');
			}
		});	
	},
	
	setDocFolder: function(folderId, folderLabel) {
		jQuery('#doc_folder_id').val(folderId);
		jQuery('#doc_folder').val(folderLabel);
	},
	
	setAssignee: function(assigneeId, assigneeLabel) {
		jQuery('#assigned_user_id').val(assigneeId);
		jQuery('#assigned_user').val(assigneeLabel);
	},
	
	toggleAutocompleteList: function(fieldId) {
		var element = jQuery('#' + fieldId);
		
		if (element.length > 0) {
			if (element.autocomplete('widget').is(':visible')) {
				element.autocomplete('close');
				return;
			}
			element.autocomplete('search', '');
		}
	},
	
	toggleAutocompleteField: function(fieldId, show) {
		var element = jQuery('#' + fieldId);
		
		if (element.length > 0) {
			element.autocomplete(show ? 'open' : 'close');
		}
	},
	
	enableAutocompleteField: function(fieldId, enable) {
		var element = jQuery('#' + fieldId);
		
		if (element.length > 0) {
			element.prop('readonly', !enable);
			element.prop('disabled', !enable);
		}
	},
	
	resetAutocompleteField: function(fieldId) {
		var me = this,
			element = jQuery('#' + fieldId),
			elementId = jQuery('#' + fieldId + '_id');
		
		if (element.length > 0) {
			element.val('');
			elementId.val('');
			me.enableAutocompleteField(fieldId, true);
			me.toggleAutocompleteList(fieldId);
			element.focus();
		}
	},
	
	changeAssigneeTypeHandler: function() {
		var type = jQuery(this).data('type');
		var icon = jQuery(this).find('.vteicon').text();
		
		jQuery(this).closest('.dropdown').find('.assignee-toggle .vteicon').text(icon);
		
		if (type === 'user') {
			jQuery('#assigned_user').autocomplete('option', 'source', DropAreaForm.availableUsers); // load users
		} else if (type === 'group') {
			jQuery('#assigned_user').autocomplete('option', 'source', DropAreaForm.availableGroups); // load groups
		}
		
		DropAreaForm.resetAutocompleteField('assigned_user');
	},
	
};