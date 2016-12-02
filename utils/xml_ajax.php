<?php
include("embx_dbconn.php");
include("embx_functions.php");
function base64_encode_audio ($filename, $filetype) {
    if ($filename) {
        $imgbinary = fread(fopen($filename, "r"), filesize($filename));
        return 'data:audio/' . $filetype . ';base64,' . base64_encode($imgbinary);
    }
}


function amstore_xmlobj2array($obj, $level=0) {
    
    $items = array();
    
    if(!is_object($obj)) return $items;
        
    $child = (array)$obj;
    
    if(sizeof($child)>1) {
        foreach($child as $aa=>$bb) {
            if(is_array($bb)) {
                foreach($bb as $ee=>$ff) {
                    if(!is_object($ff)) {
                        $items[$aa][$ee] = $ff;
                    } else
                    if(get_class($ff)=='SimpleXMLElement') {
                        $items[$aa][$ee] = amstore_xmlobj2array($ff,$level+1);
                    }
                }
            } else
            if(!is_object($bb)) {
                $items[$aa] = $bb;
            } else
            if(get_class($bb)=='SimpleXMLElement') {
                $items[$aa] = amstore_xmlobj2array($bb,$level+1);
            }
        }
    } else
    if(sizeof($child)>0) {
        foreach($child as $aa=>$bb) {
            if(!is_array($bb)&&!is_object($bb)) {
                $items[$aa] = $bb;
            } else
            if(is_object($bb)) {
                $items[$aa] = amstore_xmlobj2array($bb,$level+1);
            } else {
                foreach($bb as $cc=>$dd) {
                    if(!is_object($dd)) {
                        $items[$obj->getName()][$cc] = $dd;
                    } else
                    if(get_class($dd)=='SimpleXMLElement') {
                        $items[$obj->getName()][$cc] = amstore_xmlobj2array($dd,$level+1);
                    }
                }
            }
        }
    }

    return $items;
}


function xml2assoc($xml, $name)
{ 
    //print "<ul>";

    $tree = null;
    //print("I'm inside " . $name . "<br>");
    
    while($xml->read()) 
    {
        if($xml->nodeType == XMLReader::END_ELEMENT)
        {
            //print "</ul>";
            return $tree;
        }
        
        else if($xml->nodeType == XMLReader::ELEMENT)
        {
            $node = array();
            
            //print("Adding " . $xml->name ."<br>");
            $node['tag'] = $xml->name;

            if($xml->hasAttributes)
            {
                $attributes = array();
                while($xml->moveToNextAttribute()) 
                {
                    //print("Adding attr " . $xml->name ." = " . $xml->value . "<br>");
                    $attributes[$xml->name] = $xml->value;
                }
                $node['attr'] = $attributes;
            }
            
            if(!$xml->isEmptyElement)
            {
                $childs = xml2assoc($xml, $node['tag']);
                $node['childs'] = $childs;
            }
            
            //print($node['tag'] . " added <br>");
            $tree[] = $node;
        }
        
        else if($xml->nodeType == XMLReader::TEXT)
        {
            $node = array();
            $node['text'] = $xml->value;
            $tree[] = $node;
            //print "text added = " . $node['text'] . "<br>";
        }
    }
    
    //print "returning " . count($tree) . " childs<br>";
    //print "</ul>";
    
    return $tree; 
}



set_include_path(get_include_path() . PATH_SEPARATOR . '/usr/bin' . PATH_SEPARATOR . '/usr/sbin' . PATH_SEPARATOR . '/usr/local/bin'  );


$pf = $_GET["pf"];


//$filepath = "/Library/WebServer/Documents/embx-a/downloads/";

$filepath = AUDIO_FILE_PATH;

switch ($pf) {
	case "processxml":
		$batchfilename = $_GET["batchfilename"];
		$xml = simplexml_load_file("../uploads/" . $batchfilename);
//		print_r($xml);
		
		$xmlarr = amstore_xmlobj2array($xml, $level=0);
		echo "<pre>";
		//print_r($xmlarr["update"]["fixdic"]["fielddic"]);
		//print_r($xml);
		$json_string = json_encode($xml);
		$xmlarrfromjson = json_decode($json_string,TRUE);
		//print_r($xmlarrfromjson);
		
		echo "</pre>";
		
		echo "<pre>";
		$xmlob = new XMLReader();
		$xmlob->open("../uploads/" . $batchfilename);
		$assoc = xml2assoc($xmlob,"root");
		print_r($assoc[0][childs][0][childs][0][childs][0][childs]);
		$fielddefs = $assoc[0][childs][0][childs][0][childs][0][childs];
		foreach($fielddefs as $fielddef){
			//echo "Tag = " . $fielddef[attr][tag] . " Name = " . $fielddef[attr][name] . "<br />";
		}
		echo "</pre>";


		
	break;
	
	
	
}





?>
