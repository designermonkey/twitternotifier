var TwitterNotifier = {};

(function($){

	TwitterNotifier =
	{
		params:
		{
			section: '#section',
			fields: '#fields',
			options: null
		},

		bucket: [],

		init: function()
		{
			var p = TwitterNotifier.params;

			p.section = $(p.section);
			p.fields = $(p.fields);
			p.options = p.fields.children('option');

			TwitterNotifier.bucket = p.options.clone(true);

			TwitterNotifier.changeFields(p.section.val());

			p.section.change(function(ev){
				p.options = p.fields.children('option');
				p.options.removeAttr('selected');
				TwitterNotifier.changeFields($(this).val());
			});
		},

		changeFields: function(id)
		{
			var p = TwitterNotifier.params;

			p.options.remove();

			TwitterNotifier.bucket.each(function(idx){

				if($(TwitterNotifier.bucket[idx]).hasClass('section-id-' + id))
				{
					$(TwitterNotifier.bucket[idx]).appendTo(p.fields);
				}
			})

		}
	}

})(jQuery);

jQuery(document).ready(function(){
	TwitterNotifier.init();
});