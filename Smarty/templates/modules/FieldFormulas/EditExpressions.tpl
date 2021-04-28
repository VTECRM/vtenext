{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@109907 *}
<script src="modules/FieldFormulas/resources/functional.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript" charset="utf-8">
	var strings = {$JS_STRINGS};
	var meta_fieldnames = new Array({$VALIDATION_DATA_FIELDNAME});
	var meta_fieldlabels = new Array({$VALIDATION_DATA_FIELDLABEL});
	var meta_fielddatatypes = new Array({$VALIDATION_DATA_FIELDDATATYPE});
	var meta_fielduitype = new Array({$VALIDATION_DATA_FIELDUITYPE}); // crmv@83877
	var meta_fieldwstype = new Array({$VALIDATION_DATA_FIELDWSTYPE}); //crmv@112297
</script>
<script src="modules/FieldFormulas/resources/editexpressionscript.js" type="text/javascript" charset="utf-8"></script>


<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">

<div align=center>
		{include file='SetMenu.tpl'}
		
<!-- DISPLAY -->
<div id="view">
	{include file='modules/FieldFormulas/ModuleTitle.tpl'}
	<input type="hidden" id="pick_module" name="pick_module" value={$FORMODULE} />
	<table class="tableHeading" width="100%" border="0" cellspacing="0" cellpadding="5" align="center">
		<tr>
			<td class="big" nowrap="nowrap">
				<strong><span id="module_info">{$MOD.LBL_MODULE_INFO} "{$FORMODULE|@getTranslatedString:$MODULE}"</span></strong>
			</td>
			<td class="small" align="right">
				<span id="new_field_expression_busyicon"><b>{$MOD.LBL_CHECKING}</b>{include file="LoadingIndicator.tpl"}</span>
				<input type="button" class="crmButton create small"
					value="{'LBL_NEW_FIELD_EXPRESSION_BUTTON'|@getTranslatedString:$MODULE}" id='new_field_expression' style="display: none;"/>
					
				<span id="status_message" class="helpmessagebox" style='font-weight: bold; display: none;'></span>
			</td>
		</tr>
	</table>
	<br>
	
	{assign var="FLOAT_TITLE" value='LBL_EDIT_EXPRESSION'|@getTranslatedString:$MODULE}
	{assign var="FLOAT_WIDTH" value="650px"}
	{capture assign="FLOAT_CONTENT"}
		<table width="100%" bgcolor="white" align="center" border="0" cellspacing="0" cellpadding="5">
			<tr>
				<td class='dvtCellLabel' align="right">
					<b>{'LBL_TARGET_FIELD'|@getTranslatedString:$MODULE}</b>
				</td>
				<td class='dvtCellInfo'> 
					<select id='editpopup_field' class='small'><option></option></select>
				</td>
			</tr>
			<tr valign="top">
				<td class='dvtCellLabel' align="right">
					<b>{'LBL_EXPRESSION'|@getTranslatedString:$MODULE}</b>
				</td>
				<td class='dvtCellInfo' align="left">
					<table width="100%" border="0" cellspacing="0" cellpadding="2" align="center">
						<tr valign="top">
							<td>
								<select id='editpopup_fieldnames' class='small'></select>
							
								<select id='editpopup_functions' class='small'>
									<option value="">{$MOD.LBL_USE_FUNCTION_DASHDASH}</option>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<td>
								<textarea name="Name" rows="10" cols="50" id='editpopup_expression'></textarea>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<table width="100%" cellspacing="0" cellpadding="5" border="0" class="layerPopupTransport">
			<tr><td align="center">
				<input type="button" class="crmButton small save" value="{$APP.LBL_SAVE_BUTTON_LABEL}" name="save" id='editpopup_save'/> 
				<input type="button" class="crmButton small cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" name="cancel" id='editpopup_cancel'/>
			</td></tr>
		</table>
		
		<table width="100%" cellspacing="1" cellpadding="5" border="0" class="helpmessagebox">
			<tr valign="top">
				<td><b>{$MOD.LBL_TARGET_FIELD}</b></td>
				<td><b>{$MOD.LBL_EXPRESSION}</b></td>
			</tr>
			<tr valign="top">
				<td>Custom Revenue</td>			
				<td><i>annual_revenue</i> / 12</td>
			</tr>			
			<tr valign="top">
				<td>Full Name</td>
				<td>
					<font color=blue>if</font> <i>mailingcountry</i> == "India" <font color=blue>then</font> <font color=blue>concat</font>(<i>firstname</i>," ",<i>lastname</i>) <font color=blue>else</font> <font color=blue>concat</font>(<i>lastname</i>," ",<i>firstname</i>) <font color=blue>end</font>
				</td>
			</tr>			
		</table>
	{/capture}
	{include file="FloatingDiv.tpl" FLOAT_ID="editpopup" FLOAT_BUTTONS=""}
		
	<table class="listTable" width="100%" border="0" cellspacing="0" cellpadding="5" id='expressionlist'>
		<tr>
			<td class="colHeader small" width="20%">
				{'LBL_FIELD'|@getTranslatedString:$MODULE}				
			</td>
			<td class="colHeader small" width="65">
				{'LBL_EXPRESSION'|@getTranslatedString:$MODULE}
			</td>
			<td class="colHeader small" width="15%">
				{'LBL_SETTINGS'|@getTranslatedString:$MODULE}
			</td>
		</tr>
	</table>
	<div align='right' style='padding-right: 5px;' id="expressionlist_busyicon"><b>{$MOD.LBL_CHECKING}</b>{include file="LoadingIndicator.tpl"}</div>
</div>

</td>
        </tr>
        </table>
        </td>
        </tr>
        </table>
        </div>

        </td>
        <td valign="top"></td>
        </tr>
</tbody>
</table>
<br>