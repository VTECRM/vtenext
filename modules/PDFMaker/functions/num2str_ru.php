<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/**
 * based on http://php.su/articles/?cat=vars&page=006
 **/  
global $_1_2_ru, $_1_19_ru, $des_ru, $hang_ru, $namerub_ru, $nametho_ru, $namemil_ru, $namemrd_ru;
$_1_2_ru[1]="одна ";
$_1_2_ru[2]="две ";

$_1_19_ru[1]="один ";
$_1_19_ru[2]="два ";
$_1_19_ru[3]="три ";
$_1_19_ru[4]="четыре ";
$_1_19_ru[5]="пять ";
$_1_19_ru[6]="шесть ";
$_1_19_ru[7]="семь ";
$_1_19_ru[8]="восемь ";
$_1_19_ru[9]="девять ";
$_1_19_ru[10]="десять ";

$_1_19_ru[11]="одиннацать ";
$_1_19_ru[12]="двенадцать ";
$_1_19_ru[13]="тринадцать ";
$_1_19_ru[14]="четырнадцать ";
$_1_19_ru[15]="пятнадцать ";
$_1_19_ru[16]="шестнадцать ";
$_1_19_ru[17]="семнадцать ";
$_1_19_ru[18]="восемнадцать ";
$_1_19_ru[19]="девятнадцать ";

$des_ru[2]="двадцать ";
$des_ru[3]="тридцать ";
$des_ru[4]="сорок ";
$des_ru[5]="пятьдесят ";
$des_ru[6]="шестьдесят ";
$des_ru[7]="семьдесят ";
$des_ru[8]="восемдесят ";
$des_ru[9]="девяносто ";

$hang_ru[1]="сто ";
$hang_ru[2]="двести ";
$hang_ru[3]="триста ";
$hang_ru[4]="четыреста ";
$hang_ru[5]="пятьсот ";
$hang_ru[6]="шестьсот ";
$hang_ru[7]="семьсот ";
$hang_ru[8]="восемьсот ";
$hang_ru[9]="девятьсот ";

$namerub_ru[1]="рубль ";
$namerub_ru[2]="рубля ";
$namerub_ru[3]="рублей ";

$nametho_ru[1]="тысяча ";
$nametho_ru[2]="тысячи ";
$nametho_ru[3]="тысяч ";

$namemil_ru[1]="миллион ";
$namemil_ru[2]="миллиона ";
$namemil_ru[3]="миллионов ";

$namemrd_ru[1]="миллиард ";
$namemrd_ru[2]="миллиарда ";
$namemrd_ru[3]="миллиардов ";

$kopeek_ru[1]="копейка ";
$kopeek_ru[2]="копейки ";
$kopeek_ru[3]="копеек ";


function semantic_ru($i,&$words,&$fem,$f){
	global $_1_2_ru, $_1_19_ru, $des_ru, $hang_ru;
	$words="";
	$fl=0;
	if($i >= 100){
		$jkl = intval($i / 100);
		$words.=$hang_ru[$jkl];
		$i%=100;
	}
	if($i >= 20){
		$jkl = intval($i / 10);
		$words.=$des_ru[$jkl];
		$i%=10;
		$fl=1;
	}
	switch($i){
		case 1: $fem=1; break;
		case 2:
		case 3:
		case 4: $fem=2; break;
		default: $fem=3; break;
	}
	if( $i ){
		if( $i < 3 && $f > 0 ){
			if ( $f >= 2 ) {
				$words.=$_1_19_ru[$i];
			} else {
				$words.=$_1_2_ru[$i];
			}
		}
		else {
			$words.=$_1_19_ru[$i];
		}
	}
}


function num2str_ru($L){
	$L = str_replace(array(' ',','), array('','.'), $L);
	global $namerub_ru, $nametho_ru, $namemil_ru, $namemrd_ru, $kopeek_ru;
	
	$s=" ";
	$s1=" ";
	$s2=" ";
	$kop=intval( ( $L*100 - intval( $L )*100 ));
	$L=intval($L);
	if($L>=1000000000){
		$many=0;
		semantic_ru(intval($L / 1000000000),$s1,$many,3);
		$s.=$s1.$namemrd_ru[$many];
		$L%=1000000000;
	}
	
	if($L >= 1000000){
		$many=0;
		semantic_ru(intval($L / 1000000),$s1,$many,2);
		$s.=$s1.$namemil_ru[$many];
		$L%=1000000;
		if($L==0){
			$s.="рублей ";
		}
	}
	
	if($L >= 1000){
		$many=0;
		semantic_ru(intval($L / 1000),$s1,$many,1);
		$s.=$s1.$nametho_ru[$many];
		$L%=1000;
		if($L==0){
			$s.="рублей ";
		}
	}
	
	if($L != 0){
		$many=0;
		semantic_ru($L,$s1,$many,0);
		$s.=$s1.$namerub_ru[$many];
	}
	
	if($kop > 0){
		$many=0;
		semantic_ru($kop,$s1,$many,1);
		$s.=$s1.$kopeek_ru[$many];
	}
	else {
		$s.=" 00 копеек";
	}
	
	return $s;
}