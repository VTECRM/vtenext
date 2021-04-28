{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<div style="position:relative;display: block;" id="orgLay" class="layerPopup">
	<table border="0" cellpadding="5" cellspacing="0" class="layerHeadingULine">
		<tr>
			<td class="layerPopupHeading" align="left" width="40%" nowrap>{$MOD.ADD_PICKLIST_VALUES} - {$FIELDLABEL}</td>
		</tr>
	</table>

	<table border=0 cellspacing=0 cellpadding=5 width=100%>
		<tr>	
			<td rowspan=3 valign=top align=left width=250px;>	
				<b>{$MOD.LBL_EXISTING_PICKLIST_VALUES}</b>
				<div id="add_availPickList" name="availList" style="overflow:auto; height:170px; width:200px; border:1px solid #666666;font-family:Arial, Helvetica, sans-serif;font-size:11px;"> 				
					<table style="padding-left:5px" cellpadding="0" cellspacing="3" width="100%">
						{foreach item=pick_val from=$PICKVAL}
							<tr style="background-color: #ffffff;" height="20">
								<td>
									<span class="picklist_existing_options">{$pick_val}</span>
								</td>
							</tr>
						{/foreach}
					</table>
				</div>
				<br>
				{if is_array($NONEDITPICKLIST)}				
					<b>{$MOD.LBL_NON_EDITABLE_PICKLIST_ENTRIES} :</b>
					<div id="nonedit_pl_values" name="availList" style="overflow:auto; height:170px; width:200px; border:1px solid #666666; font-family:Arial, Helvetica, sans-serif;font-size:11px;">
						<table style="padding-left:5px" cellspacing="3" cellpadding="0" width="100%">
							{foreach item=nonedit from=$NONEDITPICKLIST}
								<tr style="background-color: #ffffff;">
									<td>
									<span class="picklist_noneditable_options">
										{$nonedit}		
									</span>							
									</td>
								</tr>	
							{/foreach}
						</table>
					</div>
				{/if}
			</td>
			
			<td valign=top align=left width=300px;>
				<b>{$MOD.LBL_PICKLIST_ADDINFO}</b>
				<textarea id="add_picklist_values" class="detailedViewTextBox" style="width:250px; border:1px solid #666666;" align="left" rows="10"></textarea>
			</td>
		</tr>
		<tr>
			<td valign=top align=left width=300px;>
				<b>{$MOD.LBL_SELECT_ROLES} </b><br />
				<select id="add_availRoles" multiple="multiple" wrap size="5" name="add_availRoles" style="width:250px;border:1px solid #666666;font-family:Arial, Helvetica, sans-serif;font-size:11px;">
					{foreach key=role_id item=role_details from=$ROLEDETAILS}
						<option value="{$role_id}">{$role_details.0}</option>
					{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td valign=top align=right>
				<input type="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" id="saveAddButton" name="save" class="crmButton small edit" onclick="validateAdd('{$FIELDNAME}','{$MODULE}');">
				<input type="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" name="cancel" class="crmButton small cancel" onclick="fnhide('actiondiv');">
			</td>			
		</tr>
	</table>
</div>