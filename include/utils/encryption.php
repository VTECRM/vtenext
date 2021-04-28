<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/


class Encryption 
{
	//function to encrypt given string
 	function encrypt($message)
 	{
		//converting a string to binary
		$encodedMessage = $this->asc2bin($message);
        $encodedMessage = $this->xor_string($encodedMessage);
        $encodedMessage = $this->urlsafe_b64encode($encodedMessage);
		return $encodedMessage;
	}
	// function to convert  provided string to binary value
 	function asc2bin($inputString, $byteLength=8)
	{
		$binaryOutput = '';
		$stringLength = strlen($inputString);
		for($x=0; $x<$stringLength; $x++)
		{
			$charBin = decbin(ord($inputString[$x]));
			$charBin = str_pad($charBin, $byteLength, '0', STR_PAD_LEFT);
			$binaryOutput .= $charBin;
		}

		return $binaryOutput;
	}
	
	// function to convert  provided binary  value to ASCII value
	function bin2asc($binaryInput, $byteLength=8)
	{
		if (strlen($binaryInput) % $byteLength)
		{
			return false;
		}
		// why run strlen() so many times in a loop? Use of constants = speed increase.
		$stringLength = strlen($binaryInput);
		$originalString = '';
		// jump between bytes.
		for($x=0; $x<$stringLength; $x += $byteLength)
		{
			// extract character's binary code
			$charBinary = substr($binaryInput, $x, $byteLength);
			$originalString .= chr(bindec($charBinary)); // conversion to ASCII.
		}
		return $originalString;
	}
 
    // function to decrypt given encrypted string
 	function decrypt($message)
 	{
		$decodedMessage = $this -> urlsafe_b64decode($message);
        $decodedMessage = $this -> xor_string($decodedMessage);
        $decodedMessage = $this -> bin2asc($decodedMessage);
		return $decodedMessage;
	}
	
	//function to generate a single-byte string from number
	 function xor_string($string)
	 {
		 $buf = '';
		 $size = strlen($string);
		 for ($i=0; $i<$size; $i++)
			 $buf .= chr(ord($string[$i]) ^ 255);
		 return $buf;
	 }
	 
	 //function to  encode the string in base64
	function urlsafe_b64encode($string)
	{
		$data = base64_encode($string);
		$data = str_replace(array('+','/','='),array('-','_','.'),$data);
		return $data;
	}
	
    //function to  decode the string from base64 format
	function urlsafe_b64decode($string) 
	{
		$data = str_replace(array('-','_'),array('+','/'),$string);
		$mod4 = strlen($data) % 4;
		if ($mod4) 
		{
			$data .= substr('====', $mod4);
		}
		return base64_decode($data);
	}
	function x_Encrypt($string, $key)
	{
		for($i=0; $i<strlen($string); $i++)
		{
			for($j=0; $j<strlen($key); $j++)
			{
				$string[$i] = $string[$i]^$key[$j];
			}
		}
		return $string;
	}

	function x_Decrypt($string, $key)
	{
		for($i=0; $i<strlen($string); $i++)
		{
			for($j=0; $j<strlen($key); $j++)
			{
				$string[$i] = $key[$j]^$string[$i];
			}
		}
		return $string;
	}
}