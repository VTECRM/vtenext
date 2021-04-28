{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

<!-- 
{assign var=TAB_NAME value=$MOD.LBL_ADV_RULE}
{assign var=TAB_DESC value=$MOD.LBL_ADV_RULE_DESCRIPTION}
{assign var=TAB_IMG value="`$IMAGE_PATH`ico-adv_rule.gif"}
{assign var=TAB_IMG_ALT value=$MOD.LBL_USERS}
{assign var=TAB_IMG_TITLE value=$MOD.LBL_USERS}
 -->

<table align="center" width="100%">
	<tbody>
		<tr>
			<td width="100%">

				{include file="SetMenu.tpl"}
				{include file='Buttons_List.tpl'}

				<table width="100%">
					<tr>
						<td class="align-middle" width="50" rowspan="2">
							<img src="{$TAB_IMG}" alt="{$TAB_IMG_ALT}" width="48" height="48" title="{$TAB_IMG_TITLE}">
						</td>
						<td class="align-middle">
							<ul class="breadcrumb" style="margin-bottom: 0">
								<li>
									<a href="javascript:void(0)">
										<b>{$MOD.LBL_SETTINGS}</b>
									</a>
								</li>
								<li class="active">
									<b>{$TAB_NAME}</b>
								</li>
							</ul>
							<div style="padding: 2px 15px; word-break: break-all">{$TAB_DESC}</div>
						</td>
					</tr>
				</table>
				
				<br>
				<br>