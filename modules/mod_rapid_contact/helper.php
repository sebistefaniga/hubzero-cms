<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 Purdue University. All rights reserved.
 * All rights reserved.
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
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Modules\RapidContact;

use Hubzero\Module\Module;
use Hubzero\Utility\Sanitize;
use Hubzero\Mail\Message;
use Request;
use Config;
use Lang;
use JURI;

/**
 * Module class for displaying a quick contact form
 */
class Helper extends Module
{
	/**
	 * Display module content
	 *
	 * @return  void
	 */
	public function display()
	{
		// Field labels
		$this->name_label    = $this->params->get('name_label', Lang::txt('MOD_RAPID_CONTACT_FIELD_NAME'));
		$this->email_label   = $this->params->get('email_label', Lang::txt('MOD_RAPID_CONTACT_FIELD_EMAIL'));
		$this->subject_label = $this->params->get('subject_label', Lang::txt('MOD_RAPID_CONTACT_FIELD_SUBJECT'));
		$this->message_label = $this->params->get('message_label', Lang::txt('MOD_RAPID_CONTACT_FIELD_MESSAGE'));

		// Button text
		$this->button_text   = $this->params->get('button_text', Lang::txt('MOD_RAPID_CONTACT_SEND'));

		// Pre text
		$this->pre_text      = $this->params->get('pre_text', '');

		// Thank you message
		$this->page_text     = $this->params->get('page_text', Lang::txt('MOD_RAPID_CONTACT_THANK_YOU'));

		// Error messages
		$this->error_text    = $this->params->get('error_text', Lang::txt('MOD_RAPID_CONTACT_ERROR_SENDING'));
		$this->no_email      = $this->params->get('no_email', Lang::txt('MOD_RAPID_CONTACT_ERROR_NO_EMAIL'));
		$this->invalid_email = $this->params->get('invalid_email', Lang::txt('MOD_RAPID_CONTACT_ERROR_INVALID_EMAIL'));

		// From
		$this->from_name     = @$this->params->get('from_name', Lang::txt('MOD_RAPID_CONTACT'));
		$this->from_email    = @$this->params->get('from_email', 'rapid_contact@yoursite.com');

		// To
		$this->recipient     = $this->params->get('email_recipient', Config::get('mailfrom'));
		if (!trim($this->recipient))
		{
			$this->recipient = Config::get('mailfrom');
		}

		// Enable Anti-spam?
		$this->enable_anti_spam = $this->params->get('enable_anti_spam', true);
		$this->anti_spam_q   = $this->params->get('anti_spam_q', Lang::txt('MOD_RAPID_CONTACT_ANTIPSAM'));
		$this->anti_spam_a   = $this->params->get('anti_spam_a', '2');

		$this->mod_class_suffix = $this->params->get('moduleclass_sfx', '');

		$disable_https       = $this->params->get('disable_https', false);
		$exact_url           = $this->params->get('exact_url', true);
		if (!$exact_url)
		{
			//$this->url = $this->_cleanXss(filter_var(JURI::current(), FILTER_SANITIZE_URL));
			$this->url = JURI::current();
		}
		else
		{
			if (!$disable_https)
			{
				$this->url = (!empty($_SERVER['HTTPS'])) ? 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] : 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
			}
			else
			{
				$this->url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
			}
		}

		//$qs = str_replace(array('"', '?'), '', urldecode($_SERVER['QUERY_STRING']));
		//$aqs = explode('?', $this->url);
		//$this->url = $aqs[0] . '?' . urlencode($qs);

		$fixed_url = $this->params->get('fixed_url', true);
		if ($fixed_url)
		{
			$this->url = $this->params->get('fixed_url_address', '');
		}

		$this->error = '';
		$this->replacement = '';

		$this->posted = array(
			'name'    => '',
			'email'   => '',
			'subject' => '',
			'message' => ''
		);

		if (isset($_POST['rp']))
		{
			$this->posted = Request::getVar('rp', array(), 'post');

			if ($this->enable_anti_spam)
			{
				if (!isset($this->posted['anti_spam_answer']) || ($this->posted['anti_spam_answer'] != $this->anti_spam_a))
				{
					$this->error = Lang::txt('MOD_RAPID_CONTACT_INVALID_ANTIPSAM_ANSWER');
				}
			}
			if ($this->posted['email'] === '')
			{
				$this->error = $this->no_email;
			}
			if (!preg_match("#^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$#i", $this->posted['email']))
			{
				$this->error = $this->invalid_email;
			}

			if ($this->error == '')
			{
				$mySubject  = Sanitize::clean($this->posted['subject']);
				$myMessage  = Lang::txt('MOD_RAPID_CONTACT_MESSAGE_FROM', $this->posted['name'], $this->posted['email'], Request::getVar('HTTP_REFERER', '', 'SERVER'), Config::get('sitename'));
				$myMessage .= "\n\n". Sanitize::clean($this->posted['message']);

				$this->from_email = $this->posted['email'];
				$this->from_name  = (isset($this->posted['name']) && Sanitize::clean($this->posted['name'])) ? Sanitize::clean($this->posted['name']) : $this->posted['email'];

				$mailSender = new Message();
				$mailSender->setSubject($mySubject)
				           ->addFrom($this->from_email, $this->from_name)
				           ->addTo($this->recipient)
				           ->addReplyTo($this->posted['email'], $this->posted['name'])
				           ->setBody($myMessage);

				if (!$mailSender->send())
				{
					$this->error = $this->error_text;
				}
				else
				{
					$this->replacement = $this->page_text;
				}
			}
		}

		require $this->getLayoutPath($this->params->get('layout', 'default'));
	}
}
