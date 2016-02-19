<?php
/*
 * Author : Anushka K R
 * Author URL : http://www.anushkakr.com
 * Created : 02/19/2016
 * This class implements the comunication facility with the plugin comunication api
 * 
 * */

if (!class_exists('CCTM_Communicator')) {
	class CCTM_Communicator {
		/* Private properties */
		private $api_url;
		private $client_info;
		private $current_plugin_data;
		
		private static $default_headers = array(
				'Name' => 'Plugin Name',
				'PluginURI' => 'Plugin URI',
				'Version' => 'Version',
				'Description' => 'Description',
				'Author' => 'Author',
				'AuthorURI' => 'Author URI',
				'TextDomain' => 'Text Domain',
				'DomainPath' => 'Domain Path',
				'Network' => 'Network',
				// Site Wide Only is deprecated in favor of Network.
				'_sitewide' => 'Site Wide Only',
				'PluginIcon' => 'Icon URL',
				'DBVersion' => 'Db Version',
				'DbRemove' => 'Db Remove',
				'LICENSE_SERVER_URL' => 'License Srv Url',
				'LICENSE_SECRET' => 'License Secert',
				'UserDoc'	=> 'UserDocumentation',
				'DevDoc' => 'DevDocumentation',
				'HelpSup' => 'HelpAndSupport',
				'Environment' => 'Environment',
				'PluginAPIURI' => 'Plugin API URI'
		);
		/* Private properties */
		
		/* Private members */ 
		private  function _loadMetaData(){			
			try {
				$this->current_plugin_data = get_file_data(CCTM_PATH.'/index.php', $this::$default_headers);
			} catch (Exception $e) {
			}
		}
		
		private function _collectClientInfo(){
			try {
				global $wp_version;
				$this->client_info['url'] = 'google.com';
				$this->client_info['site_url'] = get_site_url();
				$this->client_info['wp_version'] = $wp_version;
				$this->client_info['plugin_version'] = $this->plugin_get_version();
				$this->client_info['admin_email'] = get_option('admin_email');
				$this->client_info['date_activate'] = date("Y-m-d H:i:s");
		
			} catch (Exception $e) {
				CCTM::$warnings[] = $e.getMessage();
			}
		}
		
		private function plugin_get_version() {
			if ( ! function_exists( 'get_plugins' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				$plugin_folder = get_plugins();
				return $plugin_folder[CCTM_DIR]['Version'];
		}
		
		private function _is_curl_installed() {
			if  (in_array  ('curl', get_loaded_extensions())) {
				return true;
			}
			else {
				return false;
			}
		}
		
		/* Private members */
		
		/* public members */
		public function __construct() {
			try {
				$this->_loadMetaData();
				$this->api_url = $this->current_plugin_data['PluginAPIURI'];
				$this->client_info = array();
			} catch (Exception $e) {
				CCTM::$warnings[] = $e.getMessage();
			}
		}
		
		public function addInfo($args){
			try {
				if (is_array($args)) {
				 $this->client_info = array_merge($this->client_info,$args);
				}
			} catch (Exception $e) {
				CCTM::$warnings[] = $e.getMessage();
			}
		}
		
		public function send_info(){
			try {	
				$this->_collectClientInfo();
				$fields_string = http_build_query($this->client_info);
				
					error_reporting(0);
				
				//if($this->_is_curl_installed()){								
					//open connection
					$ch = curl_init();				
					//set the url, number of POST vars, POST data
					curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch,CURLOPT_URL, $this->api_url);
					curl_setopt($ch,CURLOPT_POST, 1);
					curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);				
					//execute post
					$result = curl_exec($ch);
					//close connection
					curl_close($ch);
				//}
				//else{	
					$opts = array('http' =>
							array(
									'method'  => 'POST',
									'header'  => 'Content-type: application/x-www-form-urlencoded',
									'content' => $fields_string
							)
					);
					
					$context  = stream_context_create($opts);
					
					$result = file_get_contents($this->api_url, false, $context);
					
					error_reporting(E_ALL);
				//}
				
			} catch (Exception $e) {
				CCTM::$warnings[] = $e.getMessage();
			}
		}
		
		/* public members */
	}
}
