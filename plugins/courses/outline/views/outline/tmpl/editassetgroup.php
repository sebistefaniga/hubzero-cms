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
 * @author    Sam Wilson <samwilson@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

$ag = new CoursesModelAssetgroup($this->scope_id);

?>

<div class="edit-assetgroup">
	<form action="<?php echo Request::base(true); ?>/api/courses/assetgroup/save" method="POST" class="edit-form">

		<p>
			<label for="title">Title:</label>
			<input type="text" name="title" value="<?php echo $ag->get('title') ?>" placeholder="Asset Group Title" />
		</p>
		<p>
			<label for="state">Published:</label>
			<select name="state">
				<option value="0"<?php if ($ag->get('state') == 0) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('JNo'); ?></option>
				<option value="1"<?php if ($ag->get('state') == 1) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('JYes'); ?></option>
			</select>
		</p>

<?php
	if ($plugins = Event::trigger('courses.onAssetgroupEdit'))
	{
		$data = $ag->get('params');

		foreach ($plugins as $plugin)
		{
			$p = Plugin::byType('courses', $plugin['name']);
			$default = new JRegistry($p->params);

			$param = new JParameter(
				(is_object($data) ? $data->toString() : $data),
				PATH_CORE . DS . 'plugins' . DS . 'courses' . DS . $plugin['name'] . DS . $plugin['name'] . '.xml'
			);
			foreach ($default->toArray() as $k => $v)
			{
				if (substr($k, 0, strlen('default_')) == 'default_')
				{
					$param->def(substr($k, strlen('default_')), $default->get($k, $v));
				}
			}
			$out = $param->render('params', 'onAssetgroupEdit');
			if (!$out)
			{
				continue;
			}
			?>
			<fieldset class="eventparams" id="params-<?php echo $plugin['name']; ?>">
				<legend><?php echo Lang::txt('%s Parameters', $plugin['title']); ?></legend>
				<?php echo $out; ?>
			</fieldset>
			<?php
		}
	}
?>

		<input type="hidden" name="course_id" value="<?php echo $this->course->get('id') ?>" />
		<input type="hidden" name="offering" value="<?php echo $this->course->offering()->alias(); ?>" />
		<input type="hidden" name="id" value="<?php echo $ag->get('id') ?>" />

		<input type="submit" value="Submit" class="submit" />
		<input type="button" value="Cancel" class="cancel" />

	</form>
</div>