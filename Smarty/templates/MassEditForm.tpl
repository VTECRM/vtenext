{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<table border=0 cellspacing=0 cellpadding=5 width=750 align=center> 
	<tr>
		<td class=small >		
		<!-- popup specific content fill in starts -->
	      <form name="EditView" id="massedit_form" action="index.php" onsubmit="VteJS_DialogBox.block();" method="POST"> {* //crmv@33995 *}
				<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
				{* <input id="idstring" value="{$IDS}" type="hidden" /> *}	{* crmv@27096 *}
				<table border=0 cellspacing=0 cellpadding=0 width=100% align=center bgcolor=white>
				<tr>
					<td colspan=4 valign="top">
						<div style='padding: 5px 0;'>
							<span class="helpmessagebox">{$APP.LBL_SELECT_FIELDS_TO_UDPATE_WITH_NEW_VALUE}</span>
						</div>
						<!-- Hidden Fields -->
						{include file='EditViewHidden.tpl'}
						{* <input type="hidden" name="massedit_recordids"> *}	{* crmv@27096 *}
						<input type="hidden" name="massedit_module">
						{* crmv@129238 - remove module input *}
						<input type="hidden" name="action" value="MassEditSave">
						<input type="hidden" name="use_workflow" value="true">	{* crmv@91571 *}
						<input type="hidden" name="enqueue" value="{$ENQUEUE}">	{* crmv@91571 *}
					</td>
				</tr>
					<tr>
						<td colspan=4>
							<table class="small" border="0" cellpadding="3" cellspacing="0" width="100%">
								<tbody><tr>
									<td class="dvtTabCache" style="width: 10px;" nowrap>&nbsp;</td>
									{* crmv@104568 *}
									{foreach name=block item=data from=$BLOCKS}
										{assign var="header" value=$data.label}
									    {if $smarty.foreach.block.index eq 0}
										    <td nowrap class="dvtSelectedCell" id="tab{$smarty.foreach.block.index}" onclick="massedit_togglediv({$smarty.foreach.block.index},{$BLOCKS|@count});">
										     <b>{$header}</b>
										    </td>
									    {else}
										    <td nowrap class="dvtUnSelectedCell" id="tab{$smarty.foreach.block.index}" onclick="massedit_togglediv({$smarty.foreach.block.index},{$BLOCKS|@count});">
										     <b>{$header}</b>
										    </td>
									    {/if}
									{/foreach}
									{* crmv@104568 *}
						    		<td class="dvtTabCache" nowrap style="width:55%;">&nbsp;</td>
							    </tr>
								</tbody>
						    </table>		
						</td>
					</tr>
					<tr>
						<td colspan=4>
							{* crmv@104568 *}
							{foreach name=block item=data from=$BLOCKS}
								{assign var="header" value=$data.label}
							    {if $smarty.foreach.block.index eq 0}
							    	{assign var="display" value="block"}
							    {else}
							    	{assign var="display" value="none"}
							    {/if}
							    <div id="massedit_div{$smarty.foreach.block.index}" style='display:{$display};height:400px;overflow-x:hidden;'>
								<table border=0 cellspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
									{include file="DisplayFields.tpl" data=$data.fields}
								</table>
								</div>
							{/foreach}
							{* crmv@104568e *}
						</td>
					</tr>
				</table>
			</form>
		</td>
	</tr>
</table>
<script type="text/javascript" id="massedit_javascript">
var mass_fieldname = new Array({$VALIDATION_DATA_FIELDNAME});
var mass_fieldlabel = new Array({$VALIDATION_DATA_FIELDLABEL});
var mass_fielddatatype = new Array({$VALIDATION_DATA_FIELDDATATYPE});
var mass_fielduitype = new Array({$VALIDATION_DATA_FIELDUITYPE}); // crmv@83877
var mass_fieldwstype = new Array({$VALIDATION_DATA_FIELDWSTYPE}); //crmv@112297
var mass_count=0;
massedit_initOnChangeHandlers(); 
</script>