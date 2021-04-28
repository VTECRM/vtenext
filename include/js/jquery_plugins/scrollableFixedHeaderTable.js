// Author: Jerome Clyde C. Bulanadi

jQuery.fn.scrollableFixedHeaderTable = scrollableFixedHeaderTable;

sfht = {};
function scrollableFixedHeaderTable(widthpx, heightpx,headerRowSize,footerRowSize) {
	/* table initialization */
	if (!jQuery(this).hasClass('scrollableFixedHeaderTable'))
		return;
	var $this = jQuery(this);
	/* fix width for tables witout width attribute */
	$this.attr('width', $this.width());
	if (heightpx > $this.height())
		return;

	$this.wrap('<div style="text-align: left"></div>');

 	headerRowSize = headerRowSize ? headerRowSize - 1: 0;
	headerRowSize = Math.floor(headerRowSize < 0 ? 0 : isNaN(headerRowSize) ? 0 : headerRowSize) ;
	
 	footerRowSize = footerRowSize ? footerRowSize: 0;
	footerRowSize = Math.floor(footerRowSize < 0 ? 0 : isNaN(footerRowSize) ? 0 : footerRowSize) ;

	var $parentDiv = $this.parent();
	var $fixedHeaderHtml = sfht.cloneHeader($parentDiv, headerRowSize);
	var $srcTableHtml = $parentDiv.html();
	var $fixedFooterHtml = sfht.cloneFooter($parentDiv, footerRowSize);

	$this.before('<table cellspacing="0" cellpadding="0" class="sfhtTable"><tr><td><div class="sfhtHeader"></div></td></tr><tr><td><div class="sfhtData"></div></td></tr><tr><td><div class="sfhtFooter"></div></td></tr></table>');
	$parentDiv.find('div:nth(0)').html($fixedHeaderHtml);
	$parentDiv.find('div:nth(1)').html($srcTableHtml);
	$parentDiv.find('div:nth(2)').html($fixedFooterHtml);
	
	var headerId = $this.attr('id') + '_header';
	var $sfhtHeader = $parentDiv.find('.sfhtHeader');
	var $sfhtTable = $sfhtHeader.find('table').attr('id', headerId);
	
	var footerId = $this.attr('id') + '_footer';
	var $sfhtFooter = $parentDiv.find('.sfhtFooter');
	var $sfhtTable2 = $sfhtFooter.find('table').attr('id', footerId);
	
	$this.remove();
	var $sfhtData = $parentDiv.find('.sfhtData');
	$sfhtData.height(heightpx).width(widthpx);
	var $mainTable = $sfhtData.find('table');
	
	var mainTableId = $mainTable.attr('id');
	/* synchronized scrolling */
	$sfhtData.scroll(function() {
		$sfhtHeader.scrollLeft(jQuery(this).scrollLeft());
		$sfhtFooter.scrollLeft(jQuery(this).scrollLeft());
	});
	
	
	/* adjustments */
	sfht.adjustTables($sfhtTable, $mainTable, headerRowSize,footerRowSize,$sfhtTable2);
	sfht.adjustHeader($sfhtHeader, $sfhtData, $mainTable, headerRowSize);
	sfht.adjustFooter($sfhtFooter, $sfhtData, $mainTable, footerRowSize);
	
	var table_lenght = $mainTable.children().attr('rows').length;
	$mainTable.children().children('tr:gt(' + eval(table_lenght-footerRowSize-1) + ')').remove();
}

sfht.adjustHeader = function($sfhtHeader, $sfhtData, $mainTable) {
	var containerWidth = $sfhtData.width();
	var containerInnerWidth = $sfhtData.innerWidth();
	var scrollBarSize = containerWidth - containerInnerWidth;
	var dataTableWidth = $mainTable.width();

	if (!(jQuery.browser.mozilla || jQuery.browser.msie || jQuery.browser.opera)) {
		containerInnerWidth = dataTableWidth >= containerWidth ? containerWidth - 17 : containerWidth;
	}

	if (dataTableWidth >= containerInnerWidth) {
		$sfhtHeader.width(containerInnerWidth);
	} else {
		$sfhtHeader.width(dataTableWidth);
	}
}
sfht.adjustFooter = function($sfhtFooter, $sfhtData, $mainTable) {
	var containerWidth = $sfhtData.width();
	var containerInnerWidth = $sfhtData.innerWidth();
	var scrollBarSize = containerWidth - containerInnerWidth;
	var dataTableWidth = $mainTable.width();

	if (!(jQuery.browser.mozilla || jQuery.browser.msie || jQuery.browser.opera)) {
		containerInnerWidth = dataTableWidth >= containerWidth ? containerWidth - 17 : containerWidth;
	}

	if (dataTableWidth >= containerInnerWidth) {
		$sfhtFooter.width(containerInnerWidth);
	} else {
		$sfhtFooter.width(dataTableWidth);
	}
}


sfht.adjustTables = function($sfhtTable, $mainTable, headerRowSize,footerRowSize,$sfhtTable2) {
	var tdWidthArr = new Array();
	var adjTableWidth = 0;

	var totalWidth = 0;
	var idAdjWidth = sfht.getSfhtVar($mainTable.attr('id'));

	//var id = '#' + $mainTable.attr('id'); // IE compatibility
	var id = $mainTable.attr('id');
	//var queryStr = id + ' tr:nth(0) td';
	var idPrefix = 'table[id=' + id + '] tr:lt(' + (headerRowSize + 1) + ')';
	jQuery(idPrefix).find('td, th').each(function(index) {
		var $this = jQuery(this);
		var actualWidth = parseInt($this.width());
		var attrWidth = parseInt($this.attr('width'));
		var plusWidth = attrWidth > actualWidth ? attrWidth : actualWidth;
		totalWidth += plusWidth;
		tdWidthArr[index] = plusWidth;
		$this.width(plusWidth);
		$sfhtTable.find('td:nth(' + index + '), th:nth(' + index + ')').width(plusWidth);
	});
	var idPrefix = 'table[id=' + id + '] tr:gt(' + (eval($mainTable.attr('rows').length-footerRowSize-1)) + ')';
	jQuery(idPrefix).find('td, th').each(function(index) {
		var $this = jQuery(this);
		var actualWidth = parseInt($this.width());
		var attrWidth = parseInt($this.attr('width'));
		var plusWidth = attrWidth > actualWidth ? attrWidth : actualWidth;
		totalWidth += plusWidth;
		tdWidthArr[index] = plusWidth;
		$this.width(plusWidth);
		$sfhtTable2.find('td:nth(' + index + '), th:nth(' + index + ')').width(plusWidth);
	});
	
	
	adjTableWidth = totalWidth;
	// $sfhtTable.width(totalWidth);
	// $mainTable.width(totalWidth);
	
	/* Register this variable to sfht globals */
	sfht[idAdjWidth] = {'lastWidth': adjTableWidth, 'tdWidths': tdWidthArr};
}

sfht.getSfhtVar = function(id) {
	return id + 'Widths';
}


sfht.getFixedHeader = function(id) {
	return (jQuery('table[id=' + id + '_header]'));
}

sfht.getFixedFooter = function(id) {
	return (jQuery('table[id=' + id + '_footer]'));
}

sfht.fillState = function (i) {
	  if (i == 0) {
	    return;
	}
	state = '';
	for (var x = 0; x < i; x++) {
	    state += '1';
	}
	return state ;
}

sfht.loadAttributes = function(dest, source) {
	var attributes = jQuery(source).listAttrs();
	jQuery(attributes).each(function(){
		var at = this + '';
		jQuery(dest).attr(at, jQuery(source).attr(at));
	});
}

sfht.cloneHeader = function(parentDiv, headerRowSize) {
	var rowIndex = headerRowSize;
	if (jQuery.fn.listAttrs) {
	  var $container = jQuery("<div><table><thead></thead></table></div>");
		var $clone = $container.find('table:eq(0)');
		var tableNode = jQuery(parentDiv).children().first();
		sfht.loadAttributes($clone,tableNode);
		sfht.loadAttributes($clone.children().first(),tableNode.children().first());

		var $thead = $clone.find('thead');
		for (var _i = 0; _i <= rowIndex; _i++) {
			var $rowHeaderNode = tableNode.children().first().children(':nth(' + _i + ')');
			var $cloneRow = jQuery('<tr></tr>');
			sfht.loadAttributes($cloneRow,$rowHeaderNode);
			$cloneRow.html($rowHeaderNode.html());
			$cloneRow.appendTo($thead);
		}
	  return $container.html();
	} else {
		var $cloned = jQuery(parentDiv).clone();
		$cloned.children().first().children().children('tr:gt(' + headerRowSize + ')').remove();
		return $cloned.html();
	}
}
sfht.cloneFooter = function(parentDiv, headerRowSize) {
	var table_lenght = jQuery(parentDiv).children().attr('rows').length;
	var rowIndex = table_lenght-headerRowSize;
	if (jQuery.fn.listAttrs) {
	  var $container = jQuery("<div><table><tfoot></tfoot></table></div>");
		var $clone = $container.find('table:eq(0)');
		var tableNode = jQuery(parentDiv).children().last();
		sfht.loadAttributes($clone,tableNode);
		sfht.loadAttributes($clone.children().last(),tableNode.children().last());

		var $thead = $clone.find('tfoot');
		for (var _i = 0; _i <= rowIndex; _i++) {
			var $rowHeaderNode = tableNode.children().last().children(':nth(' + _i + ')');
			var $cloneRow = jQuery('<tr></tr>');
			sfht.loadAttributes($cloneRow,$rowHeaderNode);
			$cloneRow.html($rowHeaderNode.html());
			$cloneRow.appendTo($thead);
		}
	  return $container.html();
	} else {
		var $cloned = jQuery(parentDiv).clone();
		$cloned.children().last().children().children('tr:lt(' + eval(table_lenght-headerRowSize) + ')').remove();
		return $cloned.html();
	}
}

function replaceOneChar(s,c,n){
	var re = new RegExp('^(.{'+ --n +'}).(.*)$','');
	return s.replace(re,'$1'+c+'$2');
};

