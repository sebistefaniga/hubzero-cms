<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_installer
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License, see LICENSE.php
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Languages Installer Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  com_installer
 * @since       2.5.7
 */
class InstallerControllerLanguages extends JControllerLegacy
{

	/**
	 * Finds new Languages.
	 *
	 * @return  void
	 */
	public function find()
	{
		// Check for request forgeries
		Request::checkToken() or jexit(Lang::txt('JINVALID_TOKEN'));

		// Get the caching duration
		jimport('joomla.application.component.helper');
		$component = Component::load('com_installer');
		$params = $component->params;
		$cache_timeout = $params->get('cachetimeout', 6, 'int');
		$cache_timeout = 3600 * $cache_timeout;

		// Find updates
		$model = $this->getModel('languages');
		$model->findLanguages($cache_timeout);

		$this->setRedirect(Route::url('index.php?option=com_installer&view=languages', false));

	}

	/**
	 * Purgue the updates list.
	 *
	 * @return  void
	 */
	public function purge()
	{
		// Check for request forgeries
		Request::checkToken() or jexit(Lang::txt('JINVALID_TOKEN'));

		// Purge updates
		$model = $this->getModel('update');
		$model->purge();
		$model->enableSites();
		$this->setRedirect(Route::url('index.php?option=com_installer&view=languages', false), $model->_message);
	}

	/**
	 * Install languages.
	 *
	 * @return void
	 */
	public function install()
	{
		$model = $this->getModel('languages');

		// Get array of selected languages
		$lids = Request::getVar('cid', array(), '', 'array');
		JArrayHelper::toInteger($lids, array());

		if (!$lids)
		{
			// No languages have been selected
			$app = JFactory::getApplication();
			$app->enqueueMessage(Lang::txt('COM_INSTALLER_MSG_DISCOVER_NOEXTENSIONSELECTED'));
		}
		else
		{
			// Install selected languages
			$model->install($lids);
		}

		$this->setRedirect(Route::url('index.php?option=com_installer&view=languages', false));
	}
}
