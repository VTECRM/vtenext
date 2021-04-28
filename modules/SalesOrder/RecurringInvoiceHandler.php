<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/


class RecurringInvoiceHandler extends VTEventHandler
{
    public function handleEvent($handlerType, $entityData)
    {
        global $adb, $table_prefix;
        $moduleName = $entityData->getModuleName();
        if ($moduleName == 'SalesOrder' && $_REQUEST['action'] != 'MassEditSave') { //crmv@36637
            $soId = $entityData->getId();
            $data = $entityData->getData();
            if ($data['enable_recurring'] == 'on' || $data['enable_recurring'] == 1) {
                $frequency = $data['recurring_frequency'];
                //crmv@58522
                $endPeriod = getValidDBInsertDateValue($data['end_period']);
                $startPeriod = getValidDBInsertDateValue($data['start_period']);
                //crmv@58522e
                $paymentDuration = $data['payment_duration'];
                $invoiceStatus = $data['invoicestatus'];
                if (isset($frequency) && $frequency != '' && $frequency != '--None--') {
                    $checkQuery = "SELECT * FROM " . $table_prefix . "_invoice_recurring_info WHERE salesorderid=?";
                    $checkRes = $adb->pquery($checkQuery, array($soId));
                    $numberOfRows = $adb->num_rows($checkRes);
                    if ($numberOfRows > 0) {
                        $query = "UPDATE " . $table_prefix . "_invoice_recurring_info SET recurring_frequency=?, start_period=?, end_period=?, payment_duration=?, invoice_status=? WHERE salesorderid=?";
                        $params = array($frequency, $startPeriod, $endPeriod, $paymentDuration, $invoiceStatus, $soId);
                    } else {
                        $query = "INSERT INTO " . $table_prefix . "_invoice_recurring_info (salesorderid, recurring_frequency, start_period, end_period, last_recurring_date, payment_duration, invoice_status)  VALUES (?,?,?,?,?,?,?)";
                        $params = array($soId, $frequency, $startPeriod, $endPeriod, $startPeriod, $paymentDuration, $invoiceStatus);
                    }
                    $adb->pquery($query, $params);
                }
            } else {
                $query = "DELETE FROM " . $table_prefix . "_invoice_recurring_info WHERE salesorderid = ?";
                $adb->pquery($query, array($soId));
            }
        }
    }
}

?>