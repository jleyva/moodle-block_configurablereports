# Changelog

All notable changes to the Configurable Reports block, newest first. Entries up to
4.5.0 were kept in `README.txt`, which this file replaces.

## 5.3.2 (2027050404) for Moodle 4.0 and later

_Release date: ..2026_

A security review of the whole plugin thanks to MDL Shield, and the first acceptance tests.

- Permissions are decided in the course a report belongs to, in the component and
  plugin editors, the report data web service and `send_emails.php`, so the
  capabilities a user holds in one course no longer reach another one's reports.
- Adding a plugin to a report, importing a report from the repository and the
  repository endpoints require a sesskey and, for SQL reports, the
  `managesqlreports` capability.
- The report query is only dumped in debug mode for users who may manage SQL
  reports, and the SQL keyword check now also runs when a report runs, not only
  when it is saved.
- Imported reports are validated: only known fields, report types and export
  formats are kept, components and plugin data are checked before they run, and
  unserialising only allows `stdClass`.
- Report data, author-entered values and export links are escaped or cleaned
  before they are displayed, and `%%FILTER_VAR%%` only accepts the columns the
  report author allows.
- Filter values are bound as SQL parameters, TLS certificates are verified when
  fetching reports from GitHub, scheduled SQL reports run from a scheduled task,
  and the bundled Tahoma font was replaced with the OFL licensed Cairo font.
- Added Behat tests covering every report type, the advanced components and the
  security checks, plus PHPUnit tests for the filters, the import path and the
  web service.
- SQL reports cut short by the report row limit now say so, instead of leaving
  rows out silently (#121).
- The search text filter matches "0" again, instead of reading it as no filter
  at all (#242).
- Deleting a component from the middle of the list no longer breaks the move
  arrows for the ones after it (#220).
- The user field ordering summary falls back to the column name for fields core
  has no string for, such as id and auth (#191).
- The date filters are read with optional_param_array(), so the user and course
  statistics columns and the timeline report no longer throw on Moodle 4.5 and
  later (#278).
- Exporting a report no longer trips a PHP deprecation when a column is NULL
  (#267).
- The bundled DataTables styles are scoped to the report page, so they no longer
  restyle the tables of other plugins across the site (#266).
- The user field filter applies values longer than 48 characters again, and keeps
  the chosen one selected after Apply (#190).
- A user field filter left empty no longer leaves its placeholder behind in the
  query, where Moodle counted the column inside it as a named parameter (#299).
- Reports are deleted together with their course, instead of lingering in the
  list with no way to open or remove them (#232).
- The remote column is added to the sites that skipped the original upgrade step,
  so the database schema check passes on them again (#228).
- environment.xml declares the PHP version the code actually needs, and warns when
  GD cannot draw the text on the graphs (#233).
- The charts no longer carry a PHP 8.1 deprecation notice drawn into the image,
  which affected the bar charts as well (#259).
- The pie and line charts are drawn with pChart2, as the bar chart already was, so
  the unmaintained pChart 1.x is gone from the plugin (#259).
- The line chart legend names its series again, rather than showing Serie0 and Serie1.
- A query is only refused for naming a table explicitly when it really does, rather
  than whenever a word happens to begin with the database prefix (#145).
- A filter_var that cannot be used raises an error instead of being dropped in
  silence, which left the report showing every row it was meant to narrow
  (CONTRIB-10669).
- Fixed the spelling of "accessible" on the report settings page (CONTRIB-8676).
- The user field filter on a SQL report no longer loads every user to build its
  options, which hung large sites and exceeded the database parameter limit
  (CONTRIB-4937).
- Columns that core has no name for are shown as themselves in the filters,
  conditions and ordering, rather than as [[sortorder]] and a debugging message.

## 5.3.1 (2027050403) for Moodle 4.0 and later

_Release date: 19.10.2026_

- Fixed the search text filter removing more than its own placeholder (#295).
- Reported SQL errors in low security mode, with regression tests (#294).
- Reworked the user field filter: custom profile fields, aliased columns, meta
  types, `sql_like()` and the contains operator (#296, #297).
- Supported `IN`, `NOT IN` and `BETWEEN` in `%%FILTER_VAR%%`, and hardened it
  against SQL injection in the clause.
- Fixed the course modules filter placeholders and restored wildcards in
  contains conditions.
- Added unit tests for the SQL report filters, conditions and components.

## 5.3.0 (2027050402) for Moodle 4.0 and later

_Release date: 07.10.2026_

- Threw an exception from the web service when the permission check fails
  (CONTRIB-10664).
- Added the Moodle Playground demo blueprint and README badge.
- Bug fixes and improvements.

## 5.2.0 (2027050401) for Moodle 4.0 and later

_Release date: 04.05.2026_

- Updated the supported branches and the minimum Moodle version.
- Set the capabilities risk bit mask (#286).
- Replaced the deprecated `get_all_user_name_fields()` (CONTRIB-9748, #247).
- Fixed the search text `~` operator to be case insensitive on PostgreSQL.
- Fixed the RTL cursor issue in the editor.

## 4.5.0 (2024051300) for Moodle 4.5
_Release date: 06.10.2024_

### What's Changed
* fix webservice calls when missing user by @danielneis in https://github.com/jleyva/moodle-block_configurablereports/pull/250
* Issue with downloading a filtered report by @luukverhoeven in https://github.com/jleyva/moodle-block_configurablereports/pull/248
* Apply format_string on the breadcrumb navigation by @golenkovm in https://github.com/jleyva/moodle-block_configurablereports/pull/181
* csv delimiter settings by @Tsheke in https://github.com/jleyva/moodle-block_configurablereports/pull/214
* Set current courseid when importing a custom SQL report. by @reskit in https://github.com/jleyva/moodle-block_configurablereports/pull/223
* Fixes jleyva/moodle-block_configurablereports#147 by @marcelorhmaia in https://github.com/jleyva/moodle-block_configurablereports/pull/231
* User Profile Fields Language Filter Fix by @sameer-ah in https://github.com/jleyva/moodle-block_configurablereports/pull/189
* Fix 201: Process report names / filenames through Moodle filters by @michael-milette in https://github.com/jleyva/moodle-block_configurablereports/pull/238
* FIX-226: Stop getting string from report_customsql plugin by @michael-milette in https://github.com/jleyva/moodle-block_configurablereports/pull/227
* Set BOM for CSV export closes #198 by @mvaraujo in https://github.com/jleyva/moodle-block_configurablereports/pull/199
* Provide support for SYLK export #200 by @mvaraujo in https://github.com/jleyva/moodle-block_configurablereports/pull/203
* Fix issue #62 Error with apostrophe by @TomoTsuyuki in https://github.com/jleyva/moodle-block_configurablereports/pull/211
* Export multilanguage support by @Tsheke in https://github.com/jleyva/moodle-block_configurablereports/pull/215
* Add embed options by @keevan in https://github.com/jleyva/moodle-block_configurablereports/pull/182
* fix variable redeclaration by @danielneis in https://github.com/jleyva/moodle-block_configurablereports/pull/256

### New Contributors
* @luukverhoeven made their first contribution in https://github.com/jleyva/moodle-block_configurablereports/pull/248
* @golenkovm made their first contribution in https://github.com/jleyva/moodle-block_configurablereports/pull/181
* @Tsheke made their first contribution in https://github.com/jleyva/moodle-block_configurablereports/pull/214
* @reskit made their first contribution in https://github.com/jleyva/moodle-block_configurablereports/pull/223
* @marcelorhmaia made their first contribution in https://github.com/jleyva/moodle-block_configurablereports/pull/231
* @sameer-ah made their first contribution in https://github.com/jleyva/moodle-block_configurablereports/pull/189
* @TomoTsuyuki made their first contribution in https://github.com/jleyva/moodle-block_configurablereports/pull/211
* @keevan made their first contribution in https://github.com/jleyva/moodle-block_configurablereports/pull/182

## 4.1.0 (2023120600) for Moodle 4.1
_Release date: 06.12.2023_
This release focuses on solving issues in Moodle 4.1 and PHP 7.4 and start with small improvements and bug fixes.
- Reformat code
- Add CI testing based on Github Actions created by Catalyst IT
- Add PHP 7.4 support - minimum PHP version is now 7.4
- Add PHP 8.0 support
- Add PHP 8.1 support
- pChart library updated to version 2.4.0
- JS tablesorter library updated to version 2.31.3
- JS CodeMirror library updated to version 5.65.16
- Move repository to https://github.com/Lesterhuis-Training-en-Consultancy/moodle-block_configurablereports
- Original repository (https://github.com/jleyva/moodle-block_configurablereports) can be used for older release or use the old branches within this repository
- removed cr_add_to_log not used anymore
- Important this branch is not backwards compatible with Moodle 3.11 and lower

Thanks to Lesterhuis Training & Consultancy for the contribution / updated by Ldesign Media

- TODOS for the future:
  * Namespaces for classes & autoloading
  * Move all to `classes` to `classes/` directory
  * Clean up code
  * Add tests
  * AJAX_SCRIPT move to webservice
  * unserialize() to json_decode()
  * Readme to Markdown format
  * validation if a class exists and inherits the correct parent class.
  * Changelog to separate file
  * Move cron to a scheduled task
  * phpcpd and phpdocs checks in CI
  * Make CSS strict should only affect this plugin!

## 3.9.0 (2019122000) for Moodle 3.4, 3.5, 3.6, 3.7, 3.8, 3.9
_Release date: Tuesday, 3 November 2020_
- Starting this version for allowing SQL queries performing data insertion/creation the following configuration variable set to 1
  is required in your site root /config.php:
  $CFG->block_configurable_reports_enable_sql_execution = 1;
  Otherwise, previous Custom SQL reports performing data insertion/creation will stop working.
- Added matching colors to pie charts
- Added unmapped palette for general colors to pie charts
- Changed fuserfield to allow for multiple instances of the filter
- Added alphabetical sort to courses filter
- Added webservice to get reports data
- Added new filters for competencies
- Other fixes and improvements:
  * Fix offset error for bar graphs when not using SQL reporting
  * Removed default sorting from DataTables JS UI
  * Fixed issue where colors would mismatch if values weren't present
  * Fixed STARTTIME and ENDTIME variable substitution
  * Avoid text filtering when exporting

Thanks Alex Rowe, David Saylor, Michael Gardener, Muhammad Osama Arshad, Daniel Poggenpohl, Daniel Neis, François Parlant and all the contributors who have sent several fixes and improvements.

## 3.8.0 (2019122000) for Moodle 3.4, 3.5, 3.6, 3.7, 3.8
_Release date: Friday, 20 Dec 2019_
- Order the list of users in users filter by fullname considering language - #130
- Other fixes and improvements:
  * Fix template array error - #132
  * Fix DB mismatch between install.xml and upgrade.php - #123
  * Fix LIMIT error for MSSQL - CONTRIB-7891
  * Remove "_" from the table header titles - #137
  * Review supported Moodle versions, because the core_userlist_provider interface is only supported from 3.4 onwards - #125

Thanks Daniel Neis (danielneis), Mike McDougal (mcdoogs), Tuan Ngoc Nguyen (tuanngocnguyen), Mike Henry (mhenry79mnet), safatshahinsd, kristian-94 and all the contributors who have sent several fixes and improvements.
And special thanks also to Carlos, Moodle HQ and Juan for letting Sara work on this again during the project week.

## 3.3.0 (2019121900) for Moodle 3.0, 3.1, 3.2, 3.3
_Release date: Friday, 20 Dec 2019_
- Order the list of users in users filter by fullname considering language - #130
- Add a new calculation type: percentage
- Allow userfields to be used more than once in the permissions
- Move JS to AMD modules
- Upgrade some 3rd party JS libraries
- Fix import from XML
- Review the year filter to take min and max years from the calendar factory
- Other fixes and improvements:
  * Fix template array error - #132
  * Fix DB mismatch between install.xml and upgrade.php - #123
  * Fix LIMIT error for MSSQL - CONTRIB-7891
  * Remove "_" from the table header titles - #137
  * Review supported Moodle versions, because the core_userlist_provider interface is only supported from 3.4 onwards - #125
  * Fix error when using templates
  * Fix error when using the Start/End filter
  * Fix legacy_polyfill error when running unit tests
  * Display custom title for the block
  * Replace deprecated methods (htmleditor, pix_url and coursecat)
  * Remove CSS files not found error in JS console
  * Show breadcrumbs for users without manage report capability
  * Fix DB query function call for the enrolled students filter
  * Raise the memory limit when exporting
  * Clean some request parameters before using them
  * Fix error with user field search box filter
  * Improve compatibility with PostgreSQL when importing

Thanks Daniel Neis (danielneis), Mike McDougal (mcdoogs), Tuan Ngoc Nguyen (tuanngocnguyen), Mike Henry (mhenry79mnet), safatshahinsd, kristian-94, David (davidpesce), Dimitrii (dmitriim), Alex (agrowe), Donald (emyb2), sopnep15, Danniel (dannielarriola) and all the contributors who have sent several fixes and improvements.
And special thanks also to Carlos, Sander, Moodle HQ and Juan for letting Sara work on this again during the project week.

## 3.7.0 (2019060300) for Moodle 3.4, 3.5, 3.6, 3.7
_Release date: Monday, 3 Jun 2019_
- Add a new calculation type: percentage
- Allow userfields to be used more than once in the permissions
- Other fixes and improvements:
  * Fix error when using templates
  * Fix error when using the Start/End filter
  * Fix legacy_polyfill error when running unit tests
  * Display custom title for the block

Thanks David (davidpesce), Dimitrii (dmitriim) and all the contributors who have sent several fixes and improvements.
And special thanks also to Sander, Moodle HQ and Juan for letting Sara work on this again during the project week.

## 3.6.0 (2019021500) for Moodle 3.4, 3.5, 3.6
_Release date: Friday, 15 Feb 2019_
- Implement Privacy API
- Replace deprecated methods:
  * htmleditor
  * pix_url
  * coursecat
- Move JS to AMD modules
- Upgrade some 3rd party JS libraries
- Fix import from XML
- Review the year filter to take min and max years from the calendar factory
- Other fixes and improvements:
  * Remove CSS files not found error in JS console
  * Show breadcrumbs for users without manage report capability
  * Fix DB query function call for the enrolled students filter
  * Raise the memory limit when exporting
  * Clean some request parameters before using them
  * Fix error with user field search box filter
  * Improve compatibility with PostgreSQL when importing

Thanks Alex (agrowe), Donald (emyb2), sopnep15, Danniel (dannielarriola) and all the contributors who have sent several
fixes and improvements.
And special thanks also to Sander, Moodle HQ and Juan for letting Sara work on this during the project week.

## 3.1.1 (2016020103) for Moodle 3.0, 3.1
_Release date: Monday, 10 Oct 2016_
- Fix a critical bug when adding user action/outline statistics, see https://tracker.moodle.org/browse/CONTRIB-4600

Thanks Marina Glancy for the fix

## 3.1.0 (2016020102) for Moodle 3.0, 3.1
_Release date: Wednesday, 25 May 2016_
- Several bug fixes

## 3.0.1 (2016020101) for Moodle 3.0
## 2.3.9 (2011040121) for Moodle 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9
_Release date: Wednesday, 17 Feb 2016_
- Fix bug when creating the category list

## 3.0.0 (2016020100) for Moodle 3.0
_Release date: Tuesday, 02 Feb 2016_
- Compatible version with Moodle 3.0

## 2.3.8 (2011040120) for Moodle 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9
_Release date: Tuesday, 02 Feb 2016_
- Release including bug fixes

## 2.3.7 (2011040119) for Moodle 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9
_Release date: Monday, 03 Aug 2015_
- Several bug fixes
- Fixed installation for old moodle versions
- Improved pie chats

Thanks CV&A and Carlos Escobedo for the contribution

## 2.3.6 (2011040118) for Moodle 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9
_Release date: Wednesday, 27 May 2015_
- Minor release fixing a regression

Thanks to Dan Marsden for the fix

## 2.3.5 (2011040116) for Moodle 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9
_Release date: Wednesday, 06 May 2015_
- Added support for Moodle 2.8 and 2.9
- Added support to the new logging system
- Improved the color palette for graphics with lots of elements
- Allow translations of DataTable strings
- Restored the fsearchuserfield plugin
- Minor issues fixes

Thanks albergasset and jpeak5 for their fixes

## 2.3.4 (2011040114) for Moodle 2.2, 2.3, 2.4, 2.5, 2.6, 2.7
_Release date: Thursday, 26 June 2014, 18:16_
- New fullname user field column
- Several bug fixes

## 2.3.3 (2011040113) for Moodle 2.2, 2.3, 2.4, 2.5, 2.6, 2.7
_Release date: Friday, 13 June 2014, 18:16_
- Fixed layout and notice/warnings problems

## 2.3.2 (2011040110) for Moodle 2.2, 2.3, 2.4, 2.5, 2.6
_Release date: Friday, 14 February 2014, 11:14 AM_
- Several bug fixes

## 2.3.2 (2011040109) for Moodle 2.2, 2.3, 2.4, 2.5, 2.6
_Release date: Thursday, 30 January 2014, 10:33 AM_
- Fixed invalid table reference in cron

## 2.3.2 (2011040108) for Moodle  2.2, 2.3, 2.4, 2.5m 2.6
_Release date Thursday, 16 January 2014, 15:25 AM_
- Some bug fixes
- New CSV export

## 2.3.1 (2011040107) for Moodle 2.0, 2.1, 2.2, 2.3, 2.4, 2.5m 2.6
_Release date Monday, 16 December 2013, 10:25 AM_
- Some minor bug fixes
- SQL syntax by default is disabled
- New block instance settings (change name and also show/hide global reports)
- New users cohorts condition

## 2.3 (2011040106) for Moodle 2.0, 2.1, 2.2, 2.3, 2.4, 2.5m 2.6
_Release date Friday, 13 December 2013, 4:35 PM_
- Support for Moodle 2.6
- Multiple bugs fixed
- Global report that can be shared in all courses
- Public reports repository with multiple sample reports available
- Public SQL queries repository
- Reports can run on a different DB that the current (production) DB
- Reports can run on a CRON scheduler
- Several filter plugins added
- Integrated DataTables.js for the report table
- Integrated CodeMirror.js for highlighting SQL query code, while editing.
- New security settings

Thanks to Nadav Kavalerchik for providing most of the new features

## 2.2 (2011040105) for Moodle 2.0, 2.1, 2.2, 2.3, 2.4, 2.5
_Release date Wednesday, 27 February 2013, 9:35 AM_
- Support for Moodle 2.4 and 2.5
- Bugs fixed

## 2.1 (2011040103) for Moodle 2.0, 2.1, 2.2, 2.3
_Release date Friday, 6 July 2012, 1:29 PM_
- Support for Moodle 2.3
- Bugs fixed

## 2.0.2 (2011040102) for Moodle 2.0, 2.1, 2.2
_Release date Monday, 9 January 2012, 11:41 AM_
- Support for Moodle 2.2
- Bugs fixed

## 2.0.1 (2011040101) for Moodle 2.0, 2.1
_Release date Thursday, 13 October 2011, 12:55 AM_

## 2.0 (2011040100) for Moodle 2.0, 2.1
_Release date Thursday, 29 September 2011, 10:47 AM_

## 1.0 (2010090100) for Moodle 1.9
_Release date Thursday, 29 September 2011, 10:38 AM_
