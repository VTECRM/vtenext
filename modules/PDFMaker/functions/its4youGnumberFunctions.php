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
 
function setCFGNumberValue($name, $value = '0')
{
    global $focus;

    $value = its4you_formatNumberFromPDF($value);
    
    $focus->PDFMakerCFGNumberValue[$name] = $value;
    
    return "";
}

function sumCFGNumberValue($name, $value1)
{
    global $focus;

    mathCFGNumberValue($name, "+", $value1);
    
    return "";
}

function deductCFGNumberValue($name, $value1)
{
    global $focus;

    mathCFGNumberValue($name, "-", $value1);
    
    return "";
}

function mathCFGNumberValue($name, $type1, $value1, $type2  = "", $value2 = "")
{
    global $focus;

    if (isset($focus->PDFMakerCFGNumberValue[$value1]) && $focus->PDFMakerCFGNumberValue[$value1] != "")
        $value1 = $focus->PDFMakerCFGNumberValue[$value1];
    else
        $value1 = its4you_formatNumberFromPDF($value1);



    if ($value2 == "")
    {
        if ($type1 == "=" )
        {
            $focus->PDFMakerCFGNumberValue[$name] = $value1;
        }
        elseif ($type1 == "+" )
        {
            $focus->PDFMakerCFGNumberValue[$name] += $value1;
        }
        elseif ($type1 == "-")
        {
            $focus->PDFMakerCFGNumberValue[$name] -= $value1;
        }
    }
    else
    {
        if (isset($focus->PDFMakerCFGNumberValue[$value2]) && $focus->PDFMakerCFGNumberValue[$value2] != "")
            $value2 = $focus->PDFMakerCFGNumberValue[$value2];
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
            $focus->PDFMakerCFGNumberValue[$name] = $newvalue;
        }
        elseif ($type1 == "+")
        {
            $focus->PDFMakerCFGNumberValue[$name] += $newvalue;
        }
        elseif ($type1 == "-")
        {
            $focus->PDFMakerCFGNumberValue[$name] -= $newvalue;
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
 
function showCFGNumberValue($name)
{
    global $focus;

    if (isset($focus->PDFMakerCFGNumberValue[$name]))
    {
        $value = $focus->PDFMakerCFGNumberValue[$name];
         
        return its4you_formatNumberToPDF($value);
    }
    else
    {
        return '[CUSTOM FUNCTION ERROR: number value "'.$name.'" is not defined.]'; 
    }
}