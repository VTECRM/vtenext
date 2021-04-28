<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@26666
function getOracleReservedWords() {
	$reserved_words = array(
		"access","else","modify","start","add","exclusive","noaudit",
		"select","all","exists","nocompress","session","alter","file",
		"not","set","and","float","notfound","share","any","for",
		"nowait","size","arraylen","from","null","smallint","as",
		"grant","number","sqlbuf","asc","group","of","successful",
		"audit","having","offline","synonym","between","identified",
		"on","sysdate","by","immediate","online","table","char","in",
		"option","then","check","increment","or","to","cluster",
		"index","order","trigger","column","initial","pctfree",
		"uid","comment","insert","prior","union","compress",
		"integer","privileges","unique","connect","intersect",
		"public","update","create","into","raw","user","current",
		"is","rename","validate","date","level","resource","values",
		"decimal","like","revoke","varchar","default","lock",
		"row","varchar2","delete","long","rowid","view","desc",
		"maxextents","rowlabel","whenever","distinct","minus",
		"rownum","where","drop","mode","rows","with",
		"shared"); // crmv@165801
	return $reserved_words;
}
//crmv@26666e

function getMsSQLReservedWords() {
	$reserved_words = getOracleReservedWords();
	$reserved_words[] = 'bulk';
	return $reserved_words;
}

// crmv@188001
function getMysqlReservedWords() {
	$reserved_words = array(
		"function", "rows",
	);
	return $reserved_words;
}
// crmv@188001e