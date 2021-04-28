/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

var VTEGeolocalization = {
	

	getLocalization: function(module){
		var selectedid = get_real_selected_ids(module);
		if(selectedid.length <= 0){
			alert(alert_arr.NO_ADDRESS_SELECTED);
		}else{
			window.open('index.php?module=Geolocalization&action=GeolocalizationAjax&file=ShowMap&ids='+selectedid+'&output=embed','_blank');
		}	
	},

	initializeMap: function(ids) {
		var me = this;
		
		if (!window.google || !window.google.maps) {
			console.log('Google Maps library not loaded');
			return;
		}
		
		jQuery.ajax({
			url: 'index.php?module=Geolocalization&action=GeolocalizationAjax&file=ShowMap',
			type: 'POST',
			data: 'fetch=true&ids='+encodeURIComponent(ids),
			success: function(data){
				var markers;
				if (data) {
					try {
						markers = JSON.parse(data);
					} catch (e) {
						console.log('Ivalid JSON returned');
					}
					if (markers) {
						me.displayMarkers(markers);
					}
				}
			}
		});
	},
	
	displayMarkers: function(markers, mapOptions) {
		var me = this,
			info = [],
			markers_tocluster = [],
			good_points = 0,
			marker, i;
		
		var myOptions = jQuery.extend({
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			mapTypeControl: false
		}, mapOptions || {});
		
		var map = new google.maps.Map(document.getElementById("map_canvas"),myOptions);
		var infowindow = new google.maps.InfoWindow(); 
		var bounds = new google.maps.LatLngBounds();
		
		for (i = 0; i <= Object.keys(markers).length; ++i) {
			
			if(markers[i] == null || typeof markers[i] === undefined || markers[i][1] == ''){
				continue;
			}
			if(markers[i][3] == null || markers[i][4] == null){
				continue;
			}
			
			//record name
			info[i] = '<b>'+markers[i][0]+'</b>';
			
			//street
			if(markers[i][1] != '')
				info[i] += '<br>'+markers[i][1];
			//phone
			if(markers[i][2] != '')
				info[i] += '<br>'+markers[i][2];
			
			var pos = new google.maps.LatLng(markers[i][3], markers[i][4]);
			
			bounds.extend(pos);
			marker = new google.maps.Marker({
				position: pos,
				map: map
			});
			markers_tocluster.push(marker);
			good_points++;
			
			google.maps.event.addListener(marker, 'click', (function(marker, i) {
				return function() {
					infowindow.setContent(info[i]);
					infowindow.open(map, marker);
				}
			})(marker, i));
			
		}
		
		if (good_points <=0) {
			
			map = null;
			alert('Unable to generate a map with selected elements');
			window.close();
		
		} else {
		
			map.fitBounds(bounds);
			
			// is this necessary ?
			var opt = [{'minimumClusterSize':'5'}];
			
			//Raggruppamento Marker Clusterer
			var mc = new MarkerClusterer(map,markers_tocluster);
		}
	}

}