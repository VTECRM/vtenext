<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/** This function returns the sales order object populated with the details from trouble ticket.
* Param $focus - Sales order object
* Param $tticket_focus - Trouble ticket focus
* Param $tticketid - trouble ticket id
* Return type is an object array or an error string if an error is found
*   Err_NoAccountContact
*   Err_ContactWithoutAccount
*/

function getConvertTTicketToSoObject($focus,$tticket_focus,$tticketid)
{
    global $log;
    global $adb,$table_prefix;
    $log->info("in getConvertTTicketToSoObject ".$tticketid);

    $InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

    if (empty($tticket_focus->column_fields['parent_id'])) {
            return 'Err_NoAccountContact';
    } else {
        $crmid_query = "SELECT setype FROM ".$table_prefix."_crmentity WHERE crmid=".$tticket_focus->column_fields['parent_id'];
        $result = $adb->query($crmid_query);
        if ($adb->query_result($result,0,'setype')=='Contacts') {
            $focus->column_fields['contact_id'] = $tticket_focus->column_fields['parent_id'];
            $accountid_query = "SELECT accountid FROM ".$table_prefix."_contactdetails WHERE contactid=".$tticket_focus->column_fields['parent_id'];
            $acntrst = $adb->query($accountid_query);
            $accountid=$adb->query_result($acntrst,0,'accountid');
            if (empty($accountid)) {
                return 'Err_ContactWithoutAccount';
            }
        } else {
            $accountid=$tticket_focus->column_fields['parent_id'];
        }
        $focus->column_fields['account_id'] = $accountid;
    }
    $focus->column_fields['assigned_user_id'] = $tticket_focus->column_fields['assigned_user_id'];
    // Change next line for any special logic of SO reference generation, e.g. automatic sequential number
    $focus->column_fields['subject'] = $tticket_focus->column_fields['ticket_title'];

    $acct_focus = CRMEntity::getInstance('Accounts');
    $acct_focus->retrieve_entity_info($accountid,"Accounts");
    $focus->column_fields['bill_city']    = $acct_focus->column_fields['bill_city'];
    $focus->column_fields['bill_street']  = $acct_focus->column_fields['bill_street'];
    $focus->column_fields['bill_state']   = $acct_focus->column_fields['bill_state'];
    $focus->column_fields['bill_code']    = $acct_focus->column_fields['bill_code'];
    $focus->column_fields['bill_country'] = $acct_focus->column_fields['bill_country'];
    $focus->column_fields['bill_pobox']   = $acct_focus->column_fields['bill_pobox'];
    $focus->column_fields['ship_city']    = (empty($acct_focus->column_fields['ship_city']) ? $acct_focus->column_fields['bill_city'] : $acct_focus->column_fields['ship_city']);
    $focus->column_fields['ship_street']  = (empty($acct_focus->column_fields['ship_street']) ? $acct_focus->column_fields['bill_street'] : $acct_focus->column_fields['ship_street']);
    $focus->column_fields['ship_state']   = (empty($acct_focus->column_fields['ship_state']) ? $acct_focus->column_fields['bill_state'] : $acct_focus->column_fields['ship_state']);
    $focus->column_fields['ship_code']    = (empty($acct_focus->column_fields['ship_code']) ? $acct_focus->column_fields['bill_code'] : $acct_focus->column_fields['ship_code']);
    $focus->column_fields['ship_country'] = (empty($acct_focus->column_fields['ship_country']) ? $acct_focus->column_fields['bill_country'] : $acct_focus->column_fields['ship_country']);
    $focus->column_fields['ship_pobox']   = (empty($acct_focus->column_fields['ship_pobox']) ? $acct_focus->column_fields['bill_pobox'] : $acct_focus->column_fields['ship_pobox']);

    $focus->column_fields['description'] = $tticket_focus->column_fields['description'];

    // Create associated inventory records
    // First we eliminate any that may be there
    $query ="DELETE FROM ".$table_prefix."_inventoryproductrel where id=?";
    $adb->pquery($query, array($tticketid));//crmv@208173
    // now we insert a new record for each InvoiceLine with a product
    //crmv@fix isnull
    // crmv@150773
    $query ="SELECT product_id,tcunits,".$table_prefix."_timecards.description
             FROM ".$table_prefix."_timecards
             INNER JOIN ".$table_prefix."_crmentity on crmid=timecardsid
             WHERE ticket_id=? and timecardtype='InvoiceLine' and product_id is not null and deleted=0";
    //crmv@fix isnull end
    $rs=$adb->pquery($query, array($tticketid));//crmv@208173
    for ($r=0;$r<$adb->num_rows($rs);$r) {
        $prodid=$adb->query_result($rs,$r,0);
        if (isset($prodid) and is_numeric($prodid)) {
            $nunit = ($adb->query_result($rs,$r,1)==0 ? 1 : $adb->query_result($rs,$r,1));
            $query ='INSERT INTO ".$table_prefix."_inventoryproductrel(id, productid, quantity, listprice, tax1, comment)';
            $query.=" VALUES(?, ?, ?, ?, ?, ?)";
            $adb->pquery($query, array($tticketid, $prodid, $nunit, getUnitPrice($prodid), $InventoryUtils->getProductTaxPercentage('tax1',$prodid,'default'), $adb->query_result($rs,$r,2)));
        }
    }

    $log->debug("Exiting getConvertTTicketToSoObject method ...");
    return $focus;
}

?>