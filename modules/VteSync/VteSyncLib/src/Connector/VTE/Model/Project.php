<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@190016 */

namespace VteSyncLib\Connector\VTE\Model;

class Project extends GenericVTERecord {

	protected static $staticModule = 'ProjectPlan';
	
	protected static $fieldMap = array(
		// VTE => CommonRecord
		'projectname' => 'name',
		'startdate' => 'start_date',
		'targetenddate' => 'target_end_date',
		'actualenddate' => 'actual_end_date',
		'projectstatus' => 'status',
		'projecttype' => 'type',
		'projecturl' => 'url',
		'description' => 'description',
		
	);

	// if needed, you can override methods and change fields/behaviour	
}