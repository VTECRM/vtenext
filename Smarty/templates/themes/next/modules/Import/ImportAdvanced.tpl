{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script type="text/javascript" src="modules/Import/resources/Import.js"></script>

{include file='Buttons_List1.tpl'} {* crmv@187110 *}

<div class="container mainContainer pt-5">
	<div class="row">
		<div class="col-sm-10 col-sm-offset-1">
			<div class="vte-card">
				<form onsubmit="VteJS_DialogBox.block();" action="index.php" enctype="multipart/form-data" method="POST" name="importAdvanced">
					<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
					<input type="hidden" name="module" value="{$FOR_MODULE}" />
					<input type="hidden" name="action" value="Import" />
					<input type="hidden" name="mode" value="import" />
					<input type="hidden" name="type" value="{$USER_INPUT->getString('type')}" />
					<input type="hidden" name="has_header" value='{$HAS_HEADER}' />
					<input type="hidden" name="file_encoding" value='{$USER_INPUT->getString('file_encoding')}' />
					<input type="hidden" name="delimiter" value='{$USER_INPUT->getString('delimiter')}' />
					<input type="hidden" name="merge_type" value='{$USER_INPUT->getString('merge_type')}' />
					<input type="hidden" name="merge_fields" value='{$USER_INPUT->getString('merge_fields')}' />
					<input type="hidden" id="mandatory_fields" name="mandatory_fields" value='{$ENCODED_MANDATORY_FIELDS}' />

					<div class="row">
						<div class="col-sm-12">
							<div class="dvInnerHeader mb-5">
								<div class="dvInnerHeaderTitle">
									{'LBL_IMPORT'|@getTranslatedString:$MODULE} {$FOR_MODULE|@getTranslatedString:$FOR_MODULE}
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
						<div class="col-sm-12">
							{include file='modules/Import/Import_Step4.tpl'}
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12 text-right my-3">
							{include file='modules/Import/Import_Advanced_Buttons.tpl'}
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>