{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@104562 *}

{* in gantt.css remove body overflow hidden *}

<link rel=stylesheet href="{$CURRENT_PATH}libs/dateField/jquery.dateField.css" type="text/css">
<link rel=stylesheet href="{$CURRENT_PATH}gantt.css" type="text/css">

<script src="{$CURRENT_PATH}libs/jquery.livequery.min.js"></script>
<script src="{$CURRENT_PATH}libs/jquery.timers.js"></script>
<script src="{$CURRENT_PATH}libs/platform.js"></script>
<script src="{$CURRENT_PATH}libs/date.js"></script>
<script src="{$CURRENT_PATH}libs/i18nJs.js"></script>
<script src="{$CURRENT_PATH}libs/dateField/jquery.dateField.js"></script>
<script src="{$CURRENT_PATH}libs/JST/jquery.JST.js"></script>

<link rel="stylesheet" type="text/css" href="{$CURRENT_PATH}libs/jquery.svg.css">
<script type="text/javascript" src="{$CURRENT_PATH}libs/jquery.svg.min.js"></script>

<script type="text/javascript" src="{$CURRENT_PATH}libs/jquery.svgdom.1.8.js"></script>

<script src="{$CURRENT_PATH}ganttUtilities.js"></script>
<script src="{$CURRENT_PATH}ganttTask.js"></script>
<script src="{$CURRENT_PATH}ganttDrawerSVG.js"></script>
<script src="{$CURRENT_PATH}ganttGridEditor.js"></script>
<script src="{$CURRENT_PATH}ganttMaster.js"></script>

<style>
{literal}
.taskStatus[status=DEFAULT]{
	background-color: #4CAF50;
}
.taskStatusSVG[status=DEFAULT]{
	fill: #4CAF50;
}
.taskStatusSVG[status=DEFAULT] .textPerc{
	fill: #fff;
}
{/literal}
{if !empty($STATUS_COLORS)}
	{foreach item=info from=$STATUS_COLORS}
		.taskStatus[status=COLOR_{$info.hex}]{ldelim}
			background-color: {$info.html};
		{rdelim}
		.taskStatusSVG[status=COLOR_{$info.hex}]{ldelim}
			fill: {$info.html};
		{rdelim}
		.taskStatusSVG[status=COLOR_{$info.hex}] .textPerc{ldelim}
			fill: {$info.text_color};
		{rdelim}
	{/foreach}
{/if}
</style>

<div id="GanttTab" class="vte-card" style="display:none">

	<div id="workSpaceGantt" class="ganttContainer"></div>
	
	<div id="gantEditorTemplates" style="display:none;">
	  <div class="__template__" type="GANTBUTTONS"></div>
	
	  <div class="__template__" type="TASKSEDITHEAD"><!--
	  <table class="gdfTable" cellspacing="0" cellpadding="0">
	    <thead>
	    <tr style="height:40px">
	      {* <th class="gdfColHeader" style="width:35px;"></th> *}
	      <th class="gdfColHeader" style="width:35px;"></th>
	      <th class="gdfColHeader gdfResizable" style="width:80px;">{'LBL_FIELD_BUTTON_CODE'|getTranslatedString:'Settings'}</th>
	      <th class="gdfColHeader gdfResizable" style="width:200px;">{'Name'|getTranslatedString}</th>
	      <th class="gdfColHeader gdfResizable" style="width:50px;">{'LBL_DAYS'|getTranslatedString}</th>
	      <th class="gdfColHeader gdfResizable" style="width:80px;">{'Start Date'|getTranslatedString:'ProjectTask'}</th>
	      <th class="gdfColHeader gdfResizable" style="width:80px;">{'End Date'|getTranslatedString:'ProjectTask'}</th>
	      {*
	      <th class="gdfColHeader gdfResizable" style="width:50px;">dep.</th>
	      *}
	    </tr>
	    </thead>
	  </table>
	  --></div>
	
	  <div class="__template__" type="TASKROW"><!--
	  <tr taskId="(#=obj.id#)" class="taskEditRow" level="(#=level#)">
	    {* <th class="gdfCell{* edit*}" align="right" {*style="cursor:pointer;"*}><span class="taskRowIndex">(#=obj.getRow()+1#)</span> <span class="teamworkIcon" style="font-size:12px;" >e</span></th> *}
	    {* <td class="gdfCell noClip" align="center"><div class="taskStatus cvcColorSquare" status="(#=obj.status#)"></div></td> *}
	    <td class="gdfCell taskAssigs" align="center">(#=obj.getAssigsString()#)</td>
	    <td class="gdfCell"><input type="text" name="code" value="(#=obj.code?obj.code:''#)"></td>
	    <td class="gdfCell indentCell" style="padding-left:(#=obj.level*10#)px;">
	      {* <div class="(#=obj.isParent()?'exp-controller expcoll exp':'exp-controller'#)" align="center"></div> *}
	      <i class="vteicon (#=obj.isParent()?'exp-controller expcoll exp':'exp-controller'#)" style="vertical-align:middle">(#=obj.isParent()?'expand_less':''#)</i>
	      <input type="text" name="name" value="(#=obj.name#)">
	    </td>
	    <td class="gdfCell"><input type="text" name="duration" value=""></td>
	    <td class="gdfCell"><input type="text" name="start" value="" class="date"></td>
	    <td class="gdfCell"><input type="text" name="end" value="" class="date"></td>
	    {* <td class="gdfCell"><input type="text" name="depends" value="(#=obj.depends#)" (#=obj.hasExternalDep?"readonly":""#)></td> *}
	  </tr>
	  --></div>
	
	  <div class="__template__" type="TASKEMPTYROW"><!--
	  <tr class="taskEditRow emptyRow" >
	    <th class="gdfCell" align="right"></th>
	    <td class="gdfCell noClip" align="center"></td>
	    <td class="gdfCell"></td>
	    <td class="gdfCell"></td>
	    <td class="gdfCell"></td>
	    <td class="gdfCell"></td>
	    <td class="gdfCell"></td>
	    <td class="gdfCell"></td>
	    <td class="gdfCell"></td>
	  </tr>
	  --></div>
	
	  <div class="__template__" type="TASKBAR"><!--
	  <div class="taskBox taskBoxDiv" taskId="(#=obj.id#)" >
	    <div class="layout (#=obj.hasExternalDep?'extDep':''#)">
	      <div class="taskStatus" status="(#=obj.status#)"></div>
	      <div class="taskProgress" style="width:(#=obj.progress>100?100:obj.progress#)%; background-color:(#=obj.progress>100?'red':'rgb(153,255,51);'#);"></div>
	      <div class="milestone (#=obj.startIsMilestone?'active':''#)" ></div>
	
	      <div class="taskLabel"></div>
	      <div class="milestone end (#=obj.endIsMilestone?'active':''#)" ></div>
	    </div>
	  </div>
	  --></div>
	
	  <div class="__template__" type="CHANGE_STATUS"><!--
	    <div class="taskStatusBox">
	      <div class="taskStatus cvcColorSquare" status="STATUS_ACTIVE" title="active"></div>
	      <div class="taskStatus cvcColorSquare" status="STATUS_DONE" title="completed"></div>
	      <div class="taskStatus cvcColorSquare" status="STATUS_FAILED" title="failed"></div>
	      <div class="taskStatus cvcColorSquare" status="STATUS_SUSPENDED" title="suspended"></div>
	      <div class="taskStatus cvcColorSquare" status="STATUS_UNDEFINED" title="undefined"></div>
	    </div>
	  --></div>
	
	
	  <div class="__template__" type="TASK_EDITOR"><!--
	  <div class="ganttTaskEditor">
	  <table width="100%">
	    <tr>
	      <td>
	        <table cellpadding="5">
	          <tr>
	            <td><label for="code">code/short name</label><br><input type="text" name="code" id="code" value="" class="formElements"></td>
	           </tr><tr>
	            <td><label for="name">name</label><br><input type="text" name="name" id="name" value=""  size="35" class="formElements"></td>
	          </tr>
	          <tr></tr>
	            <td>
	              <label for="description">description</label><br>
	              <textarea rows="5" cols="30" id="description" name="description" class="formElements"></textarea>
	            </td>
	          </tr>
	        </table>
	      </td>
	      <td valign="top">
	        <table cellpadding="5">
	          <tr>
	          <td colspan="2"><label for="status">status</label><br><div id="status" class="taskStatus" status=""></div></td>
	          <tr>
	          <td colspan="2"><label for="progress">progress</label><br><input type="text" name="progress" id="progress" value="" size="3" class="formElements"></td>
	          </tr>
	          <tr>
	          <td><label for="start">start</label><br><input type="text" name="start" id="start"  value="" class="date" size="10" class="formElements"><input type="checkbox" id="startIsMilestone"> </td>
	          <td rowspan="2" class="graph" style="padding-left:50px"><label for="duration">dur.</label><br><input type="text" name="duration" id="duration" value=""  size="5" class="formElements"></td>
	        </tr><tr>
	          <td><label for="end">end</label><br><input type="text" name="end" id="end" value="" class="date"  size="10" class="formElements"><input type="checkbox" id="endIsMilestone"></td>
	        </table>
	      </td>
	    </tr>
	    </table>
	
	  <h2>assignments</h2>
	  <table  cellspacing="1" cellpadding="0" width="100%" id="assigsTable">
	    <tr>
	      <th style="width:100px;">name</th>
	      <th style="width:70px;">role</th>
	      <th style="width:30px;">est.wklg.</th>
	      <th style="width:30px;" id="addAssig"><span class="teamworkIcon" style="cursor: pointer">+</span></th>
	    </tr>
	  </table>
	
	  <div style="text-align: right; padding-top: 20px"><button id="saveButton" class="button big">save</button></div>
	  </div>
	  --></div>
	
	
	  <div class="__template__" type="ASSIGNMENT_ROW"><!--
	  <tr taskId="(#=obj.task.id#)" assigId="(#=obj.assig.id#)" class="assigEditRow" >
	    <td ><select name="resourceId"  class="formElements" (#=obj.assig.id.indexOf("tmp_")==0?"":"disabled"#) ></select></td>
	    <td ><select type="select" name="roleId"  class="formElements"></select></td>
	    <td ><input type="text" name="effort" value="(#=getMillisInHoursMinutes(obj.assig.effort)#)" size="5" class="formElements"></td>
	    <td align="center"><span class="teamworkIcon delAssig" style="cursor: pointer">d</span></td>
	  </tr>
	  --></div>
	
	
	  <div class="__template__" type="RESOURCE_EDITOR"><!--
	  <div class="resourceEditor" style="padding: 5px;">
	
	    <h2>Project team</h2>
	    <table  cellspacing="1" cellpadding="0" width="100%" id="resourcesTable">
	      <tr>
	        <th style="width:100px;">name</th>
	        <th style="width:30px;" id="addResource"><span class="teamworkIcon" style="cursor: pointer">+</span></th>
	      </tr>
	    </table>
	
	    <div style="text-align: right; padding-top: 20px"><button id="resSaveButton" class="button big">save</button></div>
	  </div>
	  --></div>
	
	
	  <div class="__template__" type="RESOURCE_ROW"><!--
	  <tr resId="(#=obj.id#)" class="resRow" >
	    <td ><input type="text" name="name" value="(#=obj.name#)" style="width:100%;" class="formElements"></td>
	    <td align="center"><span class="teamworkIcon delRes" style="cursor: pointer">d</span></td>
	  </tr>
	  --></div>
	
	</div>

</div>