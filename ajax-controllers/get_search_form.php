<?php
if ( ! defined('CCTM_PATH')) exit('No direct script access allowed');
if (!current_user_can('edit_posts')) exit('You do not have permission to do that.');

/*------------------------------------------------------------------------------
This controller retrieves a search form
It expects the fieldname (without the cctm_ prefix), but it also needs to handle 
setting search parameters for a new field (which won't have a fieldname yet)
It also accepts the search_parameters (serialized data describing existing values)

@param	$_POST['fieldname']
@param	$_POST['fieldtype'] (optional)
@param	$_POST['search_parameters'] (optional)
------------------------------------------------------------------------------*/
$fieldname = CCTM::get_value($_POST, 'fieldname');
$fieldtype = CCTM::get_value($_POST, 'fieldtype');
$type = ''; // set once we know if we've got a fieldname or a fieldtype

if (empty($fieldname) && empty($fieldtype)) {
	print '<p>'.__('fieldname or fieldtype required.', CCTM_TXTDOMAIN) .'</p>';
	return;
}

$def = CCTM::get_value(CCTM::$data['custom_field_defs'], $fieldname);

if (empty($def)) {
	$type = $fieldtype;
}

$search_parameters_str = '';
if (isset($_POST['search_parameters'])) {
	$search_parameters_str = $_POST['search_parameters'];
}
//print '<pre>'.$search_parameters_str. '</pre>'; return;
$existing_values = array();
parse_str($search_parameters_str, $existing_values);

//print '<pre>'.print_r($existing_values, true) . '</pre>'; 
require_once(CCTM_PATH.'/includes/SummarizePosts.php');
require_once(CCTM_PATH.'/includes/GetPostsQuery.php');
require_once(CCTM_PATH.'/includes/GetPostsForm.php');

$Form = new GetPostsForm();

// How should we search?
$search_by = array();

switch ($type) {
	case 'image':
		$search_by[] = 'post_mime_type';
		$search_by[] = 'taxonomy';
		$search_by[] = 'taxonomy_term';
		$search_by[] = 'post_parent';
		$search_by[] = 'meta_key';
		$search_by[] = 'meta_value';		
		//$search_by[] = 'search_term';
		//$search_by[] = 'search_columns';	
		break;
		
	case 'media':
		$search_by[] = 'post_mime_type';
		$search_by[] = 'taxonomy';
		$search_by[] = 'taxonomy_term';
		$search_by[] = 'post_parent';
		$search_by[] = 'meta_key';
		$search_by[] = 'meta_value';
		break;
	
	// Relation etc.	
	default:
		$search_by[] = 'post_type';
		$search_by[] = 'taxonomy';
		$search_by[] = 'taxonomy_term';
		$search_by[] = 'post_parent';
		$search_by[] = 'meta_key';
		$search_by[] = 'meta_value';
		
		//$search_by[] = 'search_term';
		//$search_by[] = 'search_columns';
}

$form_tpl = '
<style>
[+css+]
</style>
<p>This form will determine which posts will be selectable when users create or edit a post that uses this field.</p>
<form id="search_parameters_form" class="[+form_name+]">
	[+content+]
	<span class="button" onclick="javascript:save_search_parameters(\'search_parameters_form\');">Save</span>
	<span class="button" onclick="javascript:tb_remove();">Cancel</span>
</form>
';

$Form->set_name_prefix('');
$Form->set_id_prefix('');

$Form->set_tpl($form_tpl);
print $Form->generate($search_by, $existing_values);

/*EOF*/