{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@55198 crmv@152701 *}

{include file='Buttons_List1.tpl'}

<table class="small" border="0" cellpadding="0" cellspacing="0" width="100%">
<tbody>
   <!-- tr>
	<td colspan="3">
		<table border=0 cellspacing=0 cellpadding=0 width=100% class="mailClientWriteEmailHeader level2Bg menuSeparation">
		<tr>
			<td>{$MOD.LBL_DETAILVIEW_FAX}</td>
		</tr>
		</table>
	</td>
   </tr --> 
   {foreach item=field from=$BLOCKS.fields}
   {foreach item=elements from=$field}
   {if $elements.fldname eq 'subject'}	
	<tr>
	<td class="mailSubHeader" width="15%" style="padding: 5px;" align="right">{$MOD.LBL_TO}</td>
	<td class="dvtCellLabel" style="padding: 5px;">&nbsp;{$TO_FAX}</td>
	<td class="dvtCellLabel" width="20%" rowspan="4"><div id="attach_cont_fax" class="addEventInnerBox" style="overflow:auto;height:140px;width:100%;position:relative;left:0px;top:0px;"></td>
   </tr>
	<td class="mailSubHeader" style="padding: 5px;" align="right">{$MOD.LBL_SUBJECT}</td>
	<td class="dvtCellLabel" style="padding: 5px;">&nbsp;{$elements.value}</td>
   </tr>
   <tr>
	<td colspan="3" class="dvtCellLabel" style="padding: 10px;" align="center"><input type="button" name="forward" value=" {$MOD.LBL_FORWARD_BUTTON} " alt="{$MOD.LBL_FORWARD_BUTTON}" title="{$MOD.LBL_FORWARD_BUTTON}" class="crmbutton small edit" onClick="parent.OpenComposeFax('{$ID}','forward')">&nbsp;
	<input type="button" title="{$APP.LBL_EDIT}" alt="{$APP.LBL_EDIT}" name="edit" value=" {$APP.LBL_EDIT} " class="crmbutton small edit" onClick="parent.OpenComposeFax('{$ID}','edit')">&nbsp;
	&nbsp;</td>
   </tr>
   {elseif $elements.fldname eq 'description'}
   <tr>
	<td style="padding: 5px;" colspan="3" valign="top"><div style="overflow:auto;height:415px;width:100%;">{$elements.value}</div></td>

   </tr>
   {elseif $elements.fldname eq 'filename'}
   <tr><td colspan="3">
   	<div id="attach_temp_cont_fax" style="display:none;">
		<table class="small" width="100% ">
		{foreach item=attachments from=$elements.options}
			<tr><td width="90%">{$attachments}</td></tr>	
		{/foreach}	
		</table>	
	</div>	
   </td></tr>	
   {/if}	
   {/foreach}
   {/foreach}

</table>		
<script>
jQuery('#attach_cont_fax').html(jQuery('#attach_temp_cont_fax').html()); // crmv@192033
jQuery(document).ready(function() {
	loadedPopup();
);
</script>