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
	 * Query for entry row in Simple Cloner content created from tab save.
	 *
	 * @access 	public
	 * @param 	string
	 * @return 	string
	 */
	public function getOriginalEntry($entry_id) {
		$entry_array = null;
		$entry_array = ee()->db->select('*')
							   ->from('simple_cloner_content')
							   ->where('entry_id', $entry_id)
							   ->get();
		$entry_array = (array) $entry_array->row();

		if($entry_array) {
			return $entry_array;
		}

		return false;
	}

	/**
	 * Fetch Ansel data for each field in the entry for rerun of Ansel.
	 *
	 * @access 	public
	 * @param 	string
	 * @return 	string
	 */
	public function anselPrepImages($entry_id, $field_id) {
		$ansel_images = ee()->db->select("original_file_id, upload_location_type, upload_location_id, x, y, width, height, position, title, caption, cover, col_id, row_id")
							->from('ansel_images')
							->where('field_id', $field_id)
							->where('content_id', $entry_id)
							->get()
							->result_array();
		return $ansel_images;
	}

	public function fetchDataByFieldtype($field_type, $filter_array, $required = false, $field_id = null) {
		if($required == true) {
			$fields = ee()->db->select('field_id, field_name')
							  ->from('channel_fields')
							  ->where('field_type', $field_type)
							  ->get()
							  ->result_array();
		} else {
			$fields = ee()->db->select('field_id, field_name')
							  ->from('channel_fields')
							  ->where_in('field_id', $filter_array)
							  ->where('field_type', $field_type)
							  ->get()
							  ->result_array();
		}

		return $fields;
	}

	public function formatGridFields($output_array, $grid_fields, $entry_id, $cloned = null) {
		foreach($grid_fields as $gridFieldKey => $gridFieldValue) {
			$grid_field_str = 'field_id_' . $gridFieldValue['field_id'];

			$grid_entry = $this->fetchGridEntry($gridFieldValue['field_id'], $entry_id);

			if(sizeOf($grid_entry) > 0) {
				$output_array[$grid_field_str] = array();
				
				if($cloned) {
					$output_array[$grid_field_str]['entry_id'] = $cloned;
				}

				foreach($grid_entry as $gridEntryKey => $gridEntryValue) {
					$grid_row = 'new_row_' . $gridEntryKey;
					$output_array[$grid_field_str]['rows'][$grid_row] = array();

					foreach($gridEntryValue as $geK => $geV) {
						if(strpos($geK, 'col_id_') !== false || strpos($geK, 'row_id') !== false) {
							$output_array[$grid_field_str]['rows'][$grid_row][$geK] = $geV;
						}
					}
				}
			}
		}

		return $output_array;
	}

	public function formatRelationshipGrid($output_array, $relationship_grid_fields, $entry_id) {
		foreach($relationship_grid_fields as $rgKey => $rgVal) {
			$grid_field_str = 'field_id_' . $rgVal['grid_field_id'];

			foreach($output_array[$grid_field_str]['rows'] as $rowK => $rowV) {
				$gr_col_str = 'col_id_' . $rgVal['grid_col_id'];

				if($rowV['row_id'] == $rgVal['grid_row_id'] && array_key_exists($gr_col_str, $rowV)) {
					if(gettype($output_array[$grid_field_str]['rows'][$rowK][$gr_col_str]['data']) == "array") {
						array_push($output_array[$grid_field_str]['rows'][$rowK][$gr_col_str]['data'], $rgVal['child_id']);
					} else {
						$output_array[$grid_field_str]['rows'][$rowK][$gr_col_str]['data'] = array($rgVal['child_id']);	
					}
				}
			}
		}

		return $output_array;
	}

	public function formatAnselGrid($output_array, $ansel_grid_fields, $entry_id) {
		foreach($ansel_grid_fields as $agKey => $agVal) {
			$field_str = 'field_id_' . $agVal['field_id'];

			$grid_images = $this->anselPrepImages($entry_id, $agVal['field_id']);

			foreach($grid_images as $imageKey => $imageVal) {
				$grid_images[$imageKey]['ansel_image_id'] = "";
				$grid_images[$imageKey]['source_file_id'] = $grid_images[$imageKey]['original_file_id'];
				unset($grid_images[$imageKey]['original_file_id']);
				$grid_images[$imageKey]['order'] = $grid_images[$imageKey]['position'];
				unset($grid_images[$imageKey]['position']);

				foreach($output_array[$field_str]['rows'] as $rowK => $rowV) {
					$ansel_col_str = 'col_id_' . $grid_images[$imageKey]['col_id'];
					if($rowV['row_id'] == $grid_images[$imageKey]['row_id'] && $rowV[$ansel_col_str]) {
						$output_array[$field_str]['rows'][$rowK][$ansel_col_str] = array();
						$new_id = 'ansel_row_id_' . uniqid();
						$output_array[$field_str]['rows'][$rowK][$ansel_col_str][$new_id] = $grid_images[$imageKey];
						unset($output_array[$field_str]['rows'][$rowK][$ansel_col_str][$new_id]['row_id']);
						unset($output_array[$field_str]['rows'][$rowK][$ansel_col_str][$new_id]['col_id']);
					}
				}
			unset($grid_images[$imageKey]);
			}
		}
		return $output_array;
	}

	public function formatRelationshipFields($output_array, $relationship_fields, $entry_id) {
		foreach($relationship_fields as $relFieldKey => $relFieldValue) {
			$rel_entry = ee()->db->select('*')
								  ->from('relationships')
								  ->where('parent_id', $entry_id)
								  ->where('grid_field_id', 0)
								  ->get()
								  ->result_array();

			if(sizeOf($rel_entry) > 0) {
				foreach($rel_entry as $reKey => $reVal) {
					$field_str = 'field_id_' . $reVal['field_id'];

					if(isset($output_array[$field_str]['data'])) {
						array_push($output_array[$field_str]['data'], $reVal['child_id']);
					} else {
						$output_array[$field_str]['data'] = array(
							$reVal['child_id']
						);
					}
				}
			}
		}

		return $output_array;
	}

	public function formatBloqsFields($output_array, $bloqs_fields, $entry_id) {
		foreach($bloqs_fields as $bloqsFieldKey => $bloqsFieldVal) {
			$bloqs_data = ee()->db->select('*')
									->from('blocks_block')
									->where('entry_id', $entry_id)
									->where('field_id', $bloqsFieldVal['field_id'])
									->get()
									->result_array();

			if(sizeOf($bloqs_data) > 0) {
				$field_str = 'field_id_' . $bloqsFieldVal['field_id'];
				$output_array[$field_str] = array();

				foreach($bloqs_data as $bloqsDataKey => $bloqsDataVal) {
					$row_str = 'blocks_new_row_' . ($bloqsDataKey + 1);
					$output_array[$field_str][$row_str] = array();
					$output_array[$field_str][$row_str]['blockdefinitionid'] = $bloqsDataVal['blockdefinition_id'];
					$output_array[$field_str][$row_str]['order'] = $bloqsDataVal['order'];
					$output_array[$field_str][$row_str]['values'] = array();
					$output_array[$field_str][$row_str]['values']['block_id'] = $bloqsDataVal['id'];

					$atom_data = ee()->db->select('*')
										->from('blocks_atom')
											->where('block_id', $bloqsDataVal['id'])
											->get()
											->result_array();
					
					foreach($atom_data as $atomKey => $atomVal) {
						$col_str = 'col_id_' . $atomVal['atomdefinition_id'];
						$output_array[$field_str][$row_str]['values'][$col_str] = $atomVal['data'];
					}
				}
			} else {
				$field_str = 'field_id_' . $bloqsFieldVal['field_id'];
				$output_array[$field_str] = array();
			}
		}

		return $output_array;
	}

	public function formatAnselBloqs($output_array, $ansel_bloqs_fields, $entry_id) {
		foreach($ansel_bloqs_fields as $abKey => $abVal) {
			$field_str = 'field_id_' . $abVal['field_id'];
			$col_str = 'col_id_' . $abVal['col_id'];
			$bloqs_images = $this->anselPrepImages($entry_id, $abVal['field_id']);

			foreach($bloqs_images as $imageKey => $imageVal) {
				$bloqs_images[$imageKey]['ansel_image_id'] = "";
				$bloqs_images[$imageKey]['source_file_id'] = $bloqs_images[$imageKey]['original_file_id'];
				unset($bloqs_images[$imageKey]['original_file_id']);
				$bloqs_images[$imageKey]['order'] = $bloqs_images[$imageKey]['position'];
				unset($bloqs_images[$imageKey]['position']);
			}

			foreach($output_array[$field_str] as $dataKey => $dataVal) {
				foreach($output_array[$field_str][$dataKey]['values'] as $blocksRowKey => $blocksRowVal) {
					// loop over ansel fields with col id values passing the key into the values array. if array key exists, set it.
					$data_block_id = $output_array[$field_str][$dataKey]['values']['block_id'];

					foreach($bloqs_images as $iK => $iV) {
						if($data_block_id == $iV['row_id'] && $abVal['col_id'] == $iV['col_id']) {
							$output_array[$field_str][$dataKey]['values'][$col_str] = array();
							$new_id = 'ansel_row_id_' . uniqid();
							$output_array[$field_str][$dataKey]['values'][$col_str][$new_id] = $bloqs_images[$iK];
							unset($output_array[$field_str][$dataKey]['values'][$col_str][$new_id]['row_id']);
							unset($output_array[$field_str][$dataKey]['values'][$col_str][$new_id]['col_id']);
						}
					}
				}
			}
		}

		return $output_array;
	}

	public function fetchGridEntry($field_id, $entry_id) {
		$table_id = "channel_grid_field_" . $field_id;

		$grid_entry = ee()->db->select('*')
							  ->from($table_id)
							   ->where('entry_id', $entry_id)
							  ->get()
							  ->result_array();
		return $grid_entry;
	}

	public function fetchAnselGridData($entry_id) {
		$ansel_grid_fields = ee()->db->select("field_id, content_type, col_id")
									 ->from("ansel_images")
									 ->where("content_type", "grid")
									 ->where("content_id", $entry_id)
									 ->get()
									 ->result_array();
		return $ansel_grid_fields;
	}

	public function fetchRelationshipGridData($entry_id) {
		$relationship_grid_fields = ee()->db->select()
											->from("relationships")
											->where("parent_id", $entry_id)
											->where("grid_field_id !=", "0")
											->get()
											->result_array();
		
		return $relationship_grid_fields;
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
		$entry = $this->getOriginalEntry($data['entry_id']);

		// Check that row has been assigned and that the entry cloning flag has been checked.
		if($entry == NULL || $entry['clone_entry'] == 0) {
			return false;
		}

		// Format date for database re-entry.
		if(! is_string($data['recent_comment_date'])) {
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

			if($entry['cloned_entry_status'] == null) {
				$channel_default = ee()->db->select('deft_status')
										   ->from('channels')
										   ->where('channel_id', $data['channel_id'])
										   ->get();
				$channel_default = (array)$channel_default->row();
				$data['status'] = $channel_default['deft_status'];
			} else {
				$status_group = ee()->db->select('status_group')
										->from('channels')
										->where('channel_id', $data['channel_id'])
										->get()
										->result_array();

				$statuses = ee()->db->select('status')
									->from('statuses')
									->where('group_id', $status_group[0]['status_group'])
									->get()
									->result_array();

				$status = (int)$entry['cloned_entry_status'] - 1;
				$data['status'] = $statuses[$status]['status'];
			}

			if(strlen($entry['title_suffix']) > 0) {
				$data['title'] = $data['title'] . ' ' . $entry['title_suffix'];
			} elseif (strlen($settings_query['title_suffix']) > 0) {
				$data['title'] = $data['title'] . ' ' . $settings_query['title_suffix'];
			}

			if(strlen($entry['url_title_suffix']) > 0) {
				$urlString = $data['url_title'] . '-' . $entry['url_title_suffix'];
				$data['url_title'] = $this->strToUrl($urlString);
			} elseif (strlen($settings_query['url_title_suffix']) > 0) {
				$urlString = $data['url_title'] . '-' . $settings_query['url_title_suffix'];
				$data['url_title'] = $this->strToUrl($urlString);
			}

			if($entry['update_entry_time'] == '1' || $settings_query['update_entry_time'] == 'y') {
				$data['entry_date'] = time();
				$data['year'] = date('Y', time());
				$data['month'] = date('m', time());
				$data['day'] = date('d', time());
			}
		}

		$ansel = ee('Addon')->get('ansel');

		if($ansel && $ansel->isInstalled()) {
			// Get Ansel fields
			$ansel_fields = ee()->db->select("field_id, field_type, group_id")
								    ->from("channel_fields")
								    ->where("field_type", "ansel")
								    ->get()
								    ->result_array();

			$channel_field_group = ee()->db->select("field_group")
										   ->from("channels")
										   ->where("channel_id", $data["channel_id"])
										   ->get();
			$channel_field_group = (array)$channel_field_group->row();
			
			foreach($ansel_fields as $afKey => $afVal) {
				if($afVal['group_id'] != $channel_field_group['field_group']) {
					unset($ansel_fields[$afKey]);
					continue;
				}

				if(!$data['field_id_' . $afVal['field_id']]) {
					unset($ansel_fields[$afKey]);
				}
			}
		}

		if(sizeOf($ansel_fields) > 0) {
			$ansel_field_array = array();

			foreach($ansel_fields as $anselKey => $anselVal) {
				$images = $this->anselPrepImages($data['entry_id'], $anselVal['field_id'], $anselVal['field_type']);

				foreach($images as $imageKey => $imageVal) {
					$images[$imageKey]['ansel_image_id'] = "";
					$images[$imageKey]['source_file_id'] = $images[$imageKey]['original_file_id'];
					unset($images[$imageKey]['original_file_id']);
					$images[$imageKey]['order'] = $images[$imageKey]['position'];
					unset($images[$imageKey]['position']);
					$images['ansel_row_id_' . uniqid()] = $images[$imageKey];
					unset($images[$imageKey]);
				}

				$ansel_field_array['field_id_' . $anselVal['field_id']] = $images;
			}
		}

		// Assign meta settings for new cloned entry and save entry to database.
		$data_template = array(
			'entry_id' 				  => 0,
			'site_id' 				  => $data['site_id'],
			'channel_id' 			  => $data['channel_id'],
			'author_id' 			  => $data['author_id'],
			'forum_topic_id' 		  => $data['forum_topic_id'],
			'ip_address' 			  => $data['ip_address'],
			'title' 				  => $data['title'],
			'url_title' 			  => $data['url_title'],
			'status' 				  => $data['status'],
			'versioning_enabled' 	  => $data['versioning_enabled'],
			'view_count_one' 		  => $data['view_count_one'],
			'view_count_two' 		  => $data['view_count_two'],
			'view_count_three' 		  => $data['view_count_three'],
			'view_count_four' 		  => $data['view_count_four'],
			'allow_comments' 		  => $data['allow_comments'],
			'entry_date' 			  => $data['entry_date'],
			'edit_date' 			  => $data['edit_date'],
			'year' 					  => $data['year'],
			'month' 				  => $data['month'],
			'day' 					  => $data['day'],
			'expiration_date' 		  => $data['expiration_date'],
			'comment_expiration_date' => $data['comment_expiration_date'],
			'sticky' 				  => $data['sticky'],
		);

		$custom_fields = array();

		// Create custom fields
		foreach ($data as $key => $value) {
			if(strpos($key, 'field_id') !== FALSE) {
				// Get field ID number to query exp_channel_fields table.
				$get_field_id = explode('field_id_', $key);
				$field_id = $get_field_id[1];
				array_push($custom_fields, $field_id);
			}
		}

		if(! empty($custom_fields)) {
			$prep_custom = ee()->db->select('field_id, field_name, field_type')
								->from('channel_fields')
								->where_in('field_id', $custom_fields)
								->get()
								->result_array();

			foreach($prep_custom as $customKey => $customValue) {
				$str_id = 'field_id_' . $customValue['field_id'];
				$str_ft = 'field_ft_' . $customValue['field_id'];
				$str_name = $customValue['field_name'];

				if($customValue['field_type'] == 'grid') {
					$grid_field = $this->fetchDataByFieldtype('grid', null, true, $customValue['field_id']);
					$data_template = $this->formatGridFields($data_template, $grid_field, $data['entry_id']);
					
					if($ansel && $ansel->isInstalled()) {
						$ansel_grid_fields = $this->fetchAnselGridData($data['entry_id']);
					}

					if(sizeOf($ansel_grid_fields) > 0) {
						$data_template = $this->formatAnselGrid($data_template, $ansel_grid_fields, $data['entry_id']);
					}

					$grid_relationship_fields = $this->fetchRelationshipGridData($data['entry_id']);
					
					if(sizeOf($grid_relationship_fields) > 0) {
						$data_template = $this->formatRelationshipGrid($data_template, $grid_relationship_fields, $data['entry_id']);
					}
					
					unset($data[$str_id]);
				} else if($customValue['field_type'] == 'relationship') {
					$relationship_fields = $this->fetchDataByFieldtype('relationship', null, true, $customValue['field_id']);
					$data_template = $this->formatRelationshipFields($data_template, $relationship_fields, $data['entry_id']);
					
					unset($data[$str_id]);
				} else if($customValue['field_type'] == 'bloqs') {
					$bloqs_fields = $this->fetchDataByFieldtype('bloqs', null, true, $customValue['field_id']);

					if(sizeOf($bloqs_fields) > 0) {
						$data_template = $this->formatBloqsFields($data_template, $bloqs_fields, $data['entry_id']);
					}

					if($ansel && $ansel->isInstalled()) {
						// Get Ansel columns inside Grid fields
						$ansel_bloqs_fields = ee()->db->select("field_id, content_type, col_id, row_id")
														->from("ansel_images")
														->where("content_type", "blocks")
														->where("content_id", $data['entry_id'])
														->get()
														->result_array();
					}

					if(sizeOf($ansel_bloqs_fields) > 0) {
						$data_template = $this->formatAnselBloqs($data_template, $ansel_bloqs_fields, $data['entry_id']);
					}

					unset($data[$str_id]);
				} else {
					$data_template[$str_id] = $data[$str_id];
				}
			}
		}

		$entry_categories = ee()->db->select('cat_id')
									->from('category_posts')
									->where('entry_id', $data['entry_id'])
									->get()
									->result_array();

		$data_template['category'] = array_column($entry_categories, 'cat_id');
		
		ee()->api_channel_fields->setup_entry_settings($data['channel_id'], $data_template);

		foreach($ansel_fields as $ansel_key => $ansel_value) {
			unset($data_template['field_id_' . $ansel_fields[$ansel_key]['field_id']]);
			unset($data['field_id_' . $ansel_fields[$ansel_key]['field_id']]);
			$data_template['field_id_' . $ansel_fields[$ansel_key]['field_id']] = $ansel_field_array['field_id_' . $ansel_fields[$ansel_key]['field_id']];
		}

		if (! ee()->api_channel_entries->save_entry($data_template, $data['channel_id'])) {
			$errors = ee()->api_channel_entries->get_errors();
			print_r($errors);
		}
		
		$original = $data['entry_id'];
		
		ee()->db->update(
			'exp_simple_cloner_content',
			array('clone_entry'	=>	'0'),
			array('entry_id'	=>	$original)
		);
	}
}
