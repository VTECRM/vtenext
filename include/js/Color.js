/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@98866

window.Color = window.Color || {
	
	/**
	 * 
	 */
	HexToRgb: function(hexcolor) {
		if (hexcolor.match(/#?([0-9a-f]{3}){1,2}/i)) {
			hexcolor = hexcolor.replace('#', '');
			return {
				r: parseInt(hexcolor.substr(0, 2), 16),
				g: parseInt(hexcolor.substr(2, 2), 16),
				b: parseInt(hexcolor.substr(4, 2), 16),
			}
		}
		return {r:0, g:0, b:0};
	},
	
	/**
	 * 
	 */
	RgbToHex: function(r,g,b) {
		r = VtString.pad(r.toString(16), 2,'0',0);
		g = VtString.pad(g.toString(16), 2,'0',0);
		b = VtString.pad(b.toString(16), 2,'0',0);
		return r+g+b;
	},
	
	/**
	 *
	 */
	RgbToHsv: function(r, g, b) {
		var min = Math.min(r, g, b),
        	max = Math.max(r, g, b),
        	delta = max - min,
        	h, s, v = max;

		v = Math.floor(max / 255 * 100);
		if (max == 0) return [0, 0, 0];

		s = Math.floor(delta / max * 100);
		var deltadiv = delta == 0 ? 1 : delta;

		if( r == max ) h = (g - b) / deltadiv;
		else if(g == max) h = 2 + (b - r) / deltadiv;
		else h = 4 + (r - g) / deltadiv;

		h = Math.floor(h * 60);
		if( h < 0 ) h += 360;

		return { h:h, s:s, v:v }
	},
	
	/**
	 *
	 */
	HsvToRgb: function(h, s, v) {
		h = h / 360;
		s = s / 100;
		v = v / 100;

		if (s == 0) {
			var val = Math.round(v * 255);
			return {r:val,g:val,b:val};
		}

		var hPos = h * 6,
    		hPosBase = Math.floor(hPos),
    		base1 = v * (1 - s),
    		base2 = v * (1 - s * (hPos - hPosBase)),
    		base3 = v * (1 - s * (1 - (hPos - hPosBase))),
    		red, green, blue;

		if (hPosBase == 0) {red = v; green = base3; blue = base1}
		else if (hPosBase == 1) {red = base2; green = v; blue = base1}
    	else if (hPosBase == 2) {red = base1; green = v; blue = base3}
    	else if (hPosBase == 3) {red = base1; green = base2; blue = v}
    	else if (hPosBase == 4) {red = base3; green = base1; blue = v}
    	else {red = v; green = base1; blue = base2};

    	red = Math.round(red * 255);
    	green = Math.round(green * 255);
    	blue = Math.round(blue * 255);

    	return {r:red, g:green, b:blue};
	},
	
	/**
	 * Gets an appropriate title text color from RGB
	 */
	getTitleTextColor: function(r, g, b) {
		var brightness;
		brightness = (r * 299) + (g * 587) + (b * 114);
		brightness = brightness / 255000;

		if (brightness >= 0.5) {
			return "#000000";
		} else {
			return "#000000"; // Should be white, but keep black for now
		}
	},
	
	/**
	 * Gets an appropriate title text color from HEX
	 */
	getTitleTextColorHEX: function(hex) {
		var me = this,
			RGB = me.HexToRgb(hex);
		
		return me.getTitleTextColor(RGB.r, RGB.g, RGB.b);
	}
		
};