{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@104853 *}

<script type="text/javascript" src="include/js/smoothscroll.js"></script>
<script type="text/javascript" src="modules/CustomView/CustomView.js"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- crmv@30683 -->
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">
				<!-- crmv@30683 -->
					{include file="SetMenu.tpl"} {include file='Buttons_List.tpl'} {* crmv@30683 *}
					<!-- DISPLAY -->
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
						<tr>
							<td width=50 rowspan=2 valign=top>
								<img src="{'ico-adv_rule.gif'|resourcever}" alt="{$MOD.LBL_USERS}" width="48" height="48" border=0 title="{$MOD.LBL_USERS}">
							</td>
							<td class=heading2 valign=bottom>
								<b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_ADV_RULE} </b>
							</td>
							<!-- crmv@30683 -->
							<td rowspan=2 class="small" align=right>&nbsp;</td>
						</tr>
						<tr>
							<td valign=top class="small">{$MOD.LBL_ADV_RULE_DESCRIPTION}</td>
						</tr>
					</table>

					<br>
					<br>
					<br>

					<!-- Custom Access Module Display Table -->
					
						<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
							<tr>
								<td class="big">
									<strong>{$CMOD.LBL_CUSTOM_ADV_ACCESS_PRIVILEGES}</strong>
								</td>
								<td class="small" align=right>&nbsp;</td>
							</tr>
						</table>

						{foreach  key=modulename item=details from=$MODSHARING}
							{assign var="MODULELABEL" value=$modulename|@getTranslatedString:$modulename}	<!-- crmv@16886 -->
							{assign var="mod_display" value=$MODULELABEL}
				
							{if $details.0 neq ''}
							<table width="100%" border="0" cellpadding="5" cellspacing="0" class="listTableTopButtons">
			                  		<tr>
					                    <td  style="padding-left:5px;" class="big"><i class="vteicon md-text nohover">arrow_forward</i>&nbsp; <b>{$mod_display}</b>&nbsp; </td>
			                		    <td align="right">
								<input class="crmButton small save" type="button" name="Create" value="{$CMOD.LBL_ADV_ADD_RULE_BUTTON}" onClick="callEditDiv(this,'{$modulename}','create','')">
							    </td>
			                  		</tr>
						  	</table>
							<table width="100%" cellpadding="5" cellspacing="0" class="table">
	                    		<thead>
		                    		<tr>
			                    		<th width="7%" class="" nowrap>{$CMOD.LBL_RULE_NO}</th>
			                          	<th width="30%" class="" nowrap>{$CMOD.ADV_RULE_TITLE}</th>
			                          	<th width="50%" class="" nowrap>{$CMOD.ADV_RULE_DESC}</th>
			                          	<th width="13%" class="" nowrap>{$APP.Tools}</th>
		                        	</tr>
	                        	</thead>
                        		<tr>
			  					{foreach key=sno item=elements from=$details}
		                          <td class="">{$sno+1}</td>
		                          <td class="">{$elements.1}</td>
		                          <td class="">{$elements.2}</td>
		                          <td align="" class="">
									<a href="javascript:void(0);" onClick="callEditDiv(this,'{$modulename}','edit','{$elements.0}')"><i class="vteicon" title='edit'>create</i></a>
									<a href='javascript:confirmdelete("index.php?module=Settings&amp;action=DeleteAdvSharingRule&amp;shareid={$elements.0}")'><i class="vteicon" title='del'>delete</i></a>
								  </td>
		                        </tr>
                    	 		{/foreach} 
                    		</table>
                    		
							<!-- End of Module Display -->
							<!-- Start FOR NO DATA -->

							<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
								<tr><td>&nbsp;</td></tr>
							</table>
	   					{else}
                    		<table width="100%" cellpadding="0" cellspacing="0" class="table"><tr><td>
		      					<table width="100%" border="0" cellpadding="5" cellspacing="0" class="listTableTopButtons">
                      				<tr>
                        				<td style="padding-left:5px;" class="big"><i class="vteicon nohover md-text">arrow_forward</i>&nbsp; <b>{$mod_display}</b>&nbsp; </td>
                        				<td align="right">
											<input class="crmButton small save" type="button" name="Create" value="{$CMOD.LBL_ADV_ADD_RULE_BUTTON}" onClick="callEditDiv(this,'{$modulename}','create','')">
										</td>
									</tr>
								<table width="100%" cellpadding="5" cellspacing="0">
									<tr>
										<td colspan="2" style="padding:20px;" align="center" class="small">
											{$CMOD.LBL_CUSTOM_ACCESS_MESG} 
											<a href="javascript:void(0);" onClick="callEditDiv(this,'{$modulename}','create','')">{$CMOD.LNK_CLICK_HERE}</a>
											{$CMOD.LBL_CREATE_RULE_MESG}
										</td>
									</tr>
								</table>
							</table>	
							<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
								<tr><td>&nbsp;</td></tr>
							</table>
						{/if}
					{/foreach}			
					</td></tr></table>
	  	 		
				</td></tr></table>
				
				</td></tr></table>
		
				</td></tr></table>
		
			</td>
			<td valign="top"></td>
		</tr>
	</tbody>
</table>

{assign var="FLOAT_TITLE" value=""} 
{assign var="FLOAT_WIDTH" value="600px"} 
{assign var="FLOAT_CONTENT" value=""} 
{include file="FloatingDiv.tpl" FLOAT_ID="tempdiv" FLOAT_MAX_WIDTH="600px"}

{literal}
<script type="text/javascript">

function callEditDiv(obj,modulename,mode,id) {
	jQuery("#status").show();
	
	jQuery.ajax({
		url: 'index.php?module=Settings&action=SettingsAjax&orgajaxadv=true&mode='+mode+'&sharing_module='+modulename+'&shareid='+id,
		method: 'POST',
		success: function(response) {
			jQuery("#status").hide();
			if (response.indexOf('FAILED') > -1) {
				var tmp = response.responseText.split('|##|');
				alert(tmp[1]);
			} else {
				jQuery("#tempdiv_div").html(response);
				jQuery("#tempdiv_div").height('400px');
				jQuery("#tempdiv_div").css('overflow-y', 'scroll');
				
				showFloatingDiv('tempdiv');
			}
		}
	});
}

function confirmdelete(url) {
	var areYouSure = "{/literal}{$APP.ARE_YOU_SURE}{literal}";
	if(confirm(areYouSure)) {
		document.location.href=url;
	}
}

</script>
{/literal}