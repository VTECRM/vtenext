{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@94525 crmv@150266 *}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JAVASCRIPT" type="text/javascript" src="include/js/ListRoles.js"></script>
<style type="text/css">
{literal}
a.x {
	color:black;
	text-align:center;
	text-decoration:none;
	padding:5px;
	font-weight:bold;
}
	
a.x:hover {
	color:#333333;
	text-decoration:underline;
	font-weight:bold;
}

.drag_Element {
	position:relative;
	left:0px;
	top:0px;
	padding-left:5px;
	padding-right:5px;
	border:0px dashed #CCCCCC;
	visibility:hidden;
}

#Drag_content {
	position:absolute;
	left:0px;
	top:0px;
	padding-left:5px;
	padding-right:5px;
	background-color:#000066;
	color:#FFFFFF;
	border:1px solid #CCCCCC;
	font-weight:bold;
	display:none;
}
{/literal}
</style>
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
        <td valign="top"></td>  
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
	<div align=center>

	
				{include file="SetMenu.tpl"}
				{include file='Buttons_List.tpl'} {* crmv@30683 *} 
				<!-- DISPLAY -->
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
				<tr>
					<td width=50 rowspan=2 valign=top><img src="{'ico-roles.gif'|resourcever}" alt="{$MOD.LBL_ROLES}" width="48" height="48" border=0 title="{$MOD.LBL_ROLES}"></td>
					<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS}  > {$MOD.LBL_ROLES}</b></td> <!-- crmv@30683 -->
				</tr>
				<tr>
					<td valign=top class="small">{$MOD.LBL_ROLE_DESCRIPTION}</td>
				</tr>
				</table>
				
				<br>
				<table border=0 cellspacing=0 cellpadding=10 width=100% >
				<tr>
				<td>
				
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
					<tr>
						<td class="big"><strong>{$MOD.LBL_ROLE_HIERARCHY_TREE}</strong></td>
						<td align=right id="listRolesVersion">
							{include file='Settings/ListRolesVersion.tpl'}
						</td>
					</tr>
					</table>

					<div id='RoleTreeFull' onMouseMove="ListRoles.displayCoords(event)">
       			        {include file='RoleTree.tpl'}
                	</div>
					
				</td>
				</tr>
				</table>
			
			</td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
		
	</div>

</td>
        <td valign="top"></td>
   </tr>
</tbody>
</table>
<div id="Drag_content">&nbsp;</div>

<script>
{if $ERROR_STRING neq ''}
	setTimeout(function(){ldelim}
		vtealert('{$ERROR_STRING}');
	{rdelim},500);
{/if}
</script>