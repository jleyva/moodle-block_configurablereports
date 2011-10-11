<?php

/*
 *
 *
 *
 * This script uses some lines of code of check_db_syntax by stronk7
*/

if (isset($_SERVER['REMOTE_ADDR'])) {
    define('LINEFEED', "<br />");
} else {
    define('LINEFEED', "\n");
}

/// Rules

	$rules = array();

	$rules[] = array(
	"exp"=>"/([^>])get_records\(([^,]+)\)/m",
	"func"=>"",
	"repl"=>"$1\$DB->get_records($2)"
	);	
	$rules[] = array(
	"exp"=>"/([^>])(get_record[s]?\([^,]+,)([^\)]+)(\))/m",
	"func"=>"add_conditions",
	"repl"=>""
	);
	$rules[] = array(
	"exp"=>"/([^>])(get_field?\([^,]+,[^,]+,)([^\)]+)(\))/m",
	"func"=>"add_conditions",
	"repl"=>""
	);
	$rules[] = array(
	"exp"=>"/([^>])(set_field?\([^,]+,[^,]+,[^,]+,)([^\)]+)(\))/m",
	"func"=>"add_conditions",
	"repl"=>""
	);
	$rules[] = array(
	"exp"=>"/([^>])(update_record|insert_record|delete_record|count_records|sql_substr)/m",
	"func"=>"",
	"repl"=>"$1\$DB->$2"
	);
	$rules[] = array(
	"exp"=>'/ENUM(VALUES)?=".*?"/',
	"func"=>"",
	"repl"=>""
	);	
	$rules[] = array(
	"exp"=>"/(global[ ]+)([^;]*;)/",
	"func"=>"",
	"repl"=>"$1\$DB, $2"
	);	
	$rules[] = array(
	"exp"=>"/(addslashes\()([^\)]+)(\))/",
	"func"=>"",
	"repl"=>'$2'
	);	
	$rules[] = array(
	"exp"=>'/(\{?\$CFG->prefix\}?)([^ ,]+)/',
	"func"=>"",
	"repl"=>'{$2}'
	);
	$rules[] = array(
	"exp"=>"/print_(box|box_start|box_end|container_end)/m",
	"func"=>"",
	"repl"=>"echo \$OUTPUT->$1"
	);
	$rules[] = array(
	"exp"=>"/([\s\t]*)print_heading\(([^\)]+)\)/m",
	"func"=>"change_print_heading",
	"repl"=>""
	);
	$rules[] = array(
	"exp"=>"/([\s\t]*)print_header\(([^\)]+)\)/m",
	"func"=>"change_print_header",
	"repl"=>""
	);
	$rules[] = array(
	"exp"=>"/([\s\t]*)print_footer\(([^\)]*)\)/m",
	"func"=>"change_print_footer",
	"repl"=>""
	);
	
	
	// Add conditions to dml functions
	function add_conditions($matches){
		$conditions = explode(',',$matches[3]);
		$cond = "array(";
		for($i=0;$i<count($conditions);$i += 2)
			$cond .= $conditions[$i]." => ".$conditions[$i+1].",";
		$cond = substr($cond,0,-1);
		$cond .= ")";
		return $matches[1]."\$DB->".$matches[2].$cond.$matches[4];
	}
	
	// Change the print heading
	function change_print_heading($matches){
		$newcode = '';
		$args = explode(',',$matches[2]);
		if(isset($args[1]))
			unset($args[1]);
		$args = implode(',',$args);	
		$newcode .= $matches[1].'echo $OUTPUT->heading('.$args.')';
		return $newcode;
	}
	
	// Change the print header
	function change_print_header($matches){
		$newcode = '';
		$args = explode(',',$matches[2]);
		
		$newcode .= $matches[1].'$PAGE->set_context($context);';
		$newcode .= $matches[1].'$PAGE->set_pagelayout(\'incourse\');';
		
		$newcode .= (isset($args[0]))? $matches[1].'$PAGE->set_title('.$args[0].');' : '';
		$newcode .= (isset($args[1]))? $matches[1].'$PAGE->set_heading('.$args[1].');' : '';
		$newcode .= (isset($args[5]))? $matches[1].'$PAGE->set_cacheable('.$args[5].');' : '';
		$newcode .= (isset($args[6]))? $matches[1].'$PAGE->set_button('.$args[6].');' : '';
		$newcode .= (isset($args[7]))? $matches[1].'$PAGE->set_headingmenu('.$args[7].');' : '';
		
		$newcode .= $matches[1].'echo $OUTPUT->header()';
		return $newcode;
	}
	
	// Change the print footer
	function change_print_footer($matches){
		$newcode = $matches[1].'echo $OUTPUT->footer()';
		return $newcode;
	}
	

/// Getting current dir
    $dir = dirname(__FILE__);
	
	// Create version.php
	
	if(!file_exists("$dir/version.php")){
		$content =  utf8_encode("<?php\n    \$plugin->version = 2007101509;\n?>");
		file_put_contents("$dir/version.php", $content);
		echo "Created version.php file".LINEFEED;
	}
		
	// Rename lang dirs
	$langdirpath = "$dir/lang";
	$langdir = opendir($langdirpath);
	
	while (false !== ($file=readdir($langdir))) {

		$fullpath = $langdirpath . '/' . $file;

		if (substr($file, 0, 1)=='.' || $file=='CVS') { /// Exclude some dirs
			continue;
		}

		if (is_dir($fullpath)) { 
			rename($fullpath, $langdirpath . '/' . str_replace('_utf8','',$file));
			echo "Renamed lang dir $file".LINEFEED;
		}
	}
		
	// Create string (pluginname)

	//*  Change array name to $capabilities only. [Warning only]
    //* Remove all references to "admin" from access.php as it is not needed. [Warning only]
    //* Rename "legacy" to "archetypes" in line with core code. [Optional]
    //* Add new "manager" role. --Frank Ralf 11:22, 14 November 2010 (UTC) 
	
/// Process starts here

    $files = files_to_check($dir);

    foreach ($files as $file) {
		$modify = false;
        //echo "  - $file: ";
		$content = file_get_contents($file);
		
		foreach($rules as $r){			
			if($r['func']){
				$content = preg_replace_callback($r['exp'],$r['func'],$content);
			}
			else{
				$content = preg_replace($r['exp'],$r['repl'],$content);
			}
		}
		
		/// The file need to be updated?
		file_put_contents($file, $content);		
	}

    /**
     * Given one full path, return one array with all the files to check
     */
    function files_to_check($path) {

        $results = array();
        $pending = array();

        $dir = opendir($path);
        while (false !== ($file=readdir($dir))) {

            $fullpath = $path . '/' . $file;

            if (substr($file, 0, 1)=='.' || $file=='CVS' || $file == 'm19tom20.php') { /// Exclude some dirs
                continue;
            }

            if (is_dir($fullpath)) { /// Process dirs later
                $pending[] = $fullpath;
                continue;
            }

            if (is_file($fullpath) && strpos($file, basename(__FILE__))!==false) { /// Exclude me
                continue;
            }

            if (is_file($fullpath) && strpos($file, '.php')===false && strpos($file, '.html')===false && strpos($file,'.xml')===false) { /// Exclude some files
                continue;
            }

            if (!in_array($fullpath, $results)) { /// Add file if doesn't exists
                $results[$fullpath] = $fullpath;
            }
        }
        closedir($dir);

        foreach ($pending as $pend) {
            $results = array_merge($results, files_to_check($pend));
        }

        return $results;
    }


?>