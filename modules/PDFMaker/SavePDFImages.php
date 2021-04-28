<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/* crmv@208173 */

global $adb,$table_prefix;
$crmid = $_REQUEST["pid"];

$sql="DELETE FROM ".$table_prefix."_pdfmaker_images WHERE crmid=?";
$adb->pquery($sql,array($crmid));

foreach($_REQUEST as $key=>$value)
{
    if(strpos($key, "img_")!==false && $value!="no_image")
    {
        list($bin,$productid,$sequence) = explode("_", $key);
        $width=$_REQUEST["width_".$productid."_".$sequence];
        $height=$_REQUEST["height_".$productid."_".$sequence];
        if(!is_numeric($width) || $width>999)
            $width=0;
        if(!is_numeric($height) || $height>999)
            $height=0;

        $sql="INSERT INTO {$table_prefix}_pdfmaker_images (crmid, productid, sequence, attachmentid, width, height) VALUES(?, ?, ?, ? ,?, ?)";
        $adb->pquery($sql, array($crmid, $productid, $sequence, $value, $width, $height));
    }
}

exit;