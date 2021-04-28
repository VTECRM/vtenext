{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{if $ENABLE_PDFMAKER eq 'true'}

	<table border=0 cellspacing=0 cellpadding=0 style="width:100%;">
		
		{if $CRM_TEMPLATES_EXIST eq '0'}
			<tr>
				<td class="rightMailMergeContent" width="100%">
					<div class="dvtCellInfo">
						<select name="use_common_template" id="use_common_template" class="detailedViewTextBox pdfmakerTemplateList" multiple size="5">
							{foreach name="tplForeach" from=$CRM_TEMPLATES item=templates_label key=templates_prefix} 
								{if $smarty.foreach.tplForeach.first}
									<option value="{$templates_prefix}" selected="selected">{$templates_label}</option>
								{else}
									<option value="{$templates_prefix}">{$templates_label}</option>
								{/if} 
							{/foreach}
						</select>
					</div>
				</td>
			</tr>
		
			{if $TEMPLATE_LANGUAGES|@sizeof > 1}
				<tr>
					<td class="rightMailMergeContent">
						<div class="dvtCellInfo">
							<select name="template_language" id="template_language" class="detailedViewTextBox" size="1"> 
								{html_options options=$TEMPLATE_LANGUAGES selected=$CURRENT_LANGUAGE}
							</select>
						</div>
					</td>
				</tr>
			{else} 
				{foreach from=$TEMPLATE_LANGUAGES item=lang key=lang_key}
					<input type="hidden" name="template_language" id="template_language" value="{$lang_key}" />
				{/foreach} 
			{/if}
			
			{* crmv@59091 *}
			
			<tr>
				<td class="rightMailMergeContent">
					<div class="pdfActionWrap">
						<div class="pdfActionImage">
							<a href="javascript:;" onclick="VTE.PDFMakerActions.onGeneratePDF(this,'{$MODULE}','{$ID}');" class="withoutripple">
								<img src="{'modules/PDFMaker/img/actionGeneratePDF.png'|resourcever}" hspace="5" align="absmiddle" border="0" />
							</a>
						</div>
						<div class="pdfActionText">
							<a href="javascript:;" onclick="VTE.PDFMakerActions.onGeneratePDF(this,'{$MODULE}','{$ID}');" class="webMnu withoutripple">{$APP.LBL_EXPORT_TO_PDF}</a>
						</div>
					</div>
				</td>
			</tr>
		
			<tr>
				<td class="rightMailMergeContent">
					<div class="pdfActionWrap">
						<div class="pdfActionImage">
							<a href="javascript:;" onclick="VTE.PDFMakerActions.onSendPDFmail(this,'{$MODULE}','{$ID}');" class="withoutripple">
								<img src="{'modules/PDFMaker/img/PDFMail.png'|resourcever}" hspace="5" align="absmiddle" border="0" />
							</a>
						</div>
						<div class="pdfActionText">
							<a href="javascript:;" onclick="VTE.PDFMakerActions.onSendPDFmail(this,'{$MODULE}','{$ID}');" class="webMnu withoutripple">{$APP.LBL_SEND_EMAIL_PDF}</a>
						</div>
					</div>
				</td>
			</tr>
		
			<tr>
				<td class="rightMailMergeContent">
					<div class="pdfActionWrap">
						<div class="pdfActionImage">
							<a href="javascript:;" onclick="VTE.PDFMakerActions.onEditAndGeneratePDF(this,'{$MODULE}','{$ID}');" class="withoutripple">
								<img src="{'modules/PDFMaker/img/PDF_edit.png'|resourcever}" hspace="5" align="absmiddle" border="0" />
							</a>
						</div>
						<div class="pdfActionText">
							<a href="javascript:;" onclick="VTE.PDFMakerActions.onEditAndGeneratePDF(this,'{$MODULE}','{$ID}');" class="webMnu withoutripple">{$APP.LBL_EDIT}{$APP.AND} {$APP.LBL_EXPORT_TO_PDF}</a>
						</div>
					</div>
				</td>
			</tr>
		
			<tr>
				<td class="rightMailMergeContent">
					<div class="pdfActionWrap">
						<div class="pdfActionImage">
							<a href="javascript:;" onclick="VTE.PDFMakerActions.onSaveAsDocument(this,'{$MODULE}','{$ID}');" class="withoutripple">
								<img src="{'modules/PDFMaker/img/PDFDoc.png'|resourcever}" hspace="5" align="absmiddle" border="0" />
							</a>
						</div>
						<div class="pdfActionText">
							<a href="javascript:;" onclick="VTE.PDFMakerActions.onSaveAsDocument(this,'{$MODULE}','{$ID}');" class="webMnu withoutripple">{$PDFMAKER_MOD.LBL_SAVEASDOC}</a>
						</div>
					</div>
				</td>
			</tr>
		
			{if $MODULE eq 'Invoice' || $MODULE eq 'SalesOrder' || $MODULE eq 'PurchaseOrder' || $MODULE eq 'Quotes' || $MODULE eq 'Receiptcards' || $MODULE eq 'Issuecards' || $MODULE eq 'Ddt'}
				<tr>
					<td class="rightMailMergeContent">
						<div class="pdfActionWrap">
							<div class="pdfActionImage">
								<a href="javascript:;" onclick="VTE.PDFMakerActions.onGetPDFBreaklineDiv(this,'{$MODULE}','{$ID}');" class="withoutripple">
									<img src="{'modules/PDFMaker/img/PDF_bl.png'|resourcever}" hspace="5" align="absmiddle" border="0" />
								</a>
							</div>
							<div class="pdfActionText">
								<a href="javascript:;" onclick="VTE.PDFMakerActions.onGetPDFBreaklineDiv(this,'{$MODULE}','{$ID}');" class="webMnu withoutripple">{$PDFMAKER_MOD.LBL_PRODUCT_BREAKLINE}</a>
							</div>
						</div>
					</td>
				</tr>
		
				<tr>
					<td class="rightMailMergeContent">
						<div class="pdfActionWrap">
							<div class="pdfActionImage">
								<a href="javascript:;" onclick="VTE.PDFMakerActions.onGetPDFImagesDiv(this,'{$MODULE}','{$ID}');" class="withoutripple">
									<img src="{'modules/PDFMaker/img/PDF_img.png'|resourcever}" hspace="5" align="absmiddle" border="0" />
								</a>
							</div>
							<div class="pdfActionText">
								<a href="javascript:;" onclick="VTE.PDFMakerActions.onGetPDFImagesDiv(this,'{$MODULE}','{$ID}');" class="webMnu withoutripple">{$PDFMAKER_MOD.LBL_PRODUCT_IMAGE}</a>
							</div>
						</div>
					</td>
				</tr>
			{/if}
			{* crmv@195354 *}
			{if $ENABLE_RTF}
			<tr>
				<td class="rightMailMergeContent">
					<div class="pdfActionWrap">
						<div class="pdfActionImage">
							<a href="javascript:;" onclick="VTE.PDFMakerActions.onGenerateRTF(this,'{$MODULE}','{$ID}');" class="withoutripple">
								<img src="{'modules/PDFMaker/img/RtfGenerator.png'|resourcever}" hspace="5" align="absmiddle" border="0" />
							</a>
						</div>
						<div class="pdfActionText">
							<a href="javascript:;" onclick="VTE.PDFMakerActions.onGenerateRTF(this,'{$MODULE}','{$ID}');" class="webMnu withoutripple">{$PDFMAKER_MOD.LBL_EXPORT_TO_RTF}</a>
						</div>
					</div>
				</td>
			</tr>
			{/if}
			{* crmv@195354e *}
			{* crmv@59091e *} 
		{else}
			<tr>
				<td class="rightMailMergeContent">
					{$PDFMAKER_MOD.CRM_TEMPLATES_DONT_EXIST} 
					{if $IS_ADMIN eq '1'} 
						{$PDFMAKER_MOD.CRM_TEMPLATES_ADMIN}
						<a href="index.php?module=PDFMaker&action=EditPDFTemplate&return_module={$MODULE}&return_id={$ID}&parenttab=Tools" class="webMnu withoutripple">{$PDFMAKER_MOD.TEMPLATE_CREATE_HERE}</a>
					{/if}
				</td>
			</tr>
		{/if}
	
	</table>
	
	<div id="alert_doc_title" style="display: none;"><br />{$PDFMAKER_MOD.ALERT_DOC_TITLE}</div>
	
{/if}