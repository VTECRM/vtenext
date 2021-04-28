<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
if(!function_exists('addColumnToQueryView')){
	function addColumnToQueryView($column,$sdk_mode,&$list_query,&$columns) {
		if ($sdk_mode == 'popup_query') {
			$add = ','.$column;
		    $from = substr($list_query, stripos($list_query,' from '),strlen($list_query));
			$select = substr($list_query, 0, stripos($list_query,' from '));
			$list_query = $select.$add.$from;
		} elseif ($sdk_mode == 'list_related_query') {
			$columns[] = $column;
		}
	}
}
if (is_array($sdk_columns) && !empty($sdk_columns)) {
	foreach($sdk_columns as $column) {
		addColumnToQueryView($column,$sdk_mode,$list_query,$columns);
	}
}
?>