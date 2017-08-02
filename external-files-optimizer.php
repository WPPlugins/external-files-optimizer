<?php
/*
Plugin Name: External Files Optimizer
Plugin URI: http://julienappert.com/realisations/plugin-external-files-optimizer
Author: Julien Appert
Author URI: http://julienappert.com
Version: 0.1.2
Description: automatically combine and compress css/js files generate with wp_head and wp_footer
*/

class WPEFOptimizer{

	function WPEFOptimizer(){$this->__construct();}
		
	function __construct(){
		add_action('get_header', array(&$this,'get_header'),999);
		add_action('get_footer', array(&$this,'get_footer'),999);
		register_activation_hook( __FILE__, array(&$this,'activate') );
		$this->content = array();
	}
	
	function activate(){
		if(!get_option('efoptimizer_externalfiles')){
			add_option('efoptimizer_externalfiles',
				array('styles'=>array(),	'scripts'=>array()	)
			);
		}		
	}
	
	function get_header(){
		ob_start();
		wp_head();
		$content = ob_get_contents();
		ob_end_clean();
		$this->content['head'] = array();
		$this->content['head']['content'] = '';
		$this->content['head']['js'] = array();
		$this->content['head']['css'] = array();
		$this->analyse('head',$content);
		remove_all_actions('wp_head');
		add_action('wp_head',array(&$this,'wp_head'), 999);			
	}
	
	function get_footer(){
		ob_start();
		wp_footer();
		$content = ob_get_contents();
		ob_end_clean();	
		$this->content['footer'] = array();
		$this->content['footer']['content'] = '';
		$this->content['footer']['js'] = array();
		$this->content['footer']['css'] = array();
		$this->analyse('footer',$content);
		remove_all_actions('wp_footer');
		add_action('wp_footer',array(&$this,'wp_footer'), 999);		
	}
	
	function analyse($pos,$content){
		$extfiles = get_option('efoptimizer_externalfiles');
		
		preg_match_all("/\<link(.*)\/>\n*/", $content, $matches);
		if(count($matches[1])>0){
			foreach($matches[1] as $key=>$match){
				preg_match('/rel=(\'|")stylesheet(\'|")/', $match, $submatch);
				if(count($submatch)>0){
					preg_match("/href=('|\")([^'\"]*)('|\")/", $match, $submatch);
					if(isset($submatch[2])){
						$aBasename = explode('?',$submatch[2]);
						$basename = $aBasename[0];
						$basename = str_replace(array('.','_'),'-',basename($basename,'.css'));
						$dirname = str_replace(array('.','_'),'-',dirname($submatch[2]));
						$aDirname = explode('/',$dirname);
						$dirname = $aDirname[count($aDirname)-1];
						$extfiles['styles'][$dirname."-".$basename] = $aBasename[0];
						$this->content[$pos]['css'][] = $dirname."-".$basename;
						$content = str_replace($matches[0][$key],'',$content);
					}
				}
			}
		}
		preg_match_all("/\<script type='text\/javascript' src='(.*)'\>\<\/script\>\n/", $content, $matches);
		if(count($matches[1])>0){
			foreach($matches[1] as $key=>$match){
						$aBasename = explode('?',$match);
						$basename = $aBasename[0];
						$basename = str_replace(array('.','_'),'-',basename($basename,'.js'));
						$dirname = str_replace(array('.','_'),'-',dirname($match));
						$aDirname = explode('/',$dirname);
						$dirname = $aDirname[count($aDirname)-1];
						$extfiles['scripts'][$dirname."-".$basename] = $aBasename[0];
						$this->content[$pos]['js'][] = $dirname."-".$basename;
						$content = str_replace($matches[0][$key],'',$content);		
			}
		}	
		update_option('efoptimizer_externalfiles',$extfiles);		
		$this->content[$pos]['content'] = $content;
	}
	
	function wp_head(){
		if(count($this->content['head']['css'])>0){
			$sCss = '';
			foreach($this->content['head']['css'] as $key=>$css){
				if($key > 0)	$sCss .= ',';
				$sCss .= $css;
			}
			echo '<link rel="stylesheet" href="'.WP_PLUGIN_URL.'/external-files-optimizer/load-styles.php?c=gzip&amp;load='.$sCss.'" type="text/css" media="all" />'."\n";
		}
		if(count($this->content['head']['js'])>0){
			$sJs = '';
			foreach($this->content['head']['js'] as $key=>$js){
				if($key > 0)	$sJs .= ',';
				$sJs .= $js;
			}
			echo '<script type="text/javascript" src="'.WP_PLUGIN_URL.'/external-files-optimizer/load-scripts.php?c=gzip&amp;load='.$sJs.'"></script>'."\n";		
		}
		echo $this->content['head']['content'];		
	}
	
	function wp_footer(){
		if(count($this->content['footer']['css'])>0){
			$sCss = '';
			foreach($this->content['footer']['css'] as $key=>$css){
				if($key > 0)	$sCss .= ',';
				$sCss .= $css;
			}
			echo '<link rel="stylesheet" href="'.WP_PLUGIN_URL.'/external-files-optimizer/load-styles.php?c=gzip&amp;load='.$sCss.'" type="text/css" media="all" />'."\n";
		}
		if(count($this->content['footer']['js'])>0){
			$sJs = '';
			foreach($this->content['footer']['js'] as $key=>$js){
				if($key > 0)	$sJs .= ',';
				$sJs .= $js;
			}
			echo '<script type="text/javascript" src="'.WP_PLUGIN_URL.'/external-files-optimizer/load-scripts.php?c=gzip&amp;load='.$sJs.'"></script>'."\n";		
		}
		echo $this->content['footer']['content'];		
	}	
}
new WPEFOptimizer();