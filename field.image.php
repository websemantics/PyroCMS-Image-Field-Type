<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Streams Image Field Type
 *
 * The streams image field type works by uploading
 * an image to a the folder of your choice, and then
 * setting a hidden input field with the image Id. An image is removed
 * by setting a null value for the hidden input.
 *
 * The image field type is integrated closely with the PyroCMS 
 * files system, so all files that are uploaded have an entry
 * in the files table and show up in the files module.
 *
 * @package		PyroCMS\Core\Modules\Streams Core\Field Types
 * @author		PyroCMS Dev Team
 * @copyright	Copyright (c) 2013, PyroCMS
 */
class Field_image
{
	public $field_type_slug			= 'image';

	// Files are saved as 15 character strings.
	public $db_col_type				= 'char';
	public $col_constraint 			= 15;

	public $custom_parameters		= array('folder', 'resize_width', 'resize_height', 'keep_ratio', 'allowed_types');

	public $version					= '2.0.0';

	public $author					= array('name' => 'PyroCMS', 'url' => 'http://pyrocms.com');

	public $input_is_file			= true;

	public function __construct()
	{
		get_instance()->load->library('image_lib');
	}

	public function event()
	{
		$this->CI->type->add_js('image', 'jquery.ui.widget.js');
		$this->CI->type->add_js('image', 'jquery.iframe-transport.js');
		$this->CI->type->add_js('image', 'jquery.fileupload.js');
		$this->CI->type->add_js('image', 'imagefield.js');
		
		$this->CI->type->add_css('image', 'imagefield.css');		
	}

	/**
	 * Output form input
	 *
	 * @param	array
	 * @param	array
	 * @return	string
	 */
	public function form_output($params, $entry_id, $field)
	{
		$this->CI->load->config('files/files');

		// Get the field ID encrypted
		$this->CI->load->library('Encrypt');
		$field_id = $this->CI->encrypt->encode($field->field_id);

		$out = '<div class="img_upload_wrap" id="'.$params['form_slug'].'_img_upload_wrap">';

		// Add the field-id as a hidden input so we can
		// access it via jQuery.
		$out .= '<input type="hidden" id="'.$params['form_slug'].'_field_id" value="'.$field_id.'" />';

		// Progress bar.
		$out .= '<div class="progress"><div class="bar" style="width: 0%;"></div></div>';

		// if there is content and it is not dummy or cleared
		if ($params['value'] and $params['value'] != 'dummy')
		{
			$out .= '<span class="image_remove" data-id="'.$params['form_slug'].'">X</span><a class="image_link" href="'.site_url('files/large/'.$params['value']).'" target="_break"><img src="'.site_url('files/thumb/'.$params['value']).'" class="img_ft_thumb" /></a><br />';
			return $out .= form_hidden($params['form_slug'], $params['value']).'</div>';
		}

		//$folder_id = $params['custom']['folder'];

		/*<!--<a href="#" data-id="'.$params['form_slug'].'" data-folder-id="'.$folder_id.'" class="choose-img-js button">Choose Image</a>-->*/

		$out .= '<p><input id="'.$params['form_slug'].'" type="file" class="upload-img-js" name="'.$params['form_slug'].'_image_input" data-field-id="'.$field_id.'" /></p>';

		//$out .= '<input type="hidden" name="'.$params['form_slug'].'_field" value="'.$field_id.'" />';

		$out .= '</div>';

		//$out .= '<p><a href="'..'" data-id="'.$params['form_slug'].'" data-folder-id="'.$folder_id.'" class="button modal">+ Upload</a></p>';

		//$out .= '<ul id="'.$params['form_slug'].'_file_choose"></ul>';

		return $out;
	}

	/**
	 * Pre Output
	 *
	 * @param	array
	 * @return	string
	 */
	public function pre_output($input, $params)
	{
		if ( ! $input or $input == 'dummy' ) {
			return null;
		}

		// Get image data
		$image = $this->CI->db
						->select('filename, alt_attribute, description, name')
						->where('id', $input)->get('files')->row();

		if ( ! $image) {
			return null;
		}

		// This defaults to 100px wide
		return '<img src="'.site_url('files/thumb/'.$input).'" alt="'.$this->obvious_alt($image).'" />';
	}

	/**
	 * Process before outputting for the plugin
	 *
	 * This creates an array of data to be merged with the
	 * tag array so relationship data can be called with
	 * a {{ field:column }} syntax
	 *
	 * @param	string
	 * @param	string
	 * @param	array
	 * @return	array
	 */
	public function pre_output_plugin($input, $params)
	{
		if ( ! $input or $input == 'dummy' ) {
			return null;
		}

		$this->CI->load->library('files/files');

		$file = Files::get_file($input);

		if ($file['status']) {

			$image = $file['data'];

			// If we don't have a path variable, we must have an
			// older style image, so let's create a local file path.
			if ( ! $image->path)
			{
				$image_data['image'] = base_url($this->CI->config->item('files:path').$image->filename);
			}
			else
			{
				$image_data['image'] = str_replace('{{ url:site }}', base_url(), $image->path);
			}

			// For <img> tags only
			$alt = $this->obvious_alt($image);

			$image_data['filename']			= $image->filename;
			$image_data['name']				= $image->name;
			$image_data['alt']				= $image->alt_attribute;
			$image_data['description']		= $image->description;
			$image_data['img']				= img(array('alt' => $alt, 'src' => $image_data['image']));
			$image_data['ext']				= $image->extension;
			$image_data['mimetype']			= $image->mimetype;
			$image_data['width']			= $image->width;
			$image_data['height']			= $image->height;
			$image_data['id']				= $image->id;
			$image_data['filesize']			= $image->filesize;
			$image_data['download_count']	= $image->download_count;
			$image_data['date_added']		= $image->date_added;
			$image_data['folder_id']		= $image->folder_id;
			$image_data['folder_name']		= $image->folder_name;
			$image_data['folder_slug']		= $image->folder_slug;
			$image_data['thumb']			= site_url('files/thumb/'.$input);
			$image_data['thumb_img']		= img(array('alt' => $alt, 'src'=> site_url('files/thumb/'.$input)));

			return $image_data;
		}
	}

	/**
	 * Choose a folder to upload to.
	 *
	 * @param	string [$value]
	 * @return	string
	 */
	public function param_folder($value = null)
	{
		// Get the folders
		$this->CI->load->model('files/file_folders_m');

		$tree = $this->CI->file_folders_m->get_folders();

		$tree = (array)$tree;

		if ( ! $tree)
		{
			return '<em>'.lang('streams:image.need_folder').'</em>';
		}

		$choices = array();

		foreach ($tree as $tree_item)
		{
			// We are doing this to be backwards compat
			// with PyroStreams 1.1 and below where
			// This is an array, not an object
			$tree_item = (object)$tree_item;

			$choices[$tree_item->id] = $tree_item->name;
		}

		return form_dropdown('folder', $choices, $value);
	}

	/**
	 * Param Resize Width
	 *
	 * @param	string  $value
	 * @return	string
	 */
	public function param_resize_width($value = null)
	{
		return form_input('resize_width', $value);
	}

	/**
	 * Param Resize Height
	 *
	 * @param	string  [$value]
	 * @return	string
	 */
	public function param_resize_height($value = null)
	{
		return form_input('resize_height', $value);
	}

	/**
	 * Param Allowed Types
	 *
	 * @param	string  [$value]
	 * @return	string
	 */
	public function param_keep_ratio($value = null)
	{
		$choices = array('yes' => lang('global:yes'), 'no' => lang('global:no'));

		return array(
				'input' 		=> form_dropdown('keep_ratio', $choices, $value),
				'instructions'	=> lang('streams:image.keep_ratio_instr'));
	}

	/**
	 * Param Allowed Types
	 *
	 * @param	string  [$value]
	 * @return	string
	 */
	public function param_allowed_types($value = null)
	{
		return array(
				'input'			=> form_input('allowed_types', $value),
				'instructions'	=> lang('streams:image.allowed_types_instr'));
	}

	/**
	 * Obvious alt attribute for <img> tags only
	 *
	 * @param	obj
	 * @return	string
	 */
	private function obvious_alt($image)
	{
		if ($image->alt_attribute) {
			return $image->alt_attribute;
		}
		if ($image->description) {
			return $image->description;
		}
		return $image->name;
	}

	/**
	 * Upload Function
	 *
	 * Accessed via AJAX.
	 *
	 * @return json
	 */
	public function ajax_upload()
	{
		// To be returned via JSON
		$return = array();

		// We need a file.
		if ( ! $_FILES) {
			$return['error'] = 'No file provided';
		}

		// Get/decode the field_id
		$field_id_raw = $this->CI->input->post('field_id');
		$field_name = $this->CI->input->post('field_name');

		if ( ! $field_id_raw or ! $field_name) {
			$return['error'] = 'No field ID provided.';
		}

		// Decode field ID
		$this->CI->load->library('Encrypt');
		$field_id = $this->CI->encrypt->decode($field_id_raw);

		if ( ! $field_id) {
			$return['error'] = 'No field ID provided.';
		}

		// Get the field.
		if ( ! $field = $this->CI->fields_m->get_field($field_id)) {
			$return['error'] = 'Invalid field ID.';
		}

		// Looks like we have a field - we're ready to
		// upload the file based on the field's settings.
		$this->CI->load->library('files/files');

		// Resize options
		$resize_width 	= (isset($field->field_data['resize_width'])) ? $field->field_data['resize_width'] : null;
		$resize_height 	= (isset($field->field_data['resize_height'])) ? $field->field_data['resize_height'] : null;
		$keep_ratio 	= (isset($field->field_data['keep_ratio']) and $field->field_data['keep_ratio'] == 'yes') ? true : false;

		// If you don't set allowed types, we'll set it to allow all.
		$allowed_types 	= (isset($field->field_data['allowed_types'])) ? $field->field_data['allowed_types'] : '*';

		$upload = Files::upload($field->field_data['folder'], null, $field_name, $resize_width, $resize_height, $keep_ratio, $allowed_types);

		if ( ! $upload['status']) {
			$return['error'] = $upload['message'];
		} else {
			// Return the ID of the file DB entry
			$return['uploadId'] = $upload['data']['id'];
		}
	
		// Return the JSON object.
		header('Content-type: application/json');
		echo json_encode($return);
	}

}