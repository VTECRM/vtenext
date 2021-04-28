{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@64542 crmv@102879 crmv@105127 crmv@160837 *}

{assign var=PANELNO value=$STEPVARS.mmaker_currentpanelno}

{* blocks and fields *}
<input type="hidden" name="mmaker_lastfieldid" value="{$STEPVARS.mmaker_lastfieldid}" />

<input type="hidden" name="mmaker_currentpanelno" id="mmaker_currentpanelno" value="{$PANELNO}" />

{if !empty($STEPVARS.mmaker_panels)}
	<table border=0 cellspacing=0 cellpadding=3 width=100% class="small" id="LayoutEditTabs">
	<tr>
	{foreach item=_tab key=panelno from=$STEPVARS.mmaker_panels name="extraDetailForeach"}
		{if $panelno eq $PANELNO}
			{assign var="_class" value="dvtSelectedCell"}
		{else}
			{assign var="_class" value="dvtUnSelectedCell"}
		{/if}
		<td class="tabCell {$_class}" align="center" nowrap="" data-panelno="{$panelno}">
			<input type="hidden" name="panel_{$panelno}_panelno" id="panel_{$panelno}_panelno" value="{$panelno}" />
			<input type="hidden" name="panel_{$panelno}_panellabel" id="panel_{$panelno}_panellabel" value="{$_tab.panellabel}" />
			<input type="hidden" name="panel_{$panelno}_label" id="panel_{$panelno}_label" value="{$_tab.label}" />
			{if count($STEPVARS.mmaker_panels) > 1 && $LAYOUT_READONLY neq true}
				<i class="vteicon md-sm nohover valign-bottom tabHandle" style="cursor:move" title="{$APP.LBL_MOVE}">reorder</i>&nbsp;
			{/if}
			<a href="javascript:;" onclick="ModuleMakerFields.changePanel('{$panelno}', this)">{$_tab.label}</a>
			{if count($STEPVARS.mmaker_panels) > 1 && $LAYOUT_READONLY neq true}
				&nbsp;&nbsp;<i class="vteicon md-link md-sm valign-bottom" title="{$APP.LBL_DELETE}" onclick="ModuleMakerFields.openRemovePanelPopup('{$panelno}')">delete</i>
			{/if}
			{* if $_class == 'dvtSelectedCell'}
				&nbsp;&nbsp;<i class="vteicon md-link md-sm valign-bottom" title="{$APP.LBL_EDIT}" onclick="ModuleMakerFields.showEditTab('{$panelno}', '{$_tab.label}', this)">create</i>
			{/if *}
		</td>
	{/foreach}
	{if $LAYOUT_READONLY neq true}
	<td class="dvtTabCache" align="right"><i class="vteicon md-link" title="{$MOD.LBL_ADD_TAB}" onclick="ModuleMakerFields.openAddPanelPopup()">add</i></td>
	{/if}
	<td class="dvtTabCache" align="right" style="width:100%"></td>
	</tr>
	</table>
	<br>
	{if count($STEPVARS.mmaker_panels) > 1}
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
					ModuleMakerFields.savePanelsOrder();
				}
			});
			{/literal}
		</script>
	{/if}
{/if}

{if $LAYOUT_READONLY}
{else}
	<table class="tableHeading" width="98%" border="0" cellspacing="0" cellpadding="0" align="center">
		<tr>
			<td align="left" width="85%">{if $PROCESSMAKERMODE eq true}<b>{$MOD.LBL_PM_MANAGE_DYNAFORM}</b>{/if}</td>
			<td align="left" width="2%"><div id="mmaker_busy" style="display:none;">{include file="LoadingIndicator.tpl"}</div></td>
			<td align="right" width="13%" nowrap>
				<input type="button" class="crmButton create small" onclick="ModuleMakerFields.openAddBlockPopup()" alt="{$MOD.ADD_BLOCK}" title="{$MOD.ADD_BLOCK}" value="{$MOD.ADD_BLOCK}"/>
				{if $PROCESSMAKERMODE eq true}
					<input type="button" class="crmButton create small" onclick="ProcessHelperScript.openImportDynaformBlocks()" alt="{$MOD.LBL_PM_IMPORT_DYNAFORM_BLOCK}" title="{$MOD.LBL_PM_IMPORT_DYNAFORM_BLOCK}" value="{$MOD.LBL_PM_IMPORT_DYNAFORM_BLOCK}..."/>
					<input type="button" class="crmButton create small" onclick="ProcessHelperScript.openImportModuleBlocks()" alt="{$MOD.LBL_PM_IMPORT_MODULE_BLOCK}" title="{$MOD.LBL_PM_IMPORT_MODULE_BLOCK}" value="{$MOD.LBL_PM_IMPORT_MODULE_BLOCK}..."/>
				{/if}
			</td>
		</tr>
	</table>
{/if}

{foreach item=block key=blockno from=$STEPVARS.mmaker_blocks name=outer}
	
	<div class="layoutBlock" id="block_{$blockno}" style="{if $block.panelno != $PANELNO}display:none{/if}" data-panelno="{$block.panelno}">
	<table class="listTable" border="0" cellpadding="3" cellspacing="0" align="center" width="98%">
	
	{* add an empty line as a separator *}
	{if $smarty.foreach.outer.first neq true}
		<tr><td colspan="4"><img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;&nbsp;</td></tr>
	{/if}
	
	{* Block header line *}
	<tr class="blockheaderrow">
		{* block inputs *}
		{foreach item=blockval key=blockkey from=$block}
			{if !is_array($blockval)}
			<input type="hidden" name="block_{$blockno}_{$blockkey}" id="block_{$blockno}_{$blockkey}" value="{$blockval}" />
			{/if}
		{/foreach}
	
		{* title *}
		<td class="colHeader small" colspan="2" width="50%">
			{if $PROCESSMAKERMODE eq true && ($MODE eq 'openimportdynaformblocks' || $MODE eq 'loadmoduleblocks')}
				<input type="checkbox" class="small {$DYNAELEMENT}_checkbox" name="import_{$DYNAELEMENT}_{$blockno}" id="import_{$DYNAELEMENT}_{$blockno}" value="{$DYNAELEMENT}_{$blockno}" />
				&nbsp;<label for="import_{$DYNAELEMENT}_{$blockno}">{$block.label}</label>&nbsp;&nbsp;
			{else}
				&nbsp;{$block.label}&nbsp;&nbsp;
			{/if}
		</td>
		{* block buttons *}
		<td class="colHeader small" id="blockno_{$blockno}" colspan="2" align="right" width="50%">
			{if $LAYOUT_READONLY}
			{else}
				{if count($STEPVARS.mmaker_panels) > 1}
				<i class="vteicon md-link" onclick="ModuleMakerFields.openMoveBlockPanelPopup('{$blockno}')" title="{$MOD.LBL_MOVE_TO_ANOTHER_TAB}">open_with</i>&nbsp;
				{/if}
				{if $block.editable}
					{if $PROCESSMAKERMODE eq true}
						<i class="vteicon md-link" onclick="ModuleMakerFields.openEditBlockPopup('{$blockno}')" title="{$MOD.LBL_EDIT}">create</i>&nbsp;
					{/if}
					{if $block.deletable}
					<i class="vteicon md-link" onClick="ModuleMakerFields.delBlock('{$blockno}')" title="Delete">delete</i>&nbsp;
					{/if}
					<i class="vteicon md-link" onclick="ModuleMakerFields.openAddFieldPopup('{$blockno}')" title="{$MOD.LBL_ADD_NEW_FIELD}">add</i>&nbsp;
					<i class="vteicon md-link" onclick="ModuleMakerFields.openAddRelatedField('{$blockno}')" title="{$MOD.LBL_ADD_NEW_RELATED_FIELD}">link</i>&nbsp;
					{* crmv@157775 *}
					{if $PROCESSMAKERMODE eq true}
						<i class="vteicon md-link" onclick="ModuleMakerFields.openAddTableFieldPopup('{$blockno}')" title="{$APP.LBL_ADD_FIELD_TABLE}">grid_on</i>&nbsp;
					{/if}
					{* crmv@157775e *}
					<i class="vteicon2 md-link fa-magnet fa-rotate-180" style="vertical-align:super" onClick="ModuleMakerFields.openMoveFieldsPopup('{$blockno}')" title="{$MOD.LBL_MOVE_FIELDS}"></i>&nbsp;
				{/if}
				{if $smarty.foreach.outer.first}
					<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;
					<i class="vteicon md-link" onclick="ModuleMakerFields.moveBlock('{$blockno}', 'down')" title="{$MOD.DOWN}">arrow_downward</i>&nbsp;
				{elseif $smarty.foreach.outer.last}
					<i class="vteicon md-link" onclick="ModuleMakerFields.moveBlock('{$blockno}', 'up')" title="{$MOD.UP}">arrow_upward</i>&nbsp;
					<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;
				{else}
					<i class="vteicon md-link" onclick="ModuleMakerFields.moveBlock('{$blockno}', 'up')" title="{$MOD.UP}">arrow_upward</i>&nbsp;
					<i class="vteicon md-link" onclick="ModuleMakerFields.moveBlock('{$blockno}', 'down')" title="{$MOD.DOWN}">arrow_downward</i>&nbsp;
				{/if}
			{/if}
		</td>
	</tr>
	
	{* Fields *}
	{assign var=blankstyle value="width:20px;height:20px;"}
	{if !empty($block.fields)}
		{assign var=blockcount value=$block.fields|@count}
	{else}
		{assign var=blockcount value=0}
	{/if}
	<tr>
		{foreach name=inner key=fieldno item=field from=$block.fields}
			{* field inputs *}
			<input type="hidden" id="fieldcount_{$blockno}" value="{$block.fields|@count}" />
			{foreach item=fieldval key=fieldkey from=$field}
				{if !is_array($fieldval)}
				<input type="hidden" name="field_{$blockno}_{$fieldno}_{$fieldkey}" id="field_{$blockno}_{$fieldno}_{$fieldkey}" value="{$fieldval|replace:'"':'&quot;'}" /> {* crmv@102879 *}
				{/if}
			{/foreach}
		
			{if $fieldno > 0 && $fieldno % 2 == 0}
				</tr>
				<tr>
			{/if}
			<td width="30%" id="colourButton" >&nbsp;
				<span>{$field.label}</span>
				{if $field.mandatory}<font color='red'> *</font>{/if}
			</td>
			<td width="20%" align = "right" class="colData small" >
				{if $LAYOUT_READONLY}
				{else}
					{if $field.editable}
						{* crmv@96450 crmv@102879 *}
						{if $PROCESSMAKERMODE eq true}
							<i class="vteicon md-sm md-link" onclick="AlertNotifications.alert(2, null, ModuleMakerFields.openEditFieldPopup, ['{$blockno}','{$fieldno}', '{$field.uitype}'], ModuleMakerFields)" title="{$MOD.LBL_EDIT_PROPERTIES}">create</i>&nbsp;&nbsp;
						{else}
							<i class="vteicon md-sm md-link" onclick="ModuleMakerFields.openEditFieldPopup('{$blockno}', '{$fieldno}', '{$field.uitype}')" title="{$MOD.LBL_EDIT_PROPERTIES}">create</i>&nbsp;&nbsp;
						{/if}
						{* crmv@96450e crmv@102879e *}
					{/if}
					{if $field.deletable}
						<i class="vteicon md-sm md-link" onclick="ModuleMakerFields.delField('{$blockno}', '{$fieldno}')" title="{$APP.LBL_DELETE_BUTTON}">delete</i>&nbsp;&nbsp;
					{/if}
					{* todo: block for edit *}
					{if $smarty.foreach.inner.first}
						{if $fieldno % 2 != 0}
							<img src="{'blank.gif'|resourcever}" style="{$blankstyle}" border="0" />&nbsp;&nbsp;
						{/if}
						<img src="{'blank.gif'|resourcever}" style="{$blankstyle}" border="0" />&nbsp;&nbsp;
				 		{if $fieldno != ($blockcount-2) && $blockcount != 1}
							<i class="vteicon md-sm md-link" onclick="ModuleMakerFields.moveField('{$blockno}', '{$fieldno}', 'down')" title="{$MOD.DOWN}">arrow_downward</i>&nbsp;&nbsp;
						{else}
							<img src="{'blank.gif'|resourcever}" style="{$blankstyle}" border="0" />&nbsp;&nbsp;
						{/if}
						{if $blockcount != 1}
							<i class="vteicon md-sm md-link" onclick="ModuleMakerFields.moveField('{$blockno}', '{$fieldno}', 'right')" title="{$MOD.RIGHT}">arrow_forward</i>&nbsp;&nbsp;
						{else}
							<img src="{'blank.gif'|resourcever}" style="{$blankstyle}" border="0" />&nbsp;&nbsp;
						{/if}
					{elseif $smarty.foreach.inner.last}
						{if $fieldno % 2 != 0}
							<i class="vteicon md-sm md-link" onclick="ModuleMakerFields.moveField('{$blockno}', '{$fieldno}', 'left')" title="{$MOD.LEFT}">arrow_back</i>&nbsp;&nbsp;
						{/if}
						{if $fieldno != 1}
							<i class="vteicon md-sm md-link" onclick="ModuleMakerFields.moveField('{$blockno}', '{$fieldno}', 'up')" title="{$MOD.UP}">arrow_upward</i>&nbsp;&nbsp;
				 		{else}
							<img src="{'blank.gif'|resourcever}" style="{$blankstyle}" border="0" />&nbsp;&nbsp;
						{/if}
						<img src="{'blank.gif'|resourcever}" style="{$blankstyle}" border="0" />&nbsp;&nbsp;
						{if $fieldno % 2 == 0}
							<img src="{'blank.gif'|resourcever}" style="{$blankstyle}" border="0" />&nbsp;&nbsp;
						{/if}
					{else}
						{if $fieldno % 2 != 0}
							<i class="vteicon md-sm md-link" onclick="ModuleMakerFields.moveField('{$blockno}', '{$fieldno}', 'left')" title="{$MOD.LEFT}">arrow_back</i>&nbsp;&nbsp;
						{/if}
						{if $fieldno != 1}
							<i class="vteicon md-sm md-link" onclick="ModuleMakerFields.moveField('{$blockno}', '{$fieldno}', 'up')" title="{$MOD.UP}">arrow_upward</i>&nbsp;&nbsp;
					 	{else}
							<img src="{'blank.gif'|resourcever}" style="{$blankstyle}" border="0" />&nbsp;&nbsp;
						{/if}
						{if $fieldno != ($blockcount - 2)}
							<i class="vteicon md-sm md-link" onclick="ModuleMakerFields.moveField('{$blockno}', '{$fieldno}', 'down')" title="{$MOD.DOWN}">arrow_downward</i>&nbsp;&nbsp;
						{else}
							<img src="{'blank.gif'|resourcever}" style="{$blankstyle}" border="0" />&nbsp;&nbsp;
						{/if}
						{if $fieldno % 2 == 0}
							<i class="vteicon md-sm md-link" onclick="ModuleMakerFields.moveField('{$blockno}', '{$fieldno}', 'right')" title="{$MOD.RIGHT}">arrow_forward</i>&nbsp;&nbsp;
						{/if}
					{/if}
				{/if}
			</td>
		{/foreach}

	</tr>
	
	</table>
	</div>
	
{/foreach}


{* floating divs *}


{* add panel *}
<div id="mmaker_div_addpanel" class="crmvDiv floatingDiv" style="min-width:400px">
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td class="level3Bg floatingHandle">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="50%"><b>{$MOD.LBL_ADD_TAB}</b></td>
					<td width="50%" align="right">&nbsp;
					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div class="crmvDivContent">
		<table border="0" cellpadding="5" cellspacing="1" width="98%" align="center">
			<tr>
				<td width="30%" align="right">{$MOD.LBL_TAB_NAME}</td>
				<td><input type="text" id="addpanelname"></td>
			</tr>
			<tr>
				<td colspan="2" align="right">
					<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}"  class="crmButton small cancel" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_addpanel')" />
					<input type="button" name="save"  value= "{$APP.LBL_SAVE_BUTTON_LABEL}"  class="crmButton small save" onclick="ModuleMakerFields.addPanel()"/>&nbsp;
				</td>
			</tr>
		</table>
	</div>
	<div class="closebutton" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_addpanel')"></div>
</div>

{* del panel *}
<div id="mmaker_div_delpanel" class="crmvDiv floatingDiv" style="min-width:400px">
	<input type="hidden" id="delpanelno" value="" />
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td class="level3Bg floatingHandle">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="50%"><b>{$MOD.LBL_DELETE} tab</b></td>
					<td width="50%" align="right">&nbsp;
					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div class="crmvDivContent">
		<table border="0" cellpadding="5" cellspacing="1" width="98%" align="center">
			<tr>
				<td width="60%" align="right">{$MOD.LBL_CHOOSE_TRANSFER_TAB}</td>
				<td>
					<select type="text" id="delpanellist">
						{foreach item=panel key=panelno from=$STEPVARS.mmaker_panels}
						<option value="{$panelno}">{$panel.label}</option>
						{/foreach}
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="right">
					<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}"  class="crmButton small cancel" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_delpanel')" />
					<input type="button" name="save"  value= "{$APP.LBL_SAVE_BUTTON_LABEL}"  class="crmButton small save" onclick="ModuleMakerFields.delPanel()"/>&nbsp;
				</td>
			</tr>
		</table>
	</div>
	<div class="closebutton" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_delpanel')"></div>
</div>

{* move block to panel *}
<div id="mmaker_div_moveblockpanel" class="crmvDiv floatingDiv" style="min-width:400px">
	<input type="hidden" id="moveblockno" value="" />
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td class="level3Bg floatingHandle">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="50%"><b>{$MOD.LBL_MOVE_BLOCK} tab</b></td>
					<td width="50%" align="right">&nbsp;
					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div class="crmvDivContent">
		<table border="0" cellpadding="5" cellspacing="1" width="98%" align="center">
			<tr>
				<td width="60%" align="right">{$MOD.LBL_CHOOSE_TRANSFER_TAB_BLOCK}</td>
				<td>
					<select type="text" id="moveblockpanellist">
						{foreach item=panel key=panelno from=$STEPVARS.mmaker_panels}
						<option value="{$panelno}">{$panel.label}</option>
						{/foreach}
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="right">
					<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}"  class="crmButton small cancel" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_moveblockpanel')" />
					<input type="button" name="save"  value= "{$APP.LBL_SAVE_BUTTON_LABEL}"  class="crmButton small save" onclick="ModuleMakerFields.moveBlockToPanel()"/>&nbsp;
				</td>
			</tr>
		</table>
	</div>
	<div class="closebutton" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_moveblockpanel')"></div>
</div>


{* add block *}
<div id="mmaker_div_addblock" class="crmvDiv floatingDiv" style="min-width:400px">
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td class="level3Bg floatingHandle">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="50%"><b>{$MOD.ADD_BLOCK}</b></td>
					<td width="50%" align="right">&nbsp;

					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div class="crmvDivContent">
		<table border="0" cellpadding="5" cellspacing="1" width="98%" align="center">
			<tr>
				<td width="30%" align="right">{$MOD.LBL_BLOCK_NAME}</td>
				<td><input type="text" id="addblockname"></td>
			</tr>
			<tr>
				<td align="right">{$MOD.AFTER}</td>
				<td>
					<select name="addafterblock" id="addafterblock">
						{foreach item=block key=blockno from=$STEPVARS.mmaker_blocks}
						<option value="{$blockno}">{$block.label}</option>
						{/foreach}
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="right">
					<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}"  class="crmButton small cancel" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_addblock')" />
					<input type="button" name="save"  value= "{$APP.LBL_SAVE_BUTTON_LABEL}"  class="crmButton small save" onclick="ModuleMakerFields.addBlock()"/>&nbsp;
				</td>
			</tr>
		</table>
	</div>
	<div class="closebutton" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_addblock')"></div>
</div>


{* edit block *}
<div id="mmaker_div_editblock" class="crmvDiv floatingDiv" style="min-width:400px">
	<input type="hidden" id="mmaker_editblock_blockno"  value="" />
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td class="level3Bg floatingHandle">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="50%"><b>{$MOD.LBL_EDIT}</b></td>
					<td width="50%" align="right">&nbsp;

					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div class="crmvDivContent">
		<table border="0" cellpadding="5" cellspacing="1" width="98%" align="center">
			<tr>
				<td width="30%" align="right">{$MOD.LBL_BLOCK_NAME}</td>
				<td><input type="text" id="editlabel" class="detailedViewTextBox"></td>
			</tr>
			{*
			<tr>
				<td width="30%" align="right">{$MOD.LBL_LABEL}</td>
				<td><input type="text" id="editblocklabel" class="detailedViewTextBox"></td>
			</tr>
			*}
			<tr>
				<td colspan="2" align="right">
					<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}"  class="crmButton small cancel" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_editblock')" />
					<input type="button" name="save"  value= "{$APP.LBL_SAVE_BUTTON_LABEL}"  class="crmButton small save" onclick="ModuleMakerFields.editBlock()"/>&nbsp;
				</td>
			</tr>
		</table>
	</div>
	<div class="closebutton" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_addblock')"></div>
</div>


{* add field *}
<div id="mmaker_div_addfield" class="crmvDiv floatingDiv" style="width:600px;height:300px">
	<input type="hidden" id="mmaker_newfield_blockno"  value="" />
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td class="level3Bg floatingHandle">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="50%"><b>{$MOD.LBL_CREATE_NEW_FIELD}</b></td>
					<td width="50%" align="right">&nbsp;

					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div class="crmvDivContent">
		<table border="0" cellpadding="5" cellspacing="1" width="98%" align="center">
			<tr>
				<td width="220" align="right">
					<div name="cfcombo" class="layoutEditorFieldPicker">
						<table border="0">
							{foreach key=nfieldno item=nfield from=$NEWFIELDS}
								<tr><td align="left"><a id="newfield_{$nfieldno}" href="javascript:void(0);" class="newFieldMnu" onclick="ModuleMakerFields.selectNewField('{$nfieldno}', '{","|implode:$nfield.properties}', '{$nfield.uitype}');">
									{if isset($nfield.vteicon)}
										<i class="vteicon customMnuIcon nohover">{$nfield.vteicon}</i> {* crmv@102879 *}
									{elseif isset($nfield.vteicon2)}
										<i class="vteicon2 {$nfield.vteicon2} customMnuIcon nohover"></i> {* crmv@102879 *}
									{/if}
									&nbsp;
									<span class="customMnuText">{$nfield.label}</span>
								</a></td></tr>
								{if $nfield.relatedmods}
									{assign var=relmods value=$nfield.relatedmods}
									{assign var=relfieldno value=$nfieldno}
								{/if}
								{* crmv@101683 *}
								{if $nfield.users}
									{assign var=users value=$nfield.users}
								{/if}
								{* crmv@101683e *}
							{/foreach}
							<input type="hidden" id="newrelfieldno" value="{$relfieldno}" />
						</table>
					</div>
				</td>
				<td valign="top" align="left">
					{* field properties *}
					<table border="0" id="mmaker_newfield_props" width="100%">
						<tr id="newfieldprop_label" class="newfieldprop">
							<td class="dataLabel" align="right" nowrap="" width="40%">{$MOD.LBL_LABEL}</td>
							<td><input type="text" name="newfieldprop_val_label" id="newfieldprop_val_label" value="" maxlength="50" class="detailedViewTextBox"/></td>
						</tr>
						<tr id="newfieldprop_length" class="newfieldprop">
							<td class="dataLabel" align="right" nowrap="" width="40%">{$MOD.LBL_LENGTH}</td>
							<td><input type="text" name="newfieldprop_val_length" id="newfieldprop_val_length" value="" maxlength="4" class="detailedViewTextBox"/></td>
						</tr>
						<tr id="newfieldprop_decimals" class="newfieldprop">
							<td class="dataLabel" align="right" nowrap="" width="40%">{$MOD.LBL_DECIMAL_PLACES}</td>
							<td><input type="text" name="newfieldprop_val_decimals" id="newfieldprop_val_decimals" value="" maxlength="1" class="detailedViewTextBox"/></td>
						</tr>
						<tr id="newfieldprop_autoprefix" class="newfieldprop">
							<td class="dataLabel" align="right" nowrap="" width="40%">{$MOD.LBL_USE_PREFIX}</td>
							<td><input type="text" name="newfieldprop_val_autoprefix" id="newfieldprop_val_autoprefix" value="" maxlength="5" class="detailedViewTextBox"/></td>
						</tr>
						<tr id="newfieldprop_picklistvalues" class="newfieldprop">
							<td class="dataLabel" align="right" nowrap="" width="40%">{$MOD.LBL_PICK_LIST_VALUES}</td>
							<td><textarea name="newfieldprop_val_picklistvalues" id="newfieldprop_val_picklistvalues"></textarea></td>
						</tr>
						{if $relmods}
						<tr id="newfieldprop_relatedmods" class="newfieldprop">
							<td class="dataLabel" align="right" nowrap="" width="40%">{$MOD.LBL_RELATED_MODULES}</td>
							<td><select name="newfieldprop_val_relatedmods" id="newfieldprop_val_relatedmods" multiple="multiple" size="8">
							{foreach key=modname item=modlabel from=$relmods}
								<option value="{$modname}">{$modlabel}</option>
							{/foreach}
							</select></td>
						</tr>
						{/if}
						{* crmv@98570 *}
						<tr id="newfieldprop_onclick" class="newfieldprop">
							<td class="dataLabel" align="right" nowrap="" width="40%">{$MOD.LBL_FIELD_BUTTON_ONCLICK}</td>
							<td><input type="text" name="newfieldprop_val_onclick" id="newfieldprop_val_onclick" value="" maxlength="50" /></td>
						</tr>
						<tr id="newfieldprop_code" class="newfieldprop">
							<td class="dataLabel" align="right" nowrap="" width="40%">{$MOD.LBL_FIELD_BUTTON_CODE}</td>
							<td><textarea name="newfieldprop_val_code" id="newfieldprop_val_code"></textarea></td>
						</tr>
						{* crmv@98570e *}
						{* crmv@101683 *}
						<tr id="newfieldprop_users" class="newfieldprop">
							<td class="dataLabel" align="right" nowrap="" width="40%">{'Users'|getTranslatedString}</td>
							<td><select name="newfieldprop_val_users" id="newfieldprop_val_users" multiple="multiple" size="8">
							{foreach key=id item=name from=$users}
								<option value="{$id}">{$name}</option>
							{/foreach}
							</select></td>
						</tr>
						{* crmv@101683e *}
						{* crmv@102879 *}
						{* <tr id="newfieldprop_columns" class="newfieldprop">
							<td class="dataLabel" align="right" nowrap="" width="40%">{$APP.LBL_COLUMNS}</td>
							<td>
								<div type="text" style="float:left" id="newfieldprop_display_columns">
								</div>
								<div style="float:right">
									<i class="vteicon md-link" title="{$APP.LBL_ADD_COLUMN}" onclick="ModuleMakerFields.addFieldColumn(this)">add</i>
								</div>
								<input type="hidden" name="newfieldprop_val_columns" id="newfieldprop_val_columns" value=""/>
								
							</td>
						</tr>
						*}
						{* crmv@102879e *}
					</table>
					{* defaults *}
					<script type="text/javascript">
						if (window.ModuleMakerFields) {ldelim}
							ModuleMakerFields.newFieldsDefaults = {ldelim}{rdelim};
							{foreach item=fld key=nfield from=$NEWFIELDS}
							{if !empty($fld.defaults)}
							ModuleMakerFields.newFieldsDefaults[{$nfield}] = {ldelim}{rdelim};
							{foreach key=prop item=val from=$fld.defaults}
							ModuleMakerFields.newFieldsDefaults[{$nfield}]['{$prop}'] = '{$val}';
							{/foreach}
							{/if}
							{/foreach}
						{rdelim}
					</script>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="right">
					<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}"  class="crmButton small cancel" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_addfield')" />
					<input type="button" name="save"  value= "{$APP.LBL_SAVE_BUTTON_LABEL}"  class="crmButton small save" onclick="ModuleMakerFields.addField()"/>&nbsp;
				</td>
			</tr>
		</table>
	</div>
	<div class="closebutton" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_addfield')"></div>
</div>

{* crmv@102879 - add table field *}
{include file="Settings/ModuleMaker/Step2TableField.tpl"}


{* move fields in block *}
<div id="mmaker_div_movefields" class="crmvDiv floatingDiv" style="width:300px;height:300px">
	<input type="hidden" id="mmaker_movefields_blockno"  value="" />
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td class="level3Bg floatingHandle">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="50%"><b>{$APP.LBL_MOVE_BLOCK_FIELD}</b></td>
					<td width="50%" align="right">&nbsp;

					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div class="crmvDivContent">
		<div id="mmaker_div_selmovefields">
		<p>&nbsp;&nbsp;{$MOD.LBL_SELECT_FIELD_TO_MOVE_IN_BLOCK}</p>
		<table border="0" cellpadding="5" cellspacing="1" width="98%" align="center">
			<tr>
				<td>
					<select id="movefields_select" size="10" multiple="" style="width:100%">
						{foreach item=block key=blockno from=$STEPVARS.mmaker_blocks}
							{if !empty($block.fields)}
								<optgroup name="movefield_group_{$blockno}" label="{$block.label}">
							{/if}
							{foreach key=fieldno item=field from=$block.fields}
								<option value="movefield_{$blockno}_{$fieldno}">{$field.label}</option>
							{/foreach}
							{if !empty($block.fields)}
								</optgroup>
							{/if}
						{/foreach}
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="right">
					<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}"  class="crmButton small cancel" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_movefields')" />
					<input type="button" name="save"  value= "{$MOD.LBL_MOVE_FIELDS}"  class="crmButton small save" onclick="ModuleMakerFields.moveFieldsToBlock()"/>&nbsp;
				</td>
			</tr>
		</table>
		</div>
		<div id="mmaker_div_nomovefields" style="display:none;padding:10px">
			<p>{$MOD.LBL_NO_FIELDS_TO_MOVE_IN_BLOCK}</p>
		</div>
	</div>
	<div class="closebutton" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_movefields')"></div>
</div>


{* edit fields properties *}
<div id="mmaker_div_editfield" class="crmvDiv floatingDiv" style="width:500px;">
	<input type="hidden" id="mmaker_editfield_blockno"  value="" />
	<input type="hidden" id="mmaker_editfield_fieldno"  value="" />
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td class="level3Bg floatingHandle">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="50%"><b>{$MOD.LBL_FIELD_PROPERTIES}</b></td>
					<td width="50%" align="right">&nbsp;

					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div class="crmvDivContent">
		<table border="0" cellpadding="5" cellspacing="1" width="97%" align="center">
			{* crmv@96450 crmv@98570 *}
			<tr><td colspan="2">{$MOD.LBL_EDIT_FIELD_PROPERTY_DESC}</td></tr>
			{if $PROCESSMAKERMODE eq true}
				<tr>
					<td><label for="fieldprop_fieldlabel">{$MOD.LBL_LABEL}</label></td>
					<td><input type="text" id="fieldprop_fieldlabel" class="detailedViewTextBox" value="" /></td>
				</tr>
			{/if}
			<tr>
				<td><b>{$MOD.LBL_PERMISSIONS}</b></td>
				<td>
					<select id="fieldprop_readonly" onchange="checkFieldPermissions('readonly')">
						<option value="1">{'Read/Write'|getTranslatedString:'Settings'}</option>
						<option value="99">{'Read Only '|getTranslatedString:'Settings'}</option>
						<option value="100">{'LBL_HIDDEN'|getTranslatedString:'Users'}</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><label for="fieldprop_mandatory">{$MOD.LBL_MANDATORY_FIELD}</label></td>
				<td><input type="checkbox" name="" id="fieldprop_mandatory" onchange="checkFieldPermissions('mandatory')" /></td>
			</tr>
			{if $PROCESSMAKERMODE neq true}
				<tr>
					<td><label for="fieldprop_masseditable">{$MOD.LBL_MASS_EDIT}</label></td>
					<td><input type="checkbox" name="" id="fieldprop_masseditable" /></td>
				</tr>
			{/if}
			<tr class="fieldprop_picklistvalues"><td colspan="2"><br>{$MOD.LBL_PICK_LIST_VALUES}</td></tr>
			<tr class="fieldprop_picklistvalues"><td colspan="2"><textarea name="fieldprop_picklistvalues" id="fieldprop_picklistvalues"></textarea></td></tr>
			{* crmv@101683 *}
			<tr class="fieldprop_users" valign="top">
				<td><b>{'Users'|getTranslatedString}</b></td>
				<td>
					<select name="fieldprop_users" id="fieldprop_users" multiple="multiple" size="8">
					{foreach key=id item=name from=$users}
						<option value="{$id}">{$name}</option>
					{/foreach}
					</select>
				</td>
			</tr>
			{* crmv@101683e *}
			{if $PROCESSMAKERMODE eq true}
				<tr class="fieldprop_default"><td colspan="2"><br>{'LBL_DEFAULT_VALUE'|getTranslatedString:'Import'}</td></tr>
				<tr class="fieldprop_default"><td colspan="2">
					{* crmv@160843 *}
					<div class="editoptions" id="defaultValueContainer" fieldname="fieldprop_default" optionstype="fieldnames" style="float:right;"></div>
					<div class="dvtCellInfo">
						<input type="text" id="fieldprop_default" name="fieldprop_default" class="detailedViewTextBox" value="" />
					</div>
					{* crmv@160843e *}
				</td></tr>
				<tr class="fieldprop_onclick"><td colspan="2"><br>{'LBL_FIELD_BUTTON_ONCLICK'|getTranslatedString:'Settings'}</td></tr>
				<tr class="fieldprop_onclick"><td colspan="2">
					<div class="dvtCellInfo"><input type="text" id="fieldprop_onclick" class="detailedViewTextBox" value="" /></div>
				</td></tr>
				<tr class="fieldprop_code"><td colspan="2"><br>{'LBL_FIELD_BUTTON_CODE'|getTranslatedString:'Settings'}</td></tr>
				<tr class="fieldprop_code"><td colspan="2">
					<div class="dvtCellInfo"><textarea name="fieldprop_code" id="fieldprop_code"></textarea></div>
				</td></tr>
			{/if}
			<tr><td colspan="2">&nbsp;</td></tr>
			{* crmv@96450e crmv@98570e *}
			<tr>
				<td colspan="2" align="right">
					<input type="button" name="save"  value= "{$APP.LBL_SAVE_BUTTON_LABEL}"  class="crmButton small save" onclick="ModuleMakerFields.editField()"/>
					<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}"  class="crmButton small cancel" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_editfield')" />
				</td>
			</tr>
		</table>
	</div>
	<div class="closebutton" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_editfield')"></div>
</div>


{* enable dragging for every floating div *}
<script type="text/javascript">
{literal}
(function() {
	// crmv@192014
	var floats = jQuery('div.floatingDiv');
	floats.each(function(index, f) {
		if (f) {
			var handle = jQuery(f).find('.floatingHandle').get(0);
			if (handle) {
				jQuery(f).draggable({
					handle: handle
				});
			}
		}
	});
	// crmv@192014e
})();
//crmv@96450
function checkFieldPermissions(mode) {
	if (mode == 'readonly') {
		if (jQuery('#fieldprop_readonly').val() != '1') jQuery('#fieldprop_mandatory').prop('checked',false);
	} else if (mode == 'mandatory') {
		if (jQuery('#fieldprop_mandatory').prop('checked') == true) jQuery('#fieldprop_readonly').val('1');
	}
}
//crmv@96450e
{/literal}
</script>