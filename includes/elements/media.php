<?php
/**
* CCTM_media
*
* Implements an field that stores a reference to a media item (i.e. any attachment post)
*
*/
class CCTM_media extends CCTM_FormElement
{
	/** 
	* The $props array acts as a template which defines the properties for each instance of this type of field.
	* When added to a post_type, an instance of this data structure is stored in the array of custom_fields. 
	* Some properties are required of all fields (see below), some are automatically generated (see below), but
	* each type of custom field (i.e. each class that extends CCTM_FormElement) can have whatever properties it needs
	* in order to work, e.g. a dropdown field uses an 'options' property to define a list of possible values.
	* 
	* 
	*
	* The following properties MUST be implemented:
	*	'name' 	=> Unique name for an instance of this type of field; corresponds to wp_postmeta.meta_key for each post
	*	'label'	=> 
	*	'description'	=> a description of this type of field.
	*
	* The following properties are set automatically:
	*
	* 	'type' 			=> the name of this class, minus the CCTM_ prefix.
	* 	'sort_param' 	=> populated via the drag-and-drop behavior on "Manage Custom Fields" page.
	*/
	public $props = array(
		'label' => '',
		'button_label' => '',
		'name' => '',
		'description' => '',
		'class' => '',
		'extra'	=> '',
		'default_value' => '',
		'is_repeatable' => '',
		'search_parameters' => '',
		'output_filter' => 'to_image_src',
	);

	public $supported_output_filters = array('to_src');

	//------------------------------------------------------------------------------
	/**
	 * Thickbox support
	 */
	public function admin_init() {	
		wp_enqueue_script('media-upload');
		wp_enqueue_script('thickbox');
		wp_register_script('cctm_relation', CCTM_URL.'/js/relation.js', array('jquery','media-upload','thickbox'));
		wp_enqueue_script('cctm_relation');
	}

	//------------------------------------------------------------------------------
	/**
	* This function provides a name for this type of field. This should return plain
	* text (no HTML). The returned value should be localized using the __() function.
	* @return	string
	*/
	public function get_name() {
		return __('Media',CCTM_TXTDOMAIN);	
	}
	
	//------------------------------------------------------------------------------
	/**
	* This function gives a description of this type of field so users will know 
	* whether or not they want to add this type of field to their custom content
	* type. The returned value should be localized using the __() function.
	* @return	string text description
	*/
	public function get_description() {
		return __('Media fields are used to store references to any type of media file that has been uploaded via the WordPress media uploader, e.g. images, videos, mp3s.',CCTM_TXTDOMAIN);
	}
	
	//------------------------------------------------------------------------------
	/**
	* This function should return the URL where users can read more information about
	* the type of field that they want to add to their post_type. The string may
	* be localized using __() if necessary (e.g. for language-specific pages)
	* @return	string 	e.g. http://www.yoursite.com/some/page.html
	*/
	public function get_url() {
		return 'http://code.google.com/p/wordpress-custom-content-type-manager/wiki/Media';
	}
	

	//------------------------------------------------------------------------------
	/**
	 * @param mixed $current_value	current value for this field (an integer ID).
	 * @return string	
	 */
	public function get_edit_field_instance($current_value) {
	
		require_once(CCTM_PATH.'/includes/SummarizePosts.php');
		require_once(CCTM_PATH.'/includes/GetPostsQuery.php');
		
		$Q = new GetPostsQuery();
		
		// Populate the values (i.e. properties) of this field
		$this->id 					= $this->get_field_id();
		$this->class 				= $this->get_field_class($this->name, 'text', $this->class);
//		$this->name 				= $this->get_field_name(); // will be named my_field[] if 'is_repeatable' is checked.
		$this->instance_id			= $this->get_instance_id();
		$this->content = '';
		
		$this->post_id = $this->value;		

		$fieldtpl = '';
		$wrappertpl = '';
		// Multi field?
		if ($this->is_repeatable) {

			$fieldtpl = CCTM::load_tpl(
				array('fields/elements/'.$this->name.'.tpl'
					, 'fields/elements/_'.$this->type.'_multi.tpl'
					, 'fields/elements/_relation.tpl'
				)
			);
	
			$wrappertpl = CCTM::load_tpl(
				array('fields/wrappers/'.$this->name.'.tpl'
					, 'fields/wrappers/_'.$this->type.'_multi.tpl'
					, 'fields/wrappers/_relation.tpl'
				)
			);		

			if ($current_value) {
				$values = (array) json_decode($current_value);
				foreach($values as $v) {
					$this->value				= (int) $v;
					$extras = $Q->append_extra_data($this->value);
					
					foreach($extras as $k => $v) {
						$this->$k = $v;
					}
					$this->content .= CCTM::parse($fieldtpl, $this->get_props());
				}
			}		
		}
		// Regular old Single-selection
		else {
			$this->value				= (int) $current_value; // Relations only store the foreign key.
			$extras = $Q->append_extra_data($this->value);
			
			foreach($extras as $k => $v) {
				$this->$k = $v;
			}
			
			$fieldtpl = CCTM::load_tpl(
				array('fields/elements/'.$this->name.'.tpl'
					, 'fields/elements/_'.$this->type.'.tpl'
					, 'fields/elements/_relation.tpl'
				)
			);
	
			$wrappertpl = CCTM::load_tpl(
				array('fields/wrappers/'.$this->name.'.tpl'
					, 'fields/wrappers/_'.$this->type.'.tpl'
					, 'fields/wrappers/_relation.tpl'
				)
			);		

			if ($this->value) {
				$this->content = CCTM::parse($fieldtpl, $this->get_props());
			}		
		}
		

		return CCTM::parse($wrappertpl, $this->get_props());

	}


	//------------------------------------------------------------------------------
	/**
	 * This should return (not print) form elements that handle all the controls required to define this
	 * type of field.  The default properties correspond to this class's public variables,
	 * e.g. name, label, etc. The form elements you create should have names that correspond
	 * with the public $props variable. A populated array of $props will be stored alongside 
	 * the custom-field data for the containing post-type.
	 *
	 * @param mixed   $current_values should be an associative array.
	 * @return	string	HTML input fields
	 */
	public function get_edit_field_definition($def) {
		$is_checked = '';
		if (isset($def['is_repeatable']) && $def['is_repeatable'] == 1) {
			$is_checked = 'checked="checked"';
		}
		// Label
		$out = '<div class="'.self::wrapper_css_class .'" id="label_wrapper">
			 		<label for="label" class="'.self::label_css_class.'">'
			 			.__('Label', CCTM_TXTDOMAIN).'</label>
			 		<input type="text" name="label" class="'.self::css_class_prefix.'text" id="label" value="'.htmlspecialchars($def['label']) .'"/>
			 		' . $this->get_translation('label').'
			 	</div>';
		// Name
		$out .= '<div class="'.self::wrapper_css_class .'" id="name_wrapper">
				 <label for="name" class="cctm_label cctm_text_label" id="name_label">'
					. __('Name', CCTM_TXTDOMAIN) .
			 	'</label>
				 <input type="text" name="name" class="'.$this->get_field_class('name','text').'" id="name" value="'.htmlspecialchars($def['name']) .'"/>'
				 . $this->get_translation('name') .'
			 	</div>';
			
		// Initialize / defaults
		$preview_html = '';
		$click_label = __('Choose Media');
		$label = __('Default Value', CCTM_TXTDOMAIN);
		$remove_label = __('Remove');

			
		// Handle the display of the default value -- this should use the same formatting stuff as the get_edit_field_instance function.
		if ( !empty($def['default_value']) ) {
			$preview_html = '';
		}

		// Button Label
		$out .= '<div class="'.self::wrapper_css_class .'" id="button_label_wrapper">
			 		<label for="button_label" class="'.self::label_css_class.'">'
			 			.__('Button Label', CCTM_TXTDOMAIN).'</label>
			 		<input type="text" name="button_label" class="'.self::css_class_prefix.'text" id="button_label" value="'.htmlspecialchars($def['button_label']) .'"/>
			 		' . $this->get_translation('button_label').'
			 	</div>';

		// Set Search Parameters
		$out .= '
			<div class="cctm_element_wrapper" id="search_parameters_wrapper">
				<label for="name" class="cctm_label cctm_text_label" id="search_parameters_label">'
					. __('Search Parameters', CCTM_TXTDOMAIN) .
			 	'</label>
				<span class="cctm_description">'.__('Define which posts are available for selection by narrowing your search parameters.', CCTM_TXTDOMAIN).'</span>
				<br/>
				<span class="button" onclick="javascript:search_form_display(\''.$def['name'].'\');">'.__('Set Search Parameters', CCTM_TXTDOMAIN) .'</span>
				<div id="cctm_thickbox"></div>
				<input type="hidden" id="search_parameters" name="search_parameters" value="'.CCTM::get_value($def,'search_parameters').'" />
				<br/>
			</div>';

		// Default Value 			
/*
		$out .= '
			<div class="cctm_element_wrapper" id="default_value_wrapper">
				<label for="default_value" class="'.self::label_css_class.'">'
			 			.__('Default Value', CCTM_TXTDOMAIN).'</label>
				<span class="cctm_description">'.__('Choose a default value(s) to display on new posts using this field.', CCTM_TXTDOMAIN).'</span>
					<span class="button" onclick="javascript:thickbox_results(\'cctm_'.$def['name'].'\');">'.$label.'</span>
					<span class="button" onclick="javascript:remove_relation(\'default_value\',\'default_value_media\');">'.$remove_label.'</span>
				</span>
				<div id="target_cctm_'.$def['name'].'"></div> 
				<input type="hidden" id="default_value" name="default_value" value="'
				.htmlspecialchars($def['default_value']).'" /><br />
				<div id="cctm_instance_wrapper_'.$def['name'].'">'.$preview_html.'</div>
				
				<br />
			</div>';
*/

		// Is Repeatable?
		$out .= '<div class="'.self::wrapper_css_class .'" id="is_repeatable_wrapper">
				 <label for="is_repeatable" class="cctm_label cctm_checkbox_label" id="is_repeatable_label">'
					. __('Is Repeatable?', CCTM_TXTDOMAIN) .
			 	'</label>
				 <br />
				 <input type="checkbox" name="is_repeatable" class="'.$this->get_field_class('is_repeatable','checkbox').'" id="is_repeatable" value="1" '. $is_checked.'/> <span>'.$this->descriptions['is_repeatable'].'</span>
			 	</div>';
			
		// Description	 
		$out .= '<div class="'.self::wrapper_css_class .'" id="description_wrapper">
			 	<label for="description" class="'.self::label_css_class.'">'
			 		.__('Description', CCTM_TXTDOMAIN) .'</label>
			 	<textarea name="description" class="'.$this->get_field_class('description','textarea').'" id="description" rows="5" cols="60">'
			 		. htmlspecialchars($def['description']).'</textarea>
			 	' . $this->get_translation('description').'
			 	</div>';
			 	
		// Output Filter
		if ( !empty($this->supported_output_filters) ) { 
			$out .= $this->get_available_output_filters($def);
		}		 
			 return $out;
	}
}


/*EOF*/