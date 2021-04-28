{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@39110 *}
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="javascript" type="text/javascript">
{literal}
function dup_validation() {
	var rolename = jQuery('#rolename').val();
	var mode = getObj('mode').value;
	var roleid = getObj('roleid').value;

	if(mode == 'edit')
		var urlstring ="&mode="+mode+"&roleName="+rolename+"&roleid="+roleid;
	else
		var urlstring ="&roleName="+rolename;

	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Settings&action=SettingsAjax&file=SaveRole&ajax=true&dup_check=true'+urlstring,
		dataType: 'json',
		success: function(result) {
			if (result['success']) {
				document.newRoleForm.submit();
			} else {
				alert(result['message']);
			}
		}
	});
}

function validate() {
	formSelectColumnString();
	if( !emptyCheck("roleName", "Role Name", "text" ) )
		return false;

	if(document.newRoleForm.selectedColumnsString.value.replace(/^\s+/g, '').replace(/\s+$/g, '').length==0) {
		alert('{/literal}{$APP.ROLE_SHOULDHAVE_INFO}{literal}');
		return false;
	}
	dup_validation();
	return false;
}
{/literal}
</script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
	<td valign="top"></td>
	<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

		<div align=center>
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'} {* crmv@30683 *}

				<!-- DISPLAY -->
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
				{literal}
				<form name="newRoleForm" action="index.php" method="post" onSubmit="if(validate()) { VteJS_DialogBox.block();} else { return false;} ">
				{/literal}
				<input type="hidden" name="module" value="Settings">
				<input type="hidden" name="action" value="SaveRole">
				<input type="hidden" name="parenttab" value="Settings">
				<input type="hidden" name="returnaction" value="{$RETURN_ACTION}">
				<input type="hidden" name="roleid" value="{$ROLEID}">
				<input type="hidden" name="mode" value="{$MODE}">
				<input type="hidden" name="parent" value="{$PARENT}">
				<tr>
					<td width=50 rowspan=2 valign=top><img src="{'ico-roles.gif'|resourcever}" alt="{$CMOD.LBL_ROLES}" width="48" height="48" border=0 title="{$CMOD.LBL_ROLES}"></td>
					{if $MODE eq 'edit'}
					<td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > <a href="index.php?module=Settings&action=listroles&parenttab=Settings">{$CMOD.LBL_ROLES}</a> &gt; {$MOD.LBL_EDIT} &quot;{$ROLENAME}&quot; </b></td> <!-- crmv@30683 -->
					{else}
					<td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > <a href="index.php?module=Settings&action=listroles&parenttab=Settings">{$CMOD.LBL_ROLES}</a> &gt; {$CMOD.LBL_CREATE_NEW_ROLE}</b></td> <!-- crmv@30683 -->
					{/if}
				</tr>
				<tr>
					{if $MODE eq 'edit'}
					<td valign=top class="small">{$MOD.LBL_EDIT} {$CMOD.LBL_PROPERTIES} &quot;{$ROLENAME}&quot; {$MOD.LBL_LIST_CONTACT_ROLE}</td>
					{else}
					<td valign=top class="small">{$CMOD.LBL_NEW_ROLE}</td>
					{/if}
				</tr>
				</table>

				<br>
				<table border=0 cellspacing=0 cellpadding=10 width=100% >
				<tr>
				<td valign=top>

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
					<tr>
						{if $MODE eq 'edit'}
						<td class="big"><strong>{$CMOD.LBL_PROPERTIES} &quot;{$ROLENAME}&quot; </strong></td>
						{else}
						<td class="big"><strong>{$CMOD.LBL_NEW_ROLE}</strong></td>
						{/if}
						<td><div align="right">
							<button type="button" class="crmbutton small save" name="add" onClick="return validate()">  {$APP.LBL_SAVE_BUTTON_LABEL}  </button>

						<button type="button" class="crmbutton cancel small" name="cancel" onClick="window.history.back()">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
						</div></td>
					  </tr>
					</table>

					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
                      <tr class="small">
                        <td width="15%" class="small cellLabel"><font color="red">*</font><strong>{$CMOD.LBL_ROLE_NAME}</strong></td>
                        <td width="85%" class="cellText">
                        	<div class="dvtCellInfo">
                        		<input id="rolename" name="roleName" type="text" value="{$ROLENAME}" class="detailedViewTextBox">
                        	</div>
                        </td>
                      </tr>
                      <tr class="small">
                        <td class="small cellLabel"><strong>{$CMOD.LBL_REPORTS_TO}</strong></td>
                        <td class="cellText">
							<div class="dvtCellInfoOff">{$PARENTNAME}</div>
						</td>
                      </tr>
                      <tr class="small">
                        <td colspan="2" valign=top class="cellLabel"><strong>{$CMOD.LBL_PROFILE_M}</strong></td>
                      </tr>
                      <tr class="small">
                        <td colspan="2" valign=top class="cellText">
						<br>
						<table width="95%"  border="0" align="center" cellpadding="5" cellspacing="0">
                          <tr>
                            <td width="40%" valign=top class="cellBottomDotLinePlain small"><strong>{$CMOD.LBL_PROFILES_AVLBL}</strong></td>
                            <td width="10%">&nbsp;</td>
                            <td width="40%" class="cellBottomDotLinePlain small"><strong>{$CMOD.LBL_ASSIGN_PROFILES}</strong></td>
                          </tr>

						<tr class=small>
					               <td valign=top>{$CMOD.LBL_PROFILES_M} {$CMOD.LBL_MEMBER} <br>
							<select multiple id="availList" name="availList" class="small crmFormList notdropdown" size=10 >
							{foreach item=element from=$PROFILELISTS}
								<option value="{$element.0}">{$element.1}</option>
							{/foreach}
							</select>
							</td>
					        <td width="50"><div align="center">
								<input type="hidden" name="selectedColumnsString"/>
								<button name="Button" type="button" class="crmbutton small" style="width:100%" onClick="addColumn()">&nbsp;&rsaquo;&rsaquo;&nbsp;</button>
								<br><br>
								<button type="button" name="Button1" class="crmbutton small" onClick="delColumn()" style="width:100%">&nbsp;&lsaquo;&lsaquo;&nbsp;</button>
							  	<br><br>
							</div></td>
							<td class="small" style="background-color:#ddFFdd" valign=top>{$CMOD.LBL_MEMBER}{if !empty($ROLENAME)} {'LBL_OF'|getTranslatedString:'Settings'} &quot;{$ROLENAME}&quot;{/if}<br>
								<select multiple id="selectedColumns" name="selectedColumns" class="small crmFormList notdropdown" size=10 >
									{foreach item=element from=$SELPROFILELISTS}
										<option value="{$element.0}">{$element.1}</option>
									{/foreach}
								</select></td>
						</tr>
					</table>
					</td>
					</tr>

        			<tr class="small">
                        <td colspan="2" valign=top class="cellLabel"><strong>{$CMOD.LBL_PROFILE_M} (Mobile)</strong></td>
                    </tr>
                    <tr>
                    	<td colspan="2">
                    		&nbsp;&nbsp;&nbsp;
                    		<select id="profileMobileList" name="profileMobileList" class="small" {if count($PROFILELISTS_MOBILE) eq 0}disabled="disabled"{/if}>
                    			<option value="0">{$APP.LBL_NONE}</option>
                    		{foreach item=element from=$PROFILELISTS_MOBILE}
                    			{if $element.0 eq $SELPROFILE_MOBILE}
                    				<option value="{$element.0}" selected="">{$element.1}</option>
                    			{else}
                    				<option value="{$element.0}">{$element.1}</option>
                    			{/if}
                    		{/foreach}
                    		</select>
							{if count($PROFILELISTS_MOBILE) eq 0}
								&nbsp;<b>{$CMOD.LBL_ENABLE_ATLEAST_MOBILE_PROF}</b>
							{/if}
                    	</td>
                    </tr>

                        </table>

						</td>
                      </tr>
                    </table>
					<br>
					{include file="Settings/ScrollTop.tpl"}


				</td>
				</tr>
				<tr>
				  <td valign=top>&nbsp;</td>
				  </tr>
				</table>



			</td>
			</tr>
			</table>
		</td>
	</tr>
	</form>
	</table>

	</div>

</td>
        <td valign="top"></td>
   </tr>
</tbody>
</table>

<script language="JavaScript" type="text/JavaScript">
{literal}
        var moveupLinkObj,moveupDisabledObj,movedownLinkObj,movedownDisabledObj;
        function setObjects()
        {
            availListObj=getObj("availList")
            selectedColumnsObj=getObj("selectedColumns")

        }

        function addColumn()
        {
            for (i=0;i<selectedColumnsObj.length;i++)
            {
                selectedColumnsObj.options[i].selected=false
            }

            for (i=0;i<availListObj.length;i++)
            {
                if (availListObj.options[i].selected==true)
                {
                	var rowFound=false;
                	var existingObj=null;
                    for (j=0;j<selectedColumnsObj.length;j++)
                    {
                        if (selectedColumnsObj.options[j].value==availListObj.options[i].value)
                        {
                            rowFound=true
                            existingObj=selectedColumnsObj.options[j]
                            break
                        }
                    }

                    if (rowFound!=true)
                    {
                        var newColObj=document.createElement("OPTION")
                        newColObj.value=availListObj.options[i].value
                        if (browser_ie) newColObj.innerText=availListObj.options[i].innerText
                        else if (browser_nn4 || browser_nn6) newColObj.text=availListObj.options[i].text
                        selectedColumnsObj.appendChild(newColObj)
                        availListObj.options[i].selected=false
                        newColObj.selected=true
                        rowFound=false
                    }
                    else
                    {
                        if(existingObj != null) existingObj.selected=true
                    }
                }
            }
        }

        function delColumn()
        {
            for (i=selectedColumnsObj.options.length;i>0;i--)	//crmv@29195
            {
                if (selectedColumnsObj.options.selectedIndex>=0)
                selectedColumnsObj.remove(selectedColumnsObj.options.selectedIndex)
            }
        }

        function formSelectColumnString()
        {
            var selectedColStr = "";
            for (i=0;i<selectedColumnsObj.options.length;i++)
            {
                selectedColStr += selectedColumnsObj.options[i].value + ";";
            }
            document.newRoleForm.selectedColumnsString.value = selectedColStr;
        }
	setObjects();
{/literal}
</script>