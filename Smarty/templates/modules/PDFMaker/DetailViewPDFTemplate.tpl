{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script type="text/javascript" src="include/js/smoothscroll.js"></script>

{include file='Buttons_List.tpl'}

<script type="text/javascript">
function ExportTemplates()
{ldelim}
	window.location.href = "index.php?module=PDFMaker&action=PDFMakerAjax&file=ExportPDFTemplate&templates={$TEMPLATEID}";
{rdelim}
</script>

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<h4><strong>{$MOD.LBL_VIEWING} &quot;{$FILENAME}&quot;</strong></h4>
		
			<form method="post" action="index.php" name="etemplatedetailview" onsubmit="VteJS_DialogBox.block();">
				<input type="hidden" name="action" value="">
				<input type="hidden" name="module" value="PDFMaker">
				<input type="hidden" name="retur_module" value="PDFMaker">
				<input type="hidden" name="return_action" value="PDFMaker">
				<input type="hidden" name="templateid" value="{$TEMPLATEID}">
				<input type="hidden" name="parenttab" value="{$PARENTTAB}">
				<input type="hidden" name="isDuplicate" value="false">
				<input type="hidden" name="subjectChanged" value="">

				<div class="row">
					<div class="col-sm-12 col-header">
						<div class="row">
							<div class="col-sm-4">
							</div>
							<div class="col-sm-8 text-right">
								{if $EDIT eq 'permitted'}						
									<button class="crmbutton edit" type="submit" name="Button" onclick="this.form.action.value='EditPDFTemplate'; this.form.parenttab.value='Tools'">{$APP.LBL_EDIT_BUTTON_LABEL}</button>
									<button class="crmbutton create" type="submit" name="Duplicate" onclick="this.form.isDuplicate.value='true'; this.form.action.value='EditPDFTemplate';">{$APP.LBL_DUPLICATE_BUTTON}</button>
								{/if}  
								<button class="crmbutton cancel" type="submit" name="Button" onclick="this.form.action.value='ChangeActiveOrDefault'; this.form.subjectChanged.value='active'">{$ACTIVATE_BUTTON}</button>
								{if $IS_ACTIVE neq $APP.Inactive}
						 			<button class="crmbutton cancel" type="submit" name="Button" onclick="this.form.action.value='ChangeActiveOrDefault'; this.form.subjectChanged.value='default'">{$DEFAULT_BUTTON}</button>
								{/if}
								{if $DELETE eq 'permitted'}
									<button class="crmbutton delete" type="button" name="Delete" onclick="this.form.return_action.value='index'; var confirmMsg = '{$APP.NTC_DELETE_CONFIRMATION}'; submitFormForActionWithConfirmation('etemplatedetailview', 'DeletePDFTemplate', confirmMsg);">{$APP.LBL_DELETE_BUTTON_LABEL}</button>
								{/if}
							</div>
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-sm-12">
						<div class="vte-card">
							<table class="vtetable vtetable-props">
								<tbody>
									<tr>
										<td class="cellLabel">{$MOD.LBL_PDF_NAME}:</td>
										<td class="cellText"><strong>{$FILENAME}</strong></td>
									</tr>
									<tr>
										<td class="cellLabel">{$MOD.LBL_DESCRIPTION}:</td>
										<td class="cellText">{$DESCRIPTION}</td>
									</tr>
									{****************************************** pdf sorce module *********************************************}	
									<tr>
										<td class="cellLabel">{$MOD.LBL_MODULENAMES}:</td>
										<td class="cellText">{$MODULENAME}</td>
									</tr>
									{****************************************** pdf is active *********************************************}
									<tr>
										<td class="cellLabel">{$APP.LBL_STATUS}:</td>
										<td class="cellText">{$IS_ACTIVE}</td>
									</tr>
									{****************************************** pdf is default *********************************************}
									<tr>
										<td class="cellLabel">{$MOD.LBL_SETASDEFAULT}:</td>
										<td class="cellText">{$IS_DEFAULT}</td>
									</tr>
									{****************************************** pdf body *****************************************************}	
								</tbody>
							</table>
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-sm-12 col-header">
						<span class="col-title">{$MOD.LBL_PDF_TEMPLATE}</span>
					</div>
				</div>
				
				<div class="row">
					<div class="col-sm-12">
						<div class="vte-card">
							<div class="table-responsive">
								<table class="vtetable vtetable-props">
									<tbody>
										<tr>
											<td class="cellLabel">{$MOD.LBL_HEADER_TAB}</td>
											<td class="cellText pdf-template-content">{$HEADER}</td>
										</tr>
										<tr>
											<td class="cellLabel">{$MOD.LBL_BODY}</td>
											<td class="cellText pdf-template-content">{$BODY}</td>
										</tr>
										<tr>
											<td class="cellLabel">{$MOD.LBL_FOOTER_TAB}</td>
											<td class="cellText pdf-template-content">{$FOOTER}</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>