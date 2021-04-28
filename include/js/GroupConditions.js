/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92272 crmv@102879 crmv@106857 crmv@115268 */

if (typeof(GroupConditions) == 'undefined') {
	GroupConditions = {
	
		groupLabel: false,
		groupGlue: true,
		//crmv@158293
		/*
		 * 0: fields of the related module are hidden and it is shown only the relation field in the primary module
		 * 1: show fields of the related module but not the relation field in the primary module
		 * 2: show both of them
		 */
		relatedFields: 1,
		//crmv@158293e
		subGroup: false,
		subGroupLabel: '',
		subGroupAddTitle: alert_arr.LBL_NEW_CONDITION_BUTTON_LABEL,
		subGroupOperation: true,
		subGroupGlue: true,
		subGroupRelatedFields: true,	// if false in the sub group hide related fields
		otherParams: {},

		init: function($, moduleName, container, context, conditions, params, initCallback){
			var vtinst = new VtenextWebservices("webservice.php",undefined,undefined,true,true);	//crmv@120039 crmv@140949
			var fieldValidator;
			var desc = null;
			var me = this;
			
			if (typeof(params) == 'undefined') var params = {};
			if (typeof(params['groupLabel']) != 'undefined') me.groupLabel = params['groupLabel'];
			if (typeof(params['groupGlue']) != 'undefined') me.groupGlue = params['groupGlue'];
			if (typeof(params['relatedFields']) != 'undefined') me.relatedFields = params['relatedFields'];
			if (typeof(params['subGroup']) != 'undefined') me.subGroup = params['subGroup'];
			if (typeof(params['subGroupLabel']) != 'undefined') me.subGroupLabel = params['subGroupLabel'];
			if (typeof(params['subGroupAddTitle']) != 'undefined') me.subGroupAddTitle = params['subGroupAddTitle'];
			if (typeof(params['subGroupOperation']) != 'undefined') me.subGroupOperation = params['subGroupOperation'];
			if (typeof(params['subGroupGlue']) != 'undefined') me.subGroupGlue = params['subGroupGlue'];
			if (typeof(params['subGroupRelatedFields']) != 'undefined') me.subGroupRelatedFields = params['subGroupRelatedFields'];
			if (typeof(params['otherParams']) != 'undefined') me.otherParams = params['otherParams'];

			function id(v){
				return v;
			}
		
			function map(fn, list){
				var out = [];
				$.each(list, function(i, v){
					out[out.length]=fn(v);
				});
				return out;
			}
		
			function field(name){
				return function(object){
					if(typeof(object) != 'undefined') {
						return object[name];
					}
				};
			}
		
			function zip(){
				var out = [];
		
				var lengths = map(field('length'), arguments);
				var min = reduceR(function(a,b){return a<b?a:b;},lengths,lengths[0]);
				for(var i=0; i<min; i++){
					out[i]=map(field(i), arguments);
				}
				return out;
			}
		
			function dict(list){
				var out = {};
				$.each(list, function(i, v){
					out[v[0]] = v[1];
				});
				return out;
			}
		
			function filter(pred, list){
				var out = [];
				$.each(list, function(i, v){
					if(pred(v)){
						out[out.length]=v;
					}
				});
				return out;
			}
			
			function diff(reflist, list) {
				var out = [];
				$.each(list, function(i, v) {
					if(contains(reflist, v)) {
						out.push(v);
					}
				});
				return out;
			}
		
		
			function reduceR(fn, list, start){
				var acc = start;
				$.each(list, function(i, v){
					acc = fn(acc, v);
				});
				return acc;
			}
		
			function contains(list, value){
				var ans = false;
				$.each(list, function(i, v){
					if(v==value){
						ans = true;
						return false;
					}
				});
				return ans;
			}
		
			function concat(lista,listb){
				return lista.concat(listb);
			}
		
			function errorDialog(message){
				alert(message);
			}
		
			function handleError(fn){
				return function(status, result){
					if(status){
						fn(result);
					}else{
						errorDialog('Failure:'+result);
					}
				};
			}
		
			function implode(sep, arr){
				var out = "";
				$.each(arr, function(i, v){
					out+=v;
					if(i<arr.length-1){
						out+=sep;
					}
				});
				return out;
			}
		
			function mergeObjects(obj1, obj2){
				var res = {};
				for(var k in obj1){
					res[k] = obj1[k];
				}
				for(var k in obj2){
					res[k] = obj2[k];
				}
				return res;
			}
		
			function jsonget(operation, params, callback){
				var obj = {
						module:'com_workflow',//crmv@207901
						action:'com_workflowAjax',//crmv@207901
						file:operation, ajax:'true'};
				$.each(params,function(key, value){
					obj[key] = value;
				});
				$.get('index.php', obj,
					function(result){
						callback(result);
				});
			}
		
			function center(el){
				el.css({position: 'absolute'});
				el.width("400px");
				el.height("125px");
				placeAtCenter(el.get(0));
			}
			
			function PageLoadingPopup(){
				function show(){
					$('#group_conditions_loading',context).css('display', 'inline');
				}
				function close(){
					$('#group_conditions_loading',context).css('display', 'none');
				}
				return {
					show:show, close:close
				};
			}
			var pageLoadingPopup = PageLoadingPopup();
		
			function NewTemplatePopup(){
				function close(){
					$('#new_template_popup',context).css('display', 'none');
				}
		
				function show(module){
					$('#new_template_popup',context).css('display', 'block');
					$('#new_template_popup',context).css('z-index',findZMax()+1);	//crmv@26986
					center($('#new_template_popup',context));
				}
				
				$('#new_template_popup_save',context).click(function(){
					var messageBoxPopup = MessageBoxPopup();
				
					if(trim(this.form.title.value) == '') {
						messageBoxPopup.show();
						$('#'+ 'empty_fields_message',context).show();
						return false;
					}
				});
		
				$('#new_template_popup_close',context).click(close);
				$('#new_template_popup_cancel',context).click(close);
				return {
					close:close,show:show
				};
			}
			var newTemplatePopup = NewTemplatePopup();
		
			var operations = function(){
				//crmv@36510 crmv@128159 crmv@166678
				var op = {
					string:["is", "is not", "contains", "does not contain", "starts with", "ends with", "has changed"],
					number:["equal to", "does not equal", "less than", "greater than", "less than or equal to", "greater than or equal to", "has changed"],
					date:["equal to", "does not equal", "less than", "greater than", "less than or equal to", "greater than or equal to", "has changed"],
					value:['is','is not', "has changed"],
					multipicklist:['is','is not','contains','does not contain'],
					reference:["is", "is not", "contains", "does not contain", "starts with", "ends with", "has changed"],	//crmv@158293
					table:["has exactly", "has less than", "has more than"],
					sdk:['is','is not',"contains","does not contain","starts with","ends with","less than","greater than","less than or equal to","greater than or equal to"]
				};
				var mapping = [
					['string', ['string', 'text', 'url', 'email', 'phone', 'picklistmultilanguage']],	//crmv@120039
					['number', ['integer', 'double','currency']],
					['date', ['datetime', 'date', 'time']],
					['value', ['reference', 'picklist', 'boolean']],
					['multipicklist', ['multipicklist']],
					['reference', ['reference']],
					['table', ['table']],
					['sdk', ['sdk']]
				];
				//crmv@36510e crmv@128159e crmv@166678e
			  	var out = {};
				$.each(mapping, function(i, v){
					var opName = v[0];
					var types = v[1];
					$.each(types, function(i, v){
						out[v] = op[opName];
					});
				});
				return out;
			}();
		
			function defaultValue(fieldType){
		
				function forPicklist(opType, condno){
					var value = $("#save_condition_"+condno+"_value",context);
					var options = implode('',
						map(function (e){return '<option value="'+e.value+'">'+e.label+'</option>';},
							opType['picklistValues'])
					);
					value.replaceWith('<select id="save_condition_'+condno+'_value" class="detailedViewTextBox value">'+
														options+'</select>');
				}
		
				function forInteger(opType, condno){
					var value = $(format("#save_condition_%s_value", condno),context);
					value.replaceWith(format('<input type="text" id="save_condition_%s_value" '+
																	 'value="0" class="detailedViewTextBox value">', condno));
				}
				//crmv@36510
				var functions = {
					string:function(opType, condno){
						var value = $(format("#save_condition_%s_value", condno),context);
						value.replaceWith(format('<input type="text" id="save_condition_%s_value" '+
																		 'value="" class="detailedViewTextBox value">', condno));
					},
					'boolean': function(opType, condno){
						var value = $("#save_condition_"+condno+"_value",context);
						value.replaceWith(
							'<select id="save_condition_'+condno+'_value" value="true" class="detailedViewTextBox value"> \
								<option value="true:boolean">'+alert_arr.YES+'</option>\
								<option value="false:boolean">'+alert_arr.NO+'</option>\
							</select>');
					},
					integer: forInteger,
					picklist:forPicklist,
					multipicklist:forPicklist,
					date: forInteger
				};
				//crmv@36510 e
				var ret = functions[fieldType];
				if(ret==null){
					ret = functions['string'];
				}
				return ret;
			}
		
			var format = fn.format;
		
		
			function fillOptions(el,options,append){
				el.empty();
				if (typeof(append) != 'undefined') {
					el.append(append);
				}
				$.each(options, function(k, v){
					el.append('<option value="'+k+'">'+v+'</option>');
				});
			}
		
			function resetFields(opType, condno){
				if (opType == '' || opType == null) return; // crmv@119564
				var ops = $("#save_condition_"+condno+"_operation",context);
				var selectedOperations = operations[opType.name];
				var l = {};
				var labels = {
					'is':'EQUALS',
					'equal to':'EQUALS',
					'is not':'NOT_EQUALS_TO',
					'does not equal':'NOT_EQUALS_TO',
					'has changed':'HAS_CHANGED',	//crmv@56962
					'contains':'CONTAINS',
					'does not contain':'DOES_NOT_CONTAINS',
					'starts with':'STARTS_WITH',
					'ends with':'ENDS_WITH',	//crmv@56962
					'less than':'LESS_THAN',
					'greater than':'GREATER_THAN',
					'less than or equal to':'LESS_OR_EQUALS',
					'greater than or equal to':'GREATER_OR_EQUALS',
					'has exactly': 'HAS_EXACTLY_ROWS',
					'has less than': 'HAS_LESS_ROWS',
					'has more than': 'HAS_MORE_ROWS'
				};
				for(var i=0; i<selectedOperations.length; i++){
					value = selectedOperations[i];
					transl = alert_arr[labels[value]];
					if (transl == undefined) transl = value;
					l[value] = transl;
				}
				fillOptions(ops, l);
				defaultValue(opType.name)(opType, condno);
				// change the rows label
				$('#rows_label_'+condno, context)[opType.name == 'table' ? 'show' : 'hide']();
			}
			function resetTableFields(opType, condno, condition){
				if (opType == '') return;
				if (typeof(me.otherParams['dynaFormConditional']) != 'undefined' && me.otherParams['dynaFormConditional']) var dynaFormConditional = true; else var dynaFormConditional = false;
				var fieldname = $("#save_condition_"+condno+"_fieldname",context).val();
				var ops = $("#save_condition_"+condno+"_tabfieldopt",context);
				var l = {};
				var selectedOperations = {
					//'':'LBL_SELECT',
					'sum':'LBL_TABLEFIELD_SUM',
					'min':'LBL_TABLEFIELD_MIN',
					'max':'LBL_TABLEFIELD_MAX',
					'average':'LBL_TABLEFIELD_AVERAGE',
					//crmv@121616
					'all':'LBL_TABLEFIELD_ALL',
					'at_least_one':'LBL_TABLEFIELD_AT_LEAST_ONE',
					//crmv@121616e
					'last':'LBL_TABLEFIELD_LAST_VALUE',
					'seq':'LBL_TABLEFIELD_SEQUENCE'
				};
				if (dynaFormConditional) selectedOperations = {'curr':'LBL_TABLEFIELD_CURR_VALUE'};
				$.each(selectedOperations, function(k,v){
					l[k] = alert_arr[v];
				});
				fillOptions(ops, l);
				if (!dynaFormConditional) ops.parent().show();
				
				if (typeof(condition) != 'undefined') {
					$(format("#save_condition_%s_tabfieldopt", condno),context).val(condition['tabfieldopt']);
					$(format("#save_condition_%s_tabfieldseq", condno),context).val(condition['tabfieldseq']);
				}
				
				ops.unbind('change');
				ops.bind('change', function(){
					if (this.value == 'seq') {
						$("#save_condition_"+condno+"_tabfieldseq",context).parent().show();
					} else {
						$("#save_condition_"+condno+"_tabfieldseq",context).parent().hide();
					}
					if (this.value == 'seq' || this.value == 'last' || this.value == 'curr') {
						resetFields(opType, condno);
					} else {
						resetFields({name:"integer",typeofdata:"I~O"}, condno);
					}
				});
				ops.change();
			}
		
			function removeConditionGroup(groupno) {
				if ($('#group_condition_'+groupno).parent().next().next().length == 0) $('#group_condition_'+groupno).parent().prev().find('.group_glue').hide();
				$(format("#group_condition_%s", groupno),context).parent().remove();
				$(format("#group_condition_%s_glue", groupno),context).parent().remove();
			}
			function removeCondition(groupno,condno){
				$(format("#save_condition_%s", condno),context).remove();
				$('#group_condition_'+groupno+' .save_conditions div').last().find('.glue').hide();
			}
		
			//Convert user type into reference for consistency in describe objects
			//This is done inplace
			function referencify(desc){
			  var fields = desc['fields'];
			  for(var i=0; i<fields.length; i++){
				var field = fields[i];
				var type = field['type'];
				if(type['name']=='owner'){
				  type['name']='reference';
				  type['refersTo']=['Users'];
				}
			  }
			  return desc;
			}
		
			function getDescribeObjects(accessibleModules, moduleName, callback){
				var callbackDescribe = handleError(function(result){
					//crmv@109851
					var parent = referencify(result);	//crmv@109683
					var relatedModules = [];
					if (me.relatedFields != 0) { //crmv@158293
						var fields = parent['fields'];
						var referenceFields = filter(function(e){return e['type']['name']=='reference';}, fields);
						var referenceFieldModules =
							map(function(e){ return e['type']['refersTo'];},
								referenceFields
							);
						function union(a, b){
							var newfields = filter(function(e){return !contains(a, e);}, b);
							return a.concat(newfields);
						}
						relatedModules = reduceR(union, referenceFieldModules, []);	//crmv@108078 skip duplicate call
						
						// Remove modules that is no longer accessible
						relatedModules = diff(accessibleModules, relatedModules);
					}
					if (relatedModules.length == 0) {
						relatedModules.push(moduleName);
					}
					//crmv@109851e
					function executer(parameters){
						var failures = filter(function(e){return e[0]==false;}, parameters);
						if(failures.length!=0){
							var firstFailure = failures[0];
							callback(false, firstFailure[1]);
						}else{
							var moduleDescriptions = map(function(e){return e[1];}, parameters);
							var modules = dict(map(function(e){return [e['name'], referencify(e)];}, moduleDescriptions));
							modules[parent['name']] = result;	//crmv@108078 skip duplicate call
							callback(true, modules);
						}
					}
					var p = parallelExecuter(executer, relatedModules.length);
					$.each(relatedModules, function(i, v){
						// crmv@104180
						if (v == 'DynaForm') {
							p(function(callback){vtinst.describeDynaForm(me.otherParams['processmakerId'], me.otherParams['metaId'], {}, callback);});
						} else if (v == 'TableField') {
							p(function(callback){vtinst.describeTableField(me.otherParams['processmakerId'], me.otherParams['metaId'], me.otherParams['fieldName'], me.otherParams, callback);});
						} else {
							p(function(callback){vtinst.describeObject(v, callback);});
						}
						// crmv@104180e
					});
				});
				// crmv@104180
				if (moduleName == 'DynaForm') {
					vtinst.describeDynaForm(me.otherParams['processmakerId'], me.otherParams['metaId'], {}, callbackDescribe);
				} else if (moduleName == 'TableField') {
					vtinst.describeTableField(me.otherParams['processmakerId'], me.otherParams['metaId'], me.otherParams['fieldName'], me.otherParams, callbackDescribe);
				} else {
					vtinst.describeObject(moduleName, callbackDescribe);
				}
				// crmv@104180e
			}
		
			var table_fields_columns = {};
			
			$(document).ready(function(){
				fieldValidator = new VTFieldValidator($('#edit_workflow_form',context));
				fieldValidator.mandatoryFields = ["description"];
				pageLoadingPopup.show();
				vtinst.extendSession(handleError(function(result){
					vtinst.listTypes(handleError(function(accessibleModules) {
						accessibleModules.push('DynaForm');	// add DynaForm to accessible modules list
						accessibleModules.push('TableField'); // crmv@104180
						getDescribeObjects(accessibleModules, moduleName, handleError(function(modules){
							var parent = modules[moduleName];
							table_fields_columns = {};
							function filteredFields(fields){
								return filter(
						  			function(e){
						  				if (e.type.name == 'table' && e.columns) {
						  					table_fields_columns[e.name] = e.columns;
						  				}
						  				if (me.relatedFields == 1) var filter = ['reference', 'autogenerated', 'owner', 'password']; //crmv@158293
										else var filter = ['autogenerated', 'owner', 'password'];
						  				return !contains(filter, e.type.name);
						  			}, fields
						 		);
							};
							var parentFields = map(function(e){return[e['name'],e['label']];}, filteredFields(parent['fields']));
							var referenceFieldTypes = filter(function(e){return (e['type']['name']=='reference' && !(e['name'].indexOf('::') > -1))}, parent['fields']);	//crmv@131239 TODO skip reference of tables
							var moduleFieldTypes = {};
		
							$.each(modules, function(k, v){
								moduleFieldTypes[k] = dict(map(function(e){return [e['name'], e['type']];},
									filteredFields(v['fields'])));
							});
			
							function getFieldType(fullFieldName){
								if (fullFieldName == '') return '';
								if (fullFieldName.indexOf('sdk:') == 0) return {'name':'sdk'};
								
								var group = fullFieldName.match(/(\w+) : \((\w+)\) (\w+)/);
								if(group==null){
									var fieldModule = moduleName;
									var fieldName = fullFieldName;
								}else{
									var fieldModule = group[2];
									var fieldName = group[3];
								}
								return moduleFieldTypes[fieldModule][fieldName];
							}
			
							function fieldReferenceNames(referenceField){
								var name = referenceField['name'];
								var label = referenceField['label'];
								function forModule(moduleName){
									// If module is not accessible return no field information
									if(!contains(accessibleModules, moduleName)) return [];
						
									return map(function(field){
										return [name+' : '+'('+moduleName+') '+field['name'], label+' : '+'('+moduleName+') '+field['label']];},
										filteredFields(modules[moduleName]['fields'])
									);
								}
								return reduceR(concat, map(forModule,referenceField['type']['refersTo']),[]);
							}
			
							if (me.relatedFields != 0) { //crmv@158293
								var referenceFields = reduceR(concat, map(fieldReferenceNames, referenceFieldTypes), []);
							} else {
								var referenceFields = [];
							}
							var fieldLabels = dict(parentFields.concat(referenceFields));
									
							function addSubGroup(calculate_newgroupno,autoaddcondition,parentgroupno,new_groupno) {
								if (calculate_newgroupno) {
									groupno++;
									var new_groupno = groupno;
								}
								$("#group_condition_"+parentgroupno,context).after(
									'<div style="margin-bottom: 5px; margin-left: 10px;"> \
										<div id="group_condition_'+new_groupno+'" class="group_condition"> \
											<input type="hidden" class="parentgroup" id="parentgroup_'+new_groupno+'" value="'+parentgroupno+'"> \
											<div class="small"> \
												<div style="padding-top:5px">'+me.subGroupLabel+'</div> \
												<div class="save_conditions"></div> \
												<input type="button" class="crmButton create small" value="'+me.subGroupAddTitle+'" id="save_conditions_add_'+new_groupno+'"/> \
											</div> \
											<div style="text-align:center" style="margin-top: 5px"> \
												<select id="group_condition_'+new_groupno+'_glue" class="group_glue" style="display:none"><option value="and">'+alert_arr.LBL_AND+'</option><option value="or">'+alert_arr.LBL_OR+'</option></select> \
											</div> \
										</div> \
									</div>'
								);
								if (autoaddcondition) addCondition(new_groupno,condno++);
								$("#save_conditions_add_"+new_groupno,context).unbind('click');
								$("#save_conditions_add_"+new_groupno,context).bind("click", function(){
									addCondition(new_groupno,condno++);
								});
								$('#enable_subgroup_'+parentgroupno,context).val(new_groupno);
							}
							function addConditionGroup(calculate_newgroupno,autoaddcondition,new_groupno) {
								if (calculate_newgroupno) {
									groupno++;
									var new_groupno = groupno;
								}
								if (typeof(autoaddcondition) == 'undefined') var autoaddcondition = false;
								var labelHtml = '', subGroupHtml = '';
								if (me.groupLabel) {
									labelHtml = '<span class="dvtCellLabel" style="float:left; padding-right:5px; padding-top:5px;">'+alert_arr.LBL_LABEL+'</span><div class="dvtCellInfo" style="float:left; width:250px;"><input class="detailedViewTextBox group_label" type="text" id="group_condition_'+new_groupno+'_label" value=""></div>';
								}
								if (me.subGroup) {
									subGroupHtml = '<div style="padding-top:10px"><input type="checkbox" id="enable_subgroup_'+new_groupno+'"><label for="enable_subgroup_'+new_groupno+'">&nbsp;'+alert_arr.LBL_KANBAN_DRAG_HERE+'</label></div>';
								}
								$("#"+container,context).append(
									'<div class="crmTable" style="padding:10px; margin-bottom:5px"> \
										<div id="group_condition_'+new_groupno+'" class="group_condition"> \
											<div class="small"> \
												<table cellpadding="0" cellspacing="0" width="100%"><tr> \
													<td align="left" style="padding:5px">'+labelHtml+'</td> \
													<td align="right"><a href="javascript:;"><i class="vteicon" id="group_condition_'+new_groupno+'_remove">highlight_remove</i></a></td> \
												</tr></table> \
												<div class="save_conditions"></div> \
												<input type="button" class="crmButton create small" value="'+alert_arr.LBL_NEW_CONDITION_BUTTON_LABEL+'" id="save_conditions_add_'+new_groupno+'"/> \
											</div> \
											'+subGroupHtml+' \
										</div> \
									</div> \
									<div class="dvtCellInfo" style="text-align:center; margin-top:5px; margin-bottom:5px; width: 50px; margin: 5px auto;"> \
										<select id="group_condition_'+new_groupno+'_glue" class="detailedViewTextBox group_glue" style="display:none"><option value="and">'+alert_arr.LBL_AND+'</option><option value="or">'+alert_arr.LBL_OR+'</option></select> \
									</div>'
								);
								if (me.groupGlue) {
									var prevGroupGlue = $("#group_condition_"+new_groupno,context).parent().prev().find('.group_glue');
									prevGroupGlue.show();
									if (prevGroupGlue.val() == null || prevGroupGlue.val() == '') prevGroupGlue.val(prevGroupGlue.find("option:first").val());
								}
								if (me.subGroup) {
									$('#enable_subgroup_'+new_groupno).change(function(){
										if (this.checked) {
											addSubGroup(true,true,new_groupno);
										} else {
											removeConditionGroup(this.value);
										}
									});
								}
								if (autoaddcondition) addCondition(new_groupno,condno++);
								$("#save_conditions_add_"+new_groupno,context).unbind('click');
								$("#save_conditions_add_"+new_groupno,context).bind("click", function(){
									addCondition(new_groupno,condno++);
								});
								$('#group_condition_'+new_groupno+'_remove',context).unbind('click');
								$('#group_condition_'+new_groupno+'_remove',context).bind("click", function(){
									if (me.subGroup) {
										var subGroup = $('#enable_subgroup_'+new_groupno,context).val();
										if (typeof(subGroup) != 'undefined') removeConditionGroup(subGroup);
									}
									removeConditionGroup(new_groupno);
								});
							}
							function addCondition(groupno,condno){
								if ($("#"+container+" #group_condition_"+groupno+" .parentgroup",context).length == 0) var isSubGroup = false; else var isSubGroup = true;
								if (isSubGroup && !me.subGroupOperation) var subGroupOperationStyle = 'display:none'; else var subGroupOperationStyle = '';
								if (isSubGroup && !me.subGroupGlue) var subGroupGlueStyle = 'display:none'; else var subGroupGlueStyle = '';
							
								$("#"+container+" #group_condition_"+groupno+" .save_conditions",context).append(
									'<div id="save_condition_'+condno+'" style=\'margin-bottom: 5px\'> \
										<div class="dvtCellInfo" style="float:left; padding-right:10px;"> \
											<select id="save_condition_'+condno+'_fieldname" class="detailedViewTextBox fieldname"></select> \
										</div> \
										<div class="dvtCellInfo" style="float:left; padding-right:10px; display:none"> \
											<select id="save_condition_'+condno+'_tabfieldopt" class="detailedViewTextBox tabfieldopt"></select> \
										</div> \
										<div class="dvtCellInfo" style="float:left; padding-right:10px; display:none"> \
											<input id="save_condition_'+condno+'_tabfieldseq" class="detailedViewTextBox tabfieldseq" type="text" size="3"></select> \
										</div> \
										<div class="dvtCellInfo" style="float:left; padding-right:10px; '+subGroupOperationStyle+'"> \
											<select id="save_condition_'+condno+'_operation" class="detailedViewTextBox operation"></select> \
										</div> \
										<div class="dvtCellInfo" style="float:left; padding-right:10px;"> \
											<input type="text" id="save_condition_'+condno+'_value" class="detailedViewTextBox value"> \
										</div> \
										<div class="dvtCellInfo" id="rows_label_'+condno+'" style="float:left; padding-right:10px; display:none"> \
										'+alert_arr.LBL_ROWS+'\
										</div> \
										<div class="dvtCellInfo" style="float:left; padding-right:10px; '+subGroupGlueStyle+'"> \
											<select id="save_condition_'+condno+'_glue" class="detailedViewTextBox glue" style="display:none"><option value="and">'+alert_arr.LBL_AND+'</option><option value="or">'+alert_arr.LBL_OR+'</option></select> \
										</div> \
										<span id="save_condition_'+condno+'_remove" class="link remove-link"><a href="javascript:;"><i class="vteicon">delete</i></a></span> \
									</div>'
								);
								var fe = $("#save_condition_"+condno+"_fieldname",context);
								var i = 1;
								
								var fieldLabelsOpt = {};
								if (isSubGroup && me.subGroupRelatedFields == false) {
									jQuery.each(fieldLabels, function(k,v){ fieldLabelsOpt[k] = v; });
									jQuery.each(referenceFieldTypes, function(k,v){ delete fieldLabelsOpt[v.name]; });
								} else {
									fieldLabelsOpt = fieldLabels;
								}
								fillOptions(fe, fieldLabelsOpt, $('#sdk_custom_functions').html());
			
								var fullFieldName = fe.val();
								resetFields(getFieldType(fullFieldName), condno);
								
								var prev_glue = $("#save_condition_"+condno+"_glue",context).parent().parent().prev().find('.glue');
								prev_glue.show();
								if (prev_glue.val() == null || prev_glue.val() == '') prev_glue.val(prev_glue.find("option:first").val());
			
								var re = $("#save_condition_"+condno+"_remove",context);
								re.unbind('click');
								re.bind("click", function(){
									removeCondition(groupno,condno);
								});
			
								fe.unbind('change');
								fe.bind("change", function(){
									var select = $(this);
									var condNo = select.attr("id").match(/save_condition_(\d+)_fieldname/)[1];
									var fullFieldName = $(this).val();
									if (fullFieldName.indexOf('::') > -1)
										resetTableFields(getFieldType(fullFieldName), condNo);
									else {
										$("#save_condition_"+condno+"_tabfieldopt",context).parent().hide();
										resetFields(getFieldType(fullFieldName), condNo);
									}
								});
							}
			
							var newTemplatePopup = NewTemplatePopup();
							$("#new_template",context).click(function(){
								newTemplatePopup.show();
							});
			
							var groupno=0;
							var condno=0;
							if(conditions){
								$.each(conditions, function(j, group_condition){
									if (typeof(group_condition["parentgroup"]) != 'undefined') {
										addSubGroup(false,false,group_condition["parentgroup"],groupno);
										$('#enable_subgroup_'+group_condition["parentgroup"],context).prop('checked',true);
									} else {
										addConditionGroup(false,false,groupno);
									}
									$(format("#group_condition_%s_glue", groupno),context).val(group_condition["glue"]);
									if (me.groupLabel) $(format("#group_condition_%s_label", groupno),context).val(group_condition["label"]);
									$.each(group_condition['conditions'], function(i, condition){
										var fieldname = condition["fieldname"];
										addCondition(groupno,condno);
										//crmv@120039
										if ($("#"+format("save_condition_%s_fieldname", condno)+" option[value='"+fieldname+"']",context).length == 0) {
											removeCondition(groupno,condno);
										} else {
											$(format("#save_condition_%s_fieldname", condno),context).val(fieldname);
											if (fieldname.indexOf('::') > -1) {
												resetTableFields(getFieldType(fieldname), condno, condition);
											} else {
												resetFields(getFieldType(fieldname), condno);
											}
											$(format("#save_condition_%s_operation", condno),context).val(condition["operation"]);
											$('#dump',context).html(condition["value"]);
											var text = $('#dump',context).text();
											$(format("#save_condition_%s_value", condno),context).val(text);
											$(format("#save_condition_%s_glue", condno),context).val(condition["glue"]);
											condno+=1;
										}
										//crmv@120039e
									});
									groupno+=1;
								});
							}
							
							$("#group_conditions_add",context).unbind('click');
							$("#group_conditions_add",context).bind("click", function(){
								addConditionGroup(true,true);
							});
							$('#group_conditions_add',context).show();
							pageLoadingPopup.close();
							
							if (typeof(initCallback) == 'function') initCallback();
						}));
					}));
				}));
			});
		},
		getJson: function($,container,context){
			var me = this;
			var group_conditions = [];
			if (me.subGroup) var group_key_map = {};
			$("#"+container+" .group_condition",context).each(function(j){
				var group = $(this);
				if (me.subGroup) {
					var match = group.attr('id').match(/[0-9]+/);
					var groupKey = match[0];
					group_key_map[groupKey] = j;
				}
				var conditions = [];
				group.find('.save_conditions').children().each(function(i){
					var fieldname = $(this).find(".fieldname").val();
					var operation = $(this).find(".operation").val();
					var value = $(this).find(".value").val();
					var condition = {fieldname:fieldname, operation:operation, value:value};
					if (fieldname.indexOf('::') > -1) {
						condition['tabfieldopt'] = $(this).find(".tabfieldopt").val();
						condition['tabfieldseq'] = $(this).find(".tabfieldseq").val();
					}
					//if ($(this).children(".glue").is(':visible')) condition['glue'] = $(this).children(".glue").val();
					if ($(this).find(".glue").css('display') != 'none') condition['glue'] = $(this).find(".glue").val();
					conditions[i]=condition;
				});
				group_conditions[j] = {conditions:conditions, glue:group.parent().next().find('.group_glue').val()};
				if (me.groupLabel) {
					group_conditions[j]['label'] = group.find('.group_label').val();
				}
				if (me.subGroup) {
					group_conditions[j]['parentgroup'] = group_key_map[group.find('.parentgroup').val()];
				}
			});
			if(group_conditions.length==0){
			  var out = "";
			}else{
			  var out = JSON.stringify(group_conditions);
			}
			//$("#save_conditions_json",context).val(out);
			return out;
		}
	}
}