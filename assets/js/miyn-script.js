(function ($) {
	"use strict";
    $(document).ready(function($){
    	$('.widgets-toggle-area .widgets-section-titles').on('click', function() {
    		if($(this).closest('.widgets-toggle-area').hasClass('collapse')) {
    			$(this).closest('.widgets-toggle-area').removeClass('collapse');
    			$(this).closest('.widgets-toggle-area').find('.miyn-widgets-section-contents').slideDown();
    		} else {
    			$(this).closest('.widgets-toggle-area').addClass('collapse');
    			$(this).closest('.widgets-toggle-area').find('.miyn-widgets-section-contents').slideUp();
    		}
    	});

    	// UPLOAD BANNER IMAGE
		$('.edit-banner a').on('click', function(){
			var button = $(this),
			custom_uploader = wp.media({
				title: 'banner image',
				library : {
					// uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
					type : 'image'
				},
				button: {
					text: 'Use this image' // button label text
				},
				multiple: false
			}).on('select', function() { // it also has "open" and "close" events
				var attachment = custom_uploader.state().get('selection').first().toJSON();
				var attachid = attachment.id;
				// button.html('<img src="' + attachment.url + '">').next().val(attachment.id).next().show();
				$('.miyn-app-plugin-banner').css({'background-image':'url('+attachment.url+')'});
				$('#miyn-app-banner-image').val(attachid);
				if(attachid) {
					$.ajax({
	                    url: object_miyn_app.siteurl+'/wp-json/miyn-app-settings/v1/change-banner-image',
	                    method: 'GET',
	                    data: {
	                        'attached-id': attachid,
	                    }
	                }).done(function(response){
	                    console.log(response);
	                }).fail(function(response){
	                    console.log(response);
	                });
				}

			}).open();
		});
		
    });

}(jQuery));