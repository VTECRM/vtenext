<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

$idstring = rtrim($_REQUEST['idstring'],";");
$out = '';

if($_REQUEST['export_record'] == true) {
	
	//This conditions user can select includesearch & (all |currentpage|selecteddata) but not search
	if (($_REQUEST['search_type'] == 'includesearch' && $_REQUEST['export_data'] == 'all') && VteSession::get('export_where') == '') {
		$out = 'NOT_SEARCH_WITHSEARCH_ALL';
	} elseif(($_REQUEST['search_type'] == 'includesearch' && $_REQUEST['export_data'] == 'currentpage') && VteSession::get('export_where') == '') {
		$out = 'NOT_SEARCH_WITHSEARCH_CURRENTPAGE';
	} elseif(($_REQUEST['search_type'] == 'includesearch' && $_REQUEST['export_data'] == 'selecteddata') && $idstring == '') {
		$out = 'NO_DATA_SELECTED';
	}
	//This conditions user can select withoutsearch & (all |currentpage|selecteddata) but  search
	elseif (($_REQUEST['search_type'] == 'withoutsearch' && $_REQUEST['export_data'] == 'all') && VteSession::get('export_where') != '') {
		$out = 'SEARCH_WITHOUTSEARCH_ALL';
	} elseif(($_REQUEST['search_type'] == 'withoutsearch' && $_REQUEST['export_data'] == 'currentpage') && VteSession::get('export_where') != '') {
		$out = 'SEARCH_WITHOUTSEARCH_CURRENTPAGE';
	} elseif(($_REQUEST['search_type'] == 'withoutsearch' && $_REQUEST['export_data'] == 'selecteddata') && $idstring == '') {
		$out = 'NO_DATA_SELECTED';
	}
}

die($out);