<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'modules/WSAPP/synclib/connectors/BaseConnector.php';

abstract class WSAPP_TargetConnector extends WSAPP_BaseConnector{

	public function transformToTargetRecord($sourceRecords){
		$destinationRecordList = array();
		foreach($sourceRecords as $record){
			$destinationRecord = clone $record;

			$destinationRecord->setId($record->getOtherAppId());
			$destinationRecord->setOtherAppId($record->getId());

			$destinationRecord->setModifiedTime($record->getOtherAppModifiedTime());
			$destinationRecord->setOtherAppModifiedTIme($record->getModifiedTime());
			$destinationRecordList[] = $destinationRecord;
		}
		return $destinationRecordList;
	}
	public function transformToSourceRecord($targetRecords){
		$sourceRcordList = array();
		foreach($targetRecords as $record){
			$sourceRecord = clone $record;

			$sourceRecord->setId($record->getOtherAppId())
						 ->setOtherAppId($record->getId())
						 ->setModifiedTime($record->getOtherAppModifiedTime())
						 ->setOtherAppModifiedTIme($record->getModifiedTime());

			$sourceRcordList[] = $sourceRecord;
		}
		return $sourceRcordList;
	}
}
?>