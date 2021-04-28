{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@39110 *}
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
	<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

		<div align="center">
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'} {* crmv@30683 *}

			<!-- TOP -->
			<table border="0" cellspacing="0" cellpadding="5" width="100%" class="settingsSelUITopLine">
				<tr>
					<td width="50" rowspan="2" valign="top"><img src="{'ico-roles.gif'|resourcever}" width="48" height="48" border="0" /></td>
					<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > <a href="index.php?module=Settings&action=listroles&parenttab=Settings">{$CMOD.LBL_ROLES}</a> &gt; {$CMOD.LBL_VIEWING} &quot;{$ROLE_NAME}&quot; </b></td> <!-- crmv@30683 -->
				</tr>
				<tr>
					<td valign=top class="small">{$CMOD.LBL_VIEWING} {$CMOD.LBL_PROPERTIES} &quot;{$ROLE_NAME}&quot; {$MOD.LBL_LIST_CONTACT_ROLE} </td>
				</tr>
			</table>

			<br>

			<div style="padding:10px">

				<form id="form" name="roleView" action="index.php" method="post" onsubmit="VteJS_DialogBox.block();">
					<input type="hidden" name="module" value="Settings">
					<input type="hidden" name="action" value="createrole">
					<input type="hidden" name="parenttab" value="Settings">
					<input type="hidden" name="returnaction" value="RoleDetailView">
					<input type="hidden" name="roleid" value="{$ROLEID}">
					<input type="hidden" name="mode" value="edit">

					<!-- TITLE AND BUTTON -->
					<table border="0" cellspacing="0" cellpadding="5" width="100%" class="tableHeading">
						<tr>
							<td class="big"><strong>{$CMOD.LBL_PROPERTIES} &quot;{$ROLE_NAME}&quot;</strong></td>
							<td><div align="right"><input value="   {$APP.LBL_EDIT_BUTTON_LABEL}   " title="{$APP.LBL_EDIT_BUTTON_TITLE}" accessKey="{$APP.LBL_EDIT_BUTTON_KEY}" class="crmButton small edit" type="submit" name="Edit" ></td>
				  		</tr>
					</table>
				</form>

				<!-- ROLE TABLE -->
				<table width="100%"  border="0" cellspacing="0" cellpadding="5">

					<tr class="small">
						<td width="15%" class="small cellLabel"><strong>{$CMOD.LBL_ROLE_NAME}</strong></td>
						<td width="85%" class="cellText" >{$ROLE_NAME}</td>
					</tr>

					<tr class="small">
						<td class="small cellLabel"><strong>{$CMOD.LBL_REPORTS_TO}</strong></td>
						<td class="cellText">{$PARENTNAME}</td>
					</tr>

					<tr class="small">
						<td valign=top class="cellLabel"><strong>{$CMOD.LBL_MEMBER}</strong></td>
						<td class="cellText">

							<table width="70%"  border="0" cellspacing="0" cellpadding="5">

								<tr class="small">
                            		<td colspan="2" class="cellBottomDotLine">
										<div align="left"><strong>{$CMOD.LBL_ASSOCIATED_PROFILES}</strong></div>
									</td>
                            	</tr>

								{foreach item=elements from=$ROLEINFO.profileinfo}
                          		<tr class="small">
                            		<td width="16"><div align="center"></div></td>
                            		<td><a href="index.php?module=Settings&action=profilePrivileges&parenttab=Settings&profileid={$elements.0}&mode=view">{$elements.1}</a><br></td>
                          		</tr>
								{/foreach}

								{if $ROLEINFO.profileinfo_mobile neq '' && count($ROLEINFO.profileinfo_mobile) > 0}
								<tr class="small">
                            		<td colspan="2" class="cellBottomDotLine">
										<div align="left"><strong>{$CMOD.LBL_ASSOCIATED_PROFILE_MOBILE} :</strong></div>
									</td>
                            	</tr>

                            	{foreach item=elements from=$ROLEINFO.profileinfo_mobile}
                          		<tr class="small">
                            		<td width="16"><div align="center"></div></td>
                            		<td><a href="index.php?module=Settings&action=profilePrivileges&parenttab=Settings&profileid={$elements.0}&mode=view">{$elements.1}</a><br></td>
                          		</tr>
								{/foreach}
								{/if}

   								<tr class="small">
                        			<td colspan="2" class="cellBottomDotLine">
										<div align="left"><strong>{$CMOD.LBL_ASSOCIATED_USERS}</strong></div>
									</td>
                            	</tr>

								{if $ROLEINFO.userinfo.0 neq ''}
								{foreach item=elements from=$ROLEINFO.userinfo}
                          		<tr class="small">
                            		<td width="16"><div align="center"></div></td>
                            		<td><a href="index.php?module=Users&action=DetailView&parenttab=Settings&record={$elements.0}">{$elements.1}</a><br></td>
                          		</tr>
								{/foreach}
								{/if}
                        	</table>
                        </td>
					</tr>
				</table>

				<br>

				{include file="Settings/ScrollTop.tpl"}

			</div>

		</div>
		</td>
	</tr></tbody>
</table>