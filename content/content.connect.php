<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/twitternotifier/lib/twitteroauth/twitteroauth.php');

class contentExtensionTwitterNotifierConnect extends AdministrationPage
{
	protected $_driver = null;
	protected $_id = null;
	protected $_uri = null;
	protected $TwitterOAuth = null;

	public function __construct(&$parent)
	{
		parent::__construct(&$parent);
		$this->_driver = Symphony::ExtensionManager()->create('twitternotifier');
		$this->_uri = URL . "/symphony/extension/twitternotifier/callback";
	}

	public function build($context)
	{
		$this->__prepareAccount($context);

		parent::build($context);
	}

	public function __prepareAccount($context)
	{
		$this->_id = $context[1];
		$this->_uri .= "/account/" . $this->_id . "/";
	}

	public function __viewAccount()
	{
		$this->TwitterOAuth = new TwitterOAuth(
			$this->_driver->getConsumerKey(),
			$this->_driver->getConsumerSecret()
		);

		$request_token = $this->TwitterOAuth->getRequestToken($this->_uri);

		unset($request_token['oauth_callback_confirmed']);

		Symphony::Database()->update($request_token, $this->_driver->table, "`id` = '".$this->_id."'");

		if($this->TwitterOAuth->http_code == 200)
		{
			$url = $this->TwitterOAuth->getAuthorizeURL($request_token['oauth_token']);
			//var_dump($url);
			//echo("<a href=\"$url\">go</a>");
			redirect("Location: " . $url);
		}
		else
		{
			// Exception? Error?
		}
	}
}

?>
