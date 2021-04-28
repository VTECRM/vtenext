{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script type="text/javascript" src="{"include/js/general.js"|resourcever}"></script>

<!-- header - level 2 tabs -->
{include file='Buttons_List1.tpl'}

<table align="center" border="0" cellpadding="0" cellspacing="0" width="98%">
	<tr>
   		<td valign="top"></td>
   		<td class="showPanelBg" valign="top" width="100%">
   			<table  cellpadding="0" cellspacing="0" width="100%" border=0>
    				<tr>
 					<td width="50%" valign=top>
						<form  name="Export_Records"  method="POST" onsubmit="VteJS_DialogBox.block();">
							<input type="hidden" name="module" value="{$MODULE}">
							<input type="hidden" name="action" value="Export">
							<input type="hidden" name="idstring" value="{$IDSTRING}">
							<input type="hidden" name="id_cur_str" value="{$IDCURSTR}">

							<table align="center" cellpadding="15" cellspacing="0" width="85%" class="mailClient importLeadUI small" border="0">
								<tr>
									<td colspan="2" valign="middle" align="left" class="mailClientBg  genHeaderSmall">{$APP.$MODULE} >> {$APP.LBL_EXPORT} </td>
								</tr>
								<tr>
  									<td border="0" cellpadding="5" cellspacing="0" width="50%">
	 									<table>
			   								<tr>
			       									<td colspan="2" align="left" valign="top" style="padding-left:40px;">
		    	       										<span class="genHeaderSmall">{$APP.LBL_SEARCH_CRITERIA_RECORDS}:</span>
												</td>
			   								</tr>
		  	   								<tr>
												{if $SESSION_WHERE neq ''}
													<td align="right" valign="top" width="50%" class=small><label for="search_type_includesearch">{$APP.LBL_WITH_SEARCH}</label></td>
													<td align="left" valign="top" width="5%" class=small>
														<input type="radio" name="search_type" checked value="includesearch" id="search_type_includesearch">
													</td>
												{else}
													<td align="right" valign="top" width="50%" class=small><label for="search_type_includesearch">{$APP.LBL_WITH_SEARCH}</label></td>
													<td align="left" valign="top" width="5%" class=small>
														<input type="radio" name="search_type" value="includesearch" id="search_type_includesearch">
													</td>
												{/if}
			   								</tr>
											<tr>
												{if $SESSION_WHERE eq ''}
													<td align="right" valign="top" width="50%" class=small><label for="search_type_withoutsearch">{$APP.LBL_WITHOUT_SEARCH}</label></td>
													<td align="left" valign="top" width="5%" class=small>
		                 								<input type="radio" name="search_type" checked value="withoutsearch" id="search_type_withoutsearch">
													</td>
												{else}
													<td align="right" valign="top" width="50%" class=small><label for="search_type_withoutsearch">{$APP.LBL_WITHOUT_SEARCH}</label></td>
													<td align="left" valign="top" width="5%" class=small>
		                 								<input type="radio" name="search_type" value="withoutsearch" id="search_type_withoutsearch">
													</td>
												{/if}
			   								</tr>
			   								<tr>
												<td colspan="2" align="left" valign="top" style="padding-left:40px;">
													<span class="genHeaderSmall">{$APP.LBL_EXPORT_RECORDS}:</span>
												</td>
			   								</tr>
			   								<tr>
												{if $IDSTRING eq ''}
													<td align="right" valign="top" width="50%" class=small><label for="export_data_all">{$APP.LBL_ALL_DATA}</label></td>
													<td align="left" valign="top" width="5%" class=small>
														<input type="radio" name="export_data" checked value="all" id="export_data_all">
													</td>
												{else}
													<td align="right" valign="top" width="50%" class=small><label for="export_data_all">{$APP.LBL_ALL_DATA}</label></td>
													<td align="left" valign="top" width="5%" class=small>
														<input type="radio" name="export_data" value="all" id="export_data_all">
													</td>
												{/if}
			   								</tr>
			   								<tr>
		        								<td align="right" valign="top" width="50%" class=small><label for="export_currentpage">{$APP.LBL_DATA_IN_CURRENT_PAGE}</label></td>
												<td align="left" valign="top" width="5%" class=small>
													<input type="radio" name="export_data" value="currentpage" id="export_currentpage">
												</td>
			   								</tr>
			   								<tr>
												{if $IDSTRING neq ''}
		   	       									<td align="right" valign="top" width="50%" class=small><label for="export_selecteddata">{$APP.LBL_ONLY_SELECTED_RECORDS}</label></td>
			   										<td align="left" valign="top" width="5%" class=small>
			   											<input type="radio" name="export_data" checked value="selecteddata" id="export_selecteddata">
													</td>
												{else}
													<td align="right" valign="top" width="50%" class=small><label for="export_selecteddata">{$APP.LBL_ONLY_SELECTED_RECORDS}</label></td>
			   										<td align="left" valign="top" width="5%" class=small>
			   											<input type="radio" name="export_data"  value="selecteddata" id="export_selecteddata">
													</td>
												{/if}
		   									</tr>
										</table>
									</td>
									<td border="0" cellpadding="5" cellspacing="0" width="50%">
										<table >
											<tr>		
												<td><div id="not_search" style="position:absolute;display:none;width:400px;height:25px;"></div></td>
											</tr>
										</table>
									</td>	
								</tr>
								<tr>
									<td align="center" colspan="2" border=0 cellspacing=0 cellpadding=5 width=98% class="layerPopupTransport">	
										<!-- crmv@14086 i - tolto la trad del modulo-->
										<input type="button" name="{$APP.LBL_EXPORT}" value="{$APP.LBL_EXPORT} {$APP.$MODULE} " class="crmbutton small create" onclick="record_export('{$MODULE}','{$CATEGORY}',this.form,'{$smarty.request.idstring}')"/>&nbsp;&nbsp;
										<!-- crmv@14086 i -->
                								<input type="button" name="{$APP.LBL_CANCEL_BUTTON_LABEL}" value=" {$APP.LBL_CANCEL_BUTTON_LABEL} " class="crmbutton small cancel" onclick="window.history.back()" />
									</td>
								</tr>
							</table>
						</form>
					</td>
				</tr>
			</table>
		</td>
		<td valign="top"></td>
	</tr>
</table>