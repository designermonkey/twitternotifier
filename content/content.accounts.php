<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/twitternotifier/lib/twitteroauth/twitteroauth.php');

class contentExtensionTwitterNotifierAccounts extends AdministrationPage
{
	protected $_driver = null;
	protected $_uri = null;
	protected $_account = null;
	protected $_accounts = null;
	protected $_cookie = null;
	protected $_mode = null;

	protected $_pagination = null;
	protected $_table_column = 'screen_name';
	protected $_table_columns = null;
	protected $_table_direction = 'asc';

	public function __construct(&$parent)
	{
		parent::__construct($parent);

		$this->_driver = Symphony::ExtensionManager()->create('twitternotifier');
		$this->_uri = URL . "/symphony/extension/twitternotifier";
	}

	public function build($context)
	{
		if(isset($context[0]))
		{
			switch($context[0])
			{
				case 'new':
					$this->_mode = 'new';
					$this->__prepareNew($context);
				break;
				case 'edit':
					$this->_mode = 'edit';
					$this->__prepareEdit($context);
				break;
			}
		}
		else
		{
			$this->__prepareIndex();
		}
		parent::build($context);
	}

	public function __prepareNew($context)
	{
		$this->_cookie = new Cookie(SYM_COOKIE_PREFIX . 'twitter_notifier', TWO_WEEKS, __SYM_COOKIE_PATH__);

		if($this->_cookie->get('id'))
		{
			$id = $this->_cookie->get('id');
			$this->_cookie->set('id', null);

			header('Location: ' . $this->_uri . '/accounts/new/' . $id . '/');
		}
		else
		{
			$this->_uri .= "/connect/";
		}

		$this->_accounts = Symphony::Database()->fetch("
			SELECT * FROM `".$this->_driver->table."`
		", 'id');

		if($context[1])
		{
			$this->_account = Symphony::Database()->fetchRow(0, "
				SELECT * FROM `" . $this->_driver->table . "` WHERE `id` = '" . $context[1] . "'
			");
		}
	}

	public function __prepareEdit($context)
	{
		$this->_uri .= "/connect/" . $context[1] . "/";
		$this->_account = Symphony::Database()->fetchRow(0, "
			SELECT * FROM `" . $this->_driver->table . "` WHERE `id` = '" . $context[1] . "'
		");
	}

	public function __viewNew()
	{
		$this->__viewEdit();
	}

	public function __viewEdit()
	{

	// Build Twitter Fieldset ---------------------------------------------------------

		$this->setPageType('form');
		$this->setTitle(__('%1$s &ndash; %2$s', array(__('Twitter Accounts'), __('Symphony'))));

		$method = ($this->_mode === 'edit') ? __('Edit your Twitter Account') : __('Create your Twitter Account');

		$this->appendSubheading($method);

		if (isset($this->_context[2]) && $this->_context[2] == 'saved') {
			$this->pageAlert(__('Twitter account saved.'), Alert::SUCCESS);
		}

		$fieldset = new XMLElement('fieldset');
		$fieldset->setAttribute('class', 'settings');
		$fieldset->appendChild(new XMLElement('legend', __('Step 1: Sign in at Twitter')));

		if($this->_account['user_id'] === null){
			$label = Widget::Label(__('Sign yourself in at Twitter with the account you want to set up.'));

			$img = new XMLElement('input');
			$img->setAttribute('type', 'image');
			$img->setAttribute('src', URL . '/extensions/twitternotifier/assets/sign-in-with-twitter.png');
			$img->setAttribute('alt', __('Sign in with Twitter'));
			$img->setAttribute('value', $this->_uri);
			$img->setAttribute('id', 'twitter_connect');

			$label->appendChild($img);
		}
		else
		{
			$label = Widget::Label(__('Your Twitter account is authrised as...'));
		}

		$fieldset->appendChild($label);

		$div = new XMLElement('div');
		$div->setAttribute('class', 'group');

		$label = Widget::Label(__('Screen Name'));
		$label->appendChild(Widget::Input('fields[screen_name]', $this->_account['screen_name'], 'text', array('readonly' => 'readonly')));
		$div->appendChild($label);

		$label = Widget::Label(__('Twitter User ID'));
		$label->appendChild(Widget::Input('fields[user_id]', $this->_account['user_id'], 'text', array('readonly' => 'readonly')));
		$div->appendChild($label);

		$fieldset->appendChild($div);

		$this->Form->appendChild($fieldset);

	// Build Details Fieldset

		$fieldset = new XMLElement('fieldset');
		$fieldset->setAttribute('class', 'settings');
		$fieldset->appendChild(new XMLElement('legend', __('Step 2: Choose Notification Details')));

		$div = new XMLElement('div');
		$div->setAttribute('class', 'group');

	// Section
		$label = Widget::Label(__('Notifying Section'));
		$SectionManager = new SectionManager(Symphony::Engine());
		$options = array();
		foreach($SectionManager->fetch(NULL, 'ASC', 'sortorder') as $section)
		{
			$options[] = array($section->get('id'), ($this->_account['section'] == $section->get('id')), $section->get('name'));
		}
		$label->appendChild(Widget::Select('fields[section]', $options, array('id' => 'section')));
		$div->appendChild($label);

	// Value Field
		$label = Widget::Label(__('Field for parameter value'));
		$FieldManager = new FieldManager(Symphony::Engine());
		$options = array();
		foreach($FieldManager->fetch(NULL, NULL, 'ASC', 'sortorder') as $field)
		{
			$options[] = array($field->get('id'), ($this->_account['field_param'] == $field->get('id')), $field->get('label'), 'section-id-'.$field->get('parent_section'));
		}
		$label->appendChild(Widget::Select('fields[field_param]', $options, array('id' => 'field_param')));
		$div->appendChild($label);

		$fieldset->appendChild($div);
		$fieldset->appendChild(new XMLElement('p', __('Choose the section to notify this account about, and it\'s field to use as the URL value.'), array('class' => 'help')));

		$div = new XMLElement('div');
		$div->setAttribute('class', 'group');

	// Message Field
		$label = Widget::Label(__('Field for message (will be excerpted)'));
		$FieldManager2 = new FieldManager(Symphony::Engine());
		$options = array();
		foreach($FieldManager2->fetch(NULL, NULL, 'ASC', 'sortorder') as $field2)
		{
			$options[] = array($field2->get('id'), ($this->_account['field_msg'] == $field2->get('id')), $field2->get('label'), 'section-id-'.$field2->get('parent_section'));
		}
		$label->appendChild(Widget::Select('fields[field_msg]', $options, array('id' => 'field_msg')));
		$div->appendChild($label);

		$label = Widget::Label(__('Authors'));
		$authors = Symphony::Database()->fetch("
			SELECT * FROM `tbl_authors`;
		");
		$options = array();
		$selected = explode(',', $this->_account['authors']);
		foreach($authors as $author)
		{
			$options[] = array($author['id'], (in_array($author['id'], $selected)), $author['first_name'] . " " . $author['last_name']);
		}
		$label->appendChild(Widget::Select('fields[authors][]', $options, array('multiple' => 'multiple')));
		$div->appendChild($label);

		$fieldset->appendChild($div);

		$this->Form->appendChild($fieldset);

	// Build the URL Fieldset

		$fieldset = new XMLElement('fieldset');
		$fieldset->setAttribute('class', 'settings');
		$fieldset->appendChild(new XMLElement('legend', __('Step 3: Choose How the Link is Built')));

		$div = new XMLElement('div');
		$div->setAttribute('class', 'group');

		$label = Widget::Label(__('Page'));
		$pages = Symphony::Database()->fetch("SELECT * FROM `tbl_pages`");
		$options = array();
		foreach($pages as $page)
		{
			$handle = $page['handle'];

			if($page['path'] != null)
			{
				$handle = $page['path'] . '/' . $handle;
			}
			$options[] = array($page['id'], ($this->_account['page'] == $page['id']), $handle);
		}
		$label->appendChild(Widget::Select('fields[page]', $options, array('id' => 'pages')));
		$div->appendChild($label);

		$label = Widget::Label(__('Parameters'));
		$value = '$field';
		if($this->_account['params'])
		{
			$value = $this->_account['params'];
		}
		$label->appendChild(Widget::Input('fields[params]', $value, 'text', array('id' => 'params')));
		$div->appendChild($label);

		$fieldset->appendChild($div);
		$fieldset->appendChild(new XMLElement('p', __('Choose the page handles, and add any extra parameters for the URL. \'$field\' represents the field you chose in step 2.'), array('class' => 'help')));

		$this->Form->appendChild($fieldset);

		$div = new XMLElement('div');
		$div->setAttribute('class', 'actions');
		$div->appendChild(Widget::Input('action[save]', ($this->_mode == 'edit' ? __('Save Changes') : __('Create Account')), 'submit', array('accesskey' => 's')));

		if($this->_context[0] == 'edit' || $this->_account['user_id']){
			$button = new XMLElement('button', __('Delete'));
			$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete this account'), 'type' => 'submit', 'accesskey' => 'd', 'data-message' => __('Are you sure you want to delete this account?')));
			$div->appendChild($button);
		}

		$this->Form->appendChild($div);
	}

	public function __prepareIndex()
	{
		// List the table columns
		$this->_table_columns = array(
			'screen_name'	=> array(__('Account'), true),
			'authors'	=> array(__('Authors'), true),
			'section'	=> array(__('Section'), true),
			'last-sent'	=> array(__('Last Sent'), true),
			'status'	=> array(__('Status'), true)
		);
		if (isset($_GET['sort']) && $_GET['sort'] && $this->_table_columns[$_GET['sort']][1]) {
			$this->_table_column = $_GET['sort'];
		}

		if (isset($_GET['order']) && $_GET['order'] == 'desc') {
			$this->_table_direction = 'desc';
		}
		$this->_pagination = (object)array(
			'page'		=> (
				isset($_GET['pg']) && $_GET['pg'] > 1
					? $_GET['pg']
					: 1
			),
			'length'	=> Symphony::Engine()->Configuration->get('pagination_maximum_rows', 'symphony')
		);

		$this->_accounts = Symphony::Database()->fetch("
			SELECT
				id,
				screen_name,
				section,
				authors,
				status
			FROM tbl_authors_twitter_accounts
			ORDER BY " . $this->_table_column . " " . strtoupper($this->_table_direction) . "
			LIMIT " . (($this->_pagination->page - 1) * $this->_pagination->length) . ", " . $this->_pagination->length . "
		");

		// Calculate pagination:
		$this->_pagination->start = max(1, (($this->_pagination->page - 1) * $this->_pagination->length));
		$this->_pagination->end = (
			$this->_pagination->start == 1
			? $this->_pagination->length
			: $start + count($this->_accounts)
		);
		$this->_pagination->total = count($this->_accounts);
		$this->_pagination->pages = ceil(
			$this->_pagination->total / $this->_pagination->length
		);
	}

	public function __viewIndex()
	{
		$this->setPageType('table');
		$this->setTitle(__('Symphony') . ' &ndash; ' . __('Twitter Accounts'));

		$this->appendSubheading(__('Twitter Accounts'), Widget::Anchor(
			__('Create New'), "{$this->_uri}/accounts/new/",
			__('Add a Twitter Account'), 'create button'
		));

		$thead = array();
		$tbody = array();
		$sections = array();
		$authors = array();

		// Columns with sorting
		foreach($this->_table_columns as $column => $values)
		{
			if($values[1])
			{
				if($column == $this->_table_column)
				{
					if($this->_table_direction == 'desc')
					{
						$direction = 'asc';
						$label = 'ascending';
					}
					else
					{
						$direction = 'desc';
						$label = 'descending';
					}
				}
				else
				{
					$direction = 'asc';
					$label = 'ascending';
				}

				$link = $this->generateLink(array(
					'sort'	=> $column,
					'order'	=> $direction
				));

				$anchor = Widget::Anchor(
					$values[0], $link,
					__("Sort by {$label} ") . strtolower($values[0])
				);

				if ($column == $this->_table_column) {
					$anchor->setAttribute('class', 'active');
				}

				$thead[] = array($anchor, 'col');
			}
			else
			{
				$thead[] = array($values[0], 'col');
			}
		}

		$SectionManager = new SectionManager(Symphony::Engine());

		foreach($SectionManager->fetch(NULL, 'ASC', 'sortorder') as $section)
		{
			$sections[$section->get('id')] = $section->get('name');
		}

		$AuthorManager = new AuthorManager(Symphony::Engine());

		foreach($AuthorManager->fetch() as $author)
		{
			$authors[$author->get('id')] = $author->get('first_name')." ".$author->get('last_name');
		}

		if(!is_array($this->_accounts) || empty($this->_accounts))
		{
			$tbody = array(
				Widget::TableRow(array(
					Widget::TableData(
						__('None Found.'), 'inactive', null, count($thead)
					)
				))
			);
		}
		else
		{
			foreach($this->_accounts as $account)
			{
				// Column 1
				$col_account = Widget::TableData(Widget::Anchor(
					$account['screen_name'],
					"{$this->_uri}/accounts/edit/{$account['id']}/"
				));
				$col_account->appendChild(Widget::Input(
					"items[{$account['id']}]",
					null, 'checkbox'
				));

				// Column 2
				$authors_arr = explode(',', $account['authors']);
				$author_str = '';
				foreach($authors_arr as $item)
				{
					$author_str .= $authors[$item] . ', ';
				}
				$col_author = Widget::TableData(trim($author_str, ', '));

				// Column 3
				$col_date = Widget::TableData(DateTimeObj::get(
					__SYM_DATETIME_FORMAT__, strtotime($account['date_last_sent'])
				));

				// Column 4
				$col_section = Widget::TableData($sections[$account['section']]);

				$col_status = Widget::TableData($account['status']);

				$tbody[] = Widget::TableRow(
					array(
						$col_account,
						$col_author,
						$col_section,
						$col_date,
						$col_status
					),
					null
				);
			}
		}

		$table = Widget::Table
		(
			Widget::TableHead($thead), null,
			Widget::TableBody($tbody)
		);
		$table->setAttribute('class', 'selectable');

		$this->Form->appendChild($table);

		$actions = new XMLElement('div');
		$actions->setAttribute('class', 'actions');

		$options = array(
			array(null, false, 'With Selected...'),
			array('delete', false, 'Delete', 'confirm'),
			array('status', false, 'Change Status')
		);

		$actions->appendChild(Widget::Select('with-selected', $options));
		$actions->appendChild(Widget::Input('action[apply]', 'Apply', 'submit'));

		$this->Form->appendChild($actions);

		// Pagination:
		if ($this->_pagination->pages > 1) {
			$ul = new XMLElement('ul');
			$ul->setAttribute('class', 'page');

			// First:
			$li = new XMLElement('li');
			$li->setValue(__('First'));

			if ($this->_pagination->page > 1) {
				$li->setValue(
					Widget::Anchor(__('First'), $this->generateLink(array(
						'pg' => 1
					)))->generate()
				);
			}

			$ul->appendChild($li);

			// Previous:
			$li = new XMLElement('li');
			$li->setValue(__('&larr; Previous'));

			if ($this->_pagination->page > 1) {
				$li->setValue(
					Widget::Anchor(__('&larr; Previous'), $this->generateLink(array(
						'pg' => $this->_pagination->page - 1
					)))->generate()
				);
			}

			$ul->appendChild($li);

			// Summary:
			$li = new XMLElement('li', __('Page %s of %s', array(
				$this->_pagination->page,
				max($this->_pagination->page, $this->_pagination->pages)
			)));
			$li->setAttribute('title', __('Viewing %s - %s of %s entries', array(
				$this->_pagination->start,
				$this->_pagination->end,
				$this->_pagination->total
			)));
			$ul->appendChild($li);

			// Next:
			$li = new XMLElement('li');
			$li->setValue(__('Next &rarr;'));

			if ($this->_pagination->page < $this->_pagination->pages) {
				$li->setValue(
					Widget::Anchor(__('Next &rarr;'), $this->generateLink(array(
						'pg' => $this->_pagination->page + 1
					)))->generate()
				);
			}

			$ul->appendChild($li);

			// Last:
			$li = new XMLElement('li');
			$li->setValue(__('Last'));

			if ($this->_pagination->page < $this->_pagination->pages) {
				$li->setValue(
					Widget::Anchor(__('Last'), $this->generateLink(array(
						'pg' => $this->_pagination->pages
					)))->generate()
				);
			}

			$ul->appendChild($li);
			$this->Form->appendChild($ul);
		}
	}

	public function __actionIndex()
	{
		$checked = (
			(isset($_POST['items']) && is_array($_POST['items']))
				? array_keys($_POST['items'])
				: null
		);

		if(is_array($checked) && !empty($checked))
		{
			switch ($_POST['with-selected'])
			{
				case 'delete':
					foreach ($checked as $id)
					{
						Symphony::Database()->query("
							DELETE FROM `tbl_authors_twitter_accounts` WHERE `id` = {$id}
						");
					}

					redirect("{$this->_uri}/accounts/");
					break;

				case 'status':
					foreach($checked as $id)
					{
						$account = Symphony::Database()->fetch("
							SELECT `status` FROM `tbl_authors_twitter_accounts` WHERE `id` = {$id}");
						$status = ($account[0]['status'] == 'Active') ? 'Inactive' : 'Active';
						Symphony::Database()->query("
							UPDATE `tbl_authors_twitter_accounts` SET `status` = '{$status}' WHERE `id` = {$id}
						");
					}
			}
		}
	}

	public function __actionEdit()
	{
		if(array_key_exists('save', $_POST['action'])){

			$authors = trim(implode(',',$_POST['fields']['authors']),',');

			Symphony::Database()->query("
				UPDATE
					`" . $this->_driver->table . "`
				SET
					`section` = {$_POST['fields']['section']},
					`field_param` = {$_POST['fields']['field_param']},
					`field_msg` = {$_POST['fields']['field_msg']},
					`page` = {$_POST['fields']['page']},
					`params` = '{$_POST['fields']['params']}',
					`authors` = '{$authors}',
					`status` = 'Active'
				WHERE
					`id` = {$this->_account['id']}
			");
			header('Location: ' . URL . '/symphony/extension/twitternotifier/accounts/edit/' . $this->_account['id'] . '/saved/');
		}
		if(array_key_exists('delete', $_POST['action']))
		{
			Symphony::Database()->query("
				DELETE FROM `" . $this->_driver->table . "` WHERE `id` = '" . $this->_account['id'] . "'
			");
			header('Location: ' . URL . '/symphony/extension/twitternotifier/accounts/');
		}
	}

	public function __actionNew()
	{
		if(array_key_exists('save', $_POST['action'])){

			$authors = trim(implode(',',$_POST['fields']['authors']),',');

			Symphony::Database()->query("
				UPDATE
					`" . $this->_driver->table . "`
				SET
					`section` = {$_POST['fields']['section']},
					`field_param` = {$_POST['fields']['field_param']},
					`field_msg` = {$_POST['fields']['field_msg']},
					`page` = {$_POST['fields']['page']},
					`params` = '{$_POST['fields']['params']}',
					`authors` = '{$authors}',
					`status` = 'Active'
				WHERE
					`id` = {$this->_account['id']}
			");
			header('Location: ' . URL . '/symphony/extension/twitternotifier/accounts/edit/' . $this->_account['id'] . '/saved/');
		}

		if(array_key_exists('delete', $_POST['action']))
		{
			Symphony::Database()->query("
				DELETE FROM `" . $this->_driver->table . "` WHERE `id` = '" . $this->_account['id'] . "'
			");
			header('Location: ' . URL . '/symphony/extension/twitternotifier/accounts/');
		}
	}


	public function generateLink($values)
	{
		$values = array_merge(array(
			'pg'	=> $this->_pagination->page,
			'sort'	=> $this->_table_column,
			'order'	=> $this->_table_direction
		), $values);

		$count = 0;
		$link = Symphony::Engine()->getCurrentPageURL();

		foreach ($values as $key => $value) {
			if ($count++ == 0) {
				$link .= '?';
			}

			else {
				$link .= '&amp;';
			}

			$link .= "{$key}={$value}";
		}

		return $link;
	}
}

?>
