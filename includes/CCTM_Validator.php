<?php
/**
 * @package CCTM_Validator
 *
 * Abstract class for validation rules.  Classes that implement 
 */
abstract class CCTM_Validator {

	/**
	 * Properties of this validator, as defined and submitted in the get_options() function
	 * published here for easy class-wide access.  Override this to set default values.
	 */ 
	public $options = array();
	
	/**
	 * If a field does not validate, set this message.
	 */
	public $error_msg;
	
	/**
	 * Most validation rules should be publicly visible when you define a field,
	 * but if desired, you can hide a rule from the menu.
	 */
	public $show_in_menus = true;
	

	/**
	 * @return string	a description of what the validation rule is and does.
	 */
	abstract public function get_description();


	/**
	 * @return string	the human-readable name of the validation rules.
	 */
	abstract public function get_name();


	/**
	 * Implement this if your validation rule requires some options: this should
	 * return some form elements that will dynamically be shown on the page via 
	 * an AJAX request if this validator is selected.  
	 * Do not include the entire form, just the inputs you need! 
	 * Form fields should use names that are nodes of $_POST['validation_options'], e.g.
	 * 	<input name="validation_options[option1]" />
	 *
	 * @param	array	$current_values
	 * @return string	HTML form elements
	 */
	public function get_options($current_values) { }
		
	/**
	 * Run the rule: check the user input. Return the (filtered) value that should
	 * be used to repopulate the form.  If an $input is invalid, this function should 
	 * set the $this->error_msg, e.g. 
	 *
	 * if ($input == 'bad') {
	 *		$this->error_msg = __('The input was bad.');
	 * }
	 *
	 * @param string 	$input the value of the field being validated (as it is stored in the database)
	 * @return string	The filtered input after validation.  Usually you want to leave this as the $input.
	 */
	abstract public function validate($input);	

	//------------------------------------------------------------------------------
	//! Public functions
	//------------------------------------------------------------------------------

	/**
	 * 
	 */
	public function get_error_msg() {
		return $this->error_msg;
	}	
	
	/**
	 * Get a unique field ID for a field element, same idea as WP Widget functions.
	 *
	 * @param	string	$id
	 * @return	string	
	 */
	public function get_field_id($id) {
		return "validation_options_$id";
	}
	
	/**
	 * Get a unique field name for a field element, same idea as WP Widget functions.
	 *
	 * @param	string	$id
	 * @return	string	
	 */
	public function get_field_name($name) {
		return "validation_options[$name]";
	}
	
	/**
	 * @param	string	$key name inside of $this->options
	 * @return	string	the value
	 */
	public function get_value($key) {
		if (isset($this->options[$key])) {
			return $this->options[$key]; 
		}
		else {
			return '';
		}
	}
	
	//------------------------------------------------------------------------------
	/**
	 * @param	string	$key inside of $this->options
	 * @return	string	depending on whether the option is set (i.e. checked)
	 */
	public function is_checked($key) {
		if (isset($this->options[$key]) && $this->options[$key]) {
			return ' checked="checked"';
		}
		else {
			return '';
		}
	}

	//------------------------------------------------------------------------------
	/**
	 * @param	string	$key inside of $this->options
	 * @return	string	depending on whether the option is set (i.e. checked)
	 */
	public function is_selected($key) {
		if (isset($this->options[$key]) && $this->options[$key]) {
			return ' selected="selected"';
		}
		else {
			return '';
		}
	}
	
	//------------------------------------------------------------------------------
	/**
	 * @param	array
	 */
	public function set_options($options) {
		$this->options = $options;
	}
	
}
/*EOF*/