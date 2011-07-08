<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/twitternotifier/lib/twitteroauth/twitteroauth.php');

class contentExtensionTwitterNotifierAccounts extends AdministrationPage
{
	protected $_driver = null;
	protected $_uri = null;
	protected $_account = null;
	protected $_accounts = null;

	protected $_pagination = null;
	protected $_table_column = null;
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
					$this->__prepareNew();
				break;
				case 'edit':
					$this->__prepareEdit($context);
				break;
			}
		}
		parent::build($context);
	}

	public function __prepareNew()
	{
		$this->_uri .= "/connect/account/";

		$this->_accounts = Symphony::Database()->fetch("
			SELECT * FROM `".$this->_driver->table."`
		", 'id');

		// var_dump($this->_accounts); TODO remove
	}

	public function __prepareEdit($context)
	{
		$this->_uri .= "/connect/account/" . $context[1] . "/";
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
		$this->appendSubheading(__('Create/Edit Twitter Account'));

		$fieldset = new XMLElement('fieldset');
		$fieldset->setAttribute('class', 'settings');
		$fieldset->appendChild(new XMLElement('legend', __('Step 1: Sign in at Twitter')));

		$img = new XMLElement('input');
		$img->setAttribute('type', 'image');
		$img->setAttribute('src', URL . '/extensions/twitternotifier/assets/sign-in-with-twitter.png');
		$img->setAttribute('alt', __('Sign in with Twitter'));
		$img->setAttribute('value', $this->_uri);
		$img->setAttribute('id', 'twitter_connect');

		$label = Widget::Label(__('Sign yourself in at Twitter with the account you want to set up.'));
		$label->appendChild($img);

		$fieldset->appendChild($label);

		$this->Form->appendChild($fieldset);

		$div = new XMLElement('div');
		$div->setAttribute('class', 'group');

		$label = Widget::Label(__('Screen Name'));
		$label->appendChild(Widget::Input('fields[screen_name]', $this->_account['screen_name'], 'text', array('readonly' => 'readonly')));
		$div->appendChild($label);

		$label = Widget::Label(__('Twitter User ID'));
		$label->appendChild(Widget::Input('fields[uesr_id]', $this->_account['user_id'], 'text', array('readonly' => 'readonly')));
		$div->appendChild($label);

		$fieldset->appendChild($div);

	// Build Details Fieldset

		$fieldset = new XMLElement('fieldset');
		$fieldset->setAttribute('class', 'settings');
		$fieldset->appendChild(new XMLElement('legend', __('Step 2: Choose Notification Details')));

		$div = new XMLElement('div');
		$div->setAttribute('class', 'group');

		$label = Widget::Label(__('Notifying Section'));
		$SectionManager = new SectionManager($this->_Parent);
		$options = array();
		foreach($SectionManager->fetch(NULL, 'ASC', 'sortorder') as $section)
		{
			$options[] = array($section->get('id'), ($fields['section'] == $section->get('id')), $section->get('name'));
		}
		$label->appendChild(Widget::Select('fields[section]', $options, array('id' => 'section')));
		$div->appendChild($label);

		$label = Widget::Label(__('Parameter Value'));
		$FieldManager = new FieldManager($this->_Parent);
		$options = array();
		foreach($FieldManager->fetch(NULL, NULL, 'ASC', 'sortorder') as $field)
		{
			$options[] = array($field->get('id'), ($fields['field'] == $field->get('id')), $field->get('label'), 'section-id-'.$field->get('parent_section'));
		}
		$label->appendChild(Widget::Select('fields[field]', $options, array('id' => 'fields')));
		$div->appendChild($label);

		$fieldset->appendChild($div);

		$this->Form->appendChild($fieldset);


	}
	public function __viewIndex()
	{
		// List the table columns
		$this->_table_columns = array(
			'account'	=> array(__('Account'), true),
			'author'	=> array(__('Author'), true),
			'sections'	=> array(__('Sections'), true),
			'last-sent'	=> array(__('Last Sent'), true),
			'status'	=> array(__('Status'), true)
		);

		// Begin pagination
		if(isset($_GET['sort']) && $_GET['sort'] && $this->_table_columns[$_GET['sort']][1])
		{
			$this->_table_columns = $_GET['sort'];
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

		$accounts = Symphony::Database()->fetch("
			SELECT
				id,
				screen_name,
				section,
				author,
				status
			FROM tbl_authors_twitter_accounts
		");

		// Calculate pagination:
		$this->_pagination->start = max(1, (($page - 1) * 17));
		$this->_pagination->end = (
			$this->_pagination->start == 1
			? $this->_pagination->length
			: $start + count($this->_importers)
		);
		$this->_pagination->total = count($accounts);
		$this->_pagination->pages = ceil(
			$this->_pagination->total / $this->_pagination->length
		);


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

		$SectionManager = new SectionManager($this->_Parent);

		foreach($SectionManager->fetch(NULL, 'ASC', 'sortorder') as $section)
		{
			$sections[$section->get('id')] = $section->get('name');
		}

		$AuthorManager = new AuthorManager($this->_Parent);

		foreach($AuthorManager->fetch() as $author)
		{
			$authors[$author->get('id')] = $author->get('first_name')." ".$author->get('last_name');
		}

		if(!is_array($accounts) || empty($accounts))
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
			foreach($accounts as $account)
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
				$col_author = Widget::TableData($authors[$account['author']]);

				// Column 3
				$col_date = Widget::TableData(DateTimeObj::get(
					__SYM_DATETIME_FORMAT__, strtotime($account['date_last_sent'])
				));


				$account_sections = '';
				$section_ids = (is_array($account['sections'])) ? explode(',',$account('sections')): array($account['sections']);
				foreach($section_ids as $section_id)
				{
					$account_sections .= $sections[$section_id].', ';
				}
				$col_sections = Widget::TableData(trim($account_sections,", "));

				$col_status = Widget::TableData($account['status']);

				$tbody[] = Widget::TableRow(
					array(
						$col_account,
						$col_author,
						$col_sections,
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
