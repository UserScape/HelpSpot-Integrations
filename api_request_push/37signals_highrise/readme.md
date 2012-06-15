# 37Signals Highrise

This is a sample Request Push API file for the CRM tool, Highrise. This example is functional as is, but could also be expanded upon as needed for your particular needs. By adding this file to your HelpSpot installation you'll allow staff members in HelpSpot to push a customer/person/contact directly into Highrise. The script will also check that you're not adding a person who already exists so that duplicates are not created.

## Installation

* Open the file in a text editor and edit the following 2 variables:
* $highrise_url - The URL to your Highrise installation
* $api_token - An API token from your "my info" page
* Upload the file into HelpSpot's /custom_code folder

That's it, you'll now be able to push any existing request from HelpSpot into Highrise.