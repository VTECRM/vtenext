<?php 

global $adb, $table_prefix;

// crmv@187823

$adb->addColumnToTable($table_prefix.'_sharedcalendar', 'only_occ', 'I(1)', 'NOTNULL DEFAULT 0');

if(!Vtiger_Utils::CheckTable("{$table_prefix}_activity_organizer")) {
	$schema = '<?xml version="1.0"?>
				<schema version="0.3">
				  <table name="'.$table_prefix.'_activity_organizer">
				  <opt platform="mysql">ENGINE=InnoDB</opt>
				    <field name="activityid" type="I" size="19">
				      <KEY/>
				    </field>
				    <field name="userid" type="I" size="19" />
				    <field name="contactid" type="I" size="19" />
				    <field name="email" type="C" size="100" />
				    <index name="activity_org_userid">
				      <col>userid</col>
				    </index>
				    <index name="activity_org_contactid">
				      <col>contactid</col>
				    </index>
				    <index name="activity_org_email">
				      <col>email</col>
				    </index>
				  </table>
				</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
	
	// now fill the table
	$adb->query(
		"INSERT INTO {$table_prefix}_activity_organizer (activityid, userid) 
		SELECT a.activityid, c.smownerid FROM {$table_prefix}_activity a
		LEFT JOIN {$table_prefix}_crmentity c ON c.crmid = a.activityid"
	);
}

SDK::setUitype('49','modules/SDK/src/49/49.php','modules/SDK/src/49/49.tpl','modules/SDK/src/49/49.js');


$fields = array(
	'organizer' => array('module'=>'Events','block'=>'LBL_EVENT_INFORMATION','name'=>'organizer','label'=>'Organizer','uitype'=>'49', 'table' => $table_prefix.'_activity_organizer', 'column' => 'email', 'readonly' => 99),
);
	
Update::create_fields($fields);

$trans = array(
	'Calendar' => array(
		'it_it' => array(
			'Organizer' => 'Organizzatore',
		),
		'en_us' => array(
			'Organizer' => 'Organizer',
		),
	),
	'Users' => array(
		'it_it' => array(
			'LBL_CALENDAR_SHARING_OCC' => 'Condividi calendario con (solo occupazione)',
		),
		'en_us' => array(
			'LBL_CALENDAR_SHARING_OCC' => 'Share calendar with (only occupation)',
		),
	),
);
$languages = vtlib_getToggleLanguageInfo();
foreach ($trans as $module => $modlang) {
	foreach ($modlang as $lang => $translist) {
		if (array_key_exists($lang, $languages)) {
			foreach ($translist as $label => $translabel) {
				SDK::setLanguageEntry($module, $lang, $label, $translabel);
			}
		}
	}
}
