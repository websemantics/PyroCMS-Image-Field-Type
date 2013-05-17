$(function() {

	function bindFileUpload(obj)
	{
  	  	/**
	  	 * Image Upload Function
	  	 */
	    obj.fileupload({

	        dataType: 'json',
	        url: SITE_URL+'streams_core/public_ajax/field/image/upload',
	        progressall: function (e, data) {

	   			var id = $(this).attr('id');
	       		var progress = parseInt(data.loaded / data.total * 100, 10);
	        	$('#'+id+'_img_upload_wrap .progress .bar').css(
	            	'width',
	            	progress + '%'
	        	);
	    	},
	        add: function (e, data) {

	            // Add formdata - field id and the name.
	            data.formData = {
	            	field_id: $(this).attr('data-field-id'),
	        		field_name: $(this).attr('name')
	        	};

	            data.context = $('<p/>').text('Uploading...').replaceAll($(this));
	            data.submit();
	        },
	        send: function (e, data) {
	        	// Now that we're uploading, show the progress bar.
	        	var id = $(this).attr('id');
				$('#'+id+'_img_upload_wrap .progress').css('display', 'block');
	        },
	        done: function (e, data) {

	 			if (data.result.error) {
	 				data.context.text(data.result.error);
	 			} else {

	   				var id = $(this).attr('id');
	   				//alert(id);

	   				// Add a hidden input with the value
	   				// of the uploaded image.
	   				$('#'+id+'_img_upload_wrap .progress').append(
	   					'<input type="hidden" name="'+
	   					id+'" value="'+data.result.uploadId+'" />');

	   				// We don't need to see the progress bar anymore.
					$('#'+id+'_img_upload_wrap .progress').css('display', 'none');

	   				// Prepend the image.
	   				$('#'+id+'_img_upload_wrap').prepend(
	   					'<p><span class="image_remove" data-id="'+id+'">X</span><img src="'+SITE_URL+'files/thumb/'+data.result.uploadId+'" class="img_ft_thumb" /></p>');

	 				// Remove any messages we were seeing.
	 				data.context.text('');
	 			}
	     
	        }

	    });

	}

	bindFileUpload($('.upload-img-js'));

	// Re-bind for new row for grid
	$('.add_grid_row').click(function(e) {

		e.preventDefault();

		setTimeout(function(){
			bindFileUpload($('.upload-img-js'));
		}, 800);

	});

  	/**
  	 * Choose Image Function
  	 */
	$('.choose-img-js').click(function(e){

		e.preventDefault();

		var id = $(this).attr('data-id');
		var folder_id = $(this).attr('data-folder-id');
		var folders_center = $('#'+id+'_file_choose');

		post_data = {parent: folder_id};
		$.post(SITE_URL+'admin/files/folder_contents', post_data, function(data){
			
			var results = $.parseJSON(data);

			if (results.status) {

				// iterate so that we have folders first, files second
				/*$.each(results.data, function(type, data){
					$.each(data, function(index, item){
						item.el_type = type;
						items.push(item);
					});
				});*/

				for (var i = 0; i < results.data.file.length; i++)
				{
					var item = results.data.file[i];
					folders_center.append(
						'<li class="file" data-id="'+item.id+'" data-name="'+item.name+'">'+
							'<img src="'+SITE_URL+'files/cloud_thumb/'+item.id+'?'+new Date().getMilliseconds()+'" alt="'+item.name+'"/>'+
							'<span class="name-text">'+item.name+'</span>'+
						'</li>'
					);
				}
			}

			console.log(results);
		
		});

	});

	
	/**
	 * Remove Image Button
	 *
	 * Removes the image and sets a hidden
	 * input to a null value.
	 */
	$('.img_upload_wrap').on('click', '.image_remove', function(r)
	{
		r.preventDefault();
		var id = $(this).attr('data-id');
		var wrap = $('#'+id+'_img_upload_wrap');
		var fId = $('#'+id+'_field_id').val();

		// Change the input to empty
		$(this).siblings('input[type="hidden"]').attr('value', '');

		// Make the upload input visible again.
		$('#'+id).css('display', 'block');

		// remove the image preview
		$(this).siblings('.img_ft_thumb').remove();
		$(this).siblings('a').remove();

		// Add a file upload button.
		wrap.prepend('<p><input id="'+id+'" type="file" class="upload-img-js" name="'+id+'_image_input" data-field-id="'+fId+'" /></p>');

		// Bind fileupload again
		bindFileUpload($('#'+id));

		// remove this close button
		$(this).remove();
		
		return false;
	
	});

});