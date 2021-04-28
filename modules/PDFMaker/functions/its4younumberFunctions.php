<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/**
 * This function
 *  
 * @param $name - number name 
 *  
 **/
 
function setCFNumberValue($name, $value = '0')
{
    global $PDFContent;

    $value = its4you_formatNumberFromPDF($value);
    
    $PDFContent->PDFMakerCFNumberValue[$name] = $value;
    
    return "";
}

function sumCFNumberValue($name, $value1)
{
    global $PDFContent;

    mathCFNumberValue($name, "+", $value1);
    
    return "";
}

function deductCFNumberValue($name, $value1)
{
    global $PDFContent;

    mathCFNumberValue($name, "-", $value1);
    
    return "";
}

function mathCFNumberValue($name, $type1, $value1, $type2 = "", $value2 = "")
{
    global $PDFContent;

    if (isset($PDFContent->PDFMakerCFNumberValue[$value1]) && $PDFContent->PDFMakerCFNumberValue[$value1] != "")
        $value1 = $PDFContent->PDFMakerCFNumberValue[$value1];
    else
        $value1 = its4you_formatNumberFromPDF($value1);



    if ($value2 == "")
    {
        if ($type1 == "=" )
        {
            $PDFContent->PDFMakerCFNumberValue[$name] = $value1;
        }
        elseif ($type1 == "+" )
        {
            $PDFContent->PDFMakerCFNumberValue[$name] += $value1;
        }
        elseif ($type1 == "-")
        {
            $PDFContent->PDFMakerCFNumberValue[$name] -= $value1;
        }
    }
    else
    {
        if (isset($PDFContent->PDFMakerCFNumberValue[$value2]) && $PDFContent->PDFMakerCFNumberValue[$value2] != "")
            $value2 = $PDFContent->PDFMakerCFNumberValue[$value2];
        else
            $value2 = its4you_formatNumberFromPDF($value2);
        
        if ($type2 == "+")
        {
            $newvalue = $value1 + $value2;
        }
        elseif ($type2 == "-")
        {
            $newvalue = $value1 - $value2;
        }
        elseif ($type2 == "*")
        {
            $newvalue = $value1 * $value2;
        }
        elseif ($type2 == "/")
        {
            $newvalue = $value1 / $value2;
        }
        
        if ($type1 == "=")
        {
            $PDFContent->PDFMakerCFNumberValue[$name] = $newvalue;
        }
        elseif ($type1 == "+")
        {
            $PDFContent->PDFMakerCFNumberValue[$name] += $newvalue;
        }
        elseif ($type1 == "-")
        {
            $PDFContent->PDFMakerCFNumberValue[$name] -= $newvalue;
        }
    }
    
    return "";
}
/**
 * This function show number value 
 *  
 * @param $name - number name 
 *  
 **/
 
function showCFNumberValue($name)
{
    global $PDFContent;

    if (isset($PDFContent->PDFMakerCFNumberValue[$name]))
    {
        $value = $PDFContent->PDFMakerCFNumberValue[$name];
         
        return its4you_formatNumberToPDF($value);
    }
    else
    {
        return '[CUSTOM FUNCTION ERROR: number value "'.$name.'" is not defined.]'; 
    }
}