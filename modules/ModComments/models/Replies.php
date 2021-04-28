<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@59626 */

class ModComments_RepliesModel {
	
	private $data = array();
	private $ids = array();
	
	function __construct($commentid, $searchkey='', $replyRows='') { //crmv@31301
		global $adb;
		$moduleName = 'ModComments';
		if(vtlib_isModuleActive($moduleName)) {
			if (is_array($replyRows)) {
				$replyRows = $replyRows[$commentid];
				if (!empty($replyRows)) {
					foreach($replyRows as $r) {
						$replyModel = new ModComments_ReplyModel($r,$searchkey);	//crmv@31301
						$this->data[] = $replyModel;
						$this->ids[] = $replyModel->id();
					}
				}
			} else {
				$entityInstance = CRMEntity::getInstance($moduleName);
				
				$where = " AND $entityInstance->table_name.parent_comments = ?";
				$query = $entityInstance->getListQuery($moduleName, $where, true, true);	//crmv@32429
				
				$queryCriteria .= sprintf(" ORDER BY %s.%s", $entityInstance->table_name, $entityInstance->table_index);
				$query .= $queryCriteria;
				
				$result = $adb->pquery($query, array($commentid));
	
				if($adb->num_rows($result)) {
					while($resultrow = $adb->fetch_array($result)) {
						$replyModel = new ModComments_ReplyModel($resultrow,$searchkey);	//crmv@31301
						$this->data[] = $replyModel;
						$this->ids[] = $replyModel->id();
					}
				}
			}
		}
	}
	
	function getReplies() {
		return $this->data;
	}
	
	function getRepliesIds() {
		return $this->ids;
	}
}
class ModComments_ReplyModel extends ModComments_CommentsModel {
	
	private $max_replies_for_comment = 5;
	
	function __construct($datarow, $searchkey='') {	//crmv@31301
		$this->data = $datarow;
		$this->searchkey = $searchkey;	//crmv@31301
	}
	
	function getMaxRepliesForComment() {
		return $this->max_replies_for_comment;
	}
}
?>