<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/**
* File created by SAKTI PRASAD MISHRA on 31 oct, 2007.
* This file is included within "DetailView.tpl" to provide SESSION value to smarty template
*/
VteSession::start();
$aAllBlockStatus = VteSession::get('BLOCKINITIALSTATUS');
$this->assign("BLOCKINITIALSTATUS",$aAllBlockStatus);