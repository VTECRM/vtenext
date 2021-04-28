{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@64542 *}

<div>
	<p>{$MOD.LBL_MMAKER_STEP4_INTRO}</p>
</div>

<div style="width:100%; text-align:right" id="mmaker_div_addrelation_button">
	<input type="button" class="small crmbutton create" value="{$APP.LBL_ADD_BUTTON}" onclick="ModuleMakerRelations.showCreateRelation()" />
</div>

<div id="mmaker_div_relations">
{include file="Settings/ModuleMaker/Step4Relations.tpl"}
</div>

<input type="hidden" id="new_module_name" value="{$NEWMODULENAME}"/>
<input type="hidden" id="new_module_single_name" value="{$NEWMODULESINGLENAME}"/>

{* div for relation creation *}
<div id="mmaker_div_addrelation" style="display:none">
	{* buttons *}
	<div style="width:100%; text-align:right" id="mmaker_div_addrelation_button">
		<span id="mmaker_busy" style="display:none;">{include file="LoadingIndicator.tpl"}</span>
		<input type="button" class="small crmbutton cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="ModuleMakerRelations.hideCreateRelation()" />
		<input type="button" class="small crmbutton save" value="{$APP.LBL_SAVE_BUTTON_LABEL}" onclick="ModuleMakerRelations.createRelation()" />
	</div>
	<br>
	{* fields *}
	<table border="0" width="100%">
		
		<tr>
			<td class="mmaker_step_field_cell" align="right" width="20%"><span>{$APP.LBL_FIRST_MODULE}</span>&nbsp;&nbsp;</td>
			<td align="left" width="250">
				<div class="dvtCellInfo">
					<select class="detailedViewTextBox" type="text" name="relation_first_module" id="relation_first_module" onchange="ModuleMakerRelations.changeFirstModule()">
						<option value=""></option>
						<option value="{$NEWMODULENAME}">{$NEWMODULENAME}</option>
						{foreach key=mod item=label from=$RELATION_MODULES}
							<option value="{$mod}">{$label}</option>
						{/foreach}
					</select>
				</div>
			</td>
			<td width="50">&nbsp;</td>
			<td>	
				{$MOD.LBL_FIRST_MODULE_DESC}
			</td>
		</tr>
		
		<tr>
			<td class="mmaker_step_field_cell" align="right" width="20%"><span>{$MOD.LBL_RELATION_TYPE}</span>&nbsp;&nbsp;</td>
			<td align="left" width="250">
				<div class="dvtCellInfo">
					<select class="detailedViewTextBox" type="text" name="relation_type" id="relation_type" onchange="ModuleMakerRelations.changeRelationType()">
						<option value="1ton">{$MOD.LBL_RELATION_TYPE_1TON}</option>
						<option value="nton">{$MOD.LBL_RELATION_TYPE_NTON}</option>
					</select>
				</div>
			</td>
			<td width="50">&nbsp;</td>
			<td>	
				{$MOD.LBL_RELATION_TYPE_DESC}
			</td>
		</tr>
	
		<tr>
			<td class="mmaker_step_field_cell" align="right" width="20%"><span>{$APP.LBL_MODULE}</span>&nbsp;&nbsp;</td>
			<td align="left" width="250">
				<div class="dvtCellInfo">
					<select class="detailedViewTextBox" type="text" name="relation_module" id="relation_module" onchange="ModuleMakerRelations.changeModule()" disabled="">
						<option value=""></option>
						<option value="{$NEWMODULENAME}">{$NEWMODULENAME}</option>
						{foreach key=mod item=label from=$RELATION_MODULES}
							<option value="{$mod}">{$label}</option>
						{/foreach}
					</select>
				</div>
			</td>
			<td width="50">&nbsp;</td>
			<td>	
				{$MOD.LBL_RELATION_MODULE_DESC}
			</td>
		</tr>
		
		<tr class="add_relation_field_1ton">
			<td class="mmaker_step_field_cell" align="right" width="20%"><span>{$MOD.LBL_BLOCK_NAME}</span>&nbsp;&nbsp;</td>
			<td align="left" width="250">
				<div class="dvtCellInfo">
					<select class="detailedViewTextBox" type="text" name="relation_block" id="relation_block">
						{* to be filled from ajax *}
					</select>
				</div>
			</td>
			<td width="50">&nbsp;</td>
			<td>	
				{$MOD.LBL_RELATION_BLOCK_DESC}
			</td>
		</tr>
		
		<tr class="add_relation_field_1ton">
			<td class="mmaker_step_field_cell" align="right" width="20%"><span>{$MOD.FieldName}</span>&nbsp;&nbsp;</td>
			<td align="left" width="250">
				<div class="dvtCellInfo">
					<input class="detailedViewTextBox" type="text" maxlength="50" name="relation_field" id="relation_field" value="" />
				</div>
			</td>
			<td width="50">&nbsp;</td>
			<td>	
				{"%s"|str_replace:$NEWMODULENAME:$MOD.LBL_RELATION_FIELD_DESC}
			</td>
		</tr>
		
	</table>
</div>