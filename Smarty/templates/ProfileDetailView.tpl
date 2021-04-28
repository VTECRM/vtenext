{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@97692 crmv@150592 *}
{literal}
<style type="text/css">
.showTable{
	display:inline-table;
}
.hideTable{
	display:none;
}
</style>
{/literal}
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JAVASCRIPT" type="text/javascript" src="include/js/ProfileUtils.js"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

	<div align=center>
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'} {* crmv@30683 *}
				<form  method="post" name="new" id="form" onsubmit="VteJS_DialogBox.block();">
			        <input type="hidden" name="module" value="Settings">
			        <input type="hidden" name="action" value="profilePrivileges">
			        <input type="hidden" name="parenttab" value="Settings">
			        <input type="hidden" name="return_action" value="profilePrivileges">
			        <input type="hidden" name="mode" value="edit">
			        <input type="hidden" name="profileid" value="{$PROFILEID}">

				<!-- DISPLAY -->
				<table class="settingsSelUITopLine" border="0" cellpadding="5" cellspacing="0" width="100%">
				<tbody><tr>
					<td rowspan="2" valign="top" width="50"><img src="{'ico-profile.gif'|resourcever}" alt="{$MOD.LBL_PROFILES}" title="{$MOD.LBL_PROFILES}" border="0" height="48" width="48"></td>
					<td class="heading2" valign="bottom"><b>{$MOD.LBL_SETTINGS} > <a href="index.php?module=Settings&action=ListProfiles&parenttab=Settings">{$CMOD.LBL_PROFILE_PRIVILEGES}</a> &gt; {$CMOD.LBL_VIEWING} &quot;{$PROFILE_NAME}&quot;</b></td> <!-- crmv@30683 -->
				</tr>
				<tr>
					<td class="small" valign="top">{$CMOD.LBL_PROFILE_MESG} &quot;{$PROFILE_NAME}&quot; </td>
				</tr>
				</tbody></table>


				<table border="0" cellpadding="10" cellspacing="0" width="100%">
				<tbody><tr>
				<td valign="top">
					<table border="0" cellpadding="0" cellspacing="0" width="100%">
                      <tbody><tr>
                        <td><table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tbody><tr class="small">
                              <td><img src="{'prvPrfTopLeft.gif'|resourcever}"></td>
                              <td class="prvPrfTopBg" width="100%"></td>
                              <td><img src="{'prvPrfTopRight.gif'|resourcever}"></td>
                            </tr>
                          </tbody></table>
                            <table class="prvPrfOutline" border="0" cellpadding="0" cellspacing="0" width="100%">
                              <tbody><tr>
                                <td><!-- tabs -->

                                    <!-- Headers -->
                                    <table border="0" cellpadding="5" cellspacing="0" width="100%">
                                      <tbody><tr>
                                        <td><table class="small" border="0" cellpadding="5" cellspacing="0" width="100%">
                                            <tbody><tr>
                                              <td><!-- Module name heading -->
                                                  <table class="small" border="0" cellpadding="2" cellspacing="0">
                                                    <tbody><tr>
                                                      <td valign="top"><img src="{'prvPrfHdrArrow.gif'|resourcever}"> </td>
                                                      <td class="prvPrfBigText"><b> {$CMOD.LBL_DEFINE_PRIV_FOR} &lt;{$PROFILE_NAME}&gt; </b><br>
                                                      <font class="small">{$CMOD.LBL_USE_OPTION_TO_SET_PRIV}</font> </td>
                                                      <td class="small" style="padding-left: 10px;" align="right"></td>

                                                    </tr>
                                                </tbody></table>
                                            </td>
											<td align="right" valign="bottom">
												&nbsp;<input type="button" value="{$APP.LBL_RENAMEPROFILE_BUTTON_LABEL}" title="{$APP.LBL_RENAMEPROFILE_BUTTON_LABEL}" class="crmButton small edit" name="rename_profile"  onClick = "showFloatingDiv('renameProfile');">
												&nbsp;<input type="submit" value="{$APP.LBL_EDIT_BUTTON_LABEL}" title="{$APP.LBL_EDIT_BUTTON_LABEL}" class="crmButton small edit" name="edit" >
                              		     </td>

                                            </tr></tbody></table>
                                   
{* RenameProfile Div start *}
{assign var="FLOAT_TITLE" value=$APP.LBL_RENAME_PROFILE}
{assign var="FLOAT_WIDTH" value="500px"}
{capture assign="FLOAT_CONTENT"}
	<table align="center" border="0" cellpadding="5" cellspacing="0" width="95%">
		<tr>
		<td class="small">
			<table cellspacing="0" align="center" bgcolor="white" border="0" cellpadding="5" width="100%">
				<tr>
					<td align="right" width="25%" style="padding-right:10px;" nowrap><b>{$APP.LBL_PROFILE_NAME} :</b></td>
					<td align="left" width="75%" style="padding-right:10px;"><input id = "profile_name" name="profile_name" class="txtBox" value="{$PROFILE_NAME}" type="text"></td>
				</tr>
				<tr>
					<td align="right" width="25%" style="padding-right:10px;" nowrap><b>{$APP.LBL_DESCRIPTION} :</b></td>
					<td align="left" width="75%" style="padding-right:10px;"><textarea name="description" id = "description" class="txtBox">{$PROFILE_DESCRIPTION}</textarea></td>
				</tr>
			</table>
		</td>
		</tr>
	</table>
	<table class="layerPopupTransport" border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr>
		<td align = "center">
			<input name="save" value="{$APP.LBL_UPDATE}" class="crmbutton small save" onclick="ProfileUtils.UpdateProfile('{$PROFILEID}','{$APP.PROFILENAME_CANNOT_BE_EMPTY}','{$APP.PROFILE_DETAILS_UPDATED}');" type="button" title="{$APP.LBL_UPDATE}">&nbsp;&nbsp;
			<input name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" class="crmbutton small save" onclick="hideFloatingDiv('renameProfile');" type="button" title="{$APP.LBL_CANCEL_BUTTON_LABEL}">&nbsp;&nbsp;
		</td>
		</tr>
	</table>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="renameProfile" FLOAT_BUTTONS=""}
{* RenameProfile Div end *}

                                            <!-- privilege lists -->
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                              <tbody><tr>
                                                <td style="height: 10px;" align="center"></td>
                                              </tr>
                                            </tbody></table>
                                            <table border="0" cellpadding="10" cellspacing="0" width="100%">
                                              <tbody><tr>
                                                <td>

						<table border="0" cellpadding="5" cellspacing="0" width="100%">
  							<tbody>
								<tr><td class="cellLabel big"> {$CMOD.LBL_SUPER_USER_PRIV} </td></tr>
							</tbody>
						</table>
						<table class="small" align="center" border="0" cellpadding="5" cellspacing="0" width="90%">
							<tbody>
								<tr>
									<td class="prvPrfTexture" style="width: 20px;">&nbsp;</td>
									<td valign="top" width="97%">
										<table class="small" border="0" cellpadding="2" cellspacing="0" width="100%">
											<tbody>
												<tr id="gva">
													<td valign="top">{$GLOBAL_PRIV.0}</td>
													<td><b>{$CMOD.LBL_VIEW_ALL}</b></td>
												</tr>
												<tr>
													<td valign="top"></td>
													<td width="100%">{$CMOD.LBL_ALLOW} "{$PROFILE_NAME}" {$CMOD.LBL_MESG_VIEW}</td>
												</tr>
												<tr>
													<td>&nbsp;</td>
												</tr>
												<tr>
													<td valign="top">{$GLOBAL_PRIV.1}</td>
													<td><b>{$CMOD.LBL_EDIT_ALL}</b></td>
												</tr>
												<tr>
													<td valign="top"></td>
													<td>{$CMOD.LBL_ALLOW} "{$PROFILE_NAME}"	{$CMOD.LBL_MESG_EDIT}</td>
												</tr>
											</tbody>
										</table>
									</td>
								</tr>
							</tbody>
						</table>
						<br>

						{* crmv@39110 *}
						<table border="0" cellpadding="5" cellspacing="0" width="100%">
  							<tbody>
								<tr><td class="cellLabel big"> {$CMOD.LBL_MOBILE_HANDLING} </td></tr>
							</tbody>
						</table>
						<table class="small" align="center" border="0" cellpadding="5" cellspacing="0" width="90%">
							<tbody>
								<tr>
									<td class="prvPrfTexture" style="width: 20px;">&nbsp;</td>
									<td valign="top" width="97%">
										<table class="small" border="0" cellpadding="2" cellspacing="0" width="100%">
											<tbody>
												<tr id="gva">
													<td valign="top">{$MOBILE_PRIV.image}</td>
													<td><b>{$CMOD.LBL_MOBILE_PROFILE}</b></td>
												</tr>
												<tr>
													<td valign="top"></td>
													<td width="100%">{$CMOD.LBL_PROFILE_TO_BE_USED_FOR_MOBILE|sprintf:$PROFILE_NAME}</td>
												</tr>
											</tbody>
										</table>
									</td>
								</tr>
							</tbody>
						</table>
						<br>
						{* crmv@39110e *}

			<table border="0" cellpadding="5" cellspacing="0" width="100%">
			  <tbody><tr>
			    <td class="cellLabel big"> {$CMOD.LBL_SET_PRIV_FOR_EACH_MODULE} </td>
			  </tr>
			</tbody></table>
			<table class="small" align="center" border="0" cellpadding="5" cellspacing="0" width="90%">
			  <tbody><tr>
			    <td class="prvPrfTexture" style="width: 20px;">&nbsp;</td>
			    <td valign="top" width="97%">
				<table class="small listTable" border="0" cellpadding="5" cellspacing="0" width="100%">
			        <tbody>
				<tr id="gva">
		        	<td colspan="2" rowspan="2" class="small colHeader">
			          	<strong> {$CMOD.LBL_TAB_MESG_OPTION} </strong>
		         	</td>
			        <td colspan="3" class="small colHeader">
			          	<div align="center">
			          		<strong> {$CMOD.LBL_EDIT_PERMISSIONS}
			          		</strong>
			          	</div>
			        </td>
			        <td rowspan="2" class="small colHeader" nowrap="nowrap">
						{$CMOD.LBL_FIELDS_AND_TOOLS_SETTINGS}
					</td>
		        </tr>
		        <tr id="gva">
			    	<td class="small colHeader">
			          	<div align="center">
				          	<strong>
								{$CMOD.LBL_CREATE_EDIT}
				          	</strong>
			          	</div>
			        </td>
			       	<td class="small colHeader">
			          	<div align="center">
			          		<strong>
								{$CMOD.LBL_VIEW}
							</strong>
						</div>
					</td>
			        <td class="small colHeader">
			        	<div align="center">
				        	<strong>
								{$CMOD.LBL_DELETE}
							</strong>
						</div>
					</td>
		       </tr>

				<!-- module loops-->
			        {foreach key=tabid item=elements from=$TAB_PRIV}
			        <tr>
					{assign var=modulename value=$TAB_PRIV[$tabid][0]}
					{assign var="MODULELABEL" value=$modulename|@getTranslatedString:$modulename}
			          <td class="small cellLabel" width="3%"><div align="right">
					{$TAB_PRIV[$tabid][1]}
			          </div></td>
			          <td class="small cellLabel" width="40%"><p>{$MODULELABEL}</p></td>
		    {if $modulename neq 'Projects'}
			          <td class="small cellText" width="15%">&nbsp;<div align="center">
					{$STANDARD_PRIV[$tabid][1]}
			          </div></td>
			          <td class="small cellText" width="15%">&nbsp;<div align="center">
					{$STANDARD_PRIV[$tabid][3]}
			          </div></td>
			          <td class="small cellText" width="15%">&nbsp;<div align="center">
					{$STANDARD_PRIV[$tabid][2]}
        			  </div></td>
			{else}
				  <td class="small cellText" width="15%">&nbsp;<div align="center"></div></td>
			      <td class="small cellText" width="15%">&nbsp;<div align="center"></div></td>
			      <td class="small cellText" width="15%">&nbsp;<div align="center"></div></td>
			{/if}
			          <td class="small cellText" width="22%">&nbsp;<div align="center">
						{if $FIELD_PRIVILEGES[$tabid] neq NULL}
						<img src="{'showDown.gif'|resourcever}" alt="{$APP.LBL_EXPAND_COLLAPSE}" title="{$APP.LBL_EXPAND_COLLAPSE}" onclick="ProfileUtils.fnToggleVIew('{$modulename}_view')" border="0" height="16" width="40">
						{/if}
						</div></td>
					</tr>
		                  <tr class="hideTable" id="{$modulename}_view" className="hideTable">
				          <td colspan="6" class="small settingsSelectedUI">
						<table class="small" border="0" cellpadding="2" cellspacing="0" width="100%">
			        	    	<tbody>
						{if $FIELD_PRIVILEGES[$tabid] neq ''}
						<tr>
							{if $modulename eq 'Calendar'}
				                	<td class="small colHeader" colspan="6" valign="top">{$CMOD.LBL_FIELDS_TO_BE_SHOWN} ({$APP.Tasks})</td>
							{else}
				                	<td class="small colHeader" colspan="6" valign="top">{$CMOD.LBL_FIELDS_TO_BE_SHOWN}</td>
							{/if}
					        </tr>
						{/if}
						{foreach item=row_values from=$FIELD_PRIVILEGES[$tabid]}
							<tr>
								{foreach item=element from=$row_values}
									{* crmv@49510 *}
									<td>{$element.0}&nbsp;{$element.1}</td>
									<td valign="top">{$element.2}</td>
									{* crmv@49510e *}
								{/foreach}
							</tr>
						{/foreach}
						{if $modulename eq 'Calendar'}
							<tr>
								<td class="small colHeader" colspan="6" valign="top">{$CMOD.LBL_FIELDS_TO_BE_SHOWN}  ({$APP.Events})</td>
					        </tr>
							{foreach item=row_values from=$FIELD_PRIVILEGES[16]}
								<tr>
									{foreach item=element from=$row_values}
							      		{* crmv@49510 *}
										<td>{$element.0}&nbsp;{$element.1}</td>
										<td valign="top">{$element.2}</td>
										{* crmv@49510e *}
									{/foreach}
								</tr>
							{/foreach}
						{/if}
						{if $UTILITIES_PRIV[$tabid] neq ''}
					        <tr>
								<td colspan="6" class="small colHeader" valign="top">{$CMOD.LBL_TOOLS_TO_BE_SHOWN} </td>
							</tr>
						{/if}
						{foreach item=util_value from=$UTILITIES_PRIV[$tabid]}
							<tr>
								{foreach item=util_elements from=$util_value}
									<td valign="top">{$util_elements.1}</td>
									<td>{$APP[$util_elements.0]}</td>
								{/foreach}
							</tr>
						{/foreach}
					        </tbody>
						</table>
					</td>
			          </tr>

				  {/foreach}
			{* //sk@2 *}
	        {foreach key=tabid item=elements from=$TAB_PRIV}
				{if $TAB_PRIV[$tabid][0] eq 'Projects'}
	              {assign var=projects_tabid value=$tabid}
	              {assign var=modulename value='Projects'}
	            {/if}
			{/foreach}
			{if $projects_tabid neq ''}
	          <tr id="gva">
			        <td colspan="2" rowspan="2" class="small colHeader"><strong> {$CMOD.LBL_TAB_MESG_OPTION} </strong><strong></strong></td>
			        <td colspan="5" class="small colHeader"><div align="center"><strong> {$CMOD.LBL_EDIT_PERMISSIONS} </strong></div></td>
			      </tr>
			      <tr id="gva">
			        <td class="small colHeader" colspan="2"><div align="center"><strong>{$CMOD.PROJECT_ADMIN}</strong></div></td>
			        <td class="small colHeader" colspan="3"> <div align="center"><strong>{$CMOD.PROJECT_LEADER}</strong></div></td>
			      </tr>
			      <tr>
	            <td class="small cellLabel" width="3%">
	             <div align="right">
				       {$TAB_PRIV[$projects_tabid][1]}
	  		       </div>
	            </td>
	  			    <td class="small cellLabel" width="40%"><p>{$APP[$modulename]}</p></td>
	  			    <td class="small cellText" width="15%" colspan="2">&nbsp;
	              <div align="center">
					       {$STANDARD_PRIV[$projects_tabid][1]}
				         </div>
	            </td>
	  			    <td class="small cellText" width="15%" colspan="3">&nbsp;
	             <div align="center">
						   {$STANDARD_PRIV[$projects_tabid][2]}
				       </div>
	            </td>
	          </tr>
			{/if}
			{* //sk@2e *}
			    	  </tbody>
				  </table>
			  </td>
			  </tr>
                          </tbody>
			</table>
		</td>
		</tr>
		<tr>
		<td style="border-top: 2px dotted rgb(204, 204, 204);" align="right">
		<!-- wizard buttons -->
		<table border="0" cellpadding="2" cellspacing="0">
		<tbody>
			<tr>
				<td><input type="submit" value="{$APP.LBL_EDIT_BUTTON_LABEL}" title="{$APP.LBL_EDIT_BUTTON_LABEL}" class="crmButton small edit" name="edit"></td>
				<td>&nbsp;</td>
			</tr>

		</tbody>
		</table>
		</td>
		</tr>
          </tbody>
	  </table>
	</td>
        </tr>
        </tbody>
	</table>
      </td>
      </tr>
      </tbody></table>
      <table class="small" border="0" cellpadding="0" cellspacing="0" width="100%">
           <tbody><tr>
                <td><img src="{'prvPrfBottomLeft.gif'|resourcever}"></td>
                <td class="prvPrfBottomBg" width="100%"></td>
                <td><img src="{'prvPrfBottomRight.gif'|resourcever}"></td>
                </tr>
            </tbody>
      </table></td>
      </tr>
      </tbody></table>
	<p>&nbsp;</p>

	</td>
	</tr>
	</tbody></table>
	</form>
	<!-- End of Display -->
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