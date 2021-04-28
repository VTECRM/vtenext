{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@173271 *}

{if !empty($UPLOADSTATUS)}
	<div class="row">
		<div class="col-sm-12">
			<div class="alert alert-danger alert-dismissable uploader">
				<button type="button" class="close" data-dismiss="alert">&times;</button>
				{'LBL_FILE_UPLOADERROR'|getTranslatedString} {$UPLOADSTATUS}
			</div>
		</div>
	</div>
{/if}

<form name="fileattachment" method="post" enctype="multipart/form-data" action="index.php" onsubmit="return VTEPortal.DropArea.HelpDesk.onFileAttachSubmit(this);">
	<input type="hidden" name="module" value="HelpDesk">
	<input type="hidden" name="action" value="index">
	<input type="hidden" name="fun" value="uploadfile">
	<input type="hidden" name="ticketid" value="{$TICKETID}">

	<div class="panel panel-default">
		<div class="panel-heading">
			<h4 style="line-height:35px;">
				<i class="material-icons">cloud_upload</i>
				{'LBL_ATTACH'|getTranslatedString}
			</h4>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-sm-12">
					<div class="alert alert-info">{'LBL_DROP_INFO'|getTranslatedString}</div>
					<input type="file" name="customerfile[]" class="detailedViewTextBox form-control" multiple />
					<button class="btn btn-primary" name="Attach" type="submit">{'LBL_ATTACH'|getTranslatedString}</button>
				</div>
			</div>
		</div>
	</div>
</form>

{include file="DropArea.tpl"}