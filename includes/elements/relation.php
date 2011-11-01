<?php
/**
* CCTM_relation
*
* Implements a special AJAX form element used to store a wp_posts.ID representing
* another post of some kind
*
*/
class CCTM_relation extends CCTM_FormElement
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
		// 'type'	=> '', // auto-populated: the name of the class, minus the CCTM_ prefix.
	);

	public $supported_output_filters = array('to_link','to_link_href');
	
	//------------------------------------------------------------------------------
	/**
	* This function provides a name for this type of field. This should return plain
	* text (no HTML). The returned value should be localized using the __() function.
	* @return	string
	*/
	public function get_name() {
		return __('Relation',CCTM_TXTDOMAIN);	
	}
	
	//------------------------------------------------------------------------------
	/**
	* This function gives a description of this type of field so users will know 
	* whether or not they want to add this type of field to their custom content
	* type. The returned value should be localized using the __() function.
	* @return	string text description
	*/
	public function get_description() {
		return __('Relation fields are used to store a reference to another post, including media posts. For example you can use a relation to link to a parent post or to an image or attachment.',CCTM_TXTDOMAIN);
	}
	
	//------------------------------------------------------------------------------
	/**
	* This function should return the URL where users can read more information about
	* the type of field that they want to add to their post_type. The string may
	* be localized using __() if necessary (e.g. for language-specific pages)
	* @return	string 	e.g. http://www.yoursite.com/some/page.html
	*/
	public function get_url() {
		return 'http://code.google.com/p/wordpress-custom-content-type-manager/wiki/Relation';
	}
	

	//------------------------------------------------------------------------------
	/**
	 * @param mixed $current_value	current value for this field (an integer ID).
	 * @return string	
	 */
	public function get_edit_field_instance($current_value) {

		$fieldtpl = $this->get_field_tpl();
/*
		$fieldtpl = CCTM::load_tpl(
			array('fields/field_id.tpl'
				, 'fields/_type.tpl'
				, 'fields/_default.tpl'
			)
		);
*/
/*
		$wrappertpl = CCTM::load_tpl(
			array('fields/wrappers/field_id.tpl'
				, 'fields/wrappers/_type.tpl'
				, 'fields/wrappers/_default.tpl'
			)
		);
*/
		$wrappertpl = $this->get_wrapper_tpl();



		// Populate the values (i.e. properties) of this field
		$this->props['id'] 					= $this->get_field_id();
		$this->props['class'] 				= $this->get_field_class($this->name, 'text', $this->class);
		$this->props['value']				= (int) $current_value; // Relations only store the foreign key.
		$this->props['name'] 				= $this->get_field_name(); // will be named my_field[] if 'is_repeatable' is checked.
		$this->props['instance_id']			= $this->get_instance_id();
		// $this->is_repeatable = 1; // testing
				
		if ($this->is_repeatable) {
			$this->props['add_button'] = '<span class="button" onclick="javascript:thickbox_results(\''.$this->props['id'].'\');">Click</span>'; 
			$this->props['delete_button'] = '<span class="button" onclick="javascript:remove_html(\''.$this->get_instance_id().'\');">Delete</span>';
			$this->i = $this->i + 1; // increment the instance 
		}
		
		$this->props['help'] = $this->get_all_placeholders(); // <-- must be immediately prior to parse
		$this->props['content'] = CCTM::parse($fieldtpl, $this->props);
		$this->props['help'] = $this->get_all_placeholders(); // <-- must be immediately prior to parse
		return CCTM::parse($wrappertpl, $this->props);
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
		$click_label = __('Choose Relation');
		$label = __('Default Value', CCTM_TXTDOMAIN);
		$remove_label = __('Remove');
		$controller_url = CCTM_URL.'/post-selector.php?';
			
		// Handle the display of the Default Image thumbnail
		if ( !empty($def['default_value']) ) {
			$preview_html = wp_get_attachment_image( $def['default_value'], 'thumbnail', true );
			$attachment_obj = get_post($def['default_value']);
			//$def['preview_html'] .= '<span class="cctm_label">'.$attachment_obj->post_title.'</span><br />';
			// Wrap it
			$preview_html .= '<span class="cctm_label">'.$attachment_obj->post_title.' <span class="cctm_id_label">('.htmlspecialchars($def['default_value']).')</span></span><br />';
			
		}

		// Button Label
		$out .= '<div class="'.self::wrapper_css_class .'" id="button_label_wrapper">
			 		<label for="button_label" class="'.self::label_css_class.'">'
			 			.__('Button Label', CCTM_TXTDOMAIN).'</label>
			 		<input type="text" name="button_label" class="'.self::css_class_prefix.'text" id="button_label" value="'.htmlspecialchars($def['button_label']) .'"/>
			 		' . $this->get_translation('button_label').'
			 	</div>';

		// Default Value 			
		$out .= '
			<div class="cctm_element_wrapper" id="custom_field_wrapper_2">
				<span class="cctm_label cctm_media_label" id="cctm_label_default_value">'.$label.' <a href="'.$controller_url.'&fieldname=default_value" name="default_value" class="thickbox button">'.$click_label.'</a>
					<span class="button" onclick="javascript:remove_relation(\'default_value\',\'default_value_media\');">'.$remove_label.'</span>
				</span> 
				<input type="hidden" id="default_value" name="default_value" value="'
				.htmlspecialchars($def['default_value']).'" /><br />
				<div id="default_value_media">'.$preview_html.'</div>
				
				<br />
			</div>';

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