<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class VtenextCRMObjectMeta extends EntityMeta {

    private $tabId;

    private $meta;
    private $assign;
    private $hasAccess;
    private $hasReadAccess;
    private $hasWriteAccess;
    private $hasDeleteAccess;
    private $assignUsers;
    var $show_hidden_fields = false;	//crmv@120039

    function __construct($webserviceObject,$user){
        global $table_prefix;
        parent::__construct($webserviceObject,$user);

        $this->columnTableMapping = null;
        $this->fieldColumnMapping = null;
        $this->userAccessibleColumns = null;
        $this->mandatoryFields = null;
        $this->emailFields = null;
        $this->referenceFieldDetails = null;
        $this->ownerFields = null;
        $this->moduleFields = array();
        $this->hasAccess = false;
        $this->hasReadAccess = false;
        $this->hasWriteAccess = false;
        $this->hasDeleteAccess = false;
        $instance = vtws_getModuleInstance($this->webserviceObject);
        $this->idColumn = $instance->tab_name_index[$instance->table_name];
        $this->baseTable = $instance->table_name;
        $this->tableList = $instance->tab_name;
        $this->tableIndexList = $instance->tab_name_index;
        if(in_array($table_prefix.'_crmentity',$instance->tab_name)){
            $this->defaultTableList = array($table_prefix.'_crmentity');
        }else{
            $this->defaultTableList = array();
        }
        $this->tabId = null;
        $this->show_hidden_fields = $webserviceObject->show_hidden_fields;	//crmv@120039
    }

    public function getTabId(){
        if($this->tabId == null){
            //crmv@23687
            //$this->tabId = getTabid($this->objectName);
            $moduleInstance = Vtecrm_Module::getInstance($this->objectName);
            $this->tabId = $moduleInstance->id;
            //crmv@23687e
        }
        return $this->tabId;
    }

    /**
     * returns tabid that can be consumed for database lookup purpose generally, events and
     * calendar are treated as the same module
     * @return Integer
     */
    public function getEffectiveTabId() {
        return getTabid($this->getTabName());
    }

    public function getTabName(){
        if($this->objectName == 'Events'){
            return 'Calendar';
        }
        return $this->objectName;
    }

    private function computeAccess(){

        global $adb,$table_prefix;

        $active = vtlib_isModuleActive($this->getTabName());
        if($active == false){
            $this->hasAccess = false;
            $this->hasReadAccess = false;
            $this->hasWriteAccess = false;
            $this->hasDeleteAccess = false;
            return;
        }

        // crmv@39110
        $userid = $this->user->id;
        require('user_privileges/requireUserPrivileges.php');
        // crmv@39110e
        if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0){
            $this->hasAccess = true;
            $this->hasReadAccess = true;
            $this->hasWriteAccess = true;
            $this->hasDeleteAccess = true;
        }else{

            //TODO get oer sort out the preference among profile2tab and profile2globalpermissions.
            //TODO check whether create/edit seperate controls required for web sevices?
            $profileList = getCurrentUserProfileList();

            $sql = "select * from ".$table_prefix."_profile2globalperm where profileid in (".generateQuestionMarks($profileList).")";
            $result = $adb->pquery($sql,array($profileList));

            $noofrows = $adb->num_rows($result);
            //globalactionid=1 is view all action.
            //globalactionid=2 is edit all action.
            for($i=0; $i<$noofrows; $i++){
                $permission = $adb->query_result($result,$i,"globalactionpermission");
                $globalactionid = $adb->query_result($result,$i,"globalactionid");
                if($permission != 1 || $permission != "1"){
                    $this->hasAccess = true;
                    if($globalactionid == 2 || $globalactionid == "2"){
                        $this->hasWriteAccess = true;
                        $this->hasDeleteAccess = true;
                    }else{
                        $this->hasReadAccess = true;
                    }
                }
            }

            $sql = 'select * from '.$table_prefix.'_profile2tab where profileid in ('.generateQuestionMarks($profileList).') and tabid = ?';
            $result = $adb->pquery($sql,array($profileList,$this->getTabId()));
            $standardDefined = false;
            $permission = $adb->query_result($result,0,"permissions"); // crmv@33018
            if($permission == 1 || $permission == "1"){
                $this->hasAccess = false;
                return;
            }else{
                $this->hasAccess = true;
            }

            //operation=2 is delete operation.
            //operation=0 or 1 is create/edit operation. precise 0 create and 1 edit.
            //operation=3 index or popup. //ignored for websevices.
            //operation=4 is view operation.
            $sql = "select * from ".$table_prefix."_profile2standardperm where profileid in (".generateQuestionMarks($profileList).") and tabid=?";
            $result = $adb->pquery($sql,array($profileList,$this->getTabId()));

            $noofrows = $adb->num_rows($result);
            for($i=0; $i<$noofrows; $i++){
                $standardDefined = true;
                $permission = $adb->query_result($result,$i,"permissions");
                $operation = $adb->query_result($result,$i,"Operation");
                if(!$operation){
                    $operation = $adb->query_result($result,$i,"operation");
                }

                if($permission != 1 || $permission != "1"){
                    $this->hasAccess = true;
                    if($operation == 0 || $operation == "0"){
                        $this->hasWriteAccess = true;
                    }else if($operation == 1 || $operation == "1"){
                        $this->hasWriteAccess = true;
                    }else if($operation == 2 || $operation == "2"){
                        $this->hasDeleteAccess = true;
                    }else if($operation == 4 || $operation == "4"){
                        $this->hasReadAccess = true;
                    }
                }
            }
            if(!$standardDefined){
                $this->hasReadAccess = true;
                $this->hasWriteAccess = true;
                $this->hasDeleteAccess = true;
            }

        }
    }

    function hasAccess(){
        if(!$this->meta){
            $this->retrieveMeta();
        }
        return $this->hasAccess;
    }

    function hasWriteAccess(){
        if(!$this->meta){
            $this->retrieveMeta();
        }
        return $this->hasWriteAccess;
    }

    function hasReadAccess(){
        if(!$this->meta){
            $this->retrieveMeta();
        }
        return $this->hasReadAccess;
    }

    function hasDeleteAccess(){
        if(!$this->meta){
            $this->retrieveMeta();
        }
        return $this->hasDeleteAccess;
    }

    function hasPermission($operation,$webserviceId){

        $idComponents = vtws_getIdComponents($webserviceId);
        $id=$idComponents[1];

        $permitted = isPermitted($this->getTabName(),$operation,$id);
        if(strcmp($permitted,"yes")===0){
            return true;
        }
        return false;
    }

    //crmv@180123
    function hasAssignPrivilege($webserviceId){
        global $adb;

        $idComponents = vtws_getIdComponents($webserviceId);
        $userId=$idComponents[1];
        $ownerTypeId = $idComponents[0];

        if($userId == null || $userId =='' || $ownerTypeId == null || $ownerTypeId ==''){
            return false;
        }

        // administrator's have assign privilege
        if(is_admin($this->user)) return true;

        $webserviceObject = VtenextWebserviceObject::fromId($adb,$ownerTypeId);//crmv@207871
        if(strcasecmp($webserviceObject->getEntityName(),"Users")===0){
            if($userId == $this->user->id){
                return true;
            }
            if(!$this->assign){
                $this->retrieveUserHierarchy();
            }
            if(in_array($userId,array_keys($this->assignUsers))){
                return true;
            }else{
                return false;
            }
        }elseif(strcasecmp($webserviceObject->getEntityName(),"Groups") === 0){
            $tabId = $this->getTabId();
            $groups = vtws_getUserAccessibleGroups($tabId, $this->user);
            foreach ($groups as $group) {
                if($group['id'] == $userId){
                    return true;
                }
            }
            return false;
        }
    }
    //crmv@180123e

    function getUserAccessibleColumns(){

        if(!$this->meta){
            $this->retrieveMeta();
        }
        return parent::getUserAccessibleColumns();
    }

    public function getModuleFields() {
        if(!$this->meta){
            $this->retrieveMeta();
        }
        return parent::getModuleFields();
    }

    function getColumnTableMapping(){
        if(!$this->meta){
            $this->retrieveMeta();
        }
        return parent::getColumnTableMapping();
    }

    function getFieldColumnMapping(){

        if(!$this->meta){
            $this->retrieveMeta();
        }
        if($this->fieldColumnMapping === null){
            $this->fieldColumnMapping =  array();
            foreach ($this->moduleFields as $fieldName=>$webserviceField) {
                if(strcasecmp($webserviceField->getFieldDataType(),'file') !== 0){
                    $this->fieldColumnMapping[$fieldName] = $webserviceField->getColumnName();
                }
            }
            $this->fieldColumnMapping['id'] = $this->idColumn;
        }
        return $this->fieldColumnMapping;
    }

    function getMandatoryFields(){
        if(!$this->meta){
            $this->retrieveMeta();
        }
        return parent::getMandatoryFields();
    }

    function getReferenceFieldDetails(){
        if(!$this->meta){
            $this->retrieveMeta();
        }
        return parent::getReferenceFieldDetails();
    }

    function getOwnerFields(){
        if(!$this->meta){
            $this->retrieveMeta();
        }
        return parent::getOwnerFields();
    }

    function getEntityName(){
        return $this->objectName;
    }

    function getEntityId(){
        return $this->objectId;
    }

    function getEmailFields(){
        if(!$this->meta){
            $this->retrieveMeta();
        }
        return parent::getEmailFields();
    }

    function getFieldIdFromFieldName($fieldName){
        if(!$this->meta){
            $this->retrieveMeta();
        }

        if(isset($this->moduleFields[$fieldName])){
            $webserviceField = $this->moduleFields[$fieldName];
            return $webserviceField->getFieldId();
        }
        return null;
    }

    function retrieveMeta(){

        require_once('modules/CustomView/CustomView.php');
        $current_user = vtws_preserveGlobal('current_user',$this->user);
        $theme = vtws_preserveGlobal('theme',VTWS_PreserveGlobal::getGlobal('theme'));	//crmv@24541
        $default_language = VTWS_PreserveGlobal::getGlobal('default_language');
        $current_language = vtws_preserveGlobal('current_language',VteSession::get('authenticated_user_language'));	//crmv@30166

        $this->computeAccess();

        $cv = CRMEntity::getInstance('CustomView'); // crmv@115329
        $module_info = $cv->getCustomViewModuleInfo($this->getTabName());
        $blockArray = array();
        foreach($cv->module_list[$this->getTabName()] as $label=>$blockList){
            $blockArray = array_merge($blockArray,explode(',',$blockList));
        }
        $this->retrieveMetaForBlock($blockArray);

        $this->meta = true;
        VTWS_PreserveGlobal::flush();
    }

    private function retrieveUserHierarchy(){

        $heirarchyUsers = get_user_array(false,"ACTIVE",$this->user->id);
        $groupUsers = vtws_getUsersInTheSameGroup($this->user->id);
        $this->assignUsers = $heirarchyUsers+$groupUsers;
        $this->assign = true;
    }

    private function retrieveMetaForBlock($block){

        global $adb,$table_prefix,$iAmAProcess;	//crmv@105685

        $tabid = $this->getTabId();
        if (empty($block)) $block = array(0); // crmv@30967
        // crmv@39110
        $userid = $this->user->id;
        require('user_privileges/requireUserPrivileges.php');
        // crmv@39110e
        /* crmv@53053 : tolti 33136,34559,34559  */
        $useFieldsArray = false;
        if (!$this->show_hidden_fields) $sql_show_hidden_fields = " and f.presence in (0,2) and f.readonly != 100";	//crmv@120039 crmv@168924
        if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] ==0 || $iAmAProcess === true){	//crmv@105685
            // crmv@168924
            $sql =
                "SELECT f.*
				FROM {$table_prefix}_field f
				LEFT JOIN {$table_prefix}_blocks b ON b.blockid = f.block
				WHERE f.tabid = ? AND f.block IN (".generateQuestionMarks($block).") AND displaytype in (1,2,3,4)".
                $sql_show_hidden_fields.
                " ORDER BY b.sequence ASC, f.sequence ASC"; // crmv@33097 crmv@55746	crmv@120039
            // crmv@168924e
            $params = array($tabid, $block);

            // crmv@193294
            /* TODO: need to handle the order by
            if ($sql_show_hidden_fields) {
                $filterFn = function($row) use ($block) {
                    $ok = (in_array($row['block'], $block) && $row['displaytype'] >= 1 && $row['displaytype'] <= 4);
                    $ok = $ok && ($row['presence'] == '0' || $row['presence'] == '2' && $row['readonly'] != '100');
                    return $ok;
                };
            } else {
                $filterFn = function($row) use ($block) {
                    $ok = (in_array($row['block'], $block) && $row['displaytype'] >= 1 && $row['displaytype'] <= 4);
                    return $ok;
                };
            }

            $fields = FieldUtils::getFields($tabid, $filterFn);
            $useFieldsArray = true;
            */
            // crmv@193294e

        }else{
            $profileList = getCurrentUserProfileList();
            //crmv@sdk-18508 crmv@33097 crmv@120039 crmv@168924
            //crmv@39110 (used subquery, otherwise there might be duplicates for multiple profiles, unpredictable order with multiple profiles )
            if (count($profileList) > 0) {
                //crmv@60969
                $sql = "SELECT f.*, COALESCE(p2ftemp.profile_sequence, f.sequence) AS profile_sequence
						FROM ".$table_prefix."_field f
						LEFT JOIN {$table_prefix}_blocks b ON b.blockid = f.block
						INNER JOIN ".$table_prefix."_def_org_field ON ".$table_prefix."_def_org_field.fieldid = f.fieldid
						INNER JOIN (
							SELECT DISTINCT fieldid, CASE p.mobile WHEN 1 THEN p2f.sequence ELSE NULL END AS profile_sequence							
							FROM {$table_prefix}_profile p
							INNER JOIN {$table_prefix}_profile2field p2f ON p2f.profileid = p.profileid
							INNER JOIN {$table_prefix}_profile2tab p2t ON p2t.tabid = p2f.tabid AND p2f.profileid = p2t.profileid AND p2t.permissions = 0
							WHERE p2f.profileid in (".generateQuestionMarks($profileList).") AND p2f.tabid = ? AND p2f.visible = 0
						) p2ftemp ON p2ftemp.fieldid = f.fieldid
						WHERE f.tabid = ?
						AND ".$table_prefix."_def_org_field.visible = 0 and f.block in (".generateQuestionMarks($block).") and f.displaytype in (1,2,3,4)".$sql_show_hidden_fields."
						ORDER BY b.sequence ASC, p2ftemp.profile_sequence ASC";
                $params = array($profileList, $tabid, $tabid, $block);
                //crmv@60969e
            } else {
                $sql = "SELECT f.*
						FROM ".$table_prefix."_field f
						INNER JOIN ".$table_prefix."_def_org_field ON ".$table_prefix."_def_org_field.fieldid = f.fieldid
						WHERE f.tabid = ?
						AND ".$table_prefix."_def_org_field.visible = 0 AND f.block in (".generateQuestionMarks($block).") AND f.displaytype in (1,2,3,4)".$sql_show_hidden_fields."
 						AND EXISTS(
							SELECT * FROM ".$table_prefix."_profile2field 
							WHERE ".$table_prefix."_profile2field.fieldid = f.fieldid AND ".$table_prefix."_profile2field.visible = 0
						)
						ORDER BY b.sequence ASC, f.sequence ASC";
                $params = array($tabid, $block);
            }
            //crmv@sdk-18508e crmv@33097e crmv@120039e crmv@39110e crmv@168924e
        }

        // crmv@193294
        if ($useFieldsArray) {
            foreach ($fields as $fld) {
                $fieldname = $fld['fieldname'];
                if(strcasecmp($fieldname,'imagename')===0){
                    continue;
                }
                $webserviceField = WebserviceField::fromArray($adb,$fld);
                $this->moduleFields[$webserviceField->getFieldName()] = $webserviceField;
            }
        } else {
            $result = $adb->pquery($sql,$params);
            $noofrows = $adb->num_rows($result);
            for($i=0; $i<$noofrows; $i++){
                $fieldname = $adb->query_result($result,$i,"fieldname");
                if(strcasecmp($fieldname,'imagename')===0){
                    continue;
                }
                $webserviceField = WebserviceField::fromQueryResult($adb,$result,$i);
                $this->moduleFields[$webserviceField->getFieldName()] = $webserviceField;
            }
        }
        // crmv@193294e

        //crmv@203747
        //adding variant fields from confproducts
        if ($this->getTabName() == 'Products' && vtlib_isModuleActive('ConfProducts')) {
            $res = $adb->pquery("SELECT * FROM {$table_prefix}_field f WHERE f.tabid = ? AND f.fieldname = ?", array($tabid, 'confprodinfo'));
            $rowConfInfo = $adb->FetchByAssoc($res, -1, false);
            $confFocus = CRMEntity::getInstance('ConfProducts');
            $attrs = $confFocus->getAllAttributes();
            foreach ($attrs as $attrinfo) {
                $row = $rowConfInfo;
                $row['fieldlabel'] = $attrinfo['fieldlabel'];
                $row['fieldname'] = $attrinfo['fieldlabel'].'prodattr';
                $webserviceField = WebserviceField::fromArray($adb,$row);
                $this->moduleFields[$webserviceField->getFieldName()] = $webserviceField;
            }
        }
        //crmv@203747e
    }

    function getObjectEntityName($webserviceId){
        global $adb,$table_prefix;

        $idComponents = vtws_getIdComponents($webserviceId);
        $id=$idComponents[1];

        $seType = null;
        if($this->objectName == 'Users'){
            $sql = "select user_name from ".$table_prefix."_users where id=? and deleted=0";
            $result = $adb->pquery($sql , array($id));
            if($result != null && isset($result)){
                if($adb->num_rows($result)>0){
                    $seType = 'Users';
                }
            }
        }else{
            $sql = "select setype from ".$table_prefix."_crmentity where crmid=? and deleted=0"; // crmv@174685
            $result = $adb->pquery($sql , array($id));
            if($result != null && isset($result)){
                if($adb->num_rows($result)>0){
                    $seType = $adb->query_result($result,0,"setype");
                    if($seType == "Calendar"){
                        $seType = vtws_getCalendarEntityType($id);
                    }
                }
            }
            // crmv@174685
            if (empty($seType)) { // search in modules without crmentity (es. Messages)
                // crmv@185647 crmv@192951
                require_once('include/utils/VTEProperties.php');
                $VP = VTEProperties::getInstance();
                $other_modules = $VP->get('performance.modules_without_crmentity');
                foreach($other_modules as $other_module) {
                    $focus = CRMEntity::getInstance($other_module);
                    $check = getSingleFieldValue($focus->table_name, $focus->table_index, $focus->table_index, $id);
                    if ($check == $id) $seType = $other_module;
                }
                // crmv@185647e crmv@192951e
            }
            // crmv@174685e
        }

        return $seType;
    }

    function exists($recordId){
        global $adb,$table_prefix;

        $exists = false;
        $sql = '';
        if ($this->objectName == 'Users') {
            $sql = "SELECT id FROM {$table_prefix}_users WHERE id=? AND deleted=0 AND status='Active'";
            // crmv@174685 crmv@185647 crmv@192951 code removed
        } else {
            $sql = "SELECT crmid FROM {$table_prefix}_crmentity WHERE crmid=? AND deleted=0 AND setype='".$this->getTabName()."'";
        }
        $result = $adb->pquery($sql , array($recordId));
        if($result != null && isset($result)){
            if($adb->num_rows($result)>0){
                $exists = true;
            }
        }
        // crmv@174685 crmv@185647 crmv@192951
        if (!$exists) {
            require_once('include/utils/VTEProperties.php');
            $VP = VTEProperties::getInstance();
            $other_modules = $VP->get('performance.modules_without_crmentity');
            foreach($other_modules as $other_module) {
                $focus = CRMEntity::getInstance($other_module);
                $sql = "SELECT {$focus->table_index} FROM {$focus->table_name} WHERE {$focus->table_index}=? AND deleted=0";
                $result = $adb->pquery($sql , array($recordId));
                if($result != null && isset($result)){
                    if($adb->num_rows($result)>0){
                        $exists = true;
                    }
                }
            }
        }
        // crmv@174685e crmv@185647e crmv@192951e
        return $exists;
    }

    public function getNameFields(){
        global $adb,$table_prefix;

        $query = "select fieldname,tablename,entityidfield from ".$table_prefix."_entityname where tabid = ?";
        $result = $adb->pquery($query, array($this->getEffectiveTabId()));
        $fieldNames = '';
        if($result){
            $rowCount = $adb->num_rows($result);
            if($rowCount > 0){
                $fieldNames = $adb->query_result($result,0,'fieldname');
            }
        }
        return $fieldNames;
    }

    public function getName($webserviceId){

        $idComponents = vtws_getIdComponents($webserviceId);
        $id=$idComponents[1];

        $nameList = getEntityName($this->getTabName(),array($id));
        return $nameList[$id];
    }

    public function getEntityAccessControlQuery(){
        $accessControlQuery = '';
        $instance = vtws_getModuleInstance($this->webserviceObject);
        if($this->getTabName() != 'Users') {
            $accessControlQuery = $instance->getNonAdminAccessControlQuery($this->getTabName(),
                $this->user);
        }
        return $accessControlQuery;
    }

    public function getEntitylistQueryNonAdminChange($query){
        $instance = vtws_getModuleInstance($this->webserviceObject);
        $module = $this->getTabName();
        if($module != 'Users') {
            $query = $instance->listQueryNonAdminChange($query,$module,$scope);
        }
        return $query;
    }

    public function getJoinClause($tableName) {
        $instance = vtws_getModuleInstance($this->webserviceObject);
        return $instance->getJoinClause($tableName);
    }

    public function isModuleEntity() {
        return true;
    }
}
?>