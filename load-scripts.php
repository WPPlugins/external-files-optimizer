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
$expires_offset = 31536000;
$out = '';
$snoopy = new Snoopy();

$extfiles = get_option('efoptimizer_externalfiles');

foreach( $load as $handle ) {
	if ( !array_key_exists($handle, $extfiles['scripts']) )
		continue;

		
	if(substr($extfiles['scripts'][$handle],-4) == '.php' || substr($extfiles['scripts'][$handle], 0, strlen(get_bloginfo('wpurl'))) != get_bloginfo('wpurl')){
		if($snoopy->fetch($extfiles['scripts'][$handle])){
			$out .= $snoopy->results;
		}
	}
	else{		
		$path = ABSPATH . str_replace(get_bloginfo('wpurl'),'',$extfiles['scripts'][$handle]);		
		$out .= get_file($path) . "\n";
	}
}
  
  
header('Content-Type: application/x-javascript; charset=UTF-8');
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
