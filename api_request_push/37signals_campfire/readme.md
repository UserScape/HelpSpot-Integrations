# 37Signals Campfire

This is a sample Request Push API file for the group chat tool, Campfire. This script by default logs the staff comment to a Campfire chat room, though it could be modified to include other information like the initial request note, status, category and so on.

This script is particularly useful to use with Automation Rules. You can have an Automation Rule use this Request Push script as it's action, so each time a request meets a certain set of conditions post a note in your Campfire account.

## Installation

* Open the file in a text editor and edit the following 4 variables:
* $campfire_url - The full URL to your account. ex: http://[account].campfirenow.com
* $campfire_username - The Campfire user you want to log messages as. (usually an email address)
* $campfire_password - The users Campfire password
* $campfire_roomid - The room ID we want to log to. You can find this in the URL of the room when you enter it. ex: /room/182650 - 182650 is the ID
* Upload the file into HelpSpot's /custom_code folder

That's it, you'll now be able to push any existing request from HelpSpot into Campfire.