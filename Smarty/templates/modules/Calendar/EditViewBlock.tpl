{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *} {* crmv@181170 *}

<div class="col-xs-12 nopadding">
	<table border="0" cellspacing="0" cellpadding="{if $OLD_STYLE eq true}2{else}5{/if}" width="100%" class="small">
		{* crmv@104568 *}
		{foreach item=dataBlock from=$BLOCKS name='blocksIteration'}
			{assign var="header" value=$dataBlock.label}
			{assign var="blockid" value=$dataBlock.blockid}
										
			{if isset($BLOCKVISIBILITY.$blockid) && $BLOCKVISIBILITY.$blockid eq 0}
				{assign var="BLOCKDISPLAYSTATUS" value="display:none"}
			{else}
				{assign var="BLOCKDISPLAYSTATUS" value=""}
			{/if}
			
			{if $smarty.foreach.blocksIteration.iteration gt 1}
			<tr class="blockrow_{$blockid}" style="{$BLOCKDISPLAYSTATUS}">
				{if $header == $MOD.LBL_ADDRESS_INFORMATION}
					{include file='AddressCopy.tpl'}
				{else}
					<td colspan="4" class="detailedViewHeader">
					<b>{$header}</b>
				{/if}
				</td>
			</tr>
			{/if}
	
			<tbody id="displayfields_{$blockid}" class="blockrow_{$blockid}" style="{$BLOCKDISPLAYSTATUS}">
				{assign var="fieldcount" value=0}
				{assign var="fieldstart" value=1}
				{assign var="tr_state" value=0}
				
				{foreach key=label item=subdata from=$dataBlock.fields}
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
						
						{if !$fldname|in_array:$EDIT_SKIP_FIELDS}
							{if !empty($keyfldid)}
								{if $readonly eq 100}
									<tr style="display:none;">
										<td colspan="4">{include file="DisplayFieldsHidden.tpl"}</td>
									</tr>
								{else}
									{assign var=fieldlength value=1}
									{if $uitype eq 19 || $uitype eq 69 || $uitype eq 210}
										{assign var=fieldlength value=2}
									{/if}
									{if $uitype eq 208 && $smarty.session.uitype208.$keyfldid.old_uitype eq '19'}
										{assign var=fieldlength value=2}
									{/if}
									
									{if $fldname|in_array:$LONG_FIELDS}
										{assign var=fieldlength value=2}
									{/if}
									
									{if ($fieldcount eq 0 or $fieldstart eq 1) and $tr_state neq 1}
										{if $fieldstart eq 1}
											{assign var="fieldstart" value=0}
										{/if}
										<tr style="height:25px" valign="top">
										{assign var="tr_state" value=1}
									{/if}
									
									{if $fieldlength eq 2 and $fieldcount neq 0}
										</tr>
										{assign var="fieldcount" value=0}
									{/if}
									{assign var="fieldcount" value=$fieldcount+1}
								
									{if $fieldlength eq 2}
										<td colspan="4" style="padding-top:5px">
									{else}
										<td colspan="2" style="padding-top:5px" width="50%">
									{/if}
									
									{if $readonly eq 99}
										{assign var="DIVCLASS" value="dvtCellInfoOff"}
										{assign var=TEMPLATE value='DisplayFieldsReadonly.tpl'}
									{else}
										{if ($MODE eq '' || $MODE eq 'create') && $keymandatory}
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
									
									{if $fldname eq 'eventstatus'}
										<div>
											<div class="dvtCellLabel"><label for="eventstatus">{$LABEL.eventstatus}</label></div>
											<div class="dvtCellInfo">
												{if $LABEL.eventstatus neq ''}
													<select name="eventstatus" id="eventstatus" class="detailedViewTextBox" onChange="getSelectedStatus();">
			                                               {foreach item=arr from=$ACTIVITYDATA.eventstatus}
													 		{if $arr[0] eq $APP.LBL_NOT_ACCESSIBLE}
			                                                   	<option value="{$arr[0]}" {$arr[2]}>{$arr[0]}</option>
															{else}
			                                                   	<option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
															{/if}
														{/foreach}
													</select>
												{/if}
											</div>
										</div>
									{elseif $fldname eq 'description'}
										<div class="dvtCellLabel">
											<label for="description">{$MOD.Description}</label>
										</div>
										<div class="dvtCellInfo">
											<textarea id="description" name="description" class="detailedViewTextBox" style="resize:vertical">{$ACTIVITYDATA.description}</textarea>
										</div>
									{else}
										{include file=$TEMPLATE}
									{/if}
									
									{if $fieldlength eq 2}
										{assign var="fieldcount" value=$fieldcount+1}
									{/if}
										
									{if $fieldcount eq 2}
										</tr>
										{assign var="fieldcount" value=0}
										{assign var="tr_state" value=0}
									{/if}
								{/if}
							{/if}
						{/if}
					{/foreach}
				{/foreach}
			</tbody>
		{/foreach}
		{* crmv@104568e *}
	</table>
</div>

<div class="col-xs-12 nopadding">
	{include file="modules/Calendar/CustomFields.tpl"}
</div>

<div class="col-xs-12 divider"></div>

<div class="col-xs-12 nopadding">
	{if $ACTIVITY_MODE eq 'Task'}
		{include file="modules/Calendar/TaskDurationBlock.tpl"}
	{else}
		{include file="modules/Calendar/EventDurationBlock.tpl"}
	{/if}
</div>