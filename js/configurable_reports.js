
M.block_configurable_reports = {

    sesskey: null,

    init: function(Y, sesskey) {
        this.Y = Y;
        this.sesskey = sesskey;
    },

    onchange_reportcategories : function (select_element,sesskey) {
        var Y = this.Y;

        //select_reportsincategory = Y.one('#id_reportsincategory');
        //select_reportsincategory.setHTML('');

        select_reportsincategory = Y.one('#id_reportsincategory');
        select_reportsincategory.setStyle('visibility', 'hidden');
        var xhr = Y.io(M.cfg.wwwroot+'/blocks/configurable_reports/list_reports_in_category.php', {
            data: 'category='+select_element[select_element.selectedIndex].value+'&sesskey='+sesskey,
            context: this,
            method: "GET",
            on: {
                success: function(id, o) {
                    var response = Y.JSON.parse(o.responseText);
                    var list = Y.Node.create('<select>');
                    option = Y.Node.create('<option value="-1">Choose...</option>');
                    list.appendChild(option);

                    for(var prop in response) {
                        if (response.hasOwnProperty(prop)) {
                            option = Y.Node.create('<option value='+response[prop]["fullname"]+'>'+response[prop]["name"]+'</option>');
                            list.appendChild(option);
                        }
                    }
                    select_reportsincategory.setStyle('visibility', 'visible');
                    list.setAttribute('id','id_reportsincategory');
                    list.setAttribute('name','reportsincategory');
                    list.setAttribute('onchange','M.block_configurable_reports.onchange_reportsincategory(this,"'+this.sesskey+'")');
                    select_reportsincategory.replace(list);
                },
                failure: function(id, o) {
                    if (o.statusText != 'abort') {
                        select_reportsincategory.setStyle('visibility', 'hidden');
//                        var instance = this.currentinstance;
//                        instance.progress.setStyle('visibility', 'hidden');
//                        if (o.statusText !== undefined) {
//                            instance.listcontainer.set('innerHTML', o.statusText);
//                        }
                    }
                }
            }
        });
    },

    onchange_reportsincategory : function (select_element,sesskey) {
        var Y = this.Y;

        //select_reportsincategory = Y.one('#id_reportsincategory');
        //select_reportsincategory.setHTML('');

        textarea_reportsincategory = Y.one('#id_remotequerysql');
        //select_reportsincategory.setStyle('visibility', 'hidden');
        var xhr = Y.io(M.cfg.wwwroot+'/blocks/configurable_reports/get_remote_report.php', {
            data: 'reportname='+select_element[select_element.selectedIndex].value+'&sesskey='+sesskey,
            context: this,
            method: "GET",
            on: {
                success: function(id, o) {
                    var response = Y.JSON.parse(o.responseText);

                    // Use regular textarea element.
                    textarea_reportsincategory.set('value', response);

                    // Use codemirror editor.
                    editor_remotequerysql.setValue(response);
                    /*
                    var list = Y.Node.create('<select>');
                    for(var prop in response) {
                        if (response.hasOwnProperty(prop)) {
                            option = Y.Node.create('<option id='+response[prop]["name"]+'>'+response[prop]["name"]+'</option>');
                            list.appendChild(option);
                        }
                    }
                    //select_reportsincategory.setStyle('visibility', 'visible');
                    list.setAttribute('id','id_reportsincategory');
                    list.setAttribute('name','reportsincategory');
                    list.setAttribute('onchange','M.block_configurable_reports.onchange_reportsincategory(this,"'+this.sesskey+'")');
                    select_reportsincategory.replace(list);
                    */
                },
                failure: function(id, o) {
                    if (o.statusText != 'abort') {
                        select_reportsincategory.setStyle('visibility', 'hidden');
//                        var instance = this.currentinstance;
//                        instance.progress.setStyle('visibility', 'hidden');
//                        if (o.statusText !== undefined) {
//                            instance.listcontainer.set('innerHTML', o.statusText);
//                        }
                    }
                }
            }
        });
    }
}

function menuplugin(event,args) {
	location.href = args.url+document.getElementById('menuplugin').value;
}

// Documentation can be found @ http://codemirror.net/
var editor_querysql = CodeMirror.fromTextArea(document.getElementById('id_querysql'), {
    mode: "text/x-mysql",
    rtlMoveVisually: true,
    indentWithTabs: true,
    smartIndent: true,
    lineNumbers: true,
    matchBrackets : true,
    autofocus: true,
    extraKeys: {
        "F11": function(cm) {
            cm.setOption("fullScreen", !cm.getOption("fullScreen"));
        },
        "Esc": function(cm) {
            if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
        }}
    });

var editor_remotequerysql = CodeMirror.fromTextArea(document.getElementById('id_remotequerysql'), {
    mode: "text/x-mysql",
    rtlMoveVisually: true,
    indentWithTabs: true,
    smartIndent: true,
    lineNumbers: true,
    matchBrackets : true,
//    autofocus: true
});

