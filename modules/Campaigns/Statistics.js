/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@126155 crmv@101503 crmv@152532 */

if (typeof(StatisticsScript) == 'undefined') {
	var StatisticsScript = {
		
		showTab: function(module, record) {
			var me = this;
			if (jQuery('#StatisticsTab').length > 0) {
				jQuery('#StatisticsTab').show();
				return;
			}
			jQuery('#DetailExtraBlock').append('<div id="StatisticsTab" class="detailTabsMainDiv" style="display:none"></div>');
			me.getStatistics(module, record);
		},
		
		hideTab: function() {
			jQuery('#StatisticsTab').hide();
		},
		
		getStatistics: function(module, record) {
			jQuery('#status').show();
			jQuery.ajax({
				'url': 'index.php?module=Campaigns&action=CampaignsAjax&file=DetailViewAjax&ajxaction=GETSTATISTICS&recordid='+record+'&src_module='+module,
				'type': 'POST',
				success: function(data) {
					jQuery('#status').hide();
					jQuery('#StatisticsTab').html(data);
					jQuery('#StatisticsTab').show();
					
					if (jQuery.fn.selectpicker) {
						setupSelectPicker('statistics_newsletter', {
							liveSearch: true, 
							right: true
						});
					}
				}
			});
		},
		
		filter_statistics_newsletter: function(record,obj) {
			var me = this;
			
			jQuery('#status').show();
		 	jQuery.ajax({
				'url': 'index.php?module=Campaigns&action=CampaignsAjax&file=DetailViewAjax&ajxaction=FILTERSTATISTICS&ajax=true&record='+record+'&statistics_newsletter='+obj.value,
				'type': 'POST',
				'dataType': 'json',
				success: function(data) {
					if (data && data.success) {
						jQuery('#status').hide();
						me.initChart(data.chart_data, data.chart_plugin); // crmv@172994
						jQuery('#RLContents').html(data.html);
					}
				}
			});
		},
		
		export_statistics: function(module,record,title,nr_rows){
			if(nr_rows > 0){
				document.location.href = "index.php?module="+module+"&action="+module+"Ajax&file=CreateXL&currmodule="+module+"&record="+record+"&title="+title;
			}else{
				alert(alert_arr.ERR_TARGET_XLS);
			}
		},

		create_target: function(module,record,title,targetname,obj,nr_rows){
			if(nr_rows > 0){
				fnvshobj(obj,'ModTarget');
				target=getObj('ModTarget');
				placeAtCenter(target);
				jQuery('#targetname').val(targetname);
				jQuery('#title').val(title);
				jQuery('#campaignid').val(record);
			}else{
				alert(alert_arr.ERR_TARGET);
			}
		},

		saveTarget: function(){
			jQuery('#status').show();
			VteJS_DialogBox.progress(); // crmv@186105
			var targetname = jQuery('#targetname').val();
			var title = jQuery('#title').val();
			var campaignid = jQuery('#campaignid').val();
			
			document.location.href = "index.php?module=Campaigns&action=CampaignsAjax&file=CreateTarget&targetname="+targetname+"&title="+title+"&campaignid="+campaignid;
		},
		
		// crmv@172994
		initChart: function(data, plugin) {
			var me = this;
			if (plugin === 'ChartJS') {
				var datasets = data['datasets'] || [];
				var labels = data['labels'] || [];
				var anchors = data['anchors'] || [];
				
				if (VTECharts.chartCache && VTECharts.chartCache['statistics'] && VTECharts.chartCache['statistics'].instance) {
					VTECharts.chartCache['statistics']['instance'].destroy();
				}
				
				VTECharts.generateCanvasChart('statistics', {
					type: 'BarHorizontal',
					values: {
						datasets: datasets,
						labels: labels,
						anchors: anchors,
					},
					options: {
						responsive: true,
						legend: true,
						drawLabels: true,
						labelType: 'ChartLabels',
						barStrokeWidth: 1,
						legendTemplate: "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span class=\"legend-box\" style=\"background-color:<%=datasets[i].fillColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>",
						showTooltips: true,
						tooltipXOffset: 150,
					},
				}, {
					initialize: true,
					legend: true,
					customTooltips: false,
					canvasOnClick: function(e) {
						if (VTECharts.chartCache && VTECharts.chartCache['statistics'] && VTECharts.chartCache['statistics'].instance) {
							var chart = VTECharts.chartCache['statistics']['instance'];
							var activePoints = chart['getBarsAtEvent'](e);
							me.onChartClick(chart, activePoints);
						}
					},
				});
			} else if (plugin === 'pChart') {
				jQuery("#StatisticChar").attr("src", "cache/charts/StatisticsChart.png?"+(new Date()).getTime()); // crmv@38600
			}
		},
		
		onChartClick: function(chartInstance, pts) {
			var me = this;
			
			if (!pts || pts.length == 0) {
				return;
			}
			
			var anchor = pts[0].anchor;
			var relatedList = jQuery('*[relation_id="'+anchor+'"]');
			
			if (relatedList.length > 0) {
				var scrollTop = relatedList.offset().top;
				scrollTop -= parseInt(jQuery('#Buttons_Detail_Placeholder').height());
				relatedList.find('.related-show-icon:visible').click();
				jQuery('html').animate({scrollTop: scrollTop}, 500);
			}
		},
		// crmv@172994e
		
	}
}