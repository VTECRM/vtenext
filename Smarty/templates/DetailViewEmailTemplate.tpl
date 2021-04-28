{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
	<div align=center>
	
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'} {* crmv@30683 *} 
				<!-- DISPLAY -->
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
		    		<form method="post" action="index.php" name="etemplatedetailview" onsubmit="VteJS_DialogBox.block();">
				<input type="hidden" name="action" value="editemailtemplate">
				<input type="hidden" name="module" value="Settings">
				<input type="hidden" name="templatename" value="{$TEMPLATENAME}">
				<input type="hidden" name="templateid" value="{$TEMPLATEID}">
				<input type="hidden" name="foldername" value="{$FOLDERNAME}">
				<input type="hidden" name="parenttab" value="{$PARENTTAB}">
				<input type="hidden" name="duplicate" value=""> {*//crmv@36773*}
				<tr>
					<td width=50 rowspan=2 valign=top><img src="{'ViewTemplate.gif'|resourcever}" border=0 ></td>
					<td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > <a href="index.php?module=Settings&action=listemailtemplates&parenttab=Settings">{$UMOD.LBL_EMAIL_TEMPLATES}</a> &gt; {$MOD.LBL_VIEWING} &quot;{$TEMPLATENAME}&quot; </b></td> <!-- crmv@30683 -->
				</tr>
				<tr>
					<td valign=top class="small">{$UMOD.LBL_EMAIL_TEMPLATE_DESC}</td>
				</tr>
				</table>
				
				<br>
				<table border=0 cellspacing=0 cellpadding=10 width=100% >
				<tr>
				<td>
				
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
					<tr>
						<td class="big"><strong>{$UMOD.LBL_PROPERTIES} &quot;{$TEMPLATENAME}&quot; </strong></td>
						<td class="small" align=right>						  &nbsp;&nbsp;
						  <input class="crmButton edit small" type="submit" name="Button" value="{$APP.LBL_DUPLICATE_BUTTON_LABEL}" onclick="this.form.duplicate.value='true';this.form.action.value='editemailtemplate'; this.form.parenttab.value='Settings'">&nbsp;&nbsp; {*//crmv@36773*}						
						  <input class="crmButton edit small" type="submit" name="Button" value="{$APP.LBL_EDIT_BUTTON_LABEL}" onclick="this.form.action.value='editemailtemplate'; this.form.parenttab.value='Settings'">&nbsp;&nbsp;
						</td>
					</tr>
					</table>
					
					{include file='PreviewEmailTemplate.tpl'}	{* crmv@80155 *}
					
					<br>
					{include file="Settings/ScrollTop.tpl"}
				</td>
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