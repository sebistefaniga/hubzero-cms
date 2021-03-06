<?php
/**
 * @package		HUBzero CMS
 * @author		Alissa Nedossekina <alisa@purdue.edu>
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

$outside = isset($this->outside) && $this->outside == 1 ? 1 : 0;
?>

<?php if ($outside)
{
	?>
	<div class="mysubmissions">
<?php	if (User::isGuest())
	{
		// Have user log in
		echo '<p class="noresults">' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_PLEASE') . ' <a href="' .
		Route::url('index.php?option=com_publications&task=submit&action=login') . '">'
		. Lang::txt('PLG_PROJECTS_PUBLICATIONS_LOGIN') . '</a> ' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_TO_VIEW_SUBMISSIONS')
		. '</p>';
	}
	else {
		// Display submissions if any ?>
		<div id="mypub">
			<div class="columns three first second">
			<h3><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_STARTED_BY_ME'); ?>
				<?php if ($this->mypubs_count > count($this->mypubs)) { ?>
					<span class="rightfloat mini"><a href="<?php echo Route::url('index.php?option=com_publications&task=submit&limit=0'); ?>">&raquo; <?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_VIEW_ALL') . ' '
					. $this->mypubs_count . ' ' . strtolower(Lang::txt('PUBLICATIONS')) ; ?></a></span>
				<?php } ?></h3>
			<?php if (count($this->mypubs) > 0 ) { ?>
				<ul class="mypubs">
					<?php foreach ($this->mypubs as $row) {
						// Normalize type title
						$cls = str_replace(' ', '', $row->cat_alias);

						$route = $row->project_provisioned
									? 'index.php?option=com_publications&task=submit'
									: 'index.php?option=com_projects&alias=' . $row->project_alias . '&active=publications';
						$url = Route::url($route . '&pid=' . $row->id);
						$preview = 	Route::url('index.php?option=com_publications&id='.$row->id);

						$status = \Components\Publications\Helpers\Html::getPubStateProperty($row, 'status', 0);
						$class = \Components\Publications\Helpers\Html::getPubStateProperty($row, 'class');
					?>
					<li>
						<span class="mypub-options">
							<a href="<?php echo $preview; ?>" title="<?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_VIEW_TITLE'); ?>"><?php echo strtolower(Lang::txt('PLG_PROJECTS_PUBLICATIONS_VIEW')); ?></a> |
							<a href="<?php echo $url; ?>" title="<?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_MANAGE_TITLE'); ?>"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_MANAGE'); ?></a>
						</span>
						<span class="mypub-status"><span class="<?php echo $class; ?> major_status"><?php echo $status; ?></span></span>
						<span class="mypub-version"><?php if ($row->dev_version_label && $row->dev_version_label != $row->version_label)
						{ echo '<span class="mypub-newversion"><a href="' . $url . '/?version=dev'
						. '">v.' . $row->dev_version_label . '</a> '
						. Lang::txt('PLG_PROJECTS_PUBLICATIONS_IN_PROGRESS') . '</span> '; } ?> v.<?php echo $row->version_label; ?></span>
						<span class="restype"><span class="<?php echo $cls; ?>"></span></span>
						<?php echo \Hubzero\Utility\String::truncate(stripslashes($row->title), 80); ?>
						<?php if ($row->project_provisioned == 0) { echo '<span class="mypub-project">'
						. Lang::txt('PLG_PROJECTS_PUBLICATIONS_IN_PROJECT') . ' <a href="'
						. Route::url('index.php?option=com_projects&alias=' . $row->project_alias) . '">'
						. \Hubzero\Utility\String::truncate(stripslashes($row->project_title), 80) . '</a>' . '</span>'; } ?>
					</li>
					<?php }?>
				</ul>
			<?php } else {
				echo ('<p class="noresults">'.Lang::txt('PLG_PROJECTS_PUBLICATIONS_NO_RELEVANT_PUBS_FOUND').'</a></p>'); } ?>
			<h3><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_COAUTHORED'); ?>
					<?php if ($this->coauthored_count > count($this->coauthored)) { ?>
						<span class="rightfloat mini"><a href="<?php echo Route::url('index.php?option=com_publications&task=submit&limit=0'); ?>">&raquo; <?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_VIEW_ALL') . ' '
						. $this->coauthored_count . ' ' . strtolower(Lang::txt('PUBLICATIONS')) ; ?></a></span>
					<?php } ?></h3>
			<?php if (count($this->coauthored) > 0 ) { ?>
					<ul class="mypubs">
						<?php foreach ($this->coauthored as $row) {
							// Normalize type title
							$cls = str_replace(' ', '', $row->cat_alias);

							$route = $row->project_provisioned
										? 'index.php?option=com_publications&task=submit'
										: 'index.php?option=com_projects&alias=' . $row->project_alias . '&active=publications';
							$url = Route::url($route . '&pid=' . $row->id);
							$preview = 	Route::url('index.php?option=com_publications&id='.$row->id);

							$status = \Components\Publications\Helpers\Html::getPubStateProperty($row, 'status', 0);
							$class = \Components\Publications\Helpers\Html::getPubStateProperty($row, 'class');

							// Check team role
							$pOwner = new \Components\Projects\Tables\Owner( $this->database );
							$owner = $pOwner->isOwner($this->uid, $row->project_id);
						?>
						<li>
							<span class="mypub-options">
								<a href="<?php echo $preview; ?>" title="<?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_VIEW_TITLE'); ?>"><?php echo strtolower(Lang::txt('PLG_PROJECTS_PUBLICATIONS_VIEW')); ?></a> <?php if ($owner != 3) { ?> |
								<a href="<?php echo $url; ?>" title="<?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_MANAGE_TITLE'); ?>"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_MANAGE'); ?></a><?php } ?>
							</span>
							<span class="mypub-status"><span class="<?php echo $class; ?> major_status"><?php echo $status; ?></span></span>
							<span class="mypub-version"><?php if ($row->dev_version_label && $row->dev_version_label != $row->version_label)
							{ echo '<span class="mypub-newversion"><a href="' . $url . '/?version=dev'
							. '">v.' . $row->dev_version_label . '</a> '
							. Lang::txt('PLG_PROJECTS_PUBLICATIONS_IN_PROGRESS') . '</span> '; } ?> v.<?php echo $row->version_label; ?></span>
							<span class="restype"><span class="<?php echo $cls; ?>"></span></span>
							<?php echo \Hubzero\Utility\String::truncate(stripslashes($row->title), 80); ?>
							<?php if ($row->project_provisioned == 0) { echo '<span class="mypub-project">'
							. Lang::txt('PLG_PROJECTS_PUBLICATIONS_IN_PROJECT') . ' <a href="'
							. Route::url('index.php?option=com_projects&alias=' . $row->project_alias) . '">'
							. \Hubzero\Utility\String::truncate(stripslashes($row->project_title), 80) . '</a>' . '</span>'; } ?>
						</li>
						<?php }?>
					</ul>
			<?php } else {
				echo ('<p class="noresults">'.Lang::txt('PLG_PROJECTS_PUBLICATIONS_NO_RELEVANT_PUBS_FOUND').'</a></span></p>'); } ?>
			</div>
			<div class="columns three third">
				<div id="contrib-start">
					<p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_CONTRIB_START'); ?></p>
					<p class="getstarted-links"><a href="/members/myaccount/projects"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_VIEW_YOUR_PROJECTS'); ?></a> | <span class="addnew"><a href="/projects/start"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_START_PROJECT'); ?></a></span></p>
					<p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_CONTRIB_START_INDEPENDENT'); ?></p>
					<p id="getstarted"><a href="<?php echo Route::url('index.php?option=com_publications&task=start'); ?>"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_GET_STARTED'); ?> &raquo;</a></p>
				</div>
			</div>
			<div class="clear"></div>
		</div>
<?php } ?>
	</div>
<?php } ?>
<div id="pubintro">
	<h3><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUB_HOW_IT_WORKS'); ?> <?php if ($this->pubconfig->get('documentation')) { ?>
	<span class="learnmore"><a href="<?php echo $this->pubconfig->get('documentation'); ?>"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_LEARN_MORE'); ?> &raquo;</a></span>
	<?php } ?></h3>

	<div class="columns three first">
		<h4><span class="num">1</span> <?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUB_INTRO_STEP_ONE'); ?></h4>
		<p><?php echo $outside
						? Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUB_INTRO_STEP_ONE_ABOUT_OUTSIDE')
						: Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUB_INTRO_STEP_ONE_ABOUT'); ?></p>
	</div>
	<div class="columns three second">
		<h4><span class="num">2</span> <?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUB_INTRO_STEP_TWO'); ?></h4>
		<p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUB_INTRO_STEP_TWO_ABOUT'); ?></p>
	</div>
	<div class="columns three third">
		<h4><span class="num">3</span> <?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUB_INTRO_STEP_THREE'); ?></h4>
		<p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUB_INTRO_STEP_THREE_ABOUT'); ?></p>
	</div>
	<div class="clear"></div>
</div>