<?php
/**
 * A collection of output filter functions that are used when passing values from the 
 * database to the theme file that is requesting the value via a get_custom_field()
 * instance. These functions must take one input (the value stored in the database),
 * and they can optionally take a 2nd input (additional options).
 */
class OutputFilters {

	/**
	 * Contains key => values, where the keys are the function names in this class and 
	 * the values are localized descriptions of the filters,
	 * e.g. array( 'email' => 'Encode Email Address' )
	 */
	public $descriptions;

	public function __construct() {
		$this->descriptions['convert_date_format'] = __('Convert Date Format', CCTM_TXTDOMAIN );
		$this->descriptions['email'] = __('Encode Email Address', CCTM_TXTDOMAIN );
		$this->descriptions['formatted_list'] = __('Formatted List', CCTM_TXTDOMAIN );
		$this->descriptions['to_array'] = __('Array', CCTM_TXTDOMAIN );
		$this->descriptions['to_image_src'] = __('Image src', CCTM_TXTDOMAIN );
		$this->descriptions['to_image_tag'] = __('Full &lt;img&gt; tag', CCTM_TXTDOMAIN );
		$this->descriptions['to_image_array'] = __('Array of image src, width, height', CCTM_TXTDOMAIN );
		$this->descriptions['to_link'] = __('Full link &lt;a&gt; tag', CCTM_TXTDOMAIN );
		$this->descriptions['to_link_href'] = __('Link href only', CCTM_TXTDOMAIN );
	}

	/**
	 * Used when you want to store one date format in the DB and display another
	 * in your templates to the end users.
	 */
	public function convert_date_format($value, $new_format=null) {
	
	}

	/**
	 * Simple way to encode an email address.  
	 * See http://davidwalsh.name/php-email-encode-prevent-spam
	 * @param string	e.g. 'you@site.com'
	 * @return string	an encoded 
	 */
	public function email($value) {
		$output = '';
		for ($i = 0; $i < strlen($value); $i++) { 
			$output .= '&#'.ord($value[$i]).';'; 
		}
		return $output;
	}

	/**
	 * Translate a json-formatted array into an actual array
	 */
	public function formatted_list($value, $opts) {

		$array = $this->to_array($value);
		if ( !empty($opts) && is_array($opts) ) {
			$out = '';
			// format each value
			if ( isset($opts[0]) ) {
				foreach ( $array as $v ) {
					$hash['value'] = $v;
					$out .= CCTM::parse($opts[0], $hash);
				}
			}
			else {
				// ???
			}
			
			// wrap the output
			if ( isset($opts[1]) ) {
				$hash['content'] = $out;
				return CCTM::parse($opts[1], $hash);		
			}			
		}
		elseif (!empty($opts) && !is_array($opts) ) {
			return implode($opts, $array);
		}
		else{
			return __('Formatted List Output Filter: Second parameter must not be empty. <a href="http://code.google.com/p/wordpress-custom-content-type-manager/wiki/OutputFilters">Info</a>', CCTM_TXTDOMAIN );
		}
	}


	/**
	 * Translate a json-formatted array into an actual array
	 */
	public function to_array($value) {
		return json_decode($value, true);
	}
	
	
	/**
	 * Translate a post_id to the src for the image represented by the 
	 */
	public function to_image_src($value) {
		return $this->to_link_href($value);
	}

	/**
	 * Translate a post_id to a full image tag.
	 */
	public function to_image_tag($value, $options='full') {
		return wp_get_attachment_image($value, $options);
	}
	
	/**
	 * Returns an array of an image's src, width, and height
	 * @ return 	array of an image's src, width, and height
	 */
	public function to_image_array($value, $options) {
		return wp_get_attachment_image_src( $value, $options, true);
	}

	/**
	 * Returns a full anchor tag (<a>) the post_id passed in as a value.
	 * @param	integer	post ID
	 * @param	string	optionally include the text to be displayed in the link
	 */
	public function to_link($value, $option=null) {
		$post = get_post($value);
		$link_text = $this->post_title;
		if (!empty($option)) {
			$link_text = $option;
		}
		return sprintf('<a href="%s">%s</a>', $post->guid, $link_text);
	}
	
	/**
	 * Retrieves the GUID for the post_id passed in as a value.
	 */
	public function to_link_href($value) {
		$post = get_post($value);
		return $post->guid;
	}


}
/*EOF*/