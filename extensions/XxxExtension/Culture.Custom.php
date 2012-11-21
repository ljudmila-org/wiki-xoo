<?php
//require_once('XxxExtension.php');
//require_once('Xvv.php');
 
#
#  XwwTemplate - Xoo World of Wiki - Templates and transclusions
#
#  Part of Xoo (c) 1997-2008 [[w:en:User:Zocky]], mitko.si
#	GPL3 applies
#
#
#	Wikivariables and parser functions for dealing with template transclusion
#
############################################################################
    


class CultureCustom extends Xxx
{	 
	function fl_embed (&$P, $F, $A) 
	{
		$args   = new XxxArgs($F, $A);
		$url    = $args->trimExpand(1);
		$width  = $args->trimExpand(2);
		$height = $args->trimExpand(3);
        	$parts = parse_url($url);

	        parse_str($parts['query'],$argsarray);
	        $queryargs=Array();
	        foreach ($argsarray as $k=>$v) {
	            $queryargs[]="$k=$v";
	        }
               
		switch ($args->command)	
		{
		case 'queryargs':
			return join("|",$queryargs);
		default:
			return array("{{Mediawiki:Embed {$parts['host']}|path={$parts[path]}|fragment={$parts[fragment]}|".join("|",$queryargs)."|width=$width|height=$height}}",'noparse'=>false);
			
		}
		return $this->notFound();
	}

    function fl_iframe(&$P, $F, $A) 
    {
		$args=new XxxArgs($F, $A);
		$width = $args->trimExpand(1);
		$height = $args->trimExpand(2);
		$src = $args->trimExpand(3);

		$output="<iframe src=\"$src\" width=\"$width\" height=\"$height\"></iframe>";
		return array( $output, 'isHTML'=>true);
		
    }

 
	function fl_xthis (&$P, $F, $A) 
	{
		$args=new XxxArgs($F, $A);
		
		switch ($args->command)	
		{
		case 'depth':
			if ($args->count != 0) return array('found'=>false);
			for ($i=0; isset($F->parent); $i++, $F=$F->parent);
			return $i;
		case 'title':
			if ($args->count != 0) return array('found'=>false);		
			return $F->title->getFullText();
			
		case 'parent':
			if ($args->count != 0) return array('found'=>false);
			if (!isset($F->parent)) return "";
			return $F->parent->title->getFullText();
		}
		return $this->notFound();
	}
	
	function fl_extract (&$P, $F, $A)
	{
		$args=new XxxArgs($F, $A);
		switch ($args->command)	
		{
		case 'section':	
			if ($args->count < 2 || $args->count > 3) return array('found'=>false);
			$section = $args->trimExpand(1);
			$text = $args->trimExpand(2);
			$deftext = $args->trimExpand(3);
			return array($P->mStripState->unstripBoth($P->getSection( $text, $section, $deftext)),'noparse'=>true);
		}
		return $this->notFound();
	}
	
	function fl_xprop (&$P, $F, $A)
	{
		$args=new XxxArgs($F, $A);
		switch ($args->command)	
		{
		case 'set':
			if ($args->count != 2 ) return $this->notFound();
			$prop = $args->trimExpand(1);
			$value = $args->trimExpand(2);
			$P->mOutput->setProperty($prop,$value);
			$P->mOutput->setProperty("{$prop}__StripState__",serialize($P->mStripState));
			return "";
		case 'get':	
			if ($args->count < 2 || $args->count > 3) return $this->notFound();

			$t = $args->trimExpand(1);
			$title=Title::newFromText($t);
			if(!$title) return $this->notFound();
			$article=new Article($title);
			$pid=$article->getId();
			if($pid==0) return $this->notFound();
			if($title->isRedirect()) {
				$title = $article->followRedirect();
				$article=new Article($title);
				$pid=$article->getId();
				if($pid==0) return $this->notFound();						
			}
			$prop = $args->trimExpand(2);
			
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select( array('page_props' ),
				array( 'pp_value' ),
				array( 'pp_propname' => $prop, 'pp_page' => $pid),
				__METHOD__ );
			if( $res === false ) return $this->cropExpand(3,$this->notFound());
			foreach( $res as $row ) {
				$val=$row->pp_value;
			}
			
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select( array('page_props' ),
				array( 'pp_value' ),
				array( 'pp_propname' => "{$prop}__StripState__", 'pp_page' => $pid),
				__METHOD__ );
			if( $res === false ) return $this->cropExpand(3,$this->notFound());
			foreach( $res as $row ) {
				$stripState=unserialize($row->pp_value);
			}
			if (is_array($stripState->nowiki->data)) {
				foreach($stripState->nowiki->data as $k=>$v) {
					$P->mStripState->addNoWiki($k,$v);
				}
			}
			if (is_array($stripState->general->data)) {
				foreach($stripState->general->data as $k=>$v) {
					$P->mStripState->addGeneral($k,$v);
				}
			}
			return $val;
		}
		return $this->notFound();
	}
	
		
	function fl_custom(&$P,$F,$A)
	{
		$args=new XxxArgs($F, $A);
		switch ($args->command)	
		{
		case 'raw':
		  $ns = $F->title->getNamespace();
			if($ns !=NS_MEDIAWIKI && $ns !=NS_TEMPLATE) {
				return "<b> {{#custom:raw}} works only in the MediaWiki: and Template: namespaces.</b>";
			}
			$a = array();
			for ($i=1; $i<=$args->count;$i++) {
				$a[]= $args->expand($i);
			}
			return array(implode('|',$a),'isHTML'=>true);
		case 'recent':
			$limit = (int)$args->trimExpand(1,10);
			$limit = $limit ? $limit : 10;
			$limit = $limit>50 ? 50 : $limit;
			$format = $args->trimExpand(2,"r");
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select( array('recentchanges' ),
				array( 'rc_title', 'MAX(rc_timestamp) AS ts' ),
				array( 'rc_namespace' => 0),
				__METHOD__,
				array(
					'GROUP BY'=>'rc_title',
					'ORDER BY'=>'MAX(rc_timestamp) DESC',
					'LIMIT'=>$limit
				)
			);
			$ret = "<table class=\"recent-page\">";
			foreach( $res as $row ) {
				$time = date_format(date_create($row->ts),$format);
				$title = Title::newFromText($row->rc_title);
				$title = $title->getText();
				$ret.="
				<tr class=\"recent-page\">
					<td class=\"recent-page-date\">".$time."</td>
					<td class=\"recent-page-title\">[[".$title."]]</td>
				</tr>
				";
			}
			$ret.="</table>";
			return $ret;
		case 'nosmw':
			$P->mOutput->mNoSMW = true;
			return "";
		case 'ago':
			$t = strtotime('now') - strtotime($args->trimExpand(1,'')) - 7200;	
			$s = abs(round($t));		
			$i = round($s/60);			
			$h = round($s/60/60);			
			$d = round($s/24/60/60);			
			$w = round($s/7/24/60/60);
			$m = round($s/30.5/24/60/60);
			$y = round($s/365/24/60/60);			
			if ($t<0) {
				$post = "in the future";
			} else {
				$post = "ago";
			}
			
			if ($s==1) {
				return "one second $post";
			} elseif ($i<2) {
				return "$s seconds $post";
			} elseif ($h<2) {
				return "$i minutes $post";
			} elseif ($d<2) {
				return "$h hours $post";
			} elseif ($w<2) {
				return "$d days $post";
			} elseif ($m<2) {
				return "$w weeks $post";
			} elseif ($y<2) {
				return "$w months $post";
			} else {
				return "$y years $post";
			}
		case 'loggedin':
			global $wgUser;
			return $wgUser->isLoggedIn() ? 'LOGGEDIN' : '';
		case 'canedit':
			global $wgUser;
			return $P->mTitle->userCan('edit') ? 'CANEDIT' : '';
		case 'sub':
			$s=$args->trimExpand(1,'');
			$f=$args->trimExpand(2,0);
			$l=$args->trimExpand(3,null);
			return mb_substr($s,$f,$l);

		case 'raw':	
		case 'edit':
			if($args->exists(1)) {
				$t = $args->trimExpand(1);
				$title = Title::newFromText($t);
				if (!$title) return $this->notFound();
			} else {
				$title = $P->mTitle;
			}
			if($args->exists(2)) {
				$text = $args->trimExpand(2);
			} else {
				$text = $title->getFullText() . '<small>[E]</small>';
			}
			if (!$title->userCan('edit')) {
				return $text;
			}
			$url = $title->getFullUrl("action=edit");			
			return array("<a href=\"$url\" class=\"editlink\">$text</a>",'isHTML'=>true); 
		case 'cgi':
			global $wgRequest;
			if(!$args->exists(1)) return $this->notFound();
			$name = $args->trimExpand(1);
			$val = $wgRequest->getText($name,'');
			if ($val==='') {
				return $args->exists(2) ? $args->cropExpand(2) : $this->notFound();
			} 
			$P->disableCache();
			return $val;
		case 'or':
			if($args->count < 1) return $this->notFound();
		  for ($i=1; $i<= $args->count; $i++) {
		    $a = $args->trimExpand($i);
		    if($a!=="") return $a;
		  }
		  return "";
		case 'and':
			if($args->count < 1) return $this->notFound();
		  for ($i=1; $i <= $args->count; $i++) {
		    $a = $args->trimExpand($i);
		    if($a==="") return "";
		  }
		  return $a;
		case 'in':
			if($args->count < 2) return $this->notFound();
		  $n = $args->trimExpand(1);
		  for ($i=2; $i <= $args->count; $i++) {
		    $a = $args->trimExpand($i);
		    if($a===$n) return $a;
		  }
		  return "";
/*		case 'declare':
		  $suffix = '([^\\s\\[\\]]+)'; #()
      $name   = '([^\\[\\]]+)'; # ()
      $arg    = '([^)\\.]+)'; # ()
      $in     = "\\s+[iI][nN]\\s+";

      $LIST   = '[(]([^)]*)[)]'; # 10
      $RANGE  = "[(]\\s*{$arg}?\\s*[.][.]\s*{$arg}?\\s*[)]"; #8,9
      $PRED   = "({$in}($name|$RANGE|$LIST))"; #6,7
      $PAGE   = "{$suffix}?\\[\\[{$name}?[*]{$name}?\\]\\]{$suffix}?" #2,3,4,5
      $TYPE   = "(text|number|integer|date|url|$PAGE)" #1
      $RE = "^\\s*{$TYPE}{$PRED}?";
*/
		}
		return $this->notFound();
	}

	function hook_LinkEnd( $skin, $target, $options, &$text, &$attribs, &$ret ) {
		if(in_array('broken',$options) && !$target->userCan('edit')){
			$ret="<span class=\"broken-link\">$text</span>";
			return false;
		}
		return true;
	}
	
	function fl_xsvg(&$parser,$f,$a)
	{
		global $wgUploadPath, $wgUploadDirectory, $wgImageMagickConvertCommand;
	    $args=new XxxArgs($f,$a);
	    $command=$args->command;
		if ($args->count<2) return $this->notFound();
		if (!in_array($command,array('thumb','url'))) return $this->notFound();
		$sizeArg = $args->trimExpand(1);
		if (!preg_match('/^\d*(x\d*)?$/', $sizeArg)) return $sizeArg;
		$size = explode('x', $sizeArg);
		$width = $size[0] ? "width=\"{$size[0]}\"" : "";
		$height = $size[1] ? "height=\"{$size[1]}\"" : "";
		
		if ($args->count==2) {
			$fileExt = 'png';
			$code = $args->cropExpand(2);
		}
		else {
			$fileExt = $args->trimExpand(2);
			$code = $args->cropExpand(3);
		}
		$hash = md5($code);
		$source = <<<END
<?xml version="1.0" standalone="no"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" 
"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">

<svg {$width} {$height} version="1.1"
xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http:///www.w3.org/1999/xlink">
$code
</svg>
END;

		$filePath = "svg/" . $hash{0} . "/" . $hash{0} . $hash{1};
		$fileDir = "$wgUploadDirectory/$filePath";
		
		$fileName = "{$sizeArg}_{$hash}";
		
		$svgFile = "$fileDir/$fileName.svg";
		$outFile = "$fileDir/$fileName.{$fileExt}";
		$thumbUrl = "$wgUploadPath/$filePath/$fileName.{$fileExt}";
		
		if(!file_exists($svgFile) or $_GET['action']=='purge')
		{
			@mkdir($fileDir, 0777, true);
			@file_put_contents($svgFile, $source);
		}

		if(!file_exists(trim($outFile)) || $_GET['action']=='purge')
		{
			$imageMagickCommand  = 
					" $svgFile"
					. " -size '{$sizeArg}!'"
					. " -blur 1x0.3"
					. " $outFile";
			@exec ("$wgImageMagickConvertCommand $imageMagickCommand",$dummy,$res);
		}

		if (!file_exists($outFile))
		{
			return "ERROR IN SVG: $wgImageMagickConvertCommand $imageMagickCommand  <br/> $res : $dummy (" . implode(", ",$dummy).")";
		}

		switch ($args->command)
		{
		case 'thumburl':
			return $thumbUrl;
		default:		
			$imgTag = "<img src=\"$thumbUrl\" alt=\"$alt\" width=\"$width\" height=\"$height\"/>";
			return array($imgTag,'isHTML'=>true);
		}
	}
	
	function fl_gk(&$P, $F, $A) 
	{
		$args=new XxxArgs($F, $A);
		$zone = $args->cropExpand(1,5);
		$LA = deg2rad($args->cropExpand(2,0));
		$FI = deg2rad($args->cropExpand(3,0));

		$AA = 6377397.155;
		$BB = 6356078.962818;
	 
		$B1 = 0.9999;
		$B2 = 500000;
		$B3 = 1000000;
		
		$EE = ( $AA * $AA - $BB * $BB ) / ( $BB * $BB );
		$E2 = ( $AA * $AA - $BB * $BB ) / ( $AA * $AA );
		$E4 = $E2 * $E2;
		$E6 = $E4 * $E2;
		$E8 = $E4 * $E4;
		$E10 = $E6 * $E4;
		
		$A = 1 
		      + $E2  *     3 /      4 
		      + $E4  *    45 /     64 
		      + $E6  *   175 /    256
		      + $E8  * 11025 /  16384 
		      + $E10 * 43659 /  65536
		      ;
		$B = $E2  *     3 /      4 
		      + $E4  *    15 /     16
		      + $E6  *   525 /    512 
		      + $E8  *  2205 /   2048
		      + $E10 * 72765 /  65536
		      ;
		$C = $E4  *    15 /     64 
		      + $E6  *   105 /    256
		      + $E8  *  2205 /   4096
		      + $E10 * 10395 /  16384
		      ;
		$D = $E6  *    35 /    512
		      + $E8  *   315 /   2048 
		      + $E10 * 31185 / 131072
		      ; 
		$E = $E8  *   315 /  16384
		      + $E10 *  3465 /  65536
		      ;
		$F = $E10 *   693 / 131072
		      ;

	 
		$SFI  = sin($FI);
		$CFI  = cos($FI);
		$CFI2 = $CFI * $CFI;
		$CFI4 = $CFI2 * $CFI2;
		
		$T  = $SFI / $CFI;
		$T2 = $T * $T;
		$T4 = $T2 * $T2;
	 
		$A1 = $AA * $AA / sqrt( $AA * $AA + $BB * $BB * $T2 );
		$A2 = $A1 * $SFI / 2;
		$A3 = $A1 * $CFI2 / 6 * ( 1 - $T2 + $EE * $CFI2 );
		$A4 = $A1 * $SFI * $CFI2 / 24 * ( 5 - $T2 + 9 * $EE * $CFI2  );
		$A5 = $A1 * $SFI * $CFI4 / 120 * ( 5 - 18 * $T2 + $T4 + $EE * ( 14 - 72 * $SFI * $SFI ) );
		$A6 = $A1 * $SFI * $CFI4 / 720 * ( 61 - 58 * $T2  + $T4 );
	 
		$LAM  = $LA - $zone * 3 * PI / 180.0;
		$LAM2 = $LAM * $LAM;
		$LAM4 = $LAM2 * $LAM2;
		
		$XX = $AA * ( 1 - $E2 ) 
		       * ( $A * $FI 
		       - $B /  2 * sin( 2 * $FI ) 
		       + $C /  4 * sin( 4 * $FI ) 
		       - $D /  6 * sin( 6 * $FI ) 
		       + $E /  8 * sin( 8 * $FI ) 
		       - $F / 10 * sin( 10 * $FI ) 
		       );
	 
		$XGP = 1 * $XX + $A2 * $LAM2 + $A4 * $LAM4 + $A6 * $LAM4 * $LAM2;
		$YGP = $A1 * $LAM + $A3 * $LAM2 * $LAM + $A5 * $LAM4 * $LAM;
		$Y = $YGP * $B1 + 1 * $B2 + $zone * $B3;
		$X = $XGP * $B1;
		switch ($args->command) {
		case 'x': 
			return round($X);
		case 'y': 
			return round($Y); 
		default: 
			return $this->notFound();
		}
	}
}
XxxInstaller::Install('CultureCustom');

function weGetPageProperty ($title, $prop) {
  if (!($title instanceof Title)) {
    $title = Title::newFromText($t);
  }
	if(!$title) return null;
	$article=new Article($title);
	$pid=$article->getId();
	if($pid==0) return null;
	if($title->isRedirect()) {
		$title = $article->followRedirect();
		$article=new Article($title);
		$pid=$article->getId();
		if($pid==0) return null;						
	}
	
	$dbr = wfGetDB( DB_SLAVE );
	$res = $dbr->select( array('page_props' ),
		array( 'pp_value' ),
		array( 'pp_propname' => $prop, 'pp_page' => $pid),
		__METHOD__ );
	if( $res === false ) return null;
	foreach( $res as $row ) {
		$val=$row->pp_value;
	}
	
	$dbr = wfGetDB( DB_SLAVE );
	$res = $dbr->select( array('page_props' ),
		array( 'pp_value' ),
		array( 'pp_propname' => "{$prop}__StripState__", 'pp_page' => $pid),
		__METHOD__ );
	if( $res === false ) return $val;
	
	foreach( $res as $row ) {
		$stripState=unserialize($row->pp_value);
	}
  if ($stripState) $val = $stripState->unstripBoth($val);
	return $val;
	if (is_array($stripState->nowiki->data)) {
		foreach($stripState->nowiki->data as $k=>$v) {
			$P->mStripState->nowiki->setPair($k,$v);
		}
	}
	if (is_array($stripState->general->data)) {
		foreach($stripState->general->data as $k=>$v) {
			$P->mStripState->general->setPair($k,$v);
		}
	}
	return $val;
}

function weGetPagePropertyParsed ($title, $prop, $atTitle = null) {
  if (!$atTitle) $atTile = $title;
  
  $val = weGetPageProperty($title,$prop);
  global$wguser;
	$newParser = new Parser();
	$options = ParserOptions::newFromUser($wgUser);
	$text =  $newParser->parse( $val, $title, $options, false)->mText;
	return $text;
	return array($parser->mStripState->unstripBoth($text),'isHTML'=>true);
}
?>
