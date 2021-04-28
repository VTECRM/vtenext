{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@158871 *} {* crmv@181170 *}
{foreach item=dataBlock from=$BLOCKS}
	{foreach key=label item=subdata from=$dataBlock.fields}
		{foreach key=mainlabel item=maindata from=$subdata}
			{if $maindata[2][0] eq $FIELDNAME}
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
				
				{include file=$TEMPLATE}
			{/if}
		{/foreach}
	{/foreach}
{/foreach}