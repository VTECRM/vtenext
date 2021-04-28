/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@198024 */

(function($) {
	
	// extends autocomplete to be able to have categories
	$.widget( "custom.vteautocomplete", $.ui.autocomplete, {
		_create: function(cfg) {
			this._super();
			if (this.options.useCategories) {
				this.widget().menu( "option", "items", "> :not(.ui-autocomplete-category)" );
			}
		},
		_renderMenu: function( ul, items ) {
			var me = this,
				currentCategory = "";
				
			if (!this.options.useCategories) return this._super(ul, items);
			 
			$.each( items, function( index, item ) {
				var li;
				if ( item.category != currentCategory ) {
					ul.append( "<li class='ui-autocomplete-category'>" + item.category + "</li>" );
					currentCategory = item.category;
				}
				li = me._renderItemData( ul, item );
				if ( item.category ) {
					li.addClass('ui-menu-item-indented');
				}
			});
		}
    });
	
})(jQuery);