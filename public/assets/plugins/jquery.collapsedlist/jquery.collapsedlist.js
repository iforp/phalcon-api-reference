'use strict';

(function($)
{
	var defaults = {expanded : '&#9660;', collapsed : '&#9658;'};
	
	$.fn.collapsedList = function(opts)
	{
		opts = $.extend({}, defaults, opts);
		
		return this.each(function()
		{
			var list = $(this);
			
			if(!list.is('UL') || list.data('collapsedList'))
			{
				return;
			}
			
			var toggler = $('<span>', {
				'html'  : opts.collapsed, 
				'class' : 'cl-toggler'
			});
			
			toggler.on('click', function()
			{
				var item = $(this);
				var li = item.parent();
				item.html(li.hasClass('cl-closed') ? opts.expanded : opts.collapsed);
				item.nextAll('ul').slideToggle('fast');
				li.toggleClass('cl-opened cl-closed');
			});
			
			list
				.data('collapsedList', true)
				.addClass('collapsed-list');
				
			list.find('LI:has(LI)')
				.addClass('cl-closed')
				.prepend(toggler);

			$('.cl-selected', list)
				.parentsUntil('.collapsed-list', 'LI.cl-closed')
				.andSelf()
				.find('> .cl-toggler')
				.trigger('click')
		});
	};
}(jQuery));