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

// Version status
$status = \Components\Publications\Helpers\Html::getPubStateProperty($this->pub, 'status');
$class 	= \Components\Publications\Helpers\Html::getPubStateProperty($this->pub, 'class');

$v = $this->version == 'default' ? '' : '?v=' . $this->version;

// Get hub config
$juri 	 = JURI::getInstance();

$site 	 = Config::get('config.live_site')
	? Config::get('config.live_site')
	: trim(preg_replace('/\/administrator/', '', $juri->base()), DS);

$now = JFactory::getDate()->toSql();

// Build our citation object
$citation = '';
if ($this->pub->doi)
{
	include_once( JPATH_ROOT . DS . 'components' . DS . 'com_citations' . DS . 'helpers' . DS . 'format.php' );

	$cite 		 	= new stdClass();
	$cite->title 	= $this->pub->title;
	$cite->year  	= JHTML::_('date', $this->pub->published_up, 'Y');
	$cite->location = '';
	$cite->date 	= '';

	// Get version authors
	$pa = new \Components\Publications\Tables\Author( $this->database );
	$authors = $pa->getAuthors($this->pub->version_id);

	$cite->url = $site . DS . 'publications' . DS . $this->pub->id . '?v=' . $this->pub->version_number;
	$cite->type = '';
	$model = new \Components\Publications\Models\Publication($this->pub);
	$cite->author = $model->getUnlinkedContributors();
	$cite->doi = $this->pub->doi;
	$citation = \Components\Citations\Helpers\Format::formatReference($cite);
}

// Get creator name
$profile = \Hubzero\User\Profile::getInstance($this->pub->created_by);
$creator = $profile->get('name') . ' (' . $profile->get('username') . ')';

$mt = new \Components\Publications\Tables\MasterType( $this->database );
$mType = $mt->getType($this->pub->base);
$typeParams = new JParameter( $mType->params );

$showCitations = $typeParams->get('show_citations', 0);

?>
<form action="<?php echo $this->url; ?>" method="post" id="plg-form" enctype="multipart/form-data">
	<?php echo $this->project->provisioned == 1
				? \Components\Publications\Helpers\Html::showPubTitleProvisioned( $this->pub, $this->route)
				: \Components\Publications\Helpers\Html::showPubTitle( $this->pub, $this->route, $this->title); ?>
	<fieldset>
		<input type="hidden" name="id" value="<?php echo $this->project->id; ?>" id="projectid" />
		<input type="hidden" name="version" value="<?php echo $this->version; ?>" />
		<input type="hidden" name="active" value="publications" />
		<input type="hidden" name="action" value="save" />
		<input type="hidden" name="section" id="section" value="<?php echo $this->active; ?>" />
		<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
		<input type="hidden" name="pid" id="pid" value="<?php echo $this->pub->id; ?>" />
		<input type="hidden" name="vid" id="vid" value="<?php echo $this->row->id; ?>" />
		<input type="hidden" name="base" id="base" value="<?php echo $this->pub->base; ?>" />
		<input type="hidden" name="provisioned" id="provisioned" value="<?php echo $this->project->provisioned == 1 ? 1 : 0; ?>" />
		<?php if ($this->project->provisioned == 1 ) { ?>
		<input type="hidden" name="task" value="submit" />
		<?php } ?>
	</fieldset>

<?php
	// Draw status bar
	\Components\Publications\Helpers\Html::drawStatusBar($this);

// Section body starts:
?>
<div id="pub-body" class="<?php echo $this->version; ?>">
	<div id="pub-editor">
		<div class="two columns first" id="c-selector">
		 <div class="c-inner">
			<h4><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_VERSION') . ' ' . $this->row->version_label . ' (' . $status . ')'; ?></h4>
			<table class="tbl-panel">
				<tbody>
					<tr>
						<td class="tbl-lbl"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_TITLE'); ?>:</td>
						<td class="tbl-input"><span><?php echo $this->row->title; ?></span></td>
					</tr>
					<tr>
						<td class="tbl-lbl"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_VERSION_LABEL'); ?>:</td>
						<td class="tbl-input"><span <?php if (($this->version == 'dev' || $this->row->state == 4) && $this->task != 'edit') { echo 'id="edit-vlabel" class="pub-edit"'; } ?>><?php echo $this->row->version_label;  ?></span> <?php if ($this->pub->main == 1) { echo '<span id="v-label">('.Lang::txt('PLG_PROJECTS_PUBLICATIONS_VERSION_DEFAULT').')</span>'; } ?></td>
					</tr>
					<tr>
						<td class="tbl-lbl"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_VERSION_NUMBER'); ?>:</td>
						<td class="tbl-input"><span><?php echo $this->row->version_number;  ?></span><?php if ($this->pub->versions) { ?> &nbsp; &nbsp;<span >[<a href="<?php echo $this->url . '/?action=versions'; ?>"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_VIEW_ALL_VERSIONS'); ?></a>]</span><?php } ?></td>
					</tr>
					<tr>
						<td class="tbl-lbl"><?php echo ucfirst(Lang::txt('PLG_PROJECTS_PUBLICATIONS_CREATED')); ?>:</td>
						<td class="tbl-input"><?php echo JHTML::_('date', $this->row->created, 'M d, Y').' ('.\Components\Projects\Helpers\Html::timeAgo($this->row->created).' '.Lang::txt('PLG_PROJECTS_PUBLICATIONS_AGO').')'; ?></td>
					</tr>
					<tr>
						<td class="tbl-lbl"><?php echo ucfirst(Lang::txt('PLG_PROJECTS_PUBLICATIONS_CREATED_BY')); ?>:</td>
						<td class="tbl-input"><?php echo $creator; ?></td>
					</tr>
					<tr>
						<td class="tbl-lbl"><?php echo ucfirst(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PRIMARY_CONTENT')); ?>:</td>
						<td class="tbl-input"><?php echo strtolower(Lang::txt('PLG_PROJECTS_PUBLICATIONS_CONTENT_' . strtoupper($this->pub->base))); ?></td>
					</tr>
					<tr>
						<td class="tbl-lbl"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_STATUS'); ?>:</td>
						<td class="tbl-input">
							<span class="<?php echo $class; ?>"> <?php echo $status; ?></span>
							<?php if ($this->row->published_up > $now ) { ?>
							<span class="embargo"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_EMBARGO') . ' ' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_UNTIL') . ' ' . JHTML::_('date', $this->row->published_up, 'M d, Y'); ?></span>
							<?php } ?>
						</td>
					</tr>
					<?php if ($this->row->doi) { ?>
					<tr>
						<td class="tbl-lbl"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_DOI'); ?>:</td>
						<td class="tbl-input"><?php echo $this->row->doi ? $this->row->doi : Lang::txt('PLG_PROJECTS_PUBLICATIONS_NA') ; ?>
						<?php if ($this->row->doi) { echo ' <a href="' . $this->pubconfig->get('doi_verify', 'http://data.datacite.org/') . $this->row->doi . '" rel="external">[&rarr;]</a>'; } ?>
						</td>
					</tr>
					<?php } ?>
					<?php if ($this->pub->state == 1 || $this->pub->state == 0) { ?>
					<?php
						if ($this->row->published_up > $now && $this->row->submitted != '0000-00-00 00:00:00')  { ?>
					<tr>
						<td class="tbl-lbl"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_SUBMITTED'); ?>:</td>
						<td class="tbl-input"><?php echo JHTML::_('date', $this->row->submitted, 'M d, Y'); ?></td>
					</tr>

					<?php } elseif ($this->row->published_up <= $now) { ?>
					<tr>
						<td class="tbl-lbl"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLISH_FROM'); ?>:</td>
						<td class="tbl-input"><?php echo JHTML::_('date', $this->row->published_up, 'M d, Y').' ('.\Components\Projects\Helpers\Html::timeAgo($this->row->published_up) . ' '.Lang::txt('PLG_PROJECTS_PUBLICATIONS_AGO') . ')'; ?></td>
					</tr>
					<?php } ?>
					<?php if ($this->row->accepted != '0000-00-00 00:00:00') { ?>
					<tr>
						<td class="tbl-lbl"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_ACCEPTED'); ?>:</td>
						<td class="tbl-input"><?php echo JHTML::_('date', $this->row->accepted, 'M d, Y').' (' . \Components\Projects\Helpers\Html::timeAgo($this->row->accepted) . ' ' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_AGO') . ')'; ?></td>
					</tr>
					<?php } ?>
					<?php } elseif ($this->pub->state != 3) {
						$date = $this->row->published_up;
						if ($this->pub->state == 5) {
							$show_action = Lang::txt('PLG_PROJECTS_PUBLICATIONS_SUBMITTED');
							$date = $this->row->submitted != '0000-00-00 00:00:00' ? $this->row->submitted : $this->row->published_up;
						}
						elseif ($this->pub->state == 4)
						{
							$show_action = Lang::txt('PLG_PROJECTS_PUBLICATIONS_FINALIZED');
						}
						elseif ($this->pub->state == 6)
						{
							$show_action = Lang::txt('PLG_PROJECTS_PUBLICATIONS_ARCHIVED');
						}
						else {
							$show_action = Lang::txt('PLG_PROJECTS_PUBLICATIONS_RELEASED');
						}
					?>
					<tr>
						<td class="tbl-lbl"><?php echo $show_action; ?>:</td>
						<td class="tbl-input"><?php echo JHTML::_('date', $date, 'M d, Y') . ' (' . \Components\Projects\Helpers\Html::timeAgo($date) . ' ' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_AGO') . ')'; ?></td>
					</tr>
					<?php } ?>
					<?php if ($this->pub->state == 0) { ?>
					<tr>
						<td class="tbl-lbl"><?php echo ucfirst(Lang::txt('PLG_PROJECTS_PUBLICATIONS_UNPUBLISHED')); ?>:</td>
						<td class="tbl-input"><?php echo JHTML::_('date', $this->row->published_down, 'M d, Y').' ('.\Components\Projects\Helpers\Html::timeAgo($this->row->published_down).' '.Lang::txt('PLG_PROJECTS_PUBLICATIONS_AGO').')'; ?></td>
					</tr>
					<?php } ?>
					<tr>
						<td class="tbl-lbl"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUB_URL'); ?>:</td>
						<td class="tbl-input"><a href="<?php echo Route::url('index.php?option=com_publications&id=' . $this->pub->id . $v); ?>"><?php echo trim($site, DS) .'/publications/' . $this->pub->id . $v; ?></a></td>
					</tr>
				</tbody>
			</table>
			<?php if ($this->version == 'dev') { ?>
				<p class="c-instruct js"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_VERSION_HINT_LABEL'); ?></p>
			<?php } ?>
		 </div>
		</div>
		<div class="two columns second" id="c-output">
		 <div class="c-inner">
			<h4>
			<?php if ($this->version == 'dev' || $this->row->state == 5) { ?>
				<?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT'); ?>
			<?php } else { ?>
				<?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_YOUR_OPTIONS'); ?>
			<?php } ?>
			</h4>

			<ul class="next-options">
			<?php if ($this->version == 'dev' || $this->row->state == 4) { // draft (initial or final) ?>
				<?php if (!$this->publication_allowed) { ?>
				<li id="next-edit"><p><?php
					echo '<strong>' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_DRAFT_INCOMPLETE') . '</strong> ' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_PUBLISH_MISSING');
					$missing = '';
					foreach ($this->checked as $key => $value) {
						if (!in_array($key, $this->required))
						{
							continue;
						}
						if ($value != 1) {
							$missing .= ' <a href="'
							. $this->url . '/?section=' . $key . '&amp;version=' . $this->version . '">'
							. strtolower(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PANEL_'.strtoupper($key)));
							$missing .= $value == 2 ? ' (' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_INCOMPLETE') . ')' : '';
							$missing .= '</a>,';
						}
					}
					$missing = substr($missing, 0, strlen($missing) - 1);
					echo '<strong>' . $missing . '</strong>';
					echo ' ' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_INFORMATION');
					?></p>
				</li>
				<?php } ?>
				<li id="next-publish"><p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_PUBLISH_READY');  ?> <?php if ($this->pubconfig->get('doi_service')) { echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_PUBLISH_DOI');  } ?></p>
				<p class="centeralign"><?php if ($this->publication_allowed) {  ?><a href="<?php echo $this->url . '/?action=publish&amp;version=' . $this->version; ?>" class="btn btn-success active"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUB_SUBMIT_TO_PUBLISH_REVIEW'); ?></a><?php } else { ?><span class="btn disabled"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUB_SUBMIT_TO_PUBLISH_REVIEW'); ?></span><?php } ?></p>
				</li>
				<?php if ($this->row->state != 4 && $this->publication_allowed) { ?>
				<li id="next-ready"><p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_SAVE');  ?></p>
					<p class="centeralign"><span class="<?php echo $this->publication_allowed ? 'btn' : 'btn disabled'; ?>"><?php if ($this->publication_allowed) {  ?><a href="<?php echo $this->url . '/?action=post&amp;version=' . $this->version; ?>"><?php } ?><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUB_SAVE_REVIEW'); ?><?php if ($this->publication_allowed) {  ?></a><?php } ?></span></p>
				</li>
				<?php } ?>
				<li id="next-cancel"><p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_NEED_TO_CANCEL') .' <a href="' . $this->url . '/?action=cancel&amp;version=' . $this->version . '">' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_CANCEL') . '</a> ' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_CANCEL_BEFORE');  ?></p></li>
			<?php } ?>
			<?php if ($this->row->state == 1 || $this->row->state == 0) { // new version allowed ?>
				<?php if ($this->pub->dev_version_label && $this->pub->dev_version_label != $this->pub->version_label) { ?>
				<li id="next-draft"><p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_VERSION_STARTED')
				. ' (<strong>v.'
				. $this->pub->dev_version_label . '</strong>)  <span class="block"><a href="'
				. $this->url . '/?version=dev">'
				. Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_NEW_VERSION_CONTINUE') . '</a></span>';  ?></p></li>
				<?php } else if (!$this->pub->dev_version_label) { ?>
				<li id="next-newversion"><p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_CHANGES_NEEDED')
				. ' <a href="' . $this->url . '/?action=newversion" class="showinbox">'
				. Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_NEW_VERSION') . '</a> ';  ?></p></li>
				<?php } ?>
			<?php } ?>

			<?php if ($this->row->state == 1) { // published ?>
				<?php if ($this->typeParams->get('option_unpublish', 0) == 1) { ?>
				<li id="next-cancel"><p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_PUBLISHED_UNPUBLISH');
				echo ' <a href="' . $this->url . '/?action=cancel&amp;version=' . $this->version . '">'
				. Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_UNPUBLISH_VERSION').' &raquo;</a> ';  ?></p></li>
				<?php } ?>
				<li id="next-usage"><p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_WATCH_STATS')
				.' <strong>'.Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_USAGE_STATS').'</strong> '
				.Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_FOLLOW_FEEDBACK');  ?>
					<span class="block italic"><a href="<?php echo $this->url . '/?action=stats' . '&amp;version=' . $this->version; ?>"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_VIEW_USAGE'); ?> &raquo;</a></span></p></li>
				<?php if ($showCitations) { ?>
				<li id="next-citation"><p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_WATCH_ADD_CITATIONS');  ?>
					<span class="block italic"><a href="<?php echo $this->url . '/?section=citations&amp;version=' . $this->version; ?>"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_ADD_CITATIONS'); ?> &raquo;</a></span></p></li>
				<?php } ?>
			<?php } ?>

			<?php if ($this->row->state == 5) { // pending approval ?>
				<li id="next-pending">
					<p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_PENDING');  ?>	</p>
					<?php if ($this->row->doi) {
						echo '<p>' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_PENDING_DOI_ISSUED') . '</p>'
						. '<div class="citeit">' . $citation . '</div>'; } ?>
				</li>
				<li id="next-ready"><p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_PENDING_REVERT');
				echo ' <a href="' . $this->url . '/?action=revert&amp;version=' . $this->version . '" id="confirm-revert">'
				.Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_REVERT').' &raquo;</a> ';  ?></p></li>
			<?php } ?>

			<?php if ($this->row->state == 0) { // unpublished
					// Check who unpublished this
					$objAA = new \Components\Projects\Tables\Activity( $this->database );
					$pubtitle = \Hubzero\Utility\String::truncate($this->row->title, 100);
					$activity = Lang::txt('PLG_PROJECTS_PUBLICATIONS_ACTIVITY_UNPUBLISHED');
					$activity .= ' ' . strtolower(Lang::txt('version')) . ' ' . $this->row->version_label . ' '
					. Lang::txt('PLG_PROJECTS_PUBLICATIONS_OF') . ' ' . strtolower(Lang::txt('publication')) . ' "'
					. $pubtitle . '" ';

					$admin = $objAA->checkActivity( $this->project->id, $activity);
				 ?>
				<?php if ($this->publication_allowed && $admin != 1) { ?>
				<li id="next-publish"><p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_UNPUBLISHED_PUBLISH')
				.' <a href="' . $this->url . '/?action=republish&amp;version=' . $this->version.'">'
				.Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUB_REPUBLISH').' &raquo;</a>';  ?></p></li>
				<?php } ?>
				<?php if ($admin == 1) { ?>
				<li id="next-question"><p><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_WHATS_NEXT_UNPUBLISHED_BY_ADMIN');  ?></p></li>
				<?php } ?>
			<?php } ?>
			</ul>
		 </div>
		</div>
	</div>
</div>
</form>
