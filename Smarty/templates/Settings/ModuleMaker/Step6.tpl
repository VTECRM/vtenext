{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@64542 *}

<div>
	<p>{$MOD.LBL_MMAKER_STEP6_INTRO}</p>
</div>

<div id="mmaker_step6_properties" {if $USEREDIT}style="display:none"{/if}>
<table border="0" width="100%">

	<tr>
		<td class="mmaker_step_field_cell" align="right" width="20%"><span>{$MOD.LBL_SHARING_ACCESS}</span>&nbsp;&nbsp;</td>
		<td align="left" width="250">
			<div class="dvtCellInfo">
				<select class="detailedViewTextBox" name="mmaker_sharing_access" id="mmaker_sharing_access">
					{foreach item=shareaction from=$SHARINGACTIONS}
						<option value="{$shareaction.code}" {if $STEPVARS.mmaker_sharing_access eq $shareaction.code}selected=""{/if}>{$shareaction.label}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td width="50">&nbsp;</td>
		<td>
			{$MOD.LBL_ENABLE_QUICKCREATE_DESC}
		</td>
	</tr>
	
	{if !$IS_INVENTORY} {* crmv@205449 *}
	
	<tr>
		<td class="mmaker_step_field_cell" colspan="4">{$MOD.LBL_NEXT_FLAGS_FOR_ALL_PROFILES}</td>
	</tr>
	
	<tr>
		<td class="mmaker_step_field_cell" align="right" width="20%"><span>{$MOD.LBL_ENABLE_QUICKCREATE}</span>&nbsp;&nbsp;</td>
		<td align="left" width="250">
			<div class="dvtCellInfo">
				<input type="checkbox" name="mmaker_enable_quickcreate" id="mmaker_enable_quickcreate" {if $STEPVARS.mmaker_enable_quickcreate}checked=""{/if} />
			</div>
		</td>
		<td width="50">&nbsp;</td>
		<td>
			{$MOD.LBL_ENABLE_QUICKCREATE_DESC}
		</td>
	</tr>
	
	<tr>
		<td class="mmaker_step_field_cell" align="right" width="20%"><span>{$MOD.LBL_ENABLE_IMPORT}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<input type="checkbox" name="mmaker_enable_import" id="mmaker_enable_import" {if $STEPVARS.mmaker_enable_import}checked=""{/if} />
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_ENABLE_IMPORT_DESC}
		</td>
	</tr>
	
	<tr>
		<td class="mmaker_step_field_cell" align="right" width="20%"><span>{$MOD.LBL_ENABLE_EXPORT}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<input type="checkbox" name="mmaker_enable_export" id="mmaker_enable_export" {if $STEPVARS.mmaker_enable_export}checked=""{/if} />
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_ENABLE_EXPORT_DESC}
		</td>
	</tr>
	
	<tr>
		<td class="mmaker_step_field_cell" align="right" width="20%"><span>{$MOD.LBL_ENABLE_DUPCHECK}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="dvtCellInfo">
				<input type="checkbox" name="mmaker_enable_dupcheck" id="mmaker_enable_dupcheck" {if $STEPVARS.mmaker_enable_dupcheck}checked=""{/if} />
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			{$MOD.LBL_ENABLE_DUPCHECK_DESC}
		</td>
	</tr>
	{/if} {* crmv@205449 *}
	
	{if $CAN_EDIT_SCRIPTS}
	<tr>
		<td class="mmaker_step_field_cell" align="right" width="20%"><span>{$MOD.LBL_EDIT_MODULE_SCRIPTS}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<div class="">
				<input type="button" class="crmbutton create" title="{$MOD.LBL_BUTTON_EDIT_SCRIPTS}" value="{$MOD.LBL_BUTTON_EDIT_SCRIPTS}" onclick="ModuleMakerCodeEditor.openScriptEditor()" />
			</div>
		</td>
		<td>&nbsp;</td>
		<td>
			
		</td>
	</tr>
	{/if}
	
</table>
</div>

{* The block for editing scripts *}
{if $CAN_EDIT_SCRIPTS}
<div id="mmaker_div_code_editor" {if !$USEREDIT}style="display:none"{/if}>

	{* scripts and styles *}
	<link rel="stylesheet" type="text/css" media="screen" href="include/js/codemirror/lib/codemirror.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="include/js/codemirror/theme/ambiance.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="include/js/codemirror/theme/eclipse.css" />
	{* Custom styles for the editor *}
	<style type="text/css">
		{literal}
		.CodeMirror {
			border: 1px solid #909090;
			border-radius: 5px;
			-webkit-border-radius: 5px;
			-moz-border-radius: 5px;
		}
		{/literal}
	</style>
	<script type="text/javascript" src="include/js/codemirror/lib/codemirror.js"></script>
	<script type="text/javascript" src="include/js/codemirror/mode/clike/clike.js"></script>
	<script type="text/javascript" src="include/js/codemirror/mode/css/css.js"></script>
	<script type="text/javascript" src="include/js/codemirror/mode/xml/xml.js"></script>
	<script type="text/javascript" src="include/js/codemirror/mode/javascript/javascript.js"></script>
	<script type="text/javascript" src="include/js/codemirror/mode/htmlmixed/htmlmixed.js"></script>
	<script type="text/javascript" src="include/js/codemirror/mode/php/php.js"></script>
	
	{* variables *}
	<input type="hidden" id="mmaker_useredit" value="{if $USEREDIT}1{else}0{/if}" />
	
	{* title *}
	<div id="mmaker_code_desc" {if !$USEREDIT}style="display:none"{/if}>
		<br>
		<p>{$MOD.LBL_USEREDIT_EDIT_CODE_DESC}</p>
		<br>
	</div>
	
	{* navigation *}
	<table border="0" width="100%">
		<tr>
			<td align="left">
				<input type="button" class="crmbutton cancel" id="mmaker_code_cancel_btn" {if $USEREDIT}style="display:none"{/if} value="{$APP.LBL_CANCEL_BUTTON_LABEL}" title="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="ModuleMakerCodeEditor.closeScriptEditor()"/>
			</td>
			<td align="center">
			{$APP.LBL_CHOOSE_FILE}:
			<select id="mmaker_code_select" onchange="ModuleMakerCodeEditor.loadEditableScript('{$MODULEID}')">
				<option value=""></option>
				{foreach key=val item=label from=$EDITABLE_SCRIPTS}
					<option value="{$val}">{$label}</option>
				{/foreach}
			</select>
			<input type="button" class="crmbutton save" id="mmaker_code_save_btn" style="visibility:hidden" value="{$APP.LBL_SAVE_FILE}" title="{$APP.LBL_SAVE_FILE}" onclick="ModuleMakerCodeEditor.saveEditableScript('{$MODULEID}')"/>
			</td>
			<td align="right" width="50">
				<div id="mmaker_busy" style="display:none;">{include file="LoadingIndicator.tpl"}</div>
			</td>
			<td align="right" width="100">
				<input type="button" class="crmbutton cancel" id="mmaker_code_reset_btn" {if !$USEREDIT}style="display:none"{/if} value="{$MOD.LBL_RESTORE_CHANGED_FILES}" title="{$MOD.LBL_RESTORE_CHANGED_FILES}" onclick="ModuleMakerCodeEditor.resetEditableScripts('{$MODULEID}')"/>
			</td>
		</tr>
	</table>
	<br>
	{* the code editor *}
	<div style="width:100%">
	<textarea id="mmaker_code_editor" style="width:100%" onkeyup="ModuleMakerCodeEditor.changeCode()"></textarea>
	</div>
	
	{* init the editor *}
	<script type="text/javascript">
		{literal}
		(function() {
			ModuleMakerCodeEditor.initEditor();
		})();
		{/literal}
	</script>
</div>
{/if}