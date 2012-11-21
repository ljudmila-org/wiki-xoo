<?php
 
$wgExtensionFunctions[] = 'wfQuery';
$wgExtensionCredits['parserhook'][] = array(
					    'name' => 'Query',
					    'description' => 'SQL query extension',
					    'author' => 'ljudmila.org',
					    'url' => 'http://wiki.ljudmila.org/index.php/Query'
					    );
 
function wfQuery() {
  global $wgParser;
  $wgParser->setHook('Query', 'renderQuery');
}
 
function fTh($v) { return '<td>'.$v.'</td>'; };
 
# The callback function for converting the input text to HTML output
function renderQuery($input, $args) {
  list($id) = explode('|',htmlspecialchars($input));

  $skin=$args['skin'];
  if(!$skin) { $skin='table'; }

  $query=wfMsg('Query:'.$id);

#   if($output=='&lt;0&gt;') {
#    return '<div class="query-error">Query <i>'+$id+'</i> not found!</div>'+"\n";
#  }
  
  $output='';
  $dbr = wfGetDB( DB_SLAVE );
  $res = $dbr->query($query);

  if($skin=='table') {
    if($args['flot']) {
      $output.='<table id="WQ_'.$input.'" class="wdbi-result flot">'."\n";
    } else {
      $output.='<table id="WQ_'.$input.'" class="wdbi-result">'."\n";
    }
    $header=0;
    while ( $row = $dbr->fetchObject( $res ) ) {
      if(!$header) { 
	$header=1; reset($row);
	$output.='<tr class="wdbi-result-header">';
	while(list($key,$val)=each($row)) {
	  $output.='<th>'.$key.'</th>';
	}
	$output.='</tr>'."\n";
	reset($row);
      }
      $output.="<tr>";
      foreach($row as $i) {
	$output.='<td>'.$i.'</td>';
      }
      $output.="</tr>\n";
    }
    $output.="</table>\n";
  }
  if($skin=='plain') {
    while ( $row = $dbr->fetchObject( $res ) ) {
      foreach($row as $i) {
	$output.=$i;
      }
    }
  }
  

  $dbr->freeResult( $res );
  $output.='<pre class="wdbi-query">'.$query.'</pre>'."\n";

  return $output;
}
?>
