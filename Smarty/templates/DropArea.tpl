{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@167019 *}

<script type="text/javascript" src="{"include/js/jszip/jszip.min.js"|resourcever}"></script>

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
						<button class="btn btn-primary btn-block btn-lg" onclick="VTE.DropArea.Documents.chooseUploadMode('single_zip_file');">{'LBL_SINGLE_ZIP_FILE'|getTranslatedString}</button>
						<button class="btn btn-primary btn-block btn-lg" onclick="VTE.DropArea.Documents.chooseUploadMode('one_by_one');">{'LBL_DOCUMENT_FOREACH_FILE'|getTranslatedString}</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="saveDocumentModal"></div>

{literal}
<script type="text/javascript">
	if (window.VTE && window.VTE.DropArea) {
		VTE.DropArea.init('droparea');
		VTE.DropArea.Documents.init();
	}
</script>
{/literal}