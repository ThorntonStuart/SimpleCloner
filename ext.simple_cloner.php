<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'simple_cloner/config.php';

setlocale(LC_ALL, 'en_US.UTF8');

/**
 * Simple Cloner Extension class
 *
 * @package        simple_cloner
 * @author         Stuart Thornton <sthornton@knightknox.com>
 * @link           http://www.github.com/ThorntonStuart
 * @copyright      Copyright (c) 2016, Stuart Thornton
 */

class Simple_cloner_ext {
	
	/**
	 * Settings array
	 *
	 * @var 	array
	 * @access 	public
	 */
	public $settings = array();

	var $addon_name		=	SIMPLE_CLONER_NAME;
	var $name 			=	SIMPLE_CLONER_NAME;
	var $version 		=	SIMPLE_CLONER_VER;
	var $description	=	SIMPLE_CLONER_DESC;
	var $settings_exist	=	'y';
	var $docs_url		=	'';

	/**
	 * Constructor
	 *
	 * @access 	public
	 * @param 	array
	 * @return 	void
	 */
	public function __construct($settings = array()) 
	{
		// Load the settings library and initialize settings to default values.
		ee()->load->library('simple_cloner_settings');
		ee()->simple_cloner_settings->set($settings);

		// Fetch current settings values and override.
		$this->settings = ee()->simple_cloner_settings->get();
	}

	/**
	 * Settings
	 *
	 * @access 	public
	 * @return 	void
	 */
	public function settings()
	{
		// Redirect extension settings to module settings.
		ee()->functions->redirect('addons/settings/simple_cloner');
	}

	/**
	 * Format string to URI
	 * 
	 * @access 	public
	 * @param 	string
	 * @return 	string
	 */
	public function strToUrl($str, $replace = array(), $delimiter = '-')
	{
		// If replace parameter empty, 
		if(! empty($replace))
		{
			$str = str_replace((array)$replace, ' ', $str);
		}

		// Convert to UTF-8, replace characters that would break URL, convert to lowercase and replace splitting characters with hyphen.
		$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
		$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $str);
		$clean = strtolower(trim($clean, '-'));
		$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

		return $clean;
	}

	/**
	 * Clone after entry save
	 *
	 * @access 	public
	 * @param 	object
	 * @param 	array
	 * @return 	void
	 */
	public function simple_cloner_content_save($entry, $data)
	{
		// Query for entry row in Simple Cloner content created from tab save. If it returns correctly, assign row and convert from object to array.
		$entry_query = ee()->db->query("SELECT * FROM exp_simple_cloner_content WHERE entry_id = " . $data['entry_id']);
		$entry_query = $entry_query->result();

		if(! empty($entry_query))
		{
			$entry_query_array = $entry_query[0];
			$entry_query_array = get_object_vars($entry_query_array);
		}
		
		// Check that row has been assigned and that the entry cloning flag has been checked.
		if(isset($entry_query_array) == TRUE && $entry_query_array['clone_entry'] != 0)
		{
			// Format date for database re-entry.
			if(! is_string($data['recent_comment_date']))
			{
				$data['recent_comment_date'] = $data['recent_comment_date']->format('Y-m-d');
			}

			// Load channel entries API.
			ee()->load->library('api');
			ee()->legacy_api->instantiate('channel_entries');
			ee()->legacy_api->instantiate('channel_fields');

			// Query for settings in extensions table. If set, process settings values for cloned entry. Tab settings take precedence over default settings.
			$settings_query = ee()->db->select('settings')
				->where('enabled', 'y')
				->where('class', __CLASS__)
				->get('extensions', 1);
			
			if($settings_query->num_rows())
			{
				$settings_query = unserialize($settings_query->row()->settings);

				if(strlen($entry_query_array['title_suffix']) > 0)
				{
					$data['title'] = $data['title'] . '_' . $entry_query_array['title_suffix'];
				} 
				elseif (strlen($settings_query['title_suffix']) > 0) 
				{
					$data['title'] = $data['title'] . '_' . $settings_query['title_suffix'];
				}

				if(strlen($entry_query_array['url_title_suffix']) > 0)
				{
					$urlString = $data['url_title'] . '_' . $entry_query_array['url_title_suffix'];
					$data['url_title'] = $this->strToUrl($urlString);
				} 
				elseif (strlen($settings_query['url_title_suffix']) > 0) 
				{
					$urlString = $data['url_title'] . '_' . $settings_query['url_title_suffix'];
					$data['url_title'] = $this->strToUrl($urlString);
				}

				if($entry_query_array['update_entry_time'] == '1' || $settings_query['update_entry_time'] == 'y')
				{
					$data['entry_date'] = time();
					$data['year'] = date('Y', time());
					$data['month'] = date('m', time());
					$data['day'] = date('d', time());
				}
			}

			// Assign meta settings for new cloned entry and save entry to database.
			$data_template = array(
				'entry_id' => $data['entry_id'],
				'site_id' => $data['site_id'],
				'channel_id' => $data['channel_id'],
				'author_id' => $data['author_id'],
				'forum_topic_id' => $data['forum_topic_id'],
				'ip_address' => $data['ip_address'],
				'title' => $data['title'],
				'url_title' => $data['url_title'],
				'status' => $data['status'],
				'versioning_enabled' => $data['versioning_enabled'],
				'view_count_one' => $data['view_count_one'],
				'view_count_two' => $data['view_count_two'],
				'view_count_three' => $data['view_count_three'],
				'view_count_four' => $data['view_count_four'],
				'allow_comments' => $data['allow_comments'],
				'entry_date' => $data['entry_date'],
				'edit_date' => $data['edit_date'],
				'year' => $data['year'],
				'month' => $data['month'],
				'day' => $data['day'],
				'expiration_date' => $data['expiration_date'],
				'comment_expiration_date' => $data['comment_expiration_date'],
				'sticky' => $data['sticky']
			);

			// Validate for required fields.
			foreach ($data as $key => $value) {
				if(strpos($key, 'field_id') !== FALSE)
				{
					// Get field ID number to query exp_channel_fields table.
					$field_id = explode('field_id_', $key);
					$field_id = $field_id[1];

					// Loop through every ID in data row and select those that are required fields.
					$validate_require = ee()->db->query("SELECT field_id, field_name FROM exp_channel_fields WHERE field_id = " . $field_id . " AND field_required = 'y'");

					if($validate_require->num_rows != 0)
					{
						$validate_require = $validate_require->result();
						
						foreach ($validate_require as $k => $v) {
							$arrayValue = get_object_vars($v);
							
							$str_id = 'field_id_' . $arrayValue['field_id'];
							$str_ft = 'field_ft_' . $arrayValue['field_id'];
							$str_name = $arrayValue['field_name'];

							// Assign required values to data_template array so that they can be processed on API save_entry.
							$data_template[$str_id] = $data[$str_id];
						}
					}
				}
			}

		 	ee()->api_channel_fields->setup_entry_settings($data['channel_id'], $data_template);

			if (! ee()->api_channel_entries->save_entry($data_template, $data['channel_id']))
			{
				$errors = ee()->api_channel_entries->get_errors();
				print_r($errors);
			}

			// Select entry ID of newly cloned entry.
			$query = ee()->db->query("SELECT MAX(entry_id) FROM `exp_channel_titles`");
			$query_result = $query->result();

			foreach ($query_result as $key => $value) {
				$array = get_object_vars($value);
				foreach ($array as $k => $v) {
					$query_result = $v;
				}
			}

			// Remove all meta fields from data array leaving just custom fields.
			foreach ($data as $key => $value) {
				if(!array_key_exists($key, $data_template))
				{
					if(preg_match('/field_ft_[0-9]/', $key) == 0 && preg_match('/field_id_[0-9]/', $key) == 0)
					{
						unset($data[$key]);
					}
				}
			}

			// Check for Seo_lite module and if it exists, create a row for this entry in the seolite_content table.
			// There is a known bug regarding interaction with the Seolite module when deleting cloned entries. This should soon be resolved.
			// This should also be expanded in a later version to copy over the values of the values of the cloned entry for the Seolite module.

			$seolite = ee()->db->query("SELECT * FROM exp_modules WHERE module_name = 'Seo_lite'");

			if($seolite->num_rows() != 0)
			{
				ee()->db->insert(
					'seolite_content',
					array(
						'entry_id'	=>	$query_result,
						'site_id'	=>	$data['site_id'],
						'title'		=>	'',
						'keywords'	=>	'',
						'description'	=>	'',
					)
				);
			}

			// Update cloned entry with custom field data.
			ee()->api_channel_entries->update_entry($query_result, $data);
			ee()->db->update(
				'exp_simple_cloner_content',
				array(
					'clone_entry'	=>	'0'
				),
				array(
					'entry_id'	=>	$data['entry_id']
				)
			);
		} else {
			return FALSE;
		}
	}
}