'use strict';

(function($)
{
	var defaults = {
		showSpeed : 'fast', 
		hideSpeed : 'fast',
	};
	
	var titleEl = $('<div>', {
		id    : 'simple-title',
		style : 'display:none'
	});
	
	$(function(){
		$('body').append(titleEl);
	});
	
	$.fn.simpleTitle = function(opts)
	{
		opts = $.extend({}, defaults, opts);
		
		return this.each(function()
		{
			var el    = $(this);
			var title = el.attr('title');
			
			if(title)
			{
				el.removeAttr('title')
				el.data('title', title);
			}
			else
			{
				title = el.data('title');
			}
			
			if(!title || el.data('simpletitle'))
			{
				return;
			}
			
			el.data('simpletitle', true);
			el.hover(function(e)
			{
				titleEl.html(title).show();
				var offset = el.offset();
				offset.top  -= titleEl.outerHeight(true);
				offset.left -= titleEl.outerWidth(true)/2 - el.width()/2;
				titleEl
					.offset(offset)
					.hide()
					.fadeIn(opts.showSpeed);
			},
			function(e)
			{
				titleEl.fadeOut(opts.hideSpeed);
			});

		});
	};
}(jQuery));