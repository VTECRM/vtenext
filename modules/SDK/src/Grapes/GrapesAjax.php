<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@205899

$action = $_REQUEST['subaction'];

$GAC = new GrapesAjaxController();
$GAC->process($action, $_REQUEST);
exit();

class GrapesAjaxController {

	public function process($action, $params) {
		$fnName = lcfirst($action);

		if (method_exists($this, $fnName)) {
			return $this->$fnName($params);
		} else {
			return $this->error('Unknown action');
		}
	}

	public function getTemplateValues($request) {
		global $adb, $table_prefix;

		$templateId = intval($request['templateid']);
		$withBody = boolval($request['with_body']);

		if (isPermitted('Newsletter', 'EditView') != 'yes') {
			return $this->error('Operation not permitted.');
		}

		$cols = $adb->getColumnNames($table_prefix . '_emailtemplates');
		if (!$withBody) {
			$cols = array_flip($cols);
			unset($cols['body']);
			$cols = array_flip($cols);
		}
		$adb->format_columns($cols);

		$selectStatement = implode(', ', $cols);

		$res = $adb->pquery("SELECT {$selectStatement} FROM {$table_prefix}_emailtemplates WHERE deleted = 0 AND templateid = ?", [$templateId]);
		$row = $adb->fetchByAssoc($res, -1, false);

		if (empty($row)) {
			return $this->error('The template doesn\'t exist.');
		}

		return $this->success(['data' => $row]);
	}

	public function error($message) {
		$out = array('success' => false, 'error' => $message);
		$this->rawOutput($out);
	}

	public function success($data = array()) {
		$out = array_merge(array('success' => true), $data);
		$this->rawOutput($out);
	}

	public function rawOutput($data) {
		echo json_encode($data);
	}

}