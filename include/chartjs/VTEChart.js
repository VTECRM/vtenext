/* crmv@82770 */
/* Some extensions to standard charts */

Chart.types.Doughnut.extend({
	name: "VTEDoughnut",
	
	defaults: this.defaultConfig,
		
	draw: function() {
		var me = this,
			ctx = me.chart.ctx;

		// add some default options
		var options = Chart.helpers.merge({
			drawTotal: false,
			drawLabels: false,
			totalTemplate: '<%=total%>',
			labelTemplate: '<%=percentage%>%',
			labelLightColor: '#ffffff',
			labelDarkColor: '#000000',
		}, me.options);
		
		// call parent
		Chart.types.Doughnut.prototype.draw.apply(me, arguments);
		
		// check if I have to draw something
		if (!options.drawTotal && !options.drawLabels) return;
		
		// calculate the total
		var total = 0;
		for (var i = 0; i < me.segments.length; i++) { 
			total += parseFloat(me.segments[i].value);
		}
		
		//draw the total
		if (options.drawTotal) {
			ctx.fillStyle = options.labelDarkColor;
			ctx.textBaseline = 'middle';
			ctx.textAlign = 'center';
			ctx.font="12px Verdana";
			var text = Chart.helpers.template(options.totalTemplate, {total: total});
			ctx.fillText(text, me.chart.width / 2, me.chart.height / 2, 100);
		}

		// draw the parcentage on the slices
		ctx.textAlign = 'center';
		ctx.textBaseline = 'middle';
		ctx.font = 'normal 10px Verdana';
		for(var i = 0; i < me.segments.length; i++){
			var value = parseFloat(me.segments[i].value),
				color = me.segments[i].fillColor,
				percentage = ((value / total) * 100).toFixed(1);

			// check if there is no enough contrast
			if (colorDistance(options.labelDarkColor, color) < 50) {
				ctx.fillStyle = options.labelLightColor;
			} else {
				ctx.fillStyle = options.labelDarkColor;
			}
							
			if (percentage > 3) {
				var centreAngle = me.segments[i].startAngle + ((me.segments[i].endAngle - me.segments[i].startAngle) / 2),
					rangeFromCentre = (me.segments[i].outerRadius - me.segments[i].innerRadius) / 2 + me.segments[i].innerRadius;
				var x = me.segments[i].x + (Math.cos(centreAngle) * rangeFromCentre);
				var y = me.segments[i].y + (Math.sin(centreAngle) * rangeFromCentre);

				var obj = {
					percentage: percentage,
					value: value,
					total: total,
					label: me.segments[i].label
				}
				var text = Chart.helpers.template(options.labelTemplate, obj);
				ctx.fillText(text, x, y);
			}
		}
	}
});


/* Some extensions to standard charts */

Chart.types.Pie.extend({
	name: "VTEPie",
	
	defaults: this.defaultConfig,
		
	draw: function() {
		var me = this,
			ctx = me.chart.ctx;

		// add some default options
		var options = Chart.helpers.merge({
			drawTotal: false,
			drawLabels: false,
			totalTemplate: '<%=total%>',
			labelTemplate: '<%=percentage%>%',
			labelLightColor: '#ffffff',
			labelDarkColor: '#000000',
		}, me.options);
			
		// call parent
		Chart.types.Pie.prototype.draw.apply(me, arguments);
		
		// check if I have to draw something
		if (!options.drawTotal && !options.drawLabels) return;
		
		// calculate the total
		var total = 0;
		for (var i = 0; i < me.segments.length; i++) { 
			total += parseFloat(me.segments[i].value);
		}
		
		//draw the total
		if (options.drawTotal) {
			ctx.fillStyle = options.labelDarkColor;
			ctx.textBaseline = 'middle';
			ctx.textAlign = 'center';
			ctx.font="12px Verdana";
			var text = Chart.helpers.template(options.totalTemplate, {total: total});
			ctx.fillText(text, me.chart.width / 2, me.chart.height / 2, 100);
		}

		// draw the parcentage on the slices
		ctx.textAlign = 'center';
		ctx.textBaseline = 'middle';
		ctx.font = 'normal 10px Verdana';
		for(var i = 0; i < me.segments.length; i++){
			var value = parseFloat(me.segments[i].value),
				color = me.segments[i].fillColor,
				percentage = ((value / total) * 100).toFixed(1);

			// check if there is no enough contrast
			if (colorDistance(options.labelDarkColor, color) < 50) {
				ctx.fillStyle = options.labelLightColor;
			} else {
				ctx.fillStyle = options.labelDarkColor;
			}
							
			if (percentage > 3) {
				var centreAngle = me.segments[i].startAngle + ((me.segments[i].endAngle - me.segments[i].startAngle) / 2),
					rangeFromCentre = me.segments[i].outerRadius * 0.55;
				var x = me.segments[i].x + (Math.cos(centreAngle) * rangeFromCentre);
				var y = me.segments[i].y + (Math.sin(centreAngle) * rangeFromCentre);
				
				var obj = {
					percentage: percentage,
					value: value,
					total: total,
					label: me.segments[i].label
				}
				var text = Chart.helpers.template(options.labelTemplate, obj);
				ctx.fillText(text, x, y);
			}
		}
	}
});