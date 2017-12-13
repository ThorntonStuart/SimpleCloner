<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'simple_cloner/config.php';

/**
 * Simple Cloner Module Control Panel class
 *
 * @package        simple_cloner
 * @author         Stuart Thornton <sthornton@knightknox.com>
 * @link           http://www.github.com/ThorntonStuart
 * @copyright      Copyright (c) 2016, Stuart Thornton
 */

class Simple_cloner_mcp {

	var $vars = array();
	var $class_name		=	SIMPLE_CLONER_NAME_FORMATTED;

	/**
	 * Constructor
	 *
	 * @access 	public
	 * @return 	void
	 */
	public function __construct()
	{
		// Load settings library.
		ee()->load->library('simple_cloner_settings');
	}

	/**
	 * Build settings index page.
	 *
	 * @access 	public
	 * @return 	string
	 */
	public function index()
	{
		// Create settings inputs and set current value to database stored value or default by calling get() method from settings library.
		$vars['sections'] = array(
			array(
				array(
					'title'		=>	'title_suffix',
					'desc'		=>	'title_suffix',
					'fields'	=>	array(
						'title_suffix'	=>	array(
							'type'	=>	'text',
							'value'	=>	ee()->simple_cloner_settings->get('title_suffix')
						)
					)
				),
				array(
					'title'		=>	'url_title_suffix',
					'desc'		=>	'url_title_suffix',
					'fields'	=>	array(
						'url_title_suffix'	=>	array(
							'type'	=>	'text',
							'value'	=>	ee()->simple_cloner_settings->get('url_title_suffix')
						)
					)
				),
				array(
					'title'		=>	'update_entry_time',
					'desc'		=>	'update_entry_time',
					'fields'	=>	array(
						'update_entry_time'	=>	array(
							'type'	=>	'yes_no',
							'value'	=>	ee()->simple_cloner_settings->get('update_entry_time')
						)
					)
				)
			)
		);

		// Set values for components on settings page and return rendered view.
		$vars += array(
			'base_url'	=>	ee('CP/URL', 'addons/settings/simple_cloner/save_settings'),
			'cp_page_title'	=>	lang('general_settings'),
			'save_btn_text'	=>	'btn_save_settings',
			'save_btn_text_working'	=>	'btn_saving'
		);

		return ee('View')->make('simple_cloner:index')->render($vars);
	}

	/**
	 * Save settings button method.
	 *
	 * @access 	public
	 * @return 	void
	 */
	public function save_settings()
	{
		// Create settings array.
		$settings = array();

		// Loop through default settings and check for saved values.
		foreach (ee()->simple_cloner_settings->_default_settings as $key => $value)
		{
			if(($settings[$key] = ee()->input->post($key)) == FALSE)
			{
				$settings[$key] = $value;
			}
		}

		// Serialize settings array and update the extensions table.
		ee()->db->where('class', $this->class_name.'_ext');
		ee()->db->update('extensions', array('settings' => serialize($settings)));

		// Create alert when settings are saved and redirect back to settings page.
		ee('CP/Alert')->makeInline('simple-cloner-save')
			->asSuccess()
			->withTitle(lang('message_success'))
			->addToBody(lang('preferences_updated'))
			->defer();

		ee()->functions->redirect(ee('CP/URL')->make('addons/settings/simple_cloner'));
	}
}
