{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<table class="vtetable vtetable-props mb-3 mx-auto" style="width:50%">
	<tr>
		<td class="cellLabel text-nowrap">{'LBL_TOTAL_RECORDS_IMPORTED'|@getTranslatedString:$MODULE}</td>
		<td class="cellText">{$IMPORT_RESULT.IMPORTED} / {$IMPORT_RESULT.TOTAL}</td>
	</tr>
	<tr>
		<td class="cellLabel text-nowrap">{'LBL_NUMBER_OF_RECORDS_CREATED'|@getTranslatedString:$MODULE}</td>
		<td class="cellText">{$IMPORT_RESULT.CREATED}</td>
	</tr>
	<tr>
		<td class="cellLabel text-nowrap">{'LBL_NUMBER_OF_RECORDS_UPDATED'|@getTranslatedString:$MODULE}</td>
		<td class="cellText">{$IMPORT_RESULT.UPDATED}</td>
	</tr>
	<tr>
		<td class="cellLabel text-nowrap">{'LBL_NUMBER_OF_RECORDS_SKIPPED'|@getTranslatedString:$MODULE}</td>
		<td class="cellText">{$IMPORT_RESULT.SKIPPED}</td>
	</tr>
	<tr>
		<td class="cellLabel text-nowrap">{'LBL_NUMBER_OF_RECORDS_MERGED'|@getTranslatedString:$MODULE}</td>
		<td class="cellText">{$IMPORT_RESULT.MERGED}</td>
	</tr>
	<tr>
		<td class="cellLabel text-nowrap">{'LBL_TOTAL_RECORDS_FAILED'|@getTranslatedString:$MODULE}</td>
		<td class="cellText">{$IMPORT_RESULT.FAILED} / {$IMPORT_RESULT.TOTAL}</td>
	</tr>
</table>