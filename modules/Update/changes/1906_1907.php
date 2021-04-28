<?php
$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('modules.emails.send_mail_queue', false);

require_once('include/utils/CronUtils.php');
$CU = CronUtils::getInstance();
$cj = CronJob::getByName('MessagesSendNotifications');
if (empty($cj)) {
	$cj = new CronJob();
	$cj->name = 'MessagesSendNotifications';
	$cj->active = 1;
	$cj->singleRun = false;
	$cj->fileName = 'cron/modules/Messages/SendNotifications.service.php';
	$cj->timeout = 300;
	$cj->repeat = 60;
	$cj->maxAttempts = 0;
	$CU->insertCronJob($cj);
}

if(!Vtiger_Utils::CheckTable($table_prefix.'_emails_not_send_queue')) {
	$schema = '<?xml version="1.0"?>
				<schema version="0.3">
				  <table name="'.$table_prefix.'_emails_not_send_queue">
				  <opt platform="mysql">ENGINE=InnoDB</opt>
				    <field name="id" type="I" size="19">
				      <KEY/>
				    </field>
					<field name="to_email" type="X"/>
					<field name="from_name" type="C" size="200" />
					<field name="from_email" type="C" size="200" />
					<field name="subject" type="C" size="200" />
				    <field name="other_params" type="XL"/>
					<field name="send_after" type="T">
				      <DEFAULT value="0000-00-00 00:00:00"/>
				    </field>
					<field name="queue_date" type="T">
				      <DEFAULT value="0000-00-00 00:00:00"/>
				    </field>
					<field name="send_date" type="T">
				      <DEFAULT value="0000-00-00 00:00:00"/>
				    </field>
				    <field name="status" type="I" size="11"/>
					<field name="error" type="C" size="200" />
				    <index name="emails_not_send_queue_status_idx">
				      <col>status</col>
				    </index>
				  </table>
				</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

if(!Vtiger_Utils::CheckTable($table_prefix.'tbl_s_enqueue_targets')) {
	$schema = '<?xml version="1.0"?>
				<schema version="0.3">
					<table name="tbl_s_enqueue_targets">
						<opt platform="mysql">ENGINE=InnoDB</opt>
						<field name="newsletterid" type="I" size="19">
							<KEY/>
						</field>
						<field name="userid" type="I" size="19">
							<KEY/>
						</field>
						<field name="crmid" type="I" size="19">
							<KEY/>
						</field>
					</table>
				</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}