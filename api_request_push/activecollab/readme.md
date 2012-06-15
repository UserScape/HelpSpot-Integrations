# 37Signals Campfire

Contributed by: Tara Kelly of [Passpack](http://www.passpack.com)

This is a sample Request Push API file for the project management tool ActiveCollab. This example is functional as is, but could also be expanded upon as needed for your particular installation. By adding this file to your HelpSpot installation you'll allow staff members in HelpSpot to push a request directly into ActiveCollab to create a new ticket.

## Installation

* Open the file in a text editor and edit the following 4 variables:
* $acollab_projid - The project ID to add a ticket to
* $acollab_token - Your API token
* $acollab_url - ActiveCollab URL from the API token page
* Upload the file into HelpSpot's /custom_code folder

That's it, you'll now be able to push any existing request from HelpSpot into Campfire.