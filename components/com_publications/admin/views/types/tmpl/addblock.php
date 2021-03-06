<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
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
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

JToolBarHelper::title(Lang::txt('COM_PUBLICATIONS_PUBLICATION') . ' ' . Lang::txt('COM_PUBLICATIONS_MASTER_TYPE') . ' - ' . $this->row->type . ': [ ' . Lang::txt('COM_PUBLICATIONS_FIELD_CURATION_ADD_BLOCK') . ' ]', 'addedit.png');
JToolBarHelper::save('saveblock');
JToolBarHelper::cancel();

$params = new JRegistry($this->row->params);
$manifest  = $this->curation->_manifest;
$curParams = $manifest->params;
$blocks	   = $manifest->blocks;

$blockSelection = array('active' => array());
$masterBlocks = array();
foreach ($this->blocks as $b)
{
	$masterBlocks[$b->block] = $b;
}
foreach ($blocks as $sequence => $block)
{
	$blockSelection['active'][] = $block->name;
}

?>
<script type="text/javascript">
function submitbutton(pressbutton)
{
	submitform( pressbutton );
	return;
}
</script>
<p class="backto"><a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=edit&id[]=' . $this->row->id ); ?>"><?php echo Lang::txt('COM_PUBLICATIONS_MTYPE_BACK') . ' ' . $this->row->type . ' ' . Lang::txt('COM_PUBLICATIONS_MASTER_TYPE'); ?></a></p>

<form action="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller); ?>" method="post" id="item-form" name="adminForm">
		<fieldset class="adminform">
			<input type="hidden" name="id" value="<?php echo $this->row->id; ?>" />
			<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
			<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
			<input type="hidden" name="task" value="saveblock" />
			<legend><span><?php echo Lang::txt('COM_PUBLICATIONS_FIELD_CURATION_ADD_BLOCK'); ?></span></legend>
			<fieldset class="adminform">
				<legend><span><?php echo Lang::txt('COM_PUBLICATIONS_FIELD_CURATION_ADD_BLOCK'); ?></span></legend>
				<div class="input-wrap">
					<label for="field-newblock"><?php echo Lang::txt('COM_PUBLICATIONS_CURATION_SELECT_BLOCK'); ?>:</label>
					<select name="newblock" id="field-newblock">
					<?php foreach ($this->blocks as $sBlock) {
						if (!in_array($sBlock->block, $blockSelection['active']) || $sBlock->maximum > 1) {  ?>
						<option value="<?php echo $sBlock->block; ?>"><?php echo $sBlock->block; ?></option>
					<?php  }
					} ?>
					</select>
				</div>
				<div class="input-wrap">
					<label for="field-order"><?php echo Lang::txt('COM_PUBLICATIONS_CURATION_INSERT_BLOCK_BEFORE'); ?>:</label>
					<select name="before" id="field-order">
					<?php foreach ($blocks as $sequence => $block) { ?>
						<option value="<?php echo $sequence; ?>"><?php echo $block->name; ?></option>
					<?php  } ?>
					</select>
				</div>
			</fieldset>
		</fieldset>
	<?php echo JHTML::_('form.token'); ?>
</form>