/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@167019
// crmv@176893

window.VTE = window.VTE || {};
	
window.VTE.DropArea = window.VTE.DropArea || {
	
	overlay: false,
	
	targetElem: null,
	
	init: function(target, options) {
		var me = this;
		
		var targetElem = document.getElementById(target);
		
		if (!targetElem) {
			console.error('No target element found');
			return false;
		}
		
		if (!me.isSupported()) {
			console.error('DropArea is not supported');
			return false;
		}
		
		me.targetElem = targetElem;
		
		options = jQuery.extend({}, {
			'window_mode': true
		}, options);
		
		me.initializeEvents(options);
	},
	
	isSupported: function() {
		var div = document.createElement('div');
		return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
	},
	
	initializeEvents: function(options) {
		var me = this,
			target = me.targetElem;
		
		if (options.window_mode) {
			// When we drag the files inside the window, show the overlay!
			window.addEventListener('dragenter', function(e) {
				me.lastTarget = e.target;
				me.showOverlay(options);
			});
		
			// When we exit the window, hide the overlay!
			window.addEventListener('dragleave', function(e) {
				if (e.target === me.lastTarget) {
					me.hideOverlay(options);
				}
			});
		} else {
			// When we drag the files inside the target, show the overlay!
			target.addEventListener('dragenter', function(e) {
				me.showOverlay(options);
			});
		
			// When we exit the target, hide the overlay!
			target.addEventListener('dragleave', function(e) {
				me.hideOverlay(options);
			});
		}
		
		// To allow a drop on the specified element, we must prevent the default handling of the element
		target.addEventListener('dragover', function(e) {
			e.preventDefault();
			me.showOverlay(options);
		});
	
		// When we drop the files, fire the drop event!
		target.addEventListener('drop', function(e) {
			e.preventDefault();
			
			me.hideOverlay(options);
			
			var droppedFiles = e.dataTransfer;
			me.processDroppedFiles(droppedFiles);
		});
	},
	
	showOverlay: function(options) {
		var me = this,
			target = me.targetElem,
			$target = jQuery(target);
		
		if (me.overlay) return;
		
		me.overlay = true;
		
		if (options.window_mode) {
			$target.css('visibility', '');
			$target.css('opacity', 1);
		}
		
		$target.addClass('dragover');
	},
	
	hideOverlay: function(options) {
		var me = this,
			target = me.targetElem,
			$target = jQuery(target);
		
		if (!me.overlay) return;
		
		me.overlay = false;
		
		if (options.window_mode) {
			$target.css('visibility', 'hidden');
			$target.css('opacity', 0);
		}
		
		$target.removeClass('dragover');
	},
	
	processDroppedFiles: function(droppedFiles) {
		var me = this;
		
		// TODO: Folders are not supported for now
		
		if (droppedFiles && droppedFiles.files && droppedFiles.files.length > 0) {
			var check = true;
			for (var i = 0; i < droppedFiles.files.length; i++) {
				var fileExtension = me.getFileExtension(droppedFiles.files[i].name);
				if (droppedFiles.files[i].size === 0 || !fileExtension || fileExtension.length === 0) {
					check = false;
					break;
				}
			}
			if (check) {
				jQuery(document).trigger('vte.events.drop', [droppedFiles.files]);
			}
		}
	},
	
	getFileExtension: function(filename) {
		return (/[.]/.exec(filename)) ? /[^.]+$/.exec(filename)[0] : undefined;
	},
	
};

window.VTE.DropArea.Documents = window.VTE.DropArea.Documents || {
	
	busy: false,
	
	mode: null,
	
	parentModule: null,
	parentRecord: null,
	
	droppedFiles: null,
	currentFileIdx: 0,
	zipFileName: null,
	
	stopUploadFlag: false,
	
	init: function() {
		var me = this,
			form = jQuery('form[name="DetailView"]');
		
		if (!form.length) return;
		
		if (form[0].module) {
			me.parentModule = form[0].module.value;
		}
		
		if (form[0].record) {
			me.parentRecord = form[0].record.value;
		}
		
		me.initializeEvents();
	},
	
	initializeEvents: function() {
		var me = this;
		
		jQuery(document).on('vte.events.drop', function(e, files) {
			me.onFilesDrop(files);
		});
	},
	
	onFilesDrop: function(files) {
		var me = this;
		
		me.droppedFiles = files;
		
		var bsModal1 = jQuery('#uploadModeModal').data('bs.modal');
		var bsModal2 = jQuery('#saveDocumentModal').data('bs.modal');
		
		var isShown = bsModal1 && bsModal1.isShown || false;
		isShown |= bsModal2 && bsModal2.isShown || false;
		
		if (isShown) return;
		
		me.currentFileIdx = 0;
		me.stopUploadFlag = false;
		
		if (me.parentModule === 'Documents') {
			me.confirmDocumentRevision();
		} else {
			if (me.droppedFiles.length > 1) {
				me.chooseUploadModeModal();
			} else {
				me.chooseUploadMode('one_by_one');
			}
		}
	},
	
	showBusy: function() {
		var me = this;
		me.busy = true;
		VteJS_DialogBox.progress();
	},
	
	hideBusy: function() {
		var me = this;
		me.busy = false;
		VteJS_DialogBox.hideprogress();
	},
	
	chooseUploadModeModal: function() {
		jQuery('#uploadModeModal').modal('show');
	},
	
	chooseUploadMode: function(mode) {
		var me = this;
		
		jQuery('#uploadModeModal').modal('hide');
		
		me.mode = mode;
		
		if (mode === 'single_zip_file') {
			me.uploadSingleZipFile();
		} else if (mode === 'one_by_one') {
			me.uploadOneByOne();
		} else {
			console.error('Unknown upload mode');
		}
	},
	
	uploadSingleZipFile: function() {
		var me = this;
		
		if (!window.JSZip) {
			console.error('JSZip library not found.');
			return;
		}
		
		me.saveDocumentModal();
	},
	
	uploadOneByOne: function() {
		var me = this;
		
		me.saveDocumentModal();
	},
	
	saveDocumentModal: function() {
		var me = this,
			parentModule = null,
			parentRecord = null;
		
		var modal = jQuery('#saveDocumentModal');
		
		if (!modal.length) {
			var modal = jQuery('<div>');
			modal.attr('id', 'saveDocumentModal');
			jQuery('body').append(modal);
		}
		
		var params = {
			'pmodule': me.parentModule,
			'precord': me.parentRecord,
			'mode': me.mode,
		};
		
		me.ajaxRequest('DROPAREAFORM', params, function(result) {
			result = JSON.parse(result);
			if (result && result.success) {
				VTE.showModal('saveDocumentModal', result.title, result.html, {
					showFooter: true,
					buttons: [{
						id: 'cancelAllDocumentBtn', 
						cls: 'crmbutton edit', 
						value: alert_arr.LBL_CANCEL_ALL,
						handler: function() {
							me.stopUploadHandler();
						},
					},
					{
						id: 'cancelDocumentBtn', 
						cls: 'crmbutton edit', 
						value: alert_arr.LBL_CANCEL,
						dismissable: true,
					},
					{
						id: 'saveDocumentBtn', 
						cls: 'crmbutton save success', 
						value: alert_arr.LBL_SAVE,
						handler: function() {
							me.saveDocument();
						},
					}],
					events: {
						'hidden.bs.modal': function() {
							me.processNextFile();
						},
					},
				});
				
				me.populateDefaultData();
			}
		});
	},
	
	saveDocument: function() {
		var me = this,
			form = jQuery('#saveDocumentForm');
		
		if (me.busy) return;
		
		me.getCurrentFile(function(file) {
			processSaveDocument(file);
		});
		
		function processSaveDocument(currentFile) {
			if (!currentFile) return;
			
			var ajaxData = new FormData(form.get(0));
			
			var docTitle = jQuery('#doc_title').val();
			if (!docTitle) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', me.getFieldLabel('doc_title')));
			
			var docFolder = parseInt(jQuery('#doc_folder_id').val());
			if (!docFolder || docFolder === 0) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', me.getFieldLabel('doc_folder')));

			ajaxData.set('notes_title', docTitle);
			ajaxData.set('folderid', docFolder);
			ajaxData.set('notecontent', jQuery('#doc_description').val());
			ajaxData.set('responseFormat', 'json');
			ajaxData.set('filename', currentFile);
			
			function processSuccess(response) {
				me.hideBusy();
				if (response && response.success == true) {
					VTE.hideModal('saveDocumentModal');
				} else if (response && response.error) {
					vtealert(alert_arr.LBL_ERROR_SAVING+': '+response.error)
				} else {
					vtealert(alert_arr.LBL_ERROR_SAVING);
				}
			}
			
			function processError() {
				me.hideBusy();
				vtealert(alert_arr.LBL_ERROR_SAVING);
			}
			
			me.showBusy();
			
			jQuery.ajax({
				url: 'index.php',
				type: 'POST',
				data: ajaxData,
				dataType: 'json',
				cache: false,
				contentType: false,
				processData: false,
				success: processSuccess,
				error: processError,
			});
		}
	},
	
	getFieldLabel: function(fieldId) {
		var label = '';
		
		var fieldElement = jQuery('#' + fieldId);
		if (fieldElement.length > 0) {
			label = fieldElement.closest('.form-group').find('label[for="' + fieldId + '"]').text();
		}
		
		return label;
	},
	
	displayError: function(message) {
		vtealert(message);
		return false;
	},
	
	confirmDocumentRevision: function() {
		var me = this;
		
		if (me.droppedFiles && me.droppedFiles.length > 1) {
			vtealert(alert_arr.LBL_REVISION_DROP_LIMIT);
			return false;
		}
		
		vteconfirm(alert_arr.LBL_REVISION_CONFIRM, function(yes) {
			if (yes) me.saveDocumentRevision();
		});
	},
	
	saveDocumentRevision: function() {
		var me = this;
		
		if (me.busy) return;
		
		me.getCurrentFile(function(file) {
			processSaveDocument(file);
		});
		
		function processSaveDocument(currentFile) {
			if (!currentFile) return;
		
			var ajaxData = new FormData();
			
			ajaxData.set('module', 'Documents');
			ajaxData.set('action', 'DocumentsAjax');
			ajaxData.set('record', me.parentRecord);
			ajaxData.set('file', 'RevisionSave');
			ajaxData.set('filename_hidden', currentFile.name);
			ajaxData.set('responseFormat', 'json');
			ajaxData.set('filename', currentFile);
			
			function processSuccess(response) {
				me.hideBusy();
				if (response && response.success == true) {
					window.location.reload();
				} else if (response && response.error) {
					vtealert(alert_arr.LBL_ERROR_SAVING+': '+response.error)
				} else {
					vtealert(alert_arr.LBL_ERROR_SAVING);
				}
			}
			
			function processError() {
				me.hideBusy();
				vtealert(alert_arr.LBL_ERROR_SAVING);
			}
			
			me.showBusy();
			
			jQuery.ajax({
				url: 'index.php',
				type: 'POST',
				data: ajaxData,
				dataType: 'json',
				cache: false,
				contentType: false,
				processData: false,
				success: processSuccess,
				error: processError,
			});
		}
	},
	
	populateDefaultData: function() {
		var me = this,
			form = jQuery('#saveDocumentForm');
		
		var defaultData = null;
		
		if (me.mode === 'single_zip_file') {
			defaultData = me.populateZipDefaultData();
		} else {
			defaultData = me.populateSingleFileDefaultData();
		}
		
		if (defaultData) {
			jQuery.each(defaultData, function(name, value) {
				form.find('#'+name).val(value);
			});
		}
		
		return true;
	},
	
	populateZipDefaultData: function() {
		var me = this,
			form = jQuery('#saveDocumentForm'),
			defaultData = {};
		
		var parentEntityName = null;
		
		if (form[0].return_entity_name) {
			parentEntityName = form[0].return_entity_name.value;
		}
		
		if (parentEntityName) {
			var now = new Date();
			
			var day = now.getDate() < 10 ? '0' + now.getDate() : now.getDate();
			var month = (now.getMonth()+1) < 10 ? '0' + (now.getMonth()+1) : (now.getMonth()+1);
			var year = now.getFullYear();
			var hours = now.getHours() < 10 ? '0' + now.getHours() : now.getHours();
			var minutes = now.getMinutes() < 10 ? '0' + now.getMinutes() : now.getMinutes();
			
			var datef = year + month + day + 'T' + hours + minutes;

			parentEntityName += '_' + datef;
			parentEntityName += '.zip';
			parentEntityName = parentEntityName.replace(/\s+/g, '_');
			
			defaultData['doc_title'] = parentEntityName;
		}
		
		me.zipFileName = parentEntityName || 'upload.zip';
		
		return defaultData;
	},
	
	populateSingleFileDefaultData: function() {
		var me = this,
			form = jQuery('#saveDocumentForm'),
			defaultData = {};
		
		var fileIdx = me.currentFileIdx;
		if (!me.droppedFiles[fileIdx]) return;
		
		var currentFile = me.droppedFiles[fileIdx];
		
		defaultData['doc_title'] = currentFile.name;
		
		return defaultData;
	},
	
	processNextFile: function() {
		var me = this;
		
		if (me.nextFile() && !me.stopUploadFlag) {
			me.saveDocumentModal();
		} else {
			reloadTurboLift(me.parentModule, me.parentRecord, 'Documents');
		}
	},
	
	nextFile: function() {
		var me = this;
		
		if (me.mode === 'single_zip_file') {
			return false;
		}
		
		++me.currentFileIdx;
		return typeof me.droppedFiles[me.currentFileIdx] !== 'undefined';
	},
	
	getCurrentFile: function(callback) {
		var me = this,
			callback = callback || function() {};
		
		if (me.mode === 'single_zip_file') {
			return me.generateZipFile(callback);
		}
		
		callback(me.droppedFiles[me.currentFileIdx]);
	},
	
	generateZipFile: function(callback) {
		var me = this;
		
		if (!window.JSZip) {
			console.error('JSZip library not found.');
			return;
		}
		
		var zip = new JSZip();
		
		for (var i = 0; i < me.droppedFiles.length; i++) {
			zip.file(me.droppedFiles[i].name, me.droppedFiles[i]);
		}
		
		zip.generateAsync({
			type: 'blob',
			compression: 'DEFLATE',
		    compressionOptions: {
		        level: 9,
		    },
		}).then(function(content) {
			var zippedFile = new File([content], me.zipFileName, {type: 'application/zip'});
			callback(zippedFile);
		});
	},
	
	stopUploadHandler: function() {
		var me = this;
		
		me.stopUploadFlag = true;
		VTE.hideModal('saveDocumentModal');
	},
	
	ajaxRequest: function(action, params, callback) {
		var url = 'index.php?module=Documents&action=DocumentsAjax&file=DetailViewAjax&ajxaction='+action;
		jQuery.ajax({
			url: url,
			method: 'GET',
			data: params || {},
			success: function(result) {
				if (typeof callback == 'function') callback(result);
			}
		});
	},
	
};