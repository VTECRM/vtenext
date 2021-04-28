<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/* crmv@208173 */

class VTEventCondition{
    private $data;
    private $expr;

    public function __construct($expr){
        if($expr!=''){
            $parser = $this->getParser($expr);
            $this->expr = $parser->statement();
        }else{
            $this->expr = null;
        }
    }

    private function evaluate($expr){
        if(is_array($expr)){
            $oper = $expr[0];
            if($oper != '.'){
                $evaled = array_map(array($this, 'evaluate'), array_slice($expr, 1));
                switch($oper){
                    case "==":
                        return $evaled[0] == $evaled[1];
                    case "list":
                        return $evaled;
                    case "in":
                        return in_array($evaled[0], $evaled[1]);
                    default:
                        return false;
                }
            }else{
                $out = $this->data;
                $syms = array_slice($expr, 1);
                foreach($syms as $sym){
                    $out = $this->get($out, $sym->name);
                }
                return $out;
            }
        }else{
            if($expr instanceof VTEventConditionSymbol){
                return $this->get($this->data, $expr->name);
            }else{
                return $expr;
            }
        }
    }

    private function get($obj, $field){
        if(is_array($obj)){
            return $obj[$field];
        }else{
            $func = "get".ucwords($field);
            return $obj->$func();
        }

    }

    public function test($obj){
        $this->data = $obj;
        if($this->expr===null){
            return true;
        }else{
            return $this->evaluate($this->expr);
        }
    }

    private function getParser($expr){
        $ass = new ANTLRStringStream($expr);
        $lex = new VTEventConditionParserLexer($ass);
        $cts = new CommonTokenStream($lex);
        return new VTEventConditionParserParser($cts);
    }
}