/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@173153

window.VTEPortal = window.VTEPortal || {};

VTEPortal.DropArea = VTEPortal.DropArea || {
	
	overlay: false,
	
	targetElem: null,
	
	init: function(target) {
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
		
		me.initializeEvents();
	},
	
	isSupported: function() {
		var div = document.createElement('div');
		return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
	},
	
	initializeEvents: function() {
		var me = this,
			target = me.targetElem;
		
		// When we drag the files inside the window, show the overlay!
		
		window.addEventListener('dragenter', function(e) {
			me.lastTarget = e.target;
			me.showOverlay();
		});
	
		// When we exit the window, hide the overlay!
		
		window.addEventListener('dragleave', function(e) {
			if (e.target === me.lastTarget) {
				me.hideOverlay();
			}
		});
		
		// To allow a drop on the specified element, we must prevent the default handling of the element
		
		me.targetElem.addEventListener('dragover', function(e) {
			e.preventDefault();
		});
	
		// When we drop the files, fire the drop event!
		
		me.targetElem.addEventListener('drop', function(e) {
			e.preventDefault();
			
			me.hideOverlay();
			
			var droppedFiles = e.dataTransfer;
			me.processDroppedFiles(droppedFiles);
		});
	},
	
	showOverlay: function() {
		var me = this,
			target = me.targetElem,
			$target = jQuery(target);
		
		if (me.overlay) return;
		
		me.overlay = true;
		
		$target.css('visibility', '');
		$target.css('opacity', 1);
		$target.addClass('dragover');
	},
	
	hideOverlay: function() {
		var me = this,
			target = me.targetElem,
			$target = jQuery(target);
		
		if (!me.overlay) return;
		
		me.overlay = false;
		
		$target.css('visibility', 'hidden');
		$target.css('opacity', 0);
		$target.removeClass('dragover');
	},
	
	processDroppedFiles: function(droppedFiles) {
		var me = this;
		
		if (droppedFiles && droppedFiles.files && droppedFiles.files.length > 0) {
			var check = true;
			// TODO: Folders are not supported for now
			for (var i = 0; i < droppedFiles.files.length; i++) {
				var fileExtension = me.getFileExtension(droppedFiles.files[i].name);
				if (droppedFiles.files[i].size === 0 || !fileExtension || fileExtension.length === 0) {
					check = false;
					break;
				}
			}
			if (check) {
				jQuery(document).trigger('vteportal.events.drop', [droppedFiles.files]);
			}
		}
	},
	
	getFileExtension: function(filename) {
		return (/[.]/.exec(filename)) ? /[^.]+$/.exec(filename)[0] : undefined;
	},
	
};

VTEPortal.DropArea.HelpDesk = VTEPortal.DropArea.HelpDesk || {
	
	busy: false,
	
	parentModule: null,
	parentRecord: null,
	droppedFiles: null,
	
	init: function() {
		var me = this;
		
		var create = me.initForCreateView();
		
		if (!create) {
			me.initForDetailView();
		}
		
		me.initializeEvents();
	},
	
	initForCreateView: function() {
		var me = this;
		
		var form = jQuery('form[name="Save"]');
		if (!form.length) return false;
		
		if (form[0].module) {
			me.parentModule = form[0].module.value;
		}
		
		me.parentRecord = 0;
		
		me.currentForm = form;
		
		var inputFile = form.find('input[name="customerfile[]"]');
		me.currentInputFile = inputFile;
		
		return true;
	},
	
	initForDetailView: function() {
		var me = this;
		
		var form = jQuery('form[name="fileattachment"]');
		if (!form.length) return false;
		
		if (form[0].module) {
			me.parentModule = form[0].module.value;
		}
		
		if (form[0].ticketid) {
			me.parentRecord = parseInt(form[0].ticketid.value);
		}
		
		me.currentForm = form;
		
		var inputFile = form.find('input[name="customerfile[]"]');
		me.currentInputFile = inputFile;
		
		return true;
	},
	
	showBusy: function() {
		var me = this;
		me.busy = true;
		jQuery('body').addClass('uploading');
	},
	
	hideBusy: function() {
		var me = this;
		me.busy = false;
		jQuery('body').removeClass('uploading');
	},
	
	initializeEvents: function() {
		var me = this;
		
		jQuery(document).on('vteportal.events.drop', function(e, droppedFiles) {
			me.onFilesDrop(droppedFiles);
		});
	},
	
	onFilesDrop: function(droppedFiles) {
		var me = this;
		
		me.droppedFiles = droppedFiles;
		
		var bsModal1 = jQuery('#uploadModeModal').data('bs.modal');
		var bsModal2 = jQuery('#zipFormModal').data('bs.modal');
		
		var isShown = bsModal1 && bsModal1.isShown || false;
		isShown |= bsModal2 && bsModal2.isShown || false;
		
		if (isShown) return;
		
		if (me.parentRecord > 0) {
			if (me.droppedFiles.length > 1) {
				me.chooseUploadModeModal();
			} else {
				me.mode = 'separate_files';
				me.uploadAllFiles();
			}
		} else {
			me.populateFileInput();
		}
	},
	
	chooseUploadModeModal: function() {
		jQuery('#uploadModeModal').modal('show');
	},
	
	populateFileInput: function() {
		var me = this,
			form = me.currentForm,
			input = me.currentInputFile;
		
		if (!input.length) return;
		
		input.get(0).files = me.droppedFiles;
	},
	
	chooseUploadMode: function(mode) {
		var me = this;
		
		me.mode = mode;
		
		jQuery('#uploadModeModal').modal('hide');
		
		if (mode === 'single_zip_file') {
			me.uploadSingleZipFile();
		} else if (mode === 'separate_files') {
			me.uploadAllFiles();
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
		
		me.populateDefaultData();
		jQuery('#zipFormModal').modal('show');
	},
	
	uploadAllFiles: function() {
		var me = this;
		
		 me.attachFiles();
	},
	
	populateDefaultData: function() {
		var me = this,
			form = jQuery('#zipForm');
		
		var defaultData = null;
		
		if (me.mode === 'single_zip_file') {
			defaultData = me.populateZipDefaultData();
			me.zipFileName = defaultData['doc_title'];
		}
		
		if (defaultData) {
			jQuery.each(defaultData, function(name, value) {
				form.find('#'+name).val(value);
			});
		}
		
		return true;
	},
	
	populateZipDefaultData: function() {
		var me = this;
		
		var defaultData = {};
		var parentEntityName = '';
		
		var now = new Date();
		
		var day = now.getDate() < 10 ? '0' + now.getDate() : now.getDate();
		var month = (now.getMonth()+1) < 10 ? '0' + (now.getMonth()+1) : (now.getMonth()+1);
		var year = now.getFullYear();
		var hours = now.getHours() < 10 ? '0' + now.getHours() : now.getHours();
		var minutes = now.getMinutes() < 10 ? '0' + now.getMinutes() : now.getMinutes();
		
		var datef = [year, month, day, 'T', hours, minutes].join('');

		parentEntityName += datef;
		parentEntityName += '.zip';
		parentEntityName = parentEntityName.replace(/\s+/g, '_');
		
		defaultData['doc_title'] = parentEntityName;
		
		return defaultData;
	},
	
	attachFiles: function() {
		var me = this;
		
		if (me.mode === 'single_zip_file') {
			jQuery('#zipFormModal').modal('hide');
			
			var zipFileName = jQuery('#zipForm').find('#doc_title').val();
			me.zipFileName = zipFileName;
			
			me.generateZipFile(zipFileName, function(zippedFile) {
				me.submitAttachments([zippedFile]);
			});
		} else if (me.mode === 'separate_files') {
			me.submitAttachments(me.droppedFiles);
		}
	},
	
	generateZipFile: function(fileName, callback) {
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
			var zippedFile = new File([content], fileName, {
				type: 'application/zip',
			});
			callback(zippedFile);
		});
	},
	
	submitAttachments: function(customerFile) {
		var me = this;
	
		if (me.busy) return;
		
		me.showBusy();
		
		var form = me.currentForm;
		var input = me.currentInputFile;
		var inputName = input.attr('name');
	
		var ajaxData = new FormData(form.get(0));
		
		ajaxData.delete(inputName);
		
		for (var i = 0; i < customerFile.length; i++) {
			ajaxData.append(inputName, customerFile[i]);
		}
		
		ajaxData.set('ajax', 'true');
		ajaxData.set('output_format', 'json');
		
		function processSuccess(response) {
			me.hideBusy();
			if (response && response.success == true) {
				window.location.reload();
			} else if (response && response.error) {
				alert(alert_arr.LBL_ERROR_SAVING+': '+response.error)
			} else {
				alert(alert_arr.LBL_ERROR_SAVING);
			}
		}
		
		function processError() {
			me.hideBusy();
			alert(alert_arr.LBL_ERROR_SAVING);
		}
		
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
	},
	
	onFileAttachSubmit: function(form) {
		var me = this,
			$form = jQuery(form),
			input = me.currentInputFile;
		
		if (!input.val()) {
			alert("INVALIDD!!!");
			return false;
		}
		
		return true;
	},
	
};