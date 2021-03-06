<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2014 Purdue University. All rights reserved.
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
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2014 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

$css = "#account-header {
border-collapse: collapse;
border: 1px solid #c2e1e3;
background: #e6fafb;
font-size: 0.9em;
line-height: 1.6em;
background-image: -webkit-gradient(linear, 0 0, 100% 100%,
	color-stop(.25, rgba(255, 255, 255, .075)), color-stop(.25, transparent),
	color-stop(.5, transparent), color-stop(.5, rgba(255, 255, 255, .075)),
	color-stop(.75, rgba(255, 255, 255, .075)), color-stop(.75, transparent),
	to(transparent));
background-image: -webkit-linear-gradient(-45deg, rgba(255, 255, 255, .075) 25%, transparent 25%,
	transparent 50%, rgba(255, 255, 255, .075) 50%, rgba(255, 255, 255, .075) 75%,
	transparent 75%, transparent);
background-image: -moz-linear-gradient(-45deg, rgba(255, 255, 255, .075) 25%, transparent 25%,
	transparent 50%, rgba(255, 255, 255, .075) 50%, rgba(255, 255, 255, .075) 75%,
	transparent 75%, transparent);
background-image: -ms-linear-gradient(-45deg, rgba(255, 255, 255, .075) 25%, transparent 25%,
	transparent 50%, rgba(255, 255, 255, .075) 50%, rgba(255, 255, 255, .075) 75%,
	transparent 75%, transparent);
background-image: -o-linear-gradient(-45deg, rgba(255, 255, 255, .075) 25%, transparent 25%,
	transparent 50%, rgba(255, 255, 255, .075) 50%, rgba(255, 255, 255, .075) 75%,
	transparent 75%, transparent);
background-image: linear-gradient(-45deg, rgba(255, 255, 255, .075) 25%, transparent 25%,
	transparent 50%, rgba(255, 255, 255, .075) 50%, rgba(255, 255, 255, .075) 75%,
	transparent 75%, transparent);
-webkit-background-size: 30px 30px;
-moz-background-size: 30px 30px;
background-size: 30px 30px;}";

$this->css($css);

?>

<!-- Start Header -->
<table class="tbl-header" cellpadding="2" cellspacing="3" border="0" width="100%" style="border-collapse: collapse; border-bottom: 2px solid #e1e1e1;">
	<tbody>
		<tr>
			<td width="10%" nowrap="nowrap" align="left" valign="bottom" style="font-size: 1.4em; color: #999; padding: 0 10px 5px 0; text-align: left;">
				<?php echo $this->config->get('sitename'); ?>
			</td>
			<td class="mobilehide" width="80%" align="left" valign="bottom" style="line-height: 1; padding: 0 0 5px 10px;">
				<span style="font-weight: bold; font-size: 0.85em; color: #666; -webkit-text-size-adjust: none;">
					<a href="<?php echo $this->baseUrl; ?>" style="color: #666; font-weight: bold; text-decoration: none; border: none;"><?php echo $this->baseUrl; ?></a>
				</span>
				<br />
				<span style="font-size: 0.85em; color: #666; -webkit-text-size-adjust: none;"><?php echo $this->config->get('MetaDesc'); ?></span>
			</td>
			<td width="10%" nowrap="nowrap" align="right" valign="bottom" style="border-left: 1px solid #e1e1e1; font-size: 1.2em; color: #999; padding: 0 0 5px 10px; text-align: right; vertical-align: bottom;">
				Accounts
			</td>
		</tr>
	</tbody>
</table>
<!-- End Header -->

<!-- Start Header Spacer -->
<table  width="100%" cellpadding="0" cellspacing="0" border="0">
	<tr style="border-collapse: collapse;">
		<td height="30" style="border-collapse: collapse;"></td>
	</tr>
</table>
<!-- End Header Spacer -->

<!-- ====== Start Header ====== -->
<table id="account-header" width="100%"  cellpadding="0" cellspacing="0" border="0">
	<tbody>
		<tr>
			<td style="font-weight: bold; border-bottom: 1px solid #c2e1e3; padding: 16px 30px; text-align: center; font-size: 1.5em; color: #e96c6c;" align="left">
				Username Reminder
			</td>
		</tr>
	</tbody>
</table>
<!-- ====== End Header ====== -->

<!-- ====== Start Header Spacer ====== -->
<table  width="100%" cellpadding="0" cellspacing="0" border="0">
	<tr style="border-collapse: collapse;">
		<td height="30" style="border-collapse: collapse;"></td>
	</tr>
</table>
<!-- ====== End Header Spacer ====== -->

<table id="account-info" width="100%"  cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; line-height: 1.6em;">
	<tbody>
		<tr>
			<td width="100%" style="padding: 18px 8px 8px 8px; border-top: 2px solid #e9e9e9;">
				<p>Hello <?php echo $this->users->first()->name; ?>,</p>

				<p>A username reminder has been requested for your <?php echo $this->config->get('sitename'); ?> account.</p>

				<p>The following usernames are associated with this email address:</p>

				<?php foreach ($this->users as $user) : ?>
				<p><b><?php echo $user->username; ?></b></p>
				<?php endforeach; ?>

				<p>Thank you!</p>
			</td>
		</tr>
	</tbody>
</table>

<!-- Start Header -->
<table width="100%" cellpadding="2" cellspacing="3" border="0" style="border-collapse: collapse; border-top: 2px solid #e1e1e1;">
	<tbody>
		<tr>
			<td align="left" valign="bottom" style="line-height: 1; padding: 5px 0 0 0; ">
				<span style="font-size: 0.85em; color: #666; -webkit-text-size-adjust: none;">
					<?php echo Lang::txt('COM_MEMBERS_CREDENTIALS_EMAIL_WHY_NOTFIED', $this->config->get('sitename'), $this->baseUrl, $this->baseUrl); ?>
				</span>
			</td>
		</tr>
	</tbody>
</table>
<!-- End Header -->

<!-- Start Footer Spacer -->
<table width="100%" cellpadding="0" cellspacing="0" border="0">
	<tbody>
		<tr style="border-collapse: collapse;">
			<td height="30" style="border-collapse: collapse; color: #fff !important;"><div style="height: 30px !important; visibility: hidden;">----</div></td>
		</tr>
	</tbody>
</table>
<!-- End Footer Spacer -->