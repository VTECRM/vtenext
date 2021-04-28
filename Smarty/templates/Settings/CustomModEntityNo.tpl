{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script type="text/javascript" src="{"modules/Settings/resources/CustomModEntityNo.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
				{include file='SetMenu.tpl'}
				{include file='Buttons_List.tpl'} {* crmv@30683 *} 

				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
					<tr>
						<td width=50 rowspan=2 valign=top><img src="{'settingsInvNumber.gif'|resourcever}" alt="{$MOD.LBL_CUSTOMIZE_MODENT_NUMBER}" width="48" height="48" border=0 title="{$MOD.LBL_CUSTOMIZE_MODENT_NUMBER}"></td>
						<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_CUSTOMIZE_MODENT_NUMBER}</b></td> <!-- crmv@30683 -->
					</tr>
					<tr>
						<td valign=top>{$MOD.LBL_CUSTOMIZE_MODENT_NUMBER_DESCRIPTION}</td>
					</tr>
				</table>

				<br>

				{if $EMPTY eq 'true'}
					<table border='0' cellpadding='5' cellspacing='0' width='98%'>
						<tbody>
							<tr>
								<td rowspan='2' width='11%'><img src="{'denied.gif'|resourcever}"></td>
								<td style='border-bottom: 1px solid rgb(204, 204, 204);' nowrap='nowrap' width='70%'>
									<span class='genHeaderSmall'>{$APP.LBL_NO_MODULES_TO_SELECT}</span></td>
								</tr>
						</tbody>
					</table>
				{else}
					<form method="POST" action="javascript:;" onsubmit="VteJS_DialogBox.block();">
						<table border="0" cellpadding="10" cellspacing="0" width="100%">
							<tr>
								<td>
									<table class="tableHeading" border="0" cellpadding="5" cellspacing="0" width="100%">
										<tr>
											<td align="right">
												{$MOD.LBL_SELECT_CF_TEXT}
												<select name="selmodule" class="detailedViewTextBox input-inline" onChange="VTE.Settings.CustomModEntityNo.getModuleEntityNoInfo(this.form)">
													{foreach key=sel_value item=label from=$MODULES}
														{if $SELMODULE eq $sel_value}
															{assign var="selected_val" value="selected"}
														{else}
															{assign var="selected_val" value=""}
														{/if}
														<option value="{$sel_value}" {$selected_val}>{$label}</option>
													{/foreach}
												</select>
											</td>
										</tr>
									</table>

									<div id='customentity_infodiv' class="listRow">
										{include file='Settings/CustomModEntityNoInfo.tpl'}				
									</div>

									{include file="Settings/ScrollTop.tpl"}
								</td>
							</tr>
						</table>
					</form>
				{/if}
		
				{* SetMenu.tpl *}
				</td>
				</tr>
				</table>
				</td>
				</tr>
				</table>
			</td>
			<td valign="top"></td>
		</tr>
	</tbody>
</table>