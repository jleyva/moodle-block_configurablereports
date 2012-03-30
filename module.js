/**
 * Javascript helper functions for block_configurable_reports
 */

M.block_configurable_reports = {
	/* Table */
	setupTable : function (Y, tableid){
		var table_data = this.parseHTMLTable(Y, "#"+tableid);
		
		Y.use("datatable-sort", function (Y) {
	        dt = new Y.DataTable.Base({
	            columnset: table_data.cols,
	            recordset: table_data.data
	        }).plug(Y.Plugin.DataTableSort).render("#"+tableid);
		});
    },
	parseHTMLTable : function (Y, table_id) {
        var tnode = Y.one( table_id ),
            thead = [],
            tr    = [];

    	//Get table columns   
        tnode.all("th").each(function(item){
        	var col = item.getContent();
            thead.push( {key: col, label: col, sortable:true} );
        });
        
        //Get table data
        tnode.all("tbody tr").each(function(item){
        	var tr_obj = {};
            item.all("td").each( function(titem, tindex){
            	var content = titem.getContent();
            	// Hacky method to allow sorting of links
            	if(link = titem.all('a')){
            		content = '<span style="display:none;">' + link.getContent() + '</span>' + content;
            	}
                tr_obj[ thead[tindex].key ] = content;                
            });
            tr.push( tr_obj );
        });
        
        //Remove old data
        tnode.setContent('');
        
        return { cols:thead, data:tr };        
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