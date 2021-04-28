{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{literal}
<style>
.showTable{
	display:inline-table;
}
.hideTable{
	display:none;
}
</style>
{/literal}
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JAVASCRIPT" type="text/javascript" src="include/js/ProfileUtils.js"></script> {* crmv@192033 *}
<script type="text/javascript" src="include/js/pako/pako.min.js?v=1.0.6"></script> {* crmv@162674 *}

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
	<div align=center>
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'} {* crmv@30683 *}
				<!-- DISPLAY -->
				<form action="index.php" method="post" name="profileform" id="form" onsubmit="if (countFormVars('form','form_var_count')) VteJS_DialogBox.block(); else return false;">	{* crmv@111926 *}
				<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
				<input type="hidden" name="module" value="Users">
				<input type="hidden" name="parenttab" value="Settings">
				<input type="hidden" name="action" value="{$ACTION}">
				<input type="hidden" name="mode" value="{$MODE}">
				<input type="hidden" name="profileid" value="{$PROFILEID}">
				<input type="hidden" name="profile_name" value="{$PROFILE_NAME}">
				<input type="hidden" name="profile_description" value="{$PROFILE_DESCRIPTION}">
				<input type="hidden" name="parent_profile" value="{$PARENTPROFILEID}">
				<input type="hidden" name="radio_button" value="{$RADIOBUTTON}">
				<input type="hidden" name="return_action" value="{$RETURN_ACTION}">

				<table class="settingsSelUITopLine" border="0" cellpadding="5" cellspacing="0" width="100%">
				<tbody><tr>
					<td rowspan="2" valign="top" width="50"><img src="{'ico-profile.gif'|resourcever}" alt="{$MOD.LBL_PROFILES}" title="{$MOD.LBL_PROFILES}" border="0" height="48" width="48"></td>
					<td class="heading2" valign="bottom"><b> {$MOD.LBL_SETTINGS} > <a href="index.php?module=Settings&action=ListProfiles&parenttab=Settings">{$CMOD.LBL_PROFILE_PRIVILEGES}</a> &gt; {$CMOD.LBL_VIEWING} &quot;{$PROFILE_NAME}&quot;</b></td> <!-- crmv@30683 -->
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
                                                      <td class="prvPrfBigText"><b> {if $MODE eq 'create'}{$CMOD.LBL_STEP_2_2} : {/if}{$CMOD.LBL_DEFINE_PRIV_FOR} &lt;{$PROFILE_NAME}&gt; </b><br>
                                                      <font class="small">{$CMOD.LBL_USE_OPTION_TO_SET_PRIV}</font> </td>
                                                      <td class="small" style="padding-left: 10px;" align="right"></td>
                                                    </tr>
                                                </tbody></table></td>
                                              <td align="right" valign="bottom">&nbsp;											 	{if $ACTION eq 'SaveProfile'}
                                                <input type="submit" value=" {$CMOD.LBL_FINISH_BUTTON} " name="save" class="crmButton create small" title="{$CMOD.LBL_FINISH_BUTTON}"/>&nbsp;&nbsp;
                                                {else}
                                                        <input type="submit" value=" {$APP.LBL_SAVE_BUTTON_LABEL} " name="save" class="crmButton small save" title="{$APP.LBL_SAVE_BUTTON_LABEL}"/>&nbsp;&nbsp;
                                                {/if}
                                                <input type="button" value=" {$APP.LBL_CANCEL_BUTTON_LABEL} " name="Cancel" class="crmButton cancel small" title="{$APP.LBL_CANCEL_BUTTON_LABEL}" onClick="window.history.back();" />
						</td>
                                            </tr>
                                          </tbody></table>
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
											<table	class="small" border="0" cellpadding="2" cellspacing="0" width="100%">
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
											<table	class="small" border="0" cellpadding="2" cellspacing="0" width="100%">
												<tbody>
													<tr id="gva">
														<td valign="top">{$MOBILE_PRIV.checkbox}</td>
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
			          <td colspan="2" rowspan="2" class="small colHeader"><strong> {$CMOD.LBL_TAB_MESG_OPTION} </strong><strong></strong></td>
			          <td colspan="3" class="small colHeader"><div align="center"><strong>{$CMOD.LBL_EDIT_PERMISSIONS}</strong></div></td>
			          <td rowspan="2" class="small colHeader" nowrap="nowrap">{$CMOD.LBL_FIELDS_AND_TOOLS_SETTINGS}</td>
			        </tr>
			        <tr id="gva">
			          <td class="small colHeader"><div align="center"><strong>
		                {$CMOD.LBL_CREATE_EDIT}
			          </strong></div></td>
			          <td class="small colHeader"> <div align="center"><strong>{$CMOD.LBL_VIEW}</strong></div></td>
			          <td class="small colHeader"> <div align="center"><strong>{$CMOD.LBL_DELETE}</strong></div></td>
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
			          <td class="small cellText" width="15%">&nbsp;<div align="center">
			          {* //sk@2 *}
		                {if $modulename neq 'Projects'}
		   			      	{$STANDARD_PRIV[$tabid][1]}
		  			      {/if}
		                {* //sk@2e *}
			          </div></td>
			          <td class="small cellText" width="15%">&nbsp;<div align="center">
		   			  	{$STANDARD_PRIV[$tabid][3]}
			          </div></td>
			          <td class="small cellText" width="15%">&nbsp;<div align="center">
			          {* //sk@2 *}
                	  {if $modulename neq 'Projects'}
    				  	{$STANDARD_PRIV[$tabid][2]}
		  		      {/if}
                	  {* //sk@2e *}
        			  </div></td>
			          <td class="small cellText" width="22%">&nbsp;<div align="center">
				{if $FIELD_PRIVILEGES[$tabid] neq NULL}
				<img src="{'showDown.gif'|resourcever}" id="img_{$tabid}" alt="{$APP.LBL_EXPAND_COLLAPSE}" title="{$APP.LBL_EXPAND_COLLAPSE}" onclick="ProfileUtils.fnToggleVIew('{$tabid}_view')" border="0" height="16" width="40" style="display:block;"> {* crmv@192033 *}
				{/if}
				</div></td>
				  </tr>
				{* crmv@49510 *}
				<tr class="hideTable" id="{$tabid}_view" className="hideTable">
					<td colspan="6" class="small settingsSelectedUI">
						<table class="small" border="0" cellpadding="0" cellspacing="0" width="100%">
						<tbody>
						{if $FIELD_PRIVILEGES[$tabid] neq ''}
							<tr>
							{if $modulename eq 'Calendar'}
								<td class="small colHeader" colspan="15" valign="top">{$CMOD.LBL_FIELDS_TO_BE_SHOWN} ({$APP.Tasks})</td>
							{else}
								<td class="small colHeader" colspan="15" valign="top">{$CMOD.LBL_FIELDS_TO_BE_SHOWN}</td>
							{/if}
					        </tr>
						{/if}
						{foreach name=field_privileges_rows item=row_values from=$FIELD_PRIVILEGES[$tabid]}
							<tr>
							{foreach name=field_privileges_groups item=element from=$row_values}
								{if $smarty.foreach.field_privileges_rows.iteration eq 1}
									<td rowspan="{$smarty.foreach.field_privileges_rows.total}" width="2%" align="center" style="background-color:#E8E8E8;" title="{'LBL_PROFILE_FIELD_VISIBLE'|@getTranslatedString:'Settings'}">
										{assign var="STR" value='LBL_PROFILE_FIELD_VISIBLE'|@getTranslatedString:'Settings'|strtoupper|str_split}
										{foreach from=$STR key=k item=char}
											{if $k <= $smarty.foreach.field_privileges_rows.total}
												<p>{$char}</p>
											{/if}
										{/foreach}
									</td>
								{/if}
								<td width="2%" style="background-color:#E8E8E8;" title="{'LBL_PROFILE_FIELD_VISIBLE'|@getTranslatedString:'Settings'}">{$element.0}</td>
								{if $smarty.foreach.field_privileges_rows.iteration eq 1}
									<td rowspan="{$smarty.foreach.field_privileges_rows.total}" width="2%" align="center" style="background-color:#EFD5D5;" title="{'LBL_PROFILE_FIELD_MANDATORY'|@getTranslatedString:'Settings'}">
										{assign var="STR" value='LBL_PROFILE_FIELD_MANDATORY'|@getTranslatedString:'Settings'|strtoupper|str_split}
										{foreach from=$STR key=k item=char}
											{if $k <= $smarty.foreach.field_privileges_rows.total}
												<p>{$char}</p>
											{/if}
										{/foreach}
									</td>
								{/if}
								<td width="2%" style="background-color:#EFD5D5;" title="{'LBL_PROFILE_FIELD_MANDATORY'|@getTranslatedString:'Settings'}">{$element.1}</td>
								<td width="25%" style="padding:5px;">{$element.2}</td>
							{/foreach}
							</tr>
						{/foreach}
						{if $modulename eq 'Calendar'}
							<tr>
								<td class="small colHeader" colspan="15" valign="top">{$CMOD.LBL_FIELDS_TO_BE_SHOWN} ({$APP.Events})</td>
							</tr>
							{foreach name=field_privileges_rows item=row_values from=$FIELD_PRIVILEGES[16]}
								<tr>
								{foreach name=field_privileges_groups item=element from=$row_values}
									{if $smarty.foreach.field_privileges_rows.iteration eq 1}
										<td rowspan="{$smarty.foreach.field_privileges_rows.total}" width="2%" align="center" style="background-color:#E8E8E8;" title="{'LBL_PROFILE_FIELD_VISIBLE'|@getTranslatedString:'Settings'}">
											{assign var="STR" value='LBL_PROFILE_FIELD_VISIBLE'|@getTranslatedString:'Settings'|strtoupper|str_split}
											{foreach from=$STR key=k item=char}
												{if $k <= $smarty.foreach.field_privileges_rows.total}
													<p>{$char}</p>
												{/if}
											{/foreach}
										</td>
									{/if}
									<td width="2%" style="background-color:#E8E8E8;" title="{'LBL_PROFILE_FIELD_VISIBLE'|@getTranslatedString:'Settings'}">{$element.0}</td>
									{if $smarty.foreach.field_privileges_rows.iteration eq 1}
										<td rowspan="{$smarty.foreach.field_privileges_rows.total}" width="2%" align="center" style="background-color:#EFD5D5;" title="{'LBL_PROFILE_FIELD_MANDATORY'|@getTranslatedString:'Settings'}">
											{assign var="STR" value='LBL_PROFILE_FIELD_MANDATORY'|@getTranslatedString:'Settings'|strtoupper|str_split}
											{foreach from=$STR key=k item=char}
												{if $k <= $smarty.foreach.field_privileges_rows.total}
													<p>{$char}</p>
												{/if}
											{/foreach}
										</td>
									{/if}
									<td width="2%" style="background-color:#EFD5D5;" title="{'LBL_PROFILE_FIELD_MANDATORY'|@getTranslatedString:'Settings'}">{$element.1}</td>
									<td width="25%" style="padding:5px;">{$element.2}</td>
								{/foreach}
								</tr>
							{/foreach}
						{/if}
						{if $UTILITIES_PRIV[$tabid] neq ''}
							<tr>
								<td colspan="15" class="small colHeader" valign="top">{$CMOD.LBL_TOOLS_TO_BE_SHOWN}</td>
							</tr>
						{/if}
						{foreach item=util_value from=$UTILITIES_PRIV[$tabid]}
							<tr>
							{foreach item=util_elements from=$util_value}
			              		<td colspan="4" align="right">{$util_elements.1}</td>
				                <td valign="top" style="padding:5px;">{$APP[$util_elements.0]}</td>
							{/foreach}
							</tr>
						{/foreach}
						</tbody>
						</table>
					</td>
				</tr>
				{* crmv@49510e *}
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
		      	        <td colspan="2" rowspan="2" class="small colHeader"><strong>{$CMOD.LBL_TAB_MESG_OPTION} </strong><strong></strong></td>
		      	        <td colspan="5" class="small colHeader"><div align="center"><strong> {$CMOD.LBL_EDIT_PERMISSIONS} </strong></div></td>
	      	      	</tr>
					<tr id="gva">
		      	        <td class="small colHeader" colspan="2"><div align="center"><strong>{$CMOD.PROJECT_ADMIN}</strong></div></td>
		      	        <td class="small colHeader" colspan="3"> <div align="center"><strong>{$CMOD.PROJECT_LEADER}</strong></div></td>
		      	    </tr>
					<tr>
		                <td class="small cellLabel" width="3%">
		                  <div align="right">
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
			<tr><td>
				{if $ACTION eq 'SaveProfile'}
					<input type="submit" value=" {$CMOD.LBL_FINISH_BUTTON} " name="save" class="crmButton create small" title="{$CMOD.LBL_FINISH_BUTTON}"/>&nbsp;&nbsp;
				{else}
					<input type="submit" value=" {$APP.LBL_SAVE_BUTTON_LABEL} " name="save" class="crmButton small save" title="{$APP.LBL_SAVE_BUTTON_LABEL}" />&nbsp;&nbsp;
				{/if}
				</td><td>
					<input type="button" value=" {$APP.LBL_CANCEL_BUTTON_LABEL} " name="Cancel" class="crmButton cancel small"onClick="window.history.back();" title="{$APP.LBL_CANCEL_BUTTON_LABEL}" /></td>

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
	{include file="Settings/ScrollTop.tpl"}

	</td>
	</tr>
	</tbody></table>
	<input type="hidden" name="form_var_count" id="form_var_count" value="0" /> {* crmv@111926 *}
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
<script language="javascript" type="text/javascript">
{literal}
var Imagid_array = new Array('img_2','img_4','img_6','img_7','img_8','img_9','img_10','img_13','img_14','img_18','img_19','img_20','img_21','img_22','img_23','img_26')

// crmv@192033 - remove duplication function

function invokeview_all()
{
	if(document.getElementById('view_all_chk').checked == true)
	{
		for(var i = 0;i < document.profileform.elements.length;i++)
		{
			if(document.profileform.elements[i].type == 'checkbox')
			{
				if(document.profileform.elements[i].id.indexOf('tab_chk_com_') != -1 || document.profileform.elements[i].id.indexOf('tab_chk_4') != -1 || document.profileform.elements[i].id.indexOf('_field_') != -1)
					document.profileform.elements[i].checked = true;
			}
		}
		showAllImages();
	}
}
function showAllImages()
{
	for(var j=0;j < Imagid_array.length;j++)
	{

		jQuery('#'+Imagid_array[j]).show();
	}
}
function invokeedit_all()
{
//crmv@192033
	if(document.getElementById('edit_all_chk').checked == true)
	{
		document.getElementById('view_all_chk').checked = true;
		for(var i = 0;i < document.profileform.elements.length;i++)
		{
			if(document.profileform.elements[i].type == 'checkbox')
			{
				if(document.profileform.elements[i].id.indexOf('tab_chk_com_') != -1 || document.profileform.elements[i].id.indexOf('tab_chk_4') != -1 || document.profileform.elements[i].id.indexOf('tab_chk_1') != -1 || document.profileform.elements[i].id.indexOf('_field_') != -1)
					document.profileform.elements[i].checked = true;
			}
		}
		showAllImages();
	}

}
function unselect_edit_all()
{
	document.getElementById('edit_all_chk').checked = false;
}
function unselect_view_all()
{
	document.getElementById('view_all_chk').checked = false;
}
function unSelectView(id)
{
	var createid = 'tab_chk_1_'+id;
	var deleteid = 'tab_chk_2_'+id;
	var tab_id = 'tab_chk_com_'+id;
	if(document.getElementById('tab_chk_4_'+id).checked == false)
	{
		unselect_view_all();
		unselect_edit_all();
		document.getElementById(createid).checked = false;
		document.getElementById(deleteid).checked = false;
		document.getElementById(tab_id).checked = false;
	}else
	{
		var imageid = 'img_'+id;
		var viewid = 'tab_chk_4_'+id;
		if(typeof(document.getElementById(imageid)) != 'undefined')
			document.getElementById(imageid).style.display = 'block';
		document.getElementById('tab_chk_com_'+id).checked = true;
	}
}
function unSelectCreate(id)
{
	var viewid = 'tab_chk_4_'+id;
	if(document.getElementById('tab_chk_1_'+id).checked == false)
	{
		unselect_edit_all();
	}else
	{
		var imageid = 'img_'+id;
		var viewid = 'tab_chk_4_'+id;
		if(typeof(document.getElementById(imageid)) != 'undefined')
			document.getElementById(imageid).style.display = 'block';
		document.getElementById('tab_chk_com_'+id).checked = true;
		document.getElementById(viewid).checked = true;
	}
}
function unSelectDelete(id)
{
	var contid = id+'_view';
	if(document.getElementById('tab_chk_2_'+id).checked == false)
	{
	}else
	{
		var imageid = 'img_'+id;
		var viewid = 'tab_chk_4_'+id;
		if(typeof(document.getElementById(imageid)) != 'undefined')
			document.getElementById(imageid).style.display = 'block';
		document.getElementById('tab_chk_com_'+id).checked = true;
		document.getElementById(viewid).checked = true;
	}

}
function hideTab(id)
{
	var createid = 'tab_chk_1_'+id;
	var viewid = 'tab_chk_4_'+id;
	var deleteid = 'tab_chk_2_'+id;
	var imageid = 'img_'+id;
	var contid = id+'_view';
	if(document.getElementById('tab_chk_com_'+id).checked == false)
	{
		unselect_view_all();
		unselect_edit_all();
		if(typeof(document.getElementById(imageid)) != 'undefined')
			document.getElementById(imageid).style.display = 'none';
		document.getElementById(contid).className = 'hideTable';
		if(typeof(document.getElementById(createid)) != 'undefined')
			document.getElementById(createid).checked = false;
		if(typeof(document.getElementById(deleteid)) != 'undefined')
			document.getElementById(deleteid).checked = false;
		if(typeof(document.getElementById(viewid)) != 'undefined')
			document.getElementById(viewid).checked = false;
	}else
	{
		if(typeof(document.getElementById(imageid)) != 'undefined')
			document.getElementById(imageid).style.display = 'block';
		if(typeof(document.getElementById(createid)) != 'undefined')
			document.getElementById(createid).checked = true;
		if(typeof(document.getElementById(deleteid)) != 'undefined')
			document.getElementById(deleteid).checked = true;
		if(typeof(document.getElementById(viewid)) != 'undefined')
			document.getElementById(viewid).checked = true;
		var fieldid = id +'_field_';
		for(var i = 0;i < document.profileform.elements.length;i++)
                {
                        if(document.profileform.elements[i].type == 'checkbox' && document.profileform.elements[i].id.indexOf(fieldid) != -1)
                        {
                                        document.profileform.elements[i].checked = true;
                        }
                }
	}
}
function initialiseprofile()
{
	var module_array = Array(1,2,4,6,7,8,9,10,13,14,15,17,18,19,20,21,22,23,24,25,26,27);
	for (var i=0;i < module_array.length;i++)
	{
		hideTab(module_array[i]);
	}
}
//initialiseprofile();
//crmv@49510
function selectUnselect(module,field) {
	if (!jQuery('#'+module+'_field_'+field).prop('checked')) {
		unselect_view_all();
		unselect_edit_all();
	}
	if (!jQuery('#'+module+'_field_'+field).prop('checked') && jQuery('#'+module+'_fieldm_'+field).prop('checked')) {
		jQuery('#'+module+'_fieldm_'+field).prop('checked',false);
	}
}
function selectMandatory(module,field) {
	if (jQuery('#'+module+'_fieldm_'+field).prop('checked') && !jQuery('#'+module+'_field_'+field).prop('checked')) {
		jQuery('#'+module+'_field_'+field).prop('checked',true);
	}
}
//crmv@49510e crmv@192033e
{/literal}
</script>