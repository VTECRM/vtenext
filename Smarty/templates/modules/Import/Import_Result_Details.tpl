{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<table cellpadding="10" cellspacing="0" align="center" class="dvtSelectedCell thickBorder">
	<tr>
		<td>{'LBL_TOTAL_RECORDS_IMPORTED'|@getTranslatedString:$MODULE}</td>
		<td width="10%">:</td>
		<td width="30%">{$IMPORT_RESULT.IMPORTED} / {$IMPORT_RESULT.TOTAL}</td>
	</tr>
	<tr>
		<td colspan="3">
			<table cellpadding="10" cellspacing="0" class="calDayHour">
				<tr>
					<td>{'LBL_NUMBER_OF_RECORDS_CREATED'|@getTranslatedString:$MODULE}</td>
					<td width="10%">:</td>
					<td width="10%">{$IMPORT_RESULT.CREATED}</td>
				</tr>
				<tr>
					<td>{'LBL_NUMBER_OF_RECORDS_UPDATED'|@getTranslatedString:$MODULE}</td>
					<td width="10%">:</td>
					<td width="10%">{$IMPORT_RESULT.UPDATED}</td>
				</tr>
				<tr>
					<td>{'LBL_NUMBER_OF_RECORDS_SKIPPED'|@getTranslatedString:$MODULE}</td>
					<td width="10%">:</td>
					<td width="10%">{$IMPORT_RESULT.SKIPPED}</td>
				</tr>
				<tr>
					<td>{'LBL_NUMBER_OF_RECORDS_MERGED'|@getTranslatedString:$MODULE}</td>
					<td width="10%">:</td>
					<td width="10%">{$IMPORT_RESULT.MERGED}</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>{'LBL_TOTAL_RECORDS_FAILED'|@getTranslatedString:$MODULE}</td>
		<td width="10%">:</td>
		<td width="30%">{$IMPORT_RESULT.FAILED} / {$IMPORT_RESULT.TOTAL}</td>
	</tr>
</table>