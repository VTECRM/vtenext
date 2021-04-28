{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@187729 *}

{literal} 
<style type="text/css"> 

.error {    
	font-size: 16px; 
	font-style: italic;
	font-weight: bold; 
	color:red;
	text-align:center;  
	padding-top: 20px;
} 

</style> 
{/literal}    

{include file='CachedValues.tpl'}
{include file='modules/SDK/src/Reference/Autocomplete.tpl'}

{include file='Settings/ProcessMaker/actions/Create.tpl' SKIP_EDITFORM=1}

<div id="editForm">
<table border="0" cellpadding="10" width="100%" cellspacing="0" class="small" align="center">
	<tr>
		<td align=right width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" mandatory=true label='LBL_PDF_ENTITY'|getTranslatedString:'PDFMaker'}
		</td>
		<td align="left">
			<div class="dvtCellInfo">
				<select name="pdf_entity" id="pdf_entity" class="detailedViewTextBox" onChange="ActionCreatePDFScript.reload_createpdf_form('CreatePDF','{$ID}','{$ELEMENTID}','{$ACTIONTYPE}','{$ACTIONID}', '', 'edit')">
					{foreach key=k item=i from=$RECORDS_INVOLVED}
						{if isset($i.group)}
							<optgroup label="{$i.group}">
								{foreach key=kk item=ii from=$i.values}
									<option value="{$kk}" {$ii.1}>{$ii.0}</option>
								{/foreach}
							</optgroup>
						{else}
							<option value="{$k}" {$i.1}>{$i.0}</option>
						{/if}
					{/foreach}
				</select>
			</div>
		</td>
		<td align=right width=15% nowrap="nowrap">&nbsp;</td>
	</tr>
	<tr>
		<td align="right" width="15%" nowrap="nowrap"></td>
		<td id="error_container" class="error">{$ERROR}</td>
		<td align=right width=15% nowrap="nowrap">&nbsp;</td>
	</tr>
	<tr>{include file='salesEditView.tpl' HIDE_BUTTON_LIST=1}
	</tr>
</div>

</table>

<script type="text/javascript">
jQuery(document).ready(function() {ldelim}
	ActionCreatePDFScript.loadForm('CreatePDF','{$ID}','{$ELEMENTID}','{$ACTIONTYPE}','{$ACTIONID}', '', '{$MODE}');

	jQuery('#pdf_entity_type').change(function() 
		{ldelim}
			ActionCreatePDFScript.reload_createpdf_form('CreatePDF','{$ID}','{$ELEMENTID}','{$ACTIONTYPE}','{$ACTIONID}', '', 'edit');
		{rdelim}
	);
	jQuery('#other_pdf_entity').focus(function() 
		{ldelim}
			ActionCreatePDFScript.reload_createpdf_form('CreatePDF','{$ID}','{$ELEMENTID}','{$ACTIONTYPE}','{$ACTIONID}', '', 'edit');
		{rdelim}
	);

	{if $MODE eq 'create'}
		jQuery('#block_0').css("display", "none");
		jQuery('#block_1').css("display", "none");
	{/if}
{rdelim});
</script>