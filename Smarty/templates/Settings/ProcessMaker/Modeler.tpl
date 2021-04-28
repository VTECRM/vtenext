{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@100972 *}
{include file='SmallHeader.tpl' HEADER_Z_INDEX=1 PAGE_TITLE="SKIP_TITLE" HEAD_INCLUDE="all" BUTTON_LIST_CLASS="navbar navbar-default"}

<script src="{"modules/Settings/ProcessMaker/resources/ProcessMakerScript.js"|resourcever}" type="text/javascript"></script>

{* crmv@187568 *}
<link rel="stylesheet" href="modules/Settings/ProcessMaker/thirdparty/bpmn-js/dist/assets/diagram-js.css">
<link rel="stylesheet" href="modules/Settings/ProcessMaker/thirdparty/bpmn-js/dist/assets/bpmn-font/css/bpmn.css">
<script src="modules/Settings/ProcessMaker/thirdparty/bpmn-js/dist/bpmn-modeler.development.js"></script>
<script src="modules/Processes/Processes.js"></script>
{* crmv@187568e *}
<link rel="stylesheet" type="text/css" href="themes/{$THEME}/processmaker.css" /> {* crmv@154044 *}

{if $MODE eq 'import'}
	<form id="cache_values">
		<input type="hidden" name="mode" value="{$MODE}" />
		<input type="hidden" name="name" value="{$NAME}" />
		<input type="hidden" name="description" value="{$DESCRIPTION}" />
		<input type="hidden" name="version" value="{$VERSION}" />	{* crmv@147720 *}
		<textarea style="display:none" name="file">{$FILE}</textarea>
		<textarea style="display:none" name="xml">{$XML}</textarea>
	</form>
{/if}

<div id="canvas" style="padding: 0px 5px;"></div>

{literal}
<script text="text/javascript">
jQuery('#canvas').css('height', jQuery(document).height() - 40);

(function(BpmnModeler, $) {
	// create modeler
	var bpmnModeler = new BpmnModeler({
		container: '#canvas'
	});

	// import function
	function importXML(xml) {

	    // import diagram
	    bpmnModeler.importXML(xml, function(err) {
			if (err) return console.error('could not import BPMN 2.0 diagram', err);
	
			var canvas = bpmnModeler.get('canvas');
	
			// zoom to fit full viewport
			canvas.zoom('fit-viewport');
		});

		// save diagram on button click
		var saveButton = document.querySelector('#save-button');

		saveButton.addEventListener('click', function() {
			// crmv@189903
			if (!ProcessMakerScript.sessionCheck()) return false;
			jQuery('#save-button').attr("disabled", true);
			// crmv@189903e
			// get the diagram contents
			bpmnModeler.saveXML({ format: true }, function(err, xml) {
				if (err) {
					console.error('diagram save failed', err);
					alert('{/literal}{$MOD.LBL_PM_SAVE_DIAGRAM_ERROR}{literal}');
					jQuery('#save-button').attr("disabled", false); // crmv@189903
				} else {
					var values = {};
					jQuery.each(jQuery('#cache_values').serializeArray(), function(){
						values[this.name] = this.value;
					});
					ProcessMakerScript.saveModel('{/literal}{$PROCESSMAKERID}{literal}',xml,values);
				}
			});
		});
	}

	// import xml
	var initialDiagram =
	  '<?xml version="1.0" encoding="UTF-8"?>' +
	  '<bpmn:definitions xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' +
	                    'xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" ' +
	                    'xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" ' +
	                    'xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" ' +
	                    'targetNamespace="http://bpmn.io/schema/bpmn" ' +
	                    'id="Definitions_1">' +
	    '<bpmn:process id="Process_1" isExecutable="false">' +
	      '<bpmn:startEvent id="StartEvent_1"/>' +
	    '</bpmn:process>' +
	    '<bpmndi:BPMNDiagram id="BPMNDiagram_1">' +
	      '<bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">' +
	        '<bpmndi:BPMNShape id="_BPMNShape_StartEvent_2" bpmnElement="StartEvent_1">' +
	          '<dc:Bounds height="36.0" width="36.0" x="173.0" y="102.0"/>' +
	        '</bpmndi:BPMNShape>' +
	      '</bpmndi:BPMNPlane>' +
	    '</bpmndi:BPMNDiagram>' +
	  '</bpmn:definitions>';
	{/literal}
	{if $MODE eq 'import'}
		if (jQuery('[name="xml"]').text() != '') importXML(jQuery('[name="xml"]').text());
		else importXML(initialDiagram);
	{* 
	elseif empty($PROCESSMAKERID)}
		importXML(initialDiagram);
	*}
	{else}
		jQuery.get('index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&mode=download&format=bpmn&id={$PROCESSMAKERID}', importXML, 'text');
	{/if}
	{literal}

})(window.BpmnJS, window.jQuery);
</script>
{/literal}