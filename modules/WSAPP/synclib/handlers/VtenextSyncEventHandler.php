<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/
require_once 'modules/WSAPP/SyncServer.php';
require_once 'modules/WSAPP/Handlers/SyncHandler.php';

class WSAPP_VtenextSyncEventHandler extends SyncHandler
{//crmv@208112

    private $putOperationClientIdAndSyncKeyMapping = array();

    function __construct($appkey)
    {
        $this->syncServer = $this->getSyncServerInstance();
        $this->key = $appkey;
    }

    public function getSyncServerInstance()
    {
        return new SyncServer();
    }

    public function get($module, $token, $user)
    {
        $this->syncModule = $module;
        $this->user = $user;
        $result = $this->syncServer->get($this->key, $module, $token, $user);
        $nativeForamtElementList = $result;
        $nativeForamtElementList['created'] = $this->syncToNativeFormat($result['created']);
        $nativeForamtElementList['updated'] = $this->syncToNativeFormat($result['updated']);
        $nativeForamtElementList['deleted'] = $this->convertedDeletedRecordToNativeFormat($result['deleted']);
        return $nativeForamtElementList;
    }

    public function put($element, $user)
    {
        $this->user = $user;
        $this->storeClientIdAndSynkeyMapping($element);
        $values = $this->syncServer->put($this->key, $element, $user);
        $nativeForamtElementList = $values;
        $nativeForamtElementList['created'] = $this->syncToNativeFormat($values['created']);
        $nativeForamtElementList['updated'] = $this->syncToNativeFormat($values['updated']);
        $nativeForamtElementList['deleted'] = $this->convertedDeletedRecordToNativeFormat($values['deleted']);
        return $nativeForamtElementList;
    }

    public function map($olMapElement, $user)
    {
        $this->user = $user;
        return $this->syncServer->map($this->key, $olMapElement, $user);
    }

    public function nativeToSyncFormat($element)
    {

    }

    public function syncToNativeFormat($recordList)
    {
        $nativeFormatRecordList = array();
        foreach ($recordList as $record) {
            $nativeRecord = $record;
            $nativeRecord['id'] = $record['_id'];
            $nativeRecord['_id'] = $record['id'];
            $nativeRecord['modifiedtime'] = $record['_modifiedtime'];
            $nativeRecord['_modifiedtime'] = $record['modifiedtime'];
            //restoring the synckey which will help synchronize controller to identify the record
            $nativeRecord['_syncidentificationkey'] = $this->putOperationClientIdAndSyncKeyMapping[$nativeRecord['_id']];
            $nativeFormatRecordList[] = $nativeRecord;
        }
        return $nativeFormatRecordList;
    }

    public function convertedDeletedRecordToNativeFormat($deletedRecords)
    {
        $nativeDeletedRecordFormat = array();
        foreach ($deletedRecords as $deletedRecord) {
            $deletedRecordResponse = array();
            $deletedRecordResponse['_id'] = $deletedRecord;
            $deletedRecordResponse['_syncidentificationkey'] = $this->putOperationClientIdAndSyncKeyMapping[$deletedRecord];
            $nativeDeletedRecordFormat[] = $deletedRecord;
        }
        return $nativeDeletedRecordFormat;
    }

    /**
     * Keeps the mapping of client id and synckey
     */
    public function storeClientIdAndSynkeyMapping($records)
    {
        foreach ($records as $record) {
            $this->putOperationClientIdAndSyncKeyMapping[$record['id']] = $record['values']['_syncidentificationkey'];
        }
    }
}

?>