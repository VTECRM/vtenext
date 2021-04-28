{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@155145 *}
{literal}
<script language="JavaScript" type="text/javascript" src="modules/Conditionals/Conditionals.js"></script>
<script type="text/javascript">
        function getUserSelectionBox()
        {
        		var url = selectContact("false","general",document.FpofvEditView);	//crmv@29190
                var s = '<input type="hidden" id="get_users_list">' +
                		'<input name="user_name" readonly type="text" style="border:1px solid ' +
						'#bababa;" value="{/literal}{$FPOFV_PIECE_DATA.SystemUserName}{literal}" class="TaskInput">' +
						'<input name="user_id" type="hidden" value="{/literal}{$FPOFV_PIECE_DATA.SystemUserID}{literal}">&nbsp;<img ' +
						'src="{/literal}{"select.gif"|resourcever}{literal}" alt="Select" title="Select" ' +
						'LANGUAGE=javascript ' +
						'onclick=\'openPopup("index.php?"+url)\' ' +	//crmv@29190
						'align="absmiddle" style="cursor:hand;cursor:pointer">&nbsp;' +
						'<input type="image" tabindex="3" ' +
						'src="{/literal}{"clear_field.gif"|resourcever}{literal}" alt="Clear" title="Clear" ' +
						'LANGUAGE=javascript onClick="this.form.user_id.value=\'\'; ' +
						'this.form.user_name.value=\'\';return false;" align="absmiddle" ' +
						'style="cursor:hand;cursor:pointer">';
                return s;
        }

		function changeReportToAction(obj) {
			var div_obj = null;
			if(obj) {
				var reassign_type = obj.value;
				switch(reassign_type) {
					case 'user':
						div_obj = document.getElementById("wf46users");
						if(div_obj) div_obj.style.display = "block";
						div_obj = document.getElementById("wf46groups");
						if(div_obj) div_obj.style.display = "none";
						div_obj = document.getElementById("wf46reportto");
						if(div_obj) div_obj.style.display = "none";

						break;
					case 'group':
						div_obj = document.getElementById("wf46users");
						if(div_obj) div_obj.style.display = "none";
						div_obj = document.getElementById("wf46groups");
						if(div_obj) div_obj.style.display = "block";
						div_obj = document.getElementById("wf46reportto");
						if(div_obj) div_obj.style.display = "none";

						break;
					case 'reportto':
						div_obj = document.getElementById("wf46users");
						if(div_obj) div_obj.style.display = "none";
						div_obj = document.getElementById("wf46groups");
						if(div_obj) div_obj.style.display = "none";
						div_obj = document.getElementById("wf46reportto");
						if(div_obj) div_obj.style.display = "block";
						break;
					default:					
				}
					

				obj.form.user_id.value = "";
				obj.form.user_name.value = "";
				
				obj.form.group_id.value ="-1";
				obj.form.group_name.value = "";

			}
		}

        function setEmptyNamesToAllRieldValueFields()
        {
			// var obj = null;
                {/literal}{foreach from=$modules_list item=module_name name=modules}{literal}
				//	for (i = 1; i <= {/literal}{$modules_fields[$module_name.0]|@count}{literal}; i++)
					//{
						//obj = document.getElementById('wf41fc{/literal}{$smarty.foreach.modules.iteration}{literal}box' + i);
						//if(obj) obj.name = '';
					//}
                {/literal}{/foreach}{literal}
        }

		function toggle_permissions(taskfield) {
			var obj_ = document.getElementById("FpovManaged"+taskfield);
			var obj1 = document.getElementById("FpovReadPermission"+taskfield);
			var obj2 = document.getElementById("FpovWritePermission"+taskfield);
			var obj3 = document.getElementById("FpovMandatoryPermission"+taskfield);
			
			if(obj_.checked) {
			
				obj1.disabled = false;
				obj2.disabled = false;
				obj3.disabled = false;
			
				if(obj2.checked )
					obj1.checked = 1;	
				
				if(obj3.checked ) {
					obj1.checked = 1;
					obj2.checked = 1;	
				}
			
			} else {
				obj1.disabled = true;
				obj2.disabled = true;
				obj3.disabled = true;			
			
				obj1.checked = false;
				obj2.checked = false;
				obj3.checked = false;
			}
			
		}



function setAll(boolset,type) {
	var table = document.getElementById("rule_table"); 
	var checks = table.getElementsByTagName("input"); 
	
	for (var i = 0; i < checks.length; i++) {
		if(checks[i].id.indexOf(type)>-1) {
			var taskfield = checks[i].id.replace(type,"");
			checks[i].checked = boolset; 
			if(taskfield != "")
				toggle_permissions(taskfield);
		}
	}
}

function change_label(obj,val1,val2) {
	if (obj.value == val1) {
		obj.title = val2;
		obj.accesskey = val2;
		obj.value = val2;
	}
	/*
	else if (obj.value == val2) {
		obj.title = val1;
		obj.accesskey = val1;
		obj.value = val1;
	}
	*/
}
</script>
{/literal}

<style type="text/css">
.TaskInput {ldelim}width: 240px; {rdelim}
.TaskTextArea {ldelim}width: 460px; height: 240px;{rdelim}
</style>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" valign="top" width="100%">

        <div align=center>
        {if $PARENTTAB eq 'Settings'}
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'} {* crmv@30683 *}
        {/if}

		<form name="FpofvEditView" method="POST" action="index.php" ENCTYPE="multipart/form-data">
			<input type="hidden" name="module" value="Conditionals">
			<input type="hidden" name="record" value="{$ID}">
			<input type="hidden" name="sequence" value="{$SEQUENCE}">
			<input type="hidden" name="mode" value="{$MODE}">
			<input type='hidden' name='parenttab' value='{$PARENTTAB}'>
			<input type="hidden" name="activity_mode" value="{$ACTIVITYMODE}">
			<input type="hidden" name="action">
			<input type="hidden" name="return_module" value="{$RETURN_MODULE}">
			<input type="hidden" name="return_id" value="{$RETURN_ID}">
			<input type="hidden" name="return_action" value="{$RETURN_ACTION}">
			<input type="hidden" name="tz" value="Europe/Berlin">
			<input type="hidden" name="holidays" value="de,en_uk,fr,it,us,">
			<input type="hidden" name="workdays" value="0,1,2,3,4,5,6,">
			<input type="hidden" name="namedays" value="">
			<input type="hidden" name="weekstart" value="1">
			<input type="hidden" name="hour_format" value="{$HOUR_FORMAT}">
			<input type="hidden" name="start_hour" value="{$START_HOUR}">
			<input type="hidden" name="ruleid" value="{$smarty.request.ruleid}">
			<input type="hidden" name="total_conditions" value="">

	        <table width="100%"  border="0" cellspacing="0" cellpadding="0">
	        <tr><td align="left">
	                <table class="settingsSelUITopLine" border="0" cellpadding="5" cellspacing="0" width="100%">
	                <tr>
                        <td rowspan="2" style="width: 50px;"><img src="{'workflow.gif'|resourcever}" align="absmiddle"></td>
                        <td class="heading2">
                                <span aclass="lvtHeaderText">
                                {if $PARENTTAB neq ''}
                                <b> {$MOD.LBL_SETTINGS} &gt; <a href="index.php?module=Conditionals&action=index&parenttab=Settings">{$MOD.LBL_COND_MANAGER}</a> &gt; <!-- crmv@30683 -->
                                        {if $MODE eq 'edit'}
                                                {$APP.LBL_EDITING} &quot;{$WorkflowName}&quot;
                                        {else}
                                                {$UMOD.LBL_CREATE_NEW_CONDITIONAL}
                                        {/if}
                                        </b></span>
                                {else}
                                <span class="lvtHeaderText">
                                <b>{$APP.LBL_MY_PREFERENCES}</b>
                                </span>
                                {/if}
                        </td>
                        <td rowspan="2" nowrap>&nbsp;
                        </td>
                 </tr>
                </table>
	        </td>
	        </tr>
	        <tr><td>&nbsp;</td></tr>
        
       		<tr><td class="padTab" align="left">
			<table width="100%" border="0" cellpadding="0" cellspacing="0">

                <tr><td colspan="2">
					<table align="center" border="0" cellpadding="0" cellspacing="0" width="99%">
                        <tr>
                            <td align="left" valign="top">
								<table border="0" cellpadding="0" cellspacing="0" width="100%">
									<tr><td align="left" cospan="2">

						                <div class="workflow">
						                
						                	<table width="100%" cellspacing="0" cellpadding="5" border="0" class="tableHeading">
												<tr class="tableHeading">
													<td nowrap="nowrap" class="big">
														<strong>{$APP.LBL_SUMMARY}</strong>
													</td>
										        	<td nowrap align="right">
														<input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accesskey="{$APP.LBL_SAVE_BUTTON_KEY}" class="small crmbutton save"  name="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" onclick="this.form.action.value='Save'; ConditionalsUtils.verify_data_conditionals(FpofvEditView,function(){ldelim}FpofvEditView.submit();{rdelim},true);" style="width: 100px;" type="button" /> {* crmv@190416 *}
										            	<input title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accesskey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="small crmbutton cancel" name="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="window.history.back()" style="width: 100px;" type="button" />
										            </td>
												</tr>
											</table>
											<table cellspacing="0" cellpadding="0" border="0" width="100%">
				                        		<tr height="30px">
				                        			<td class="dvtCellLabel" width="20%" align="right"><b>{$UMOD.LBL_FPOFV_RULE_NAME}</b></td>
				                        			<td align="left">
				                        				<div class="dvtCellInfo">
						                                	<input type="text" name="workflow_name" value="{$WorkflowName}" class="detailedViewTextBox">
						                                	<input type="hidden" name="sequence" value="{$FPOFV_PIECE_DATA.0.FpofvSequence}" >
						                                </div>
					                            	</td>
					                            </tr>
						
					                        	<tr height="30px">
					                        		<td class="dvtCellLabel" width="20%" align="right"><b>{$UMOD.LBL_FPOFV_MODULE_NAME}</b></td>
					                        		<td align="left">
					                        			<div class="dvtCellInfo">
							                                <select onChange="resetConditions(getObj('moduleName').value);" name="module_name" id="moduleName" class="detailedViewTextBox">
						                                        {foreach from=$modules_list item=module_name name=modules}
						                                        	{assign var="modulelabel" value=$module_name.1|@getTranslatedString:$module_name.1}	<!-- crmv@16886 -->
							                                        <option value="{$module_name.0}"{if $ModuleName eq $module_name.0} selected{/if}>{$modulelabel}</option> <!-- crmv@16886 -->
						                                        {/foreach}
							                                </select>
						                                </div>
						                            </td>
					                        	</tr>
								                
						                        <tr height="30px">
						                        	<td class="dvtCellLabel" width="20%" align="right"><b>{$UMOD.LBL_FPOFV_CRITERIA_NAME}</b></td>
						                            <td align="left">
						                            	<div class="dvtCellInfo">{$ROLE_GRP_CHECK_PICKLIST}</div>
						                            </td>
						                        </tr>
						                   </table>
						                   
						                   <br />
						                   
						                   <table width="100%" cellspacing="0" cellpadding="5" border="0" class="tableHeading">
												<tr class="tableHeading">
													<td nowrap="nowrap" class="big">
														<strong>{$UMOD.LBL_FPOFV_FIELD_NAME}</strong>
													</td>
													<td nowrap align="right">
														<span id="workflow_loading" style="display:block;">
														  <b>{$APP.LBL_LOADING}</b>{include file="LoadingIndicator.tpl"}
														</span>
														<input id="add_rule" title="{$UMOD.ADD_RULES}" accesskey="{$UMOD.ADD_RULES}" class="small crmbutton save" onclick="fnAddProductRow(getObj('moduleName').value);" name="button" value="{$UMOD.ADD_RULES}" type="button" style='display: none;'/>
													</td>
												</tr>
											</table>
											
											<script>var rowCnt = 0;</script>
											<table cellspacing="0" cellpadding="0" border="0" width="100%" id="proTab">   
						                        {if $MODE eq 'edit'}
					                        		{foreach from=$Rules key=key item=rule}
					                        			<script>
					                        				fnAddProductRow(getObj('moduleName').value,'{$rule.chk_fieldname}','{$rule.chk_criteria_id}','{$rule.chk_field_value|escape:'javascript'}'); {* crmv@173596 *}
					                        			</script>
					                        		{foreachelse}	
					                        			<script>
					                        				getObj('workflow_loading').style.display='none';
	        												getObj('add_rule').style.display='block';
					                        			</script>
					                        		{/foreach}
					                        	{else}
					                        		<script>
				                        				fnAddProductRow(getObj('moduleName').value);
				                        			</script>
						                        {/if}
											</table>
										</div>
									</td></tr>
								</table>
								
								<br />
								
								<table width="100%" cellspacing="0" cellpadding="5" border="0" class="tableHeading">
									<tr class="tableHeading">
										<td nowrap="nowrap" class="big">
											<strong>{$UMOD.LBL_FPOFV_ACTION_NAME}</strong>
										</td>
										<td align='right'>
										{if $MODE eq 'edit'}
											{assign var=label value=LBL_ST_RESET}
										{else}
											{assign var=label value=LBL_ST_SHOW}
										{/if}
		                            	<input title="{$UMOD.$label}" accesskey="{$UMOD.$label}" class="small crmbutton save"  name="button" value="{$UMOD.$label}"  onclick="ConditionalsUtils.verify_data_conditionals(FpofvEditView,function(){ldelim}load_field_permissions_table();change_label(this,'{$UMOD.LBL_ST_SHOW}','{$UMOD.LBL_ST_RESET}');{rdelim},false);" style="width: 100px;" type="button" /> {* crmv@190416 *}
			                            </td>
									</tr>
								</table>

                        <div class="wf4">
	                        <div>
								<input {if $MODE eq 'edit'} disabled {/if} type="hidden" name="task" id="taskbox" value="FieldChange" >
							</div>
                        </div>
                        
						<div style="margin-bottom: 20px;display: {$FIELD_PERMISSIONS_DISPLAY};" id='field_permissions_table' name='field_permissions_table' > 
	                   		{include file="modules/Conditionals/FieldTable.tpl"}
						</div>

                </div>
                                <br>
                                            </table>
                                         </td></tr>
                                        </table>
                                     </td></tr>
                                   </table>
                                 <br>
                                  </td></tr>
                                </table>
                                {include file='Settings/ScrollTop.tpl'}
                        </td>
                        </tr>
                        </table>
                        </form>
</td>
</tr>
</table>
</td></tr></table>
<br>
{$JAVASCRIPT}
<!-- crmv@manuele e -->