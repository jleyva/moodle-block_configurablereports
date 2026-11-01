define(
    [
        'jquery',
        'block_configurable_reports/jquery.tablesorter',
        'block_configurable_reports/jquery.dataTables',
        'block_configurable_reports/codemirror',
        'block_configurable_reports/sql',
        'core/config',
        'core/notification'
    ], function($, tablesorter, dataTable, CodeMirror, sql, Config, Notification) {

        // The editor wrapping the remote query textarea, when syntax highlighting is on. Setting the
        // textarea's own value is not enough once CodeMirror has taken it over.
        var remoteeditor = null;

        /**
         * Ask one of the plugin's AJAX scripts for a category listing or a report.
         *
         * @param {String} script The script to call, relative to the plugin.
         * @param {Object} params The parameters it takes, beyond the course and the sesskey.
         * @return {Promise} Resolved with the decoded response.
         */
        var request = function(script, params) {
            params.courseid = Config.courseId;
            params.sesskey = Config.sesskey;

            var url = Config.wwwroot + '/blocks/configurable_reports/' + script + '?' +
                new URLSearchParams(params).toString();

            return fetch(url, {credentials: 'same-origin'}).then(function(response) {
                if (!response.ok) {
                    throw new Error(response.statusText);
                }
                return response.json();
            }).then(function(data) {
                // An AJAX script answers with the usual 200 and reports the failure in the body.
                if (data && data.error) {
                    throw new Error(data.error);
                }
                return data;
            });
        };

        /**
         * Build an option element. The value and the text come from the repository, so neither is
         * parsed as HTML.
         *
         * @param {String} value
         * @param {String} text
         * @return {HTMLElement}
         */
        var option = function(value, text) {
            var element = document.createElement('option');
            element.value = value;
            element.textContent = text;
            return element;
        };

        return {
            js_order: function(params) {
                $(params.selector).tablesorter();
                $(params.selector + ' th.header').css({
                    'background-image': 'url(' + params.background + ')',
                    'background-position': 'right center',
                    'background-repeat': 'no-repeat',
                    'cursor': 'pointer'
                });

                $(params.selector + ' th.headerSortUp').css({
                    'background-image': 'url(' + params.backgroundasc + ')'
                });

                $(params.selector + ' th.headerSortDown').css({
                    'background-image': 'url(' + params.backgrounddesc + ')'
                });
            },
            add_jsdatatables: function(params) {
                $(params.selector).dataTable({
                    'bAutoWidth': false,
                    'sPaginationType': 'full_numbers',
                    'fixedHeader': true,
                    'aaSorting': [],
                    'oLanguage': {
                        'oAria': {
                            'sSortAscending': M.str.block_configurable_reports.datatables_sortascending,
                            'sSortDescending': M.str.block_configurable_reports.datatables_sortdescending,
                        },
                        'oPaginate': {
                            'sFirst': M.str.block_configurable_reports.datatables_first,
                            'sLast': M.str.block_configurable_reports.datatables_last,
                            'sNext': M.str.block_configurable_reports.datatables_next,
                            'sPrevious': M.str.block_configurable_reports.datatables_previous
                        },
                        'sEmptyTable': M.str.block_configurable_reports.datatables_emptytable,
                        'sInfo': M.str.block_configurable_reports.datatables_info,
                        'sInfoEmpty': M.str.block_configurable_reports.datatables_infoempty,
                        'sInfoFiltered': M.str.block_configurable_reports.datatables_infofiltered,
                        'sInfoThousands': M.str.langconfig.thousandssep,
                        'sLengthMenu': M.str.block_configurable_reports.datatables_lengthmenu,
                        'sLoadingRecords': M.str.block_configurable_reports.datatables_loadingrecords,
                        'sProcessing': M.str.block_configurable_reports.datatables_processing,
                        'sSearch': M.str.block_configurable_reports.datatables_search,
                        'sZeroRecords': M.str.block_configurable_reports.datatables_zerorecords
                    }
                });
            },
            cmirror: function() {
                // Documentation can be found @ http://codemirror.net/
                var query = document.getElementById('id_querysql');
                if (query) {
                    CodeMirror.fromTextArea(query, {
                        mode: "text/x-mysql",
                        rtlMoveVisually: true,
                        indentWithTabs: true,
                        smartIndent: true,
                        lineNumbers: true,
                        autofocus: true,
                    });
                }

                // Only on the page when a remote report repository is configured.
                var remotequery = document.getElementById('id_remotequerysql');
                if (remotequery) {
                    remoteeditor = CodeMirror.fromTextArea(remotequery, {
                        mode: "text/x-mysql",
                        rtlMoveVisually: true,
                        indentWithTabs: true,
                        smartIndent: true,
                        lineNumbers: true,
                        //    autofocus: true
                    });
                }
            },
            remote_reports: function() {
                var categories = document.getElementById('id_reportcategories');
                var reports = document.getElementById('id_reportsincategory');
                var query = document.getElementById('id_remotequerysql');

                // All three are added together, and only when a repository is configured.
                if (!categories || !reports || !query) {
                    return;
                }

                categories.addEventListener('change', function() {
                    // Keep the placeholder the form rendered, drop the previous category's reports.
                    while (reports.options.length > 1) {
                        reports.remove(1);
                    }
                    reports.selectedIndex = 0;

                    // The placeholder itself names no category to list.
                    if (categories.selectedIndex <= 0) {
                        return;
                    }

                    request('list_reports_in_category.php', {category: categories.value})
                        .then(function(list) {
                            list.forEach(function(report) {
                                reports.appendChild(option(report.fullname, report.name));
                            });
                            return;
                        })
                        .catch(Notification.exception);
                });

                reports.addEventListener('change', function() {
                    // The placeholder, and a list cleared from under a selection, name no report.
                    if (reports.selectedIndex <= 0) {
                        return;
                    }

                    request('get_remote_report.php', {reportname: reports.value})
                        .then(function(response) {
                            query.value = response;
                            if (remoteeditor) {
                                remoteeditor.setValue(response);
                            }
                            return;
                        })
                        .catch(Notification.exception);
                });
            }
        };
    });