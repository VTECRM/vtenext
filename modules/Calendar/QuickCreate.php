<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

	$activity_mode = $_REQUEST['activity_mode'];
        if(isset($_REQUEST['activity_mode']) && $_REQUEST['activity_mode'] == 'Events')
        {
                $tab_type = 'Events';
                $module = 'Events';
        }

        $tab_id=getTabid($tab_type);
	require_once('include/quickcreate.php');