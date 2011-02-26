<?php
/*------------------------------------------------------------------------------
This generates form elements (e.g. text inputs, dropdowns) by passing the 
generate() function a definition data object.  This plugin does not 
generate the entire form! The developer is expected to supply his own <form>
tags, nonces, and submit buttons as required.

USAGE:

$my_def = array(
		array(
			'name'			=> 'my_custom_field', 
			'label'			=> 'My Custom Field',
			'value'			=> 'default value',
			'extra'			=> ' size="10"', // used inside the HTML input element
			'description'	=> 'A bit more information about the field',	
			'type'			=> 'text',
			'sort_param'	=> 1,			// smaller #s appear at the top
		)
);

$html = FormGenerator::generate($my_def);

// ... then, later, in your template, something like:

<form action="#" method="post">
	<?php print $html; ?>
	<?php wp_nonce_field('my_demo_action', 'my_demo_nonce'); ?>
	<input type="submit" name="Submit" value="Submit" />
</form>

------------------------------------------------------------------------------*/
class FormGenerator
{
	public static $sorting_column = 'sort_param';
	public static $element_wrapper_class = 'formgenerator_element_wrapper';
	
	// Stores keys => values each time the generate() function is run.
	public static $placeholders = array();
		
	// A div wraps each element, their ids have an integer appended to the prefix, e.g. 
	// <div id="custom_field_id_5"> 
	const element_wrapper_id_prefix = 'custom_field_wrapper_';
		
	// You can use these to put in headings or styling
	public static $before_elements = '';
	public static $after_elements = '';
	// used for global placeholders, e.g. if you always want [+post_type+] to parse, set this
	// to self::$global_placeholders['post_type'] = 'Something';
	//public static $global_placeholders = array('cctm_path' => CCTM_PATH, 'cctm_url' => CCTM_URL );
	public static $global_placeholders = array();
	// Name of a javascript function on the Manage Custom Fields page that is used
	// to iterate. That function must provide a unique integer as elements are added
	// and deleted dynamically via Ajax.
	const javascript_iterator_function = 'get_element_id';
	
	// simple iterator used whenever we need a serial #, e.g. form element or div count
	public static $i = 0;  
	
	//! Private Functions
	/*------------------------------------------------------------------------------
	The following '_get_xxx_element' functions each generate a single form element
	(with the exception of the dropbox function, which under special circumstances
	will generate additional inputs for each value passed to its 'special' array.
	
	INPUT: $data (array) reference. Contains an associative array describing how the element
	should look with keys for name, title, description, and type (see top of this 
	file for an example).
	Passed by reference so that any custom manipulations to the definition can be easily
	tracked in self::$placeholders.
	OUTPUT: HTML text.
	------------------------------------------------------------------------------*/

	/*------------------------------------------------------------------------------
	The checked value is hard-coded to '1' for compatibility with boolean functions
	------------------------------------------------------------------------------*/
	private static function _get_checkbox_element(&$data)
	{ 
//		print_r($data); 
		if (!isset($data['checked_value']) || empty($data['checked_value']))
		{
			$data['checked_value'] = 1;
		}
		$tpl ='
			<input type="checkbox" name="[+name+]" class="formgenerator_checkbox" id="[+id+]" value="[+checked_value+]" [+is_checked+] [+extra+]/> 
			<label for="[+id+]" class="formgenerator_label formgenerator_checkbox_label" id="formgenerator_label_[+name+]">[+label+]</label>';
		// Special handling to see if the box is checked.
		if ( $data['value'] == $data['checked_value'] )
		{
			$data['is_checked'] = 'checked="checked"';
		}
		else
		{
			$data['is_checked'] = '';
		}
	
		return self::parse($tpl, $data);
	}

	
	/*------------------------------------------------------------------------------
	The dropdown is special: it requires that you supply an array of options in its
	'options' key to specify each possible value in the dropdown.
	Also, extra inputs will be generated if the dropdown function is passed arguments
	in its 'special' array.  This situation only arises when we need to provide a
	form for EDITING dropdown arguments instead of just DISPLAYING them.
	NOTE: for each option in 'options', the value stored equals the value displayed.
	------------------------------------------------------------------------------*/
	private static function _get_dropdown_element(&$data)
	{
		//print_r($data); 
		// Some error messaging.
		if ( !isset($data['options']) || !is_array($data['options']) )
		{
			return '<p><strong>Custom Content Error:</strong> No options supplied for '.$data['name'].'</p>';
		}
		
		$tpl = '
			<label for="[+name+]" class="formgenerator_label" id="formgenerator_label_[+name+]">[+label+]</label>
				<select name="[+name+]" class="formgenerator_dropdown formgenerator_dropdown_label" id="[+name+]"[+extra+]>
					[+options+]  
				</select>
				[+special+]';
		
		$option_str = '';
		foreach ( $data['options'] as $option )
		{
			if ( empty($option) )
			{
				$option_str .= '<option value="">Pick One</option>';
			}
			else
			{
				$option = htmlspecialchars($option); // Filter the values
				$is_selected = '';
				if ( isset($data['value']) && $data['value'] == $option )
				{
					$is_selected = 'selected="selected"';
				}
				$option_str .= '<option value="'.$option.'" '.$is_selected.'>'.$option.'</option>';
			}
		}
		
		if ( isset($data['special']) && is_array($data['special']) )
		{
			
			$data['special'] = self::_get_special($data);
		}
		
		$data['options'] = $option_str; // overwrite the array with the string.
		
		return self::parse($tpl, $data);
	}

	/*------------------------------------------------------------------------------
	Used to create a media upload field.
	For "featured images", WP stores the post ID of the attachment post_type as a 
	normal foreign-key relationship. It stores the post_type.ID in a custom field
	(wp_postmeta) named _thumbnail_id.
	
	Do not use this function to generate forms in the Custom Content Types Manager 
	content-class admin area!	It should only generate forms for creating/editing 
	a post.
	------------------------------------------------------------------------------*/
	private static function _get_image_element(&$data)
	{	
		global $post;
		
		$media_html = '';

		// It got a value
		if ( !empty($data['value']) )
		{
			$data['preview_html'] = wp_get_attachment_image( $data['value'], 'thumbnail', true );
			$attachment_obj = get_post($data['value']);
			//$data['preview_html'] .= '<span class="formgenerator_label">'.$attachment_obj->post_title.'</span><br />';
			$data['preview_html'] .= '<span class="formgenerator_label">'.$attachment_obj->post_title.' <span class="formgenerator_id_label">('.$data['value'].')</span></span><br />';
			
		}
		// It's not set yet
		else
		{
			$data['preview_html'] = '';
		}
		
		$data['controller_url'] = CCTM_URL.'/post-selector.php?post_type=attachment&b=1&post_mime_type=';
		$data['click_label'] = __('Choose Image');
		$tpl = '
			<span class="formgenerator_label formgenerator_media_label" id="formgenerator_label_[+name+]">[+label+]</span>
			<input type="hidden" id="[+id+]" name="[+name+]" value="[+value+]" /><br />
			<div id="[+id+]_media">[+preview_html+]</div>
			<br class="clear" />
			<a href="[+controller_url+]&fieldname=[+id+]" name="[+click_label+]" class="thickbox button">[+click_label+]</a>
			<br class="clear" /><br />';
		return self::parse($tpl, $data);
	}
	
	/*------------------------------------------------------------------------------
	Used to create a media upload field.
	For "featured images", WP stores the post ID of the attachment post_type as a 
	normal foreign-key relationship. It stores the post_type.ID in a custom field
	(wp_postmeta) named _thumbnail_id.
	
	Do not use this function to generate forms in the Custom Content Types Manager 
	content-class admin area!	It should only generate forms for creating/editing 
	a post.
	------------------------------------------------------------------------------*/
	private static function _get_media_element(&$data)
	{	
		global $post;
		
		$media_html = '';

		// It got a value
		if ( !empty($data['value']) )
		{
			$data['preview_html'] = wp_get_attachment_image( $data['value'], 'thumbnail', true );
			$attachment_obj = get_post($data['value']);
//			$data['preview_html'] .= '<span class="formgenerator_label">'.$attachment_obj->post_title.'</span><br />';
			$data['preview_html'] .= '<span class="formgenerator_label">'.$attachment_obj->post_title.' <span class="formgenerator_id_label">('.$data['value'].')</span></span><br />';

			
		}
		// It's not set yet
		else
		{
			$data['preview_html'] = '';
		}
		
		$data['controller_url'] = CCTM_URL.'/post-selector.php?post_type=attachment';
		$data['click_label'] = __('Choose Media');
		$tpl = '
			<span class="formgenerator_label formgenerator_media_label" id="formgenerator_label_[+name+]">[+label+]</span>
			<input type="hidden" id="[+id+]" name="[+name+]" value="[+value+]" /><br />
			<div id="[+id+]_media">[+preview_html+]</div>
			<br class="clear" />
			<a href="[+controller_url+]&fieldname=[+id+]" name="[+click_label+]" class="thickbox button">[+click_label+]</a>
			<br class="clear" /><br />';
		return self::parse($tpl, $data);
	}

	//------------------------------------------------------------------------------
	private static function _get_readonly_element(&$data)
	{
		$tpl = '
		<p><strong>[+name+]:</strong> [+value+]</p>
		<input type="hidden" name="[+name+]" class="formgenerator_readonly" id="[+name+]" value="[+value+]"[+extra+]/>';
		return self::parse($tpl, $data);
	}

	/*------------------------------------------------------------------------------
	Closely related to the media elements: ties into the post-selector Ajax controller.
	A "relation" stores a post ID (i.e. a relation to wp_posts.ID).  In that sense,
	it is exactly the same as the media element, except instead of querying wp_posts
	where post_type='attachment', this lets you query any post_type.
	The $data['option'] value stores the post_type to use for this relation.
	------------------------------------------------------------------------------*/
	private static function _get_relation_element(&$data)
	{
		global $post;
		global $wpdb;
		
		$media_html = '';

		// How do we format the current field value? 
		if ( !empty($data['value']) )
		{
			$query = "SELECT * FROM {$wpdb->posts} WHERE ID = %s";

			$relation_post = $wpdb->get_results( $wpdb->prepare( $query, $data['value'] ), OBJECT );
			$data['preview_html'] = '<span class="formgenerator_label">'.$relation_post[0]->post_title.' <span class="formgenerator_id_label">('.$data['value'].')</span></span> <br/>';
//			$data['preview_html'] = '<span class="formgenerator_label">'.$relation_post[0]->post_title.'</span> <br/>';

		}
		
		$data['controller_url'] = CCTM_URL.'/post-selector.php?';
		$data['click_label'] = __('Choose Relation');
		$tpl = '
			<span class="formgenerator_label formgenerator_media_label" id="formgenerator_label_[+name+]">[+label+]</span><br />
			<input type="hidden" id="[+id+]" name="[+name+]" value="[+value+]" />
			<div id="[+id+]_media">[+preview_html+]</div>
			<br class="clear" />
			<a href="[+controller_url+]&fieldname=[+id+]&post_type=[+options+]" name="[+click_label+]" class="thickbox button">[+click_label+]</a>
			<br class="clear" /><br />';
		return self::parse($tpl, $data);
	}
	
	/*------------------------------------------------------------------------------
	This special function helps us when we need to edit the options of an existing
	dropdown.  It is ONLY called by the _get_dropdown_element() function.
	------------------------------------------------------------------------------*/
	private static function _get_special($data)
	{
		$special_str = '<div id="'.self::element_wrapper_id_prefix.self::$i.'_dropdown">
			<strong>Dropdown Options</strong> 
			<span class="button" onclick="javascript:addDropdownOption(this.parentNode.id,'
				.$data['def_i'].')">Add Option</span>';
		$i = 0;		
		foreach ($data['special'] as $opt )
		{
			
			$special_str .= sprintf('
				<div id="'.self::element_wrapper_id_prefix.'%s_dropdown_opt%s">
					<input class="" name="custom_fields[%d][options][]" value="%s" type="text"> 
					<span class="button" onclick="javascript:removeDiv(this.parentNode.id)">Remove</span>
				</div>'
				, self::$i	// used to correctly name this div as part of the parent dropdown 
				, $i 				// used only for a unique option name
				, $data['def_i']	// identifies the correct place in the $_POST array
				, htmlspecialchars($opt)
			);
			$i++;
		}
		$special_str .= '</div>';

		return $special_str;
	
	}
	
	//------------------------------------------------------------------------------
	private static function _get_text_element(&$data)
	{
		$data['bears'] = 'yogi';
		$tpl = '
			<label for="[+name+]" class="formgenerator_label formgenerator_text_label" id="formgenerator_label_[+name+]">[+label+]</label>
			<input type="text" name="[+name+]" class="formgenerator_text" id="[+name+]" value="[+value+]"[+extra+]/>';
		return self::parse($tpl, $data);
	}
	
	//------------------------------------------------------------------------------
	private static function _get_textarea_element($data)
	{
		$tpl = '
			<label for="[+name+]" class="formgenerator_label formgenerator_textarea_label" id="formgenerator_label_[+name+]">[+label+]</label>
			<textarea name="[+name+]" class="formgenerator_textarea" id="[+name+]" [+extra+]>[+value+]</textarea>';
		return self::parse($tpl, $data);	
	}


	//------------------------------------------------------------------------------
	private static function _get_wysiwyg_element(&$data)
	{
		$tpl = '
			<label for="[+name+]" class="formgenerator_label formgenerator_wsyiwyg_label" id="formgenerator_label_[+name+]">[+label+]</label>
			<textarea name="[+name+]" class="formgenerator_wysiwyg" id="[+name+]" [+extra+]>[+value+]</textarea>
			<script type="text/javascript">
				jQuery( document ).ready( function() {
					jQuery( "[+name+]" ).addClass( "mceEditor" );
					if ( typeof( tinyMCE ) == "object" && typeof( tinyMCE.execCommand ) == "function" ) {
						tinyMCE.execCommand( "mceAddControl", false, "[+name+]" );
					}
				});
			</script>';	
		return self::parse($tpl, $data);
	}
	
	

	//! Public Functions	
	/*------------------------------------------------------------------------------
	This is the workhorse function of this entire class. See top of this file for
	a usage example.
	
	Each form element is wrapped in a div with unique id. How that div is identified
	is controlled by the $id_method parameter. $id_method can have the following values:
	
	css-friendly
	When the div is generated for the pages that are creating/editing posts 
	(i.e. a normal manager user), then the id's are based off a CSS friendly field name.
	This ensures easy styling for designers who want to customize the admin forms, and
	any style definitions are not affected by the seq order of the fields as they would
	be if you let either javascript or PHP (via the default option here) generate 
	numerical based ids.
	
	javascript
	When the form is being generated for inclusing in a Javascript function, it's important
	to let Javascript iterate an integer to ensure a unique element id. When this 
	option is used, a Javascript function (named in self::javascript_iterator_function)
	is called which will append an integer to the element id name.
	
	default
	Default behavior is to rely on the class variable Function::$i; this is used when generating
	existing custom fields in the "Manage Custom Fields" page.
	
	INPUT: $field_defs_array (mixed) a definition array of arrays
	@param string $id_method  (optional) css-friendly|javascript or omitted. See above for description.
	OUTPUT: HTML text.
	------------------------------------------------------------------------------*/
	public static function generate($field_defs_array, $id_method=null)
	{
		// self::$placeholders = array(); // reset.
		
		if ( empty($field_defs_array) )
		{
			return '';
		}

		usort($field_defs_array, 'FormGenerator::sort_recordset');

		$output = '';
		
		foreach ( $field_defs_array as $def_i => $field_def ) 
		{
			//print_r($field_def); exit;
			$output_this_field = '';
			if (!isset($field_def['id']))
			{
				$field_def['id'] = $field_def['name'];
			}
			$field_def['i'] = self::$i;
			switch ( $field_def['type'] ) 
			{
				case 'checkbox':
					$output_this_field .= self::_get_checkbox_element($field_def);
					break;
				case 'dropdown':
					$output_this_field .= self::_get_dropdown_element($field_def);
					break;
				case 'image':
					$output_this_field .= self::_get_image_element($field_def);
					break;
				case 'media':
					$output_this_field .= self::_get_media_element($field_def);
					break;
				case 'readonly':
					$output_this_field .= self::_get_readonly_element($field_def);
					break;
				case 'relation':
					$output_this_field .= self::_get_relation_element($field_def);
					break;
				case 'textarea':
					$output_this_field .= self::_get_textarea_element($field_def);
					break;
				case 'wysiwyg':
					$output_this_field .= self::_get_wysiwyg_element($field_def);
					break;
				case 'text':
				default: 
					$output_this_field .= self::_get_text_element($field_def);
					break;
			}
			// optionally add description
			if ( isset($field_def['description']) && !empty($field_def['description']) ) 
			{
				$output_this_field .= '
					<span class="formgenerator_description">'.$field_def['description'].'</span>';
			}

			// The way we id each element is controlled by the $id_method param
			$div_id = '';
			switch ($id_method)
			{
				case 'css-friendly':
					$div_id = self::element_wrapper_id_prefix . $field_def['raw_name'];
					break;
				case 'javascript':
					$div_id = self::element_wrapper_id_prefix . "'+ ".self::javascript_iterator_function."() + '";
					break;
				default:
					$div_id = self::element_wrapper_id_prefix . self::$i;
			}
			
			// Div that wraps each form element.
			$output .= sprintf('
				<div class="%s" id="%s">
					%s
				</div>'
				, self::$element_wrapper_class
				, $div_id
				, $output_this_field
			);
			self::$i = self::$i + 1;
			
			// STORE placeholder for this field
			$key = $field_def['name'];
			if ( isset($field_def['raw_name']) )
			{
				$key = $field_def['raw_name'];
			}
			
			//print $key. '<-----------'; exit;
			self::$placeholders[$key] = $output;
			// STORE a 'help' placeholder as an aid to devs creating a custom tpl for a given content type in /tpls/manager/
			$placeholders_this_field = array_keys($field_def);
			
			foreach ( $placeholders_this_field as &$p )
			{
				$p = '&#91;+'. $key.'.'.$p.'+&#93;';
			}
//			print_r($placeholders_this_field); exit;
			// Add the primary placeholder that contains the formatted field.
			array_unshift($placeholders_this_field, '&#91;+'.$key.'+&#93;');
			
			// Messaging to the user
			self::$placeholders[$key.'.help'] = sprintf( __('The %s custom field can use the following placeholders: %s',CCTM_TXTDOMAIN)
				, $key
				, implode(' ,', $placeholders_this_field)
			);
			// STORE placeholders for each value in this field 
			foreach ( $field_def as $k => $v )
			{
				self::$placeholders[$key.'.'.$k] = $v;	
			}
		}

		// Wrap output
		$output = self::$before_elements . $output . self::$after_elements;

		return $output;
	}
	
	/*------------------------------------------------------------------------------
	SYNOPSIS: a simple parsing function for basic templating.
	INPUT:
		$tpl (str): a string containing [+placeholders+]
		$hash (array): an associative array('key' => 'value');
	OUTPUT
		string; placeholders corresponding to the keys of the hash will be replaced
		with the values and the string will be returned.
	------------------------------------------------------------------------------*/
	public static function parse($tpl, $hash) 
	{
		$hash = array_merge($hash, self::$global_placeholders);

	    foreach ($hash as $key => $value) 
	    {
	    	if ( !is_array($value) )
	    	{
	        	$tpl = str_replace('[+'.$key.'+]', $value, $tpl);
	        }
	    }
	    
	    // Remove any unparsed [+placeholders+]
	    $tpl = preg_replace('/\[\+(.*?)\+\]/', '', $tpl);
	    
	    return $tpl;
	}

	/*------------------------------------------------------------------------------
	Callback function used by usort(). Sorts a recordset: an array of hashes, e.g.

		array(
			array('name' => 'Abe', 'sort_param' => '2'),
			array('name' => 'Bob', 'sort_param' => '1'),
		)

	Technique from: http://www.the-art-of-web.com/php/sortarray/
	------------------------------------------------------------------------------*/
	public static function sort_recordset($a, $b)
	{
		return strnatcmp($a[self::$sorting_column], $b[self::$sorting_column]);
	}
	
} // End class
/*EOF*/