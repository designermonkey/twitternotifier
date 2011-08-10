var TwitterNotifier = {};

(function($){

	TwitterNotifier =
	{
		params:
		{
			section: '#section',
			field_param: '#field_param',
			field_msg: '#field_msg',
			options_param: null,
			options_msg: null
		},

		bucket_param: [],
		bucket_msg: [],

		init: function()
		{
			var p = TwitterNotifier.params;

			p.section = $(p.section);
			p.field_param = $(p.field_param);
			p.field_msg = $(p.field_msg);
			p.options_param = p.field_param.children('option');
			p.options_msg = p.field_msg.children('option');

			TwitterNotifier.bucket_param = p.options_param.clone(true);
			TwitterNotifier.bucket_msg = p.options_msg.clone(true);

			TwitterNotifier.changeFields(p.section.val());

			p.section.change(function(ev){
				p.options_param = p.field_param.children('option');
				p.options_msg = p.field_msg.children('option');
				p.options_param.removeAttr('selected');
				p.options_msg.removeAttr('selected');
				TwitterNotifier.changeFields($(this).val());
			});
		},

		changeFields: function(id)
		{
			var p = TwitterNotifier.params;

			p.options_param.remove();
			p.options_msg.remove();

			TwitterNotifier.bucket_param.each(function(idx){

				if($(TwitterNotifier.bucket_param[idx]).hasClass('section-id-' + id))
				{
					$(TwitterNotifier.bucket_param[idx]).clone(true).appendTo(p.field_param);
				}
			});
			TwitterNotifier.bucket_param.each(function(idx){

				if($(TwitterNotifier.bucket_msg[idx]).hasClass('section-id-' + id))
				{
					$(TwitterNotifier.bucket_msg[idx]).clone(true).appendTo(p.field_msg);
				}
			});

		}
	}

})(jQuery);

jQuery(document).ready(function(){
	TwitterNotifier.init();
});