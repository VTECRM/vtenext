{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@37679 crmv@43764 crmv@57221 crmv@181170 *}

{assign var="fromlink" value=$fromlink_val}

<!-- Added this file to display the fields in Create Entity page based on ui types  -->
{assign var="fieldcount" value=0}
{assign var="fieldstart" value=1}
{assign var="tr_state" value=0}
{assign var="hidden_fields" value=""}	{* crmv@99316 *}
{foreach key=label item=subdata from=$data}
	{foreach key=mainlabel item=maindata from=$subdata}
		{assign var="uitype" value=$maindata[0][0]}
		{assign var="fldlabel" value=$maindata[1][0]}
		{assign var="fldlabel_sel" value=$maindata[1][1]}
		{assign var="fldlabel_combo" value=$maindata[1][2]}
		{assign var="fldname" value=$maindata[2][0]}
		{assign var="fldvalue" value=$maindata[3][0]}
		{assign var="secondvalue" value=$maindata[3][1]}
		{assign var="thirdvalue" value=$maindata[3][2]}
		{assign var="readonly" value=$maindata[4]}
		{assign var="typeofdata" value=$maindata[5]}
		{assign var="isadmin" value=$maindata[6]}
		{assign var="keyfldid" value=$maindata[7]}
		{if $typeofdata eq 'M'}
			{assign var="mandatory_field" value="*"}
			{assign var="keymandatory" value=true}
		{else}
			{assign var="mandatory_field" value=""}
			{assign var="keymandatory" value=false}
		{/if}
		
		{if !empty($keyfldid)}
			{if $readonly eq 100}
				{* crmv@99316 *}
				{capture assign="hidden_field"}
				<tr><td colspan="4">
					{include file="DisplayFieldsHidden.tpl"}
				</td></tr>
				{/capture}
				{assign var="hidden_fields" value=$hidden_fields|cat:$hidden_field}
				{* crmv@99316 *}
			{else}
			
				{assign var=fieldlength value=1}
				{if $uitype eq 19 || $uitype eq 69 || $uitype eq 210 || $uitype eq 220} {* crmv@102879 *}
					{assign var=fieldlength value=2}
				{/if}
				{if $uitype eq 208 && $smarty.session.uitype208.$keyfldid.old_uitype eq '19'}
					{assign var=fieldlength value=2}
				{/if}
				
				{if ($fieldcount eq 0 or $fieldstart eq 1) and $tr_state neq 1}
					{if $fieldstart eq 1}
						{assign var="fieldstart" value=0}
					{/if}
					{if $header eq 'Product Details'}
						<tr valign="top">
					{else}
						<tr style="height:25px" valign="top">
					{/if}
					{assign var="tr_state" value=1}
				{/if}
				
				{if $fieldlength eq 2 and $fieldcount neq 0}
					</tr>
					{assign var="fieldcount" value=0}
				{/if}
				{assign var="fieldcount" value=$fieldcount+1}
				
				{if $fieldlength eq 2}
					<td colspan="4" class="fieldCont fieldContFull">
				{else}
					{if $fieldcount eq 1}
						{assign var="fielddirection" value="Left"}
					{else}
						{assign var="fielddirection" value="Right"}
					{/if}
					<td colspan="2" class="fieldCont fieldCont{$fielddirection}" width="50%">
				{/if}
				
				{if $readonly eq 99}
					{assign var="DIVCLASS" value="dvtCellInfoOff"}
					{assign var=TEMPLATE value='DisplayFieldsReadonly.tpl'}
				{else}
					{if ($MODE eq '' || $MODE eq 'create' || $LAYOUT_CONFIG.enable_always_mandatory_css eq 1) && $keymandatory}	{* crmv@118551 *}
						{assign var="DIVCLASS" value="dvtCellInfoM"}
					{else}
						{assign var="DIVCLASS" value="dvtCellInfo"}
					{/if}
					{assign var=TEMPLATE value='EditViewUI.tpl'}
				{/if}
				
				{assign var="DIVCLASSOTHER" value=""}
				{if $OLD_STYLE eq true}
					{assign var="DIVCLASS" value=$DIVCLASS|cat:" dvtCellInfoOldStyle"}
					{assign var="DIVCLASSOTHER" value="dvtCellInfoOldStyle "}
				{/if}
				
				{include file=$TEMPLATE}
				
				{if $fieldlength eq 2}
					{assign var="fieldcount" value=$fieldcount+1}
				{/if}
					
				{if $fieldcount eq 2}
					</tr>
					{assign var="fieldcount" value=0}
					{assign var="tr_state" value=0}
				{/if}
				
				<!-- This is added to display the existing comments -->
				{if ($MODULE eq 'HelpDesk' || $MODULE eq 'Faq') && $fldname eq 'comments'}
					<tr><td colspan=4>{$COMMENT_BLOCK}</td></tr>
					<script type="text/javascript">
				   		// crmv@97430
				   		var objDiv = document.getElementById("comments_div");
				   		if (objDiv) {ldelim}
							objDiv.scrollTop = objDiv.scrollHeight;
						{rdelim}
						// crmv@97430e
					</script>
				{/if}
				
			{/if}
		{/if}
	{/foreach}
	{if $tr_state eq 1}
		</tr>
		{assign var="fieldcount" value=0}
		{assign var="tr_state" value=0}
	{/if}
{/foreach}
{* crmv@99316 *}
{if !empty($hidden_fields)}
	<tr style="display:none"><td><table>{$hidden_fields}</table></td></tr>
{/if}
{* crmv@99316e *}

{* crmv@181170 *}
<script type="text/javascript">
	VTE.EditView.initializeDisplayFields();
</script>
{* crmv@181170e *}