<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@208173 */

class SqlResultIterator implements Iterator{

    public function __construct($adb, $result){
        $this->result = $result;
        $this->adb = $adb;
        $this->pos = 0;
    }

    public function next(){
        $this->pos+=1;
    }

    public function current(){
        $adb = $this->adb;
        $data = $adb->raw_query_result_rowdata($this->result, $this->pos);
        return new SqlResultIteratorRow($data);
    }

    public function key(){
        return $this->pos;
    }

    public function rewind(){
        $this->pos = 0;
    }

    public function valid(){
        $adb = $this->adb;
        return $this->pos < $adb->num_rows($this->result);
    }


    /**
     * Return the contents of the resultset as an array. Destroys a running iterator's state.
     *
     * This method will reset the iterator using the rewind method.
     *
     * $copyFields specify which fields of the result to copy to the array.
     * If not specified the function will return values for all the fields.
     * @param null $copyFields
     * @return array
     */
    public function toArray($copyFields=null){
        $adb = $this->adb;
        $this->rewind();

        if($copyFields===null){
            $columnData = $adb->getFieldsDefinition($this->result);
            $columnNames = [];
            foreach($columnData as $column){
                $columnNames[]=$column->name;
            }
            $copyFields = $columnNames;
        }

        $arr = [];
        foreach($this as $row){
            $rowArr = [];
            foreach($copyFields as $name){
                $rowArr[$name]=$row->$name;
            }
            $arr[]=$rowArr;
        }
        return $arr;
    }
}

class SqlResultIteratorRow{
    public function __construct($data){
        $this->data = $data;
    }

    public function __get($column){
        return $this->get($column);
    }

    public function get($column){
        return $this->data[$column];
    }
}