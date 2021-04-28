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
								{'LBL_IMPORT'|@getTranslatedString:$MODULE} - {'LBL_ERROR'|@getTranslatedString:$MODULE}
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
						{if $ERROR_DETAILS neq ''}
							<b>{'ERR_DETAILS_BELOW'|@getTranslatedString:$MODULE}</b>
							<table class="vtetable vtetable-props mb-3 mx-auto" style="width:50%">
								{foreach key=_TITLE item=_VALUE from=$ERROR_DETAILS}
									<tr>
										<td class="cellLabel text-nowrap">{$_TITLE}</td>
										<td class="cellText">{$_VALUE}</td>
									</tr>
								{/foreach}
							</table>
						{/if}
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12 text-center my-3">
						{if $CUSTOM_ACTIONS neq ''}
							{foreach key=_LABEL item=_ACTION from=$CUSTOM_ACTIONS}
								<button type="button" name="{$_LABEL}" onclick="{$_ACTION}" class="crmbutton create">{$_LABEL|@getTranslatedString:$MODULE}</button>
							{/foreach}
						{/if}
						<button type="button" name="goback" onclick="window.history.back()" class="crmbutton edit">{'LBL_GO_BACK'|@getTranslatedString:$MODULE}</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>