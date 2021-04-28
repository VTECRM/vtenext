{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@98570 *}
{if $sdk_mode eq 'detail'}
	<button title="{$label}" type="button" name="{$keyfldname}" class="crmbutton small" onclick="{$keyval.onclick}">{$label}</button>
	<script type="text/javascript">
	{$keyval.code}
	</script>
{elseif $sdk_mode eq 'edit'}
	<button title="{$fldlabel}" type="button" name="{$fldname}" class="crmbutton small" onclick="{$fldvalue}">{$fldlabel}</button>
	<script type="text/javascript">
	{$secondvalue}
	</script>
{/if}