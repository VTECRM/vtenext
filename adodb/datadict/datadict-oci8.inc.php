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

class ADODB2_oci8 extends ADODB_DataDict {
	
	var $databaseType = 'oci8';
	var $seqField = false;
	var $seqPrefix = 'SEQ_';
	var $dropTable = "DROP TABLE %s CASCADE CONSTRAINTS";
	var $trigPrefix = 'TRIG_';
	var $alterCol = ' MODIFY ';
	var $typeX = 'VARCHAR(4000)';
	var $typeXL = 'CLOB';
	
	function MetaType($t,$len=-1)
	function MetaType($t,$len=-1, $fieldobj=false) // crmv@165801
	{
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}
		switch (strtoupper($t)) {
	 	case 'VARCHAR':
	 	case 'VARCHAR2':
		case 'CHAR':
		case 'VARBINARY':
		case 'BINARY':
			if (isset($this) && $len <= $this->blobSize) return 'C';
			return 'X';
		
		case 'NCHAR':
		case 'NVARCHAR2':
		case 'NVARCHAR':
			if (isset($this) && $len <= $this->blobSize) return 'C2';
			return 'X2';
			
		case 'NCLOB':
		case 'CLOB':
			return 'XL';
		
		case 'LONG RAW':
		case 'LONG VARBINARY':
		case 'BLOB':
			return 'B';
		
		case 'TIMESTAMP':
			return 'TS';
			
		case 'DATE':
		case 'DATETIME':	//crmv@51509
			return 'T';		//crmv@51509
		
		case 'INT': 
		case 'SMALLINT':
		case 'INTEGER': 
			return 'I';
			
		default:
			return 'N';
		}
	}
	
 	function ActualType($meta)
	{
		switch($meta) {
		case 'C': return 'VARCHAR';
		case 'X': return $this->typeX;
		case 'XL': return $this->typeXL;
		
		case 'C2': return 'NVARCHAR2';
		case 'X2': return 'NVARCHAR2(4000)';
		
		case 'B': return 'BLOB';
		
		case 'TS':
				return 'TIMESTAMP';
				
		case 'D': 
		case 'T': 
		case 'DT': return 'DATE'; //crmv@51509
		case 'L': return 'NUMBER(1)';
		case 'I1': return 'NUMBER(3)';
		case 'I2': return 'NUMBER(5)';
		case 'I':
		case 'I4': return 'NUMBER(10)';
		
		case 'I8': return 'NUMBER(20)';
		case 'F': return 'NUMBER';
		case 'N': return 'NUMBER';
		case 'R': return 'NUMBER(20)';
		default:
			return $meta;
		}	
	}
	
	function CreateDatabase($dbname, $options=false)
	{
		$options = $this->_Options($options);
		$password = isset($options['PASSWORD']) ? $options['PASSWORD'] : 'tiger';
		$tablespace = isset($options["TABLESPACE"]) ? " DEFAULT TABLESPACE ".$options["TABLESPACE"] : '';
		$sql[] = "CREATE USER ".$dbname." IDENTIFIED BY ".$password.$tablespace;
		$sql[] = "GRANT CREATE SESSION, CREATE TABLE,UNLIMITED TABLESPACE,CREATE SEQUENCE TO $dbname";
		
		return $sql;
	}
	
	function AddColumnSQL($tabname, $flds)
	{
		$f = array();
		list($lines,$pkey) = $this->_GenFields($flds);
		$s = "ALTER TABLE $tabname ADD (";
		foreach($lines as $v) {
			$f[] = "\n $v";
		}
		
		$s .= implode(', ',$f).')';
		$sql[] = $s;
		return $sql;
	}
	
	function AlterColumnSQL($tabname, $flds, $tableflds='',$tableoptions='') // crmv@165801
	{
		$f = array();
		list($lines,$pkey) = $this->_GenFields($flds);
		$s = "ALTER TABLE $tabname MODIFY(";
		foreach($lines as $v) {
			$f[] = "\n $v";
		}
		$s .= implode(', ',$f).')';
		$sql[] = $s;
		return $sql;
	}
	
	function DropColumnSQL($tabname, $flds, $tableflds='',$tableoptions='') // crmv@165801
	{
		if (!is_array($flds)) $flds = explode(',',$flds);
		foreach ($flds as $k => $v) $flds[$k] = $this->NameQuote($v);
		
		$sql = array();
		$s = "ALTER TABLE $tabname DROP(";
		$s .= implode(', ',$flds).') CASCADE CONSTRAINTS';
		$sql[] = $s;
		return $sql;
	}
	
	function _DropAutoIncrement($t)
	{
		if (strpos($t,'.') !== false) {
			$tarr = explode('.',$t);
			return "drop sequence ".$tarr[0].".seq_".$tarr[1];
		}
		return "drop sequence seq_".$t;
	}
	
	// return string must begin with space
	function _CreateSuffix($fname,&$ftype,$fnotnull,$fdefault,$fautoinc,$fconstraint,$funsigned)
	{
		$suffix = '';
		
		if ($fdefault == "''" && $fnotnull) {// this is null in oracle
			$fnotnull = false;
			if ($this->debug) ADOConnection::outp("NOT NULL and DEFAULT='' illegal in Oracle");
		}
		
		if (strlen($fdefault)) $suffix .= " DEFAULT $fdefault";
		if ($fnotnull) $suffix .= ' NOT NULL';
		
		if ($fautoinc) $this->seqField = $fname;
		if ($fconstraint) $suffix .= ' '.$fconstraint;
		
		return $suffix;
	}
	
/*
CREATE or replace TRIGGER jaddress_insert
before insert on jaddress
for each row
begin
select seqaddress.nextval into :new.A_ID from dual;
end;
*/
	function _Triggers($tabname,$tableoptions)
	{
		if (!$this->seqField) return array();
		
		if ($this->schema) {
			$t = strpos($tabname,'.');
			if ($t !== false) $tab = substr($tabname,$t+1);
			else $tab = $tabname;
			$seqname = $this->schema.'.'.$this->seqPrefix.$tab;
			$trigname = $this->schema.'.'.$this->trigPrefix.$this->seqPrefix.$tab;
		} else {
			$seqname = $this->seqPrefix.$tabname;
			$trigname = $this->trigPrefix.$seqname;
		}
		
		if (strlen($seqname) > 30) {
			$seqname = $this->seqPrefix.uniqid('');
		} // end if
		if (strlen($trigname) > 30) {
			$trigname = $this->trigPrefix.uniqid('');
		} // end if

		if (isset($tableoptions['REPLACE'])) $sql[] = "DROP SEQUENCE $seqname";
		$seqCache = '';
		if (isset($tableoptions['SEQUENCE_CACHE'])){$seqCache = $tableoptions['SEQUENCE_CACHE'];}
		$seqIncr = '';
		if (isset($tableoptions['SEQUENCE_INCREMENT'])){$seqIncr = ' INCREMENT BY '.$tableoptions['SEQUENCE_INCREMENT'];}
		$seqStart = '';
		if (isset($tableoptions['SEQUENCE_START'])){$seqIncr = ' START WITH '.$tableoptions['SEQUENCE_START'];}
		$sql[] = "CREATE SEQUENCE $seqname $seqStart $seqIncr $seqCache";
		$sql[] = "CREATE OR REPLACE TRIGGER $trigname BEFORE insert ON $tabname FOR EACH ROW WHEN (NEW.$this->seqField IS NULL OR NEW.$this->seqField = 0) BEGIN select $seqname.nextval into :new.$this->seqField from dual; END;";
		
		$this->seqField = false;
		return $sql;
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
	

	
	function _IndexSQL($idxname, $tabname, $flds,$idxoptions)
	{
		$sql = array();
		
		if ( isset($idxoptions['REPLACE']) || isset($idxoptions['DROP']) ) {
			$sql[] = sprintf ($this->dropIndex, $idxname, $tabname);
			if ( isset($idxoptions['DROP']) )
				return $sql;
		}
		
		if ( empty ($flds) ) {
			return $sql;
		}
		
		if (isset($idxoptions['BITMAP'])) {
			$unique = ' BITMAP'; 
		} elseif (isset($idxoptions['UNIQUE'])) {
			$unique = ' UNIQUE';
		} else {
			$unique = '';
		}
		
		//crmv@fix field name
		if ( is_array($flds) ){
			foreach ($flds as $k=>$f){
				if (in_array($f,getOracleReservedWords()))	//crmv@24791
					$flds[$k] = '"'.$f.'"';
			}	
			$flds = implode(', ',$flds);
		}
		else {
			if (in_array($flds,getOracleReservedWords()))	//crmv@24791
				$flds = '"'.$flds.'"';			
		}
		//crmv@fix field name end
		$s = 'CREATE' . $unique . ' INDEX ' . $idxname . ' ON ' . $tabname . ' (' . $flds . ')';
		
		if ( isset($idxoptions[$this->upperName]) )
			$s .= $idxoptions[$this->upperName];
		
		if (isset($idxoptions['oci8']))
			$s .= $idxoptions['oci8'];
		

		$sql[] = $s;
		
		return $sql;
	}
	
	function GetCommentSQL($table,$col)
	{
		$table = $this->connection->qstr($table);
		$col = $this->connection->qstr($col);	
		return "select comments from USER_COL_COMMENTS where TABLE_NAME=$table and COLUMN_NAME=$col";
	}
	
	function SetCommentSQL($table,$col,$cmt)
	{
		$cmt = $this->connection->qstr($cmt);
		return  "COMMENT ON COLUMN $table.$col IS $cmt";
	}
	function _GenFields($flds,$widespacing=false)
	{
		if (is_string($flds)) {
			$padding = '     ';
			$txt = $flds.$padding;
			$flds = array();
			$flds0 = Lens_ParseArgs($txt,',');
			$hasparam = false;
			foreach($flds0 as $f0) {
				$f1 = array();
				foreach($f0 as $token) {
					switch (strtoupper($token)) {
					case 'INDEX':
						$f1['INDEX'] = '';
						// fall through intentionally
					case 'CONSTRAINT':
					case 'DEFAULT': 
						$hasparam = $token;
						break;
					default:
						if ($hasparam) $f1[$hasparam] = $token;
						else $f1[] = $token;
						$hasparam = false;
						break;
					}
				}
				// 'index' token without a name means single column index: name it after column
				if (array_key_exists('INDEX', $f1) && $f1['INDEX'] == '') {
					$f1['INDEX'] = isset($f0['NAME']) ? $f0['NAME'] : $f0[0];
					// check if column name used to create an index name was quoted
					if (($f1['INDEX'][0] == '"' || $f1['INDEX'][0] == "'" || $f1['INDEX'][0] == "`") &&
						($f1['INDEX'][0] == substr($f1['INDEX'], -1))) {
						$f1['INDEX'] = $f1['INDEX'][0].'idx_'.substr($f1['INDEX'], 1, -1).$f1['INDEX'][0];
					}
					else
						$f1['INDEX'] = 'idx_'.$f1['INDEX'];
				}
				// reset it, so we don't get next field 1st token as INDEX...
				$hasparam = false;

				$flds[] = $f1;
				
			}
		}
		$this->autoIncrement = false;
		$lines = array();
		$pkey = array();
		$idxs = array();
		foreach($flds as $fld) {
			$fld = _array_change_key_case($fld);
			
			$fname = false;
			$fdefault = false;
			$fautoinc = false;
			$ftype = false;
			$fsize = false;
			$fprec = false;
			$fprimary = false;
			$fnoquote = false;
			$fdefts = false;
			$fdefdate = false;
			$fconstraint = false;
			$fnotnull = false;
			$funsigned = false;
			$findex = '';
			$funiqueindex = false;
			
			//-----------------
			// Parse attributes
			foreach($fld as $attr => $v) {
				if ($attr == 2 && is_numeric($v)) $attr = 'SIZE';
				else if (is_numeric($attr) && $attr > 1 && !is_numeric($v)) $attr = strtoupper($v);
				
				switch($attr) {
				case '0':
				case 'NAME': 	$fname = $v; break;
				case '1':
				case 'TYPE': 	$ty = $v; $ftype = $this->ActualType(strtoupper($v)); break;
				
				case 'SIZE': 	
								$dotat = strpos($v,'.'); if ($dotat === false) $dotat = strpos($v,',');
								if ($dotat === false) $fsize = $v;
								else {
									$fsize = substr($v,0,$dotat);
									$fprec = substr($v,$dotat+1);
								}
								break;
				case 'UNSIGNED': $funsigned = true; break;
				case 'AUTOINCREMENT':
				case 'AUTO':	$fautoinc = true; $fnotnull = true; break;
				case 'KEY':
                // a primary key col can be non unique in itself (if key spans many cols...)
				case 'PRIMARY':	$fprimary = $v; $fnotnull = true; /*$funiqueindex = true;*/ break;
				case 'DEF':
				case 'DEFAULT': $fdefault = $v; break;
				case 'NOTNULL': $fnotnull = $v; break;
				case 'NOQUOTE': $fnoquote = $v; break;
				case 'DEFDATE': $fdefdate = $v; break;
				case 'DEFTIMESTAMP': $fdefts = $v; break;
				case 'CONSTRAINT': $fconstraint = $v; break;
				// let INDEX keyword create a 'very standard' index on column
				case 'INDEX': $findex = $v; break;
				case 'UNIQUE': $funiqueindex = true; break;
				} //switch
			} // foreach $fld
			
			//--------------------
			// VALIDATE FIELD INFO
			if (!strlen($fname)) {
				if ($this->debug) ADOConnection::outp("Undefined NAME");
				return false;
			}
			
			$fid = strtoupper(preg_replace('/^`(.+)`$/', '$1', $fname));
			$fname = $this->NameQuote($fname);
			
			if (!strlen($ftype)) {
				if ($this->debug) ADOConnection::outp("Undefined TYPE for field '$fname'");
				return false;
			} else {
				$ftype = strtoupper($ftype);
			}
			
			$ftype = $this->_GetSize($ftype, $ty, $fsize, $fprec);
			
			if ($ty == 'X' || $ty == 'X2' || $ty == 'B') $fnotnull = false; // some blob types do not accept nulls
			
			if ($fprimary) {
				//crmv@fix field name
				if (in_array($fname,getOracleReservedWords()))	//crmv@24791
					$fname = '"'.$fname.'"';
				//crmv@fix field name end	
				$pkey[] = $fname;
			}
			
			// some databases do not allow blobs to have defaults
			if ($ty == 'X') $fdefault = false;
			
			// build list of indexes
			if ($findex != '') {
				if (array_key_exists($findex, $idxs)) {
					$idxs[$findex]['cols'][] = ($fname);
					if (in_array('UNIQUE', $idxs[$findex]['opts']) != $funiqueindex) {
						if ($this->debug) ADOConnection::outp("Index $findex defined once UNIQUE and once not");
					}
					if ($funiqueindex && !in_array('UNIQUE', $idxs[$findex]['opts']))
						$idxs[$findex]['opts'][] = 'UNIQUE';
				}
				else
				{
					$idxs[$findex] = array();
					$idxs[$findex]['cols'] = array($fname);
					if ($funiqueindex)
						$idxs[$findex]['opts'] = array('UNIQUE');
					else
						$idxs[$findex]['opts'] = array();
				}
			}

			//--------------------
			// CONSTRUCT FIELD SQL
			if ($fdefts) {
				if (substr($this->connection->databaseType,0,5) == 'mysql') {
					$ftype = 'TIMESTAMP';
				} else {
					$fdefault = $this->connection->sysTimeStamp;
				}
			} else if ($fdefdate) {
				if (substr($this->connection->databaseType,0,5) == 'mysql') {
					$ftype = 'TIMESTAMP';
				} else {
					$fdefault = $this->connection->sysDate;
				}
			} else if ($fdefault !== false && !$fnoquote) {
				if ($ty == 'C' or $ty == 'X' or 
					( substr($fdefault,0,1) != "'" && !is_numeric($fdefault))) {

					if (($ty == 'D' || $ty == 'T') && strtolower($fdefault) != 'null') {
						// convert default date into database-aware code
						if ($ty == 'T')
						{
							$fdefault = $this->connection->DBTimeStamp($fdefault);
						}
						else
						{
							$fdefault = $this->connection->DBDate($fdefault);
						}
					}
					else
					if (strlen($fdefault) != 1 && substr($fdefault,0,1) == ' ' && substr($fdefault,strlen($fdefault)-1) == ' ') 
						$fdefault = trim($fdefault);
					else if (strtolower($fdefault) != 'null')
						$fdefault = $this->connection->qstr($fdefault);
				}
			}
			$suffix = $this->_CreateSuffix($fname,$ftype,$fnotnull,$fdefault,$fautoinc,$fconstraint,$funsigned);
			
			//crmv@fix field name
			if (in_array($fname,getOracleReservedWords()))	//crmv@24791
				$fname = '"'.$fname.'"';
			//crmv@fix field name end
			// add index creation
			if ($widespacing) $fname = str_pad($fname,24);
			
			 // check for field names appearing twice
            if (array_key_exists($fid, $lines)) {
            	 ADOConnection::outp("Field '$fname' defined twice");
            }
			
			$lines[$fid] = $fname.' '.$ftype.$suffix;
			
			if ($fautoinc) $this->autoIncrement = true;
		} // foreach $flds
		
		return array($lines,$pkey,$idxs);
	}
	function getCreateTableString($tabname,$temporary = false){ // crmv@165801
		if ($temporary)
			return "GLOBAL TEMPORARY TABLE $tabname";
		else
			return parent::getCreateTableString($tabname);	
	}
	function _GetSize($ftype, $ty, $fsize, $fprec)
	{
    	if ($ty == 'N' && $fsize > 38) $fsize = 38;
    	return parent::_GetSize($ftype, $ty, $fsize, $fprec);    
	}
	
	// crmv@188001
	function NameQuote($name = NULL,$allowBrackets=false) {
		$name = parent::NameQuote($name, $allowBrackets);
		
		$quote = $this->connection->nameQuote;
		if (is_string($name) && $name[0] !== $quote) {
			require_once('include/utils/db_utils.php');
			if (in_array($name, getOracleReservedWords())) {
				$name = $quote . $name . $quote;
			}
		}
		
		return $name;
	}
	// crmv@188001e
	
}
