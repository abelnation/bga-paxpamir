<?php
/**
 * This script generates (updates) material.inc.php from CSV like file (pipe separated) defining game element problems.
 * File material.inc.php must have soecial mark up to identify insertion points (see example below). 
 * You need to install php cli to run it.
 * 
 * usage: php genmap.php <input.csv> <path to material.inc.php>
 * 
 * Sample input file
 * 
element_id|element_type|element_name|element_description|element_action_description
train|token|Locomotive|The number of a locomotive indicates the number of spaces it can reach on a railroad:
factory|token|Factory|
slot_action_1|slot_action {'o'=>"1,0,1,bb"}|2 Black Track Advancements|This action gives you two advancements of black track. You cannot use this action if you cannot complete all advancements.
 *
 * Sample material.inc.php BEFORE change
 

$this->token_types = array(
        // --- gen php begin ---
         
        // --- gen php end --- 
);

 * After running script

$this->token_types = array(
        // --- gen php begin ---
'train' => array(
  'type' => 'token',
  'name' => clienttranslate("Locomotive"),
  'tooltip' => clienttranslate("The number of a locomotive indicates the number of spaces it can reach on a railroad:"),
),
'factory' => array(
  'type' => 'token',
  'name' => clienttranslate("Factory"),
),
'slot_action_1' => array(
  'type' => 'slot_action',
  'name' => clienttranslate("2 Black Track Advancements"),
  'tooltip' => clienttranslate("This action gives you two advancements of black track. You cannot use this action if you cannot complete all advancements."),
  'o'=>"1,0,1,bb",
),       
        // --- gen php end --- 
);

 */

function genbody($incsv) {
    global $out;
    $ins = fopen($incsv, "r") or die("Unable to open file! $ins");
    $i = 0;
    $prev = "";
    $ctype = "";
    while ( ($line = fgets($ins)) !== false ) {
        $fields = explode('|', $line);
        list ( $id, $name, $ftype, $region, $suit, $rank, $impact, $actions, $special, $prize, $patriot, $desc ) = $fields;
        $act = '';
        $extra='';
        if (count($fields) >= 5)
            $act = $fields [4];
        if ($name == 'element_name')
            continue;
        $pos = strpos($ftype, '{');
        if ($pos !== false) {
            $extra = substr($ftype, $pos + 1);
            $ftype = trim(substr($ftype, 0, $pos));
            $pos = strpos($extra, '}');
            if ($pos !== false) {
                $extra = substr($extra, 0, $pos);
            }
     
        }
            
        $tt = explode(' ', $ftype);
        $type = $tt [0];
        if ($type == $prev)
            $i ++;
        else
            $i = 1;
        if ($id == "") {
            $id = "${type}_${i}";
        }
        fwrite($out, "'$id' => array(\n");
        fwrite($out, "  'type' => '$ftype',\n");
        if ($name)
            fwrite($out, "  'name' => clienttranslate(\"$name\"),\n");
        else
            fwrite($out, "  'name' => '',\n");
        if ($region) {
            $region = trim($region);
            if ($region)
                fwrite($out, "  'region' => '$region',\n");
        }
        if ($suit) {
            $suit = trim($suit);
            if ($suit)
                fwrite($out, "  'suit' => '$suit',\n");
        }
        if ($rank) {
            if ($rank)
                fwrite($out, "  'rank' => $rank,\n");
        }
        if ($impact) {
            $suit = trim($impact);
            if ($impact)
                fwrite($out, "  'impact' => '$impact',\n");
        }
        if ($actions) {
            $suit = trim($actions);
            if ($actions)
                fwrite($out, "  'actions' => '$actions',\n");
        }
        if ($special) {
            if ($special)
                fwrite($out, "  'special' => $special,\n");
        }
        if ($prize) {
            $suit = trim($prize);
            if ($prize)
                fwrite($out, "  'prize' => '$prize',\n");
        }
        if ($patriot) {
            $suit = trim($prize);
            if ($patriot)
                fwrite($out, "  'patriot' => '$patriot',\n");
        }
        if ($desc) {
            $desc = trim($desc);
            if ($desc)
                fwrite($out, "  'tooltip' => clienttranslate(\"$desc\"),\n");
        }
        if ($act) {
            $act = trim($act);
            if ($act)
                fwrite($out, "  'tooltip_action' => clienttranslate(\"$act\"),\n");
        }
        if ($extra) {
            fwrite($out, "  $extra,\n");
        }
        fwrite($out, "),\n");
        $prev = $type;
    }
}
$incsv = $argv [1];
if (isset($argv [2])) {
    $infile = $argv [2];
} else {
    $infile = dirname("$incsv") . "/../material.inc.php";
}
$outfile = $infile . ".out";
$out = fopen($outfile, "w") or die("Unable to open file! $outfile");
$in = fopen($infile, "r") or die("Unable to open file! $in");
while ( ($line = fgets($in)) !== false ) {
    fwrite($out, $line);
    $line = trim($line);
    $k = strpos($line, "--- gen php begin ---");
    if ($k !== false) {
        genbody($incsv);
        break;
    }
}
while ( ($line = fgets($in)) !== false ) {
    if (strpos($line, "--- gen php end ---") !== false) {
        fwrite($out, $line);
        break;
    }
}
while ( ($line = fgets($in)) !== false ) {
    fwrite($out, $line);
}
fclose($out);
fclose($in);
rename($outfile, $infile);
echo "Generated => $infile\n";
?>