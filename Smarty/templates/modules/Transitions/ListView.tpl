{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

<script type="text/javascript" src="modules/Transitions/Transitions.js"></script>

<div id="createcf" style="display:block;position:absolute;width:500px;"></div>


<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">
				   <form action="index.php" method="post" name="EditView" id="form">
					<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
					<input type='hidden' name='module' value='Transitions'>
					<input type='hidden' name='action' value='Transitions/EditView'>
					<input type='hidden' name='return_action' value='index'>
					<input type='hidden' name='return_module' value='Transitions'>
					<input type='hidden' name='return_mode' value='StateTransitions'>
					<input type='hidden' name='parenttab' value='Settings'>

					{include file='SetMenu.tpl'}
					{include file='Buttons_List.tpl'} {* crmv@30683 *}

					<!-- DISPLAY -->
					{if $TMODE neq 'edit'}
						<b><font color=red>{$DUPLICATE_ERROR} </font></b>
					{/if}


					<table class="settingsSelUITopLine" border="0" cellpadding="5" cellspacing="0" width="100%">
						<tbody>
							<tr>
								<td rowspan="2" valign="top" width="50"><img src="{'Transitions.gif'|resourcever}" alt="{$TMOD.LBL_ST_MANAGER}" title="{$TMOD.LBL_ST_MANAGER}" border="0" height="48" width="48"></td>
								<td class="heading2" valign="bottom"><b> {$SMOD.LBL_SETTINGS} &gt; {$TMOD.LBL_ST_MANAGER}</b></td> <!-- crmv@30683 -->
							</tr>
							<tr>
								<td class="small" valign="top">{$TMOD.LBL_ST_MANAGER_DESCRIPTION}</td>
							</tr>
						</tbody>
					</table>
					   <!-- crmv@191067 -->
					   <table border=0 cellspacing=0 cellpadding=3 width=100%>
						   <tr>
							   <td colspan="6" align="right">
								   <a href="index.php?module=Transitions&amp;action=Create&amp;parenttab=Settings" class="crmbutton small create">{$APP.LBL_NEW}</a>
							   </td>
						   </tr>
					   </table>
					   <!-- crmv@191067e -->
					   <!-- crmv@191067 -->
					   {if $TRANS_LIST_DATA_COUNT <= 0}
						   <i>{'LBL_TRANS_ST_FLD_NO_PERM'|getTranslatedString:'Transitions'}</i>
						   <br>
					   {else}
						   <br>
						   <i>{'LBL_TRANS_ST_FLD_PERM'|getTranslatedString:'Transitions'}</i>
						   <br>
						   <table class="table table-hover dataTable" id="TransitionsList">
							   <thead>
							   <th class="small">{'LBL_LIST_TOOLS'|getTranslatedString:'Users'}</th>
							   <th>{'LBL_MODULE'|getTranslatedString:'Transitions'}</th>
							   <th>{'LBL_CURR_ST_FIELD'|getTranslatedString:'Transitions'}</th>
							   <th>{'LBL_ROLE'|getTranslatedString:'Transitions'}</th>
							   </thead>
							   <tbody>
							   {foreach from=$TRANS_LIST_DATA key=data1 item=data2 name=records}
								   <tr class="odd" role="row" id="trtag{$smarty.foreach.records.index}">
									   <td class="listTableRow small">
										   <a href="index.php?module=Transitions&amp;action=Change&amp;parenttab=Settings&amp;module_name={$data2.module}&amp;role_id={$data2.roleid}&amp;field={$data2.field}">
											   <i class="vteicon" title="{$APP.LBL_EDIT}">create</i>
										   </a>											   &nbsp;
										   <a href="javascript:void(0);" onclick="deleteTransition('{$data2.module}', '{$data2.roleid}', '{$data2.field}', '{$data2.initial_value}', 'trtag{$smarty.foreach.records.index}')">
												   <i class="vteicon" title="{$APP.LBL_DELETE}">delete</i>
										   </a>
									   </td>
									   <td class="listTableRow small">{$data2.module}</td>
									   <td class="listTableRow small">{$data2.fieldlabel}</td>
									   <td class="listTableRow small">{$data2.role}</td>
								   </tr>
							   {/foreach}

							   </tbody>
						   </table>
					   {/if}
					   <!-- crmv@191067e -->

					   <!-- End of Display -->
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