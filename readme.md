#Twitter Notifier

Version: 2alpha
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

2.	Sign in at Twitter to authenticate the website with your account.

3.	Choose the section, and it's field. The field chosen is where the value will be taken from that Twitter will link to.

4.	Select the Page your entries will display under.

5.	If you require any additional Url parameter values, use the text input field to add them. `$field` in this box represents the field you chose earlier, and is required for the notification process to target a specific article. To igonre specific entry targeting, don't add `$field`. All links will resolve to this url.

##Information

If you want more than one section to notify the same Twitter account, or more than one Author, you will need to input the Twitter account more than once. This problem will hopefully be addressed in the future once the extension's core has been tested and is bug free.
