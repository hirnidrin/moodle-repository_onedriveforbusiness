## Synopsis

This is a [**Moodle**](www.moodle.org) repository plugin to access Microsoft **OneDrive for Business** online storage, which is part of the Microsoft Office 365 Business / Enterprise / Education license plans. The plugin uses OAuth 2 to authenticate with MS Azure Active Directory, and the Office 365 REST API to get the files.

## Motivation

Moodle currently has a SkyDrive repository plugin which works for personal OneDrive accounts. However, it fails when trying to authenticate and access resources in Office 365 enterprise accounts.

## Installation

#### Register your Moodle site to use Azure AD as authentication provider

* With your Office 365 organization admin credentials, login and create an Azure account on http://azure.microsoft.com/ (one-month trial). The Azure account will be linked to your Office 365 account, and you will find all your current Office 365 users already existing in your brand new Azure AD.
* In Azure, go to Active Directory, and select the already existing directory (which represents your Office 365 users).
* Go to Applications, and add a new application (click Add at the bottom of the page). Initial setup:
  * What do you want to do: "Add an application my organization is developing"
  * Enter a friendly name, like "Moodle", and select type "Web application and/or Web API"
  * Sign-on URL: Enter the URL of you Moodle site.
  * App ID URI: This just serves as an unique identifier, and we do not need it later. You can enter the URL of you Moodle site.
* Now that the application is created, go to Configure and fill in the details:
  * Application is multi-tenant: No
  * Client ID: copy this, you need it later.
  * Keys: use the dropdown to select a duration (1 or 2 years) -> this will generate a key / password / client secret that you need later, together with the client ID. Copy the key NOW, you cannot access it anymore later. If you lose it, you need to generate a new key.
  * Reply URL: remove the existing URL, and add your Moodle site specific OAuth callback URL "admin/oauth2callback.php", for example: http://moodle.mydomain.com/admin/oauth2callback.php -- Note: It is not required that your Moodle site is reachable from the public internet. If you run an intranet-only Moodle site, just enter the intranet callback URL.
  * Permissions to other applications:
    * Windows Azure AD > Delegated Permissions: Enable sign-on and read users profiles
    * Office 365 Sharepoint Online > Delegated Permissions: Read users files / Read items in all site collections (both are required)
  * Click Save at the bottom
* Done :)

#### Install, configure and test the plugin

* In your Moodle installation, create the folder "onedriveforbusiness" as a subfolder of the existing "repository" folder.
* Copy the plugin code into it.
* Login with your Moodle admin user. You will get a message about the newly found plugin -> install it.
* Go to Site administration > Plugins > Repositories > Manage repositories
* Set the "Microsoft OneDrive for Business" to "Enabled and visible"
* Click on "Settings":
  * Client ID, Client secret: fill these fields with the values from above.
  * Login hint: The text in this field will show up in the username field when trying to login to OneDrive for Business. For example, you could set it to @myoffice365domain.com
* Save, and done :)
* Test: In the Moodle file picker, the OneDrive for Business repository should show up. Click on it, login and download a file.

## Contributors

Sponsored by: BWZ Brugg, Switzerland - many thanks!<br/>
Author and copyright: 2014 Werner Urech

## License

The plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY. Use at your own risk. 

It is released under the same license as the Moodle system itself:<br/>
http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
