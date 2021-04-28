<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/**
 * This function returns 
 *  
 * @param $name - array name
 * @param $value -    
 *  
 **/
 
function addToCFArray($name,$value)
{
    global $PDFContent;
    
    $PDFContent->PDFMakerCFArray[$name][] = $value;
    
    return "";
}

/**
 * Join array elements with a string
 *  
 * @param $name - array name
 * @param $glue -    
 *  
 **/
 
function implodeCFArray($name,$glue)
{
    global $PDFContent;
    
    return implode($glue,$PDFContent->PDFMakerCFArray[$name]);
}

/**
 * This function returns 
 *  
 * @param $name - array name
 * @param $value -    
 *  
 **/
 
function addToCFArrayALL($name,$value)
{
    global $focus;
    
    $focus->PDFMakerCFArrayALL[$name][] = $value;
    
    return "";
}

/**
 * Join array elements with a string
 *  
 * @param $name - array name 
 * @param $glue -    
 *  
 **/
 
function implodeCFArrayALL($name,$glue)
{
    global $focus;
    
    return implode($glue,$focus->PDFMakerCFArrayALL[$name]);
}



/**
 * This function returns the sum of values in an array. 
 *  
 * @param $name - array name 
 *  
 **/
 
function sumCFArray($name)
{
    global $PDFContent;
    
    foreach ($PDFContent->PDFMakerCFArray[$name] AS $key => $number)
    {
        $PDFContent->PDFMakerCFArray[$name][$key] = its4you_formatNumberFromPDF($number);
    } 
       
    $value = array_sum($PDFContent->PDFMakerCFArray[$name]);
    
    return its4you_formatNumberToPDF($value);
}

/**
 * This function returns the sum of values in an array. 
 *  
 * @param $name - array name
 *  
 **/
 
function sumCFArrayAll($name)
{
    
    foreach ($focus->PDFMakerCFArrayALL[$name] AS $key => $number)
    {
        $focus->PDFMakerCFArrayALL[$name][$key] = its4you_formatNumberFromPDF($number);
    } 
       
    $value = array_sum($focus->PDFMakerCFArrayALL[$name]);
    
    return its4you_formatNumberToPDF($value);
}