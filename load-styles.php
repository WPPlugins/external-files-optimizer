<?php

include(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php' );
include(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-includes/class-snoopy.php' );

function get_file($path) {

	if ( function_exists('realpath') )
		$path = realpath($path);

	if ( ! $path || ! @is_file($path) )
		return '';
	return @file_get_contents($path);
}

$load = preg_replace( '/[^a-z0-9,_-]+/i', '', $_GET['load'] );
$load = explode(',', $load);
if ( empty($load) )
	exit;

$compress = ( isset($_GET['c']) && $_GET['c'] );
$force_gzip = ( $compress && 'gzip' == $_GET['c'] );
$rtl = ( isset($_GET['dir']) && 'rtl' == $_GET['dir'] );
$expires_offset = 31536000;
$out = '';
$snoopy = new Snoopy();
$extfiles = get_option('efoptimizer_externalfiles');

foreach( $load as $handle ) {
	if ( !array_key_exists($handle, $extfiles['styles']) )
		continue;

	$content = '';
	if(substr($extfiles['styles'][$handle],-4) == '.php' || substr($extfiles['styles'][$handle], 0, strlen(get_bloginfo('wpurl'))) != get_bloginfo('wpurl')){
		if($snoopy->fetch($extfiles['styles'][$handle])){
			$content = $snoopy->results;
		}
	}
	else{
		$path = ABSPATH . str_replace(get_bloginfo('wpurl'),'',$extfiles['styles'][$handle]);
		$content = get_file($path) . "\n";
	}
	
	// remplace les url() par les url absolues si nécessaire
	preg_match_all("/url\(['|\"]*([^'|\"|\)]*)['|\"]*\)/", $content, $matches,PREG_PATTERN_ORDER);	
	if(count($matches[1])>0){
		foreach($matches[1] as $key=>$match){
			if(substr($match,0,7) != 'http://'){
					$content = preg_replace("/(".str_replace(array('(',')','/'), array('\(','\)','\/'),$matches[0][$key]).")/", "url(".dirname($extfiles['styles'][$handle])."/".$match.")", $content);
			}
		}	
	}
	
	$out .= $content ;
}

header('Content-Type: text/css');
header('Expires: ' . gmdate( "D, d M Y H:i:s", time() + $expires_offset ) . ' GMT');
header("Cache-Control: public, max-age=$expires_offset");
if ( $compress && ! ini_get('zlib.output_compression') && 'ob_gzhandler' != ini_get('output_handler') ) {
	header('Vary: Accept-Encoding'); // Handle proxies
	if ( false !== strpos( strtolower($_SERVER['HTTP_ACCEPT_ENCODING']), 'deflate') && function_exists('gzdeflate') && ! $force_gzip ) {
		header('Content-Encoding: deflate');
		$out = gzdeflate( $out, 3 );
	} elseif ( false !== strpos( strtolower($_SERVER['HTTP_ACCEPT_ENCODING']), 'gzip') && function_exists('gzencode') ) {
		header('Content-Encoding: gzip');
		$out = gzencode( $out, 3 );
	}
}

echo $out;
exit;
