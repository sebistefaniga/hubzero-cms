<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Components\System\Models;

/**
 * Model class for getting system information
 */
class Info extends \JModelLegacy
{
	/**
	 * @var  array  some php settings
	 */
	protected $php_settings = null;

	/**
	 * @var  array config values
	 */
	protected $config = null;

	/**
	 * @var  array  somme system values
	 */
	protected $info = null;

	/**
	 * @var  string  php info
	 */
	protected $php_info = null;

	/**
	 * @var  array  informations about writable state of directories
	 */
	protected $directories = null;

	/**
	 * @var  string  The current editor.
	 */
	protected $editor = null;

	/**
	 * Method to get the ChangeLog
	 *
	 * @return  array  some php settings
	 */
	public function getPhpSettings()
	{
		if (is_null($this->php_settings))
		{
			$this->php_settings = array();
			$this->php_settings['safe_mode']          = ini_get('safe_mode') == '1';
			$this->php_settings['display_errors']     = ini_get('display_errors') == '1';
			$this->php_settings['short_open_tag']     = ini_get('short_open_tag') == '1';
			$this->php_settings['file_uploads']       = ini_get('file_uploads') == '1';
			$this->php_settings['magic_quotes_gpc']   = ini_get('magic_quotes_gpc') == '1';
			$this->php_settings['register_globals']   = ini_get('register_globals') == '1';
			$this->php_settings['output_buffering']   = (bool) ini_get('output_buffering');
			$this->php_settings['open_basedir']       = ini_get('open_basedir');
			$this->php_settings['session.save_path']  = ini_get('session.save_path');
			$this->php_settings['session.auto_start'] = ini_get('session.auto_start');
			$this->php_settings['disable_functions']  = ini_get('disable_functions');
			$this->php_settings['xml']                = extension_loaded('xml');
			$this->php_settings['zlib']               = extension_loaded('zlib');
			$this->php_settings['zip']                = function_exists('zip_open') && function_exists('zip_read');
			$this->php_settings['mbstring']           = extension_loaded('mbstring');
			$this->php_settings['iconv']              = function_exists('iconv');
		}
		return $this->php_settings;
	}

	/**
	 * Method to get the config
	 *
	 * @return  array  Config values
	 */
	public function getConfig()
	{
		if (is_null($this->config))
		{
			$registry = new \JRegistry(Config::getRoot());
			$this->config = $registry->toArray();

			foreach (array('host', 'user', 'password', 'ftp_user', 'ftp_pass', 'smtpuser', 'smtppass') as $key)
			{
				$this->config[$key] = 'xxxxxx';
			}
		}
		return $this->config;
	}

	/**
	 * Method to get the system information
	 *
	 * @return  array  System information values
	 */
	public function getInfo()
	{
		if (is_null($this->info))
		{
			$version  = new \JVersion();
			$platform = new \JPlatform();
			$db       = \JFactory::getDBO();

			if (isset($_SERVER['SERVER_SOFTWARE']))
			{
				$sf = $_SERVER['SERVER_SOFTWARE'];
			}
			else
			{
				$sf = getenv('SERVER_SOFTWARE');
			}

			$this->info = array();
			$this->info['php']         = php_uname();
			$this->info['dbversion']   = $db->getVersion();
			$this->info['dbcollation'] = $db->getCollation();
			$this->info['phpversion']  = phpversion();
			$this->info['server']      = $sf;
			$this->info['sapi_name']   = php_sapi_name();
			$this->info['version']     = $version->getLongVersion();
			$this->info['platform']    = $platform->getLongVersion();
			$this->info['useragent']   = $_SERVER['HTTP_USER_AGENT'];
		}
		return $this->info;
	}

	/**
	 * Method to get the PHP info
	 *
	 * @return  string  PHP info
	 */
	public function getPHPInfo()
	{
		if (is_null($this->php_info))
		{
			ob_start();
			date_default_timezone_set('UTC');
			phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
			$phpinfo = ob_get_contents();
			ob_end_clean();

			preg_match_all('#<body[^>]*>(.*)</body>#siU', $phpinfo, $output);
			$output = preg_replace('#<table[^>]*>#', '<table class="adminlist">', $output[1][0]);
			$output = preg_replace('#(\w),(\w)#', '\1, \2', $output);
			$output = preg_replace('#<hr />#', '', $output);
			$output = str_replace('<div class="center">', '', $output);
			$output = preg_replace('#<tr class="h">(.*)<\/tr>#', '<thead><tr class="h">$1</tr></thead><tbody>', $output);
			$output = str_replace('</table>', '</tbody></table>', $output);
			$output = str_replace('</div>', '', $output);

			$this->php_info = $output;
		}
		return $this->php_info;
	}

	/**
	 * Method to get the directory states
	 *
	 * @return  array  states of directories
	 */
	public function getDirectory()
	{
		if (is_null($this->directories))
		{
			$this->directories = array();

			jimport('joomla.filesystem.folder');
			$cparams = \Component::params('com_media');

			$this->_addDirectory('administrator/components', JPATH_ADMINISTRATOR . '/components');
			$this->_addDirectory('administrator/language', JPATH_ADMINISTRATOR . '/language');

			// List all admin languages
			$admin_langs = \JFolder::folders(JPATH_ADMINISTRATOR . '/language');
			foreach ($admin_langs as $alang)
			{
				$this->_addDirectory('administrator/language/' . $alang, JPATH_ADMINISTRATOR . '/language/' . $alang);
			}

			// List all manifests folders
			$manifests = \JFolder::folders(JPATH_ADMINISTRATOR . '/manifests');
			foreach ($manifests as $_manifest)
			{
				$this->_addDirectory('administrator/manifests/' . $_manifest, JPATH_ADMINISTRATOR . '/manifests/' . $_manifest);
			}

			$this->_addDirectory('administrator/modules', JPATH_ADMINISTRATOR . '/modules');
			$this->_addDirectory('administrator/templates', JPATH_THEMES);

			$this->_addDirectory('components', JPATH_SITE . '/components');

			$this->_addDirectory($cparams->get('image_path'), JPATH_SITE . '/' . $cparams->get('image_path'));

			$image_folders = \JFolder::folders(JPATH_SITE . '/' . $cparams->get('image_path'));
			// List all images folders
			foreach ($image_folders as $folder)
			{
				$this->_addDirectory('images/' . $folder, JPATH_SITE . '/' . $cparams->get('image_path') . '/' . $folder);
			}

			$this->_addDirectory('language', JPATH_SITE . '/language');
			// List all site languages
			$site_langs = \JFolder::folders(JPATH_SITE . '/language');
			foreach ($site_langs as $slang)
			{
				$this->_addDirectory('language/' . $slang, JPATH_SITE . '/language/' . $slang);
			}

			$this->_addDirectory('libraries', JPATH_LIBRARIES);

			$this->_addDirectory('media', JPATH_SITE . '/media');
			$this->_addDirectory('modules', JPATH_SITE . '/modules');
			$this->_addDirectory('plugins', JPATH_PLUGINS);

			$plugin_groups = \JFolder::folders(JPATH_PLUGINS);
			foreach ($plugin_groups as $folder)
			{
				$this->_addDirectory('plugins/' . $folder, JPATH_PLUGINS . '/' . $folder);
			}

			$this->_addDirectory('templates', JPATH_SITE . '/templates');
			$this->_addDirectory('configuration.php', JPATH_CONFIGURATION . '/configuration.php');
			$this->_addDirectory('cache', JPATH_SITE . '/cache', 'COM_SYSTEM_INFO_CACHE_DIRECTORY');
			$this->_addDirectory('administrator/cache', JPATH_CACHE, 'COM_SYSTEM_INFO_CACHE_DIRECTORY');

			$this->_addDirectory(Config::get('log_path', PATH_APP . '/log'), Config::get('log_path', PATH_APP . '/log'), 'COM_SYSTEM_INFO_LOG_DIRECTORY');
			$this->_addDirectory(Config::get('tmp_path', PATH_APP . '/tmp'), Config::get('tmp_path', PATH_APP . '/tmp'), 'COM_SYSTEM_INFO_TEMP_DIRECTORY');
		}
		return $this->directories;
	}

	/**
	 * Add a directory to the list
	 *
	 * @param   string  $name
	 * @param   string  $path
	 * @param   string  $message
	 * @return  void
	 */
	private function _addDirectory($name, $path, $message = '')
	{
		$this->directories[$name] = array(
			'writable' => is_writable($path),
			'message'  => $message
		);
	}

	/**
	 * Method to get the editor
	 * has to be removed (it is present in the config...)
	 *
	 * @return  string  The default editor
	 */
	public function getEditor()
	{
		if (is_null($this->editor))
		{
			$this->editor = \Config::get('editor');
		}
		return $this->editor;
	}
}
