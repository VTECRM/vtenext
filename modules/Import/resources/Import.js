/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

if (typeof(ImportJs) == 'undefined') {
    /*
	 * Namespaced javascript class for Import
	 */
    ImportJs = {

		toogleMergeConfiguration: function() {
			var mergeChecked = jQuery('#auto_merge').is(':checked');
			if(mergeChecked) {
				jQuery('#duplicates_merge_configuration').show();
			} else {
				jQuery('#duplicates_merge_configuration').hide();
			}
		},

		checkFileType: function() {
			var filePath = jQuery('#import_file').val();
			if(filePath != '') {
				var fileExtension = filePath.split('.').pop();
				jQuery('#type').val(fileExtension);
				ImportJs.handleFileTypeChange();
			}
		},

		handleFileTypeChange: function() {
			var fileType = jQuery('#type').val();
			if(fileType != 'csv') {
				jQuery('#delimiter_container').hide();
				jQuery('#has_header_container').hide();
			} else {
				jQuery('#delimiter_container').show();
				jQuery('#has_header_container').show();
			}
		},

        uploadAndParse: function() {
			if(!ImportJs.validateFilePath()) return false;
			if(!ImportJs.validateMergeCriteria()) return false;
			return true;
        },

		validateFilePath: function() {
			var filePath = jQuery('#import_file').val();
			if(jQuery.trim(filePath) == '') {
				alert(sprintf(alert_arr.CANNOT_BE_EMPTY, 'Import File'));
				jQuery('#import_file').focus();
				return false;
			}
			if(!ImportJs.uploadFilter("import_file", "csv|vcf")) {
				return false;
			}
			return true;
		},

		uploadFilter: function(elementId, allowedExtensions) {
			var obj = jQuery('#'+elementId);
			if(obj) {
				var filePath = obj.val();
				var fileParts = filePath.toLowerCase().split('.');
				var fileType = fileParts[fileParts.length-1];
				var validExtensions = allowedExtensions.toLowerCase().split('|');

				if(validExtensions.indexOf(fileType) < 0) {
					alert(alert_arr.PLS_SELECT_VALID_FILE+' '+validExtensions);
					obj.focus();
					return false;
				}
			}
			return true;
		},

		validateMergeCriteria: function() {
			$mergeChecked = jQuery('#auto_merge').is(':checked');
			if($mergeChecked) {
				var selectedOptions = jQuery('#selected_merge_fields option');
				if(selectedOptions.length == 0) {
					alert(alert_arr.ERR_SELECT_ATLEAST_ONE_MERGE_CRITERIA_FIELD);
					return false;
				}
			}
			convertOptionsToJSONArray('selected_merge_fields', 'merge_fields');
			return true;
		},

		sanitizeAndSubmit: function() {
			if(!ImportJs.sanitizeFieldMapping()) return false;
			if(!ImportJs.validateCustomMap()) return false;
			return true;
		},

		// crmv@83878
		sanitizeFieldMapping: function() {
			var fieldsList = jQuery('.fieldIdentifier');
			var mappedFields = {};
			var mappedDefaultValues = {};
			var mappedFieldsFormats = {};
			
			for(var i=0; i<fieldsList.length; ++i) {
				var fieldElement = jQuery(fieldsList.get(i));
				var rowId = jQuery('[name=row_counter]', fieldElement).get(0).value;
				var selectedFieldElement = jQuery('select option:selected', fieldElement);
				var selectedFieldName = selectedFieldElement.val();
				var selectedFieldDefaultValueElement = jQuery('#'+selectedFieldName+'_defaultvalue', fieldElement);
				var defaultValue = '';
				if(selectedFieldDefaultValueElement.prop('type') == 'checkbox') {
					defaultValue = selectedFieldDefaultValueElement.is(':checked');
				} else {
					defaultValue = selectedFieldDefaultValueElement.val();
				}
				if(selectedFieldName != '') {
					if(selectedFieldName in mappedFields) {
						vtealert(alert_arr.ERR_FIELDS_MAPPED_MORE_THAN_ONCE + ' "' + selectedFieldElement.html() +'"');   // crmv@187110
						return false;
					}
					mappedFields[selectedFieldName] = rowId-1;
					if(defaultValue != '') {
						mappedDefaultValues[selectedFieldName] = defaultValue;
					}
					
					var fieldFormat = jQuery('#'+selectedFieldName+'_format', fieldElement).val();
					if (fieldFormat) {
						mappedFieldsFormats[selectedFieldName] = fieldFormat;
					}
				}
				
			}

			var mandatoryFields = JSON.parse(jQuery('#mandatory_fields').val());
			var missingMandatoryFields = [];
			//crmv@42329
			if (mandatoryFields.length > 0){
				for(var mandatoryFieldName in mandatoryFields) {
					if(mandatoryFieldName in mappedFields) {
						continue;
					} else {
						missingMandatoryFields.push('"'+mandatoryFields[mandatoryFieldName]+'"');
					}
				}
			}
			//crmv@42329e
			if(missingMandatoryFields.length > 0) {
				alert(alert_arr.ERR_PLEASE_MAP_MANDATORY_FIELDS + ' : ' + missingMandatoryFields.join(','));
				return false;
			}
			jQuery('#field_mapping').val(JSON.stringify(mappedFields));
			jQuery('#default_values').val(JSON.stringify(mappedDefaultValues));
			jQuery('#fields_formats').val(JSON.stringify(mappedFieldsFormats));
			return true;
		},
		// crmv@83878e

		validateCustomMap: function() {
			var saveMap = jQuery('#save_map').is(':checked');
			if(saveMap) {
				var mapName = jQuery('#save_map_as').val();
				if(jQuery.trim(mapName) == '') {
					alert(alert_arr.ERR_MAP_NAME_CANNOT_BE_EMPTY);
					return false;
				}
				var mapOptions = jQuery('#saved_maps option');
				for(var i=0; i<mapOptions.length; ++i) {
					var mapOption = jQuery(mapOptions.get(i));
					if(mapOption.html() == mapName) {
						alert(alert_arr.ERR_MAP_NAME_ALREADY_EXISTS);
						return false;
					}
				}
			}
			return true;
		},

		// crmv@136181
		loadSavedMap: function() {
			var mapId = jQuery('#saved_maps').val();
			var fieldsList = jQuery('.fieldIdentifier');
			fieldsList.each(function(i, element) {
				var fieldElement = jQuery(element);
				jQuery('[name=mapped_fields]', fieldElement).val('');
			});
			if(mapId == -1) {
				jQuery('#delete_map_container').hide();
				return;
			}
			jQuery('#delete_map_container').show();
			var mapping = {};
			var mappingData = savedMappings[mapId];
			if (!mappingData || !mappingData.mapping) return;
			jQuery.each(mappingData.mapping, function(prop, value) {
				mapping["'"+prop+"'"] = value;
			});
			fieldsList.each(function(i, element) {
				var fieldElement = jQuery(element);
				var rowId = jQuery('[name=row_counter]', fieldElement).get(0).value;
				rowId = rowId-1;	//crmv@31619
				var headerNameElement = jQuery('[name=header_name]', fieldElement).get(0);
				var headerName = jQuery(headerNameElement).html();
				if("'"+headerName+"'" in mapping) {
					jQuery('[name=mapped_fields]', fieldElement).val(mapping["'"+headerName+"'"]);
				} else if("'"+rowId+"'" in mapping) {
					jQuery('[name=mapped_fields]', fieldElement).val(mapping["'"+rowId+"'"]);
				}
				ImportJs.loadDefaultValueWidget(fieldElement.attr('id'));
				
				//crmv@146191
				if("'"+headerName+"'" in mapping) {
					var fieldname = mapping["'"+headerName+"'"];
					// set the default value
					if (typeof mappingData.defaults === 'object' && fieldname in mappingData.defaults) {
						fieldElement.find('[name='+fieldname+'_defaultvalue]').val(mappingData.defaults[fieldname]);
					}
					// and the format
					if (typeof mappingData.formats === 'object' && fieldname in mappingData.formats) {
						fieldElement.find('[name='+fieldname+'_format]').val(mappingData.formats[fieldname]);
					}
				}
				else if("'"+rowId+"'" in mapping) {
					var fieldname = mapping["'"+rowId+"'"];
					// set the default value
					if (typeof mappingData.defaults === 'object' && fieldname in mappingData.defaults) {
						fieldElement.find('[name='+fieldname+'_defaultvalue]').val(mappingData.defaults[fieldname]);
					}
					// and the format
					if (typeof mappingData.formats === 'object' && fieldname in mappingData.formats) {
						fieldElement.find('[name='+fieldname+'_format]').val(mappingData.formats[fieldname]);
					}
				}
				//crmv@146191e
			});
		},
		// crmv@136181e

		deleteMap : function(module) {
			if(confirm(alert_arr.ARE_YOU_SURE_YOU_WANT_TO_DELETE)) {
				var mapId = jQuery('#saved_maps').val(); // crmv@136181
				jQuery('#status').show();
				jQuery.ajax( {
					url  : 'index.php',
					type : 'POST',
					data : {module: module,
							action: module+'Ajax',
							file: 'Import',
							mode: 'delete_map',
							mapid: mapId,
							ajax: true},
					complete : function(response) {
						jQuery('#savedMapsContainer').html(response.responseText);
						jQuery('#status').hide();
					}
				});
			}
		},

		loadListViewPage: function(module, pagenum, userid) {
			jQuery('#status').show();
			jQuery.ajax( {
                url  : 'index.php',
                type : 'POST',
                data : {module: module,
						action: module+'Ajax',
						file: 'Import',
						mode: 'listview',
						start: pagenum,
						foruser: userid,
						ajax: true},
                complete : function(response) {
					jQuery('#import_listview_contents').html(response.responseText);
					jQuery('#status').hide();
                }
            });
		},

		loadListViewSelectedPage: function(module, userid) {
			var pagenum = jQuery('#page_num').val();
			ImportJs.loadListViewPage(module, pagenum, userid);
		},

		loadDefaultValueWidget: function(rowIdentifierId) {
			var affectedRow = jQuery('#'+rowIdentifierId);
			if(typeof affectedRow == 'undefined' || affectedRow == null) return;
			var selectedFieldElement = jQuery('[name=mapped_fields]', affectedRow).get(0);
			var selectedFieldName = jQuery(selectedFieldElement).val();
			var defaultValueContainer = jQuery(jQuery('[name=default_value_container]', affectedRow).get(0));
			var allDefaultValuesContainer = jQuery('#defaultValuesElementsContainer');
			if(defaultValueContainer.children.length > 0) {
				var copyOfDefaultValueWidget = jQuery(':first', defaultValueContainer).detach();
				copyOfDefaultValueWidget.appendTo(allDefaultValuesContainer);
			}
			var selectedFieldDefValueContainer = jQuery('#'+selectedFieldName+'_defaultvalue_container', allDefaultValuesContainer);
			var defaultValueWidget = selectedFieldDefValueContainer.detach();
			defaultValueWidget.appendTo(defaultValueContainer);
			
			this.loadFormatWidget(rowIdentifierId); // crmv@83878
		},
		
		// crmv@83878
		loadFormatWidget: function(rowIdentifierId) {
			var affectedRow = jQuery('#'+rowIdentifierId);
			if (typeof affectedRow == 'undefined' || affectedRow == null) return;
			var selectedFieldElement = jQuery('[name=mapped_fields]', affectedRow).get(0);
			var selectedFieldName = jQuery(selectedFieldElement).val();
			var formatContainer = jQuery(jQuery('[name=format_container]', affectedRow).get(0));
			var allFormatsContainer = jQuery('#formatsElementsContainer');
			if(formatContainer.children.length > 0) {
				var copyOfFormatWidget = jQuery(':first', formatContainer).detach();
				copyOfFormatWidget.appendTo(allFormatsContainer);
			}
			
			var selectedFieldFormatContainer = jQuery('#'+selectedFieldName+'_format_container', allFormatsContainer);
			var formatWidget = selectedFieldFormatContainer.detach();
			formatWidget.appendTo(formatContainer);
			
			// and select a default format if possible
			var options = formatWidget.find('option').map(function() {return jQuery(this).val(); }).get();
			if (options && options.length > 0) {
				var format = this.detectFormat(rowIdentifierId, options);
				if (format) {
					formatWidget.find('select').first().val(format);
				}
			}
		},
		
		// try to guess the format from the data provided
		detectFormat: function(rowIdentifierId, options) {
			var affectedRow = jQuery('#'+rowIdentifierId);
			var value = affectedRow.find('.importValueContainer').first().text().trim();
			var type = null;
			var format = null;
			
			if (value == '') return null;
			
			// guess the type from the options
			// TODO: use a safer way to detect it!
			if (options[0].indexOf('PERIOD') >= 0) {
				type = 'number';
			} else if (options[0].indexOf('Y-m') >= 0) {
				type = 'date';
			}
			
			if (type && value) {
				if (type == 'number') {
					var dss = ['.', ','];
					var tss = ['', '.', ',', ' ', "'"];
					var ds, ts;
					var valid = false;
					for (var i=0; i<dss.length && !valid; ++i) {
						ds = dss[i];
						for (var j=0; j<tss.length && !valid; ++j) {
							ts = tss[j];
							if (ds == ts) continue;
							valid = validateUserNumber(value, ds, ts);
						}
					}
					if (valid) {
						// found a format!
						var trans = [
							['.', 'PERIOD'],
							[',', 'COMMA'],
							[' ', 'SPACE'],
							["'", 'QUOTE'],
						];
						if (ts == '') {
							ts = 'EMPTY';
						} else {
							for (var i=0; i<trans.length; ++i) {
								ts = ts.replace(trans[i][0],trans[i][1]);
							}
						}
						for (var i=0; i<trans.length; ++i) {
							ds = ds.replace(trans[i][0],trans[i][1]);
						}
						format = ts+':'+ds;
					}
				} else if (type == 'date') {
					if (value.match(/[0-9]{4}-[01][0-9]-[0123][0-9]/)) {
						format = 'Y-m-d';
					} else if (value.match(/[0-9]{4}[01][0-9][0123][0-9]/)) {
						format = 'Ymd';
					// the next 2 are not mutually exclusive, so a date like 02/03/2000 will fall always in the first case
					} else if (value.match(/[0123][0-9]\/[01][0-9]\/[0-9]{4}/)) {
						format = 'd/m/Y';
					} else if (value.match(/[01][0-9]\/[0123][0-9]\/[0-9]{4}/)) {
						format = 'm/d/Y';
					}
				}
			}
			return format;
		},
		// crmv@83878e

		loadDefaultValueWidgetForMappedFields: function() {
			var fieldsList = jQuery('.fieldIdentifier');
			fieldsList.each(function(i, element) {
				var fieldElement = jQuery(element);
				var mappedFieldName = jQuery('[name=mapped_fields]', fieldElement).val();
				if(mappedFieldName != '') {
					ImportJs.loadDefaultValueWidget(fieldElement.attr('id'));
				}
			});
		},
		
		// crmv@92218
		changeEncoding: function(encoding) {
			var form = jQuery('form[name=importAdvanced]');
			form.find('input[name=mode]').val('reload_mapping');
			form.submit();
		}
		// crmv@92218e
		
    }

	jQuery(document).ready(function() {
		ImportJs.toogleMergeConfiguration();
		ImportJs.loadDefaultValueWidgetForMappedFields();
	});
}