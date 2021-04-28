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
				<input type="hidden" name="module" value="{$FOR_MODULE}" />
				<div class="row">
					<div class="col-sm-12">
						<div class="dvInnerHeader mb-5">
							<div class="dvInnerHeaderTitle">
								{'LBL_IMPORT'|@getTranslatedString:$MODULE} {$FOR_MODULE|@getTranslatedString:$FOR_MODULE} - {'LBL_UNDO_RESULT'|@getTranslatedString:$MODULE}
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
						<table class="vtetable vtetable-props mb-3 mx-auto" style="width:50%">
							<tr>
								<td class="cellLabel text-nowrap">{'LBL_TOTAL_RECORDS'|@getTranslatedString:$MODULE}</td>
								<td class="cellText">{$TOTAL_RECORDS}</td>
							</tr>
							<tr>
								<td class="cellLabel text-nowrap">{'LBL_NUMBER_OF_RECORDS_DELETED'|@getTranslatedString:$MODULE}</td>
								<td class="cellText">{$DELETED_RECORDS_COUNT}</td>
							</tr>
						</table>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12 text-center my-3">
						{include file='modules/Import/Import_Done_Buttons.tpl'}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>