{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@150592 *}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JAVASCRIPT" type="text/javascript" src="include/js/ProfileUtils.js"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
	<div align=center>
				{include file='SetMenu.tpl'}
				{include file='Buttons_List.tpl'} {* crmv@30683 *} 
				{literal}
				<form action="index.php" method="post" name="profileform" id="form" onSubmit="if(ProfileUtils.rolevalidate('{$MOD.LBL_ENTER_PROFILE}')) { VteJS_DialogBox.block();return true;}else{return false;}">
				{/literal}
                                <input type="hidden" name="module" value="Settings">
                                <input type="hidden" name="mode" value="{$MODE}">
                                <input type="hidden" name="action" value="profilePrivileges">
                                <input type="hidden" name="parenttab" value="Settings">
                                <input type="hidden" name="parent_profile" value="{$PARENT_PROFILE}">
                                <input type="hidden" name="radio_button" value="{$RADIO_BUTTON}">
	
				<!-- DISPLAY -->
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
				<tr>
					<td width=50 rowspan=2 valign=top><img src="{'ico-profile.gif'|resourcever}" alt="{$MOD.LBL_PROFILES}" width="48" height="48" border=0 title="{$MOD.LBL_PROFILES}"></td>
					<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > <a href="index.php?module=Settings&action=ListProfiles&parenttab=Settings">{$CMOD.LBL_PROFILE_PRIVILEGES}</a></b></td> <!-- crmv@30683 -->
				</tr>
				<tr>
					<td valign=top class="small">{$MOD.LBL_PROFILE_DESCRIPTION}</td>
				</tr>
				</table>
				
				
				<table border=0 cellspacing=0 cellpadding=10 width=100% >
				<tr>
					<td valign="top">
					<table border="0" cellpadding="0" cellspacing="0" width="100%">
		                      	<tbody><tr>
                			     <td>
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
			                        <tbody><tr class="small">
		                              <td><img src="{'prvPrfTopLeft.gif'|resourcever}"></td>
               			               <td class="prvPrfTopBg" width="100%"></td>
		                              <td><img src="{'prvPrfTopRight.gif'|resourcever}"></td>

                	            </tr>
                          </tbody></table>
                            <table class="prvPrfOutline" border="0" cellpadding="0" cellspacing="0" width="100%">
                              <tbody>
				<tr>
                                              <td><!-- Module name heading -->
                                                  <table class="small" border="0" cellpadding="2" cellspacing="0">
                                                    <tbody><tr>
                                                      <td valign="top"><img src="{'prvPrfHdrArrow.gif'|resourcever}"> </td>
                                                      <td class="prvPrfBigText"><b> {$CMOD.LBL_STEP_1_2} : {$CMOD.LBL_WELCOME_PROFILE_CREATE} </b><br>
                                                          <font class="small"> {$CMOD.LBL_SELECT_CHOICE_NEW_PROFILE} </font> </td>

                                                      <td class="small" style="padding-left: 10px;" align="right"></td>
                                                    </tr>
                                                </tbody></table></td>
                                              <td align="right" valign="bottom">&nbsp;											  </td>
                                            </tr>
				<tr>
                                <td><!-- tabs -->
					<table width="95%" border="0" cellpadding="5" cellspacing="0" align="center">
					<tr><td colspan="2">&nbsp;</td></tr>
					<tr>
						<td align="right" width="25%" style="padding-right:10px;">
						<b style="color:#FF0000;font-size:16px;">{$APP.LBL_REQUIRED_SYMBOL}</b>&nbsp;<b>{$CMOD.LBL_NEW_PROFILE_NAME} : </b></td>
						<td width="75%" align="left" style="padding-left:10px;">
						<input type="text" name="profile_name" id="pobox" value="{$PROFILENAME}" class="txtBox" /></td>
					</tr>
					<tr><td colspan="2">&nbsp;</td></tr>
					<tr>
						<td align="right" style="padding-right:10px;" valign="top"><b>{$CMOD.LBL_DESCRIPTION} : </b></td>
						<td align="left" style="padding-left:10px;"><textarea name="profile_description" class="txtBox">{$PROFILEDESC}</textarea></td>
					</tr>
					<tr><td colspan="2" style="border-bottom:1px dashed #CCCCCC;" height="75">&nbsp;</td></tr>
					<tr>
						<td align="right" width="10%" style="padding-right:10px;">
						{if  $RADIO_BUTTON neq 'newprofile'}
						<input name="radiobutton" checked type="radio" value="baseprofile" />
						{else}
						<input name="radiobutton" type="radio"  value="baseprofile" />
						{/if}
						</td>
						<td width="90%" align="left" style="padding-left:10px;">{$CMOD.LBL_BASE_PROFILE_MESG}</td>
					</tr>
					<tr>
						<td align="right"  style="padding-right:10px;">&nbsp;</td>
						<td align="left" style="padding-left:10px;">{$CMOD.LBL_BASE_PROFILE}
						<select name="parentprofile" class="importBox">
							{foreach item=combo from=$PROFILE_LISTS}
							{if $PARENT_PROFILE eq $combo.1}
								<option  selected value="{$combo.1}">{$combo.0}</option>
							{else}
								<option value="{$combo.1}">{$combo.0}</option>	
							{/if}
							{/foreach}
						</select>
						</td>
					</tr>
					<tr><td colspan="2">&nbsp;</td></tr>
					<tr><td align="center" colspan="2"><b>(&nbsp;{$CMOD.LBL_OR}&nbsp;)</b></td></tr>
					<tr><td colspan="2">&nbsp;</td></tr>
					<tr>
						<td align="right" style="padding-right:10px;">
						{if  $RADIO_BUTTON eq 'newprofile'}
						<input name="radiobutton" checked type="radio" value="newprofile" />
						{else}
						<input name="radiobutton" type="radio" value="newprofile" />
						{/if}
						</td>
						<td  align="left" style="padding-left:10px;">{$CMOD.LBL_BASE_PROFILE_MESG_ADV}</td>
					</tr>
					<tr><td colspan="2" style="border-bottom:1px dashed #CCCCCC;" height="75">&nbsp;</td></tr>
					<tr>
						<td colspan="2" align="right">
						<input type="button" value=" {$APP.LNK_LIST_NEXT} &rsaquo; " title="{$APP.LNK_LIST_NEXT}" name="Next" class="crmButton small" onClick="return ProfileUtils.rolevalidate('{$MOD.LBL_ENTER_PROFILE}');"/>&nbsp;&nbsp;
						<input type="button" value=" {$APP.LBL_CANCEL_BUTTON_LABEL} " title="{$APP.LBL_CANCEL_BUTTON_TITLE}" name="Cancel" onClick="window.history.back();" class="crmButton small cancel"/>
						</td>
					</tr>
					</table>

                                </td></tr>  	  
                            	<table class="small" border="0" cellpadding="0" cellspacing="0" width="100%">
                              	<tbody><tr>
                                <td><img src="{'prvPrfBottomLeft.gif'|resourcever}"></td>
                                <td class="prvPrfBottomBg" width="100%"></td>
                                <td><img src="{'prvPrfBottomRight.gif'|resourcever}"></td>
                              </tr>
                          </tbody></table></td>
                      </tr>
                    </tbody></table>

					<p>&nbsp;</p>
					
				</td>
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
				</form>
   </tr>
</tbody>
</table>