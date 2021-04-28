/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* functions for charts */


function createChart(reportid) {
	jQuery('#div_create_chart').show();

}

function closeCreateChart(reportid) {
	jQuery('#div_create_chart').hide();

}

function saveChart() {

}

function chartSelectType(chartbtn) {
	var tokens = chartbtn.id.split('_');

	if (tokens.length > 0) {
		var ctype = tokens[tokens.length-1];
		jQuery('#chart_type').val(ctype).change(); // also fire change event
		//jQuery(chartbtn).css('border: 1px solid #505050');
	}
	return false;
}

function generatePreview(event, formdata) { // crmv@97862

	var chtype = jQuery('#chart_type').val();
	if (chtype == '') return;

	if (event.target.name == 'button' || event.target.name == 'chartname') return;

	if( typeof generatePreview.ajaxrun == 'undefined' ) generatePreview.ajaxrun = false;

	// skip multiple ajax calls
	if (generatePreview.ajaxrun == true) return;

	formdata = formdata || jQuery('#qcform form[name=QcEditView]').serialize(); // crmv@97862
	var container = jQuery('#chart_create_preview');
	var girella = jQuery('#chart_create_preview_wait');
	var baseurl = 'index.php?module=Charts&action=ChartsAjax&file=ChartPreview';

	// remove unwanted fields
	formdata = formdata.replace(/module=[^&]*?&/, '').replace(/action=[^&]*?&/, '').replace(/file=[^&]*?&/, '');

	container.hide();
	girella.show();
	generatePreview.ajaxrun = true;

	jQuery.ajax({
		type: 'POST',
		url: baseurl,
		data: formdata,
		error: function() {
			container.html('Ajax Error');
			girella.hide();
			container.show();
			generatePreview.ajaxrun = false;
		},
		success: function(data, tstatus) {
			container.html(data);
			// trick to show div after image has loaded
			var chartImg = jQuery(container).find('img');
			if (chartImg.length > 0) {
				chartImg.load(function() {
					girella.hide();
					container.show();
					generatePreview.ajaxrun = false;
				});
			} else {
				// new chart, simply show it
				girella.hide();
				container.show();
				generatePreview.ajaxrun = false;
			}
		}
	});
}

// tooltip handling

var chartX = 0;
var chartY = 0;
var chartShiftX = {};
var chartShiftY = {};
var currentTooltipDivID = null;

// calculate the distance of 2 colors (0-255) as
// the maximum distance of single components
function colorDistance(col1, col2) {
	var r1=parseInt(col1.substr(1,2),16);
    var g1=parseInt(col1.substr(3,2),16);
    var b1=parseInt(col1.substr(5,2),16);

    var r2=parseInt(col2.substr(1,2),16);
    var g2=parseInt(col2.substr(3,2),16);
    var b2=parseInt(col2.substr(5,2),16);

    return Math.max(Math.abs(r1-r2), Math.abs(g1-g2), Math.abs(b1-b2));
}

function chartTipShow(mapid, chid) {
	TooltipDivID = 'chart_tooltip_'+chid;
	var element = jQuery('#'+TooltipDivID);

	// stop animations
	element.stop(true, true);

	// set content
	contLabel = jQuery('#chart_maparea_label_'+chid+'_'+mapid).val();
	contValue = jQuery('#chart_maparea_value_'+chid+'_'+mapid).val();
	contPercent = jQuery('#chart_maparea_percent_'+chid+'_'+mapid).val();
	if (contPercent != '') contPercent += '%';
	contColor = jQuery('#chart_maparea_color_'+chid+'_'+mapid).val();

	// use white text if the background is too dark
	var textColor = '#000000';
	if (colorDistance(contColor, textColor) < 50) textColor = '#f0f0f0';

	element.find('#chart_tooltip_title_'+chid).text(contLabel);
	element.find('#chart_tooltip_value_'+chid).text(contValue).css({'background-color': contColor, 'color': textColor });
	element.find('#chart_tooltip_percent_'+chid).text(contPercent).css({'background-color': contColor, 'color': textColor});


	moveDiv(TooltipDivID);
	element.fadeIn();

	// trick to get base x,y
	if (chartShiftX[TooltipDivID] == undefined) {
		var baseofs =jQuery('#'+TooltipDivID).css({left: '0px', top: '0px'}).offset();
		chartShiftX[TooltipDivID] = {x: baseofs.left, y:baseofs.top}
	}

	currentTooltipDivID = TooltipDivID;
}

function chartTipHide(mapid, chid) {
	TooltipDivID = 'chart_tooltip_'+chid;

	var element = jQuery('#'+TooltipDivID);

	element.fadeOut();
	currentTooltipDivID = null;
}

/* Move the div to the mouse location */
function moveDiv(TooltipDivID) {
	var dx = 0;
	var dy = 0;

	if (chartShiftX[TooltipDivID] != undefined) {
		dx = chartShiftX[TooltipDivID].x;
		dy = chartShiftX[TooltipDivID].y;
	}
	jQuery('#'+TooltipDivID).css({left:(chartX - dx + 10)+'px', top: (chartY - dy + 10)+'px'});
}

/*
// DEPRECATED
jQuery(document).mousemove(function(event){
	chartX = event.pageX;
	chartY = event.pageY;
	if (currentTooltipDivID) {
		moveDiv(currentTooltipDivID);
	}
});
*/

/* crmv@82770 - ChartJS functions */ 
// TODO: move all the above functions inside here

var VTECharts = {
	
	// config
	autoDefer: true,			// if true, in case the canvas is not visible, wait until it becomes visible
	deferPollingInterval: 250,	// check for the canvas to be visible avery this ms
	deferPollingTimeout: 5000,	// give up after this ms
	
	// internal vars
	deferPollingTimers: {},
	chartCache: {},
	chartIds: {},
	
	initialHeights: {},			// crmv@109279
	
	deferCanvasChart: function(chartid, data, opts) {
		var me = this;
		
		opts = opts || {};
		
		// poll to see if the canvas become visible
		if (me.deferPollingTimers[chartid]) return;
	
		var tim = setInterval(function() {
			var now = (new Date()).getTime();
			var t = me.deferPollingTimers[chartid];
			var canvas = document.getElementById("chart_img_"+chartid);

			if (jQuery(canvas).is(':visible')) {
				// ok, stop the interval and trigger the chart creation
				clearInterval(t.timer);
				delete me.deferPollingTimers[chartid];
				opts.noDefer = true;
				me.generateCanvasChart(chartid, data, opts);
			} else {
				// check timeout
				if (now - t.ts > me.deferPollingTimeout) {
					// abort!
					clearInterval(t.timer);
					delete me.deferPollingTimers[chartid];
					console.log('Canvas not ready after '+me.deferPollingTimeout+'ms, aborted.');
				} else {
					// keep going
				}
			}
		}, me.deferPollingInterval);

		me.deferPollingTimers[chartid] = {
			timer: tim,
			ts: (new Date()).getTime()
		}
		
	},
	
	generateCanvasChart: function(chartid, data, opts) {
		var me = this;
		var canvas = document.getElementById("chart_img_"+chartid);

		if (!canvas) {
			console.log('Unable to find Canvas element');
			return;
		}
		
		// crmv@172994
		opts = jQuery.extend({}, {
			customTooltips: true,
		}, opts || {});
		// crmv@172994e

		if (opts.initialize) {
			// clear all old data/cache associated with the chart
			delete me.chartCache[chartid];
			delete me.chartIds[chartid];
			// crmv@109279
			if (!me.initialHeights[chartid]) {
				me.initialHeights[chartid] = jQuery(canvas).attr('height');
			}
			// crmv@109279e
		}

		if (!me.chartCache[chartid]) me.chartCache[chartid] = {chartid: chartid, data: data};
		
		if (!jQuery(canvas).is(':visible')) {
			if (me.autoDefer && !opts.noDefer) {
				// canvas is not visible, so defer the creation of the chart until it is shown
				me.deferCanvasChart(chartid, data, opts);
				//console.log('Chart #'+chartid+' creation deferred until canvas element becomes visible');
			} else {
				console.log('Canvas is not visible, but deferring creation is disabled');
			}
			return;
		}
		
		var	ctx = (canvas ? canvas.getContext("2d") : null);
			
		if (!ctx) {
			console.log('Unable to get a Canvas Context');
			return;
		}
		if (!data) {
			console.log('No data available');
			// write a simple text on canvas
			ctx.font = "16px sans-serif";
			ctx.textAlign = "center";
			ctx.fillText(alert_arr.LBL_CHART_NO_DATA, canvas.clientWidth/2, canvas.clientHeight/2); // crmv@172355
			return;
		// crmv@172355
		} else if (data === 'NO_SUMMARY') {
			console.log('Report doesn\'t have summary');
			// write a simple text on canvas
			ctx.font = "16px sans-serif";
			ctx.textAlign = "center";
			ctx.fillText(alert_arr.LBL_CHART_NO_SUMMARY, canvas.clientWidth/2, canvas.clientHeight/2);
			return;
		}
		// crmv@172355e
		
		var chart;
		var options = data.options || {};
		
		// fix for bar charts which don't have the total property
		if (data.type != 'Pie' && data.type != 'Ring' && opts.customTooltips) { // crmv@172994
			options.customTooltips = function(tooltip) {
				if (tooltip) {
					var pieces = tooltip.text.split('###');
					var text = '';
					if (pieces[0]) {
						text += pieces[0]+': ';
					}
					text += formatUserNumber(+pieces[1]); // crmv@162794
					if (chart.total != 0) {
						text += ' ('+Math.round(100.0 * parseFloat(pieces[1]) / total)+'%)';
					}
					tooltip.text = text;
					tooltip.custom = false;
					// and redraw with standard function
					tooltip.draw();
				}
			}
		}
		
		if (options.drawLabels) {
			switch (options.labelType) {
				case 'ChartValuesRaw':
					// use a different template
					options.labelTemplate = '<%=value%>';
					break;
				case 'ChartValuesPercent':
					// ok, by default percentage are drawn
					break;
				case 'ChartLabels':
					// show the label
					options.labelTemplate = '<%=label%>';
					break;
				default:
					delete options.drawLabels;
					break;
			}
		}
		
		// crmv@109279
		// make scrollable if there are many labels
		// TODO: other chart types
		if (data.type == 'BarHorizontal') {
			if (data.values.labels.length >= 25) {
				var $canvas = jQuery(canvas),
					$contDiv = $canvas.parent();
				$contDiv.css('overflow-y', 'scroll').height(me.initialHeights[chartid]);
				$canvas.attr('height', 50 + data.values.labels.length*20);
			} else if (me.initialHeights[chartid]) {
				// restore height
				var $canvas = jQuery(canvas),
					$contDiv = $canvas.parent();
				$contDiv.css('overflow-y', 'initial').height('initial');
				$canvas.attr('height', me.initialHeights[chartid]);
			}
		// crmv@168395
		} else if (data.type == 'BarVertical') {
			// crmv@184319
			var len = data.values.labels.reduce(function(acc, item) {
				return Math.max(acc, item.length);
			}, 0)

			if (len >= 20) {
				var $canvas = jQuery(canvas),
					$contDiv = $canvas.parent();

				$contDiv.css('overflow-y', 'scroll').height(me.initialHeights[chartid]);
				$canvas.attr('height', +me.initialHeights[chartid] + 10 * (len-20));
			}
			// crmv@184319e
		}
		// crmv@109279e crmv@168395e

		if (data.type == 'Pie') {
			 chart = new Chart(ctx).VTEPie(data.values,options);
			var eventFn = 'getSegmentsAtEvent';
		} else if (data.type == 'Ring') {
			chart = new Chart(ctx).VTEDoughnut(data.values,options);
			var eventFn = 'getSegmentsAtEvent';
		} else if (data.type == 'BarVertical') {
			chart = new Chart(ctx).Bar(data.values,options);
			var eventFn = 'getBarsAtEvent';
		} else if (data.type == 'BarHorizontal') {
			chart = new Chart(ctx).HorizontalBar(data.values,options);
			var eventFn = 'getBarsAtEvent';
		} else if (data.type == 'Line') {
			chart = new Chart(ctx).Line(data.values,options);
			var eventFn = 'getPointsAtEvent';
		} else if (data.type == 'Scatter') {
			chart = new Chart(ctx).Scatter(data.values,options);
			var eventFn = 'getPointsAtEvent';
		} else {
			console.log('Chart type '+data.type+' not supported');
		}
		
		// something went wrong
		if (!chart) return;
		
		// add the total property for lines chart
		if (data.type != 'Pie' && data.type != 'Ring') {
			var allvalues = data.values.datasets[0].data;
			var total = 0;
			for (var i=allvalues.length; i--;) {
				total += parseFloat(allvalues[i]);
			}
			chart.total = total;
		}

		// save the instance
		me.chartCache[chartid]['instance'] = chart;
		
		// crmv@177382
		if (data.limited === true) {
			jQuery('#chart_limited_'+chartid).show();
		}
		// crmv@177382e
		
		// crmv@172994
		// attach the onclick event
		if (typeof opts.canvasOnClick == 'function') {
			canvas.onclick = opts.canvasOnClick;
		} else {
			canvas.onclick = function(e){
				var activePoints = chart[eventFn](e);
				me.onCanvasClick(chartid, activePoints);
			};
		}
		// crmv@172994e
		
		if (options.legend) {
			var htmlLegend = chart.generateLegend();
			me.initLegend(chartid, htmlLegend);
		}
	},
	
	initLegend: function(chartid, text) {
		var me = this;
		var legendCont = jQuery('#chart_legend_'+chartid);
		
		if (legendCont) {
			var level = me.getCurrentLevel(chartid),
				maxlevels = me.getMaxLevel(chartid),
				levelTitle = me.getLevelTitle(chartid, level);
				
			legendCont.html(text).removeClass('hidden');
			if (levelTitle && maxlevels > 1) {
				legendCont.find('.legend-title').text(levelTitle+':').removeClass('hidden');
			} else {
				legendCont.find('.legend-title').addClass('hidden').empty();
			}
			
			// add listener for mouseover
			legendCont.on('mouseenter', function() {
				jQuery(this).removeClass('in').addClass("fade out");
			});
			legendCont.on('mouseleave', function() {
				jQuery(this).removeClass('out').addClass("fade in");
			});
		}
		
	},
	
	refreshAll: function() {
		var me = this;
		
		for (var chartid in me.chartCache) {
			if (chartid !== null) me.refresh(chartid);
		}
	},
	
	refresh: function(chartid) {
		var me = this;
		var cache = me.chartCache[chartid];
		
		if (cache && cache.data) {
			// crmv@172355
			if (typeof cache.data !== 'string') {
				var data = jQuery.extend(true, {}, cache.data);
			} else {
				var data = cache.data;
			}
			// crmv@172355e
			me.destroyCanvas(chartid);
			me.generateCanvasChart(chartid, data);
		}
	},
	
	destroyCanvas: function(chartid) {
		var me = this;
		var cache = me.chartCache[chartid];
		
		if (cache) {
			if (cache.instance) {
				cache.instance.clear();
				cache.instance.destroy();
				delete cache.instance;
			}
			delete cache;
			delete me.chartCache[chartid];
		}
		// it looks that some event listeners remains attached to the canvas, so i need to clone it
		var canvas = jQuery('#chart_img_'+chartid);
		if (canvas) {
			canvas.replaceWith(canvas.clone(false));
		}
	},
	
	onCanvasClick: function(chartid, pts) {
		var me = this;
		
		// click outside chart
		if (!pts || pts.length == 0) {
			//console.log('No data under the clicked point');
			return;
		}
		
		var label = pts[0].label,
			value = pts[0].value,
			curlevel = me.getCurrentLevel(chartid),
			maxlevel = me.getMaxLevel(chartid);
		
		// check maxlevel
		if (curlevel >= maxlevel) {
			console.log('No more levels');
			return;
		}
		
		var dataid = me.findDataId(chartid, label, value);
		if (dataid !== null) {
			var ids = (me.chartIds[chartid] ? me.chartIds[chartid].slice() : []);
			if (ids.indexOf(dataid) >= 0) {
				console.log('Dataid already present in list');
			} else {
				ids.push(dataid);
				me.getSubchartData(chartid, ids, function(subdata) {
					if (subdata && subdata.data) {
						me.drawSubchart(chartid, dataid, subdata.data);
					} else {
						console.log('Invalid subdata returned');
					}
				});
			}
		} else {
			console.log('No dataid found for the clicked point');
		}
	},
	
	findDataId: function(chartid, label, value) {
		var me = this;
		var dataid = null;
		
		var cache = me.chartCache[chartid];
		
		if (cache && cache.data && cache.data.values) {
			if (cache.data.type == 'Pie' || cache.data.type == 'Ring') {
 				// data format for pies and doughnuts
				for (var i=0; i<cache.data.values.length; ++i) {
					var obj = cache.data.values[i];
					if (obj.value == value && obj.label == label && obj.dataid !== undefined) {
						dataid = obj.dataid;
						break;
					}
				}
			} else {
				// other data type
				var dset = cache.data.values.datasets[0];
				for (var i=0; i<dset.data.length; ++i) {
					var dvalue = dset.data[i],
						dlabel = cache.data.values.labels[i],
						ddataid = dset.dataids[i];
					if (dvalue == value && dlabel == label && ddataid !== undefined) {
						dataid = ddataid;
						break;
					}
				}
			}
		}
		
		return dataid;
	},
	
	getCurrentLevel: function(chartid) {
		var me = this;
		var ids = me.chartIds[chartid] || [];
		
		return ids.length + 1;
	},
	
	getMaxLevel: function(chartid) {
		var me = this;
		var cache = me.chartCache[chartid];
		if (cache && cache.data.maxlevels) return cache.data.maxlevels;
		return 1;
	},
	
	getLevelTitle: function(chartid, level) {
		var me = this;
		var cache = me.chartCache[chartid];
		
		if (cache && cache.data && cache.data.leveltitles) {
			return cache.data.leveltitles[level-1] || '';
		}
		return null;
	},
	
	getSubchartData: function(chartid, subids, callback, errorCallback) {
		var me = this;
		
		subids = subids || [];
		
		var baseurl = 'index.php?module=Charts&action=ChartsAjax&file=Subchart&subaction=getdata';
		var formdata = {
			chartid: chartid,
			level: subids.length + 1,
			dataids: JSON.stringify(subids),
		};

		jQuery.ajax({
			type: 'POST',
			url: baseurl,
			data: formdata,
			error: function() {
				if (typeof errorCallback == 'function') errorCallback();
			},
			success: function(data, tstatus) {
				try {
					data = JSON.parse(data);
				} catch (e) {
					console.log('Invalid JSON data', data);
					if (typeof errorCallback == 'function') errorCallback();
					return;
				}
				if (!data.success || !data.result) {
					console.log('Error: ', data);
					if (typeof errorCallback == 'function') errorCallback();
					return;
				}
				if (typeof callback == 'function') callback(data.result);
			}
		});
	},
	
	drawSubchart: function(chartid, lastdataid, data, opts) {
		var me = this,
			cache = me.chartCache[chartid];
		
		opts = opts || {};

		if (lastdataid !== null && data && data.values && cache) {
			if (!me.chartIds[chartid]) me.chartIds[chartid] = [];
			
			if (me.chartIds[chartid].indexOf(lastdataid) >= 0) {
				console.log('Dataid already present in list');
				return;
			}

			me.chartIds[chartid].push(lastdataid);
			
			if (data.levelids) {
				if (opts.backward) {
					me.removeBreadcrumbsAfter(chartid, data.levelids[lastdataid]);
				} else {
					me.addBreadcrumb(chartid, data.levelids[lastdataid]);
				}
			}
			
			// remove some cache
			me.destroyCanvas(chartid);
			
			// redraw
			me.generateCanvasChart(chartid, data);
		}
	},
	
	drawHomeChart: function(chartid) {
		var me = this,
			cache = me.chartCache[chartid];
		
		if (cache) {
			me.getSubchartData(chartid, [], function(subdata) {
				me.removeBreadcrumbs(chartid);
				me.destroyCanvas(chartid);
				me.chartIds[chartid] = [];
				me.generateCanvasChart(chartid, subdata.data);
			});
		}
	},
	
	addBreadcrumb: function(chartid, bcdata) { 
		var me = this,
			dataid = bcdata.dataid;
			
		if (typeof dataid !== undefined && dataid !== null) {
			var level = me.getCurrentLevel(chartid);
			var title = me.getLevelTitle(chartid, level-1);
			var cont = jQuery('#chart_bccont_'+chartid);
			
			// add the root
			if (cont.children().length == 0) {
				cont.append('<span class="chart-breadcrumb home-bc"><a href="javascript:void(0);" onclick="VTECharts.onBreadcrumbClick('+chartid+');">'+'Base'+'</a></span>');
			}
			// remove last class
			cont.find('span.chart-breadcrumb').removeClass('last-bc');
			
			// set the new bc 
			var txt = '';
			if (bcdata.label) {
				txt = (title ? title+': ' : '') + bcdata.label;
			} else {
				txt = 'Level '+bcdata.level;
			};
			cont.append('<span class="chart-breadcrumb last-bc" data-bcid="'+dataid+'"><span class="bc-sep"></span> <a href="javascript:void(0);" onclick="VTECharts.onBreadcrumbClick('+chartid+', \''+dataid+'\');">'+txt+'</a></span>');
			
			// show it!
			if (cont.hasClass('hidden')) {
				cont.hide().removeClass('hidden').slideDown(400);
			}
		}
	},
	
	removeBreadcrumbs: function(chartid) {
		var me = this;
		var cont = jQuery('#chart_bccont_'+chartid);
		
		cont.slideUp(400, function() {
			jQuery(this).addClass('hidden').empty();
		});
	},
	
	removeBreadcrumbsAfter: function(chartid, bcdata) {
		var me = this,
			dataid = bcdata.dataid;
			
		if (typeof dataid !== undefined && dataid !== null) {
			var remove = false;
			var cont = jQuery('#chart_bccont_'+chartid);
			cont.find('span.chart-breadcrumb').each(function() {
				var bid = this.dataset.bcid;
				if (bid == dataid) {
					remove = true;
				} else if (remove) {
					jQuery(this).remove();
				}
			});
		}
	},
	
	onBreadcrumbClick: function(chartid, dataid) {
		var me = this;
		
		if (dataid) {
			// go back to the right place
			
			var ids = me.chartIds[chartid].slice(),
				idx = ids.indexOf(dataid),
				level = idx+2,
				curlevel = me.getCurrentLevel(chartid);
			
			if (level == curlevel) return;
			if (idx >= 0) {
				ids = ids.slice(0, idx+1);
				me.getSubchartData(chartid, ids, function(subdata) {
					if (subdata && subdata.data) {
						me.chartIds[chartid] = me.chartIds[chartid].slice(0, idx);
						me.drawSubchart(chartid, dataid, subdata.data, {backward: true});
					} else {
						console.log('Invalid subdata returned');
					}
				});
			} else {
				console.log('Unable to find the level');
			}
		} else {
			// go home
			me.drawHomeChart(chartid);
		}
	},
	
}