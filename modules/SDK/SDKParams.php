<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * This class contains handy methods to define an order between the parameters in $_REQUEST
 * in order to be able to choose the most specific (for the inclusion) or spot incompatible sets
*/

class SDKParams {

	static $NOTNULL = '$NOTNULL$'; 
	
 	/*
 	 * Restituisce vero se il parametro v1 è incluso (= più specifico) o uguale a v2
 	 * valori validi sono <valore>, "$NOTNULL$", NULL e vale che
 	 * <valore> < "$NOTNULL$" < NULL
 	 */
 	public static function valueLTE($v1, $v2) {
 		if (is_null($v1)) {
 			// v1 not specified
 			return is_null($v2);	
 		} elseif ($v1 == self::$NOTNULL) {
 			// v1 exists, but can be anything
 			return (is_null($v2) || $v2 == self::$NOTNULL);
 		} else {
 			// v1 has a specific value
 			return (is_null($v2) || $v2 == self::$NOTNULL || $v1 === $v2);
 		}
 		return false;
 	}

 	/*
 	 * Restituisce vero se il parametro v1 è equivalente (per l'inclusione) a v2
 	 * valori validi sono <valore>, "$NOTNULL$", NULL e vale che
 	 * <valore> < "$NOTNULL$" < NULL
 	 */
 	public static function valueE($v1, $v2) {
 		return ($v1 === $v2);
 	}
 	
 	/*
 	 * Trasforma le liste di parametri in modo che abbiano le stesse chiavi
 	 * nello stesso ordine. Le chiavi aggiunte hanno valore NULL
 	 */
 	public static function paramsEqualize(&$p1, &$p2) {
 		// modifico le liste in modo che abbiano le stesse chiavi
 		$missingp2 = array_diff_key($p1, $p2);
 		$missingp1 = array_diff_key($p2, $p1);
 		$p2 = $p2 + array_fill_keys(array_keys($missingp2), NULL);
 		$p1 = $p1 + array_fill_keys(array_keys($missingp1), NULL);
 		ksort($p1);
 		ksort($p2);
 	}
 	
	/*
 	 * Restituisce vero se la lista di parametri $p1 è compresa in $p2, 
 	 * falso se $p2 è compresa in $p1 e NULL se le liste non sono confrontabili
 	 * TODO: restituire un numero che dice la "distanza"
 	 */
 	public static function paramsLTE($p1, $p2) {
		self::paramsEqualize($p1, $p2);
 		 
 		// confronto chiave per chiave
 		$less = 0;
 		$more = 0;
 		foreach ($p1 as $k1=>$v1) {
 			$lte = self::valueLTE($v1, $p2[$k1]);
 			if (self::valueE($v1, $p2[$k1])) continue;
 			if ($lte) ++$less; else ++$more;
 			// una delle 2 non è zero
 			if ($less * $more != 0) return NULL;
 		}
 		return ($less == $more || $less > 0);
 	}
 	
  	/*
 	 * Restituisce vero se le 2 liste di parametri sono equivalenti (per l'inclusione)
 	 */
 	public static function paramsE($p1, $p2) {
 		self::paramsEqualize($p1, $p2);
 		 
 		// confronto chiave per chiave
 		foreach ($p1 as $k1=>$v1) {
 			$eq = self::valueE($v1, $p2[$k1]);
 			if (!$eq) return false;
 		}
 		return true;
 	}
 
 	/*
 	 * Restituisce vero se $request soddisfa i requisiti di $params
 	 */
 	public static function paramsMatch($request, $params) {
 		self::paramsEqualize($request, $params);
 		foreach ($request as $k=>$v) {
 			if (!self::valueLTE($v, $params[$k])) return false;
 		}
 		return true;
 	}
 	
 	/*
 	 * Get the minimum parameters list according to the paramsLTE order
 	 * $plist = array( 0=>array(templatefile, array(params) ), 1=>... )
 	 */
 	public static function paramsMin($plist) {
		$minid = 0;
		for ($i = 1; $i<count($plist); ++$i) {
			if (self::paramsLTE($plist[$i][1], $plist[$minid][1])) $minid = $i;
		}
		return $plist[$minid][0];
 	}
 	
 	/*
 	 * Controlla se i nuovi parametri sono compatibili con quelli esistenti
 	 * 		$plist    : array con i parametri del database
 	 * 		$newparam : i nuovi parametri da inserire
 	 * Ritorna:
 	 *   array() = ok
 	 *   array( array(TIPOFAIL, BADPARAMS), ...)
 	 *     con TIPOFAIL  = 1=duplicato, 2=incompatibile
 	 *         BADPARAMS = stringa con i parametri cattivi
 	 */ 	
 	public static function paramsValidate($plist, $newparam) {
 		$ret = array();

		foreach ($plist as $oldparam) {
			if (SDKParams::paramsE($newparam, $oldparam)) {
				$ret[] = array(1, Zend_Json::encode($oldparam));
				continue;
			}
			$lte1 = SDKParams::paramsLTE($newparam, $oldparam);
			// quick shortcut
			if (!is_null($lte1)) continue;
			$lte2 = SDKParams::paramsLTE($oldparam, $newparam);
			if (is_null($lte2)) {
				$ret[] = array(2, Zend_Json::encode($oldparam));
			}
		}
 		
 		return $ret;
 	}
 	
}

?>