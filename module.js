/**
 * Javascript helper functions for block_configurable_reports
 */

M.block_configurable_reports = {
	/* Tables */
	setup_html_table : function (Y, tableid){
		var tableNode = Y.one('#'+tableid);
		
		Y.use("datatable-sort", function (Y) {
			var cols   = [];
	        var fields = [];
	        var sortField = function (a, b, desc){
	        	console.log(a);
	        	var aa = a.get('lastName') + a.get('firstName'),
	        	bb = a.get('lastName') + b.get('firstName'),
	        	order = (aa > bb) ? 1 : -(aa < bb);
	        	return desc ? -order : order;
	        };

		    //Get columnset
	        tableNode.all("th").each(function(thNode){
	        	var col = thNode.get("text");
	            cols.push( {key: col, label: col, sortable:true, sortFn: sortField} );
	        });
	        
	        //Get recordset
	        tableNode.all("tbody tr").each(function(item){
	        	var field = {};
	            item.all("td").each( function(titem, tindex){
	                field[ cols[tindex].key ] = titem.getContent();
	            });
	            fields.push( field );
	        });
	        
        	var dt = new Y.DataTable.Base({
        		columnset: cols, 
    			recordset: fields,
    			summary: tableNode.getAttribute('summary')
			 });
        	dt.plug(Y.Plugin.DataTableSort);
	        
	        //Hide HTML content with JS content
	        tableNode.all('thead').setAttribute('style', 'display:none;');
	        tableNode.all('tbody').setAttribute('style', 'display:none;');
	        dt.render('#'+tableid);
		});
    },
    setup_data_table : function (Y, tableid){
		var tableNode = Y.one('#'+tableid);
		
		Y.use("datatype", "datasource-xmlschema", "datasource-local", 
				"datatable-datasource", "datatable-sort", function (Y) {
			var cols   = [];
	        var fields = [];
	        
	        // Each record is held in a TR
	        schema = {resultListLocator:"tr"};
	        // Each field name is held in a TH
	        thList = tableNode.all("th");
	
		    // Generate field definitions based on TH
		    thList.each(function(thNode, i){
		    	var text = thNode.get("text");
		    	cols.push({key: text, label: text, sortable: true});
		        // Note that the XPath indexes are 1-based!
		        fields.push({
		        	key: text, 
		        	locator: "td["+(i+1)+"]", 
		        	//FIXME Custom parser used until YUI adds parsers (3.6?) 
		        	parser: function(val) { 
		        		if(isFinite(String(val))){
		        			return Number(val);
		        		}
		        		return val;
	        		}
		        });
		    });
		    schema.resultFields = fields;
		    
	        console.log();

		    var ds = new Y.DataSource.Local({source: Y.Node.getDOMNode(tableNode.one('tbody'))});
			ds.plug(Y.Plugin.DataSourceXMLSchema, {schema: schema});
				
	        var dt = new Y.DataTable.Base({
	        	columnset: cols, 
	        	summary: tableNode.getAttribute('summary')
        	});
	        dt.plug(Y.Plugin.DataTableDataSource, {datasource: ds, initialRequest: ""});
	        dt.plug(Y.Plugin.DataTableSort);
	        
	        //Hide HTML content with JS content
	        tableNode.all('thead').setAttribute('style', 'display:none;');
	        tableNode.all('tbody').setAttribute('style', 'display:none;');
	        dt.render('#'+tableid);
		});
    },
    
    /* Printable */
    printDiv : function (Y, controlid, printid){
    	//Remove link to standard print page
    	var controldiv = Y.one('#'+controlid);
    	var content = controldiv.all('a').getContent();
    	controldiv.all('a').remove();
    	controldiv.setContent(''+content);
    	controldiv.setAttribute('style', 'cursor:pointer;');
    	
    	//Set listener for div
    	Y.on('click', function(e) {
			var win = window.open(" ", M.util.get_string('print'));
		   
			win.document.open();
			win.document.write('<html><body>' + Y.one('#'+id).getContent() + '</body></html>');
			win.document.close();
			win.print();
			win.close();
    	}, '#'+controlid);
	}
}