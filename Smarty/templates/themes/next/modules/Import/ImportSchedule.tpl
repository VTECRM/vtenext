{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script type="text/javascript" src="modules/Import/resources/Import.js"></script>

{include file='Buttons_List1.tpl'}

<div class="container mainContainer pt-5">
	<div class="row">
		<div class="col-sm-10 col-sm-offset-1">
			<div class="vte-card">
				<div class="row">
					<div class="col-sm-12">
						<div class="dvInnerHeader mb-5">
							<div class="dvInnerHeaderTitle">
								{'LBL_IMPORT_SCHEDULED'|@getTranslatedString:$MODULE} 
							</div>
						</div>
					</div>
				</div>
				{if $ERROR_MESSAGE neq ''}
					<div class="row">
						<div class="col-sm-12 style1">
							{$ERROR_MESSAGE}
						</div>
					</div>
				{/if}
				<div class="row">
					<div class="col-sm-12 mb-5">
						<b>{'LBL_SCHEDULED_IMPORT_DETAILS'|@getTranslatedString:$MODULE}</b>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12 text-center my-3">
						<button type="button" name="cancel" class="crmbutton delete" onclick="location.href='index.php?module={$FOR_MODULE}&action=Import&mode=cancel_import&import_id={$IMPORT_ID}'">
							{'LBL_CANCEL_IMPORT'|@getTranslatedString:$MODULE}
						</button>
						{include file='modules/Import/Import_Done_Buttons.tpl'}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>