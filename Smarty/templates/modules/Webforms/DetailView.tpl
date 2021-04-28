{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script type="text/javascript" src="{"include/js/dtlviewajax.js"|resourcever}"></script>

<span id="crmspanid" style="display:none;position:absolute;" onmouseover="show('crmspanid');">
	<a class="edit" href="javascript:;">{$APP.LBL_EDIT_BUTTON}</a>
</span>

{include file='SetMenu.tpl'}

<script type="text/javascript">
var gVTModule = '{$smarty.request.module|@vtlib_purify}';
</script>

<!-- Contents -->

<table border=0 cellspacing=0 cellpadding=0 width=100% align=center>
	<tr>
		<td valign=top></td>
		<td class="showPanelBg" valign=top width=100%>
			<!-- PUBLIC CONTENTS STARTS-->
			{include file='Buttons_List1.tpl'}
			{include file='Buttons_List_Detail.tpl'}
			
			<form name="action_form" action="" method="post">
			    <input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
				<input type="hidden" name="id" value="{$WEBFORMMODEL->getId()}" />
			</form>

			<div id="orgLay1" class="crmvDiv" style="display:none; position:absolute; top:25%; left:30%; height:410px; width:50%; z-index:100;">
				<table id="orgLay1_Handle" cellspacing="0" cellpadding="5" border="0" width="100%"> {* crmv@187142 *}
					<tr>
						<td class="level3Bg" align="left"><b>
							<img src="modules/Webforms/img/Webform_small.png">
							<p id="webform_popup_header" style="display:inline;">{$WEBFORMMODEL->getName()}</p></b>
						</td>						
					</tr>
				</table>
				<table cellspacing="0" cellpadding="0" border="0" align="center" width="95%">
						<tr>
							<td class="small">
								<table cellpadding="5" border="0" bgcolor="white" align="center" width="100%" celspacing="0">
									<tr>
										<td>
											<font color="green">{'LBL_EMBED_MSG'|@getTranslatedString:$MODULE }</font>
										</td>
									</tr>
									<tr>
										<td rowspan="5">
											<textarea readonly="readonly" style="width:100%;height:320px;" rows="25" cols="25" id="webform_source" name="webform_source" value=""></textarea>
										</td>
									</tr>
								</table>
							</td>
						</tr>						
				</table>
				<div class="closebutton" onClick="hideFloatingDiv('orgLay1');"></div>
			</div>
			
			<!-- Account details tabs -->
			<table border=0 cellspacing=0 cellpadding=0 width=100% align=center>
				<tr>
					<td>
						<table border=0 cellspacing=0 cellpadding=3 width=100% class="small">
							<tr>
								<td class="dvtTabCache" style="width:10px" nowrap>&nbsp;</td>
								<td class="dvtSelectedCell" align=center nowrap>{$APP.LBL_INFORMATION}</td>
								<td class="dvtTabCache" style="width:10px">&nbsp;</td>
								<td class="dvtTabCache" align="right" style="width:100%"></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td valign=top align=left>
						<table border=0 cellspacing=0 cellpadding=3 width=100% class="dvtContentSpace">
							<tr>
								<td align=left valign="top"> {* crmv@20260 *}
									<!-- content cache -->
									<table border=0 cellspacing=0 cellpadding=0 width=100%>
										<tr>
											<td>
												<!-- Command Buttons -->
												<!-- NOTE: We should avoid form-inside-form condition, which could happen when Singlepane view is enabled. -->
												<form action="index.php" method="post" name="DetailView" id="form">
													{include file='DetailViewHidden.tpl'}
											
													{assign var=BLOCKINITIALSTATUS value=VteSession::get('BLOCKINITIALSTATUS')} {* crmv@181170 *}

													<!-- Detailed View Code starts here-->
													<table border=0 cellspacing=0 cellpadding=0 width=100% class="small">
														<tr>
															<td id="autocom"></td>
														</tr>
														<tr>
															<td>
																<!-- General details -->
																<table class="small" border="0" cellpadding="0" cellspacing="0" width="100%">
																	<!--Block Head-->
																	<tr>
																		<td colspan={if $WEBFORMMODEL->hasId()}"3"{else}"4"{/if} class="detailedViewHeader" style="border-right: none;">
																			<b>{'LBL_MODULE_INFORMATION'|@getTranslatedString:$MODULE}</b>
																		</td>
																		<td colspan="1" class="detailedViewHeader" align="right" style="border-left: none;" nowrap>
																			{'LBL_ENABLED'|@getTranslatedString:$MODULE}
																			{if $WEBFORMMODEL->getEnabled() eq 1}
																				<img src="{'prvPrfSelectedTick.gif'|resourcever}">
																			{else}
																				<img src="{'no.gif'|resourcever}">
																			{/if}
																		</td>
																	</tr>
																	<!-- Cell information -->
																	<tr style="height:25px">
																		<td class="dvtCellLabel" align="right" width="10%">
																			<font color="red">*</font>{'LBL_WEBFORM_NAME'|@getTranslatedString:$MODULE}
																		</td>
																		<td class="dvtCellInfo" align="left" width="40%">
																			{$WEBFORMMODEL->getName()}
																		</td>
																		<td class="dvtCellLabel" align="right" width="10%">
																			<font color="red">*</font>{'LBL_MODULE'|@getTranslatedString:$MODULE}
																		</td>
																		<td class="dvtCellInfo" align="left" width="40%">
																			{$WEBFORMMODEL->getTargetModule()}
																		</td>
																	</tr>
																	<tr style="height:10px"><td colspan="4"></td></tr>
																	<tr style="height:25px">
																		<td class="dvtCellLabel" align="right">
																			<font color="red">*</font>{'LBL_ASSIGNED_TO'|@getTranslatedString:$MODULE}
																		</td>
																		<td class="dvtCellInfo" align="left">
																			{$OWNER}
																		</td>
																		<td class="dvtCellLabel" align="right">
																			{'LBL_RETURNURL'|@getTranslatedString:$MODULE}
																		</td>
																		<td class="dvtCellInfo" align="left">
																			{$WEBFORMMODEL->getReturnUrl()} {* crmv@177927 *}
																		</td>
																	</tr>
																	<tr style="height:10px"><td colspan="4"></td></tr>
																	<tr style="height:25px;">
																		<td class="dvtCellLabel" align="right">
																			{'LBL_PUBLICID'|@getTranslatedString:$MODULE}
																		</td>
																		<td class="dvtCellInfo" align="left">
																			{$WEBFORMMODEL->getPublicId()}
																		</td>
																		<td class="dvtCellLabel" align="right">
																			{'LBL_POSTURL'|@getTranslatedString:$MODULE}
																		</td>
																		<td class="dvtCellInfo" align="left">
																			{$ACTIONPATH}
																		</td>
																	</tr>
																	<tr style="height:10px"><td colspan="4"></td></tr>
																	<tr>
																		<td class="dvtCellLabel" align="right" style="height:25px;">
																			{'LBL_DESCRIPTION'|@getTranslatedString:$MODULE}
																		</td>
																		<td class="dvtCellInfo" align="left">
																			{$WEBFORMMODEL->getDescription()}
																		</td>
																	</tr>
																	<!--Cell Information end-->
																	<tr style="height:25px"><td colspan="4"></td></tr>
																	<!--Block Head-->
																	<!-- Cell information for fields -->
																	<tr>
																		<td class="detailedViewHeader" colspan="4">
																			<b>{'LBL_FIELD_INFORMATION'|@getTranslatedString:$MODULE}</b>
																		</td>
																	</tr>
																	<tr>
																		<td colspan="4">
																			<div id="Webforms_FieldsView"></div>
																			<!--Fields View-->
																			<table id="field_table" class="vtetable">
																				{* crmv@32257 *}
																				<thead>
																					<tr>
																						<th>{'LBL_FIELDLABEL'|@getTranslatedString:$MODULE}</th>
																						<th>{'LBL_DEFAULT_VALUE'|@getTranslatedString:$MODULE}</th>
																						<th style="width:2%;">{'LBL_HIDDEN'|@getTranslatedString:$MODULE}</th>
																						<th style="width:2%;">{'LBL_REQUIRED'|@getTranslatedString:$MODULE}</th>
																						<th style="width:20%;">{'LBL_NEUTRALIZEDFIELD'|@getTranslatedString:$MODULE}</th>
																					</tr>
																				</thead>
																				{* crmv@32257e *}
																				<tbody>
																					{foreach item=field from=$WEBFORMMODEL->getFields() name=fieldloop}
																						{assign var=fieldinfo value=$WEBFORM->getFieldInfo($WEBFORMMODEL->getTargetModule(), $field->getFieldName())}
																						{if $WEBFORMMODEL->isActive($fieldinfo.name,$WEBFORMMODEL->getTargetModule())}
																							<tr style="height:25px" id="field_row">
																								<td class="dvtCellLabel" align="left" colspan="1">
																									{if $fieldinfo.mandatory eq 1}
																										<font color="red">*</font>
																									{/if}
																									{$fieldinfo.label}
																								</td>
																								<td class="dvtCellInfo">
																									{assign var="defaultvalueArray" value=$WEBFORMMODEL->retrieveDefaultValue($WEBFORMMODEL->getId(),$fieldinfo.name)}
																									{if $fieldinfo.type.name eq 'boolean'}
																										{if $defaultvalueArray[0] eq 'off'}
																											no
																										{elseif $defaultvalueArray[0] eq 'on'}
																											yes
																										{/if}
																									{else}
																										{','|implode:$defaultvalueArray}
																									{/if}
																								</td>
																								{* crmv@32257 *}
																								<td align="center" colspan="1">
																									{if $WEBFORMMODEL->isHidden($WEBFORMMODEL->getId(),$fieldinfo.name) eq true}
																										<img src="{'prvPrfSelectedTick.gif'|resourcever}">
																									{else}
																										<img src="{'no.gif'|resourcever}">
																									{/if}
																								</td>
																								{* crmv@32257e *}
																								<td align="center" colspan="1">
																									{if $WEBFORMMODEL->isRequired($WEBFORMMODEL->getId(),$fieldinfo.name) eq true}
																										<img src="{'prvPrfSelectedTick.gif'|resourcever}">
																									{else}
																										<img src="{'no.gif'|resourcever}">
																									{/if}
																								</td>
																								<td class="dvtCellLabel" align="left" colspan="1">
																									{$fieldinfo.name} {* crmv@179954 *}
																								</td>
																							</tr>
																						{/if}
																					{/foreach}
																				</tbody>
																			</table>
																			<!--Fields view ends here-->
																		</td>
																	</tr>
																	<!--Cell Information end-->
																	<tr style="height:25px">
																		<td colspan="4"></td>
																	</tr>
																</table>
															</td>
														</tr>
													</table>
												</form>
												<!-- End the form related to detail view -->			
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
		<!-- PUBLIC CONTENTS STOPS-->
	</tr>
</table>

{* SetMenu.tpl *}
</td>
</tr>
</table>
</td>
</tr>
</table>
	
<!-- added for validation -->
<script type="text/javascript">
  var fieldname = new Array({$VALIDATION_DATA_FIELDNAME});
  var fieldlabel = new Array({$VALIDATION_DATA_FIELDLABEL});
  var fielddatatype = new Array({$VALIDATION_DATA_FIELDDATATYPE});
  var fielduitype = new Array({$VALIDATION_DATA_FIELDUITYPE}); // crmv@83877
  var fieldwstype = new Array({$VALIDATION_DATA_FIELDWSTYPE}); //crmv@112297
</script>