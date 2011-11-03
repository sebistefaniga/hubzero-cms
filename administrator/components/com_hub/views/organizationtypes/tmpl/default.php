<?php
/**
 * @package     hubzero-cms
 * @copyright   Copyright 2005-2011 Purdue University. All rights reserved.
 * @license     http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
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
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );
JToolBarHelper::title( JText::_( 'HUB Configuration' ).': <small><small>[ '. JText::_('Employment Types').' ]</small></small>', 'user.png' );
JToolBarHelper::addNew('addorgtype');
JToolBarHelper::editList('editorgtype');
JToolBarHelper::deleteList('Remove employment type?','removeorgtype');

?>
<script type="text/javascript">
function submitbutton(pressbutton) 
{
	var form = document.getElementById('adminForm');
	if (pressbutton == 'cancel') {
		submitform( pressbutton );
		return;
	}
	// do field validation
	submitform( pressbutton );
}
</script>

<form action="index.php" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter">
		<label>
			<?php echo JText::_('SEARCH'); ?>: 
			<input type="text" name="search" value="<?php echo $this->filters['search']; ?>" />
		</label>

		<input type="submit" value="<?php echo JText::_('Go'); ?>" />
	</fieldset>

	<table class="adminlist" summary="<?php echo JText::_('Organization Types'); ?>">
		<thead>
			<tr>
				<th><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->rows );?>);" /></th>
				<th><?php echo JText::_('ID'); ?></th>
				<th><?php echo JText::_('Type'); ?></th>
				<th><?php echo JText::_('Title'); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="4">
					<?php echo $this->pageNav->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
<?php
$k = 0;
for ($i=0, $n=count($this->rows); $i < $n; $i++)
{
	$row = &$this->rows[$i];
?>
			<tr class="<?php echo "row$k"; ?>">
				<td><input type="checkbox" name="id[]" id="cb<?php echo $i;?>" value="<?php echo $row->id; ?>" onclick="isChecked(this.checked);" /></td>
				<td><?php echo $row->id; ?></td>
				<td><a href="index.php?option=<?php echo $this->option ?>&amp;task=editorgtype&amp;id[]=<? echo $row->id; ?>"><?php echo stripslashes($row->type); ?></a></td>
				<td><a href="index.php?option=<?php echo $this->option ?>&amp;task=editorgtype&amp;id[]=<? echo $row->id; ?>"><?php echo stripslashes($row->title); ?></a></td>
			</tr>
<?php
	$k = 1 - $k;
}
?>
		</tbody>
	</table>

	<input type="hidden" name="option" value="<?php echo $this->option ?>" />
	<input type="hidden" name="task" value="orgs" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
