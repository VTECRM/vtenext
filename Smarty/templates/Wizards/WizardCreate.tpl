{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@96233 crmv@99132 *}

{include file="modules/SDK/src/Reference/Autocomplete.tpl"}

{assign var="FIELDS" value=$STEP.fields}

<div id="nlWizStep{$STEP_NO}" {if $STEP_NO > 1}style="display:none"{/if}>

	<form name="EditView" id="nlw_RecordFields{$STEP_NO}" onsubmit="return false;">
		<input type="hidden" name="module" value="{$STEP.module}">
		{foreach item=FLD from=$FIELDS}
			{if $FLD.mandatory}
				{assign var="divclass" value="dvtCellInfoM"}
			{else}
				{assign var="divclass" value="dvtCellInfo"}
			{/if}
			{include file="EditViewUI.tpl" NOLABEL=false MODULE=$STEP.module DIVCLASS=$divclass uitype=$FLD.uitype keymandatory=$FLD.mandatory fldlabel=$FLD.label fldname=$FLD.name fldvalue=$FLD.value secondvalue=$FLD.secondvalue}
			<br>
		{/foreach}
	</form>
</div>