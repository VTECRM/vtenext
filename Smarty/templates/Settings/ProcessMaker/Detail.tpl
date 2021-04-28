{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@92272 crmv@97566 crmv@100972 *}

{include file='SmallHeader.tpl' HEADER_Z_INDEX=1 PAGE_TITLE="SKIP_TITLE" HEAD_INCLUDE="all" BUTTON_LIST_CLASS="navbar navbar-default"}

{SDK::checkJsLanguage()}	{* crmv@sdk-18430 *} {* crmv@181170 *}
{include file='CachedValues.tpl'}	{* crmv@26316 *}

<script src="{"include/js/dtlviewajax.js"|resourcever}" type="text/javascript"></script>
<script src="{"modules/Settings/ProcessMaker/resources/ProcessMakerScript.js"|resourcever}" type="text/javascript"></script>
<script src="modules/Settings/ProcessMaker/thirdparty/bpmn-js/dist/bpmn-viewer.development.js"></script> {* crmv@187568 *}
<link rel="stylesheet" type="text/css" href="themes/{$THEME}/processmaker.css" /> {* crmv@154044 *}

{* in order to enable ajax edit *}
<script type="text/javascript">
var gVTModule = 'Settings';
var default_charset = '{$default_charset}';
var fieldname = new Array();
var fieldlabel = new Array();
var fielddatatype = new Array();
var fielduitype = new Array();
</script>
<span id="crmspanid" style="display:none;position:absolute;" onmouseover="show('crmspanid');">
   <a class="edit" href="javascript:;">{$APP.LBL_EDIT_BUTTON}</a>
</span>
<input type="hidden" id="hdtxt_IsAdmin" value="{if $IS_ADMIN}1{else}0{/if}"> {* crmv@181170 *}
{* end *}

<form name="Edit" method="POST" action="index.php" onsubmit="VteJS_DialogBox.block();">
	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	<input type="hidden" name="module" value="Settings">
	<input type="hidden" name="action" value="ProcessMaker">
	<input type="hidden" name="mode" value="save">
	<input type="hidden" name="id" value="{$DATA.id}">
	<input type="hidden" name="vte_metadata" value="">
	<div style="padding:5px">
		<table border=0 cellspacing=0 cellpadding=3 width=100% class="listRow">
			{* crmv@190834 *}
			{if $DISCOVERY_MODE}
				<tr valign="top">
					<td width="50%">
						{include file="DetailViewUI.tpl" DIVCLASS="dvtCellInfoOff" keyid=1 keymandatory=false label=$MOD.LBL_PROCESS_DISCOVERY_ID AJAXEDITTABLEPERM=false keyfldname="pd_id" keyval=$DATA.id MODULE="Settings" keytblname=$TABLE_NAME ID=$DATA.id}
					</td>
					<td width="50%">
						{include file="DetailViewUI.tpl" DIVCLASS="dvtCellInfoOff" keyid=1 keymandatory=false label=$MOD.LBL_PROCESS_DISCOVERY_EVENTS AJAXEDITTABLEPERM=false keyfldname="pd_event" keyval=$DATA.event MODULE="Settings" keytblname=$TABLE_NAME ID=$DATA.id}
					</td>
				</tr>
			{else}
			{* crmv@190834e *}
				<tr valign="top">
					<td width="50%">
						{include file="DetailViewUI.tpl" DIVCLASS="dvtCellInfoM" keyid=1 keymandatory=true label=$MOD.LBL_PROCESS_MAKER_RECORD_NAME AJAXEDITTABLEPERM=true keyfldname="pm_name" keyval=$DATA.name MODULE="Settings" keytblname=$TABLE_NAME ID=$DATA.id}
					</td>
					<td width="50%">
						{include file="DetailViewUI.tpl" DIVCLASS="dvtCellInfo" keyid=21 keymandatory=false label=$MOD.LBL_PROCESS_MAKER_RECORD_DESC AJAXEDITTABLEPERM=true keyfldname="pm_description" keyval=$DATA.description MODULE="Settings" keytblname=$TABLE_NAME ID=$DATA.id}
					</td>
				</tr>
				<tr valign="top">
					<td>
						{if $DATA.active eq 1}
							{assign var=ACTIVE value=$APP.yes}
						{else}
							{assign var=ACTIVE value=$APP.no}
						{/if}
						{include file="DetailViewUI.tpl" DIVCLASS="dvtCellInfo" keyid=56 keymandatory=false label=$APP.Active AJAXEDITTABLEPERM=true keyfldname="pm_active" keyval=$ACTIVE MODULE="Settings" keytblname=$TABLE_NAME ID=$DATA.id AJAXSAVEFUNCTION="ProcessMakerScript.setActive"}
					</td>
					<td>
						{* crmv@147720 crmv@150751 *}
						{include file="DetailViewUI.tpl" DIVCLASS="dvtCellInfoOff" keyid=1 keymandatory=false label=$MOD.VTLIB_LBL_PACKAGE_VERSION AJAXEDITTABLEPERM=false keyfldname="pm_version" keyval=$DATA.version MODULE="Settings" keytblname=$TABLE_NAME ID=$DATA.id}
						{* crmv@147720e crmv@150751e *}
					</td>
				</tr>
			{/if} {* crmv@190834 *}
			<tr>
				<td colspan="2">
					{include file="FieldHeader.tpl" label=$MOD.LBL_PM_MODELER}
					{* <textarea id="xml" style="display:none">{$DATA.xml}</textarea> *}
					<textarea id="structure" style="display:none">{$DATA.structure}</textarea>
					<div id="canvas"></div>
				</td>
			</tr>
		</table>
	</div>
</form>

{literal}
<style type="text/css">
	.highlights-shape:not(.djs-connection) .djs-visual > :nth-child(1) {
		stroke: #2c80c8 !important;
	}
</style>
<script>
jQuery('#canvas').css('height', 2000);

(function(BpmnViewer) {
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
			
			{/literal}{if $DISCOVERY_MODE}return;{/if}{literal} // crmv@190834
			
			var structure = {'shapes':{},'connections':{},'tree':{}};
			
			jQuery.each(elementRegistry.getAll(), function(index, object) {
			    if (object.constructor.name == 'Shape') {
			    	var id = object.id,
			    		type = ProcessMakerScript.formatType(object.type),
			    		dom_obj = jQuery('[data-element-id='+id+']'),
			    		subType = '';

					if (jQuery(object.businessObject.eventDefinitions).length > 0) {
						jQuery.each(object.businessObject.eventDefinitions, function(k,v){
							subType = ProcessMakerScript.formatType(v.$type);
						});
					}
					if (typeof(object.businessObject.cancelActivity) == 'boolean') {
						var cancelActivity = object.businessObject.cancelActivity;
					}
			    	if (typeof(elementRegistry.get(id+'_label')) == 'object') {
			    		var text = jQuery('[data-element-id='+id+'_label]').find('text').text();
			    	} else {
			    		var text = dom_obj.find('text').text();
			    	}
			    	
			    	var connections = {'incoming':{},'outgoing':{},'attachers':new Array()};
			    	if (object.incoming != undefined) {
				    	jQuery(object.incoming).each(function(index,connection){
				    		connections['incoming'][connection.id] = connection.source.id;
				    	});
				    }
			    	if (object.outgoing != undefined) {
				    	jQuery(object.outgoing).each(function(index,connection){
				    		connections['outgoing'][connection.id] = connection.target.id;
				    	});
				    }
				    if (object.attachers != undefined && jQuery(object.attachers).length > 0) {
				    	jQuery(object.attachers).each(function(index,attacher){
				    		connections['attachers'].push(attacher.id);
				    	});
				    }
				    structure['shapes'][id] = {'type':type,'text':text};
				    if (subType != '') structure['shapes'][id]['subType'] = subType;
				    if (typeof(cancelActivity) == 'boolean') structure['shapes'][id]['cancelActivity'] = cancelActivity;
				    structure['tree'][id] = connections;

				    if (type == 'Participant' || type == 'Lane' || type == 'TextAnnotation' || (type == 'StartEvent' && subType != 'TimerEventDefinition')) return;

					//console.log(id, type, text);
			    	dom_obj
			    	.css('cursor','pointer')
			    	.hover(function(){
		    				canvas.toggleMarker(id, 'highlights-shape');
			    		}, function(){
		    				canvas.toggleMarker(id, 'highlights-shape');
			    		})
			    	.click(function(){
		    			ProcessMakerScript.openMetadata({/literal}{$DATA.id}{literal},id,structure['shapes'][id]);
			    	});
			    } else if (object.constructor.name == 'Connection') {
			    	var id = object.id,
			    		type = ProcessMakerScript.formatType(object.type);
			    	if (typeof(elementRegistry.get(id+'_label')) == 'object') {
			    		var text = jQuery('[data-element-id='+id+'_label]').find('text').text();
			    	}
			    	structure['connections'][id] = {'type':type,'text':text};
			    }
			});
			
			if (jQuery('#structure').html() == '') {
				jQuery.ajax({
					'url': 'index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=save_structure',
					'type': 'POST',
					'data': jQuery.param({'id': {/literal}{$DATA.id}{literal}, 'structure': JSON.stringify(structure)}),
					success: function(data) {},
					error: function() {}
				});
			}
		});
	}
	
	// import xml
	//importXML(jQuery('#xml').val());
	jQuery.get('index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=download&format=bpmn&id={/literal}{$DATA.id}{literal}', importXML, 'text'); // crmv@190834 crmv@215597

})(window.BpmnJS);
</script>
{/literal}
