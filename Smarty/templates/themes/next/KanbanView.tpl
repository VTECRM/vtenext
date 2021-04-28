{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@OPER6288 crmv@102334 *}

<script language="JavaScript" type="text/javascript" src="{"modules/`$MODULE`/`$MODULE`.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/dtlviewajax.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/ListView.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/KanbanView.js"|resourcever}"></script>
<script language="javascript" type="text/javascript" src="include/js/jquery_plugins/slimscroll/jquery.slimscroll.min.js"></script>

{include file='Buttons_List.tpl'}

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<div id="Buttons_List_Kanban">
				<table id="bl3" border=0 cellspacing=0 cellpadding=2 width=100% class="small">
					<tr height="34">
						<td align="left">
							{include file="Buttons_List_Contestual.tpl"}
						</td>
						<td align="right" style="padding-right:5px;">
							<!-- Filters -->
							<table border=0 cellspacing=0 cellpadding=0 class="small"><tr>
								<td style="padding-right:20px" nowrap>
									<a class="crmbutton only-icon" href="index.php?module={$MODULE}&amp;action=HomeView&amp;modhomeid={$MODHOMEID}&viewmode=ListView"><i class="vteicon" title="{'LBL_LIST'|getTranslatedString}" data-toggle="tooltip" data-placement="bottom">view_headline</i></a>
									<button type="button" class="crmbutton only-icon save" disabled><i class="vteicon" title="Kanban" data-toggle="tooltip" data-placement="bottom">view_column</i></button>
								</td>
								<td>{$APP.LBL_VIEW}</td>
								<td style="padding-left:5px;padding-right:5px">
									<div class="dvtCellInfo">
										<select name="viewname" id="viewname" class="detailedViewTextBox" onchange="showDefaultCustomView(this,'{$MODULE}','{$CATEGORY}','{$FOLDERID}','KanbanView','{$MODHOMEID}')">{$CUSTOMVIEW_OPTION}</select> {* crmv@141557 *}
									</div>
								</td>
								<td>
									{* crmv@21723 crmv@21827 crmv@22622 *}
									{if $HIDE_CUSTOM_LINKS neq '1'}
										<div class="dropdown vcenter" id="customViewEdit"> {* crmv@168361 *}
											<span data-toggle="dropdown">
												<i class="vteicon md-link" id="filter_option_img" data-toggle="tooltip" data-placement="bottom" title="{$APP.LBL_FILTER_OPTIONS}">settings</i>
											</span>
											<ul class="dropdown-menu" id="customLinks">
												{if $ALL eq 'All'}
													<li>
														<a href="index.php?module={$MODULE}&action=CustomView&duplicate=true&record={$VIEWID}&parenttab={$CATEGORY}">{$APP.LNK_CV_DUPLICATE}</a>
													</li>
													<li>
														<a href="index.php?module={$MODULE}&action=CustomView&parenttab={$CATEGORY}">{$APP.LNK_CV_CREATEVIEW}</a>
													</li>
												{else}
													{if $CV_EDIT_PERMIT eq 'yes'}
														<li>
															<a href="index.php?module={$MODULE}&action=CustomView&record={$VIEWID}&parenttab={$CATEGORY}">{$APP.LNK_CV_EDIT}</a>
														</li>
													{/if}
													
													<li>
														<a href="index.php?module={$MODULE}&action=CustomView&duplicate=true&record={$VIEWID}&parenttab={$CATEGORY}">{$APP.LNK_CV_DUPLICATE}</a>
													</li>
													
													{if $CV_DELETE_PERMIT eq 'yes'}
														<li>
															<a href="javascript:confirmdelete('index.php?module=CustomView&action=Delete&dmodule={$MODULE}&record={$VIEWID}&parenttab={$CATEGORY}')">{$APP.LNK_CV_DELETE}</a>
														</li>
													{/if}
													{if $CUSTOMVIEW_PERMISSION.ChangedStatus neq '' && $CUSTOMVIEW_PERMISSION.Label neq ''}
														<li>
															<a href="#" id="customstatus_id" onClick="ChangeCustomViewStatus({$VIEWID},{$CUSTOMVIEW_PERMISSION.Status},{$CUSTOMVIEW_PERMISSION.ChangedStatus},'{$MODULE}','{$CATEGORY}')">{$CUSTOMVIEW_PERMISSION.Label}</a>
														</li>
													{/if}
													<li>
														<a href="index.php?module={$MODULE}&action=CustomView&parenttab={$CATEGORY}">{$APP.LNK_CV_CREATEVIEW}</a>
													</li>
												{/if}
											</ul>
										</div>
									{/if}
									{* crmv@21723e crmv@21827e crmv@22622e *}
									{* crmv@29617 crmv@42752 *}
									{if $HIDE_CV_FOLLOW neq '1'}
										{* crmv@83305 *}
										{assign var=FOLLOWIMG value=$VIEWID|@getFollowImg:'customview'}
										{if preg_match('/_on/', $FOLLOWIMG)}
											{assign var=FOLLOWTITLE value='LBL_UNFOLLOW'|getTranslatedString:'ModNotifications'}
										{else}
											{assign var=FOLLOWTITLE value='LBL_FOLLOW'|getTranslatedString:'ModNotifications'}
										{/if}
										<div class="vcenter">
											<i data-toggle="tooltip" data-placement="bottom" id="followImgCV" title="{$FOLLOWTITLE}" class="vteicon md-link" onClick="ModNotificationsCommon.followCV();">{$VIEWID|@getFollowCls:'customview'}</i>
										</div>
										{* crmv@83305e *}
									{/if}
									{* crmv@29617e crmv@42752e *}
								</td> {* crmv@30967 *}
								{* crmv@7634 *}
								{if $OWNED_BY eq 0}
									<td style="padding-left:10px" nowrap>{$APP.LBL_ASSIGNED_TO}</td>
									<td style="padding-left:5px;"><div class="dvtCellInfo">{$LV_USER_PICKLIST}</div></td>
								{/if}
								{* crmv@7634e *}
							</tr></table>
						</td>
					</tr>
				</table>
			</div>

			<div id="KanbanViewContents" class="vte-card">
				{include file='KanbanGrid.tpl'}
			</div>
		</div>
	</div>
</div>