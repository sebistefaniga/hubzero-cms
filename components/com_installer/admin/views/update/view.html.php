<?php
/**
 * @copyright	Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

include_once dirname(__FILE__).'/../default/view.php';

/**
 * Extension Manager Update View
 *
 * @package		Joomla.Administrator
 * @subpackage	com_installer
 * @since		1.6
 */
class InstallerViewUpdate extends InstallerViewDefault
{
	/**
	 * @since	1.6
	 */
	public function display($tpl=null)
	{
		// Get data from the model
		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');

		$paths = new stdClass();
		$paths->first = '';

		$this->assignRef('paths', $paths);

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		$canDo = InstallerHelper::getActions();

		Toolbar::custom('update.update', 'upload', 'upload', 'COM_INSTALLER_TOOLBAR_UPDATE', true, false);
		Toolbar::custom('update.find', 'refresh', 'refresh', 'COM_INSTALLER_TOOLBAR_FIND_UPDATES', false, false);
		Toolbar::custom('update.purge', 'purge', 'purge', 'JTOOLBAR_PURGE_CACHE', false, false);
		Toolbar::divider();
		parent::addToolbar();
		Toolbar::help('update');
	}
}
