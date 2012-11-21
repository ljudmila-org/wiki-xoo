<?php
/**
 * API for fetching the HTML to embed remote content based on a provided URL.
 * Used internally by the {@link WP_Embed} class, but is designed to be generic.
 *
 * @link http://codex.wordpress.org/oEmbed oEmbed Codex Article
 * @link http://oembed.com/ oEmbed Homepage
 *
 * @package WordPress
 * @subpackage oEmbed
 */

XxxInstaller::Install('XuuEmbed');

class XuuEmbed extends Xxx {
  var $providers = array();
  function fl_embed($parser,$frame,$a) {
    $args = new XxxArgs($frame,$a);
    if ($args->count<1) return $this->notFound();
  
    $url = $args->trimExpand(1);
    $width = intval($args->trimExpand(2,0));
    $height = intval($args->trimExpand(3,0));
    switch ($args->command){
    case 'html':
      return array(
         0 => $this->get_html($url,array('width'=>$width,'height'=>$height)),
         'isHTML' => true
      );
    }
    return $this->notFound();
  }
  function fn_oembed($parser,$url,$width=false,$height=false,$template=false) {
    return array(
       0 => $this->get_html($url,array('width'=>$width,'height'=>$height)),
       'isHTML' => true
    );
  }

//$newFrame=$this->newExtendedFrame($f,$argArray);


	function __construct() {
		// List out some popular sites that support oEmbed.
		// The WP_Embed class disables discovery for non-unfiltered_html users, so only providers in this array will be used for them.
		// Add to this list using the wp_oembed_add_provider() function (see it's PHPDoc for details).
		$this->providers = array(
			'#http://(www\.)?youtube.com/watch.*#i'         => array( 'http://www.youtube.com/oembed',            true  ),
			'http://youtu.be/*'                             => array( 'http://www.youtube.com/oembed',            false ),
			'http://blip.tv/*'                              => array( 'http://blip.tv/oembed/',                   false ),
			'#http://(www\.)?vimeo\.com/.*#i'               => array( 'http://www.vimeo.com/api/oembed.{format}', true  ),
			'#http://(www\.)?dailymotion\.com/.*#i'         => array( 'http://www.dailymotion.com/api/oembed',    true  ),
			'#http://(www\.)?flickr\.com/.*#i'              => array( 'http://www.flickr.com/services/oembed/',   true  ),
			'#http://(.+)?smugmug\.com/.*#i'                => array( 'http://api.smugmug.com/services/oembed/',  true  ),
			'#http://(www\.)?hulu\.com/watch/.*#i'          => array( 'http://www.hulu.com/api/oembed.{format}',  true  ),
			'#http://(www\.)?viddler\.com/.*#i'             => array( 'http://lab.viddler.com/services/oembed/',  true  ),
			'http://qik.com/*'                              => array( 'http://qik.com/api/oembed.{format}',       false ),
			'http://revision3.com/*'                        => array( 'http://revision3.com/api/oembed/',         false ),
			'http://i*.photobucket.com/albums/*'            => array( 'http://photobucket.com/oembed',            false ),
			'http://gi*.photobucket.com/groups/*'           => array( 'http://photobucket.com/oembed',            false ),
			'#http://(www\.)?scribd\.com/.*#i'              => array( 'http://www.scribd.com/services/oembed',    true  ),
			'http://wordpress.tv/*'                         => array( 'http://wordpress.tv/oembed/',              false ),
			'#http://(answers|surveys)\.polldaddy.com/.*#i' => array( 'http://polldaddy.com/oembed/',             true  ),
			'#http://(www\.)?funnyordie\.com/videos/.*#i'   => array( 'http://www.funnyordie.com/oembed',         true  ),
		);
   }

   function shortcode_parse_atts($text) {
	   $atts = array();
	   $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
	   $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
	   if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
		   foreach ($match as $m) {
			   if (!empty($m[1]))
				   $atts[strtolower($m[1])] = stripcslashes($m[2]);
			   elseif (!empty($m[3]))
				   $atts[strtolower($m[3])] = stripcslashes($m[4]);
			   elseif (!empty($m[5]))
				   $atts[strtolower($m[5])] = stripcslashes($m[6]);
			   elseif (isset($m[7]) and strlen($m[7]))
				   $atts[] = stripcslashes($m[7]);
			   elseif (isset($m[8]))
				   $atts[] = stripcslashes($m[8]);
		   }
	   } else {
		   $atts = ltrim($text);
	   }
	   return $atts;
   }

	/**
	 * The do-it-all function that takes a URL and attempts to return the HTML.
	 *
	 * @see WP_oEmbed::discover()
	 * @see WP_oEmbed::fetch()
	 * @see WP_oEmbed::data2html()
	 *
	 * @param string $url The URL to the content that should be attempted to be embedded.
	 * @param array $args Optional arguments. Usually passed from a shortcode.
	 * @return bool|string False on failure, otherwise the UNSANITIZED (and potentially unsafe) HTML that should be used to embed.
	 */
	function get_html( $url, $args = array() ) {
		$provider = false;

		if ( !isset($args['discover']) )
			$args['discover'] = true;

		foreach ( $this->providers as $matchmask => $data ) {
			list( $providerurl, $regex ) = $data;

			// Turn the asterisk-type provider URLs into regex
			if ( !$regex )
				$matchmask = '#' . str_replace( '___wildcard___', '(.+)', preg_quote( str_replace( '*', '___wildcard___', $matchmask ), '#' ) ) . '#i';
        
			if ( preg_match( $matchmask, $url ) ) {
				$provider = str_replace( '{format}', 'json', $providerurl ); // JSON is easier to deal with than XML
				$args['format'] = 'json';
				break;
			}
		}

		if ( !$provider && $args['discover'] )
			list($provider,$args['format']) = $this->discover( $url );

		if ( !$provider || false === $data = $this->fetch( $provider, $url, $args ) )
			return false;

		return /*'data <pre>'.print_r($data,1)."</pre>".*/ $this->data2html( $data, $url );
	}

	/**
	 * Attempts to find oEmbed provider discovery <link> tags at the given URL.
	 *
	 * @param string $url The URL that should be inspected for discovery <link> tags.
	 * @return bool|string False on failure, otherwise the oEmbed provider URL.
	 */
	function discover( $url ) {
		$providers = array();

		// Fetch URL content
		if ( $html = file_get_contents( $url ) ) {

			// <link> types that contain oEmbed provider URLs
			$linktypes = array(
				'application/json+oembed' => 'json',
				'text/xml+oembed' => 'xml',
				'application/xml+oembed' => 'xml', // Incorrect, but used by at least Vimeo
			);

			// Strip <body>
			$html = substr( $html, 0, stripos( $html, '</head>' ) ).'</html>';

			// Do a quick check
			$tagfound = false;
			foreach ( $linktypes as $linktype => $format ) {
				if ( stripos($html, $linktype) ) {
					$tagfound = true;
					break;
				}
			}

			if ( $tagfound && preg_match_all( '/<link([^<>]+)>/i', $html, $links ) ) {
				foreach ( $links[1] as $link ) {
					$atts = $this->shortcode_parse_atts( $link );

					if ( !empty($atts['type']) && !empty($linktypes[$atts['type']]) && !empty($atts['href']) ) {
						$providers[$linktypes[$atts['type']]] = $atts['href'];

						// Stop here if it's JSON (that's all we need)
						if ( 'json' == $linktypes[$atts['type']] )
							break;
					}
				}
			}
		}

		// JSON is preferred to XML
		if ( !empty($providers['json']) )
			return array($providers['json'],'json');
		elseif ( !empty($providers['xml']) )
			return array($providers['xml'],'xml');
		else
			return false;
	}

	/**
	 * Connects to a oEmbed provider and returns the result.
	 *
	 * @param string $provider The URL to the oEmbed provider.
	 * @param string $url The URL to the content that is desired to be embedded.
	 * @param array $args Optional arguments. Usually passed from a shortcode.
	 * @return bool|object False on failure, otherwise the result in the form of an object.
	 */
	function fetch( $provider, $url, $args = array() ) {
		if (strpos($provider,"?")===false) $provider .= '?url='.urlencode($url).'&format='.$args['format'];
		if ($args['width']) $provider .= '&maxwidth='.$args['width'];
		if ($args['height']) $provider .= '&maxheight='.$args['height'];
//echo "provider $provider<br>";
   	if ( ! $body = file_get_contents( $provider) )
			return false;
		$parse_method = "_parse_{$args['format']}";
		return $this->$parse_method( $body );
	}

	/**
	 * Parses a json response body.
	 *
	 * @since 3.0.0
	 * @access private
	 */
	function _parse_json( $response_body ) {
		return ( ( $data = json_decode( trim( $response_body ) ) ) && is_object( $data ) ) ? $data : false;
	}

	/**
	 * Parses an XML response body.
	 *
	 * @since 3.0.0
	 * @access private
	 */
	function _parse_xml( $response_body ) {
		if ( function_exists('simplexml_load_string') ) {
			$errors = libxml_use_internal_errors( 'true' );
			$data = simplexml_load_string( $response_body );
			libxml_use_internal_errors( $errors );
			if ( is_object( $data ) )
				return $data;
		}
		return false;
	}

	/**
	 * Converts a data object from {@link WP_oEmbed::fetch()} and returns the HTML.
	 *
	 * @param object $data A data object result from an oEmbed provider.
	 * @param string $url The URL to the content that is desired to be embedded.
	 * @return bool|string False on error, otherwise the HTML needed to embed.
	 */
	function data2html( $data, $url ) {
		if ( !is_object($data) || empty($data->type) )
			return false;

		switch ( $data->type ) {
			case 'photo':
				if ( empty($data->url) || empty($data->width) || empty($data->height) )
					return false;

				$title = ( !empty($data->title) ) ? $data->title : '';
				$return = '<a href="' . $url . '"><img src="' . $data->url . '" alt="' . addslashes($title) . '" width="' . $data->width . '" height="' . $data->height . '" /></a>';
				break;

			case 'video':
			case 'rich':
				$return = ( !empty($data->html) ) ? $data->html : false;
				break;

			case 'link':
				$return = ( !empty($data->title) ) ? '<a href="' . $url . '">' . $data->title . '</a>' : false;
				break;

			default;
				$return = false;
		}

      if ($return) {
         $file = $data->title ? "<a href='{$url}' title='{$data->title}'>{$data->title}</a>" : "<a href='{$url}' title='{$url}'>{$url}</a>";
         $author = $data->author_url ? "by <a href='{$data->author_url}' title='More from {$data->author_name}'>{$data->author_name}</a>" : "";
    
         $return = "
   <div class='oembed'>
     $return
     <div class='oembed-caption'>
         $file $author on {$data->provider_name}
     </div>
   </div>
         ";
      }
		// You can use this filter to add support for custom data types or to filter the result
		return $return;
	}

	/**
	 * Strip any new lines from the HTML.
	 *
	 * @access private
	 * @param string $html Existing HTML.
	 * @param object $data Data object from WP_oEmbed::data2html()
	 * @param string $url The original URL passed to oEmbed.
	 * @return string Possibly modified $html
	 */
	function _strip_newlines( $html, $data, $url ) {
		if ( false !== strpos( $html, "\n" ) )
			$html = str_replace( array( "\r\n", "\n" ), '', $html );

		return $html;
	}
}


?>
