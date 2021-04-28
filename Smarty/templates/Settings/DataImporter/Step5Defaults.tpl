{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<div style="visibility: hidden; height: 0px;" id="defaultValuesElementsContainer">
	{foreach key=_FIELD_NAME item=_FIELD_INFO from=$ALLFIELDS}
	<span id="{$_FIELD_NAME}_defaultvalue_container" name="{$_FIELD_NAME}_defaultvalue" class="small">
		{assign var="_FIELD_TYPE" value=$_FIELD_INFO.type}
		{if $_FIELD_TYPE eq 'picklist' || $_FIELD_TYPE eq 'multipicklist'}
			<select id="{$_FIELD_NAME}_defaultvalue" name="{$_FIELD_NAME}_defaultvalue" class="small">
			{foreach item=_PICKLIST_DETAILS from=$_FIELD_INFO.picklistdetails}
				<option value="{$_PICKLIST_DETAILS.value}">{$_PICKLIST_DETAILS.label|@getTranslatedString:$FOR_MODULE}</option>
			{/foreach}
			</select>
		{elseif $_FIELD_TYPE eq 'integer' || $_FIELD_TYPE eq 'double'}
			<input type="text" id="{$_FIELD_NAME}_defaultvalue" name="{$_FIELD_NAME}_defaultvalue" class="small" value="0" />
		{elseif $_FIELD_TYPE eq 'owner' || $_FIELD_INFO.uitype eq '52'}
			<select id="{$_FIELD_NAME}_defaultvalue" name="{$_FIELD_NAME}_defaultvalue" class="small">
				<option value="">--{'LBL_NONE'|@getTranslatedString:$FOR_MODULE}--</option>
			{foreach key=_ID item=_NAME from=$USERS_LIST}
				<option value="{$_ID}">{$_NAME}</option>
			{/foreach}
			{if $_FIELD_INFO.uitype eq '53'}
				{foreach key=_ID item=_NAME from=$GROUPS_LIST}
				<option value="{$_ID}">{$_NAME}</option>
				{/foreach}
			{/if}
			</select>
		{elseif $_FIELD_TYPE eq 'date'}
			{* crmv@190519 *}
			<input type="text" id="{$_FIELD_NAME}_defaultvalue" name="{$_FIELD_NAME}_defaultvalue" class="detailedViewTextBox input-inline" value="" />
			<i class="vteicon md-link md-text" id="jscal_trigger_{$_FIELD_NAME}">event</i>
			<script type="text/javascript">
				(function() {ldelim}
					setupDatePicker('{$_FIELD_NAME}_defaultvalue', {ldelim}
						trigger: 'jscal_trigger_{$_FIELD_NAME}',
						date_format: "YYYY-MM-DD",
						language: "{$APP.LBL_JSCALENDAR_LANG}",
					{rdelim});
				{rdelim})();
			</script>
			{* crmv@190519e *}
		{elseif $_FIELD_TYPE eq 'datetime'}
			{* crmv@190519 *}
			<input type="text" id="{$_FIELD_NAME}_defaultvalue" name="{$_FIELD_NAME}_defaultvalue" class="detailedViewTextBox input-inline" value="" />
			<i class="vteicon md-link md-text" id="jscal_trigger_{$_FIELD_NAME}">event</i>
			<script type="text/javascript">
				(function() {ldelim}
					setupDatePicker('{$_FIELD_NAME}_defaultvalue', {ldelim}
						trigger: 'jscal_trigger_{$_FIELD_NAME}',
						date_format: "YYYY-MM-DD",
						language: "{$APP.LBL_JSCALENDAR_LANG}",
					{rdelim});
				{rdelim})();
			</script>
			{* crmv@190519e *}
		{elseif $_FIELD_TYPE eq 'boolean'}
			<input type="checkbox" id="{$_FIELD_NAME}_defaultvalue" name="{$_FIELD_NAME}_defaultvalue" class="small" value="1" /> {* crmv@93741 *}
		{elseif $_FIELD_TYPE neq 'reference'}
			<input type="input" id="{$_FIELD_NAME}_defaultvalue" name="{$_FIELD_NAME}_defaultvalue" class="small" />
		{/if}
	</span>
	{/foreach}
</div>