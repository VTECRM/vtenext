{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@173153 *}

<link rel="stylesheet" type="text/css" href="css/DropArea.css">
<script type="text/javascript" src="js/DropArea.js"></script>
<script type="text/javascript" src="js/jszip/jszip.min.js"></script>

<div id="droparea" class="droparea" style="opacity:0;visibility:hidden;">
	<div class="droparea-text">{'LBL_DROP_FILES_HERE'|getTranslatedString}</div>
</div>

<div id="uploadModeModal" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">{'LBL_CHOOSE_UPLOAD_MODE'|getTranslatedString}</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-sm-8 col-sm-offset-2">
						<button class="btn btn-primary btn-block btn-lg" onclick="VTEPortal.DropArea.HelpDesk.chooseUploadMode('single_zip_file');">{'LBL_SINGLE_ZIP_FILE'|getTranslatedString}</button>
						<button class="btn btn-primary btn-block btn-lg" onclick="VTEPortal.DropArea.HelpDesk.chooseUploadMode('separate_files');">{'LBL_SEPARATE_FILES'|getTranslatedString}</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="zipFormModal" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">{'LBL_ZIP_FORM_TITLE'|getTranslatedString}</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-sm-12">
						<form id="zipForm">
							<div class="alert alert-info">
								{'LBL_ZIP_FORM_INFO'|getTranslatedString}
							</div>
							<div class="form-group">
								<label class="control-label" for="doc_title">{'LBL_FILE_ZIP_NAME'|getTranslatedString}</label>
								<input type="text" class="form-control" id="doc_title" name="doc_title">
							</div>
						</form>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" onclick="VTEPortal.DropArea.HelpDesk.attachFiles();">{'LBL_SAVE'|getTranslatedString:'HelpDesk'}</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">{'LBL_DISMISS'|getTranslatedString:'HelpDesk'}</button>
			</div>
		</div>
	</div>
</div>

<div class="uploading-loader">
	<div class="lds-ring"><div></div><div></div><div></div><div></div></div>
</div>

{literal}
<script type="text/javascript">
	if (VTEPortal && VTEPortal.DropArea) {
		VTEPortal.DropArea.init('droparea');
		VTEPortal.DropArea.HelpDesk.init();
	}
</script>
{/literal}