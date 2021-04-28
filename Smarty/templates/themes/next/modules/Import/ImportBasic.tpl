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
				<form onsubmit="VteJS_DialogBox.block();" action="index.php" enctype="multipart/form-data" method="POST" name="importBasic">
					<input type="hidden" name="module" value="{$FOR_MODULE}" />
					<input type="hidden" name="action" value="Import" />
					<input type="hidden" name="mode" value="upload_and_parse" />
					
					<div class="row">
						<div class="col-sm-12">
							<div class="dvInnerHeader mb-5">
								<div class="dvInnerHeaderTitle">{'LBL_IMPORT'|@getTranslatedString:$MODULE} {$FOR_MODULE|@getTranslatedString:$FOR_MODULE}</div>
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
							{include file='modules/Import/Import_Step1.tpl'}
						</div>
						<div class="col-sm-12 mb-5">
							{include file='modules/Import/Import_Step2.tpl'}
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12 mb-5">
							{include file='modules/Import/Import_Step3.tpl'}
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12 text-right">
							{include file='modules/Import/Import_Basic_Buttons.tpl'}
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>