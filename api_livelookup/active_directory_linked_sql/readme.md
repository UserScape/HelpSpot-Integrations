# Active Directory via Linked SQL Server

Contributed by: Richard Edwards of [DNN Stuff](http://www.dnnstuff.com/).

This implementation explains how to connect to Active Directory via a link through Microsoft SQL Server rather than directly via PHP's LDAP functions. Using this linked method has shown significant performance improvements when querying for Live Lookup results.

## Configuration

* Create a linked server on MS SQL Server by issuing the following command: sp_addlinkedserver 'ADSI', 'Active Directory Service Interfaces', 'ADSDSOObject', 'adsdatasource'
* In SQL Enterprise Manager, go to Security, Linked Servers, ADSI and right click, choose properties
* On Security tab, select the last option 'Be made with this security context' and enter an account that has access to read Active Directory, click OK.
* Put the live_lookup_ad_mssql_linkedserver.php file (downloadable below) into your HelpSpot custom_code directory
* Edit the file and change the parameters for $myServer, $myUser, $myPass to point to your MS SQL server. Also, edit line 61 (the AD query) to use your domain. For example change DC=example,DC=com to OU=my org unit,DC=mydomain,DC=net if you wanted to just search the organizational unit 'my org unit' for the domain 'mydomain.net'. The query on lines 60-62 determines the scope of the search.
* In HelpSpot under Admin->Settings change the Live Lookup URL to point to this file