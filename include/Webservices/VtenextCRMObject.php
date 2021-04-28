<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class VtenextCRMObject{

    private $moduleName ;
    private $moduleId ;
    private $instance ;

    function __construct($moduleCredential, $isId=false){

        if($isId){
            $this->moduleId = $moduleCredential;
            $this->moduleName = $this->getObjectTypeName($this->moduleId);
        }else{
            $this->moduleName = $moduleCredential;
            $this->moduleId = $this->getObjectTypeId($this->moduleName);
        }
        $this->instance = null;
        $this->getInstance();
    }

    public function getModuleName(){
        return $this->moduleName;
    }

    public function getModuleId(){
        return $this->moduleId;
    }

    public function getInstance(){
        if($this->instance == null){
            $this->instance = $this->getModuleClassInstance($this->moduleName);
        }
        return $this->instance;
    }

    public function getObjectId(){
        if($this->instance==null){
            $this->getInstance();
        }
        return $this->instance->id;
    }

    public function setObjectId($id){
        if($this->instance==null){
            $this->getInstance();
        }
        $this->instance->id = $id;
    }

    private function titleCase($str){
        $first = substr($str, 0, 1);
        return strtoupper($first).substr($str,1);
    }

    private function getObjectTypeId($objectName){

        // Use getTabid API
        $tid = getTabid($objectName);

        if($tid === false) {
            global $adb,$table_prefix;

            $sql = "select * from ".$table_prefix."_tab where name=?"; //crmv@fix;
            $params = array($objectName);
            $result = $adb->pquery($sql, $params);
            $data1 = $adb->fetchByAssoc($result,1,false);

            $tid = $data1["tabid"];
        }
        // END

        return $tid;

    }

    private function getModuleClassInstance($moduleName){
        return CRMEntity::getInstance($moduleName);
    }

    private function getObjectTypeName($moduleId){

        return getTabModuleName($moduleId);

    }

    private function getTabName(){
        return $this->moduleName;
    }

    public function read($id){
        global $adb;

        $error = false;
        $adb->startTransaction();
        $this->instance->retrieve_entity_info($id,$this->getTabName(), false); // crmv@31780 - prevent die here
        $error = $adb->hasFailedTransaction();
        $adb->completeTransaction();

        // crmv@205306 moved in Products class

        return !$error;
    }

    public function create($element){
        global $adb;

        $error = false;
        foreach($element as $k=>$v){
            $this->instance->column_fields[$k] = $v;
        }

        // crmv@80883
        // salvo i prodotti (provenienti da WS)
        if (isInventoryModule($this->moduleName) && is_array($element['product_block'])) {
            $this->productInfoToRequest($element['product_block']);
        }
        // crmv@80883e

        // crmv@48267
        if ($this->moduleName == 'Events' && is_array($element['invitees'])) {
            $this->attendeesInfoToRequest($element['invitees']);
        }

        // crmv@93301 crmv@205306e
        if (($this->moduleName == 'Products' || $this->moduleName == 'Services') && is_array($element['taxclass'])) {
            $this->productTaxInfoToRequest($element['taxclass']);
        }
        // crmv@93301e crmv@205306e

        $adb->startTransaction();
        $this->instance->Save($this->getTabName());
        $error = $adb->hasFailedTransaction();
        $adb->completeTransaction();

        if ($this->moduleName == 'Events' && is_array($element['invitees'])) {
            $this->updatePartecipations($this->instance->id);
        }
        // crmv@48267e

        return !$error;
    }

    public function update($element){

        global $adb;
        $error = false;

        foreach($element as $k=>$v){
            $this->instance->column_fields[$k] = $v;
        }

        // crmv@31780
        // salvo i prodotti (provenienti da WS)
        if (isInventoryModule($this->moduleName) && is_array($element['product_block'])) {
            $this->productInfoToRequest($element['product_block']);
        }
        // crmv@31780e

        // crmv@48267
        if ($this->moduleName == 'Events' && is_array($element['invitees'])) {
            $this->attendeesInfoToRequest($element['invitees']);
        }

        // crmv@93301 crmv@205306
        if (($this->moduleName == 'Products' || $this->moduleName == 'Services') && is_array($element['taxclass'])) {
            $this->productTaxInfoToRequest($element['taxclass']);
        }
        // crmv@93301e crmv@205306e

        $adb->startTransaction();
        $this->instance->mode = "edit";
        $this->instance->Save($this->getTabName());
        $error = $adb->hasFailedTransaction();
        $adb->completeTransaction();

        if ($this->moduleName == 'Events' && is_array($element['invitees'])) {
            $this->updatePartecipations($this->instance->id);
        }
        // crmv@48267e

        return !$error;
    }

    // crmv@48267

    // convert info about attendees into params in $_REQUEST to fit Activity::save_module's format
    // pass an array of array('email'=> 'invitee@email.com', 'partecipation' => 0/1/2
    protected function attendeesInfoToRequest($attendees) {
        // fields: email, partecipation

        $list = array(
            'Users' => array(),
            'Contacts' => array(),
        );
        $this->partecipations = array();

        // find invitees from email
        foreach ($attendees as $attend) {
            if (!empty($attend['email'])) {
                $records = $this->lookForInvitee($attend['email']);
                foreach ($records as $record) {
                    if ($record['crmid'] > 0) {
                        $list[$record['module']][] = $record['crmid'];
                        $this->partecipations[$record['module']][$record['crmid']] = $attend['partecipation'];
                    }
                }
            }
        }

        // add the to the request
        if (empty($list['Users'])) {
            $_REQUEST['inviteesid'] = '--none--';
        } else {
            $_REQUEST['inviteesid'] = implode(';', $list['Users']);
        }

        if (empty($list['Contacts'])) {
            $_REQUEST['inviteesid_con'] = '--none--';
        } else {
            $_REQUEST['inviteesid_con'] = implode(';', $list['Contacts']);
        }
    }

    protected function updatePartecipations($activityid) {
        global $adb, $table_prefix;

        $tablemap = array(
            'Users' => "{$table_prefix}_invitees",
            'Contacts' => "{$table_prefix}_invitees_con",
        );

        if (is_array($this->partecipations)) {
            foreach ($this->partecipations as $mod => $list) {
                $table = $tablemap[$mod];
                if (empty($table)) continue;
                foreach ($list as $inviteeid => $part) {
                    $adb->pquery("update $table set partecipation = ? where activityid = ? and inviteeid = ?", array($part, $activityid, $inviteeid));
                }
            }
        }
    }

    // returns crmid of a visible user or contact with this email
    // returns array of Array('module'=>..., 'crmid'=>...)
    public function lookForInvitee($email, $onlyOne = false, $searchAccounts = false) { // crmv@68357 crmv@189222
        global $adb, $table_prefix, $current_user;

        $list = array();

        require('user_privileges/requireUserPrivileges.php');
        require('user_privileges/sharing_privileges_'.$current_user->id.'.php');

        if ($is_admin==false && $profileGlobalPermission[2] == 1 && ($defaultOrgSharingPermission[$this->moduleId] == 3 or $defaultOrgSharingPermission[$this->moduleId] == 0)) {
            $private = 'private';
        } else {
            $private = '';
        }

        // check in users

        $requestModSaved = $_REQUEST['module'];
        $_REQUEST['module'] = $this->moduleName;
        $users = get_user_array(false, 'Active', '', $private);
        $_REQUEST['module'] = $requestModSaved;
        if (is_array($users)) {
            $ids = array();
            foreach ($users as $id=>$u) {
                $ids[] = $id;
            }

            // crmv@68357
            if ($onlyOne) {
                $res = $adb->limitpQuery("select id from {$table_prefix}_users where (email1 = ? or email2 = ?) and id in (".generateQuestionMarks($ids).")", 0, 1, array($email, $email, $ids));
            } else {
                $res = $adb->pquery("select id from {$table_prefix}_users where (email1 = ? or email2 = ?) and id in (".generateQuestionMarks($ids).")", array($email, $email, $ids));
            }
            // crmv@68357e
            if ($res) {
                while ($row = $adb->FetchByAssoc($res, -1, false)) {
                    $list[] = array('module'=>'Users', 'crmid'=>$row['id']);
                }
            }
        }

        if ($onlyOne && count($list) > 0) return $list; // crmv@68357

        //crmv@189222
        if ($searchAccounts) {
            $res = $adb->limitpQuery(
                "SELECT u.id 
				FROM {$table_prefix}_users u
				INNER JOIN {$table_prefix}_messages_account ma ON ma.userid = u.id
				WHERE u.status = ? AND ma.email = ? AND u.id IN (".generateQuestionMarks($ids).")",
                0, $onlyOne ? 1 : 100,
                array('Active', $email, $ids)
            );
            // crmv@68357e
            if ($res) {
                while ($row = $adb->FetchByAssoc($res, -1, false)) {
                    $list[] = array('module'=>'Users', 'crmid'=>$row['id']);
                }
            }

            if ($onlyOne && count($list) > 0) return $list; // crmv@68357
        }
        //crmv@189222e

        // check in Contacts
        $queryGenerator = QueryGenerator::getInstance('Contacts', $current_user);
        $queryGenerator->addField('email');

        $searchRequest['search_fields'] = array_flip(array('email'=>'email'));
        $searchRequest['search_text'] = $email;
        $queryGenerator->addUserSearchConditions($searchRequest);

        $query = $queryGenerator->getQuery();
        $query = preg_replace('/^select /i', "select {$table_prefix}_crmentity.crmid, ", $query);

        // execute query (by default, max 100 results)
        $res = $adb->limitQuery($query, 0, $onlyOne ? 1 : 100); // crmv@68357
        if ($res) {
            while ($row = $adb->FetchByAssoc($res, -1, false)) {
                $list[] = array('module' => 'Contacts', 'crmid'=>$row['crmid']);
            }
        }

        return $list;

    }
    // crmv@48267e

    // crmv@31780
    // mette nella request i campi del blocco prodotti provenienti da WS
    protected function productInfoToRequest($prodinfo) {
        $products = $prodinfo['products'];
        $final_details = $prodinfo['final_details'];
        if (is_array($products) && is_array($final_details)) {
            // prezzi globali
            $_REQUEST['totalProductCount'] = count($products);
            $_REQUEST['total'] = $final_details['grandTotal'];
            $_REQUEST['subtotal'] = $final_details['hdnSubTotal'];
            $final_details['adjustment'] = floatval($final_details['adjustment']);
            $_REQUEST['adjustmentType'] = ( $final_details['adjustment'] < 0 ? '-' : '+' );
            foreach ($final_details as $pfield => $prod) {
                $_REQUEST[$pfield] = $prod;
            }
            if ($final_details['taxtype'] == 'group' && is_array($final_details['taxes'])) {
                foreach ($final_details['taxes'] as $taxinfo) {
                    $_REQUEST[$taxinfo['taxname'].'_group_percentage'] = $taxinfo['percentage'];
                }
            }
            if (is_array($final_details['sh_taxes'])) {
                foreach ($final_details['sh_taxes'] as $taxinfo) {
                    $_REQUEST[$taxinfo['taxname'].'_sh_percent'] = $taxinfo['percentage'];
                }
            }

            // prezzi prodotti
            $i = 1;
            foreach ($products as $prod) {
                foreach ($prod as $pfield => $pval) {
                    if ($pfield == 'taxes' && is_array($pval) && $final_details['taxtype'] == 'individual') {
                        foreach ($pval as $taxinfo) {
                            $_REQUEST[$taxinfo['taxname'].'_percentage'.$i] = $taxinfo['percentage'];
                        }
                    } else {
                        $_REQUEST[$pfield.$i] = $pval;
                    }
                }
                $_REQUEST['netPriceInput'.$i] = $prod['lineTotal'];
                if (is_array($prod['hdnProductId']) && !empty($prod['hdnProductId']['crmid'])) $_REQUEST['hdnProductId'.$i] = $prod['hdnProductId']['crmid'];
                ++$i;
            }
            // and remove the ajax from the request, to enable the save
            unset($_REQUEST['ajxaction']); // crmv@80883
        }
    }
    // crmv@31780e

    // crmv@93301
    function productTaxInfoToRequest($taxclass) {
        if (!empty($taxclass) && is_array($taxclass)) {
            foreach ($taxclass as $taxname => $taxvalue) {
                $_REQUEST[$taxname . '_check'] = 1;
                $_REQUEST[$taxname] = floatval($taxvalue);
            }
            unset($_REQUEST['ajxaction']);
        }
    }

    // crmv@205306 moved in Products class

    // crmv@93301e

    public function revise($element){
        global $adb;
        $error = false;

        $error = $this->read($this->getObjectId());
        if($error == false){
            return $error;
        }

        foreach($element as $k=>$v){
            $this->instance->column_fields[$k] = $v;
        }

        //added to fix the issue of utf8 characters
        foreach($this->instance->column_fields as $key=>$value){
            // crmv@120777
            if (is_array($value)) {
                $this->instance->column_fields[$key] = $value;
            } else {
                $this->instance->column_fields[$key] = decode_html($value);
            }
            // crmv@120777e
        }

        // crmv@80883
        // salvo i prodotti (provenienti da WS)
        if (isInventoryModule($this->moduleName) && is_array($element['product_block'])) {
            $this->productInfoToRequest($element['product_block']);
        }
        // crmv@80883e

        // crmv@48267
        if ($this->moduleName == 'Events' && is_array($element['invitees'])) {
            $this->attendeesInfoToRequest($element['invitees']);
        }

        // crmv@93301 crmv@205306
        if (($this->moduleName == 'Products' || $this->moduleName == 'Services') && is_array($element['taxclass'])) {
            $this->productTaxInfoToRequest($element['taxclass']);
        }
        // crmv@93301e crmv@205306e

        $adb->startTransaction();
        $this->instance->mode = "edit";
        $this->instance->Save($this->getTabName());
        $error = $adb->hasFailedTransaction();
        $adb->completeTransaction();

        if ($this->moduleName == 'Events' && is_array($element['invitees'])) {
            $this->updatePartecipations($this->instance->id);
        }
        // crmv@48267e

        return !$error;
    }

    public function delete($id){
        global $adb;
        $error = false;
        $adb->startTransaction();
        DeleteEntity($this->getTabName(), $this->getTabName(), $this->instance, $id,$returnid);
        $error = $adb->hasFailedTransaction();
        $adb->completeTransaction();
        return !$error;
    }

    public function getFields(){
        return $this->instance->column_fields;
    }

    function exists($id){
        global $adb,$table_prefix;

        $exists = false;
        $sql = "select * from ".$table_prefix."_crmentity where crmid=? and deleted=0";
        $result = $adb->pquery($sql , array($id));
        if($result != null && isset($result)){
            if($adb->num_rows($result)>0){
                $exists = true;
            }
        }
        return $exists;
    }

    function getSEType($id){
        global $adb,$table_prefix;

        $seType = null;
        $sql = "select * from ".$table_prefix."_crmentity where crmid=? and deleted=0";
        $result = $adb->pquery($sql , array($id));
        if($result != null && isset($result)){
            if($adb->num_rows($result)>0){
                $seType = $adb->query_result($result,0,"setype");
            }
        }
        return $seType;
    }

}

?>