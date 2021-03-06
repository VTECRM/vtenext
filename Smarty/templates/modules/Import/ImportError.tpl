{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script language="JavaScript" type="text/javascript" src="include/js/jquery.js"></script>
<script type="text/javascript" charset="utf-8">
	jQuery.noConflict();
</script>
<script language="JavaScript" type="text/javascript" src="modules/Import/resources/Import.js"></script>

<input type="hidden" name="module" value="{$FOR_MODULE}" />
<table style="width:70%;margin-left:auto;margin-right:auto;margin-top:10px;" cellpadding="10" cellspacing="10" class="searchUIBasic">
	<tr>
		<td class="heading2" align="left">
			{'LBL_IMPORT'|@getTranslatedString:$MODULE} - {'LBL_ERROR'|@getTranslatedString:$MODULE}
		</td>
	</tr>
	<tr>
		<td valign="top">
			<table cellpadding="10" cellspacing="0" align="center" class="dvtSelectedCell thickBorder">
				<tr>
					<td class="style1" align="left" colspan="2">
						{$ERROR_MESSAGE}
					</td>
				</tr>
				{if $ERROR_DETAILS neq ''}
				<tr>
					<td class="errorMessage" align="left" colspan="2">
						{'ERR_DETAILS_BELOW'|@getTranslatedString:$MODULE}
						<table cellpadding="5" cellspacing="0">
						{foreach key=_TITLE item=_VALUE from=$ERROR_DETAILS}
							<tr>
								<td>{$_TITLE}</td>
								<td>-</td>
								<td>{$_VALUE}</td>
							</tr>
						{/foreach}
						</table>
					</td>
				</tr>
				{/if}
			</table>
		</td>
	</tr>
	<tr>
		<td align="right">
		{if $CUSTOM_ACTIONS neq ''}
		{foreach key=_LABEL item=_ACTION from=$CUSTOM_ACTIONS}
			<input type="button" name="{$_LABEL}" value="{$_LABEL|@getTranslatedString:$MODULE}"
				   onclick="{$_ACTION}" class="crmButton small create" />
		{/foreach}
		{/if}
		<input type="button" name="goback" value="{'LBL_GO_BACK'|@getTranslatedString:$MODULE}"
			   onclick="window.history.back()" class="crmButton small edit" />
		</td>
	</tr>
</table>