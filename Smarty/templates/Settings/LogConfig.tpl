{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@173186 *}

<script type="text/javascript" src="{"modules/Settings/resources/LogConfig.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">
				<form action="index.php" method="post" id="form" onsubmit="VteJS_DialogBox.block();">
					{include file='SetMenu.tpl'}
					{include file='Buttons_List.tpl'}

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
						<tr>
							<td width=50 rowspan=2 valign=top><img src="{'set-IcoLoginHistory.gif'|resourcever}" alt="{$MOD.LBL_LOG_CONFIG}" width="48" height="48" border=0 title="{$MOD.LBL_LOG_CONFIG}"></td>
							<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_LOG_CONFIG}</b></td>
						</tr>
						<tr>
							<td valign=top>{$MOD.LBL_LOG_CONFIG_DESCRIPTION} </td>
						</tr>
					</table>

					<br>

					<table border=0 cellspacing=0 cellpadding=10 width=100%>
						<tr><td><b>{$MOD.LBL_LOG_GENERAL_CONFIG}</b></td></tr>
						<tr>
							<td>
								<table class="table">
									{foreach key=key item=conf from=$GENERAL_CONFIG}
										<tr>
											<td width="10%" class="cell-vcenter">{$conf.label|getTranslatedString:'Settings'}</td>
											<td width="90%" class="cell-vcenter">
												{if $conf.ui eq 'picklist'}
													<div class="dvtCellInfo" {if isset($conf.ui_prop.picklist_width)}style="width:{$conf.ui_prop.picklist_width}"{/if}>
														<select name="{$key}" id="{$key}" class="detailedViewTextBox" onchange="VTE.Settings.LogConfig.saveGlobalConfig('{$key}',this.value)">
															{foreach key=ov item=ol from=$conf.ui_prop.picklist_values}
																<option value="{$ov}" {if $ov eq $conf.value}selected{/if}>{$ol}</option>
															{/foreach}
														</select>
													</div>
												{/if}
											</td>
										</tr>
									{/foreach}
								</table>
							</td>
						</tr>
						<tr><td><b>{$MOD.LBL_LOG_LIST}</b></td></tr>
						<tr>
							<td>
								<table class="table table-hover">
									{foreach key=logid item=conf from=$CONFIG}
										<tr>
											<td width="10%" class="cell-vcenter">
												{if $conf.enabled}
													<a href="javascript:void(0);" onclick="VTE.Settings.LogConfig.toggleLogProp('{$logid}')"><i class="vteicon checkok" title="{$MOD.LBL_DISABLE}">check</i></a>
												{else}
													<a href="javascript:void(0);" onclick="VTE.Settings.LogConfig.toggleLogProp('{$logid}')"><i class="vteicon checkko" title="{$MOD.LBL_ENABLE}">clear</i></a>
												{/if}
												
												&nbsp;&nbsp;
												
												{if $conf.file}
													<a href="index.php?module=Settings&action=SettingsAjax&file=LogView&log={$logid}" target="_blank">
														<i class="vteicon" title="{$MOD.LBL_VIEW_LOG}">info_outline</i>
													</a>
												{/if}
											</td>
											<td width="90%" class="cell-vcenter">{$conf.label|getTranslatedString:'Settings'}</td>
										</tr>
									{/foreach}
								</table>
							</td>
						</tr>
						{* crmv@181096 *}
						<tr><td><b>{$MOD.LBL_OTHER_LOG_LIST}</b></td></tr>
						<tr>
							<td>
								<table class="table table-hover">
									{foreach item=conf from=$OTHER_LOGS}
										<tr>
											<td width="10%" class="cell-vcenter">
												{if $conf.url}
													<a href="{$conf.url}" target="_blank">
														<i class="vteicon" title="{$MOD.LBL_VIEW_LOG}">info_outline</i>
													</a>
												{/if}
											</td>
											<td width="90%" class="cell-vcenter">{$conf.label}</td>
										</tr>
									{/foreach}
								</table>
							</td>
						</tr>
						{* crmv@181096e *}
					</table>

					{* SetMenu.tpl *}
					</td>
					</tr>
					</table>
					</td>
					</tr>
					</table>
				</form>
			</td>
			<td valign="top"></td>
		</tr>
	</tbody>
</table>