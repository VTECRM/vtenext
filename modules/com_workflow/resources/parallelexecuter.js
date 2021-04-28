/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
function parallelExecuter(executer, operationCount){
	var parameters = [];
	var n = 0;
	var ctr = 0;
	function makeParallel(operation){
		var id = n;
		n++;
		function cookie(){
			parameters[id] = arguments;
			ctr++;
			if(ctr == operationCount){
				executer(parameters);
			}
		}
		operation(cookie);
	}
	return makeParallel;
}