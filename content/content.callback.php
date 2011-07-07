<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/twitternotifier/lib/twitteroauth/twitteroauth.php');

class contentExtensionTwitterNotifierCallback extends AdministrationPage
{
	protected $_driver = null;
	protected $_account = null;
	protected $TwitterOAuth = null;

	public function __construct(&$parent)
	{
		parent::__construct(&$parent);
		$this->_driver = Symphony::ExtensionManager()->create('twitternotifier');
	}

	public function build($context)
	{
		$this->__prepareAccount($context);
		parent::build($context);
	}

	public function __prepareAccount($context)
	{
		$this->_account = Symphony::Database()->fetch("
			SELECT * FROM `".$this->_driver->table."` WHERE `oauth_token` = '".$_GET['oauth_token']."' LIMIT 1
		");
	}

	public function __viewAccount()
	{
		if(!isset($this->_account[0]['oauth_token'])) return false;

		$this->TwitterOAuth = new TwitterOAuth(
			$this->_driver->getConsumerKey(),
			$this->_driver->getConsumerSecret(),
			$this->_account[0]['oauth_token'],
			$this->_account[0]['oauth_token_secret']
		);

		$access_token = $this->TwitterOAuth->getAccessToken($_GET['oauth_verifier']);
		$access_token['authorised'] = "yes";

		Symphony::Database()->update($access_token, $this->_driver->table, "`oauth_token` = '".$this->_account[0]['oauth_token']."'");

	}
}

?>
<html>
	<head></head>
	<body>
		<script type="text/javascript">
		// <![CDATA[
		//window.close();
		// ]]>
		</script>
	</body>
</html>