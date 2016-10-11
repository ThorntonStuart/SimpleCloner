<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Simple Cloner Settings Library class
 *
 * @package        simple_cloner
 * @author         Stuart Thornton <sthornton@knightknox.com>
 * @link           http://www.github.com/ThorntonStuart
 * @copyright      Copyright (c) 2016, Stuart Thornton
 */

class Simple_cloner_settings {

	// Initialize settings and default settings arrays.
	var $_settings = array();

	var $_default_settings = array(
		'title_suffix'	=>	'',
		'url_title_suffix'	=>	'',	
		'update_entry_time'	=>	'y'
	);

	/**
	 * Set value of settings array to defaults for extension constructor.
	 *
	 * @access 	public
	 * @param 	array
	 * @return 	void
	 */
	public function set($settings)
	{
		$this->_settings = array_merge($this->_default_settings, $settings);
	}

	/**
	 * Get current settings.
	 *
	 * @access 	public
	 * @param 	null
	 * @return 	string
	 */
	public function get($key = NULL)
	{
		// If val is empty, retrieve settings from extensions table and set val from array.
		if(empty($this->_settings))
		{
			$query = ee()->db->select('settings')
						->from('extensions')
						->where('class', 'Simple_cloner_ext')
						->limit(1)
						->get();
			
			$this->_settings = (array) @unserialize($query->row('settings'));
		}
		
		// Set _settings to defaults if no values given.
		$this->_settings = array_merge($this->_default_settings, $this->_settings);

		// If key is still null, return settings array and check if key is set. If so, return key, if not return NULL.
		return is_null($key) ? $this->_settings : (isset($this->_settings[$key]) ? $this->_settings[$key] : NULL);
	}
}