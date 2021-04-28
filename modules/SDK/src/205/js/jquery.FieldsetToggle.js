/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
jQuery.fn.FieldsetToggle = function(label,state)
{
	this.each(function(){
		var that = this;
		jQuery.FieldsetToggle(that,label,state);
	});

	return this;
};

jQuery.FieldsetToggle = function(o,label,state)
{
	var $obj = jQuery(o);

	function togDivState() {
		if (this.checked) $obj.slideDown().parent().addClass('showing');
		else $obj.fadeOut().parent().removeClass('showing');
	};

	$obj.wrap('<fieldset></fieldset>').parent().prepend(
		jQuery(document.createElement('legend')).append(
			jQuery(document.createElement('label')).append(
					jQuery(document.createElement('input'))
						.attr('type','checkbox')
						.attr('checked',state?true:false)
						.click(togDivState)
					, ' '+label
			)
		)
	);

	if (state) { $obj.show(); } else { $obj.hide(); }

}