{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@39110 crmv@105127 crmv@113771 *}
<script language="JavaScript" type="text/javascript" src="{"include/js/customview.js"|resourcever}"></script>

<form action="index.php" method="post" name="form" onsubmit="VteJS_DialogBox.block();">
	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	<input type="hidden" name="fld_module" value="{$MODULE}">
	<input type="hidden" name="panelid" value="{$PANELID}">
	<input type="hidden" name="module" value="Settings">
	<input type="hidden" name="parenttab" value="Settings">
	<input type="hidden" name="mode">
	
	{if !empty($TABS) && $FORMOBILE neq true}
		<br>
		<table border="0" cellspacing="0" cellpadding="3" width="100%" class="small" id="LayoutEditTabs">
			<tr>
			{foreach item=_tab from=$TABS name="extraDetailForeach"}
				{if ($PANELID > 0 && $_tab.panelid == $PANELID) || ($PANELID == 0 && $smarty.foreach.extraDetailForeach.iteration eq 1) }
					{assign var="_class" value="dvtSelectedCell"}
				{else}
					{assign var="_class" value="dvtUnSelectedCell"}
				{/if}
				<td class="tabCell {$_class}" align="center" nowrap="" data-panelid="{$_tab.panelid}">
					{if count($TABS) > 1}
					<i class="vteicon md-sm nohover valign-bottom tabHandle" style="cursor:move" title="{$APP.LBL_MOVE}">reorder</i>&nbsp;
					{/if}
					<a href="javascript:;" onClick="LayoutEditor.changeTab('{$_tab.panelid}')">{$_tab.label}</a>
					{if $_class == 'dvtSelectedCell'}
					&nbsp;&nbsp;<i class="vteicon md-link md-sm valign-bottom" title="{$APP.LBL_EDIT}" onclick="LayoutEditor.showEditTab('{$_tab.panelid}', '{$_tab.label}', this)">create</i>
					{/if}
				</td>
			{/foreach}
			<td class="dvtTabCache" align="right" nowrap="">
				<a href="javascript:;" onclick="LayoutEditor.showEditTab(0, '', this)">
					<i class="vteicon md-link" style="vertical-align:middle" title="{$MOD.LBL_ADD_TAB}">add</i>
					<span>{$MOD.LBL_ADD_TAB}</span>
				</a>
			</td>
			<td class="dvtTabCache" align="right" style="width:100%">

			<input type="button" class="crmButton create small" onclick="LayoutEditor.alignAddBlockList(); showFloatingDiv('addblock', this);" title="{$MOD.ADD_BLOCK}" value="{$MOD.ADD_BLOCK}"/>
			<input type="button" class="crmButton create small" onclick="showFloatingDiv('addrelated', this);" title="{$MOD.LBL_ADD_RELATEDLIST}" value="{$MOD.LBL_ADD_RELATEDLIST}"/>

			</td>
			</tr>
		</table>
		<br>
		{if count($TABS) > 1}
		<script type="text/javascript">
			{literal}
			var cont = jQuery('#LayoutEditTabs tr');
			cont.sortable({
				axis: 'x',
				items: "> .tabCell",
				handle: ".tabHandle",
				distance: 10,
				opacity: 0.8,
				update: function(event, ui) {
					LayoutEditor.saveTabOrder();
				}
			});
			{/literal}
		</script>
		{/if}
	{/if}
	
	
	{* tab create box *}
	{assign var="FLOAT_TITLE" value=$MOD.LBL_ADD_TAB}
	{assign var="FLOAT_WIDTH" value="350px"}
	{capture assign="FLOAT_CONTENT"}
		<input type="hidden" id="editTabPanelId" value="" />
		<table width="100%">
			<tr align="left">
				<td class="dataLabel" nowrap="nowrap" align="right" width="30%">
					<b>{$MOD.LBL_TAB_NAME} </b>
				</td>
				<td align="left" width="70%">
					<input id="newTabName"  value="" type="text" class="txtBox">
				</td>
			</tr>
			<tr>
				<td align="center" colspan="2">
					<input type="button" name="save" value="{$APP.LBL_CREATE_BUTTON_LABEL}" class="crmButton small save" onclick ="LayoutEditor.createTab()"/>
					<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" class="crmButton small cancel" onclick="hideFloatingDiv('createTab');" />
				</td>
			</tr>
		</table>
	{/capture}
	{include file="FloatingDiv.tpl" FLOAT_ID="createTab" FLOAT_BUTTONS=""}
	
	{* tab edit box *}
	{assign var="FLOAT_TITLE" value=$MOD.LBL_EDIT_TAB}
	{assign var="FLOAT_WIDTH" value="400px"}
	{capture assign="FLOAT_CONTENT"}
		<table width="100%">
			<tr align="left">
				<td class="dataLabel" nowrap="nowrap" align="right" width="30%">
					<b>{$MOD.LBL_TAB_NAME} </b>
				</td>
				<td align="left" width="70%">
					<input id="editTabName"  value="" type="text" class="txtBox">
				</td>
			</tr>
			<tr>
				<td align="center" colspan="2">
					<input type="button" name="save" value="{$APP.LBL_SAVE_BUTTON_LABEL}" class="crmButton small save" onclick ="LayoutEditor.editTab()"/>
					{if count($TABS) > 1}
					<input type="button" name="delete" value="{$MOD.LBL_DELETE}" class="crmButton small cancel" onclick ="LayoutEditor.preDeleteTab(this)"/>
					{/if}
					<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" class="crmButton small cancel" onclick="hideFloatingDiv('editTab');" />
				</td>
			</tr>
		</table>
	{/capture}
	{include file="FloatingDiv.tpl" FLOAT_ID="editTab" FLOAT_BUTTONS=""}
	
	{* choose where to move blocks box *}
	{assign var="FLOAT_TITLE" value="`$MOD.LBL_DELETE` tab"}
	{assign var="FLOAT_WIDTH" value="500px"}
	{capture assign="FLOAT_CONTENT"}
		<table width="100%">
			<tr align="left">
				<td class="dataLabel" align="right" width="70%">
					{$MOD.LBL_CHOOSE_TRANSFER_TAB} 
				</td>
				<td align="left" width="30%">
					<select id="delTabSelect">
						{foreach item=tab from=$TABS}
							{if $tab.panelid neq $PANELID}
							<option value="{$tab.panelid}">{$tab.label}</option>
							{/if}
						{/foreach}
					</select>
				</td>
			</tr>
			<tr>
				<td align="center" colspan="2">
					<input type="button" name="save" value="{$MOD.LBL_DELETE} tab" class="crmButton small delete" onclick ="LayoutEditor.deleteTab()"/>
					<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" class="crmButton small cancel" onclick="hideFloatingDiv('chooseTabForBlocks');" />
				</td>
			</tr>
		</table>
	{/capture}
	{include file="FloatingDiv.tpl" FLOAT_ID="chooseTabForBlocks" FLOAT_BUTTONS=""}
	
	{* choose where to move blocks box *}
	{assign var="FLOAT_TITLE" value=$MOD.LBL_MOVE_BLOCK}
	{assign var="FLOAT_WIDTH" value="500px"}
	{capture assign="FLOAT_CONTENT"}
		<input type="hidden" id="moveBlockTabId" value=""/>
		<table width="100%">
			<tr align="left">
				<td class="dataLabel" align="right" width="70%">
					{$MOD.LBL_CHOOSE_TRANSFER_TAB_BLOCK} 
				</td>
				<td align="left" width="30%">
					<select id="delTabSelectBlock">
						{foreach item=tab from=$TABS}
							{if $tab.panelid neq $PANELID}
							<option value="{$tab.panelid}">{$tab.label}</option>
							{/if}
						{/foreach}
					</select>
				</td>
			</tr>
			<tr>
				<td align="center" colspan="2">
					<input type="button" name="save" value="{$APP.LBL_MOVE}" class="crmButton small save" onclick ="LayoutEditor.moveBlock()"/>
					<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" class="crmButton small cancel" onclick="hideFloatingDiv('chooseTabForBlocks');" />
				</td>
			</tr>
		</table>
	{/capture}
	{include file="FloatingDiv.tpl" FLOAT_ID="chooseTabForBlock" FLOAT_BUTTONS=""}
	
		{foreach item=entries key=id from=$CFENTRIES name=outer}
			{if $entries.blocklabel neq '' }
				<div id="block_{$entries.blockid}" style="{if $FORMOBILE neq true && $entries.panelid != $PANELID}display:none{/if}">
				<table class="listTable" border="0" cellpadding="3" cellspacing="0" align="center" width="98%">
				
				<tr>
					<td class="colHeader small" colspan="2" width="50%">
					{if $FORMOBILE neq true}
					<select name="display_status_{$entries.blockid}" style="font-size:11px; width:auto" onChange="LayoutEditor.changeShowstatus('{$entries.tabid}','{$entries.blockid}','{$MODULE}')" id='display_status_{$entries.blockid}'>
                		    <option value="show" {if $entries.display_status==1}selected{/if}>{$MOD.LBL_Show}</option>
							<option value="hide" {if $entries.display_status==0}selected{/if}>{$MOD.LBL_Hide}</option>
							<!-- option value="disable" {if $entries.visible==1}selected{/if}>{$MOD.LBL_Disable}</option -->
							{if count($TABS) > 1}
							<option value="move">{$MOD.LBL_MOVE_TO_ANOTHER_TAB}</option>
							{/if}
					</select>
					&nbsp;&nbsp;
					{/if}
					{$entries.blocklabel}&nbsp;&nbsp;
	  				</td>
					<td class="small" colspan="2" align="right" width="50%">

						{if $entries.iscustom == 1 }
							<i class="vteicon md-link" onClick="LayoutEditor.deleteCustomBlock('{$MODULE}','{$entries.blockid}','{$entries.no}')" title="{$APP.LBL_DELETE}">delete</i>&nbsp;&nbsp;
						{/if}

						{* crmv@202080 *}

						{if $entries.can_show_hidden_field}
							<i class="vteicon md-link"  onclick="showFloatingDiv('hiddenfields_{$entries.blockid}', this);" title="{$MOD.HIDDEN_FIELDS}">visibility_off</i>&nbsp;&nbsp;
						
							{* Hidden Fields floating div *}
							{assign var="FLOAT_TITLE" value=$MOD.HIDDEN_FIELDS}
							{assign var="FLOAT_WIDTH" value="250px"}
							{assign var="FLOAT_BUTTONS" value=""}
							{capture assign="FLOAT_CONTENT"}
							<table>
								<tr>
									<td>
										{if $entries.hidden_count neq '0' || $entries.hidden_count neq null}
										{$APP.LBL_SELECT_FIELD_TO_MOVE}
										{/if}
									</td>
								</tr>
								<tr align="left">
									<td>
										{if $entries.hidden_count neq '0'}
										<select class="small notdropdown" id="hiddenfield_assignid_{$entries.blockid}" style="width:225px" size="10" multiple>
											{foreach name=inner item=value from=$entries.hiddenfield}
												<option value="{$value.fieldselect}">{$value.label}</option>
											{/foreach}
										</select>
										{else}
											{$MOD.NO_HIDDEN_FIELDS}
										{/if}
									</td>
								</tr>
								<tr>
									<td align="center">
										<input type="button" name="save" value="{$MOD.LBL_ASSIGN_BUTTON_LABEL}" class="crmButton small save" onclick="LayoutEditor.show_move_hiddenfields('{$MODULE}','{$entries.tabid}','{$entries.blockid}','showhiddenfields');"/>
										<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" class="crmButton small cancel" onclick="hideFloatingDiv('hiddenfields_{$entries.blockid}');" />
									</td>
								</tr>
							</table>
							{/capture}
							{include file="FloatingDiv.tpl" FLOAT_ID="hiddenfields_`$entries.blockid`"}
						{/if}

						{if $entries.can_add_field}
							<i class="vteicon md-link" onclick="showFloatingDiv('addfield_{$entries.blockid}', this);" title="{$MOD.Add_CustomField}">add</i>&nbsp;&nbsp;
						
							{* Add Fields floating div *}
							{include file="Settings/LayoutBlockAddFieldPopup.tpl"} {* crmv@158543 *}
						{/if}

						{if $entries.can_move_field}
							<i class="vteicon2 md-link fa-magnet fa-rotate-180" style="vertical-align:super" onClick="showFloatingDiv('movefields_{$entries.blockid}',this);" title="{$MOD.LBL_MOVE_FIELDS}"></i>&nbsp;&nbsp;
						
							{assign var="FLOAT_TITLE" value=$MOD.LBL_MOVE_FIELDS}
							{assign var="FLOAT_WIDTH" value="350px"}
							{assign var="FLOAT_BUTTONS" value=""}
							{capture assign="FLOAT_CONTENT"}
							<table width="100%" align="center">
								<tr>
									<td>{$MOD.LBL_SELECT_FIELD_TO_MOVE_IN_BLOCK}</td>
								</tr>
								<tr>
									<td align="center">
										<select class="small notdropdown"  id="movefield_assignid_{$entries.blockid}" style="width:225px" size="10" multiple>
										{foreach name=inner item=value from=$entries.movefield}
											<option value="{$value.fieldid}">{$value.fieldlabel}</option>	{* crmv@24862 *}
										{/foreach}
										</select>
									</td>
								</tr>
								<tr>
									<td align="center">
										<input type="button" name="save" value="{$MOD.LBL_ASSIGN_BUTTON_LABEL}" class="crmButton small save" onclick="LayoutEditor.show_move_hiddenfields('{$MODULE}','{$entries.tabid}','{$entries.blockid}','movehiddenfields');"/>
										<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" class="crmButton small cancel" onclick="hideFloatingDiv('movefields_{$entries.blockid}');" />
									</td>
								</tr>
							</table>
							{/capture}
							{include file="FloatingDiv.tpl" FLOAT_ID="movefields_`$entries.blockid`"}
						{/if}

						{* crmv@202080e *}

						{if $FORMOBILE neq true}
							{if $smarty.foreach.outer.first}
						 		<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;&nbsp;
								<i class="vteicon md-link" onclick="LayoutEditor.changeBlockorder('block_down','{$entries.tabid}','{$entries.blockid}','{$MODULE}') " title="{$MOD.DOWN}">arrow_downward</i>&nbsp;&nbsp;
							{elseif $smarty.foreach.outer.last}
								<i class="vteicon md-link" onclick="LayoutEditor.changeBlockorder('block_up','{$entries.tabid}','{$entries.blockid}','{$MODULE}') " title="{$MOD.DOWN}">arrow_upward</i>&nbsp;&nbsp;
								<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;&nbsp;
							{else}
						 		<i class="vteicon md-link" onclick="LayoutEditor.changeBlockorder('block_up','{$entries.tabid}','{$entries.blockid}','{$MODULE}') " title="{$MOD.DOWN}">arrow_upward</i>&nbsp;&nbsp;
						 		<i class="vteicon md-link" onclick="LayoutEditor.changeBlockorder('block_down','{$entries.tabid}','{$entries.blockid}','{$MODULE}') " title="{$MOD.DOWN}">arrow_downward</i>&nbsp;&nbsp;
							{/if}
						{/if}

					</td>
				</tr>
				<tr>
					{foreach name=inner item=value from=$entries.field}


						{if $value.no % 2 == 0 || $FORMOBILE}
					  		</tr>
					  		{assign var="rightcellclass" value=""}
					  		<tr>
					 	{else}
					 		{assign var="rightcellclass" value="class='rightCell'"}
					 	{/if}
					<td width="{if $FORMOBILE}60%{else}30%{/if}" id="colourButton" >&nbsp;
						<span>{$value.label}</span>
				 		{if $value.fieldtype eq 'M'}
				 			<font color='red'> *</font>
				 		{/if}
				 	</td>
					<td width="{if $FORMOBILE}30%{else}20%{/if}" align = "right" class="colData small" >
						{* crmv@106857 *}
						<i class="vteicon md-sm md-link" onclick="
						{if $value.uitype eq 220}
							MlTableFieldConfig.openAddTableFieldPopup('{$entries.blockid}','{$value.fieldselect}');
						{else}
							showFloatingDiv('editfield_{$value.fieldselect}', this);
						{/if}
						" title="{$MOD.LBL_EDIT_PROPERTIES}">mode_edit</i>&nbsp;&nbsp;
						{* crmv@106857e *}

						{assign var="FLOAT_TITLE" value=$value.label}
						{assign var="FLOAT_WIDTH" value="300px"}
						{assign var="FLOAT_BUTTONS" value=""}
						{capture assign="FLOAT_CONTENT"}
						<table width="100%" border="0" cellpadding="5" cellspacing="0" class="small">
							{if $FORMOBILE neq true}
							<tr>
								<td valign="top" class="dvtCellInfo" align="left" width="10px">
									<input id="mandatory_check_{$value.fieldselect}"  type="checkbox"
									{if $value.fieldtype neq 'M' && $value.mandatory eq '0'}
										 disabled
									{elseif $value.mandatory eq '0' && $value.fieldtype eq 'M'}
										checked  disabled
									{elseif $value.mandatory eq '3' }
										disabled
									{elseif $value.mandatory eq '2'}
									 	checked
									{/if}
									 onclick = "{if $value.presence neq '0'} enableDisableCheckBox(this,presence_check_{$value.fieldselect}); {/if}
									 			{if $value.quickcreate neq '0' && $value.quickcreate neq '3'} enableDisableCheckBox(this,quickcreate_check_{$value.fieldselect}); {/if}">
								</td>
								<td valign="top" class="dvtCellInfo" align="left">
									&nbsp;{$MOD.LBL_MANDATORY_FIELD}
								</td>
							</tr>
							{/if}
							<tr>
								<td valign="top" class="dvtCellInfo" align="left" width="10px">
									<input id="presence_check_{$value.fieldselect}"  type="checkbox"
									{if $value.presence eq '0' || $value.mandatory eq '0' || $value.quickcreate eq '0' || $value.mandatory eq '2'}
										checked  disabled
									{/if}
									{if $value.presence eq '2'}
									 	checked
									 {/if}
									  {if $value.presence eq '3'}
										disabled
									{/if}
									 >
								</td>
								<td valign="top" class="dvtCellInfo" align="left">
									&nbsp;{$MOD.LBL_ACTIVE}
								</td>
							</tr>
							{if $FORMOBILE neq true}
							<tr>
								<td valign="top" class="dvtCellInfo" align="left" width="10px">
									<input id="quickcreate_check_{$value.fieldselect}"  type="checkbox"
									{if $value.quickcreate eq '0'|| $value.quickcreate eq '2' && ($value.mandatory eq '0' || $value.mandatory eq '2')}
										checked  disabled
									{/if}
									{if $value.quickcreate eq '2'}
										checked
									{/if}
									{if $value.quickcreate eq '3'}
										disabled
									{/if}
									 >
								</td>
								<td valign="top" class="dvtCellInfo" align="left">
									&nbsp;{$MOD.LBL_QUICK_CREATE}
								</td>
							</tr>
							<tr>
								<td valign="top" class="dvtCellInfo" align="left" width="10px">
									<input id="massedit_check_{$value.fieldselect}"  type="checkbox"
									{if $value.massedit eq '0'}
										disabled
									{/if}
									{if $value.massedit eq '1'}
										checked
									{/if}
									{if $value.displaytype neq '1' || $value.massedit eq '3'}
										disabled
									{/if}>
								</td>
								<td valign="top" class="dvtCellInfo" align="left">
								&nbsp;{$MOD.LBL_MASS_EDIT}
								</td>
							</tr>
							{/if}
							{if $value.uitype eq 213 && !empty($value.info)}
								<tr>
									<td colspan="2" class="dvtCellInfo">
										<br>{$MOD.LBL_FIELD_BUTTON_ONCLICK}
										<input type="text" id="editOnclick_{$value.fieldselect}" class="detailedViewTextBox" value="{$value.info.onclick}"/>
									</td>
								</tr>
								<tr>
									<td colspan="2" class="dvtCellInfo">
										<br>{$MOD.LBL_FIELD_BUTTON_CODE}
										<textarea id="editCode_{$value.fieldselect}">{$value.info.code}</textarea>
									</td>
								</tr>
							{/if}
							{* crmv@101683 *}
							{if $value.uitype eq 50 && !empty($value.info)}
								<tr>
									<td colspan="2" class="dvtCellInfo">
										<br>{'Users'|getTranslatedString}
										<select MULTIPLE class="small notdropdown" style="width:100%; height:100px" size="10" id="editCustomUserPick_{$value.fieldselect}">
										{foreach item=arr from=$value.info}
											<option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
										{/foreach}
										</select>
									</td>
								</tr>
							{/if}
							{* crmv@101683e *}
							<tr>
								<td colspan="3" class="dvtCellInfo" align="center">
									<input  type="button" name="save"  value=" &nbsp; {$APP.LBL_SAVE_BUTTON_LABEL} &nbsp; " class="crmButton small save" onclick="LayoutEditor.saveFieldInfo('{$value.fieldselect}','{$MODULE}','updateFieldProperties');" />&nbsp;
									{if $value.customfieldflag && $FORMOBILE neq true}
										<input type="button" name="delete" value=" {$APP.LBL_DELETE_BUTTON_LABEL} " class="crmButton small delete" onclick="LayoutEditor.deleteCustomField('{$value.fieldselect}','{$MODULE}','{$value.columnname}','{$value.uitype}')" />
									{/if}
									<input  type="button" name="cancel" value=" {$APP.LBL_CANCEL_BUTTON_LABEL} " class="crmButton small cancel" onclick="hideFloatingDiv('editfield_{$value.fieldselect}');" />
								</td>
							</tr>
						</table>
						{/capture}
						{include file="FloatingDiv.tpl" FLOAT_ID="editfield_`$value.fieldselect`"}
						

				 		{if $FORMOBILE}
				 			{if $value.no != 0}
								<i class="vteicon md-link md-sm" onclick="LayoutEditor.changeFieldorder('up','{$value.fieldselect}','{$value.blockid}','{$MODULE}') " title="{$MOD.UP}">arrow_upward</i>&nbsp;&nbsp;
							{else}
								<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;&nbsp;
							{/if}
				 			{if $value.no != ($entries.field|@count - 1)}
								<i class="vteicon md-link md-sm" onclick="LayoutEditor.changeFieldorder('down','{$value.fieldselect}','{$value.blockid}','{$MODULE}') " title="{$MOD.DOWN}">arrow_downward</i>&nbsp;&nbsp;
							{else}
								<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;&nbsp;
							{/if}
						{elseif $smarty.foreach.inner.first}
							{if $value.no % 2 != 0}
								<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;&nbsp;
							{/if}
							<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;&nbsp;
					 		{if $value.no != ($entries.field|@count - 2) && $entries.no!=1}
								<i class="vteicon md-link md-sm" onclick="LayoutEditor.changeFieldorder('down','{$value.fieldselect}','{$value.blockid}','{$MODULE}') " title="{$MOD.DOWN}">arrow_downward</i>&nbsp;&nbsp;
							{else}
								<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;&nbsp;
							{/if}
							{if $entries.no!=1}
								<i class="vteicon md-link md-sm" onclick="LayoutEditor.changeFieldorder('Right','{$value.fieldselect}','{$value.blockid}','{$MODULE}')" title="{$MOD.RIGHT}">arrow_forward</i>&nbsp;&nbsp;
							{else}
								<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;&nbsp;
							{/if}
						{elseif $smarty.foreach.inner.last}
							{if $value.no % 2 != 0}
								<i class="vteicon md-link md-sm" onclick="LayoutEditor.changeFieldorder('Left','{$value.fieldselect}','{$value.blockid}','{$MODULE}')" title="{$MOD.LEFT}">arrow_back</i>&nbsp;&nbsp;
							{/if}
							{if $value.no != 1}
								<i class="vteicon md-link md-sm" onclick="LayoutEditor.changeFieldorder('up','{$value.fieldselect}','{$value.blockid}','{$MODULE}') " title="{$MOD.UP}">arrow_upward</i>&nbsp;&nbsp;
					 		{else}
								<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;&nbsp;
							{/if}
							<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;&nbsp;
							{if $value.no % 2 == 0}
								<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;&nbsp;
							{/if}
						{else}
							{if $value.no % 2 != 0}
								<i class="vteicon md-link md-sm" onclick="LayoutEditor.changeFieldorder('Left','{$value.fieldselect}','{$value.blockid}','{$MODULE}')" title="{$MOD.LEFT}">arrow_back</i>&nbsp;&nbsp;
							{/if}
							{if $value.no != 1}
								<i class="vteicon md-link md-sm" onclick="LayoutEditor.changeFieldorder('up','{$value.fieldselect}','{$value.blockid}','{$MODULE}') " title="{$MOD.UP}">arrow_upward</i>&nbsp;&nbsp;
						 	{else}
								<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;&nbsp;
							{/if}
							{if $value.no != ($entries.field|@count - 2)}
								<i class="vteicon md-link md-sm" onclick="LayoutEditor.changeFieldorder('down','{$value.fieldselect}','{$value.blockid}','{$MODULE}') " title="{$MOD.DOWN}">arrow_downward</i>&nbsp;&nbsp;
							{else}
								<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;&nbsp;
							{/if}
							{if $value.no % 2 == 0}
								<i class="vteicon md-link md-sm" onclick="LayoutEditor.changeFieldorder('Right','{$value.fieldselect}','{$value.blockid}','{$MODULE}')" title="{$MOD.RIGHT}">arrow_forward</i>&nbsp;&nbsp;
							{/if}
						{/if}
					</td>

				{/foreach}
				</tr>
				</table>
				<br>
				</div>
			{/if}
		{/foreach}
		
	{* tab related lists *}
	{if count($TAB_RELATED) > 0 && $FORMOBILE neq true}
		<div id="tabRelatedLists">
		{foreach item=REL from=$TAB_RELATED}
			<div class="tabrelated" data-relationid="{$REL.id}" id="tabrelated_{$REL.id}" style="padding:5px">
			<table width="100%" cellspacing="0" cellpadding="0" border="0" class="small lvt">
				<tr>
					{* drag handle *}
					{if count($TAB_RELATED) > 1}
					<td class="relHandle" style="cursor:move" width="40">
						<i class="vteicon nohover" style="cursor:move">reorder</i>
					</td>
					{/if}
					<td class="dvInnerHeader">
						<div style="float:left;font-weight:bold;">
							<div class="vcenter" style="margin-right:5px">
								<i class="icon-module icon-{$REL.module|strtolower}" data-first-letter="{$REL.module[0]|strtoupper}"></i>				
							</div>
							<div class="vcenter">{$REL.label}</div>
						</div>
					</td>
					<td align="right">
						<i class="vteicon md-link valign-bottom" onclick="LayoutEditor.removeTabRelated('{$REL.id}')">clear</i>
					</td>
				</tr>
			</table>
			</div>
		{/foreach}
		</div>
		{if count($TAB_RELATED) > 1}
		<script type="text/javascript">
			{literal}
			var cont = jQuery('#tabRelatedLists');
			cont.sortable({
				axis: 'y',
				items: "> div",
				handle: ".relHandle",
				containment: 'parent',
				tolerance: 'pointer',
				distance: 10,
				opacity: 0.8,
				update: function(event, ui) {
					LayoutEditor.saveRelatedTabOrder();
				}
			});
			{/literal}
		</script>
		{/if}
	{/if}
	
	{assign var="FLOAT_TITLE" value=$MOD.ADD_BLOCK}
	{assign var="FLOAT_WIDTH" value="500px"}
	{capture assign="FLOAT_CONTENT"}
	<table width="100%" border="0" cellpadding="5" cellspacing="0">
		<tr>
			<td class="dataLabel" nowrap="nowrap" align="right" width="30%"><b>{$MOD.LBL_BLOCK_NAME}</b></td>
			<td align="left" width="70%">
				<input id="blocklabel" value="" type="text" class="txtBox">
			</td>
		</tr>
		<tr>
			<td class="dataLabel" align="right" width="30%"><b>{$MOD.AFTER}</b></td>
			<td align="left" width="70%">
			<select id="after_blockid" name="after_blockid">
				{foreach key=blockid item=blockname from=$BLOCKS}
				<option value="{$blockid}"> {$blockname} </option>
				{/foreach}
			</select>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<input type="button" name="save"  value= "{$APP.LBL_SAVE_BUTTON_LABEL}"  class="crmButton small save" onclick="LayoutEditor.getCreateCustomBlockForm('{$MODULE}','add') "/>&nbsp;
				<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}"  class="crmButton small cancel" onclick= "hideFloatingDiv('addblock');" />
			</td>
		</tr>
	</table>
	{/capture}
	{include file="FloatingDiv.tpl" FLOAT_ID="addblock" FLOAT_BUTTONS=""}

	{assign var="FLOAT_TITLE" value=$MOD.LBL_ADD_RELATEDLIST}
	{assign var="FLOAT_WIDTH" value="350px"}
	{capture assign="FLOAT_CONTENT"}
	<table width="100%" border="0" cellpadding="5" cellspacing="0">
		<tr>
			<td class="dataLabel" align="right" width="40%"><b>{$MOD.LBL_RELATED_LIST}</b></td>
			<td align="left" width="60%">
			
			<select id="addRelatedSelect" multiple="" size="10">
				{foreach item=relinfo from=$RELATEDLIST}
				{if $relinfo.presence eq 0 && !in_array($relinfo.id, $TAB_RELIDS)}
					<option value="{$relinfo.id}">{$relinfo.label}</option>
				{/if}
				{/foreach}
			</select>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<input type="button" name="save"  value= "{$APP.LBL_SAVE_BUTTON_LABEL}"  class="crmButton small save" onclick="LayoutEditor.addRelatedList() "/>&nbsp;
				<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}"  class="crmButton small cancel" onclick= "hideFloatingDiv('addrelated');" />
			</td>
		</tr>
	</table>
	{/capture}
	{include file="FloatingDiv.tpl" FLOAT_ID="addrelated" FLOAT_BUTTONS=""}
		
	</form>

{* crmv@104568 *}
<script type="text/javascript">
	{if $TABS_JSON}
	var panelBlocks = {$TABS_JSON};
	{else}
	var panelBlocks = {ldelim}{rdelim};
	{/if}
</script>
{* crmv@104568e *}