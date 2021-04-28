<?php
/* crmv@198038 */
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('vtlib/Vtecrm/Utils.php');

class Vtiger_Utils extends Vtecrm_Utils
{

    static function isNumber($value)
    {
        logDeprecated('Deprecated method isNumber called in Vtiger_Utils');
        return parent::isNumber($value);
    }

    static function implodestr($prefix, $count, $suffix = false)
    {
        logDeprecated('Deprecated method implodestr called in Vtiger_Utils');
        return parent::implodestr($prefix, $count, $suffix);
    }

    static function checkFileAccess($filepath, $dieOnFail = true)
    {
        logDeprecated('Deprecated method checkFileAccess called in Vtiger_Utils');
        return parent::checkFileAccess($filepath, $dieOnFail);
    }

    static function Log($message, $delimit = true)
    {
        parent::Log($message, $delimit);
        logDeprecated('Deprecated method Log called in Vtiger_Utils');
    }

    static function SQLEscape($value)
    {
        logDeprecated('Deprecated method SQLEscape called in Vtiger_Utils');
        return parent::SQLEscape($value);
    }

    static function CheckTable($tablename)
    {
        logDeprecated('Deprecated method CheckTable called in Vtiger_Utils');
        return parent::CheckTable($tablename);
    }

    static function CreateTable($tablename, $criteria, $suffixTableMeta = false, $temporary = false, $skipClearCache = false)
    {
        parent::CreateTable($tablename, $criteria, $suffixTableMeta, $temporary, $skipClearCache);
        logDeprecated('Deprecated method CreateTable called in Vtiger_Utils');
    }

    static function AlterTable($tablename, $criteria)
    {
        parent::AlterTable($tablename, $criteria);
        logDeprecated('Deprecated method AlterTable called in Vtiger_Utils');
    }

    static function AddColumn($tablename, $columnname, $criteria)
    {
        parent::AddColumn($tablename, $columnname, $criteria);
        logDeprecated('Deprecated method AddColumn called in Vtiger_Utils');
    }

    static function ExecuteQuery($sqlquery, $supressdie=false)
    {
        parent::ExecuteQuery($sqlquery, $supressdie);
        logDeprecated('Deprecated method ExecuteQuery called in Vtiger_Utils');
    }

    static function ExecuteSchema($schema, $supressdie=false) {
        parent::ExecuteSchema($schema, $supressdie);
        logDeprecated('Deprecated method ExecuteSchema called in Vtiger_Utils');
    }

    static function CreateTableSql($tablename) {
        logDeprecated('Deprecated method CreateTableSql called in Vtiger_Utils');
        return parent::CreateTableSql($tablename);
    }

    static function CreateTableSchema($tablename,$data=false) {
        logDeprecated('Deprecated method CreateTableSchema called in Vtiger_Utils');
        return parent::CreateTableSchema($tablename, $data);
    }

    static function IsCreateSql($sql) {
        logDeprecated('Deprecated method IsCreateSql called in Vtiger_Utils');
        return parent::IsCreateSql($sql);
    }

    static function IsDestructiveSql($sql) {
        logDeprecated('Deprecated method IsDestructiveSql called in Vtiger_Utils');
        return parent::IsDestructiveSql($sql);
    }

    static function CreateIndex($name,$table,$fields,$options=false){
        parent::CreateIndex($name, $table, $fields, $options);
        logDeprecated('Deprecated method CreateIndex called in Vtiger_Utils');
    }
}
