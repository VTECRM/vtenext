{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script> 

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody>
  <tr>
      <td valign="top"></td>
      <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
  <form action="index.php" method="post" id="form">
  <input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
  <input type='hidden' name='module' value='Users'>
  <input type='hidden' name='action' value='DefModuleView'>
  <input type='hidden' name='return_action' value='ListView'>
  <input type='hidden' name='return_module' value='Users'>
  <input type='hidden' name='parenttab' value='Settings'>
  <input type='hidden' name='mode' value='{$MODE}'>

      <div align=center>
  			{include file='SetMenu.tpl'}
  				<!-- DISPLAY -->
  				{if $MODE eq "quotes"}
      				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
      				<tr>
      					<td width=50 rowspan=2 valign=top><img src="{'settingsInvNumber.gif'|resourcever}" alt="{$MOD.LBL_CUSTOMIZE_QUOTES_NUMBER}" width="48" height="48" border=0 title="{$MOD.LBL_CUSTOMIZE_QUOTES_NUMBER}"></td>
      					<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_CUSTOMIZE_QUOTES_NUMBER}</b></td> <!-- crmv@30683 -->
      				</tr>
      				<tr><td valign=top class="small">{$MOD.LBL_CUSTOMIZE_QUOTES_NUMBER_DESCRIPTION}</td></tr>
      				</table>
  				{else}
      				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
      				<tr>
      					<td width=50 rowspan=2 valign=top><img src="{'settingsInvNumber.gif'|resourcever}" alt="{$MOD.LBL_CUSTOMIZE_SALES_ORDER_NUMBER}" width="48" height="48" border=0 title="{$MOD.LBL_CUSTOMIZE_SALES_ORDER_NUMBER}"></td>
      					<td class=heading2 valign=bottom><b><a href="index.php?module=Administration&action=index&parenttab=Settings">{$MOD.LBL_SETTINGS}</a> > {$MOD.LBL_CUSTOMIZE_SALES_ORDER_NUMBER}</b></td>
      				</tr>
      				<tr><td valign=top class="small">{$MOD.LBL_CUSTOMIZE_SALES_ORDER_NUMBER_DESCRIPTION}</td></tr>
      				</table>
  				{/if}
  				
  				<br>
  				<table border=0 cellspacing=0 cellpadding=10 width=100% >
  				<tr>
  				<td>				
  				<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
  				<tr>
  				{if $MODE eq "quotes"}
  				<td class="big" height="40px;" width="70%"><strong>{$MOD.LBL_CUSTOM_QUOTES_NUMBER_VIEW}</strong></td>
  				{else}
  				<td class="big" height="40px;" width="70%"><strong>{$MOD.LBL_CUSTOM_SALES_ORDER_NUMBER_VIEW}</strong></td>
  				{/if}
         
          <td class="small" align="center" width="30%">&nbsp;
  					<span id="view_info" class="crmButton small cancel" style="display:none;">Successfully Updated.</span>
  				</td>
  				</tr>
  				</table>
  		
  							<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
  			<tr>
  	         	    <td class="small" valign=top ><table width="100%"  border="0" cellspacing="0" cellpadding="5">
                          <tr>
                              <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_CUSTOM_ALL_STRING}</strong></td>
                              <td width="80%" class="small cellText">
  <input type="text" id="string" name="string" class="small" style="width:30%" value="{$str}" onkeyup="preview();"/>
  			</td>
                          </tr>
                          <tr>
      {if $MODE eq "quotes"}
  		   <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_CUSTOM_QUOTES_NUMBER}</strong>
  		{else}
  			 <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_CUSTOM_SALES_ORDER_NUMBER}</strong>
  		{/if}
  		
      </td>
                  <td width="80%" class="small cellText">
  <input type="text" id="number" name="number" class="small" style="width:30%" value="{$no}"  onkeyup="preview();"/>
  </td>
  			</tr>
  <tr>
                  <td width="20%" nowrap class="small cellLabel"><strong>{$MOD.LBL_INVOICE_NUMBER_PREVIEW}</strong>
                  </td>
                  <td width="80%" class="small cellText" id="preview" style="font-weight:bold">{$inv_str}{$inv_no}</td>
                          </tr>
  <tr>
                  <td width="20%" nowrap colspan="2" align ="center">
  
  {if $MODE eq "quotes"}
     <input type="button" name="Button" class="crmbutton small create" value="{$MOD.LBL_QUOTES_NUMBER_BUTTON}" onclick="validatefn1();" />
  {else}
     <input type="button" name="Button" class="crmbutton small create" value="{$MOD.LBL_SALES_ORDER_NUMBER_BUTTON}" onclick="validatefn1();" />
  {/if}
          </td>
                          </tr>                     
                          </table>
  	    </td>
                          </tr>
                          </table>	
  				{include file="Settings/ScrollTop.tpl"}
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
  </form>
  </table>
  
  {literal}
  <script>
  
function setinvoiceid(){
	var inv_no=document.getElementById("number").value;
	var inv_str=document.getElementById("string").value;

	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		{/literal}
		data: 'module=Users&action=UsersAjax&file=UpdateCustomNo&ajax=true&no='+inv_no+'&str='+inv_str+'&mode=configure&status='+status + '&mod={$MODE}',
		{literal}
		success: function(result) {
			if((result != '')) {
				alert(result)
			} else {
				jQuery('#view_info').show();
			}
			jQuery("#status").hide();
		}
	});
	setTimeout("hide('view_info')",3000);
}
  
  function preview(){
    document.getElementById("preview").innerHTML=(document.getElementById("string").value + document.getElementById("number").value);
  }
  
  function validatefn1(){
  var invnumber=document.getElementById("number").value;
  var invstring=document.getElementById("string").value;
  
  var iChars = "!@#$%^&*()+=[]\\\';,.{}|\":<>?";
  
            for (var i = 0; i < invstring.length; i++){
                 if (iChars.indexOf(invstring.charAt(i)) != -1)
                  {
                 alert (alert_arr.NO_SPECIAL_CHARS);
                 return false;
                  }
              }
  
  if (!emptyCheck("number","Number","any")) return false
  if (!emptyCheck("string","String","text")) return false
  if (!numValidate("number","Number","any")) return false 
  
  setinvoiceid();
  }
  
  </script>
{/literal}