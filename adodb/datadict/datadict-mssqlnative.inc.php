<?php

/**
  V5.11 5 May 2010   (c) 2000-2010 John Lim (jlim#natsoft.com). All rights reserved.
  Released under both BSD license and Lesser GPL library license. 
  Whenever there is any discrepancy between the two licenses, 
  the BSD license will take precedence.
	
  Set tabs to 4 for best viewing.
 
*/

/*
In ADOdb, named quotes for MS SQL Server use ". From the MSSQL Docs:

	Note Delimiters are for identifiers only. Delimiters cannot be used for keywords, 
	whether or not they are marked as reserved in SQL Server.
	
	Quoted identifiers are delimited by double quotation marks ("):
	SELECT * FROM "Blanks in Table Name"
	
	Bracketed identifiers are delimited by brackets ([ ]):
	SELECT * FROM [Blanks In Table Name]
	
	Quoted identifiers are valid only when the QUOTED_IDENTIFIER option is set to ON. By default, 
	the Microsoft OLE DB Provider for SQL Server and SQL Server ODBC driver set QUOTED_IDENTIFIER ON 
	when they connect. 
	
	In Transact-SQL, the option can be set at various levels using SET QUOTED_IDENTIFIER, 
	the quoted identifier option of sp_dboption, or the user options option of sp_configure.
	
	When SET ANSI_DEFAULTS is ON, SET QUOTED_IDENTIFIER is enabled.
	
	Syntax
	
		SET QUOTED_IDENTIFIER { ON | OFF }


*/

// security - hide paths
if (!defined('ADODB_DIR')) die();

class ADODB2_mssqlnative extends ADODB_DataDict {
	var $databaseType = 'mssqlnative';
	var $dropIndex = 'DROP INDEX %2$s.%1$s';
	var $renameTable = "EXEC sp_rename '%s','%s'";
	var $renameColumn = "EXEC sp_rename '%s.%s','%s'";

	var $typeX = 'TEXT';  ## Alternatively, set it to VARCHAR(4000)
	var $typeXL = 'TEXT';
	
	//var $alterCol = ' ALTER COLUMN ';
	
	function MetaType($t,$len=-1,$fieldobj=false)
	{
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}
		
		$len = -1; // mysql max_length is not accurate
		switch (strtoupper($t)) {
		case 'R':
		case 'INT': 
		case 'INTEGER': return  'I';
		case 'BIT':
		case 'TINYINT': return  'I1';
		case 'SMALLINT': return 'I2';
		case 'BIGINT':  return  'I8';
		
		case 'REAL':
		case 'FLOAT': return 'F';
		default: return parent::MetaType($t,$len,$fieldobj);
		}
	}
	
	function ActualType($meta)
	{
		switch(strtoupper($meta)) {

		case 'C': return 'VARCHAR';
		case 'XL': return (isset($this)) ? $this->typeXL : 'TEXT';
		case 'X': return (isset($this)) ? $this->typeX : 'TEXT'; ## could be varchar(8000), but we want compat with oracle
		case 'C2': return 'NVARCHAR';
		case 'X2': return 'NTEXT';
		
		case 'B': return 'IMAGE';
			
		case 'D': return 'DATETIME';
		case 'DT': return 'DATETIME'; //crmv@155585
		
		case 'T': return 'DATETIME';
		case 'L': return 'BIT';
		
		case 'R':		
		case 'I': return 'INT'; 
		case 'I1': return 'TINYINT';
		case 'I2': return 'SMALLINT';
		case 'I4': return 'INT';
		case 'I8': return 'BIGINT';
		
		case 'F': return 'REAL';
		case 'N': return 'NUMERIC';
		default:
			return $meta;
		}
	}
	
	
	function AddColumnSQL($tabname, $flds)
	{
		$tabname = $this->TableName ($tabname);
		$f = array();
		list($lines,$pkey) = $this->_GenFields($flds);
		$s = "ALTER TABLE $tabname $this->addCol";
		foreach($lines as $v) {
			$f[] = "\n $v";
		}
		$s .= implode(', ',$f);
		$sql[] = $s;
		return $sql;
	}
	
	/*
	function AlterColumnSQL($tabname, $flds)
	{
		$tabname = $this->TableName ($tabname);
		$sql = array();
		list($lines,$pkey) = $this->_GenFields($flds);
		foreach($lines as $v) {
			$sql[] = "ALTER TABLE $tabname $this->alterCol $v";
		}

		return $sql;
	}
	*/
	
	function DropColumnSQL($tabname, $flds, $tableflds = '', $tableoptions = '') // crmv@155585
	{
		$tabname = $this->TableName ($tabname);
		if (!is_array($flds))
			$flds = explode(',',$flds);
		$f = array();
		$s = 'ALTER TABLE ' . $tabname;
		foreach($flds as $v) {
			$f[] = "\n$this->dropCol ".$this->NameQuote($v);
		}
		$s .= implode(', ',$f);
		$sql[] = $s;
		return $sql;
	}
	
	// return string must begin with space
	function _CreateSuffix($fname,&$ftype,$fnotnull,$fdefault,$fautoinc,$fconstraint,$funsigned)
	{	
		$suffix = '';
		if (strlen($fdefault)) $suffix .= " DEFAULT $fdefault";
		if ($fautoinc) $suffix .= ' IDENTITY(1,1)';
		if ($fnotnull) $suffix .= ' NOT NULL';
		else if ($suffix == '') $suffix .= ' NULL';
		if ($fconstraint) $suffix .= ' '.$fconstraint;
		return $suffix;
	}
	
	/*
CREATE TABLE 
    [ database_name.[ owner ] . | owner. ] table_name 
    ( { < column_definition > 
        | column_name AS computed_column_expression 
        | < table_constraint > ::= [ CONSTRAINT constraint_name ] }

            | [ { PRIMARY KEY | UNIQUE } [ ,...n ] 
    ) 

[ ON { filegroup | DEFAULT } ] 
[ TEXTIMAGE_ON { filegroup | DEFAULT } ] 

< column_definition > ::= { column_name data_type } 
    [ COLLATE < collation_name > ] 
    [ [ DEFAULT constant_expression ] 
        | [ IDENTITY [ ( seed , increment ) [ NOT FOR REPLICATION ] ] ]
    ] 
    [ ROWGUIDCOL] 
    [ < column_constraint > ] [ ...n ] 

< column_constraint > ::= [ CONSTRAINT constraint_name ] 
    { [ NULL | NOT NULL ] 
        | [ { PRIMARY KEY | UNIQUE } 
            [ CLUSTERED | NONCLUSTERED ] 
            [ WITH FILLFACTOR = fillfactor ] 
            [ON {filegroup | DEFAULT} ] ] 
        ] 
        | [ [ FOREIGN KEY ] 
            REFERENCES ref_table [ ( ref_column ) ] 
            [ ON DELETE { CASCADE | NO ACTION } ] 
            [ ON UPDATE { CASCADE | NO ACTION } ] 
            [ NOT FOR REPLICATION ] 
        ] 
        | CHECK [ NOT FOR REPLICATION ] 
        ( logical_expression ) 
    } 

< table_constraint > ::= [ CONSTRAINT constraint_name ] 
    { [ { PRIMARY KEY | UNIQUE } 
        [ CLUSTERED | NONCLUSTERED ] 
        { ( column [ ASC | DESC ] [ ,...n ] ) } 
        [ WITH FILLFACTOR = fillfactor ] 
        [ ON { filegroup | DEFAULT } ] 
    ] 
    | FOREIGN KEY 
        [ ( column [ ,...n ] ) ] 
        REFERENCES ref_table [ ( ref_column [ ,...n ] ) ] 
        [ ON DELETE { CASCADE | NO ACTION } ] 
        [ ON UPDATE { CASCADE | NO ACTION } ] 
        [ NOT FOR REPLICATION ] 
    | CHECK [ NOT FOR REPLICATION ] 
        ( search_conditions ) 
    } 


	*/
	
	/*
	CREATE [ UNIQUE ] [ CLUSTERED | NONCLUSTERED ] INDEX index_name 
    ON { table | view } ( column [ ASC | DESC ] [ ,...n ] ) 
		[ WITH < index_option > [ ,...n] ] 
		[ ON filegroup ]
		< index_option > :: = 
		    { PAD_INDEX | 
		        FILLFACTOR = fillfactor | 
		        IGNORE_DUP_KEY | 
		        DROP_EXISTING | 
		    STATISTICS_NORECOMPUTE | 
		    SORT_IN_TEMPDB  
		}
*/
	function _IndexSQL($idxname, $tabname, $flds, $idxoptions)
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
		
		$unique = isset($idxoptions['UNIQUE']) ? ' UNIQUE' : '';
		$clustered = isset($idxoptions['CLUSTERED']) ? ' CLUSTERED' : '';
		
		//crmv@155585
		if ( is_array($flds) ){
			foreach ($flds as $k=>$f){
				$flds[$k] = '['.$f.']';
			}	
			$flds = implode(', ',$flds);
		}
		else {
			$flds = '['.$flds.']';			
		}
		//crmv@155585 field name end
		
		$s = 'CREATE' . $unique . $clustered . ' INDEX ' . $idxname . ' ON ' . $tabname . ' (' . $flds . ')';
		
		if ( isset($idxoptions[$this->upperName]) )
			$s .= $idxoptions[$this->upperName];
		

		$sql[] = $s;
		
		return $sql;
	}
	
	
	function _GetSize($ftype, $ty, $fsize, $fprec)
	{
		switch ($ftype) {
		case 'INT':
		case 'SMALLINT':
		case 'TINYINT':
		case 'BIGINT':
			return $ftype;
		}
    	if ($ty == 'T') return $ftype;
    	return parent::_GetSize($ftype, $ty, $fsize, $fprec);    

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
			$fname = "[".$this->NameQuote($fname)."]"; // crmv@155585
			
			if (!strlen($ftype)) {
				if ($this->debug) ADOConnection::outp("Undefined TYPE for field '$fname'");
				return false;
			} else {
				$ftype = strtoupper($ftype);
			}
			
			$ftype = $this->_GetSize($ftype, $ty, $fsize, $fprec);
			
			if ($ty == 'X' || $ty == 'X2' || $ty == 'B') $fnotnull = false; // some blob types do not accept nulls
			
			if ($fprimary) $pkey[] = $fname;
			
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
	
	// crmv@155585
	function changeTableName($tabname){
			return "#$tabname";
	}
	
	// crmv@129940
	// sql server doesn't like the ANSI quotes (") around temporary tables, for example: "#my_temp_table" is invalid
	
	function isTempTable($tabname) {
		return (is_string($tabname) && $tabname[0] === '#');
	}
	
	function TableName($name)
	{
		if ( !is_object($this->connection) ) {
			return $name;
		}
		
		if (!$this->isTempTable($name)) return parent::TableName($name);
		
		if ( $this->schema ) {
			return '['.$this->schema .'].['. $name.']';
		}
		return '['.$name.']';
	}
	// crmv@129940e
	// crmv@155585e
	
}

