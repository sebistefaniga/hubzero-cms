<?php
/**
 * @package		HUBzero CMS
 * @author		Shawn Rice <zooley@purdue.edu>
 * @copyright	Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906.
 * All rights reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

$exec_pu = $this->config->get('exec_pu', 1);

$execChoices[''] = Lang::txt('COM_TOOLS_SELECT_TOP');
$execChoices['@OPEN'] =  ucfirst(Lang::txt('COM_TOOLS_TOOLACCESS_OPEN'));
$execChoices['@US'] = ucfirst(Lang::txt('COM_TOOLS_TOOLACCESS_US'));
$execChoices['@D1'] = ucfirst(Lang::txt('COM_TOOLS_TOOLACCESS_D1'));
if ($exec_pu)
{
	$execChoices['@PU'] = ucfirst(Lang::txt('COM_TOOLS_TOOLACCESS_PU'));
}
$execChoices['@GROUP'] = ucfirst(Lang::txt('COM_TOOLS_RESTRICTED')).' '.Lang::txt('COM_TOOLS_TO').' '.Lang::txt('COM_TOOLS_GROUP_OR_GROUPS');

$codeChoices[''] = Lang::txt('COM_TOOLS_SELECT_TOP');
$codeChoices['@OPEN'] = ucfirst(Lang::txt('COM_TOOLS_OPEN_SOURCE')). ' ('.Lang::txt('COM_TOOLS_OPEN_SOURCE_TIPS').')';
$codeChoices['@DEV'] = ucfirst(Lang::txt('COM_TOOLS_ACCESS_RESTRICTED'));

$wikiChoices[''] = Lang::txt('COM_TOOLS_SELECT_TOP');
$wikiChoices['@OPEN'] = ucfirst(Lang::txt('COM_TOOLS_ACCESS_OPEN'));
$wikiChoices['@DEV'] = ucfirst(Lang::txt('COM_TOOLS_ACCESS_RESTRICTED'));

$this->css('pipeline.css')
     ->js('pipeline.js');
?>
<header id="content-header">
	<h2><?php echo $this->escape($this->title); ?></h2>

	<div id="content-header-extra">
		<ul id="useroptions">
		<?php if ($this->id) { ?>
			<li><a class="icon-status status btn" href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=status&app=' . $this->defaults['toolname']); ?>"><?php echo Lang::txt('COM_TOOLS_TOOL_STATUS'); ?></a></li>
		<?php } ?>
			<li class="last"><a class="icon-main main-page btn" href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=pipeline'); ?>"><?php echo Lang::txt('COM_TOOLS_CONTRIBTOOL_ALL_TOOLS'); ?></a></li>
		</ul>
	</div><!-- / #content-header-extra -->
</header><!-- / #content-header -->

<?php if ($this->getError()) { ?>
<p class="error"><?php echo implode('<br />', $this->getErrors()); ?></p>
<?php } ?>

<section class="section">
	<div class="subject contracted">
		<form action="<?php echo Route::url('index.php?option=' . $this->option); ?>" method="post" id="hubForm" class="full" enctype="multipart/form-data">
			<fieldset>
				<legend><?php echo Lang::txt('COM_TOOLS_LEGEND_ABOUT'); ?>:</legend>

				<input type="hidden" name="toolid" value="<?php echo $this->id; ?>" />
				<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
				<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
				<input type="hidden" name="task" value="<?php echo ($this->id) ? 'save' : 'register'; ?>" />
				<input type="hidden" name="editversion" value="<?php echo $this->editversion; ?>" />

				<label for="t_toolname">
					<?php echo Lang::txt('COM_TOOLS_TOOLNAME'); ?>:
				<?php if ($this->id) { ?>
					<input type="hidden" name="tool[toolname]" id="t_toolname" value="<?php echo $this->defaults['toolname']; ?>" />
					<strong><?php echo $this->defaults['toolname']; ?> (<?php echo ($this->editversion == 'current') ? Lang::txt('COM_TOOLS_CURRENT_VERSION') : Lang::txt('COM_TOOLS_DEV_VERSION'); ?>)</strong>
					<?php if (isset($this->defaults['published']) && $this->defaults['published']) { ?>
						<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=versions&app='.$this->id); ?>"><?php echo Lang::txt('COM_TOOLS_ALL_VERSIONS'); ?></a>
					<?php } ?>
				<?php } else { ?>
					<span class="required"><?php echo Lang::txt('COM_TOOLS_REQUIRED'); ?></span>
					<input type="text" name="tool[toolname]" id="t_toolname" maxlength = "15" value="<?php echo $this->escape($this->defaults['toolname']); ?>" />
					<p class="hint"><?php echo Lang::txt('COM_TOOLS_HINT_TOOLNAME'); ?></p>
				<?php } ?>
				</label>

				<label for="t_title">
					<?php echo Lang::txt('COM_TOOLS_TITLE') ?>: <span class="required"><?php echo Lang::txt('COM_TOOLS_REQUIRED'); ?></span>
					<input type="text" name="tool[title]" id="t_title" maxlength = "127" value="<?php echo $this->escape(stripslashes($this->defaults['title'])); ?>" />
					<p class="hint"><?php echo Lang::txt('COM_TOOLS_HINT_TITLE'); ?></p>
				</label>

				<label for="t_version">
					<?php echo Lang::txt('COM_TOOLS_VERSION') ?>:
					<?php if ($this->editversion == 'current') { ?>
						<input type="hidden" name="tool[version]" id="t_version" value="<?php echo $this->escape($this->defaults['version']); ?>" />
						<strong><?php echo $this->defaults['version']; ?></strong>
						<p class="hint"><?php echo Lang::txt('COM_TOOLS_HINT_VERSION_PUBLISHED'); ?></p>
					<?php } else { ?>
						<input type="text" name="tool[version]" id="t_version" maxlength="15" value="<?php echo $this->escape($this->defaults['version']); ?>" />
						<p class="hint"><?php echo Lang::txt('COM_TOOLS_HINT_VERSION'); ?></p>
					<?php } ?>
				</label>

				<label for="t_description">
					<?php echo Lang::txt('COM_TOOLS_AT_A_GLANCE') ?>: <span class="required"><?php echo Lang::txt('COM_TOOLS_REQUIRED'); ?></span>
					<input type="text" name="tool[description]" id="t_description" maxlength="256" value="<?php echo $this->escape(stripslashes($this->defaults['description'])); ?>" />
					<p class="hint"><?php echo Lang::txt('COM_TOOLS_HINT_DESCRIPTION'); ?></p>
				</label>
			<?php if ($this->id && isset($this->defaults['resourceid'])) { ?>
				<label>
					<?php echo Lang::txt('COM_TOOLS_DESCRIPTION'); ?>:
					<a href="<?php echo Route::url('index.php?option=com_resources&id=' . $this->defaults['resourceid'] . '&rev=dev'); ?>"><?php echo Lang::txt('COM_TOOLS_PREVIEW') ?></a> |
					<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=resource&app=' . $this->defaults['toolname']); ?>"><?php echo Lang::txt('COM_TOOLS_TODO_EDIT_PAGE') ?>...</a>
				</label>
			<?php } ?>
				<fieldset>
					<legend><?php echo ($this->id) ? Lang::txt('COM_TOOLS_APPLICATION_SCREEN_SIZE'): Lang::txt('COM_TOOLS_SUGGESTED_SCREEN_SIZE')  ?>:</legend>
					<?php echo Lang::txt('COM_TOOLS_MARKER_WIDTH'); ?> <input type="text" class="sameline" name="tool[vncGeometryX]" id="vncGeometryX" size="4" maxlength="4" value="<?php echo $this->defaults['vncGeometryX']; ?>" /> x
					<?php echo Lang::txt('COM_TOOLS_MARKER_HEIGHT'); ?> <input type="text"class="sameline"  name="tool[vncGeometryY]" id="vncGeometryY" size="4" maxlength="4" value="<?php echo $this->defaults['vncGeometryY']; ?>" />
					<p class="hint"><?php echo Lang::txt('COM_TOOLS_HINT_VNC'); ?></p>
				</fieldset>

				<label for="t_exec">
					<?php echo Lang::txt('COM_TOOLS_TOOL_ACCESS'); ?>: <span class="required"><?php echo Lang::txt('COM_TOOLS_REQUIRED'); ?></span>
					<?php echo ToolsHelperHtml::formSelect('tool[exec]', 't_exec', $execChoices, $this->defaults['exec'], 'groupchoices'); ?>
				</label>

				<div id="groupname" <?php echo ($this->defaults['exec']=='@GROUP') ? 'style="display:block"': 'style="display:none"'; ?>>
					<input type="text" name="tool[membergroups]" id="t_groups" value="<?php echo ToolsHelperHtml::getGroups($this->defaults['membergroups'], $this->id); ?>" />
					<p class="hint"><?php echo Lang::txt('COM_TOOLS_HINT_GROUPS'); ?></p>
				</div>

				<label for="t_code">
					<?php echo Lang::txt('COM_TOOLS_CODE_ACCESS'); ?>: <span class="required"><?php echo Lang::txt('COM_TOOLS_REQUIRED'); ?></span>
					<?php echo ToolsHelperHtml::formSelect('tool[code]', 't_code', $codeChoices, $this->defaults['code']); ?>
				</label>

				<label for="t_wiki">
					<?php echo Lang::txt('COM_TOOLS_WIKI_ACCESS'); ?>: <span class="required"><?php echo Lang::txt('COM_TOOLS_REQUIRED'); ?></span>
					<?php echo ToolsHelperHtml::formSelect('tool[wiki]', 't_wiki', $wikiChoices, $this->defaults['wiki']); ?>
				</label>

				<label for="t_team">
					<?php echo Lang::txt('COM_TOOLS_DEVELOPMENT_TEAM'); ?>: <span class="required"><?php echo Lang::txt('COM_TOOLS_REQUIRED'); ?></span>
					<input type="text" name="tool[developers]" id="t_team" value="<?php echo ToolsHelperHtml::getDevTeam($this->defaults['developers'], $this->id);  ?>" />
					<p class="hint"><?php echo Config::get('sitename') . ' ' . Lang::txt('COM_TOOLS_HINT_TEAM'); ?></p>
				</label>

				<p class="submit">
					<input type="submit" value="<?php echo (!$this->id) ? Lang::txt('COM_TOOLS_REGISTER_TOOL') : Lang::txt('COM_TOOLS_SAVE_CHANGES'); ?>" />
					<?php if ($this->id) { ?>
						<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=status&app=' . $this->defaults['toolname']); ?>" title="<?php echo Lang::txt('COM_TOOLS_HINT_CANCEL'); ?>"><?php echo Lang::txt('COM_TOOLS_CANCEL'); ?></a>
					<?php } ?>
				</p>
			</fieldset>
		</form>
	</div><!-- / .subject -->
	<aside class="aside expanded">
	<?php if (!$this->id) { ?>
		<h3><?php echo Lang::txt('COM_TOOLS_SIDE_WHAT_TOOLNAME'); ?></h3>
		<p><?php echo Lang::txt('COM_TOOLS_SIDE_TIPS_TOOLNAME'); ?></p>
	<?php } else { ?>
		<p><?php echo Lang::txt('COM_TOOLS_SIDE_EDIT_TOOL'); ?></p>
	<?php } ?>
		<h3><?php echo Lang::txt('COM_TOOLS_SIDE_WHAT_TOOLACCESS'); ?></h3>
		<p><?php echo Lang::txt('COM_TOOLS_SIDE_TIPS_TOOLACCESS'); ?></p>
		<h3><?php echo Lang::txt('COM_TOOLS_SIDE_WHAT_CODEACCESS'); ?></h3>
		<?php echo Lang::txt('COM_TOOLS_SIDE_TIPS_CODEACCESS'); ?>
		<h3><?php echo Lang::txt('COM_TOOLS_SIDE_WHAT_WIKIACCESS'); ?></h3>
		<p><?php echo Lang::txt('COM_TOOLS_SIDE_TIPS_WIKIACCESS'); ?></p>
	</aside><!-- / .aside -->
</section><!-- / .section -->