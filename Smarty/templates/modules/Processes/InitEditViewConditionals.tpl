{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@112297 *}
{if $MODULE neq 'Processes' && $ENABLE_CONDITIONALS eq true}
	<script src="{"modules/Processes/Processes.js"|resourcever}" type="text/javascript"></script> {* crmv@176621 *}
	<input type="hidden" id="enable_conditionals" value="1">
	<script type="text/javascript">
		// crmv@134058
		{if $CONDITIONAL_FIELDS}
		var condition_fields = {$CONDITIONAL_FIELDS|@json_encode};
		{else}
		var condition_fields = null;
		{/if}
		ProcessScript.initEditViewConditionals(condition_fields);
		// crmv@134058e
	</script>
{/if}