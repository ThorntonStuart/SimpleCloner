<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'simple_cloner/config.php';

/**
 * Simple Cloner Update class
 *
 * @package        simple_cloner
 * @author         Stuart Thornton <sthornton@knightknox.com>
 * @link           http://www.github.com/ThorntonStuart
 * @copyright      Copyright (c) 2016, Stuart Thornton
 */

class Simple_cloner_upd {
	var $version 		=	SIMPLE_CLONER_VER;
	var $class_name		=	SIMPLE_CLONER_NAME_FORMATTED;

	public function __construct()
	{
	}

	/**
	 * Install the module
	 *
	 * @return	bool
	 */
	public function install()
	{
		// Insert data to modules table.
		$data = array(
			'module_name'	=>	'Simple_cloner',
			'module_version'	=>	$this->version,
			'has_cp_backend'	=>	'y',
			'has_publish_fields'	=>	'y'
		);

		ee()->db->insert('modules', $data);

		// Load settings library and DBForge class.
		ee()->load->library('simple_cloner_settings');

		ee()->load->dbforge();

		// Create Simple Cloner Content table and insert table fields.
		$simple_cloner_content_fields = array(
			'simple_cloner_content_id'	=>	array(
				'type'	=>	'int',
				'constraint'	=>	'10',
				'unsigned'	=>	TRUE,
				'auto_increment'	=>	TRUE
			),
			'site_id'	=>	array(
				'type'	=>	'int',
				'constraint'	=>	'10',
				'null'	=>	FALSE
			),
			'entry_id'	=>	array(
				'type'	=>	'int',
				'constraint'	=>	'10',
				'null'	=>	FALSE
			),
			'title_suffix'	=>	array(
				'type'	=>	'varchar',
				'constraint'	=>	'1024'
			),
			'url_title_suffix'	=>	array(
				'type'	=>	'varchar',
				'constraint'	=>	'1024'
			),
			'update_entry_time'	=>	array(
				'type'	=>	'varchar',
				'constraint'	=>	'1024'
			),
			'clone_entry'	=>	array(
				'type'	=>	'varchar',
				'constraint'	=>	'1024',
				'null'	=>	FALSE
			)
		);

		ee()->dbforge->add_field($simple_cloner_content_fields);
		ee()->dbforge->add_key('simple_cloner_content_id', TRUE);
		ee()->dbforge->create_table('simple_cloner_content');

		// Execute hook insertion to set up extension in database.
		$this->_insert_hook();

		// Add Simple Cloner tab to publish form.
		ee()->load->library('layout');
		ee()->layout->add_layout_tabs($this->tabs(), 'simple_cloner');

		return TRUE;
	}

	/**
	 * Update the module
	 *
	 * @return	bool
	 */
	public function update($current = '')
	{
		if($current == $this->version)
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Uninstall the module
	 *
	 * @return	bool
	 */
	public function uninstall()
	{
		// Load DBForge class.
		ee()->load->dbforge();

		// Find module in table and remove row.
		ee()->db->where('module_name', $this->class_name);
		ee()->db->delete('modules');

		// Find extension in table and remove row.
		ee()->db->where('class', $this->class_name.'_ext');
		ee()->db->delete('extensions');

		// Drop Simple Cloner Content table.
		ee()->dbforge->drop_table('simple_cloner_content');

		// Remove Simple Cloner tab from publish layout.
		ee()->load->library('layout');
		ee()->layout->delete_layout_tabs($this->tabs(), 'simple_cloner');

		return TRUE;
	}

	public function tabs()
	{
		$tabs['simple_cloner'] = array(
			'title_suffix'	=>	array(
				'visible'	=>	'true',
				'collapse'	=>	'false',
				'htmlbuttons'	=>	'true',
				'width'	=>	'100%'
			),
			'url_title_suffix'	=>	array(
				'visible'	=>	'true',
				'collapse'	=>	'false',
				'htmlbuttons'	=>	'true',
				'width'	=>	'100%'
			),
			'update_entry_time'	=>	array(
				'visible'	=>	'true',
				'collapse'	=>	'false',
				'htmlbuttons'	=>	'true',
				'width'	=>	'100%'
			),
			'clone_entry'	=>	array(
				'visible'	=>	'true',
				'collapse'	=>	'false',
				'htmlbuttons'	=>	'true',
				'width'	=>	'100%'
			)
		);

		return $tabs;
	}

	/**
	 * Install the extension
	 *
	 */
	private function _insert_hook()
	{
		// Define extension columns and insert to exp_extensions table.
		$data = array(
			'class'		=>	$this->class_name.'_ext',
			'method'	=>	'simple_cloner_content_save',
			'hook'		=>	'after_channel_entry_save',
			'settings'	=>	serialize(ee()->simple_cloner_settings->get()),
			'priority'	=>	10,
			'version'	=>	$this->version,
			'enabled'	=>	'y'
		);

		ee()->db->insert('extensions', $data);
	}
}