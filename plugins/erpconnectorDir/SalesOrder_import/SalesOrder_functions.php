<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
function do_import_salesorder($time_start,$import_id=false, $dbobject = null) {
	global $log,$adb,$table_prefix,$seq_log,$current_user,$mapping,$root_directory,$erpconnector_dir;
	global $external_code,$module,$table,$fields_auto_create,$fields_auto_update;
	global $where,$update_field,$additional_fields,$mapping_entity,$internal_code,$time_field;
	global $order_by,$fields_jump_update,$limit;
	global $override_query, $override_query_row;
	if (is_null($dbobject) || empty($dbobject)) $dbobject = $adb;

	if (count($additional_fields)> 1)
		$add_f = ",".implode(",",$additional_fields);
	elseif(count($additional_fields)>0)
		$add_f = ",".$additional_fields[0];
	$focus = CRMEntity::getInstance($module);
	$res_tot = Array();
	$process = false;

	$sql="select ".implode(",",array_keys($mapping))." $add_f from $table $where $order_by";
	if (!empty($override_query)) $sql = $override_query;

	$res = $dbobject->limitquery($sql,0,1);
	if ($res && $dbobject->num_rows($res) == 1){
		$process = true;
	}
	if ($process){
		$adb->startTransaction(); //crmv@154174
		$import = new importer($module,$mapping,$external_code,$time_start,$fields_auto_create,$fields_auto_update,$table,$import_id,$fields_jump_update); // crmv@186024
		if ($limit){
			$res_tot=$import->go($dbobject->limitquery($sql,0,$limit));
		}
		else{
			$res_tot=$import->go($dbobject->query($sql));
		}
		$adb->completeTransaction(); //crmv@154174
	}
	else{
		$res_tot = Array(
			'records_created'=>0,
			'records_updated'=>0,
			'records_deleted'=>0,
		);
	}
	//ora importo anche tutte le righe!
	global $mapping_row,$table_row,$module_row,$additional_fields_row,$where_row,$order_by_row,$limit_row,$dbconfig,$external_code_product;
	// crmv@48699
	global $IUtils;
	$IUtils = InventoryUtils::getInstance();
	$IUtils->workingPrecision = $IUtils->outputPrecision = 3;
	// crmv@48699e
	if($table_row != '') { // rimosso controllo su table_row dato che e' su altro db
		if (count($additional_fields_row)> 1)
			$add_f = ",".implode(",",$additional_fields_row);
		elseif(count($additional_fields_row)>0)
			$add_f = ",".$additional_fields_row[0];
		$process = false;

		$sql_row = "select ".implode(",",array_keys($mapping_row))." $add_f from $table_row $where_row $order_by_row";
		if (!empty($override_query_row)) $sql_row = $override_query_row;

		$res = $dbobject->limitquery($sql_row,0,1);
		if ($res && $dbobject->num_rows($res) == 1){
			$process = true;
		}
		if ($process){
			$sql_file_update_name = $root_directory.$erpconnector_dir."sql/".$module."_sql_update_row_".$import_id.".sql";
			@unlink($sql_file_update_name);
			$sql_file_update = fopen($sql_file_update_name,'w+');

			$table = $table_prefix.'_inventoryproductrel';
			if (!is_array($external_code)) {
				$fields_key = array($external_code=>$mapping[$external_code]);
			} else {
				foreach($external_code as $ext_cod) {
					$fields_key[$ext_cod] = $mapping[$ext_cod];
				}
			}

			if ($limit_row){
				$res = $dbobject->limitquery($sql_row,0,$limit_row);
			}
			else{
				$res = $dbobject->query($sql_row);
			}
			$id_tmp = 0;

			while($row = $dbobject->fetchByAssoc($res,-1,false)){
				$id = get_existing_entity_runtime_unique_offline($module,$fields_key,$row);
				if ($id !==false){
					//gestione eliminazione e conteggio totali - i
					$delete = false;
					if ($id_tmp == 0) {
						$id_tmp = $id;
						$delete = true;
					}
					if ($id_tmp != $id) {	//serve per individuare i blocchi prodotti legati a ogni diversa entit�
						$id_tmp = $id;
						calculate_inventory_totals($module,$focus,$sql_file_update,array_filter($totals));
						$totals = array();
						$delete = true;
					}
					if ($delete) {
						fwrite($sql_file_update,"delete from $table where id = $id;\n");
					}
					//gestione eliminazione e conteggio totali - e
					$real_update = Array();
					//ora prendo il prodotto
					$productid = get_existing_entity_runtime_unique_offline('Products',$external_code_product,$row);
					if ($productid !==false){ //esiste
						$real_update['productid'] = $productid;
						$real_update['id'] = $id;
						$real_update['incrementondel'] = 0;
						$real_update['relmodule'] = $module; // crmv@48699
						$real_update['lineitem_id'] = $adb->getUniqueID($table); // crmv@48699
						// now calculate the other fields
						foreach ($mapping_row as $f=>$f2){
							switch ($f2){
								case 'id':
								case 'productid':
								case 'incrementondel':
								case 'lineitem_id':
									$update_value = $real_update[$f2];
									break;
								/*
								case 'listprice':
									$update_value = round($row['val_net']/$row['q_actual'],3);
									break;
								case 'date_request':
									$update_value = substr($row[$f],0,10);
									break;
								*/
								default:
									$update_value = $row[$f];
									break;
							}
							$real_update[$f2] = $update_value;
						}

						// crmv@48699
						$prodPrices = $IUtils->calcProductTotals($real_update);
						$prodPrices['id'] = $id;
						if (!isset($real_update['total_notaxes'])) $real_update['total_notaxes'] = $prodPrices['price_discount'];
						if (!isset($real_update['linetotal'])) $real_update['linetotal'] = $prodPrices['price_taxes'];
						// crmv@48699e

						$sql_ins = "insert into $table (".implode(",",array_keys($real_update)).") values (".generateQuestionMarks($real_update).")";
						$sql_ins=$adb->convert2Sql($sql_ins,$adb->flatten_array($real_update));
						fwrite($sql_file_update,$sql_ins.";\n");
						$totals[] = $prodPrices; // crmv@48699
					}
				}
			}
			//gestione eliminazione e conteggio totali - i
			calculate_inventory_totals($module,$focus,$sql_file_update,array_filter($totals));
			$totals = array();
			//gestione eliminazione e conteggio totali - e
			fclose($sql_file_update);
			if (filesize($sql_file_update_name)>0){
				$filename = $sql_file_update_name;
				if ($adb->isMySQL()) {
					$port = str_replace(":","",$dbconfig['db_port']);
					$string_update = "mysql -h {$dbconfig['db_server']} -u {$dbconfig['db_username']} --password=\"{$dbconfig['db_password']}\" -P {$port} {$dbconfig['db_name']} < {$filename}";
					system($string_update,$result);
					if ($result != '0'){
						$str_err = "Error updating file {$sql_file_update_name}";
						$log->fatal($str_err);
					}
				} else {
					$handle = fopen($filename, "r");
					//$contents = fread($handle, filesize($filename));
					while (!feof($handle)) // Loop til end of file.
					{
						$contents = $buffer = fgets($handle, 4096);
						$adb->query($contents);
					}
					fclose($handle);
				}
				@unlink($sql_file_update_name);
			}
		}
	}
	return $res_tot;
}
?>