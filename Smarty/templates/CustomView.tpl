{* ********************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
*}
{* crmv@98500 *}

{$DATE_JS}
<script type="text/javascript" src="modules/Reports/Reports.js"></script>
<script type="text/javascript" src="modules/CustomView/CustomView.js"></script>
{* crmv@208475 *}
{* crmv@OPER6288 *}
<script src="modules/com_workflow/resources/functional.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/webservices.js" type="text/javascript" charset="utf-8"></script>{* crmv@207901 *}
<script src="modules/com_workflow/resources/parallelexecuter.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/fieldvalidator.js" type="text/javascript" charset="utf-8"></script>
<script src="include/js/GroupConditions.js" type="text/javascript"></script>
<script type="text/javascript">
	var kanban_loaded = false;
	var kanban_json = null;
	{if !empty($KANBAN_JSON)} kanban_json = {$KANBAN_JSON}; {/if} {* crmv@177514 *}
	GroupConditions.init(jQuery,'{$CVMODULE}','kanban_columns','#kanbanColumns',kanban_json,{ldelim}'groupLabel':true,'relatedFields':0,'groupGlue':false,'subGroup':true,'subGroupAddTitle':'{$MOD.LBL_KANBAN_ADD_DRAG_ACTION}','subGroupOperation':false,'subGroupGlue':false,'subGroupRelatedFields':false{rdelim},function(){ldelim}kanban_loaded = true;{rdelim}); //crmv@158293
</script>
{* crmv@OPER6288e *}

<div class="container-fluid pageContainer customViewContainer">
<div class="row">
<div class="col-sm-12">

{literal}
<form id="customview_form" enctype="multipart/form-data" name="EditView" method="POST" action="index.php" onsubmit="if(mandatoryCheck()){VteJS_DialogBox.block();} else{ return false; }">	<!-- crmv@29615 crmv@29190 -->
{/literal}
<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
<input type="hidden" name="module" value="CustomView">
<input type="hidden" name="action" value="Save">
<input type="hidden" name="parenttab" value="{$CATEGORY}">
<input type="hidden" name="cvmodule" value="{$CVMODULE}">
<input type="hidden" name="return_module" value="{$RETURN_MODULE}">
<input type="hidden" name="record" value="{$CUSTOMVIEWID}">
<input type="hidden" name="duplicate" value="{$DUPLICATE}">
<input type="hidden" name="return_action" value="{$RETURN_ACTION}">
<input type="hidden" id="user_dateformat" name="user_dateformat" value="{$DATEFORMAT}">
<script language="javascript" type="text/javascript">
var typeofdata = new Array();
typeofdata['V'] = ['e','n','s','ew','c','k'];
typeofdata['N'] = ['e','n','l','g','m','h'];
typeofdata['T'] = ['e','n','l','g','m','h'];
typeofdata['I'] = ['e','n','l','g','m','h'];
typeofdata['C'] = ['e','n'];
typeofdata['DT'] = ['e','n','l','g','m','h'];
typeofdata['D'] = ['e','n','l','g','m','h'];
typeofdata['NN'] = ['e','n','l','g','m','h'];
typeofdata['E'] = ['e','n','s','ew','c','k'];

var fLabels = new Array();
fLabels['e'] = alert_arr.EQUALS;
fLabels['n'] = alert_arr.NOT_EQUALS_TO;
fLabels['s'] = alert_arr.STARTS_WITH;
fLabels['ew'] = alert_arr.ENDS_WITH;
fLabels['c'] = alert_arr.CONTAINS;
fLabels['k'] = alert_arr.DOES_NOT_CONTAINS;
fLabels['l'] = alert_arr.LESS_THAN;
fLabels['g'] = alert_arr.GREATER_THAN;
fLabels['m'] = alert_arr.LESS_OR_EQUALS;
fLabels['h'] = alert_arr.GREATER_OR_EQUALS;
var noneLabel;
var searchAdvFields = new Array();	{* crmv@29615 *}

function mandatoryCheck()
{ldelim}

        var mandatorycheck = false;
        var i,j;
        var manCheck = new Array({$MANDATORYCHECK});
        var showvalues = "{$SHOWVALUES}";
        if(manCheck)
        {ldelim}
                var isError = false;
                var errorMessage = "";
                if (trim(document.EditView.viewName.value) == "") {ldelim}	//crmv@29615
                        isError = true;
                        errorMessage += "\n{$MOD.LBL_VIEW_NAME}";
                {rdelim}
                // Here we decide whether to submit the form.
                if (isError == true) {ldelim}
                        alert("{$MOD.Missing_required_fields}:" + errorMessage);
                        return false;
                {rdelim}
		
		for(i=1;i<=9;i++)
                {ldelim}
                        var columnvalue = document.getElementById("column"+i).value;
                        if(columnvalue != null)
                        {ldelim}
                                for(j=0;j<manCheck.length;j++)
                                {ldelim}
                                        if(columnvalue == manCheck[j])
                                        {ldelim}
                                                mandatorycheck = true;
                                        {rdelim}
                                {rdelim}
                                if(mandatorycheck == true)
                                {ldelim}
					if((jQuery("#jscal_field_date_start").val().replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0) || (jQuery("#jscal_field_date_end").val().replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0))
						return stdfilterdateValidate();
					else
						return true;
                                {rdelim}else
                                {ldelim}
                                        mandatorycheck = false;
                                {rdelim}
                        {rdelim}
                {rdelim}
        {rdelim}
        if(mandatorycheck == false)
        {ldelim}
                alert("{$APP.MUSTHAVE_ONE_REQUIREDFIELD}"+showvalues);
        {rdelim}
        
        return false;
{rdelim}
</script>
{include file='Buttons_List.tpl'} {* crmv@22223 *}
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
 <tbody><tr>
	<td valign="top" style="padding:0px">
		{include file='Buttons_List_Edit.tpl'} {* crmv@22223 *}
	</td>
  </tr>
  <tr>
  <td class="showPanelBg" valign="top" width="100%">
	<div class="vte-card">
	<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
      <tbody><tr>
      <td align="left" valign="top">
      <table width="100%"  border="0" cellspacing="0" cellpadding="5">
		<tr>
		 	<td colspan="5">
				<div class="dvInnerHeader">
					<div class="dvInnerHeaderTitle">{$MOD.Details}</div>
				</div>
			</td>
		</tr>
		<tr>
			<td colspan=5 width="100%" style="padding:5px">
			<table cellpadding=4 cellspacing=0 width=100% border=0>
				<tr>
					<td width="10%" align="right"><span class="style1">*</span>{$MOD.LBL_VIEW_NAME}</td>
					<td class="dvtCellInfo" width="30%">
						<input class="detailedViewTextBox" type="text" name="viewName" id="viewName" value="{$VIEWNAME}" onfocus="this.className='detailedViewTextBoxOn'" onblur="this.className='detailedViewTextBox'" size="40"/>
		 			</td>
		 			{* crmv@49398 *}
		 			<td width="15%" align="center">
		  			{if $CHECKED eq 'checked'}
		      			<input type="checkbox" id="setDefault" name="setDefault" value="1" checked/>
		  			{else}
		      			<input type="checkbox" id="setDefault" name="setDefault" value="0" />
		  			{/if}
		  			<label for="setDefault">{$MOD.LBL_SETDEFAULT}</label>
		 			</td>
		 			<td width="15%" align="center">
		  			{if $MCHECKED eq 'checked'}
		      			<input type="checkbox" id="setMetrics" name="setMetrics" value="1" checked/>
		  			{else}
		      			<input type="checkbox" id="setMetrics" name="setMetrics" value="0" />
		  			{/if}
		  			<label for="setMetrics">{$MOD.LBL_LIST_IN_METRICS}</label>
		 			</td>
					<td width="15%" align="center">
					{if $STATUS eq '' || $STATUS eq 1}
						<input type="checkbox" id="setStatus" name="setStatus" value="1"/>
					{elseif $STATUS eq 2}
						<input type="checkbox" id="setStatus" name="setStatus" value="2" checked/>
					{elseif $STATUS eq 3 || $STATUS eq 0}
						<input type="checkbox" id="setStatus" name="setStatus" value="3" checked/>
					{/if}
					<label for="setStatus">{$MOD.LBL_SET_AS_PUBLIC}</label>
					</td>
					<td width="15%" align="center">
		  			{if $APPCHECKED eq 'checked'}
		      			<input type="checkbox" id="setMobile" name="setMobile" value="1" checked/>
		  			{else}
		      			<input type="checkbox" id="setMobile" name="setMobile" value="0" />
		  			{/if}
		  			<label for="setMobile">{$MOD.LBL_AVAIL_APP_MOBILE}</label>
		 			</td>
		 			{* crmv@49398e *}
				</tr>
			</table>
			</td>
		</tr>
		<tr><td colspan="5">&nbsp;</td></tr>
		<tr>
		 <td colspan="5" class="detailedViewHeader">
		  <b>{$MOD.LBL_STEP_2_TITLE} </b>
		 </td>
		</tr>
		<tr><td id="cv_columns" colspan="2">{include file='CustomViewColumns.tpl'}</td></tr> {* crmv@34627 *}
		<tr><td colspan="5">&nbsp;</td></tr>
		<tr><td colspan="5"><table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
		<tbody><tr>
		 <td>
		  <table class="small" border="0" cellpadding="3" cellspacing="0" width="100%">
		   <tbody><tr>
		    <td class="dvtTabCache" style="width: 10px;" nowrap>&nbsp;</td>
		    {* crmv@31775 *}
			{if $STDCOLUMNSCOUNT neq 0}
				<td nowrap class="dvtSelectedCell" id="pi" onclick="fnLoadCvValues('pi','mi','ri','ki','mnuTab','mnuTab2','mnuTab3','mnuTab4')"><a href="javascript:;">{$MOD.LBL_STEP_3_TITLE}</a></td>
				<td class="dvtUnSelectedCell" align="center" nowrap id="mi" onclick="fnLoadCvValues('mi','pi','ri','ki','mnuTab2','mnuTab','mnuTab3','mnuTab4')"><a href="javascript:;">{$MOD.LBL_STEP_4_TITLE}</a></td>
				{if $REPORT_FILTER_ACCESS eq true}
					<td class="dvtUnSelectedCell" align="center" nowrap id="ri" onclick="fnLoadCvValues('ri','pi','mi','ki','mnuTab3','mnuTab','mnuTab2','mnuTab4')"><a href="javascript:;">{$MOD.LBL_STEP_5_TITLE}</a></td>
				{/if}
			{else}
				<td class="dvtSelectedCell" align="center" nowrap id="mi" onclick="fnLoadCvValues('mi','pi','ri','ki','mnuTab2','mnuTab','mnuTab3','mnuTab4')"><a href="javascript:;">{$MOD.LBL_STEP_4_TITLE}</a></td>
				{if $REPORT_FILTER_ACCESS eq true}
					<td class="dvtUnSelectedCell" align="center" nowrap id="ri" onclick="fnLoadCvValues('ri','pi','mi','ki','mnuTab3','mnuTab','mnuTab2','mnuTab4')"><a href="javascript:;">{$MOD.LBL_STEP_5_TITLE}</a></td>
				{/if}
			{/if}
			{* crmv@31775e *}
			{* crmv@OPER6288 *}
			<td class="dvtUnSelectedCell" align="center" nowrap id="ki" onclick="fnLoadCvValues('ki','pi','mi','ri','mnuTab4','mnuTab','mnuTab2','mnuTab3')"><a href="javascript:;">{'LBL_KANBAN_SETTINGS'|getTranslatedString:'CustomView'}</a></td>
			{* crmv@OPER6288e *}
		    <td class="dvtTabCache" nowrap style="width:55%;">&nbsp;</td>
		   </tr>
		   </tbody>
	          </table>
		 </td>
		</tr>
		<tr>
		 <td align="left" valign="top">
			{if $STDCOLUMNSCOUNT eq 0}
				{assign var=stddiv value="style=display:none"}
				{assign var=advdiv value="style=display:block"}
			{else}
				{assign var=stddiv value="style=display:block"}
				{assign var=advdiv value="style=display:none"}
			{/if}
		  <div id="mnuTab" {$stddiv}>
		     <table width="100%" cellspacing="0" cellpadding="5">
                      <tr><td><br>
			<table width="75%" border="0" cellpadding="5" cellspacing="0" align="center">
			  <tr>
			     <td width="50%" align="right" class="dvtCellLabel">{$MOD.LBL_Select_a_Column} :</td>
			     <td width="50%">
			     	<div class="dvtCellInfo">
						<select name="stdDateFilterField" class="detailedViewTextBox" onchange="standardFilterDisplay();">
						{foreach item=stdfilter from=$STDFILTERCOLUMNS}
							<option {$stdfilter.selected} value={$stdfilter.value}>{$stdfilter.text}</option>	
						{/foreach}
						</select>
					</div>
				</td>
			  </tr>
			  <tr>
			     <td align="right" class="dvtCellLabel">{$MOD.Select_Duration} :</td>
			     <td>
			     	<div class="dvtCellInfo">
						<select name="stdDateFilter" id="stdDateFilter" class="detailedViewTextBox" onchange='showDateRange(this.options[this.selectedIndex].value )'>
						{foreach item=duration from=$STDFILTERCRITERIA}
							<option {$duration.selected} value={$duration.value}>{$duration.text}</option>
						{/foreach}
						</select>
					</div>
			     </td>
			  </tr>
			  <tr>
				<td align="right" class="dvtCellLabel">{$MOD.Start_Date} :</td>
				<td align=left>
					<div class="dvtCellInfo">
						{if $STDFILTERCRITERIA.0.selected eq "selected" || $CUSTOMVIEWID eq ""}
							{assign var=img_style value="visibility:visible"}
							{assign var=msg_style value=""}
						{else}
							{assign var=img_style value="visibility:hidden"}
							{assign var=msg_style value="readonly"}
						{/if}
						{* crmv@100585 *}
						<input name="startdate" id="jscal_field_date_start" type="text" size="10" class="detailedViewTextBox input-inline" value="{$STARTDATE}" {$msg_style}>
						<i class="vteicon md-link md-text" id="jscal_trigger_date_start" style="{$img_style}">event</i>
						<font size=1><em old="(yyyy-mm-dd)">({$DATEFORMAT})</em></font>
						<script type="text/javascript">
							(function() {ldelim}
								setupDatePicker('jscal_field_date_start', {ldelim}
									trigger: 'jscal_trigger_date_start',
									date_format: "{$DATEFORMAT|strtoupper}",
									language: "{$APP.LBL_JSCALENDAR_LANG}",
								{rdelim});
							{rdelim})();
						</script>
						{* crmv@100585e *}
					</div>
				</td>
			  </tr>
			  <tr>
				<td align="right" class="dvtCellLabel">{$MOD.End_Date} :</td> 
				<td align=left>
					<div class="dvtCellInfo">
						{* crmv@100585 *}
						<input name="enddate" {$msg_style} id="jscal_field_date_end" type="text" size="10" class="detailedViewTextBox input-inline" value="{$ENDDATE}">
						<i class="vteicon md-link md-text" id="jscal_trigger_date_end" style="{$img_style}">event</i>
						<font size=1><em old="(yyyy-mm-dd)">({$DATEFORMAT})</em></font>
						<script type="text/javascript">
							(function() {ldelim}
								setupDatePicker('jscal_field_date_end', {ldelim}
									trigger: 'jscal_trigger_date_end',
									date_format: "{$DATEFORMAT|strtoupper}",
									language: "{$APP.LBL_JSCALENDAR_LANG}",
								{rdelim});
							{rdelim})();
						</script>
						{* crmv@100585e *}
					</div>
				</td>
			  </tr>
			  
			  <!-- crmv@10468 -->
			  <tr>
			  	<td align="right" class="dvtCellLabel"><label for="only_month_and_day">{$MOD.Only_Month_and_Day}</label> :</td> 
  			  	<td align=left>
  			  		<input name="only_month_and_day" id="only_month_and_day" type="checkbox" {$ONLY_MONTH_AND_DAY}>
  			  	</td>
			  </tr>
			  <!-- crmv@10468e -->
			  
			</table>
		      </td></tr>
		      <tr><td>&nbsp;</td></tr>
            </table>
   </div>
   <div id="mnuTab2" {$advdiv} >
      <table width="100%" cellspacing="0" cellpadding="5">
       <tr><td>&nbsp;</td></tr>
       <tr><td>{$MOD.LBL_AF_HDR1}<br /><br />
			<li style="margin-left:30px;">{$MOD.LBL_AF_HDR2}</li>
			<li style="margin-left:30px;">{$MOD.LBL_AF_HDR3}</li>
			<br /><br />
       </td></tr>
       <tr><td>
	<table width="75%" border="0" cellpadding="5" cellspacing="0" align="center">
	<tr><td colspan="4" class="detailedViewHeader"><b>{$MOD.LBL_RULE}</b></td></tr>
	{* crmv@29615 *}
	<tr class="dvtCellLabel">
		<td>
			<div class="dvtCellInfo">
				<select name="fcol1" id="fcol1" onchange="updatefOptions(this, 'fop1', '{$MODULE}', 1);" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$BLOCK1}
						<optgroup label="{$label}" class="select">
						{foreach item=text from=$filteroption}
						<option {$text.selected} value={$text.value}>{$text.text}</option>
						{/foreach}
					{/foreach}
				</select>
			</div>
		</td>
		<td>
			<div class="dvtCellInfo">
				<select name="fop1" id="fop1" onchange="updatefOptions(document.getElementById('fcol1'), 'fop1', '{$MODULE}', 1);" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=criteria from=$FOPTION1}
						<option {$criteria.selected} value={$criteria.value}>{$criteria.text}</option>
					{/foreach}
				</select>
			</div>
			<input name="fval1" id="fval1" type="hidden" value="{$VALUE1}">
		</td>
		<td>    
			<div id="Srch_adv_value1"></div>
		</td>
		<td>
			<span id="andfcol1">{$AND_TEXT1}</span>
		</td>
	</tr>
	<tr class="dvtCellInfo">
		<td>
			<div class="dvtCellInfo">
				<select name="fcol2" id="fcol2" onchange="updatefOptions(this, 'fop2', '{$MODULE}', 2);" class="detailedViewTextBox">	
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$BLOCK2}
						<optgroup label="{$label}" class="select">
						{foreach item=text from=$filteroption}
							<option {$text.selected} value={$text.value}>{$text.text}</option>
						{/foreach}
					{/foreach}
					</select>
			</div>
		</td>
		<td>
			<div class="dvtCellInfo">
				<select name="fop2" id="fop2" onchange="updatefOptions(document.getElementById('fcol2'), 'fop2', '{$MODULE}', 2);" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=criteria from=$FOPTION2}
						<option {$criteria.selected} value={$criteria.value}>{$criteria.text}</option>
					{/foreach}
				</select>
			</div>
			<input name="fval2" id="fval2" type="hidden" value="{$VALUE2}">
		</td>
		<td>    
			<div id="Srch_adv_value2"></div>
		</td>
		<td>
			<span id="andfcol2">{$AND_TEXT2}</span>
		</td>
	</tr>
	<tr class="dvtCellLabel">
		<td>
			<div class="dvtCellInfo">
				<select name="fcol3" id="fcol3" onchange="updatefOptions(this, 'fop3', '{$MODULE}', 3);" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$BLOCK3}
						<optgroup label="{$label}" class="select">
						{foreach item=text from=$filteroption}
							<option {$text.selected} value={$text.value}>{$text.text}</option>
						{/foreach}
					{/foreach}
				</select>
			</div>
		</td>
		<td>
			<div class="dvtCellInfo">
				<select name="fop3" id="fop3" onchange="updatefOptions(document.getElementById('fcol3'), 'fop3', '{$MODULE}', 3);" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=criteria from=$FOPTION3}
						<option {$criteria.selected} value={$criteria.value}>{$criteria.text}</option>
					{/foreach}
				</select>
			</div>	
			<input name="fval3" id="fval3" type="hidden" value="{$VALUE3}">
		</td>
		<td>    
			<div id="Srch_adv_value3"></div>
		</td>
		<td>
			<span id="andfcol3">{$AND_TEXT3}</span>
		</td>
	</tr>
	<tr class="dvtCellInfo">
		<td>
			<div class="dvtCellInfo">
				<select name="fcol4" id="fcol4" onchange="updatefOptions(this, 'fop4', '{$MODULE}', 4);" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$BLOCK4}
						<optgroup label="{$label}" class="select" style="border:none">
						{foreach item=text from=$filteroption}
							<option {$text.selected} value={$text.value}>{$text.text}</option>
						{/foreach}
					{/foreach}
				</select>
			</div>
		</td>
		<td>
			<div class="dvtCellInfo">
				<select name="fop4" id="fop4" onchange="updatefOptions(document.getElementById('fcol4'), 'fop4', '{$MODULE}', 4);" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=criteria from=$FOPTION4}
						<option {$criteria.selected} value={$criteria.value}>{$criteria.text}</option>
					{/foreach}
				</select>
			</div>	
			<input name="fval4" id="fval4" type="hidden" value="{$VALUE4}">
		</td>
		<td>
			<div id="Srch_adv_value4"></div>
		</td>
		<td>
			<span id="andfcol4">{$AND_TEXT4}</span>
		</td>
	</tr>
	<tr class="dvtCellLabel">
		<td>
			<div class="dvtCellInfo">
				<select name="fcol5" id="fcol5" onchange="updatefOptions(this, 'fop5', '{$MODULE}', 5);" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$BLOCK5}
						<optgroup label="{$label}" class="select">
						{foreach item=text from=$filteroption}
							<option {$text.selected} value={$text.value}>{$text.text}</option>
						{/foreach}
					{/foreach}
				</select>
			</div>
		</td>
		<td>
			<div class="dvtCellInfo">
				<select name="fop5" id="fop5" onchange="updatefOptions(document.getElementById('fcol5'), 'fop5', '{$MODULE}', 5);" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=criteria from=$FOPTION5}
						<option {$criteria.selected} value={$criteria.value}>{$criteria.text}</option>
					{/foreach}
				</select>
			</div>
			<input name="fval5" id="fval5" type="hidden" value="{$VALUE5}">
		</td>
		<td>    
			<div id="Srch_adv_value5"></div>
		</td>
		<td>
			<span id="andfcol5">{$AND_TEXT5}</span>
		</td>
	</tr>
	</table>
       </td></tr>
       <tr><td>&nbsp;</td></tr>
     </table>
   </div>
	{* crmv@31775 *}
	{if $REPORT_FILTER_ACCESS eq true}
		<div id="mnuTab3" style=display:none>
			<table width="100%" cellspacing="0" cellpadding="5">
				<tr><td><br>
				<table width="85%" border="0" cellpadding="5" cellspacing="0" align="center"> {* crmv@198701 *}
					<tr>
						<td width="50%" align="right" class="dvtCellLabel">{'LBL_MODULE_NAME'|getTranslatedString:'Reports'} :</td> 
					    <td width="50%" align=left class="dvtCellInfo">
					    	{assign var="fldname" value="report"}
							<input id="{$fldname}" name="{$fldname}" type="hidden" value="{$REPORT_ID}">
							{assign var=fld_displayvalue value=$REPORT_NAME}
							{assign var=fld_style value='class="detailedViewTextBox detailedViewReference detailedViewReferenceRO" readonly'}
							{if $fld_displayvalue|trim eq '' && $REPORT_FILTER_READONLY neq true} {* crmv@146032 *}
								{assign var=fld_displayvalue value='LBL_SEARCH_STRING'|getTranslatedString}
								{assign var=fld_style value='class="detailedViewTextBox detailedViewReference"'}
							{/if}
							<input id="{$fldname}_display" name="{$fldname}_display" type="text" value="{$fld_displayvalue}" {$fld_style}>
							{if $REPORT_FILTER_READONLY neq true} {* crmv@146032 *}
								{assign var="popup_params" value="module=Reports&action=ReportsAjax&file=AutocompleteCV&cvmodule=$CVMODULE"}
								<script type="text/javascript">
								initAutocomplete('{$fldname}','{$fldname}_display',encodeURIComponent('{$popup_params}'));
								</script>
								<i class="vteicon md-link" title="{$APP.LBL_SELECT}" onclick='jQuery( this ).blur(); jQuery("#{$fldname}_display").autocomplete("search","ALL");'>view_list</i>
								<i class="vteicon md-link" title="{$APP.LBL_CLEAR}" onClick="document.forms['EditView'].{$fldname}.value=''; document.forms['EditView'].{$fldname}_display.value=''; enableReferenceField(document.forms['EditView'].{$fldname}_display); return false;" >highlight_remove</i>
								<i class="vteicon md-link" title="{$APP.LBL_CREATE}" onClick="Reports.createNew(null, null, '{$CVMODULE}');" >add</i>
								<i class="vteicon md-link" title="{$APP.LBL_EDIT}" onClick="Reports.editReport(jQuery('#{$fldname}').val());">create</i>
							{/if} {* crmv@146032 *}
						</td>
					</tr>
				</table>
				</td></tr>
				<tr><td>&nbsp;</td></tr>
            </table>
		</div>
	{/if}
	{* crmv@31775e *}
	{* crmv@OPER6288 *}
	<div id="mnuTab4" style=display:none>
		<div id="kanbanColumns">
			<table class="tableHeading" width="100%" border="0" cellspacing="0" cellpadding="5">
				<tr height="40">
					<td class="big" nowrap="nowrap">
						<strong>{$MOD.LBL_KANBAN_COLUMNS}</strong>
					</td>
					<td class="small" align="right">
						<span id="group_conditions_loading" style="display:none">
							{include file="LoadingIndicator.tpl"}
						</span>
						<input type="button" class="crmButton create small" value="{$MOD.LBL_KANBAN_ADD_COLUMN}" id="group_conditions_add" style="display:none"/>
					</td>
				</tr>
			</table>
			<div id="kanban_columns"></div>
			<div id="dump" style="display:none;"></div>
		</div>
	</div>
	<input type="hidden" name="kanban_json">
	{* crmv@OPER6288e *}
  </td></tr>
  </tbody>
  </table>
  </td></tr>
  
  	<!-- crmv@7635 -->
	<tr><td>&nbsp;</td></tr>
	<tr><td class="detailedViewHeader" colspan="5"><b>{$MOD.LBL_ORDERBY}</b></td></tr>
	<tr class="dvtCellLabel">
		<td width="25%">
			<div class="dvtCellInfo">
				<select name="cv_column_order_by" id="cv_column_order_by" class="detailedViewTextBox">
	            	<option value="">{$MOD.LBL_NONE}</option>
	                {foreach item=filteroption key=label from=$CV_ORDERBY}
	                	<optgroup label="{$label}" class="select" style="border:none">
	                    	{foreach item=text from=$filteroption}
	                        	<option {$text.selected} value={$text.value}>{$text.text}</option>
							{/foreach}
					{/foreach}
	                {$CV_FOPTION}
				</select>
			</div>
		</td>
		<td width="75%">
			{if $CV_ORDERBY_TYPE eq 'ASC'}
				<input type="radio" id="cv_order_asc" name="cv_order_by_type" value="ASC" checked><label for="cv_order_asc">{$MOD.LBL_ORDERBY_ASC}</label>
				<input type="radio" id="cv_order_desc" name="cv_order_by_type" value="DESC"><label for="cv_order_desc">{$MOD.LBL_ORDERBY_DESC}</label>
			{else}
				<input type="radio" id="cv_order_asc" name="cv_order_by_type" value="ASC"><label for="cv_order_asc">{$MOD.LBL_ORDERBY_ASC}</label>
				<input type="radio" id="cv_order_desc" name="cv_order_by_type" value="DESC" checked><label for="cv_order_desc">{$MOD.LBL_ORDERBY_DESC}</label>
			{/if}
		</td>
	</tr>
	<!-- crmv@7635e -->
		  
  <tr><td colspan="5">&nbsp;</td></tr>
</table>
</td>
</tr>
</tbody>
</table>
</div>
</td>
</tr>
</tbody>
</table>
</form>

</div>
</div>
</div>

{$STDFILTER_JAVASCRIPT}
{$JAVASCRIPT}
<!-- to show the mandatory fields while creating new customview -->
<script language="javascript" type="text/javascript">
var k;
var colOpts;
var manCheck = new Array({$MANDATORYCHECK});
{literal}
if(document.EditView.record.value == '')	//crmv@29615
{
  for(k=0;k<manCheck.length;k++)
  {
      selname = "column"+(k+1);
      if (isdefined(selname)){
	      colOpts = document.getElementById(selname).options;
	      for (l=0;l<colOpts.length;l++)
	      {
	        if(colOpts[l].value == manCheck[k])
	        {
	          colOpts[l].selected = true;
	        }
	      }
      }
  }
}

function checkDuplicate() {
	var viewName = jQuery("#viewName").val().toLowerCase();

	if (viewName == "all") {
		alert(alert_arr.ALL_FILTER_CREATION_DENIED);
		return false;
	}

	var cvselect_array = ["column1", "column2", "column3", "column4", "column5", "column6", "column7", "column8", "column9"];

	for (var loop = 0; loop < cvselect_array.length - 1; loop++) {
		var selected_cv_columnvalue = jQuery('#' + cvselect_array[loop]).val() || "";
		if (selected_cv_columnvalue.length > 0) {
			for (var iloop = 0; iloop < cvselect_array.length; iloop++) {
				if (iloop == loop) iloop++;
				var selected_cv_icolumnvalue = jQuery('#' + cvselect_array[iloop]).val() || "";
				if (selected_cv_columnvalue === selected_cv_icolumnvalue) {
					alert(alert_arr.COLUMNS_CANNOT_BE_DUPLICATED);
					jQuery('#' + cvselect_array[iloop]).val("");
					return false;
				}
			}
		}
	}

	if (!checkval()) return false;

	return true;
}

jQuery(document).ready(function() {
	checkDuplicate();
});

function stdfilterdateValidate()
{
	if(!dateValidate("startdate",alert_arr.STDFILTER+" - "+alert_arr.STARTDATE,"OTH"))
	{
		getObj("startdate").focus()
		return false;
	}
	else if(!dateValidate("enddate",alert_arr.STDFILTER+" - "+alert_arr.ENDDATE,"OTH"))
	{
		getObj("enddate").focus()
		return false;
	}
	else
	{
		if (!dateComparison("enddate",alert_arr.STDFILTER+" - "+alert_arr.ENDDATE,"startdate",alert_arr.STDFILTER+" - "+alert_arr.STARTDATE,"GE")) {
                        getObj("enddate").focus()
                        return false
                } else return true;
	}
}
{/literal}
{* crmv@29615 *}
updatefOptions(document.getElementById("fcol1"), 'fop1', '{$MODULE}', 1);
updatefOptions(document.getElementById("fcol2"), 'fop2', '{$MODULE}', 2);
updatefOptions(document.getElementById("fcol3"), 'fop3', '{$MODULE}', 3);
updatefOptions(document.getElementById("fcol4"), 'fop4', '{$MODULE}', 4);
updatefOptions(document.getElementById("fcol5"), 'fop5', '{$MODULE}', 5);
{* crmv@29615e *}
standardFilterDisplay();

{* crmv@132522 *}
{if !$STARTDATE || !$ENDDATE}
	showDateRange(jQuery('#stdDateFilter').val()); //crmv@95824
{/if}
{* crmv@132522e *}

</script>