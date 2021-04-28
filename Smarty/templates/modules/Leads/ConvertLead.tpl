{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@29463 crmv@41880 *}

{assign var=row value=$UIINFO->getLeadInfo()}

<form name="ConvertLead" method="POST" action="index.php" onsubmit="VteJS_DialogBox.block();">
	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	<input type="hidden" name="module" value="Leads">
	<input type="hidden" name="transferToName" value="{$row.company}">
	<input type="hidden" name="record" value="{$UIINFO->getLeadId()}">
	<input type="hidden" name="action">
	<input type="hidden" name="parenttab" value="{$CATEGORY}">
	<input type="hidden" name="current_user_id" value="{$UIINFO->getUserId()}">

	<div id="orgLay2" style="display: block; border: 1; width: 500px;" class="layerPopup crmvDiv">

		<table border="0" cellpadding="5" cellspacing="0" width="100%">
			<tr height="34">
				<td style="padding:5px" class="level3Bg">
					<table cellpadding="0" cellspacing="0" width="100%">
					<tr>
						<td width="80%" style="cursor:move;" id="ConvertLead_Handle"><b>{'ConvertLead'|@getTranslatedString:$MODULE} : {$row.firstname} {$row.lastname}</b></td>
						<td width="20%" align="right">
							<input name="Save" value="{'LBL_SAVE_BUTTON_LABEL'|@getTranslatedString:$MODULE}" onclick="javascript:this.form.action.value='LeadConvertToEntities'; return verifyConvertLeadData(ConvertLead)" type="submit" class="crmbutton save small">
						</td>
					</tr>
					</table>
				</td>
			</tr>
		</table>
		<table border=0 cellspacing=0 cellpadding=5 width=100% align=center>
			{if $UIINFO->isModuleActive('Accounts') && $row.company neq '' }
			<tr>
				<td class="small" >
					<table border="0" cellspacing="0" cellpadding="0" width="100%" align="center" bgcolor="white">
						<tr>
							<td colspan="4" class="detailedViewHeader">
								<input type="checkbox" onclick="javascript:showHideStatus('account_block',null,null);" id="select_account" name="entities[]" value="Accounts" {if $row.company neq ''} checked {/if} />
								<label for="select_account"><b>{'SINGLE_Accounts'|@getTranslatedString:$MODULE}</b></label>
							</td>
						</tr>
						<tr>
							<td>
								<div id="account_block" {if $row.company neq ''} style="display:block;" {else} style="display:none;" {/if}>
									<table border="0" cellspacing="0" cellpadding="5" width="100%" align="center" bgcolor="white">
										<tr>
											<td width="50%">
												{assign var=mandatory value=$UIINFO->isMandatory('Accounts','accountname')}
												{include file="FieldHeader.tpl" uitype=$UIINFO->getUitype('Accounts','accountname') mandatory=$mandatory label='LBL_ACCOUNT_NAME'|@getTranslatedString:$MODULE}
												<div class="{if $mandatory}dvtCellInfoOff{else}dvtCellInfo{/if}">
													<input type="text" name="accountname" class="detailedViewTextBox" value="{$UIINFO->getMappedFieldValue('Accounts','accountname',0)}" readonly="readonly" module="Accounts" {if $mandatory}record="true"{/if}>
												</div>
											</td>
											<td width="50%">
												{if $UIINFO->isActive('industry','Accounts')}
													{assign var=mandatory value=$UIINFO->isMandatory('Accounts','industry')}
													{include file="FieldHeader.tpl" uitype=$UIINFO->getUitype('Accounts','industry') mandatory=$mandatory label='industry'|@getTranslatedString:$MODULE}
													<div class="{if $mandatory}dvtCellInfoOff{else}dvtCellInfo{/if}">
														{* crmv@77469 *}
														<select name="industry" class="detailedViewTextBox" module="Accounts" {if $mandatory}record="true"{/if}>
															{$UIINFO->getPickListOptions('industry','Accounts',$MODULE)}
														</select>
                                                        {* crmv@77469e *}
													</div>
												{/if}
											</td>
										</tr>
									</table>
								</div>
							<td>
						</tr>
					</table>
				</td>
			</tr>
			{/if}
			{if $UIINFO->isModuleActive('Potentials')}
			<tr>
				<td class="small">
					<table border="0" cellspacing="0" cellpadding="0" width="100%" align="center" bgcolor="white">
						<tr>
							<td colspan="4" class="detailedViewHeader">
								<input type="checkbox" onclick="javascript:showHideStatus('potential_block',null,null);"id="select_potential" name="entities[]" value="Potentials" />
								<label for="select_potential"><b>{'SINGLE_Potentials'|@getTranslatedString:$MODULE}</b></label>
							</td>
						</tr>
						<tr>
							<td>
								<div id="potential_block" style="display:none;">
									<table border="0" cellspacing="0" cellpadding="5" width="100%" align="center" bgcolor="white">
										<tr>
										{if $UIINFO->isActive('potentialname','Potentials')}
											<td width="50%">
												{assign var=mandatory value=$UIINFO->isMandatory('Potentials','potentialname')}
												{include file="FieldHeader.tpl" uitype=$UIINFO->getUitype('Potentials','potentialname') mandatory=$mandatory label='LBL_POTENTIAL_NAME'|@getTranslatedString:$MODULE}
												<div class="{if $mandatory}dvtCellInfoOff{else}dvtCellInfo{/if}">
													<input name="potentialname" id="potentialname" {if $mandatory}record="true"{/if} module="Potentials" value="{$UIINFO->getMappedFieldValue('Potentials','potentialname',0)}" class="detailedViewTextBox" />
												</div>
											</td>
										{/if}
										{if $UIINFO->isActive('closingdate','Potentials')}
											<td width="50%">
												{assign var=mandatory value=$UIINFO->isMandatory('Potentials','closingdate')}
												{include file="FieldHeader.tpl" uitype=$UIINFO->getUitype('Potentials','closingdate') mandatory=$mandatory label='Expected Close Date'|@getTranslatedString:$MODULE}
												<div class="{if $mandatory}dvtCellInfoOff{else}dvtCellInfo{/if}">
													{* crmv@98824 crmv@100585 *}
													<table cellspacing="0" cellpadding="0" width="100%">
														<tr>
															<td><font size=1><em old="(yyyy-mm-dd)">({$DATE_FORMAT})</em></font></td>
															<td>
																<input name="closingdate" {if $mandatory}record="true"{/if} module="Potentials" class="detailedViewTextBox" id="jscal_field_closedate" type="text" tabindex="4" size="10" maxlength="10" value="{$UIINFO->getMappedFieldValue('Potentials','closingdate',1)}">
															</td>
															<td>
																<i class="vteicon md-link" id="jscal_trigger_closedate">event</i>
															</td>
														</tr>
													</table>
													<script id="conv_leadcal">
														(function() {ldelim}
															setupDatePicker('jscal_field_closedate', {ldelim}
																trigger: 'jscal_trigger_closedate',
																date_format: "{$DATE_FORMAT|strtoupper}",
																language: "{$APP.LBL_JSCALENDAR_LANG}",
															{rdelim});
														{rdelim})();
													</script>
													{* crmv@98824e crmv@100585e *}
												</div>
											</td>
										</tr>
										{/if}
										</tr>
										<tr>
										{if $UIINFO->isActive('sales_stage','Potentials')}
											<td width="50%">
												{assign var=mandatory value=$UIINFO->isMandatory('Potentials','sales_stage')}
												{include file="FieldHeader.tpl" uitype=$UIINFO->getUitype('Potentials','sales_stage') mandatory=$mandatory label='LBL_SALES_STAGE'|@getTranslatedString:$MODULE}
												<div class="{if $mandatory}dvtCellInfoOff{else}dvtCellInfo{/if}">
													{* crmv@77469 *}
													<select name="sales_stage" {if $mandatory}record="true"{/if} module="Potentials" class="detailedViewTextBox">
                                                        {$UIINFO->getPickListOptions('sales_stage','Potentials',$MODULE)}
													</select>
													{* crmv@77469e *}
												</div>
											</td>
										{/if}
										{if $UIINFO->isActive('amount','Potentials')}
											<td width="50%">
												{assign var=mandatory value=$UIINFO->isMandatory('Potentials','amount')}
												{include file="FieldHeader.tpl" uitype=$UIINFO->getUitype('Potentials','amount') mandatory=$mandatory label='Amount'|@getTranslatedString:$MODULE}
												<div class="{if $mandatory}dvtCellInfoOff{else}dvtCellInfo{/if}">
													<input type="text" name="amount" class="detailedViewTextBox" {if $mandatory}record="true"{/if} module="Potentials" value="{$UIINFO->getMappedFieldValue('Potentials','amount',1)}" />
												</div>
											</td>
										{/if}
										</tr>
									</table>
								</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			{/if}
			{if $UIINFO->isModuleActive('Contacts')}
			<tr>
				<td class="small">
					<table border="0" cellspacing="0" cellpadding="0" width="100%" align="center" bgcolor="white">
						<tr>
							<td colspan="4" class="detailedViewHeader">
								<input type="checkbox" checked="checked" onclick="javascript:showHideStatus('contact_block',null,null);" id="select_contact" name="entities[]" value="Contacts" />
								<label for="select_contact"><b>{'SINGLE_Contacts'|@getTranslatedString:$MODULE}</b></label>
							</td>
						</tr>
						<tr>
							<td>
								<div id="contact_block" style="display:block;" >
									<table border="0" cellspacing="0" cellpadding="5" width="100%" align="center" bgcolor="white">
										<tr>
											<td width="50%">
												{assign var=mandatory value=$UIINFO->isMandatory('Contacts','lastname')}
												{include file="FieldHeader.tpl" uitype=$UIINFO->getUitype('Contacts','lastname') mandatory=$mandatory label='Last Name'|@getTranslatedString:$MODULE}
												<div class="{if $mandatory}dvtCellInfoOff{else}dvtCellInfo{/if}">
													<input type="text" name="lastname" {if $mandatory}record="true"{/if} module="Contacts" class="detailedViewTextBox" value="{$UIINFO->getMappedFieldValue('Contacts','lastname',0)}">
												</div>
											</td>
											{if $UIINFO->isActive('firstname','Contacts')}
												<td width="50%">
													{assign var=mandatory value=$UIINFO->isMandatory('Contacts','firstname')}
													{include file="FieldHeader.tpl" uitype=$UIINFO->getUitype('Contacts','firstname') mandatory=$mandatory label='First Name'|@getTranslatedString:$MODULE}
													<div class="{if $mandatory}dvtCellInfoOff{else}dvtCellInfo{/if}">
														<input type="text" name="firstname" class="detailedViewTextBox" module="Contacts" value="{$UIINFO->getMappedFieldValue('Contacts','firstname',0)}" {if $mandatory}record="true"{/if} >
													</div>
											</tr>
											{/if}
										</tr>
										{if $UIINFO->isActive('email','Contacts')}
										<tr>
											<td width="50%">
												{assign var=mandatory value=$UIINFO->isMandatory('Contacts','email')}
												{include file="FieldHeader.tpl" uitype=$UIINFO->getUitype('Contacts','email') mandatory=$mandatory label='SINGLE_Emails'|@getTranslatedString:$MODULE}
												<div class="{if $mandatory}dvtCellInfoOff{else}dvtCellInfo{/if}">
													<input type="text" name="email" class="detailedViewTextBox" value="{$UIINFO->getMappedFieldValue('Contacts','email',0)}" {if $mandatory}record="true"{/if} module="Contacts">
												</div>
											</td>
											<td width="50%"></td>
										</tr>
										{/if}
									</table>
								</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			{/if}
			<tr>
				<td style="border-top:1px solid #DEDEDE;">
					<table border="0" cellspacing="0" cellpadding="5" width="100%" align="center" bgcolor="white">
						<tr valign="top">
							<td width="50%">
								{include file="FieldHeader.tpl" label='LBL_LIST_ASSIGNED_USER'|@getTranslatedString:$MODULE}
								<div>
									<input type="radio" id="c_assigntype_u" name="c_assigntype" value="U" onclick="javascript: c_toggleAssignType(this.value)" {$UIINFO->getUserSelected()} /><label for="c_assigntype_u">{'LBL_USER'|@getTranslatedString:$MODULE}</label>
									{if $UIINFO->getOwnerList('group')|@count neq 0}
										<input type="radio" id="c_assigntype_t" name="c_assigntype" value="T" onclick="javascript: c_toggleAssignType(this.value)" {$UIINFO->getGroupSelected()} /><label for="c_assigntype_t">{'LBL_GROUP'|@getTranslatedString:$MODULE}</label>
									{/if}
									<div class="dvtCellInfo" id="c_assign_user" style="display:{$UIINFO->getUserDisplay()}">
										<select name="c_assigned_user_id" class="detailedViewTextBox">
											{foreach item=user from=$UIINFO->getOwnerList('user') name=userloop}
												<option value="{$user.userid}" {if $user.selected eq true}selected="selected"{/if}>{$user.username}</option>
											{/foreach}
										</select>
									</div>
									<div class="dvtCellInfo" id="c_assign_team" style="display:{$UIINFO->getGroupDisplay()}">
										{if $UIINFO->getOwnerList('group')|@count neq 0}
										<select name="c_assigned_group_id" class="detailedViewTextBox">
											{foreach item=group from=$UIINFO->getOwnerList('group') name=grouploop}
												<option value="{$group.groupid}" {if $group.selected eq true}selected="selected"{/if}>{$group.groupname}</option>
											{/foreach}
										</select>
										{/if}
									</div>
								</div>
							</td>
							<td width="50%">
								{include file="FieldHeader.tpl" label='LBL_TRANSFER_RELATED_RECORDS_TO'|@getTranslatedString:$MODULE}
								<div>
									{if $UIINFO->isModuleActive('Accounts') eq true && $row.company neq ''}<input type="radio" name="transferto" id="transfertoacc" value="Accounts" onclick="selectTransferTo('Accounts')" {if $UIINFO->isModuleActive('Contacts') neq true}checked="checked"{/if} /><label for="transfertoacc">{'SINGLE_Accounts'|@getTranslatedString:$MODULE}</label>{/if}
									{if $UIINFO->isModuleActive('Contacts') eq true}<input type="radio" name="transferto" id="transfertocon" value="Contacts" checked="checked" onclick="selectTransferTo('Contacts')"  /><label for="transfertocon">{'SINGLE_Contacts'|@getTranslatedString:$MODULE}</label>{/if}
								</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<div class="closebutton" onClick="javascript:fninvsh('orgLay2');"></div>
	</div>
</form>
<script type="text/javascript" id="drag_conv_leadcal">
	// crmv@192014
	jQuery("#convertleaddiv").draggable({ldelim}
		handle: '#ConvertLead_Handle'
	{rdelim});
	// crmv@192014e
</script>