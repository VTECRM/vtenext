{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<div id="nlWizStep2" style="display:none;">
	<div id="nlw_templateDetails">
	<p>{$MOD.NowChooseATemplate}:</p>
	<div style="min-height:190px;overflow-y:auto">
		{$TPLLIST}
		<input type="hidden" id="nlw_templateid" value="" />
	</div>

	<div id="nlw_templatePreviewHeader" style="display:none">
	<table border="0" cellspacing="0" cellpadding="0" width="100%">
		<tr>
			<td align="left"><b>{$APP.LBL_PREVIEW}</b></td>
			{if $CAN_EDIT_TEMPLATES}
			<td align="right"><input type="button" class="crmbutton edit" id="nlw_temlateEditButton" value="{$APP.LBL_EDIT_BUTTON}" style="display:none" onclick="VTE.GrapesEditor.showGrapesDiv(null, jQuery('#nlw_templateid').val());"></td>	{* crmv@59091 *}
			{/if}
		</tr>
	</table>
	</div>

	<div id="nlw_templatePreviewCont" style="display:none;">
		<table border="0" cellspacing="1" cellpadding="1" width="100%" height="100%">
			<tr>
				<td width="100" valign="top" height="16" style="border-bottom:1px solid #e0e0e0"><i>{$APP.LBL_SUBJECT}:</i></td>
				<td valign="top" height="16" style="border-bottom:1px solid #e0e0e0"><div id="nlw_templatePreviewSubject"></div></td>
			</tr>
			<tr><td valign="top"></td><td><div id="nlw_templatePreviewBody" class="template-preview-body" style=""></div></td></tr>
		</table>
	</div>
	</div>

	{if $CAN_EDIT_TEMPLATES}
	<div id="nlw_templateEditCont" style="display:none">
		<input type="hidden" id="nlw_templateEditId" value="" />
		<input type="hidden" id="template_editor" value="{$TEMPLATE_EDITOR}" />	{* crmv@197575 *}

		<table border="0" cellspacing="0" cellpadding="0" width="100%">
			<tr>
				<td align="left"><input type="button" class="crmbutton cancel" onclick="nlwCancelEditTemplate()" id="nlw_cancelEditTemplate" value="&lt; {$APP.LBL_CANCEL_BUTTON_LABEL}"></td>
				<td align="right"><div id="nlw_templateEditlIndicator" style="width:50px;display:none;">{include file="LoadingIndicator.tpl"}</div></td>
				<td align="right" width="60"><input type="button" class="crmbutton save" onclick="nlwSaveTemplate()" id="nlw_saveTemplate" value="{$APP.LBL_SAVE_LABEL}"></td>
			</tr>
		</table>
		<br>
		
		<table border="0" width="100%" style="margin-bottom:5px">
			{*crmv@104558*}
			<tr><td>{'LBL_NAME'|getTranslatedString:'Settings'}:</td><td><div class="dvtCellInfoM"><input type="text" class="detailedViewTextBox" id="nlw_template_name" name="nlw_template_name" value=""></div></td></tr>
			<tr><td>{$APP.LBL_DESCRIPTION}:</td><td><div class="dvtCellInfo"><input type="text" class="detailedViewTextBox" id="nlw_template_description" value=""></div></td></tr>
			<tr><td>{$APP.LBL_SUBJECT}:</td><td><div class="dvtCellInfoM"><input type="text" class="detailedViewTextBox" id="nlw_template_subject" value=""></div></td></tr> {* crmv@151466 *}
			{*crmv@104558e*}
			{* crmv@168109 *}
			{if $BU_MC_ENABLED}
			<tr>
				<td>Business Unit</td>
				<td>
					<div class="dvtCellInfoM">
						<select id="nlw_template_bu_mc" name="nlw_template_bu_mc[]" class="detailedViewTextBox" multiple>
						{foreach item=arr from=$BU_MC}
							<option value="{$arr.value}" {$arr.selected}>{$arr.label}</option>
						{/foreach}
						</select>
					</div>
				</td>
			</tr>
			{/if}
			{* crmv@168109e *}
			{if $TEMPLATE_EDITOR neq 'grapesjs'}	{* crmv@197575 *}
			<tr><td width="20%">{$MOD.InsertVariable}:</td><td>

				<table border="0" width="100%">
				<tr style="display:none;"> {*crmv@104558*}
					<td>{'LBL_STEP'|getTranslatedString:'Settings'}1</td>
					<td>{'LBL_STEP'|getTranslatedString:'Settings'}2</td>
					<td>{'LBL_STEP'|getTranslatedString:'Settings'}3</td>
				</tr>
				<tr>
					<td>
						<div class="dvtCellInfo">
							<select class="detailedViewTextBox" id="entityType" onchange="modifyMergeFieldSelect(this, document.getElementById('mergeFieldSelect'));">
								<option value="None" selected="">{$APP.LBL_NONE}</option>
								{foreach key=module item=arr from=$TPLVARIABLES}
									<option value="{$module}">{$module|@getTranslatedString:$module}</option>
								{/foreach}
							</select>
						</div>
					</td>
					<td>
						<div class="dvtCellInfo">
							<select class="detailedViewTextBox" id="mergeFieldSelect" onchange="jQuery('#mergeFieldValue').val(jQuery(this).val());">
								<option value="" selected>{$APP.LBL_NONE}</option>
							</select>
						</div>
					</td>
					<td>
						<div class="dvtCellInfo">
							<input type="text" class="detailedViewTextBox" id="mergeFieldValue" name="variable" value="" />
						</div>
					</td>
					<td>
						<input class="crmbutton create" type="button" onclick="InsertIntoTemplate('mergeFieldValue');" value="{'LBL_INSERT_INTO_TEMPLATE'|getTranslatedString}">
					</td>
				</tr>
				</table>

			</td></tr>
			{/if}	{* crmv@197575e *}
		</table>
		
		<div class="cellInfo">
			{* crmv@197575 *}
			{if $TEMPLATE_EDITOR eq 'grapesjs'}
				<textarea name="nlw_template_body" style="width:90%;height:315px; display:none; " class=small tabindex="5"></textarea>
				<iframe allowfullscreen id="grapes_editor" id="grapes_editor" style="width: 100%; height: 950px; border:none;" src=""></iframe>
			{else}
				<textarea name="nlw_template_body" style="width:90%;height:315px" class=small tabindex="5"></textarea>
			{/if}
			{* crmv@197575e *}
		</div>
	</div>

		{* crmv@197575 *}
		{if $TEMPLATE_EDITOR eq 'grapesjs'}
			<script src="modules/SDK/src/Grapes/Grapes.js"></script>
		{else}
			<script type="text/javascript" defer="1">
				var curr_lang = '{$CALENDAR_LANG}';
				CKEDITOR.replace('nlw_template_body', {ldelim}
					filebrowserBrowseUrl: 'include/ckeditor/filemanager/index.html',
					//toolbar : 'Basic',
					language : curr_lang,
					{* crmv@56235 *}
					{literal}
					on: {
						'instanceReady': function (evt) { 
							//crmv@104558
							//evt.editor.resize('100%', jQuery('#nlWizRightPane').height() - 305);
							evt.editor.resize('100%', jQuery('#nlWizRightPane').height());
							//crmv@104558e
						}
					}
					{/literal}
					{* crmv@56235e *}
				{rdelim});

				// template variables initialization
				allTplOptions = {$TPLVARIABLES|@json_encode};
				allTplOptions['None'] = [['{$APP.LBL_NONE}', 'None']];
			</script>
		{/if}
		{* crmv@197575e *}
	{/if}
</div>