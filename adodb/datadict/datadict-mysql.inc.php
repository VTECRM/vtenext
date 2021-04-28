<?php

/**
  V5.11 5 May 2010   (c) 2000-2010 John Lim (jlim#natsoft.com). All rights reserved.
  Released under both BSD license and Lesser GPL library license. 
  Whenever there is any discrepancy between the two licenses, 
  the BSD license will take precedence.
	
  Set tabs to 4 for best viewing.
 
*/

// security - hide paths
if (!defined('ADODB_DIR')) die();

class ADODB2_mysql extends ADODB_DataDict {
	var $databaseType = 'mysql';
	var $alterCol = ' CHANGE'; // crmv@fix
	var $alterTableAddIndex = true;
	var $dropTable = 'DROP TABLE IF EXISTS %s'; // requires mysql 3.22 or later
	
	var $dropIndex = 'DROP INDEX %s ON %s';
	var $renameColumn = 'ALTER TABLE %s CHANGE COLUMN %s %s %s';	// needs column-definition!
	
	function MetaType($t,$len=-1,$fieldobj=false)
	{
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}
		$is_serial = is_object($fieldobj) && $fieldobj->primary_key && $fieldobj->auto_increment;
		
		$len = -1; // mysql max_length is not accurate
		switch (strtoupper($t)) {
		case 'STRING': 
		case 'CHAR':
		case 'VARCHAR': 
		case 'TINYBLOB': 
		case 'TINYTEXT': 
		case 'ENUM': 
		case 'SET':
			if ($len <= $this->blobSize) return 'C';
			
		case 'TEXT':
		case 'LONGTEXT': 
		case 'MEDIUMTEXT':
			return 'XL';	//crmv@fix : migrazione db mysql -> oracle
			
		// php_mysql extension always returns 'blob' even if 'text'
		// so we have to check whether binary...
		case 'IMAGE':
		case 'LONGBLOB': 
		case 'BLOB':
		case 'MEDIUMBLOB':
			return !empty($fieldobj->binary) ? 'B' : 'X';
			
		case 'YEAR':
		case 'DATE': return 'D';
		
		case 'TIME':
		case 'TIMESTAMP': return 'T';
		case 'DATETIME': return 'DT'; //crmv@51509
		
		case 'FLOAT':
		case 'DOUBLE':
			return 'F';
			
		case 'INT': 
		case 'INTEGER': return $is_serial ? 'R' : 'I';
		case 'TINYINT': return $is_serial ? 'R' : 'I1';
		case 'SMALLINT': return $is_serial ? 'R' : 'I2';
		case 'MEDIUMINT': return $is_serial ? 'R' : 'I4';
		case 'BIGINT':  return $is_serial ? 'R' : 'I8';
		default: return 'N';
		}
	}

	function ActualType($meta)
	{
		switch(strtoupper($meta)) {
		case 'C': return 'VARCHAR';
		case 'XL':return 'LONGTEXT';
		case 'X': return 'TEXT';
		
		case 'C2': return 'VARCHAR';
		case 'X2': return 'LONGTEXT';
		
		case 'B': return 'LONGBLOB';
			
		case 'D': return 'DATE';
		case 'DT': return 'DATETIME'; //crmv@51509
		case 'TS':
		case 'T': return 'TIMESTAMP'; //crmv@51509
		case 'L': return 'TINYINT';
		
		case 'R':
		case 'I4':
		case 'I': return 'INTEGER';
		case 'I1': return 'TINYINT';
		case 'I2': return 'SMALLINT';
		case 'I8': return 'BIGINT';
		
		case 'F': return 'DOUBLE';
		case 'N': return 'NUMERIC';
		default:
			return $meta;
		}
	}
	
	// return string must begin with space
	function _CreateSuffix($fname,&$ftype,$fnotnull,$fdefault,$fautoinc,$fconstraint,$funsigned)
	{	
		$suffix = '';
		// crmv@fix
		if ($fdefault == "'CURRENT_TIMESTAMP'")
			$fdefault = "CURRENT_TIMESTAMP";
		// crmv@fix-e
		if ($funsigned) $suffix .= ' UNSIGNED';
		if ($fnotnull) $suffix .= ' NOT NULL';
		if (strlen($fdefault)) $suffix .= " DEFAULT $fdefault";
		if ($fautoinc) $suffix .= ' AUTO_INCREMENT';
		if ($fconstraint) $suffix .= ' '.$fconstraint;
		return $suffix;
	}
	
	/*
	CREATE [TEMPORARY] TABLE [IF NOT EXISTS] tbl_name [(create_definition,...)]
		[table_options] [select_statement]
		create_definition:
		col_name type [NOT NULL | NULL] [DEFAULT default_value] [AUTO_INCREMENT]
		[PRIMARY KEY] [reference_definition]
		or PRIMARY KEY (index_col_name,...)
		or KEY [index_name] (index_col_name,...)
		or INDEX [index_name] (index_col_name,...)
		or UNIQUE [INDEX] [index_name] (index_col_name,...)
		or FULLTEXT [INDEX] [index_name] (index_col_name,...)
		or [CONSTRAINT symbol] FOREIGN KEY [index_name] (index_col_name,...)
		[reference_definition]
		or CHECK (expr)
	*/
	
	/*
	CREATE [UNIQUE|FULLTEXT] INDEX index_name
		ON tbl_name (col_name[(length)],... )
	*/
	
	function _IndexSQL($idxname, $tabname, $flds, $idxoptions)
	{
		$sql = array();
		
		if ( isset($idxoptions['REPLACE']) || isset($idxoptions['DROP']) ) {
			if ($this->alterTableAddIndex) $sql[] = "ALTER TABLE $tabname DROP INDEX $idxname";
			else $sql[] = sprintf($this->dropIndex, $idxname, $tabname);

			if ( isset($idxoptions['DROP']) )
				return $sql;
		}
		
		if ( empty ($flds) ) {
			return $sql;
		}
		
		if (isset($idxoptions['FULLTEXT'])) {
			$unique = ' FULLTEXT';
		} elseif (isset($idxoptions['UNIQUE'])) {
			$unique = ' UNIQUE';
		} else {
			$unique = '';
		}
		
		if ( is_array($flds) ) $flds = implode(', ',$flds);
		
		if ($this->alterTableAddIndex) $s = "ALTER TABLE $tabname ADD $unique INDEX $idxname ";
		else $s = 'CREATE' . $unique . ' INDEX ' . $idxname . ' ON ' . $tabname;
		
		$s .= ' (' . $flds . ')';
		
		if ( isset($idxoptions[$this->upperName]) )
			$s .= $idxoptions[$this->upperName];
		
		$sql[] = $s;
		
		return $sql;
	}
	
	function getCreateTableString($tabname,$temporary = false){ // crmv@146653
		if ($temporary)
			return "TEMPORARY TABLE $tabname";
		else
			return parent::getCreateTableString($tabname);	
	}	
	
	//crmv@fix
	/**
	 * Rename one column
	 *
	 * Some DBM's can only do this together with changeing the type of the column (even if that stays the same, eg. mysql)
	 * @param string $tabname table-name
	 * @param string $oldcolumn column-name to be renamed
	 * @param string $newcolumn new column-name
	 * @param string $flds='' complete column-defintion-string like for AddColumnSQL, only used by mysql atm., default=''
	 * @return array with SQL strings
	 */
	function RenameColumnSQL($tabname,$oldcolumn,$newcolumn,$flds='')
	{
		$tabname = $this->TableName ($tabname);
		if ($flds) {
			list($lines,$pkey,$idxs) = $this->_GenFields($flds);
			// genfields can return FALSE at times
			if ($lines == null) $lines = array();
			list(,$first) = each($lines);
			list(,$column_def) = preg_split("/[\t ]+/",$first,2);
		}
		return array(sprintf($this->renameColumn,$tabname,$this->NameQuote($oldcolumn),$this->NameQuote($newcolumn),$column_def));
	}
	//crmv@fix end
	
	
	/**
	"Florian Buzin [ easywe ]" <florian.buzin#easywe.de>
	
	This function changes/adds new fields to your table. You don't
	have to know if the col is new or not. It will check on its own.
	*/
	function ChangeTableSQL($tablename, $flds, $tableoptions = false, $dropOldFlds=false)
	{
	global $ADODB_FETCH_MODE;
	
		$save = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		if ($this->connection->fetchMode !== false) $savem = $this->connection->SetFetchMode(false);
		
		// check table exists
		$save_handler = $this->connection->raiseErrorFn;
		$this->connection->raiseErrorFn = '';
		$cols = $this->MetaColumns($tablename);
		$this->connection->raiseErrorFn = $save_handler;
		
		if (isset($savem)) $this->connection->SetFetchMode($savem);
		$ADODB_FETCH_MODE = $save;
		
		if ( empty($cols)) { 
			return $this->CreateTableSQL($tablename, $flds, $tableoptions);
		}
		
		if (is_array($flds)) {
		// Cycle through the update fields, comparing
		// existing fields to fields to update.
		// if the Metatype and size is exactly the
		// same, ignore - by Mark Newham
			$holdflds = array();
			foreach($flds as $k=>$v) {
				if ( isset($cols[$k]) && is_object($cols[$k]) ) {
					// If already not allowing nulls, then don't change
					$obj = $cols[$k];
					if (isset($obj->not_null) && $obj->not_null)
						$v = str_replace('NOT NULL','',$v);
					if (isset($obj->auto_increment) && $obj->auto_increment && empty($v['AUTOINCREMENT'])) 
					    $v = str_replace('AUTOINCREMENT','',$v);
					
					$c = $cols[$k];
					$ml = $c->max_length;
					$mt = $this->MetaType($c->type,$ml);
					
					if (isset($c->scale)) $sc = $c->scale;
					else $sc = 99; // always force change if scale not known.
					
					if ($sc == -1) $sc = false;
					list($fsize, $fprec) = $this->_getSizePrec($v['SIZE']);

					if ($ml == -1) $ml = '';
					if ($mt == 'X') $ml = $v['SIZE'];
					if (($mt != $v['TYPE']) || ($ml != $fsize || $sc != $fprec) || (isset($v['AUTOINCREMENT']) && $v['AUTOINCREMENT'] != $obj->auto_increment)) {
						$holdflds[$k] = $v;
					}
				} else {
					$holdflds[$k] = $v;
				}		
			}
			$flds = $holdflds;
		}
	

		// already exists, alter table instead
		list($lines,$pkey,$idxs) = $this->_GenFields($flds);
		// genfields can return FALSE at times
		if ($lines == null) $lines = array();
		$alter = 'ALTER TABLE ' . $this->TableName($tablename);
		$sql = array();
		foreach ( $lines as $id => $v ) {
			if ( isset($cols[$id]) && is_object($cols[$id]) ) {
			
				$flds = Lens_ParseArgs($v,',');
				
				//  We are trying to change the size of the field, if not allowed, simply ignore the request.
				// $flds[1] holds the type, $flds[2] holds the size -postnuke addition
				if ($flds && in_array(strtoupper(substr($flds[0][1],0,4)),$this->invalidResizeTypes4)
				 && (isset($flds[0][2]) && is_numeric($flds[0][2]))) {
					if ($this->debug) ADOConnection::outp(sprintf("<h3>%s cannot be changed to %s currently</h3>", $flds[0][0], $flds[0][1]));
					#echo "<h3>$this->alterCol cannot be changed to $flds currently</h3>";
					continue;	 
	 			}
	 			//crmv@fix alter table
	 			$split = explode(" ",$v);
	 			$cnt = 0;
	 			$res_str = '';
	 			foreach ($split as $val){
	 				if ($cnt != 0)
	 					$res_str .=" ";
	 				$res_str .= $val;
	 				if ($cnt == 0)
	 					$res_str.=" ".$val;
	 				$cnt++;	
	 			}
	 			$v=$res_str;
	 			//crmv@fix alter table end
				$sql[] = $alter . $this->alterCol.' '.$v;
			} else {
				$sql[] = $alter . $this->addCol . ' ' . $v;
			}
		}
		if ($dropOldFlds) {
			foreach ( $cols as $id => $v )
			    if ( !isset($lines[$id]) ) 
					$sql[] = $alter . $this->dropCol . ' ' . $v->name;
		}
		return $sql;
	}
	
	// crmv@188001
	function NameQuote($name = NULL,$allowBrackets=false) {
		$name = parent::NameQuote($name, $allowBrackets);
		
		$quote = $this->connection->nameQuote;
		if (is_string($name) && $name[0] !== $quote) {
			require_once('include/utils/db_utils.php');
			if (in_array($name, getMysqlReservedWords())) {
				$name = $quote . $name . $quote;
			}
		}
		
		return $name;
	}
	// crmv@188001e
	
}
