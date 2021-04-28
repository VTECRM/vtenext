{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{include file='Buttons_List1.tpl'}

<div class="container mainContainer pt-5">
	<div class="row">
		<div class="col-sm-10 col-sm-offset-1">
			<div class="vte-card">
				<form name="Export_Records" method="POST" onsubmit="VteJS_DialogBox.block();">
					<input type="hidden" name="module" value="{$MODULE}">
					<input type="hidden" name="action" value="Export">
					<input type="hidden" name="idstring" value="{$IDSTRING}">
					<input type="hidden" name="id_cur_str" value="{$IDCURSTR}">

					<div class="row">
						<div class="col-sm-12">
							<div class="dvInnerHeader mb-5">
								<div class="dvInnerHeaderTitle">
									{$APP.$MODULE} >> {$APP.LBL_EXPORT}
								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-sm-12 pl-5 pb-3">
							<span class="genHeaderSmall">{$APP.LBL_SEARCH_CRITERIA_RECORDS}:</span>
						</div>
					</div>
					<table class="vtetable vtetable-props mb-3 mx-auto" style="width:50%">
						<tr>
							{if $SESSION_WHERE neq ''}
								<td class="cellLabel text-nowrap"><label for="search_type_includesearch">{$APP.LBL_WITH_SEARCH}</label></td>
								<td class="cellText">
									<input type="radio" name="search_type" checked value="includesearch" id="search_type_includesearch">
								</td>
							{else}
								<td class="cellLabel text-nowrap"><label for="search_type_includesearch">{$APP.LBL_WITH_SEARCH}</label></td>
								<td class="cellText">
									<input type="radio" name="search_type" value="includesearch" id="search_type_includesearch">
								</td>
							{/if}
						</tr>
						<tr>
							{if $SESSION_WHERE eq ''}
								<td class="cellLabel text-nowrap"><label for="search_type_withoutsearch">{$APP.LBL_WITHOUT_SEARCH}</label></td>
								<td class="cellText">
									<input type="radio" name="search_type" checked value="withoutsearch" id="search_type_withoutsearch">
								</td>
							{else}
								<td class="cellLabel text-nowrap"><label for="search_type_withoutsearch">{$APP.LBL_WITHOUT_SEARCH}</label></td>
								<td class="cellText">
									<input type="radio" name="search_type" value="withoutsearch" id="search_type_withoutsearch">
								</td>
							{/if}
						</tr>
					</table>

					<div class="row">
						<div class="col-sm-12 pl-5 pb-3">
							<span class="genHeaderSmall">{$APP.LBL_EXPORT_RECORDS}:</span>
						</div>
					</div>
					<table class="vtetable vtetable-props mb-3 mx-auto" style="width:50%">
						<tr>
							{if $IDSTRING eq ''}
								<td class="cellLabel text-nowrap"><label for="export_data_all">{$APP.LBL_ALL_DATA}</label></td>
								<td class="cellText">
									<input type="radio" name="export_data" checked value="all" id="export_data_all">
								</td>
							{else}
								<td class="cellLabel text-nowrap"><label for="export_data_all">{$APP.LBL_ALL_DATA}</label></td>
								<td class="cellText">
									<input type="radio" name="export_data" value="all" id="export_data_all">
								</td>
							{/if}
						</tr>
						<tr>
							<td class="cellLabel text-nowrap"><label for="export_currentpage">{$APP.LBL_DATA_IN_CURRENT_PAGE}</label></td>
							<td class="cellText">
								<input type="radio" name="export_data" value="currentpage" id="export_currentpage">
							</td>
						</tr>
						<tr>
							{if $IDSTRING neq ''}
								<td class="cellLabel text-nowrap"><label for="export_selecteddata">{$APP.LBL_ONLY_SELECTED_RECORDS}</label></td>
								<td class="cellText">
									<input type="radio" name="export_data" checked value="selecteddata" id="export_selecteddata">
								</td>
							{else}
								<td class="cellLabel text-nowrap"><label for="export_selecteddata">{$APP.LBL_ONLY_SELECTED_RECORDS}</label></td>
								<td class="cellText">
									<input type="radio" name="export_data"  value="selecteddata" id="export_selecteddata">
								</td>
							{/if}
						</tr>
					</table>

					<div class="row">
						<div class="col-sm-12 text-right my-3">
							<button type="button" name="{$APP.LBL_EXPORT}" class="crmbutton create" onclick="record_export('{$MODULE}','{$CATEGORY}',this.form,'{$smarty.request.idstring}')">{$APP.LBL_EXPORT} {$APP.$MODULE}</button>
							<button type="button" name="{$APP.LBL_CANCEL_BUTTON_LABEL}" class="crmbutton cancel" onclick="window.history.back()">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
						</div>
					</div>

					<div id="not_search" style="position:absolute;display:none;width:400px;height:25px;"></div>
				</form>
			</div>
		</div>
	</div>
</div>