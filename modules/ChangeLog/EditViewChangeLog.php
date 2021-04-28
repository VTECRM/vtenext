<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@171832 */
class EditViewChangeLog {
	
	protected static $currentid = '';
	protected static $table = '_editview_changelog';
	public static $skip_modules = array('MorphsuitServer');
	
	public static function store_editview($record,$userid,$col_fields){
		if (empty(self::$currentid)){
			self::$currentid = md5(uniqid(rand(), true));
			global $adb, $table_prefix;
			$params = Array(
				'etag' => self::$currentid,
				'userid' => $userid,
				'record' => $record,
				'column_fields' => Zend_Json::encode($col_fields),
				'createdtime' => $adb->formatDate(date("Y-m-d H:i:s"),true)
			);
			$sql = "insert into ".$table_prefix.self::$table." (".implode(",",array_keys($params)).") values (".generateQuestionMarks($params).")";
			$adb->pquery($sql,$params);
		}
	}
	public static function markdelete_editview($currentid){
		global $adb, $table_prefix;
		$sql = "update ".$table_prefix.self::$table." set status = 1 where etag = ?";
		$adb->pquery($sql,Array($currentid));
	}
	public static function get_currentid(){
		return self::$currentid;
	}
	public static function set_currentid($currentid){
		self::$currentid = $currentid;
	}
	public static function get_data(){
		$data = Array();
		if (!empty(self::$currentid)){
			global $adb, $table_prefix;
			$sql = "select column_fields from ".$table_prefix.self::$table." where etag = ?";
			$res = $adb->pquery($sql,Array(self::$currentid));
			if ($res && $adb->num_rows($res)>0){
				$data = @Zend_Json::decode($adb->query_result_no_html($res,0,'column_fields'));
			}
		}
		return $data;
	}
	public static function clean_etag(){
		global $adb, $table_prefix;
		$VTEP = VTEProperties::getInstance();
		$clean_interval = $VTEP->getProperty('performance.editview_changelog_clean_interval'); // seconds to wait for cleaning table data
		$data = date("Y-m-d H:i:s",strtotime("-".$clean_interval." seconds"));
		$sql = "delete from ".$table_prefix.self::$table." where createdtime < ? or status = 1";
		$res = $adb->pquery($sql,Array($data));
	}
}