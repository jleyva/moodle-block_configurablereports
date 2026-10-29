/* TODO needs to be converted to AMD and converted to vanila JS */

M.block_configurable_reports = {

    loadReportCategories: function(Y) {
        this.Y = Y;

        select_reportcategories = Y.one('#id_crreportcategories');
        Y.io(M.cfg.wwwroot + '/blocks/configurable_reports/repository.php', {
            data: 'action=listreports&courseid=' + M.cfg.courseId + '&sesskey=' + M.cfg.sesskey,
            context: this,
            method: "GET",
            on: {
                success: function(id, o) {
                    var response = Y.JSON.parse(o.responseText);

                    for (var prop in response) {
                        if (response.hasOwnProperty(prop)) {
                            select_reportcategories.appendChild(create_option(response[prop]["path"], response[prop]["name"]));
                        }
                    }

                },
                failure: function(id, o) {
                    // TODO use strings.
                    window.alert('Repository unreachable');
                }
            }
        });

    },

    onchange_crreportcategories: function(select_element) {
        var Y = this.Y;

        select_reportnames = Y.one('#id_crreportnames');

        Y.io(M.cfg.wwwroot + '/blocks/configurable_reports/repository.php', {
            data: 'action=listcategory&category=' + encodeURIComponent(select_element[select_element.selectedIndex].value) +
                '&courseid=' + M.cfg.courseId + '&sesskey=' + M.cfg.sesskey,
            context: this,
            method: "GET",
            on: {
                success: function(id, o) {
                    var response = Y.JSON.parse(o.responseText);
                    select_reportnames.get('childNodes').remove();
                    option = Y.Node.create('<option value="-1">...</option>');
                    select_reportnames.appendChild(option);

                    for (var prop in response) {
                        if (response.hasOwnProperty(prop)) {
                            select_reportnames.appendChild(create_option(response[prop]["git_url"], response[prop]["name"]));
                        }
                    }
                },
                failure: function(id, o) {
                    window.alert('Repository unreachable');
                }
            }
        });
    },

    onchange_crreportnames: function(select_element) {
        var Y = this.Y;

        var path = select_element[select_element.selectedIndex].value;
        location.href = location.href + "&importurl=" + encodeURIComponent(path) + "&sesskey=" + M.cfg.sesskey;
    }
}

/**
 * Create an option element, the value and text come from the repository so they are not parsed as HTML.
 *
 * @param {String} value
 * @param {String} text
 * @return {HTMLElement}
 */
function create_option(value, text) {
    var option = document.createElement('option');
    option.value = value;
    option.textContent = text;
    return option;
}

function menuplugin(event, args) {
    location.href = args.url + document.getElementById('menuplugin').value;
}