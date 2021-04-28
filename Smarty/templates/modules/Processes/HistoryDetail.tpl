{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@188364 *}
{if $type eq 'condition'}
	{if isset($info.module)}
		{$info.module}
	{elseif isset($info.related_to_name)}
		{'LBL_CONDITION_ON'|getTranslatedString:'Processes'} <a href="{$info.related_to_url}" title="{$info.related_to_module}">{$info.related_to_name}</a> ({$info.related_to_module}).
	{/if}
	{if isset($info.execution_condition)}
		{$info.execution_condition}{if empty($info.condition)}.{/if}
	{/if}
	{if isset($info.condition)}
		{'LBL_WHEN'|getTranslatedString|strtolower}: {' '|implode:$info.condition}.
	{/if}
{elseif $type eq 'actions'}
	{foreach item=action from=$info}
		{$action.title}
		{if isset($action.related_to_name)}
			<a href="{$action.related_to_url}" title="{$action.related_to_module}">{$action.related_to_name}</a> ({$action.related_to_module})
		{/if}
		{if isset($action.delete_perm)}
			<a href="javascript:;" onclick="ProcessScript.deleteRecord('{$PROCESSESID}','{$elementid}','{$action.module}','{$action.crmid}',this)"><i class="vteicon md-sm" title="{'LBL_DELETE'|getTranslatedString}" style="vertical-align:middle">clear</i></a>
		{/if}
	{/foreach}
{elseif $type eq 'process_helper'}
	<img src="{$info.userimg}" alt="{$info.username}" title="{$info.username}" class="userAvatar">
	<b>{$info.username}</b> {$info.action_label} {$info.description}
	{if isset($info.related_to_name)}
		{'LBL_ABOUT'|getTranslatedString:'ModComments'} <a href="{$info.related_to_url}" title="{$info.related_to_module}">{$info.related_to_name}</a> ({$info.related_to_module})
	{/if}
{elseif $type eq 'subprocess'}
	{$info.str}
	{if isset($info.link)}
		<a href="{$info.link}">{$info.name}</a>
	{else}
		{$info.name}
	{/if}
{elseif $type eq 'delay' || $type eq 'start'}
	{$info.str}
{elseif $type eq 'gateway'}
	{foreach item=action from=$info}
		{$action.label} <b>{$action.to}</b><br>
	{/foreach}
{/if}