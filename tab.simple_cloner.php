<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'simple_cloner/config.php';

/**
 * Simple Cloner Tab class
 *
 * @package        simple_cloner
 * @author         Stuart Thornton <sthornton@knightknox.com>
 * @link           http://www.github.com/ThorntonStuart
 * @copyright      Copyright (c) 2016, Stuart Thornton
 */

class Simple_cloner_tab {

	var $class_name		=	SIMPLE_CLONER_NAME_FORMATTED;

	/**
	 * Constructor
	 *
	 * @access 	public
	 * @return 	void
	 */
	public function __construct()
	{
		// Load module lang file and set tab title.
		ee()->lang->loadfile('simple_cloner');

		$tab_title = 'Simple Cloner';

		if($tab_title) {
			ee()->lang->language['simple_cloner'] = $tab_title;
		}
	}

	/**
	 * Tab display
	 *
	 * @access 	public
	 * @param 	int
	 * @param 	int
	 * @return 	array
	 */
	public function display($channel_id, $entry_id = '') {
		// Initialize settings array and assign each setting as an index with required vals.
		$settings = array();

		$settings['cloned_entry_status'] = array(
			'field_id'				=>	'cloned_entry_status',
			'field_label'			=>	lang('tab_cloned_entry_status'),
			'field_required'		=>	'n',
			'field_data'			=>	'',
			'field_list_items'		=>	'',
			'field_fmt'				=>	'',
			'options'				=>	array(),
			'field_instructions'	=>	lang('tab_cloned_entry_status_instructions'),
			'field_show_fmt'		=>	'n',
			'field_text_direction'	=>	'ltr',
			'field_type'			=>	'text',
			'field_maxl'			=>	'1024'
		);

		$settings['title_suffix'] = array(
			'field_id'				=>	'title_suffix',
			'field_label'			=>	lang('tab_title_suffix'),
			'field_required'		=>	'n',
			'field_data'			=>	'',
			'field_list_items' 		=> '',
			'field_fmt' 			=> '',
			'options' 				=> array(),
			'field_instructions' 	=> lang('tab_title_suffix_instructions'),
			'field_show_fmt' 		=> 'n',
			'field_fmt_options' 	=> array(),
			'field_pre_populate' 	=> 'n',
			'field_text_direction' 	=> 'ltr',
			'field_type' 			=> 'text',
			'field_maxl' 			=> '1024'
		);

		$settings['url_title_suffix'] = array(
			'field_id'				=>	'url_title_suffix',
			'field_label'			=>	lang('tab_url_title_suffix'),
			'field_required'		=>	'n',
			'field_data'			=>	'',
			'field_list_items' 		=> '',
			'field_fmt' 			=> '',
			'options' 				=> array(),
			'field_instructions' 	=> lang('tab_url_title_suffix_instructions'),
			'field_show_fmt' 		=> 'n',
			'field_fmt_options' 	=> array(),
			'field_pre_populate' 	=> 'n',
			'field_text_direction' 	=> 'ltr',
			'field_type' 			=> 'text',
			'field_maxl' 			=> '1024'
		);

		$settings['update_entry_time'] = array(
			'field_id'				=>	'update_entry_time',
			'field_label'			=>	lang('tab_update_entry_time'),
			'field_required'		=>	'n',
			'field_data'			=>	'',
			'field_list_items' 		=> '',
			'field_fmt' 			=> '',
			'options' 				=> array(),
			'field_instructions' 	=> lang('tab_update_entry_time_instructions'),
			'field_show_fmt' 		=> 'n',
			'field_fmt_options' 	=> array(),
			'field_pre_populate' 	=> 'n',
			'field_text_direction' 	=> 'ltr',
			'field_type' 			=> 'toggle',
			'field_maxl' 			=> '1024'
		);

		$settings['clone_entry'] = array(
			'field_id'				=>	'clone_entry',
			'field_label'			=>	lang('tab_clone_entry'),
			'field_required'		=>	'n',
			'field_data'			=>	'',
			'field_list_items' 		=> '',
			'field_fmt' 			=> '',
			'options' 				=> array(),
			'field_instructions' 	=> lang('tab_clone_entry_instructions'),
			'field_show_fmt' 		=> 'n',
			'field_fmt_options' 	=> array(),
			'field_pre_populate' 	=> 'n',
			'field_text_direction' 	=> 'ltr',
			'field_type' 			=> 'toggle',
			'field_maxl' 			=> '1024'
		);

		// Check if toggle is a fieldtype in user's EE version. If not, change fieldtype.
		$hasToggle = ee()->db->select('*')
							->from('fieldtypes')
							->where(array('name' =>	'toggle'))
							->get();

		$status_group_query = ee()->db->select('status_group')
								->from('channels')
								->where('channel_id', $channel_id)
								->get();
		$status_group = $status_group_query->result_array();

		$statuses_query = ee()->db->select('status')
							->from('statuses')
							->where('group_id', $status_group[0]['status_group'])
							->get();
		$statuses = $statuses_query->result_array();

		$settings['cloned_entry_status']['field_type'] = 'select';
		$settings['cloned_entry_status']['field_list_items'] = array();

		array_push($settings['cloned_entry_status']['field_list_items'], "Channel Default");

		foreach($statuses as $key => $value) {
			array_push($settings['cloned_entry_status']['field_list_items'], ucwords($statuses[$key]['status']));
		}

		if($hasToggle->num_rows() == 0) {
			$settings['update_entry_time']['field_type'] = 'select';
			$settings['update_entry_time']['field_list_items'] = array('Yes', 'No');

			$settings['clone_entry']['field_type'] = 'select';
			$settings['clone_entry']['field_list_items'] = array('Yes', 'No');
		}

		// Query module settings and set update_entry_time to default in tab value exists.
		$settings_query = ee()->db->select('settings')
				->where('enabled', 'y')
				->where('class', $this->class_name.'_ext')
				->get('extensions', 1);

		$settings_query = unserialize($settings_query->row()->settings);

		if($settings_query['update_entry_time'] == 'y') {
			if($hasToggle->num_rows() == 0) {
				$settings['update_entry_time']['field_data'] = '0';
				$settings['clone_entry']['field_data'] = '1';
			} else {
				$settings['update_entry_time']['field_data'] = '1';
				$settings['clone_entry']['field_data'] = '0';
			}
		}

		return $settings;
	}

	/**
	 * Validate method (not used)
	 *
	 * @access 	public
	 * @param 	EllisLabExpressionEngineModuleChannelModelChannelEntry
	 * @param 	array
	 * @return 	bool
	 */
	public function validate($channel_entry, $params) {
		return TRUE;
	}

	/**
	 * Save tab
	 *
	 * @access 	public
	 * @param 	EllisLabExpressionEngineModuleChannelModelChannelEntry
	 * @param 	array
	 * @return 	void
	 */
	public function save($channel_entry, $params) {
		// Check if fieldtypes have been switched to selects. If they have, convert values to toggle values for DB. Needs refactoring.
		$hasToggle = ee()->db->select('*')
							->from('fieldtypes')
							->where(array('name' =>	'toggle'))
							->get();

		if($hasToggle->num_rows() == 0) {
			switch($params['update_entry_time']) {
				case '0':
					$params['update_entry_time'] = '1';
					break;
				case '1':
					$params['update_entry_time'] = '0';
					break;
				default:
					$params['update_entry_time'] = '0';
			}

			switch($params['clone_entry']) {
				case '0':
					$params['clone_entry'] = '1';
					break;
				case '1':
					$params['clone_entry'] = '0';
					break;
				default:
					$params['clone_entry'] = '0';
			}
		}

		// Assign content variables to be entered into Simple Cloner Content table.
		$site_id = $channel_entry->site_id;
		$entry_id = $channel_entry->entry_id;

		$content = array(
			'site_id'	=>	$site_id,
			'entry_id'	=>	$entry_id,
			'cloned_entry_status'	=>	$params['cloned_entry_status'],
			'title_suffix'		=>	$params['title_suffix'],
			'url_title_suffix'	=>	$params['url_title_suffix'],
			'update_entry_time'	=>	$params['update_entry_time'],
			'clone_entry'		=>	$params['clone_entry']
		);

		// Fetch table and set where statement for specific entry and site.
		$table_name = 'simple_cloner_content';
		$where = array(
			'entry_id'	=>	$entry_id,
			'site_id'	=>	$site_id
		);

		$default_where = $where;
		$default_conent = $content;
		$default_table_name = $table_name;

		$query = ee()->db->get_where($table_name, $where);

		// If clone entry flag has been selected, update the table, if not insert row.
		if($params['clone_entry'] == '1') {
			if($query->num_rows()) {
				ee()->db->where($where);
				ee()->db->update($table_name, $content);
			} else {
				ee()->db->insert($table_name, $content);
			}
		}
	}

	/**
	 * Delete row from table if entry deleted.
	 *
	 * @access 	public
	 * @param 	array
	 * @return 	void
	 */
	public function delete($entry_ids)
	{
		foreach ($entry_ids as $i => $entry_id) {
			ee()->db->where('entry_id', $entry_id);
			ee()->db->delete('simple_cloner_content');
		}
	}
}
