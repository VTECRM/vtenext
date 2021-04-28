<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
interface VTEnv{
	function get($var);
}

function _vt_add($arr){
	if(sizeof($arr)==1){
		return $arr[0];
	}else{
		// crmv@199002
		if(empty($arr[0])) $arr[0]=0;
		if(empty($arr[1])) $arr[1]=0;
		// crmv@199002e
		return $arr[0]+$arr[1];
	}
}

function _vt_sub($arr){
	if(sizeof($arr)==1){
		return -$arr[0];
	}else{
		return $arr[0]-$arr[1];
	}
}

function _vt_mul($arr){
	// crmv@199002
	if(empty($arr[0])) $arr[0]=0;
	if(empty($arr[1])) $arr[1]=0;
	// crmv@199002e
	return $arr[0]*$arr[1];
}

function _vt_div($arr){
	if (empty($arr[1]) || $arr[1] == 0) return 0; // crmv@194547
	return $arr[0]/$arr[1];
}

function _vt_equals($arr){
	return $arr[0] == $arr[1];
}

function _vt_ltequals($arr) {
	return $arr[0] <= $arr[1];
}

function _vt_gtequals($arr) {
	return $arr[0] >= $arr[1];
}

function _vt_lt($arr) {
	return $arr[0] < $arr[1];
}

function _vt_gt($arr) {
	return $arr[0] > $arr[1];
}

//crmv@65404
function _vt_concat($arr){
	$return = '';
	if(is_array($arr[0])){
		$return = _vt_implode_r('',$arr);
	}
	else{
		$return = implode($arr);
	}
	
	return $return;
}

function _vt_implode_r($glue,$arr){
	$ret = '';

	foreach($arr as $piece)
	{
		if(is_array($piece))
			$ret .= $glue . _vt_implode_r($glue, $piece);
		else
			$ret .= $glue . $piece;
	}

	return $ret;
}
//crmv@65404e

/* Date difference between (input times) or (current time and input time)
 *
 * @param Array $a $a[0] - Input time1, $a[1] - Input time2
 * (if $a[1] is not available $a[0] = Current Time, $a[1] = Input time1)
 * @return int difference timestamp
 */

function _vt_time_diff($arr) {

    $time_operand1 = $time_operand2 = 0;
    if(count($arr) > 1) {
            $time_operand1 = $arr[0];
            $time_operand2 = $arr[1];
    } else {
            $time_operand1 = date('Y-m-d H:i:s'); // Current time
            $time_operand2 = $arr[0];
    }

    if(empty($time_operand1) || empty($time_operand2)) return 0;

    $time_operand1 = getValidDBInsertDateValue($time_operand1);
	$time_operand2 = getValidDBInsertDateValue($time_operand2);

    return (strtotime($time_operand1) - strtotime($time_operand2));
}
/**
 * Calculate the time difference (input times) or (current time and input time) and
 * convert it into number of days.
 * @param Array $a $a[0] - Input time1, $a[1] - Input time2
 * (if $a[1] is not available $a[0] = Current Time, $a[1] = Input time1)
 * @return int number of days
 */
function _vt_time_diffdays($arr) {
    $timediff  = _vt_time_diff($arr);
    $days_diff = floor($timediff / (60 * 60 * 24));
    return $days_diff;
}


/** END **/
class VTExpressionEvaluater{
	function __construct($expr){
			
		$this->operators = array(
				'+'  => '_vt_add',
				'-'  => '_vt_sub',
				'*'  => '_vt_mul',
				'/'  => '_vt_div',
				'==' => '_vt_equals',
				'<=' => '_vt_ltequals',
				'>=' => '_vt_gtequals',
				'<' => '_vt_lt',
				'>' => '_vt_gt',
		);
		$this->functions = array(
				'concat'=>'_vt_concat',
				'time_diff' => '_vt_time_diff',
 	            'time_diffdays' => '_vt_time_diffdays'
				);
					
				$this->operations = array_merge($this->functions, $this->operators);
				$this->expr = $expr;
					
	}

	function evaluate($env){
		$this->env = $env;
		return $this->exec($this->expr);
	}

	function exec($expr){
		if($expr instanceof Symbol){
			return $this->env($expr);
		}else if($expr instanceof VTTreeNode){
			$op = $expr->getName();
			if($op->value=='if'){
				$params = $expr->getParams();
				$cond = $this->exec($params[0]);
				if($cond){
					return $this->exec($params[1]);
				}else{
					return $this->exec($params[2]);
				}
			}else{
				$params = array_map(array($this, 'exec'), $expr->getParams());
				$func = $this->operations[strtolower($op->value)];	//crmv@35743
				return $func($params);
			}
		}else{
			return $expr;
		}
	}

	function env($sym){
		return $this->env->get($sym->value);
	}
}
?>