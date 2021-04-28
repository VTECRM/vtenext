/* crmv@81309 */

(function ($) {
    $.fn.fixHeader = function (marginTop) {
        return this.each(function () {
            var $table = $(this);
            var $sp = $table.scrollParent();
            var tableOffset = $table.offset().top - (typeof(marginTop) == 'undefined' ? 0 : marginTop);
            var $tableFixed = $("<table />")
                .attr('class', $table.attr('class'))
                .attr('cellspacing', $table.attr('cellspacing'))
                .attr('cellpadding', $table.attr('cellpadding'))
                .attr('align', $table.attr('align'))
                .css({
                position: "fixed",
                    "table-layout": "fixed",
                display: "none",
                    "margin-top": (typeof(marginTop) == 'undefined' ? 0 : marginTop) + "px"
            });
            $table.before($tableFixed);
            $tableFixed.append($table.find("thead").clone());

            $sp.bind("scroll", function () {
                var offset = $(this).scrollTop();
                if (offset > tableOffset && $tableFixed.is(":hidden")) {
                    $tableFixed.show();
                    var p = $table.offset();
                    var offset = $sp.offset();
                    //Set the left and width to match the source table and the top to match the scroll parent
                    $tableFixed.css({
                        left: p.left - $sp.scrollLeft() + "px",
                        top: (offset ? offset.top : 0) + "px",
                    }).width($table.width());

                    //Set the width of each column to match the source table
                    $.each($table.find('th, td'), function (i, th) {
                        $($tableFixed.find('th, td')[i]).width($(th).width());
                    });

                } else if (offset <= tableOffset && !$tableFixed.is(":hidden")) {
                    $tableFixed.hide();
                } else if (!$tableFixed.is(":hidden")) {
                    var p = $table.offset();
                    $tableFixed.css({
                        left: (p.left - $sp.scrollLeft()) + "px"
                    });
                }
            });
        });
    };
    $.fn.unFixHeader = function () {
    	return this.each(function () {
	    	var $table = $(this);
	    	var $sp = $table.scrollParent();
	    	$sp.unbind("scroll");
	    });
    };
})(jQuery);