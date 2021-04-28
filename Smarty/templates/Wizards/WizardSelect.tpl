{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@96233 *}
<div id="nlWizStep{$STEP_NO}" {if $STEP_NO > 1}style="display:none"{/if}>
	
	{if count($STEP.lists) > 1}
	<div class="" id="nlwChooseListMod{$STEP_NO}">
		{counter start=0 print=false assign="listidx" name="list"}
		{foreach item=list from=$STEP.lists}
			{counter print=false name="list"}
			<div class="radio radio-primary radio-horiz">
			<label for="radioSelect{$STEP_NO}_{$list.module}">
				<input type="radio" name="radioSelect{$STEP_NO}" value="{$list.module}" id="radioSelect{$STEP_NO}_{$list.module}" {if $listidx == 1}checked="checked"{/if} onclick="Wizard.changeSelectList({$STEP_NO}, '{$list.module}')">
				{$list.module}
			</label>
			</div>
			&nbsp;&nbsp;&nbsp;&nbsp;
		{/foreach}
	</div>
	<br>
	{/if}
	
	{counter start=0 print=false assign="listidx" name="list"}
	{foreach item=list from=$STEP.lists}
		{counter print=false name="list"}
		<div class="nlWizTargetList" id="nlw_targetList{$STEP_NO}_{$list.module}" {if $listidx > 1}style="display:none"{/if}>
			{$list.list}
		</div>
		<input type="hidden" id="listlabel{$STEP_NO}_{$list.module}" value="{$list.label}">
	{/foreach}
	
	<div id="nlw_targetsBoxCont{$STEP_NO}">
		<p class="selectListTitle" style="font-weight:bold">{$STEP.label}</p>
		<div id="selectList{$STEP_NO}"></div>
	</div>
	
</div>