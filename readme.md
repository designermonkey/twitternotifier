#Twitter Notifier

Version: 1.0

Author: John Porter <john.porter@designermonkey.co.uk>

Release Date: n/a

##Installation

Simply put, don't. It's not finished yet, but if you must:

1.	Upload the 'twitternotifier' folder in this archive to your Symphony 'extensions' folder.

2.	Enable it by accessing your administration panel and navigating to 'System -> Extensions'.
3.	Select the 'Twitter Notifier' entry by clicking on it and select 'enable' in the 'with-selected' dropdown box. Click the 'Apply' button.

4.	Go to 'System -> Preferences' and add your Twitter Application details.
	To register an application for your site, go to http://dev.twitter.com/login and follow instructions.

##Usage

A new menu option is available under Preferences called 'Twitter Accounts' where authors can register Twitter accounts to monitor sections of Symphony and post when new entries are made by specified Authors. The account creation process is as follows:

1.	Click on 'Create New' to open the new entry page.

2.	Sign in at Twitter to authenticate the website and application with the account.

3.	Choose the section, and it's fields, one field is where the value will be taken from that Twitter will link to in the URL. The other is for the message to start the tweet with. The message will be truncated and the link added.

4.	Select the Page your entries will display under.

5.	**Advanced:** If you require any additional Url parameter values, use the text input field to add them. `$field` in this box represents the parameter field you chose earlier, and is required for the notification process to target a specific article.

##Information

If you want more than one section to notify the same Twitter account, you will need to input the Twitter account more than once. This problem will hopefully be addressed in the future once the extension has been in production for some time.

##Planned Features

1.	Logging of Twitter Notifications. All notifications logged with Tracker, will ask Craig for help on this one [Need Help]

2.	Event. To post on user comments or other frontend submission. Should this just be a filter? [Hard]

3.	Create Datasource. An action in the accounts index, and a button on the edit/saved page to create a datasource for the account, of the status feed. Will utilise the dynamic datasource functions of Symphony. [Hard]

4.	Members integration. Create the Twitter Accounts menu item as a new main nav item with sub pages for Authors and Members. Allow account addition for registered members. This is just an idea, and use cases need thinking of to justify it. [Too Hard For Me!]

##Change Log

v1.0 - Initial Release.