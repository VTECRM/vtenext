{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/menu.js"|resourcever}"></script>
{literal}
<style>
DIV.fixedLay{
	border:3px solid #CCCCCC;
	background-color:#FFFFFF;
	width:500px;
	position:fixed;
	left:250px;
	top:200px;
	display:block;
}
</style>
{/literal}
{literal}
<!--[if lte IE 6]>
<STYLE type=text/css>
DIV.fixedLay {
	POSITION: absolute;
}
</STYLE>
<![endif]-->

{/literal}

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

	<div align=center>
	
			{include file='SetMenu.tpl'}

			<!-- DISPLAY -->
			<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
			<tr>
				<td width="50" rowspan="2" valign="top"><img src="{'inventory.gif'|resourcever}" alt="{$MOD.LBL_USERS}" width="48" height="48" border=0 title="{$MOD.LBL_USERS}"></td>
				<td colspan="2" class="heading2" valign=bottom align="left"><b>{$MOD.LBL_SETTINGS} > {$MOD.INVENTORYNOTIFICATION} </b></td> <!-- crmv@30683 -->
				<td rowspan=2 class="small" align=right>&nbsp;</td>
			</tr>
			<tr>
				<td valign=top class="small" align="left">{$MOD.LBL_INV_NOTIF_DESCRIPTION}</td>
			</tr>
			</table>
<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
						<tr><td>&nbsp;</td></tr>
				</table>

				<table width="100%" border="0" cellpadding="5" cellspacing="0" class="listTableTopButtons">
                  <tr >

                    <td  style="padding-left:5px;" class="big">{$MOD.INVENTORYNOTIFICATION}</td>
                    <td align="right">&nbsp;</td>
                  </tr>
			  </table>
	
	<div id="notifycontents">
	{include file='Settings/InventoryNotifyContents.tpl'}
	</div>
	
	{include file='Settings/ScrollTop.tpl'}
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
	<div id="editdiv" style="display:none;position:absolute;width:450px;"></div>
{literal}
<script>
// crmv@192033
function fetchSaveNotify(id)
{
	jQuery("#editdiv").hide();
	jQuery("#status").show();
	var subject = jQuery("#notifysubject").val();
	var body = jQuery("#notifybody").val();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=SettingsAjax&module=Settings&file=SaveInventoryNotification&notifysubject='+subject+'&notifybody='+body+'&record='+id,
		success: function(result) {
			jQuery("#status").hide();
		}
	});
}

function fetchEditNotify(id)
{
	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=SettingsAjax&module=Settings&file=EditInventoryNotification&record='+id,
		success: function(result) {
			jQuery("#status").hide();
			jQuery("#editdiv").html(result);
		}
	});
}
// crmv@192033e
</script>
{/literal}