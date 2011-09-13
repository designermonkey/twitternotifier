<?php

require_once(TOOLKIT . '/class.sectionmanager.php');
require_once(TOOLKIT . '/class.entrymanager.php');
require_once(TOOLKIT . '/class.authormanager.php');
require_once(EXTENSIONS . '/twitternotifier/lib/twitteroauth/twitteroauth.php');

class Extension_TwitterNotifier extends Extension
{
	public $table = "tbl_authors_twitter_accounts";


	public function about()
	{
		return array
		(
			'name'         => 'Twitter Notifier',
			'version'      => '1.0.2',
			'release-date' => '2011-08-12',
			'author'       => array(
				'name'    => 'John Porter',
				'website' => 'http://designermonkey.co.uk/',
				'email'   => 'contact@designermonkey.co.uk'
			),
			'description' => 'Notify Twitter when you create an entry.'
		);
	}

	public function uninstall()
	{
		Symphony::Database()->query("
			DROP TABLE `".$this->table."`
		");
		return Symphony::Configuration()->remove('twitter-notifier');

	}

	public function install()
	{
		return Symphony::Database()->query("
			CREATE TABLE IF NOT EXISTS `".$this->table."` (
				`id` int(10) unsigned NOT NULL auto_increment,
				`screen_name` varchar(100) NOT NULL,
				`user_id` varchar(100),
				`oauth_token` text,
				`oauth_token_secret` text,
				`authorised` enum('yes','no') DEFAULT 'no',
				`section` int(10) unsigned NOT NULL,
				`field_param` int(10) unsigned NOT NULL,
				`field_msg` int(10) unsigned NOT NULL,
				`page` int(10) NOT NULL,
				`params` varchar(250) NOT NULL,
				`authors` varchar(250) NOT NULL,
				`status` varchar(250) NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=MyISAM;
		");
	}

	public function getSubscribedDelegates()
	{
		return array(
			array(
				'page'		=> '/publish/new/',
				'delegate'	=> 'EntryPostCreate',
				'callback'	=> 'sendTwitterNotification'
			),
			array(
				'page' 		=> '/backend/',
				'delegate'	=> 'InitaliseAdminPageHead',
				'callback'	=> 'initialiseAdminPageHead'
			),
			array(
				'page'		=> '/system/preferences/',
				'delegate' 	=> 'AddCustomPreferenceFieldsets',
				'callback' 	=> 'appendToPreferences'
			),
			array(
				'page' => '/system/preferences/',
				'delegate' => 'Save',
				'callback' => 'savePreferences'
			),
			array(
				'page' => '/system/preferences/success/',
				'delegate' => 'Save',
				'callback' => 'savePreferences'
			)
		);
	}

	public function fetchNavigation()
	{
		return array(
			array(
				'location' => 'System',
				'name' => __('Twitter Accounts'),
				'link' => '/accounts/'
				)
		);
	}

	/**
	 * Add the JavaScript to the head of the 'accounts' page
	 */
	public function initialiseAdminPageHead($context)
	{
			$page = $context['parent']->Page;
			if($page instanceof contentExtensionTwitterNotifierAccounts){
				$page->addStylesheetToHead(URL . '/extensions/twitternotifier/assets/twitternotifier.css');
				$page->addScriptToHead(URL . '/extensions/twitternotifier/assets/jquery.oauthpopup.js', null, false);
				$page->addScriptToHead(URL . '/extensions/twitternotifier/assets/twitternotifier.js', null, false);
			}
	}

	private function _checkConsumerDetails()
	{
		if(Symphony::Configuration()->get('consumer-secret', 'twitter-notifier') && Symphony::Configuration()->get('consumer-key', 'twitter-notifier'))
		{
			return true;
		}
		return false;
	}

	public function savePreferences($context)
	{
		if(empty($context['settings']['twitter-notifier']['consumer-key']) || empty($context['settings']['twitter-notifier']['consumer-secret']))
		{
			if($this->_checkConsumerDetails() == true && $context['settings']['twitter-notifier']['remove-details'] == 'yes')
			{
				$context['settings']['twitter-notifier']['consumer-key'] = $this->getConsumerKey();
				$context['settings']['twitter-notifier']['consumer-secret'] = $this->getConsumerSecret();
			}
		}
	}

	public function appendToPreferences($context)
	{
		$fieldset = new XMLElement('fieldset');
		$fieldset->setAttribute('class', 'settings');
		$fieldset->appendChild(new XMLElement('legend', __('Twitter API')));

		$status = new XMLElement('div');

		if($this->_checkConsumerDetails() == true)
		{
			$status->appendChild(new XMLElement('p', __('Your Consumer details are saved in the preferences, to change them, please re-enter them here.')));
		}
		else
		{
			// Add info
			$fieldset->appendChild(new XMLElement('p',__('An <a href="http://dev.twitter.com/login" title="Login at Twitter Developers site">application will need registering</a> with a Twitter account to get the details required here.')));
			$status->setAttribute('class', 'invalid');
			$p = new XMLElement('p', __('Your Consumer details are not saved in the preferences enter them to save.'));
			$p->setAttribute('style', 'padding-top:0.75em');
			$status->appendChild($p);
		}
		$fieldset->appendChild($status);

		$div = new XMLElement('div');
		$div->setAttribute('class','group');

		// Add Consumer Key field
		$label = Widget::Label(__('Consumer Key'));
		$label->appendChild(Widget::Input('settings[twitter-notifier][consumer-key]', null, 'password'));
		$div->appendChild($label);
		// Add Consumer Secret field
		$label = Widget::Label(__('Consumer Secret'));
		$label->appendChild(Widget::Input('settings[twitter-notifier][consumer-secret]', null, 'password'));
		$div->appendChild($label);
		$fieldset->appendChild($div);
		// Add sub help
		$fieldset->appendChild(new XMLElement('p',__('Your Consumer details are required to allow Author accounts access to notify Twitter.'), array('class' => 'help')));

		$context['wrapper']->appendChild($fieldset);
	}

	/**
	 * Sends the notification to Twitter
	 */
	public function sendTwitterNotification($context)
	{

	// Get any accounts that relate to this section
		$accounts = Symphony::Database()->fetch("
			SELECT * FROM `" . $this->table . "` WHERE `section` = " . (int)$context['section']->get('id') . ";
		");

		$author_id = $context['entry']->get('author_id');

		foreach($accounts as $account)
		{
			$account['authors'] = explode(',',$account['authors']);

			if(!in_array($author_id, $account['authors'])) continue;

			$page = Symphony::Database()->fetch("
				SELECT * FROM `tbl_pages` WHERE `id` = " . $account['page'] . ";
			");
			$page = current($page);

			$url = URL . '/' . ($page['path'] ? $page['path'] . '/' : '') . $page['handle'] . '/' . $account['params'] . '/';
			$url_handle = $context['entry']->getData($account['field_param']);
			$url = str_replace('$field', $url_handle['handle'], $url);

			$msg = $context['entry']->getData($account['field_msg']);

			$TwitterOAuth = new TwitterOAuth(
				$this->getConsumerKey(),
				$this->getConsumerSecret(),
				$account['oauth_token'],
				$account['oauth_token_secret']
			);

			$reserve = $TwitterOAuth->get('help/configuration')->short_url_length;

			$msg_append = '... ' . __('Read more') . ' ';

			$reserve += count($msg_append);

			$msg = preg_replace('/\s+?(\S+)?$/', '', substr($msg['value'], 0, (141 - $reserve)));

			$tweet = $msg . $msg_append . $url;

			$result = $TwitterOAuth->post('statuses/update', array(
				'status' => $tweet,
				'wrap_links' => 'true',
				'trim_user' => 'true',
				'include_entities' => 'true'
			));
			// I've requested entities to be returned for future implemntation of Tracker support
		}
	}

	/**
	 * Helper Function - Get the consumer key from the Configuration object
	 *
	 * @return String    Twitter Consumer Key
	 */
	public function getConsumerKey()
	{
		return Symphony::Configuration()->get('consumer-key', 'twitter-notifier');
	}

	/**
	 * Helper Function - Get the consumer secret from the Configuration object
	 *
	 * @return String    Twitter Consumer Secret
	 */
	public function getConsumerSecret()
	{
		return Symphony::Configuration()->get('consumer-secret', 'twitter-notifier');
	}
}
