{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@163191 *}

<form class="form-horizontal" name="PDFDocForm" method="post" action="index.php" onSubmit="return VTE.PDFMakerActions.validatePDFDocForm();">
	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	<input type="hidden" name="module" value="PDFMaker" />
	<input type="hidden" name="action" value="SavePDFDoc" />
	<input type="hidden" name="pmodule" value="{$RETURN_MODULE}" />
	<input type="hidden" name="pid" value="{$RETURN_ID}" />
	<input type="hidden" name="template_ids" value="" />
	<input type="hidden" name="language" value="" />

	<div class="form-group">
		<label class="control-label col-sm-3" for="notes_title">
			<font color="red">*</font>{'Title'|getTranslatedString:'Documents'}
		</label>
		<div class="col-sm-9">
			<input type="text" class="form-control" id="notes_title" name="notes_title" value="{$DEFAULT_TITLE}">
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-sm-3" for="folderid">{'Folder Name'|getTranslatedString:'Documents'}</label>
		<div class="col-sm-9">
			<select id="folderid" name="folderid" class="form-control">
				{foreach from=$FOLDERS item=folder}
					<option value="{$folder.id}">{$folder.name}</option>
				{/foreach}
			</select>
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-sm-3" for="notecontent">{'Note'|getTranslatedString:'Documents'}</label>
		<div class="col-sm-9">
			<textarea id="notecontent" name="notecontent" class="form-control" style="min-height:100px;resize:vertical;"></textarea>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-12 text-center">
			<input type="submit" value="{'LBL_SAVE_BUTTON_LABEL'|getTranslatedString}" class="crmbutton small save" />
			&nbsp;&nbsp;
			<input type="button" name="{'LBL_CANCEL_BUTTON_LABEL'|getTranslatedString}" value="{'LBL_CANCEL_BUTTON_LABEL'|getTranslatedString}" class="crmbutton small cancel" data-dismiss="modal" />
			&nbsp;&nbsp;
			<input type="button" name="{'LBL_PREVIEW'|getTranslatedString}" value="{'LBL_PREVIEW'|getTranslatedString}" class="crmbutton small create" onclick="VTE.PDFMakerActions.onGeneratePDF(this, '{$RETURN_MODULE}', '{$RETURN_ID}');" />
		</div>
	</div>
</form>