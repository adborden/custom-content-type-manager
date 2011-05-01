<?php
/**
* CCTM_multiselect
*
* Implements an HTML multi-select element with options (multiple select).
*
*/
class CCTM_multiselect extends FormElement
{
	/** 
	* The $props array acts as a template which defines the properties for each instance of this type of field.
	* When added to a post_type, an instance of this data structure is stored in the array of custom_fields. 
	* Some properties are required of all fields (see below), some are automatically generated (see below), but
	* each type of custom field (i.e. each class that extends FormElement) can have whatever properties it needs
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
		'name' => '',
		'description' => '',
		'class' => '',
		'extra'	=> '',
		'default_value' => '',
		'options'	=> array(),
		'output_filter' => 'to_array',
		// 'type'	=> '', // auto-populated: the name of the class, minus the CCTM_ prefix.
		// 'sort_param' => '', // handled automatically
	);

	public $supported_output_filters = array('to_array','formatted_list');
	
	//------------------------------------------------------------------------------
	/**
	* This function provides a name for this type of field. This should return plain
	* text (no HTML). The returned value should be localized using the __() function.
	* @return	string
	*/
	public function get_name() {
		return __('Multi-select',CCTM_TXTDOMAIN);	
	}
	
	//------------------------------------------------------------------------------
	/**
	* Used to drive a thickbox pop-up when a user clicks "See Example"
	*/
	public function get_example_image() {
		return '';
	}
	
	//------------------------------------------------------------------------------
	/**
	* This function gives a description of this type of field so users will know 
	* whether or not they want to add this type of field to their custom content
	* type. The returned value should be localized using the __() function.
	* @return	string text description
	*/
	public function get_description() {
		return __('Multi-select fields implement a <select> element which lets you select mutliple items.
			"Extra" parameters, e.g. "size" can be specified in the definition.',CCTM_TXTDOMAIN);
	}

	//------------------------------------------------------------------------------
	/**
	* This function should return the URL where users can read more information about
	* the type of field that they want to add to their post_type. The string may
	* be localized using __() if necessary (e.g. for language-specific pages)
	* @return	string 	e.g. http://www.yoursite.com/some/page.html
	*/
	public function get_url() {
		return 'http://code.google.com/p/wordpress-custom-content-type-manager/wiki/MultiSelect';
	}


	//------------------------------------------------------------------------------
	/**
	 * Very similar to the dropdown, but this accepts arrays.
	 *
	 * @param string json-encrypted $current_value of the field for the current post
	 * @return string
	 */
	public function get_edit_field_instance($current_value) {

		$current_values_arr = json_decode(html_entity_decode($current_value), true );

		// Some error messaging: the options thing is enforced at time of def creation too
		if ( !isset($this->options) || !is_array($this->options) ) {
			return sprintf('<p><strong>%$1s</strong> %$2s %$3s</p>'
				, __('Custom Content Error', CCTM_TXTDOMAIN)
				, __('No options supplied for the following custom field: ', CCTM_TXTDOMAIN)
				, $data['name']
			);
		}

		// ListBox: problem with CSS styling in WP manager (shows only 1 line)
		// Note the [] after the name: this is important to let this input accept multiple values
/*
		$output = $this->wrap_label();
		$output .= '<select multiple="multiple" name="'.$this->get_field_name().'[]" class="'
				.$this->get_field_class($this->name, 'multiselect') . ' ' . $this->class.'" id="'.$this->get_field_id().'" '.$this->extra.'>
				<!-- option value="">'.__('Pick One').'</option -->
				';
			foreach ($this->options as $opt) {
				$opt = htmlspecialchars($opt); // Filter the values
				$is_selected = '';
				if ( $current_value == $opt ) {
					$is_selected = 'selected="selected"';
				}
				$output .= '<option value="'.$opt.'" '.$is_selected.'>'.$opt.'</option>';
			}
		$output .= '</select>';
*/
		// Multiple Checkboxes
		$output = $this->wrap_label('cctm_multiselect_checkbox') . '<br/>';
		
		$i = 0; // used for labels
		$name = $this->get_field_name();
		$id = $this->get_field_id();
		$class = $this->get_field_class($this->name, 'muticheckbox');
		
		foreach ($this->options as $opt) {
			$is_checked = '';
			if ( is_array($current_values_arr) && in_array( $opt, $current_values_arr) ) {
				$is_checked = 'checked="checked"';
			}
			//  <input type="checkbox" name="vehicle" value="Car" checked="checked" />
			$output .= '<div class="cctm_muticheckbox_wrapper"><input type="checkbox" name="'.$name.'[]" class="'.$class.'" id="'.$id.$i.'" value="'.$opt.'" '.$is_checked.'> <label class="cctm_muticheckbox" for="'.$id.$i.'">'.htmlspecialchars($opt).'</label></div><br/>';
			$i = $i + 1;
		}
		return $this->wrap_outer($output);
	}

	//------------------------------------------------------------------------------
	/**
	 * Note that the HTML in $option_html should match the JavaScript version of 
	 * the same HTML in js/manager.js (see the append_dropdown_option() function).
	 * I couldn't think of a clean way to do this, but the fundamental problem is 
	 * that both PHP and JS need to draw the same HTML into this form:
	 * PHP draws it when an existing definition is *edited*, whereas JS draws it
	 * when you dynamically *create* new dropdown options.
	 *

			<style>
			input.cctm_error { 
				background: #fed; border: 1px solid red;
			}
			</style>
	 * @param mixed $def	nested array of existing definition.
	 */
	public function get_edit_field_definition($def) {
	
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
			 	
		// Default Value
		$out .= '<div class="'.self::wrapper_css_class .'" id="default_value_wrapper">
			 	<label for="default_value" class="cctm_label cctm_text_label" id="default_value_label">'
			 		.__('Default Value', CCTM_TXTDOMAIN) .'</label>
			 		<input type="text" name="default_value" class="'.$this->get_field_class('default_value','text').'" id="default_value" value="'. htmlspecialchars($def['default_value'])
			 		.'"/>
			 	' . $this->get_translation('default_value') .'
			 	</div>';

		// Extra
		$out .= '<div class="'.self::wrapper_css_class .'" id="extra_wrapper">
			 		<label for="extra" class="'.self::label_css_class.'">'
			 		.__('Extra', CCTM_TXTDOMAIN) .'</label>
			 		<input type="text" name="extra" class="'.$this->get_field_class('extra','text').'" id="extra" value="'
			 			.htmlspecialchars($def['extra']).'"/>
			 	' . $this->get_translation('extra').'
			 	</div>';

		// Class
		$out .= '<div class="'.self::wrapper_css_class .'" id="class_wrapper">
			 	<label for="class" class="'.self::label_css_class.'">'
			 		.__('Class', CCTM_TXTDOMAIN) .'</label>
			 		<input type="text" name="class" class="'.$this->get_field_class('class','text').'" id="class" value="'
			 			.htmlspecialchars($def['class']).'"/>
			 	' . $this->get_translation('class').'
			 	</div>';
			
		// OPTIONS

		$option_cnt = 0;
		if (isset($def['options'])) {
			$option_cnt = count($def['options']);
		}

		// using the parse function because this got too crazy with escaping single quotes
		$hash = array();
		$hash['option_cnt'] 	= $option_cnt;
		$hash['delete'] 		= __('Delete');
		$hash['options'] 		= __('Options', CCTM_TXTDOMAIN);
		$hash['add_option'] 	= __('Add Option',CCTM_TXTDOMAIN);
		$hash['set_as_default'] = __('Set as Default', CCTM_TXTDOMAIN);		
		
		$tpl = '
			<div class="cctm_element_wrapper" id="dropdown_options">
				<label for="options" class="cctm_label cctm_select_label" id="cctm_label_options">[+options+] <span class="button" onclick="javascript:append_dropdown_option(\'dropdown_options\',\'[+delete+]\',\'[+set_as_default+]\',\'[+option_cnt+]\');">[+add_option+]</span>
				</label>
				<br />';
		$out .= CCTM::parse($tpl, $hash);
		
		// this html should match up with the js html in manager.js
		$option_html = '
			<div id="%s">
				<input type="text" name="options[]" id="option_%s" value="%s"/> 
				<span class="button" onclick="javascript:remove_html(\'%s\');">%s</span>
				<span class="button" onclick="javascript:set_as_default(\'option_%s\');">%s</span>
			</div>';


		$i = 0; // used to uniquely ID options.
		if ( !empty($def['options']) && is_array($def['options']) ) {
		
			foreach ($def['options'] as $opt_val) {
				$option_css_id = 'cctm_dropdown_option'.$i;
				$out .= sprintf($option_html
					, $option_css_id
					, $i
					, htmlspecialchars($opt_val)
					, $option_css_id, __('Delete') 
					, $i, __('Set as Default') 
				);
				$i = $i + 1;
			}
		}
			
		$out .= '</div>';
		
		// Description	 
		$out .= '<div class="'.self::wrapper_css_class .'" id="description_wrapper">
			 	<label for="description" class="'.self::label_css_class.'">'
			 		.__('Description', CCTM_TXTDOMAIN) .'</label>
			 	<textarea name="description" class="'.$this->get_field_class('description','textarea').'" id="description" rows="5" cols="60">'
			 		.htmlentities($def['description'])
			 		.'</textarea>
			 	' . $this->get_translation('description').'
			 	</div>';

		// Output Filter
		if ( !empty($this->supported_output_filters) ) { 
			$out .= $this->get_available_output_filters($def);
		}	 
		 
		 return $out;
	}

	//------------------------------------------------------------------------------
	/**
	 * Validate and sanitize any submitted data. Used when editing the definition for 
	 * this type of element. Default behavior here is require only a unique name and 
	 * label. Override this if customized validation is required.
	 *
	 * @param	array	$posted_data = $_POST data
	 * @param	string	$post_type the string defining this post_type
	 * @return	array	filtered field_data that can be saved OR can be safely repopulated
	 *					into the field values.
	 */
	public function save_definition_filter($posted_data, $post_type) {
		$posted_data = parent::save_definition_filter($posted_data, $post_type);		
		if ( empty($posted_data['options']) ) {
			$this->errors['options'][] = __('At least one option is required.', CCTM_TXTDOMAIN);
		}
		return $posted_data; // filtered data
	}

	//------------------------------------------------------------------------------
	/**
	 * This function allows for custom handling of submitted post/page data just before
	 * it is saved to the database. Data validation and filtering should happen here,
	 * although it's difficult to enforce any validation errors.
	 *
	 * Note that the field name in the $_POST array is prefixed by FormElement::post_name_prefix,
	 * e.g. the value for you 'my_field' custom field is stored in $_POST['cctm_my_field']
	 * (where FormElement::post_name_prefix = 'cctm_').
	 *
	 * Output should be whatever string value you want to store in the wp_postmeta table
	 * for the post in question. This function will be called after the post/page has
	 * been submitted: this can be loosely thought of as the "on save" event
	 *
	 * @param mixed   	$posted_data  $_POST data
	 * @param string	$field_name: the unique name for this instance of the field
	 * @return	string	whatever value you want to store in the wp_postmeta table where meta_key = $field_name	
	 */
	public function save_post_filter($posted_data, $field_name) {
		return json_encode($posted_data[ FormElement::post_name_prefix . $field_name ]);
	}

}


/*EOF*/