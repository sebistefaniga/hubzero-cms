<?php
/**
 * @package		Joomla.Administrator
 * @subpackage	com_templates
 * @copyright	Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

// Initiasile related data.
require_once JPATH_ROOT.'/components/com_menus/admin/helpers/menus.php';
$menuTypes = MenusHelper::getMenuLinks();
?>
		<fieldset class="adminform">
			<legend><?php echo Lang::txt('COM_TEMPLATES_MENUS_ASSIGNMENT'); ?></legend>
			<label id="jform_menuselect-lbl" for="jform_menuselect"><?php echo Lang::txt('JGLOBAL_MENU_SELECTION'); ?></label>

			<button type="button" class="jform-rightbtn" onclick="$('.chk-menulink').each(function(i, el) { el.checked = !el.checked; });">
				<?php echo Lang::txt('JGLOBAL_SELECTION_INVERT'); ?>
			</button>
			<div class="clr"></div>

			<div id="menu-assignment">
				<?php echo JHtml::_('tabs.start', 'module-menu-assignment-tabs', array('useCookie'=>1));?>
			<?php foreach ($menuTypes as &$type) : ?>
				<?php echo JHtml::_('tabs.panel', $type->title ? $type->title : $type->menutype, $type->menutype.'-details'); ?>
				<ul class="menu-links">
					<h3><?php echo $type->title ? $type->title : $type->menutype; ?></h3>
					<?php foreach ($type->links as $link) :?>
					<li class="menu-link">
						<input type="checkbox" name="jform[assigned][]" value="<?php echo (int) $link->value;?>" id="link<?php echo (int) $link->value;?>"<?php if ($link->template_style_id == $this->item->id):?> checked="checked"<?php endif;?><?php if ($link->checked_out && $link->checked_out != User::get('id')):?> disabled="disabled"<?php else:?> class="chk-menulink "<?php endif;?> />
						<label for="link<?php echo (int) $link->value;?>" >
							<?php echo $link->text; ?>
						</label>
					</li>
					<?php endforeach; ?>
				</ul>
				<div class="clr"></div>
			<?php endforeach; ?>
				<?php echo JHtml::_('tabs.end');?>
			</div>
		</fieldset>
