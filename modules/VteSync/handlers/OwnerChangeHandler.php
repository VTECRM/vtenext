<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* not used at the moment */
class OwnerChangeHandler extends VTEventHandler {

	public $tablename = 'vtesync_ownerchanges';

	function handleEvent($eventName, $entityData) {
		global $adb, $table_prefix, $current_user;

		if($eventName == 'vte.entity.aftersave') {//crmv@207852
			if ($current_user->id == 0) {
				return false;
			}

			$this->setupTable();

			$moduleName = $entityData->getModuleName();
			$recordId = $entityData->getId();

			if (in_array($moduleName, array('ModNotifications', 'ChangeLog'))) return;

			$vtEntityDelta = new VTEntityDelta();

			$oldOwner = $vtEntityDelta->getOldValue($moduleName, $recordId, 'assigned_user_id');
			$newOwner = $vtEntityDelta->getCurrentValue($moduleName, $recordId, 'assigned_user_id');

			if ($oldOwner != $newOwner) {
				$this->insertIntoTable($recordId, $oldOwner, $newOwner);
			}

		}
	}

	function insertIntoTable($crmid, $oldOwner, $newOwner) {
		global $adb, $table_prefix;

		/*$res = $adb->pquery("select crmid from {$this->tablename} where crmid = ?", array($crmid));
		if ($res && $adb->num_rows($res) > 0) {
			// update
			$adb->pquery("update {$this->tablename} set prev_smownerid = ?, next_smownerid = ?, changetime = ? where crmid = ?", array($oldOwner, $newOwner, date('Y-m-d H:i:s'), $crmid));
		} else {
			// insert
			$module = getSalesEntityType($crmid);
			$adb->pquery("insert into {$this->tablename} (crmid, module, prev_smownerid, next_smownerid, changetime) values (?,?,?,?,?)", array($crmid, $module, $oldOwner, $newOwner, date('Y-m-d H:i:s')));
		}*/

		$module = getSalesEntityType($crmid);
		$id = $adb->getUniqueId($this->tablename);
		$adb->pquery("insert into {$this->tablename} (id, crmid, module, prev_smownerid, next_smownerid, changetime) values (?, ?,?,?,?,?)", array($id, $crmid, $module, $oldOwner, $newOwner, date('Y-m-d H:i:s')));
	}

	function setupTable() {
		global $adb, $table_prefix;
		$table = $this->tablename;

		$schema_table =
		'<schema version="0.3">
			<table name="'.$table.'">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="id" type="R" size="19">
					<KEY/>
				</field>
				<field name="crmid" type="R" size="19" />
				<field name="module" type="C" size="127" />
				<field name="prev_smownerid" type="R" size="19" />
				<field name="next_smownerid" type="R" size="19" />
				<field name="changetime" type="T">
					<DEFAULT value="0000-00-00 00:00:00"/>
                </field>
                <index name="vsync_ochange_crmid">
                	<col>crmid</col>
                </index>
                <index name="vsync_ochange_module">
                	<col>module</col>
                </index>
                <index name="vsync_ochange_owner1">
                	<col>prev_smownerid</col>
                </index>
                <index name="vsync_ochange_owner2">
                	<col>next_smownerid</col>
                </index>
			</table>
		</schema>';
		if(!Vtecrm_Utils::CheckTable($table)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}

	}
}

?>