<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include_once('vtlib/Vtecrm/Utils.php');//crmv@207871

if(!Vtecrm_Utils::CheckTable('sdk_language')) {
	global $adb;
	$schema = '<?xml version="1.0"?>
				<schema version="0.3">
				  <table name="sdk_language">
				  <opt platform="mysql">ENGINE=InnoDB</opt>
					<field name="languageid" type="I" size="11">
				   	  <key/>
				    </field>
				    <field name="module" type="C" size="50"/>
				    <field name="language" type="C" size="10"/>
				    <field name="label" type="C" size="200"/>
				    <field name="trans_label" type="X" size="2000"/>
				    <index name="mod_lan">
				      <col>module</col>
				      <col>language</col>
				    </index>
				  </table>
				</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}
if(!Vtecrm_Utils::CheckTable('sdk_uitype')) {
	Vtecrm_Utils::CreateTable(
		'sdk_uitype',
		"uitypeid I(19) PRIMARY,
		uitype I(19),
		src_php C(255),
		src_tpl C(255),
		src_js C(255),
		old_style I(1) DEFAULT 0", 
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_utils')) {
	Vtecrm_Utils::CreateTable(
		'sdk_utils',
		"utilid I(19) PRIMARY,
		src C(255)", 
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_popup_return_funct')) {
	Vtecrm_Utils::CreateTable(
		'sdk_popup_return_funct',
		"id I(19) PRIMARY,
		module C(100), 
		fieldname C(100), 
		src C(255)", 
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_smarty')) {
	Vtecrm_Utils::CreateTable(
		'sdk_smarty',
		"smartyid I(19) PRIMARY,
		params X, 
		src C(255)", 
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_presave')) {
	Vtecrm_Utils::CreateTable(
		'sdk_presave',
		"presaveid I(19) PRIMARY,
		module C(255),
		src C(255)", 
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_popup_query')) {
	Vtecrm_Utils::CreateTable(
		'sdk_popup_query',
		"id I(19) PRIMARY,
		type C(100),
		module C(100),
		param C(100),
		src C(255),
		hidden_rel_fields X",
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_adv_query')) {
	Vtecrm_Utils::CreateTable(
		'sdk_adv_query',
		"id I(19) PRIMARY,
		module C(100),
		function C(255), 
		src C(255)", 
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_adv_permission')) {
	Vtecrm_Utils::CreateTable(
		'sdk_adv_permission',
		"id I(19) PRIMARY,
		module C(100),
		function C(255), 
		src C(255)", 
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_class')) {
	Vtecrm_Utils::CreateTable(
		'sdk_class',
		"id I(19) PRIMARY,
		extends C(100),
		module C(100),
		src C(255)", 
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_view')) {
	Vtecrm_Utils::CreateTable(
		'sdk_view',
		"viewid I(19) PRIMARY,
		module C(100),
		src C(255),
		sequence I(19),
		mode C(100),
		on_success C(100)",
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_extra_src')) {
	Vtecrm_Utils::CreateTable(
		'sdk_extra_src',
		"id I(19) PRIMARY,
		module C(100),
		src C(255)",
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_file')) {
	Vtecrm_Utils::CreateTable(
		'sdk_file',
		"fileid I(19) PRIMARY,
		module C(100),
		file C(100),
		new_file C(100)",
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_home_iframe')) {
	Vtecrm_Utils::CreateTable(
		'sdk_home_iframe',
		"stuffid I(19) PRIMARY,
		size I(11) NOTNULL,
		iframe I(1) NOTNULL DEFAULT 1,
		url C(255)",
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_menu_fixed')) {
	Vtecrm_Utils::CreateTable(
		'sdk_menu_fixed',
		"id I(19) PRIMARY,
		title C(100),
		onclick X,
		image C(100),
		cond C(200)",
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_menu_contestual')) {
	Vtecrm_Utils::CreateTable(
		'sdk_menu_contestual',
		"id I(19) PRIMARY,
		module C(100),
		action C(100),
		title C(100),
		onclick X,
		image C(100),
		cond C(200)",
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_reports')) {
	Vtecrm_Utils::CreateTable(
	  'sdk_reports',
	  "reportid I(19) PRIMARY,
	  reportrun C(255),
	  runclass C(100),
	  jsfunction C(100)",
	true);
}
//crmv@sdk-27926
if(!Vtecrm_Utils::CheckTable('sdk_transitions')) {
	Vtecrm_Utils::CreateTable(
  		'sdk_transitions',
		  "transitionid I(19) PRIMARY,
		  module C(100) NOTNULL,
		  fieldname C(100) NOTNULL,
		  file C(255),
		  function C(100)",
	true);
}
//crmv@sdk-27926e
//crmv@sdk-28873
//crmv@208472
//crmv@sdk-28873e
//crmv@2539m
if(!Vtecrm_Utils::CheckTable('sdk_pdf_cfunctions')) {
	Vtecrm_Utils::CreateTable(
	   'sdk_pdf_cfunctions',
	   "id I(19) PRIMARY,
	   label C(255),
	   name C(255),
	   params X",
	true);
}
//crmv@2539me
//crmv@3079m
if(!Vtecrm_Utils::CheckTable('sdk_home_global_iframe')) {
	Vtecrm_Utils::CreateTable(
		'sdk_home_global_iframe',
		"name C(100) PRIMARY,
		size I(11) NOTNULL,
		iframe I(1) NOTNULL DEFAULT 1,
		url C(255)",
	true);
	
}
//crmv@3079me
//crmv@51605
if(!Vtecrm_Utils::CheckTable('sdk_turbolift_count')) {
	Vtecrm_Utils::CreateTable(
		'sdk_turbolift_count',
		"relation_id I(19) PRIMARY,
		method C(255)",
	true);
	
}
//crmv@51605e
//crmv@92272
if(!Vtecrm_Utils::CheckTable('sdk_processmaker_factions')) {
	Vtecrm_Utils::CreateTable(
		'sdk_processmaker_factions',
		"id I(19) PRIMARY,
		funct C(100),
		src C(255),
		label C(100),
		params X,
		block C(50)",
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_processmaker_tcond')) {
	Vtecrm_Utils::CreateTable(
		'sdk_processmaker_tcond',
		"id I(19) PRIMARY,
		funct C(100),
		src C(255),
		label C(100)",
	true);
}
if(!Vtecrm_Utils::CheckTable('sdk_processmaker_actions')) {
	Vtecrm_Utils::CreateTable(
		'sdk_processmaker_actions',
		"id I(19) PRIMARY,
		funct C(100),
		src C(255),
		label C(100)",
	true);
}
//crmv@92272e
?>