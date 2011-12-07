<?php
/**
* CCTM_wysiwyg
*
* Implements an WYSIWYG textarea input (a textarea with formatting controls).
*
*/
class CCTM_wysiwyg extends CCTM_FormElement
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
		'name' => '',
		'description' => '',
		'class' => '',
		'extra'	=> 'cols="80" rows="10"',
		'default_value' => '',
		// 'type'	=> '', // auto-populated: the name of the class, minus the CCTM_ prefix.
		// 'sort_param' => '', // handled automatically
	);
	
	/**
	 * See http://dannyvankooten.com/450/tinymce-wysiwyg-editor-in-wordpress-plugin/
	 */ 
	public function load_tiny_mce() {
		wp_tiny_mce( false );
	}
	
	public function preload_dialogs() {
		wp_quicktags();
		//wp_preload_dialogs( array( 'plugins' => 'wpdialogs,wplink,wpfullscreen' ) );
	}
	//------------------------------------------------------------------------------
	/**
	 * Register the appropriate js: array('jquery', 'editor', 'thickbox', 'media-upload')
	 http://codex.wordpress.org/Function_Reference/wp_register_script
	 */
	public function admin_init() {
		wp_register_script('cctm_wysiwyg', CCTM_URL.'/js/wysiwyg.js', array('jquery', 'editor', 'thickbox', 'media-upload'));
		wp_enqueue_script('cctm_wysiwyg');
		wp_enqueue_style('thickbox');
		
		add_action('admin_head','wp_tiny_mce');
		//add_action('admin_head',array($this,'load_tiny_mce'));
		//add_action( 'admin_print_footer_scripts', 'wp_tiny_mce', 25 );
		// wp_preload_dialogs( array( 'plugins' => 'wpdialogs,wplink,wpfullscreen' ) );
		add_action( 'admin_print_footer_scripts', array($this,'preload_dialogs'));
	}

	//------------------------------------------------------------------------------
	/**
	* This function provides a name for this type of field. This should return plain
	* text (no HTML). The returned value should be localized using the __() function.
	* @return	string
	*/
	public function get_name() {
		return __('WYSIWYG',CCTM_TXTDOMAIN);	
	}
	
	//------------------------------------------------------------------------------
	/**
	* This function gives a description of this type of field so users will know 
	* whether or not they want to add this type of field to their custom content
	* type. The returned value should be localized using the __() function.
	* @return	string text description
	*/
	public function get_description() {
		return __('What-you-see-is-what-you-get (WYSIWYG) fields implement a <textarea> element with formatting controls. 
			"Extra" parameters, e.g. "cols" can be specified in the definition, however a minimum size is required to make room for the formatting controls.',CCTM_TXTDOMAIN);
	}
	
	//------------------------------------------------------------------------------
	/**
	* This function should return the URL where users can read more information about
	* the type of field that they want to add to their post_type. The string may
	* be localized using __() if necessary (e.g. for language-specific pages)
	* @return	string 	e.g. http://www.yoursite.com/some/page.html
	*/
	public function get_url() {
		return 'http://code.google.com/p/wordpress-custom-content-type-manager/wiki/WYSIWYG';
	}

	//------------------------------------------------------------------------------
	/**
	 * See Issue http://code.google.com/p/wordpress-custom-content-type-manager/issues/detail?id=138
	 * and this one: http://keighl.com/2010/04/switching-visualhtml-modes-with-tinymce/
	 *
	 * @param string $current_value	current value for this field.
	 * @return string	
	 */
	public function get_edit_field_instance($current_value) {

		$this->id 					= $this->name; 

		$fieldtpl = '';
		$wrappertpl = '';
		
		// Multi-version of the field
		if ($this->is_repeatable) {
			$fieldtpl = CCTM::load_tpl(
				array('fields/elements/'.$this->name.'.tpl'
					, 'fields/elements/_'.$this->type.'_multi.tpl'
				)
			);
			
			$wrappertpl = CCTM::load_tpl(
				array('fields/wrappers/'.$this->name.'.tpl'
					, 'fields/wrappers/_'.$this->type.'_multi.tpl'
					, 'fields/wrappers/_text_multi.tpl'
				)
			);
			
			$this->i = 0;
			// print 'here...'; print_r(json_decode($current_value)); exit;
			$values = (array) json_decode($current_value,true);
			//die(print_r($values,true));
			$this->content = '';
			foreach($values as $v) {
				$this->value	= htmlspecialchars( html_entity_decode($v) );
				$this->content .= CCTM::parse($fieldtpl, $this->get_props());
				$this->i 		= $this->i + 1;
			}
		
		}
		// Singular
		else {
			$fieldtpl = CCTM::load_tpl(
				array('fields/elements/'.$this->name.'.tpl'
					, 'fields/elements/_'.$this->type.'.tpl'
					, 'fields/elements/_default.tpl'
				)
			);
			
			$wrappertpl = CCTM::load_tpl(
				array('fields/wrappers/'.$this->name.'.tpl'
					, 'fields/wrappers/_'.$this->type.'.tpl'
					, 'fields/wrappers/_default.tpl'
				)
			);
			
			$this->value				= $current_value;			
			$this->content = CCTM::parse($fieldtpl, $this->get_props() );
		}		


		$this->add_label = __('Add', CCTM_TXTDOMAIN);		

		return CCTM::parse($wrappertpl, $this->get_props());
	}

	//------------------------------------------------------------------------------
	/**
	 *
	 * @param mixed $def	field definition; see the $props array
	 */
	public function get_edit_field_definition($def) {

		$is_repeatable_checked = '';
		if (isset($def['is_repeatable']) && $def['is_repeatable'] == 1) {
			$is_repeatable_checked = 'checked="checked"';
		}
				
		// Label
		$out = '<div class="'.self::wrapper_css_class .'" id="label_wrapper">
			 		<label for="label" class="'.self::label_css_class.'">'
			 			.__('Label', CCTM_TXTDOMAIN).'</label>
			 		<input type="text" name="label" class="cctm_text" id="label" value="'.htmlspecialchars($def['label']) .'"/>
			 		' . $this->get_translation('label').'
			 	</div>';
		// Name
		$out .= '<div class="'.self::wrapper_css_class .'" id="name_wrapper">
				 <label for="name" class="cctm_label cctm_text_label" id="name_label">'
					. __('Name', CCTM_TXTDOMAIN) .
			 	'</label>
				 <input type="text" name="name" class="cctm_text" id="name" value="'.htmlspecialchars($def['name']) .'"/>'
				 . $this->get_translation('name') .'
			 	</div>';
			 	
		// Default Value
		$out .= '<div class="'.self::wrapper_css_class .'" id="default_value_wrapper">
			 	<label for="default_value" class="cctm_label cctm_text_label" id="default_value_label">'
			 		.__('Default Value', CCTM_TXTDOMAIN) .'</label>
			 		<input type="text" name="default_value" class="cctm_text" id="default_value" value="'. htmlspecialchars($def['default_value'])
			 		.'"/>
			 	' . $this->get_translation('default_value') .'
			 	</div>';

		// Extra
		$out .= '<div class="'.self::wrapper_css_class .'" id="extra_wrapper">
			 		<label for="extra" class="'.self::label_css_class.'">'
			 		.__('Extra', CCTM_TXTDOMAIN) .'</label>
			 		<input type="text" name="extra" class="cctm_text" id="extra" value="'
			 			.htmlspecialchars($def['extra']).'"/>
			 	' . $this->get_translation('extra').'
			 	</div>';

		// Class
		$out .= '<div class="'.self::wrapper_css_class .'" id="class_wrapper">
			 	<label for="class" class="'.self::label_css_class.'">'
			 		.__('Class', CCTM_TXTDOMAIN) .'</label>
			 		<input type="text" name="class" class="cctm_text" id="class" value="'
			 			.htmlspecialchars($def['class']).'"/>
			 	' . $this->get_translation('class').'
			 	</div>';

		// Is Repeatable?
		$out .= '<div class="'.self::wrapper_css_class .'" id="is_repeatable_wrapper">
				 <label for="is_repeatable" class="cctm_label cctm_checkbox_label" id="is_repeatable_label">'
					. __('Is Repeatable?', CCTM_TXTDOMAIN) .
			 	'</label>
				 <br />
				 <input type="checkbox" name="is_repeatable" class="cctm_checkbox" id="is_repeatable" value="1" '. $is_repeatable_checked.'/> <span>'.$this->descriptions['is_repeatable'].'</span>
			 	</div>';

		// Description	 
		$out .= '<div class="'.self::wrapper_css_class .'" id="description_wrapper">
			 	<label for="description" class="'.self::label_css_class.'">'
			 		.__('Description', CCTM_TXTDOMAIN) .'</label>
			 	<textarea name="description" class="cctm_textarea" id="description" rows="5" cols="60">'.htmlspecialchars($def['description']).'</textarea>
			 	' . $this->get_translation('description').'
			 	</div>';
		return $out;
	}

	//------------------------------------------------------------------------------
	/**
	 * This function allows for custom handling of submitted post/page data just before
	 * it is saved to the database. Data validation and filtering should happen here,
	 * although it's difficult to enforce any validation errors.
	 *
	 * Note that the field name in the $_POST array is prefixed by CCTM_FormElement::post_name_prefix,
	 * e.g. the value for you 'my_field' custom field is stored in $_POST['cctm_my_field']
	 * (where CCTM_FormElement::post_name_prefix = 'cctm_').
	 *
	 * Output should be whatever string value you want to store in the wp_postmeta table
	 * for the post in question. This function will be called after the post/page has
	 * been submitted: this can be loosely thought of as the "on save" event
	 *
	 * @param mixed   	$posted_data  $_POST data
	 * @param string	$field_name: the unique name for this instance of the field
	 * @return	string	whatever value you want to store in the wp_postmeta table where meta_key = $field_name	
	 */
/*
	public function save_post_filter($posted_data, $field_name) {
		if ( isset($posted_data[ CCTM_FormElement::post_name_prefix . $field_name ]) ) {

		
			if (is_array($posted_data[ CCTM_FormElement::post_name_prefix . $field_name ])) {
				foreach($posted_data[ CCTM_FormElement::post_name_prefix . $field_name ] as &$f) {
					$f = wpautop($f);
				}
				return json_encode($posted_data[ CCTM_FormElement::post_name_prefix . $field_name ]);
			}
			else{
				return wpautop($posted_data[ CCTM_FormElement::post_name_prefix . $field_name ]);
			}
		}
		else {
			return '';
		}
*/

/*
		if ($this->is_repeatable) {
			die('holy smokes');
		}
		else {
			$value = $posted_data[ CCTM_FormElement::post_name_prefix . $field_name ];
			return wpautop( $value ); // Auto-paragraphs for any WYSIWYG		
		}
*/
	//}
}


/*EOF*/