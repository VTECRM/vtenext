/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
function searchshowhide(argg,argg2)
{
	var x, y;
	
	if(!document.getElementById(argg)){
		x = document.getElementById(argg).style;
	}
	if(!document.getElementById(argg2)){
		y = document.getElementById(argg2).style;
	}
	
    if (x != null && x.display=="none" && (y == null || y.display=="none"))
    {
        x.display="block" 
    }
    else {
	    if (y != null)
		{			
				y.display="none";
	    }
        else if (x != null) 
		{	
				x.display="none";
		}
    }
}

function searchhide(argg,argg2)
{
	var x, y;
	
	if(!document.getElementById(argg)){
		x = document.getElementById(argg).style;
	}
	if(!document.getElementById(argg2)){
		y = document.getElementById(argg2).style;
	}

	if (y != null) 
	{
		y.display="none";
	}
    else if (x != null) 
	{
		x.display="none";
	}
}

 function moveMe(arg1) {
    var posx = 0;
    var posy = 0;
    var e = document.getElementById(arg1);
   
    if (!e) {
    	e = window.event;
    }
   
    if (e.pageX || e.pageY)
    {
		posx = e.pageX;
		posy = e.pageY;
    }
    else if (e.clientX || e.clientY)
    {
		posx = e.clientX + document.body.scrollLeft;
		posy = e.clientY + document.body.scrollTop;
    }
 }