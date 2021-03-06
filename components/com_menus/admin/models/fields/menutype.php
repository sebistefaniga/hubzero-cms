<?php
/**
 * @copyright	Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

JFormHelper::loadFieldClass('list');

/**
 * Form Field class for the Joomla Framework.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_menus
 * @since		1.6
 */
class JFormFieldMenutype extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'menutype';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
	protected function getInput()
	{
		// Initialise variables.
		$html 		= array();
		$recordId	= (int) $this->form->getValue('id');
		$size		= ($v = $this->element['size']) ? ' size="'.$v.'"' : '';
		$class		= ($v = $this->element['class']) ? ' class="'.$v.'"' : 'class="text_area"';

		// Get a reverse lookup of the base link URL to Title
		$model 	= JModelLegacy::getInstance('menutypes', 'menusModel');
		$rlu 	= $model->getReverseLookup();

		switch ($this->value)
		{
			case 'url':
				$value = Lang::txt('COM_MENUS_TYPE_EXTERNAL_URL');
				break;

			case 'alias':
				$value = Lang::txt('COM_MENUS_TYPE_ALIAS');
				break;

			case 'separator':
				$value = Lang::txt('COM_MENUS_TYPE_SEPARATOR');
				break;

			default:
				$link	= $this->form->getValue('link');
				// Clean the link back to the option, view and layout
				$value	= Lang::txt(JArrayHelper::getValue($rlu, MenusHelper::getLinkKey($link)));
				break;
		}
		// Load the javascript and css
		JHtml::_('behavior.framework');
		JHtml::_('behavior.modal');

		JFactory::getDocument()->addScriptDeclaration("
			jQuery(document).ready(function($){
				$('input.modal').fancybox({
					arrows: false,
					type: 'iframe',
					autoSize: false,
					fitToView: false,
					width: 600,
					height: 450,
					href: '" . Route::url('index.php?option=com_menus&view=menutypes&tmpl=component&recordId='.$recordId, false) . "'
				});
			});
		");

		$html[] = '<div class="input-modal">';
		$html[] = '<span class="input-cell">';
		$html[] = '<input type="text" id="'.$this->id.'" readonly="readonly" disabled="disabled" value="'.$value.'"'.$size.$class.' />';
		$html[] = '</span><span class="input-cell">';
		$html[] = '<input type="button" class="modal" value="'.Lang::txt('JSELECT').'" />';
		$html[] = '<input type="hidden" name="'.$this->name.'" value="'.htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8').'" />';
		$html[] = '</span>';
		$html[] = '</div>';

		return implode("\n", $html);
	}
}
