Hyperplanning Importation Tool
==============================

[![Build Status](https://travis-ci.org/call-learning/moodle-tool_hyperplanningsync.svg?branch=master)](https://travis-ci.org/call-learning/moodle-tool_hyperplanningsync)


This tool is aiming to import users into the right cohort and course group depending on
a CSV (and later API call) from Hyperplanning. 


Installation and configuration notes
==

A new table is created - mdl_tool_hyperplanningsync_log
A capability is created - tool/hyperplanningsync:manage


There is a settings page for the default column names at Site admin > Users > Accounts > Hyperplanning Sync > Settings

Or direct to   `/admin/settings.php?section=tool_hyperplanningsync_settings`

There are also menus for import csv and import log Site admin > Users > Accounts > Hyperplanning Sync > Import CSV

    /admin/tool/hyperplanningsync/index.php

Site admin > Users > Accounts > Hyperplanning Sync > Import log

    /admin/tool/hyperplanningsync/viewlog.php

Access will require the capability - tool/hyperplanningsync:manage


Testing
==

Go to the first page and upload a csv

It should give errors if the field names are missing or not matching the field names.
It will then import the file along with a status for each row. If the status is empty then its ready to be processed.
On the preview page, there are options to remove the existing cohorts and groups.
Clicking process will process the rows adding/removing cohorts and groups
Then redirects to the import log view - filtered for the current import id.
When looking for a cohort or group, the code will try to look for the idnumber first, if that is empty then it will look for the name.

There is also some further testing in the testing folder.

That's it!
