{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@96584 crmv@101506 crmv@103450 crmv@112539 crmv@112297 crmv@150751 crmv@188364 crmv@193096 *}

<script src="{"modules/Settings/ProcessMaker/resources/ProcessMakerScript.js"|resourcever}" type="text/javascript"></script>
<script src="modules/Settings/ProcessMaker/thirdparty/bpmn-js/dist/bpmn-viewer.development.js"></script> {* crmv@187568 *}
<link rel="stylesheet" type="text/css" href="themes/{$THEME}/processmaker.css" /> {* crmv@154044 *}

<div id="{if $MODE eq 'graph'}ProcessGraph{elseif $MODE eq 'history'}ProcessHistory{/if}" class="detailTabsMainDiv vte-card" style="display:none">
	{if $MODE eq 'graph' && $ENABLE_ROLLBACK}
	<div style="float:right">
		<input type="hidden" id="running_process_active" value="{if $RUNNING_PROCESS_ACTIVE}1{else}0{/if}">
		<button title="{'LBL_CHANGE_POSITION'|getTranslatedString:'Processes'}" class="crmbutton small save" id="rollback_btn_3" style="float:right; {if $RUNNING_PROCESS_ACTIVE}display:none{else}display:block{/if}">{'LBL_CONTINUE_EXECUTION'|getTranslatedString:'Processes'}</button>
		<button title="{'LBL_CHANGE_POSITION'|getTranslatedString:'Processes'}" class="crmbutton small cancel" onclick="ProcessScript.changeRollbackMode(false);" id="rollback_btn_2" style="float:right; display:none">{'LBL_CANCEL_BUTTON_LABEL'|getTranslatedString}</button>
		<button title="{'LBL_CHANGE_POSITION'|getTranslatedString:'Processes'}" class="crmbutton small edit" onclick="ProcessScript.changeRollbackMode(true);" id="rollback_btn_1" style="float:right">{'LBL_CHANGE_POSITION'|getTranslatedString:'Processes'}</button>
	</div>
	{/if}
	<table>
		{if !empty($SELECTION_PROCESSES)}
		<tr>
			<td width="20"></td>
			<td>{include file="FieldHeader.tpl" label='SINGLE_Processes'|getTranslatedString:'Processes'}</td>
			<td nowrap>
				<div class="dvtCellInfo" style="{if $SELECTION_PROCESSES|@count eq 1}display:none{/if}">
					<select id="{if $MODE eq 'graph'}selectProcessGraph{elseif $MODE eq 'history'}selectProcessHistory{/if}" class="detailedViewTextBox" onchange="{if $MODE eq 'graph'}ProcessScript{elseif $MODE eq 'history'}ProcessHistoryScript{/if}.showGraph(this.value)">
						{foreach item=arr from=$SELECTION_PROCESSES}
							<option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
						{/foreach}
					</select>
				</div>
				{if $SELECTION_PROCESSES|@count eq 1}
					{$arr[0]}
				{/if}
			</td>
		</tr>
		{/if}
		{if $MODE eq 'graph' && !empty($USERS_LIST)}
		<tr>
			<td width="20"></td>
			<td>{include file="FieldHeader.tpl" label='LBL_SELECT_ACTOR'|getTranslatedString:'Processes'}</td>
			<td nowrap>
				<div class="dvtCellInfo">
					<select id="executers" class="detailedViewTextBox">
						{foreach key=key_one item=arr from=$USERS_LIST}
							{foreach key=sel_value item=value from=$arr}
								<option value="{$key_one}" {$value}>{$sel_value}</option>
							{/foreach}
						{/foreach}
					</select>
				</div>
			</td>
		</tr>
		{/if}
	</table>
	<br>
	{if $MODE eq 'graph'}
	<div id="canvas"></div>
	{elseif $MODE eq 'history'}
	<div id="processChangeLog"></div>
	{/if}
</div>

{if $MODE eq 'graph'}
{literal}
<script>
bpmnLoad = function(BpmnViewer, data, callback) {
	var processmaker = data['processmaker'],
		processesid = data['processesid']
		current_elementid = data['current_elementid'],
		xml_version = data['xml_version'],
		info = data['info'],
		executers = data['executers'],
		marked_executers = [];
		
	jQuery('#rollback_btn_3').click(function(){
		ProcessScript.continueExecution(processesid);
	});
	
	// create viewer
	var bpmnViewer = new BpmnJS({ // crmv@187568
		container: '#canvas'
	});

	// import function
	function importXML(xml) {

		// import diagram
		bpmnViewer.importXML(xml, function(err) {
			if (err) console.error('could not import BPMN 2.0 diagram', err);
	
			var canvas = bpmnViewer.get('canvas'),
				overlays = bpmnViewer.get('overlays'),
				elementRegistry = bpmnViewer.get('elementRegistry');
	
			// zoom to fit full viewport
			canvas.zoom('fit-viewport');

			jQuery.each(elementRegistry.getAll(), function(index, object) {
			    if (object.constructor.name == 'Shape') {
			    	var id = object.id,
			    		type = ProcessMakerScript.formatType(object.type),
			    		dom_obj = jQuery('[data-element-id='+id+']');
			    	
				    if (type == 'Participant' || type == 'Lane' || type == 'TextAnnotation' || type == 'ParallelGateway') return;

				    if (current_elementid.indexOf(id) > -1) {
				    	canvas.toggleMarker(id, 'current-shape');
				    }
				    
			    	dom_obj
				    	.css('cursor','pointer')
				    	.hover(function(){
				    			if (ProcessScript.mode == 'rollback' || (ProcessScript.mode == 'view' && jQuery(info[id]).length > 0)) {
			    					canvas.toggleMarker(id, 'highlights-shape');
			    				}
				    		}, function(){
				    			if (ProcessScript.mode == 'rollback' || (ProcessScript.mode == 'view' && jQuery(info[id]).length > 0)) {
			    					canvas.toggleMarker(id, 'highlights-shape');
			    				}
				    		})
				    	.click(function(){
				    		if (ProcessScript.mode == 'rollback') {
				    			ProcessScript.changePosition(processesid,id);
				    		} else if (ProcessScript.mode == 'view' && jQuery(info[id]).length > 0) {
				    		
			    				if (jQuery('#activityInfo').length == 0) jQuery('#ProcessGraph').append('<div id="activityInfo" class="crmvDiv" style="position:absolute;display:none"><div id="activityInfoContent"></div><div class="closebutton" onclick="jQuery(\'#activityInfo\').hide();"></div></div>');
			    				jQuery('#activityInfo').hide();
			    				
			    				var html = '<div class="layerPopup" style="padding:5px">';
			    				
			    				// TODO move all this shit to modules/Processes/HistoryDetail.tpl
			    				
			    				// condition
			    				if (jQuery(info[id]['condition']).length > 0) {
			    					var condition = info[id]['condition'];
			    					html += '<table cellpadding="5" cellspacing="5" border="0">'
										+'<tr><td>';
									if (condition['module'] != null && condition['module'] != '') html += condition['module'];
									else if (condition['related_to_name'] != null && condition['related_to_name'] != '') 
										html += '{/literal}{'LBL_CONDITION_ON'|getTranslatedString:'Processes'}{literal} <a href="'+condition['related_to_url']+'" title="'+condition['related_to_module']+'">'+condition['related_to_name']+'</a> ('+condition['related_to_module']+'). ';
									if (condition['execution_condition'] != null && condition['execution_condition'] != '') html += condition['execution_condition'];
									if (condition['condition'] != null && condition['condition'] != '') html += ' {/literal}{$APP.LBL_WHEN|strtolower}{literal}: '+condition['condition'].join(' '); else html += '.';
									html += '</td></tr>'
										+'</table>'
										+'<div style="height:5px"></div>';
			    				}
			    				// actions
			    				if (jQuery(info[id]['actions']).length > 0) {
			    					html += '<table cellpadding="5" cellspacing="5" border="0">'
										+'<tr><td><b>{/literal}{'LBL_PM_ACTIONS'|getTranslatedString:'Settings'}{literal}</b></td></tr>';
									jQuery(info[id]['actions']).each(function(k,action){
										html += '<tr><td nowrap>'+action['title'];
										if (action['related_to_name'] != null && action['related_to_name'] != '')
											html += ':&nbsp;<a href="'+action['related_to_url']+'" title="'+action['related_to_module']+'">'+action['related_to_name']+'</a> ('+action['related_to_module']+')';
										if (action['delete_perm'])
											html += '&nbsp<a href="javascript:;" onclick="ProcessScript.deleteRecord(\''+processesid+'\',\''+id+'\',\''+action['module']+'\',\''+action['crmid']+'\',this)"><i class="vteicon md-sm" title="{/literal}{'LBL_DELETE'|getTranslatedString}{literal}" style="vertical-align:middle">clear</i></a>';
										html += '</td></tr>';
									});
									html += '</table>'
										+'<div style="height:5px"></div>';
			    				}
			    				// process helper
			    				if (jQuery(info[id]['process_helper']).length > 0) {
			    					var ph = info[id]['process_helper'];
									html += '<table cellpadding="5" cellspacing="5" border="0">'
										+'<tr><td><b>{/literal}{'LBL_PROCESS_HELPER'|getTranslatedString:'Settings'}{literal}</b></td></tr>'
										+'</table>';
									html += '<table cellpadding="5" cellspacing="5" border="0"><tr>'
			    						+'<td nowrap><img src="'+ph['userimg']+'" alt="'+ph['username']+'" title="'+ph['username']+'" class="userAvatar"></td>'
										+'<td nowrap>'
										+'<div><b>'+ph['username']+'</b> '+ph['action_label']+' '+ph['description']+'</div>';
									if (ph['related_to_name'] != null && ph['related_to_name'] != '')
										html += '<div>{/literal}{'LBL_ABOUT'|getTranslatedString:'ModComments'}{literal}&nbsp;<a href="'+ph['related_to_url']+'" title="'+ph['related_to_module']+'">'+ph['related_to_name']+'</a> ('+ph['related_to_module']+')</div>';
									html += '</td></tr></table>';
									html += '</table>'
										+'<div style="height:5px"></div>';
			    				}
			    				// subprocess
			    				if (jQuery(info[id]['subprocess']).length > 0) {
			    					var subprocess = info[id]['subprocess'];
			    					html += '<table cellpadding="5" cellspacing="5" border="0">'
										+'<tr><td>'+subprocess['str']+' ';
									if (subprocess['link'] != null && subprocess['link'] != '')
										html += '<a href="'+subprocess['link']+'">'+subprocess['name']+'</a>';
									else html += subprocess['name'];
									html += '</td></tr>'
										+'</table>'
										+'<div style="height:5px"></div>';
			    				}
			    				// delay
			    				if (jQuery(info[id]['delay']).length > 0) {
			    					var delay = info[id]['delay'];
			    					html += '<table cellpadding="5" cellspacing="5" border="0">'
										+'<tr><td nowrap>'+delay['str']+'</td></tr>'
										+'</table>'
										+'<div style="height:5px"></div>';
			    				}
			    				// start
			    				if (jQuery(info[id]['start']).length > 0) {
			    					var start = info[id]['start'];
			    					html += '<table cellpadding="5" cellspacing="5" border="0">'
										+'<tr><td nowrap>'+start['str']+'</td></tr>'
										+'</table>'
										+'<div style="height:5px"></div>';
			    				}
			    				// gateway
			    				if (jQuery(info[id]['gateway']).length > 0) {
			    					html += '<table cellpadding="5" cellspacing="5" border="0">'
									jQuery(info[id]['gateway']).each(function(k,action){
										html += '<tr><td nowrap>'+action['label']+' <b>'+action['to']+'</b></td></tr>';
									});
									html += '</table>'
										+'<div style="height:5px"></div>';
			    				}
			    				// logs
			    				if (jQuery(info[id]['logs']).length > 0) {
				    				html += '<table cellpadding="5" cellspacing="5" border="0">'
										+'<tr><td><b>{/literal}{'LBL_PM_LOGS'|getTranslatedString:'Settings'}{literal}</b></td></tr>'
										+'</table>';
				    				jQuery(info[id]['logs']).each(function(k,log){
				    					//crmv@177598
				    					if (log['type'] == 'etc') {
				    						html += '<table cellpadding="5" cellspacing="5" border="0"><tr><td nowrap style="padding: 5px 35px">'+log['other_label']+'</td></tr></table>';
				    					} else {
				    					//crmv@177598e
				    						if (log['type'] == 'start') var label = "{/literal}{'LBL_LOG_START_ACTIVITY'|getTranslatedString:'Processes'}{literal}";
					    					else if (log['type'] == 'end') var label = "{/literal}{'LBL_LOG_END_ACTIVITY'|getTranslatedString:'Processes'}{literal}";
					    					if (log['rollbck'] == 1) var rollback_label = ' ({/literal}{'LBL_MANUAL_CHANGED_POSITION'|getTranslatedString:'Processes'}{literal})'; else var rollback_label = '';
					    					html += '<table cellpadding="5" cellspacing="5" border="0"><tr>'
					    						+'<td nowrap><img src="'+log['userimg']+'" alt="'+log['username']+'" title="'+log['username']+'" class="userAvatar"></td>'
												+'<td nowrap>'
												+'<div><b>'+log['username']+'</b> '+label+' <b>'+log['prev_elementid_title']+'</b>'+rollback_label+'</div>'
												+'<div><a href="javascript:;" title="'+log['logtime']+'" style="color: gray; text-decoration: none;">'+log['friendly_logtime']+'</a></div>'
												+'</td></tr></table>';
				    					} //crmv@177598
				    				});
			    				}
								html += '</div>';
	
								jQuery('#activityInfoContent').html(html);
								jQuery('#activityInfo').show();
								var info_width = jQuery('#activityInfo .layerPopup').outerWidth(true);
								var element_width = dom_obj[0].getBoundingClientRect().width;
								var left = dom_obj.offset().left + element_width + 10;
								var top = dom_obj.offset().top;
								if ((left + info_width + 20) > jQuery(document).width()) {
									left = jQuery(document).width() - info_width - 20;
									top = top + dom_obj[0].getBoundingClientRect().height + 15;
								}
								jQuery('#activityInfo').css('left',left);
								jQuery('#activityInfo').css('top',top);
							}
				    	});
			    }
			});
			
			jQuery('#executers').change(function(){
				if (jQuery(marked_executers).length > 0) {
					jQuery(marked_executers).each(function(k,v){
						canvas.removeMarker(v, 'executer-shape');
					});
					marked_executers = [];
				}
				if (typeof(executers[this.value]) != 'undefined') {
					jQuery.each(executers[this.value],function(k,v){
						marked_executers.push(v);
						canvas.addMarker(v, 'executer-shape');
					});
				}
			});
		});
		
		if (typeof(callback)) callback();
	}
	// import xml
	jQuery('#canvas').css('height', 2000);
	jQuery.get('index.php?module=Processes&action=ProcessesAjax&file=GetGraph&mode=download&format=bpmn&id='+processmaker+'&xml_version='+xml_version, importXML, 'text');
};
</script>
{/literal}
{/if}