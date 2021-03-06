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
 * @license   GNU General Public License, version 2 (GPLv2)
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

	$sitename = Config::get('sitename');

	$jobsHtml = new \Components\Jobs\Helpers\Html();

	// get some configs
	$promoline = $this->config->get('promoline') ? $this->config->get('promoline') : '';
	$infolink = $this->config->get('infolink') ? $this->config->get('infolink') : '';

	$allowed_ads = $this->service->maxads - $this->activejobs;
	$allowed_ads = $allowed_ads < 0 ? 0 : $allowed_ads;

	$class = 'no';
	switch ( $this->subscription->status )
	{
		case '0':    $status = Lang::txt('COM_JOBS_JOB_STATUS_PENDING');
		break;
		case '1':    $status = Lang::txt('COM_JOBS_JOB_STATUS_ACTIVE');
					 $class  = 'yes';
		break;
		case '2':    $status = Lang::txt('COM_JOBS_JOB_STATUS_CANCELLED');
		break;
		default:     $status = Lang::txt('N/A');
		break;
	}

	$today = date( 'Y-m-d');

	$status = $this->subscription->expires < $today && $this->subscription->status==1
				? Lang::txt('COM_JOBS_SUBSCRIPTION_STATUS_EXPIRED')
				: $status;
	$length = $this->subscription->status==0
				? $this->subscription->pendingunits
				: $this->subscription->units;
	$pending = $this->subscription->pendingunits && $this->subscription->status==1
				? ' <span class="no">('.$this->subscription->pendingunits.' '.Lang::txt('COM_JOBS_ADDITIONAL').' '.$this->service->unitmeasure.'MULTIPLE_S'.' '.Lang::txt('COM_JOBS_MONTHS_PENDING').')</span>'
				: '';
	$expiredate = $this->subscription->expires
				? JHTML::_('date', $this->subscription->expires, Lang::txt('DATE_FORMAT_HZ1'))
				: Lang::txt('N/A');

	// site admins
	if ($this->masteradmin)
	{
		$this->subscription->code = Lang::txt(' N/A');
		$this->service->title = Lang::txt('COM_JOBS_NOTICE_ADMIN_UNLIMITED_ACCESS');
		$class  = 'yes';
		$status = Lang::txt('COM_JOBS_SUBSCRIPTION_STATUS_ACTIVE_ADMIN');
	}

?>
<header id="content-header">
	<h2><?php echo $this->title; ?></h2>

	<?php if ($this->emp && !$this->masteradmin) { ?>
		<div id="content-header-extra">
			<ul id="useroptions">
				<li><a class="shortlist btn" href="<?php echo Route::url('index.php?option='.$this->option.'&task=resumes').'?filterby=shortlisted'; ?>"><?php echo Lang::txt('COM_JOBS_SHORTLIST'); ?></a></li>
			</ul>
		</div><!-- / #content-header-extra -->
	<?php } ?>
</header><!-- / #content-header -->

<section class="main section">
	<?php if ($this->getError()) { ?>
		<p class="error"><?php echo $this->getError(); ?></p>
	<?php }
	if ($this->msg_warning) { ?>
		<p class="warning"><?php echo $this->msg_warning; ?></p>
	<?php }
	if ($this->msg_passed) { ?>
		<p class="passed"><?php echo $this->msg_passed; ?></p>
	<?php } ?>

	<div class="grid">
		<div class="col span6">
			<div id="activities">
				<h3><?php echo Lang::txt('COM_JOBS_DASHBOARD_ACTIVITIES'); ?></h3>
				<h4><?php echo '<a href="'.Route::url('index.php?option='.$this->option.'&task=resumes').'">'.Lang::txt('COM_JOBS_ACTION_BROWSE_RESUMES').' ('.$this->stats['total_resumes'].')</a>'; ?></h4>
				<span class="sub-heading"><?php echo Lang::txt('COM_JOBS_DASHBOARD_TOTAL_POOL'); ?></span>
				<p>
					<span class="view">
						<?php echo '<a href="'.Route::url('index.php?option='.$this->option.'&task=resumes').'" class="cancelit">[ '.Lang::txt( 'COM_JOBS_DASHBOARD_VIEW' ).' ]</a>'; ?>
					</span><?php echo $this->stats['total_resumes']; ?>
				</p>
				<span class="sub-heading"><?php echo Lang::txt('COM_JOBS_DASHBOARD_SHORTLISTED'); ?></span>
				<p>
					<span class="view">
						<?php if ($this->stats['shortlisted'] > 0) {
						echo '<a href="'.Route::url('index.php?option='.$this->option.'&task=batch').'?pile=shortlisted" class="cancelit">[ '.Lang::txt( 'COM_JOBS_DASHBOARD_DOWNLOAD' ).' ]</a> &nbsp;&nbsp;&nbsp;'; }
						echo '<a href="'.Route::url('index.php?option='.$this->option.'&task=resumes').'?filterby=shortlisted" class="cancelit">[ '.Lang::txt( 'COM_JOBS_DASHBOARD_VIEW' ).' ]</a>'; ?>
					</span><?php echo $this->stats['shortlisted']; ?>
				</p>
				<span class="sub-heading"><?php echo Lang::txt('COM_JOBS_DASHBOARD_APPLIED_TO_ADS'); ?></span>
				<p>
					<span class="view">
						<?php if ($this->stats['applied'] > 0) {
						echo '<a href="'.Route::url('index.php?option='.$this->option.'&task=batch').'?pile=applied" class="cancelit">[ '.Lang::txt( 'COM_JOBS_DASHBOARD_DOWNLOAD' ).' ]</a> &nbsp;&nbsp;&nbsp;'; }
						echo '<a href="'.Route::url('index.php?option='.$this->option.'&task=resumes').'?filterby=applied" class="cancelit">[ '.Lang::txt( 'COM_JOBS_DASHBOARD_VIEW' ).' ]</a>'; ?>
					</span><?php echo $this->stats['applied']; ?>
				</p>
				<div class="spacer"></div>
				<h4><span><?php echo Lang::txt('COM_JOBS_DASHBOARD_MANAGE_ADS').' ('.count($this->myjobs).')'; ?></span></h4>

				<p class="reg">
					<span><?php echo Lang::txt('COM_JOBS_DASHBOARD_YOU_HAVE_CURRENTLY').' '.$this->activejobs.' '.Lang::txt('COM_JOBS_DASHBOARD_PUBLISHED_ADS');
					if (!$this->masteradmin) { ?> <br /><?php echo $allowed_ads.' '.Lang::txt('COM_JOBS_DASHBOARD_NUMBER_ADS_STILL_ALLOWED'); } ?></span>
				</p>
				<?php if (count($this->myjobs) > 0) {
				foreach ($this->myjobs as $mj) { ?>
				<p class="reg myjob<?php
							if ($mj->status == 3) { echo '_inactive'; }
							else if ($mj->status == 4 or $mj->status == 0) { echo '_pending'; } ?>">
								<span class="view"><?php if ($mj->status == 1)
								{ echo $mj->applications.' '.Lang::txt('COM_JOBS_DASHBOARD_APPLICATIONS').' <a href="'.Route::url('index.php?option='.$this->option.'&task=job&code='.$mj->code).'#applications" class="cancelit">[ '.Lang::txt( 'COM_JOBS_DASHBOARD_VIEW' ).' ]</a>'; }
								else if ($mj->status == 4) { echo '('.strtolower(Lang::txt('COM_JOBS_JOB_STATUS_DRAFT')).')'; }
								else if ($mj->status == 0) { echo '('.strtolower(Lang::txt('COM_JOBS_JOB_STATUS_PENDING')).')'; }
								else if ($mj->status == 3) { echo '('.strtolower(Lang::txt('COM_JOBS_JOB_STATUS_INACTIVE')).')'; } ?>
								</span>
							<?php echo '<span class="code">'.$mj->code.'</span>: <a href="'.Route::url('index.php?option='.$this->option.'&task=job&code='.$mj->code).'">'.\Hubzero\Utility\String::truncate($mj->title, 50).'</a>';  ?>
					</p>
				<?php }
				} ?>
			<?php if ($this->subscription->status == 1 or $this->masteradmin) { ?>
				<p class="reg">
					<a class="add btn" href="<?php echo Route::url('index.php?option='.$this->option.'&task=addjob'); ?>"><?php echo Lang::txt('COM_JOBS_DASHBOARD_AD_NEW_JOB'); ?></a>
				</p>
				 <?php } ?>
			</div>
		</div>
		<div class="col span6 omega">
			<div id="subinfo">
				<h3><?php echo Lang::txt('COM_JOBS_SUBSCRIPTION_DETAILS'); ?><span><?php echo Lang::txt('COM_JOBS_JOB_REFERENCE_CODE').': '.$this->subscription->code; ?></span></h3>

				<span class="sub-heading"><?php echo Lang::txt('COM_JOBS_SUBSCRIPTION_SERVICE'); ?></span>
				<p><?php echo $this->service->title; ?></p>

				<span class="sub-heading"><?php echo Lang::txt('COM_JOBS_TABLE_STATUS'); ?></span>
				<p class="<?php echo $class; ?>"><?php echo $status; ?></p>

				<?php if (!$this->masteradmin) { ?>
					<span class="sub-heading"><?php echo Lang::txt('COM_JOBS_SUBSCRIPTION_LENGTH'); ?></span>
					<p><?php echo $length.'-'.$this->service->unitmeasure.$pending; ?></p>

					<span class="sub-heading"><?php echo Lang::txt('COM_JOBS_SUBSCRIPTION_EXPIRE_DATE'); ?></span>
					<p><?php echo $expiredate; ?></p>
					<p>
						<?php echo '<a href="'.Route::url('index.php?option='.$this->option.'&task=subscribe').'" class="cancelit">[ '.Lang::txt( 'COM_JOBS_SUBSCRIPTION_EXTEND_OR_RENEW_OR_CANCEL' ).' ]</a>'; ?>
					</p>
					<?php echo $jobsHtml->confirmscreen(Route::url('index.php?option='.$this->option.'&task=dashboard&uid='.$this->uid), Route::url('index.php?option='.$this->option.'&task=cancel&uid='.$this->uid)); ?>
					<div class="spacer"></div>

					<h3><?php echo Lang::txt('COM_JOBS_SUBSCRIPTION_EMPLOYER_INFORMATION'); ?><span><?php echo Lang::txt('COM_JOBS_EMPLOYER_USERNAME').': '.$this->login; ?></span></h3>

					<span class="sub-heading"><?php echo Lang::txt('COM_JOBS_EMPLOYER_COMPANY'); ?></span>
					<p><?php $emp_com = $this->employer->companyName ? $this->employer->companyName : Lang::txt('COM_JOBS_NOTICE_UNSPECIFIED') ; echo $emp_com ?></p>

					<span class="sub-heading"><?php echo Lang::txt('COM_JOBS_EMPLOYER_LOCATION'); ?></span>
					<p><?php $emp_loc = $this->employer->companyLocation ? $this->employer->companyLocation : Lang::txt('COM_JOBS_NOTICE_UNSPECIFIED') ; echo $emp_loc ?></p>

					<span class="sub-heading"><?php echo Lang::txt('COM_JOBS_EMPLOYER_WEBSITE'); ?></span>
					<p><?php $emp_web = $this->employer->companyWebsite ? $this->employer->companyWebsite : Lang::txt('COM_JOBS_NOTICE_UNSPECIFIED') ; echo $emp_web ?></p>
					<p><?php echo '<a href="'.Route::url('index.php?option='.$this->option.'&task=subscribe').'" class="cancelit">[ '.Lang::txt( 'COM_JOBS_EMPLOYER_EDIT_INFO' ).' ]</a>'; ?></p>
				<?php } ?>
			</div>
		</div>
	</div>
</section>