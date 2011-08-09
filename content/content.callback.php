<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/twitternotifier/lib/twitteroauth/twitteroauth.php');

class contentExtensionTwitterNotifierCallback extends AdministrationPage
{
	protected $_driver = null;
	protected $_account = null;
	protected $TwitterOAuth = null;
	protected $_cookie = null;

	public function __construct(&$parent)
	{
		parent::__construct(&$parent);
		$this->_driver = Symphony::ExtensionManager()->create('twitternotifier');
	}

	public function build($context)
	{
		$this->__prepareIndex();
		parent::build($context);
	}

	public function __prepareIndex()
	{
		$this->_account = Symphony::Database()->fetch("
			SELECT * FROM `".$this->_driver->table."` WHERE `oauth_token` = '".$_GET['oauth_token']."' LIMIT 1
		");
		$this->_account = current($this->_account);
	}

	public function __viewIndex()
	{
		if(!isset($this->_account['oauth_token'])) return false;

		$this->TwitterOAuth = new TwitterOAuth(
			$this->_driver->getConsumerKey(),
			$this->_driver->getConsumerSecret(),
			$this->_account['oauth_token'],
			$this->_account['oauth_token_secret']
		);

		$access_token = $this->TwitterOAuth->getAccessToken($_GET['oauth_verifier']);

		$this->setTitle(__('%1$s &ndash; %2$s', array(__('Twitter Accounts'), __('Symphony'))));
		$this->appendSubheading(__('Twitter Authorization'));

		$fieldset = new XMLElement('fieldset');
		$fieldset->setAttribute('class', 'settings');

		if($this->TwitterOAuth->http_code == 200)
		{
			$this->_cookie = new Cookie(SYM_COOKIE_PREFIX . 'twitter_notifier', TWO_WEEKS, __SYM_COOKIE_PATH__);
			$this->_cookie->set('id', $this->_account['id']);

			$access_token['authorised'] = "yes";

			Symphony::Database()->update($access_token, $this->_driver->table, "`id` = '".$this->_account['id']."'");

			$fieldset->appendChild(new XMLElement('legend', __('Authorization Complete')));
			$label = Widget::Label(__('The Twitter authorization was successful.'));
		}
		else
		{
			Symphony::Database()->query("
				DELETE FROM `" . $this->_driver->table . "` WHERE `id` = '" . $this->_account['id'] . "';
			");

			$fieldset->appendChild(new XMLElement('legend', __('Authorization Failed.')));
			$label = Widget::Label(__('The Twitter authorization failed for some reason.'));
		}

		$input = new XMLElement('a', __('Close Window'));
		$input->setAttribute('href', '#');
		$input->setAttribute('id', 'twitter_close');

		$label->appendChild($input);

		$script = new XMLElement('script', "
			(function($){
				$(document).ready(function(){
					$('#twitter_close').click(function(ev){
						ev.preventDefault();
						window.close();
					});
				});
			})(jQuery);
		");

		$label->appendChild($script);
		$fieldset->appendChild($label);

		$this->Form->appendChild($fieldset);
	}
}

?>
