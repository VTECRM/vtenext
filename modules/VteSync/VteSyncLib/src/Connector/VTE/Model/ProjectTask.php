<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@190016 */

namespace VteSyncLib\Connector\VTE\Model;

class ProjectTask extends GenericVTERecord {

	protected static $staticModule = 'ProjectTask';
	
	protected static $fieldMap = array(
		// VTE => CommonRecord
		'projecttaskname' => 'subject',
		'projecttaskpriority' => 'priority',
		'Project' => 'projectid',
		'projectid' => 'projectid',
		'startdate' => 'start_date',
		'enddate' => 'end_date',
		'description' => 'description',
		'assigned_user_id'=> 'assignee',
	);
	
	// if needed, you can override methods and change fields/behaviour	
}