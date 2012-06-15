# FogBugz

This is a sample Request Push API file for the bug tracker FogBugz. This example is functional as is, but could also be expanded upon as needed for your particular installation. By adding this file to your HelpSpot installation you'll allow staff members in HelpSpot to push a request directly into FogBugz as well as check on the progress of that newly created case from within HelpSpot.

## Installation

* Open the file in a text editor and edit the following 3 variables:
* $fb_url - The URL to your FogBugz installation
* $fb_user_email - The email of the user who should be used to login to the API
* $fb_user_password - The password of the user who should be used to login to the API
* Upload the file into HelpSpot's /custom_code folder

That's it, you'll now be able to push any existing request from HelpSpot into FogBugz.