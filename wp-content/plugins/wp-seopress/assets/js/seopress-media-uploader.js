jQuery(document).ready(function($){
	var mediaUploader;
	$('.button.seopress_social_facebook_img_cpt').click(function(e) {
			e.preventDefault();

			var url_field = $(this).parent().find('input[type=text]');
			// Extend the wp.media object
			mediaUploader = wp.media.frames.file_frame = wp.media({
				multiple: false });

			// When a file is selected, grab the URL and set it as the text field's value
			mediaUploader.on('select', function() {
				attachment = mediaUploader.state().get('selection').first().toJSON();
				$(url_field).val(attachment.url);
			});
			// Open the uploader dialog
			mediaUploader.open();
	});
	
	const array = [
		"#seopress_social_knowledge_img", 
		"#seopress_social_twitter_img", 
		"#seopress_social_fb_img"
	]

	array.forEach(function (item) {
		var mediaUploader;
		$(item + '_upload').click(function(e) {
			e.preventDefault();
			// If the uploader object has already been created, reopen the dialog
			if (mediaUploader) {
				mediaUploader.open();
				return;
			}
			// Extend the wp.media object
			mediaUploader = wp.media.frames.file_frame = wp.media({
				multiple: false });
	
			// When a file is selected, grab the URL and set it as the text field's value
			mediaUploader.on('select', function() {
				attachment = mediaUploader.state().get('selection').first().toJSON();
				$(item + '_meta').val(attachment.url);
				if(item == '#seopress_social_fb_img' && typeof sp_social_img !="undefined") { sp_social_img('fb'); }
				if(item == '#seopress_social_twitter_img' && typeof sp_social_img !="undefined") { sp_social_img('twitter'); }
			});
			
			// Open the uploader dialog
			mediaUploader.open();
		});
	});
});