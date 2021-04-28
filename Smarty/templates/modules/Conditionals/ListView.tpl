{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@155145 *}

<script type="text/javascript" src="modules/Conditionals/Conditionals.js"></script>

<div id="createcf" style="display:block;position:absolute;width:500px;"></div>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
				{include file='SetMenu.tpl'}
				{include file='Buttons_List.tpl'} {* crmv@30683 *}

				<!-- DISPLAY -->
				{if $MODE neq 'edit'}
				<b><font color=red>{$DUPLICATE_ERROR} </font></b>
				{/if}

				<table class="settingsSelUITopLine" border="0" cellpadding="5" cellspacing="0" width="100%">
					<tbody>
						<tr>
							<td rowspan="2" valign="top" width="50"><img src="{'workflow.gif'|resourcever}" alt="{$MOD.LBL_COND_MANAGER}" title="{$MOD.LBL_COND_MANAGER}" border="0" height="48" width="48"></td>
							<td class="heading2" valign="bottom"><b> {$MOD.LBL_SETTINGS} &gt; {$MOD.LBL_COND_MANAGER}</b></td> <!-- crmv@30683 -->
						</tr>
						<tr>
							<td class="small" valign="top">{$MOD.LBL_COND_MANAGER_DESCRIPTION}</td>
						</tr>
					</tbody>
				</table>

				<br>

				<table border=0 cellspacing=0 cellpadding=10 width=100%>
					<tr>
						<td>
							<div id="ListViewContents">
								{include file="modules/Conditionals/ListViewContents.tpl"}
							</div>
						</td>
					</tr>
				</table>

				<!-- End of Display -->
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

{literal}
<script type="text/javascript">
	function getListViewEntries_js(module, url) {
		VTE.Settings.Conditionals.getListViewEntries_js(module, url);
	}
</script>
{/literal}

{if $ERROR_STRING neq ''}
<script type="text/javascript">
	setTimeout(function() {ldelim}
		vtealert('{$ERROR_STRING}');
	{rdelim}, 500);
</script>
{/if}