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

// Do we have any published versions?
$published = $this->pub->versions > 0 ? 1 : 0;

// Are we allowed to edit?
$canedit = 1;

$ptitle = Lang::txt('PLG_PROJECTS_PUBLICATIONS_ADD_CITATIONS_TO_RELATED');

$config = Component::params( 'com_citations' );
$allow_import = $config->get('citation_import', 1);

// Build url
$route = $this->project->provisioned
	? 'index.php?option=com_publications&task=submit'
	: 'index.php?option=com_projects&alias=' . $this->project->alias;

$newCiteUrl   = $this->project->provisioned == 1
		? Route::url( $route) . '?active=links&action=editcite'
		: Route::url( $route . '&active=links&action=editcite') .'/?pid='
		. $this->pub->id . '&amp;vid=' . $this->pub->version_id;

?>
<?php echo $this->project->provisioned == 1
			? \Components\Publications\Helpers\Html::showPubTitleProvisioned( $this->pub, $this->route, $this->title)
			: \Components\Publications\Helpers\Html::showPubTitle( $this->pub, $this->route); ?>
<?php
	// Draw status bar
	\Components\Publications\Helpers\Html::drawStatusBar($this);

// Section body starts:
?>

<div id="pub-body">
	<div id="pub-editor">
		<div class="two columns first" id="c-selector">
		 <div class="c-inner">
			<h4><?php echo $ptitle; ?>
				<?php if (in_array($this->active, $this->required)) { ?><span class="required">
					<?php echo Lang::txt('REQUIRED'); ?></span><?php } ?>
			</h4>

			<form action="<?php echo Route::url($this->route . '&active=publications'); ?>" method="post" id="addmember-form">
					<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
					<input type="hidden" name="id" value="<?php echo $this->project->id; ?>" />
					<input type="hidden" name="version" value="<?php echo $this->version; ?>" />
					<input type="hidden" name="uid" value="<?php echo $this->uid; ?>" />
					<input type="hidden" name="versionid" value="<?php echo $this->row->id; ?>" />
					<input type="hidden" name="active" value="links" />
					<input type="hidden" name="action" value="addcitation" />
					<input type="hidden" name="pid" value="<?php echo $this->pid; ?>" />
					<input type="hidden" name="provisioned" value="<?php echo $this->project->provisioned == 1 ? 1 : 0; ?>" />
				<div class="c-panel-citations">
					<label><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_CITATIONS_ENTER_DOI'); ?>
						<input name="citation-doi" id="citation-doi" maxlength="200" size="35" type="text" value="" class="long pubinput" placeholder="<?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_CITATIONS_PLACEHOLDER'); ?>" />
					</label>
				</div>
				<div id="citation-preview"></div>
			</form>
			<?php if ($allow_import) { ?>
			<p class="and_or centeralign">OR</p>
			<p class="centeralign"><a href="<?php echo $newCiteUrl; ?>" class="btn showinbox" rel="external"><?php echo Lang::txt('Enter manually'); ?></a></p>
			<?php } ?>
			<p class="pub-info"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUB_TIPS_CITATIONS_PANEL'); ?></p>

		 </div>
		</div>
		<div class="two columns second" id="c-output">
			<form action="<?php echo $this->url; ?>" method="post" id="plg-form" enctype="multipart/form-data">
				<fieldset>
					<input type="hidden" name="id" value="<?php echo $this->project->id; ?>" id="projectid" />
					<input type="hidden" name="version" value="<?php echo $this->version; ?>" />
					<input type="hidden" name="active" value="publications" />
					<input type="hidden" name="action" value="save" />
					<input type="hidden" name="base" id="base" value="<?php echo $this->pub->base; ?>" />
					<input type="hidden" name="section" id="section" value="<?php echo $this->active; ?>" />
					<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
					<input type="hidden" name="move" id="move" value="<?php echo $this->move; ?>" />
					<input type="hidden" name="review" value="<?php echo $this->inreview; ?>" />
					<input type="hidden" name="pid" id="pid" value="<?php echo $this->pub->id; ?>" />
					<input type="hidden" name="vid" id="vid" value="<?php echo $this->row->id; ?>" />
					<input type="hidden" name="required" id="required" value="<?php echo in_array($this->active, $this->required) ? 1 : 0; ?>" />
					<input type="hidden" name="provisioned" id="provisioned" value="<?php echo $this->project->provisioned == 1 ? 1 : 0; ?>" />
					<?php if ($this->project->provisioned == 1 ) { ?>
					<input type="hidden" name="task" value="submit" />
					<?php } ?>
				</fieldset>
		 		<div class="c-inner">
					<?php if ($canedit) { ?>
							<span class="c-submit"><input type="submit" class="btn" value="<?php if ($this->move) { echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_SAVE_AND_CONTINUE'); } else { echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_SAVE_CHANGES'); } ?>" id="c-continue" /></span>
					<?php } ?>
					<h5><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_CITATIONS'); ?>: </h5>
					<ul id="c-citations" class="c-list">
						<li id="nosel" <?php if ($this->citations) { echo 'class="hidden"'; } ?> ><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_CITATIONS_NONE'); ?></li>
					<?php if ($this->citations) {

						$formatter = new \Components\Citations\Helpers\Format;
						$formatter->setTemplate($this->format);

						foreach ($this->citations as $cite) {
							$citeText = $cite->formatted
								? '<p>' . $cite->formatted . '</p>'
								: \Components\Citations\Helpers\Format::formatReference($cite, '');
						?>
						<li id="citation-<?php echo $cite->id; ?>" class="c-drag">
							<span class="c-delete"><a href="<?php echo Route::url('index.php?option=com_projects&alias=' . $this->project->alias . '&active=links&action=deletecitation').'/?pid=' . $this->pub->id . '&amp;cid=' . $cite->id; ?>">[<?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_DELETE'); ?>]</a></span>
							<span class="c-edit"><a href="<?php echo $newCiteUrl .'&cid=' . $cite->id; ?>" class="showinbox" rel="external">[<?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_EDIT'); ?>]</a></span>
							<span class="c-citation"><?php echo $citeText; ?></span>
						</li>
						<?php
						}
					} ?>
					</ul>
			 	</div>
			</form>
		</div>
	</div>
</div>
