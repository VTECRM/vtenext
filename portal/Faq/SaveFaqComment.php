<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

 /* crmv@173271 TODO: migrate to class */

//This is to save the comment made by the customer

$faqid = $_REQUEST['faqid'];
$comment = $_REQUEST['comments'];
$customerid = $_SESSION['customer_id'];
$sessionid = $_SESSION['customer_sessionid'];

//commented customer should be added as author for the comment
$params = Array(Array('id' => "$customerid", 'sessionid'=>"$sessionid", 'faqid'=>"$faqid", 'comment'=>"$comment"));
$result = $client->call('save_faq_comment', $params, $Server_Path, $Server_Path);
