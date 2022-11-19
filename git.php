<?php
/*
Plugin Name: Git
Plugin URI: https://nouslesdevs.com
Description: Just for display current repository for developpment team
Version: 1.0.0
Requires at least: any
Requires PHP: 5.6
Author: Yann Vangampelaere
Author URI: https://nouslesdevs.com
License: GPLv2 or later
Text Domain: git
*/

namespace GOL_git;

class git{

	public $current_branch = null;
	public $last_logs = array();
	public $version = "1.0.0";

	public function __construct(){

		add_action( 'init' , array($this, 'init'), 99 );
		add_action( 'admin_bar_menu' , array( $this, 'add_toolbar_items' ), 100);
		add_action( 'admin_enqueue_scripts' , array( $this, 'admin_styles') );


	}

	public function add_toolbar_items( $admin_bar ){

		$admin_bar->add_menu( array(
			'id'    => __NAMESPACE__,
			'title' => $_SESSION[ __NAMESPACE__ ]["branch"] ?? "No git",
			'href'  => '#',
			'meta'  => array(
				'class' => __('git'),            
			),
		));

	}


	function admin_styles() {
		wp_enqueue_style(__NAMESPACE__, plugin_dir_url(__FILE__) . 'css/build/app.css',array(), $this->version );
	}


	public function init(){

		$this->load_git_information();

		//$this->parse_git_information();

	}

	/*
	public function parse_git_information(){
		$head = end(explode("/", $this->head));
		$this->current_branch = $head;
	}
	*/

	public function load_git_information(){

		if( 
			!isset($_SESSION[ __NAMESPACE__ ]) 
			|| empty($_SESSION[ __NAMESPACE__ ]) 
		){
			$_SESSION[ __NAMESPACE__ ] = array(
				"branch" 		=> "",
				"last_update" 	=> 0,
				"logs"			=> array()
			);
		}



		// retrieve head
		$git_path = ABSPATH . ".git/HEAD";

		// get branch
		if( file_exists($git_path) ){
			
			$timestamp_last_update_head_file = filemtime($git_path);

			// update branch information if file has updated
			if( $_SESSION[ __NAMESPACE__ ]["last_update"] < $timestamp_last_update_head_file ){
			
				$_SESSION[ __NAMESPACE__ ]["last_update"] = $timestamp_last_update_head_file;

				// read HEAD file
				$this->head = file_get_contents($git_path);
				preg_match('/ref: (refs\/heads)\/(.*)/m', $this->head, $match);

				$this->ref_head 		= $match[1] ?? null;
				$this->current_branch 	= $match[2] ?? null;

				$_SESSION[ __NAMESPACE__ ]["branch"] = $this->current_branch;
			
			}

		}

		// retrieve logs if empty
		if(	empty($_SESSION[ __NAMESPACE__ ]["logs"]) ){

			// TODO button for refresh logs in ajax
			// make new functions in later
			$git_logs_path = ABSPATH . ".git/logs/" . $this->ref_head . "/" . $this->current_branch;
			if( file_exists($git_logs_path) ){
				$this->logs = file_get_contents($git_logs_path);
				$_SESSION[ __NAMESPACE__ ]["logs"] = $this->parse_logs();
			}

		}



	}

	public function parse_logs(){
		
		$logs_line = explode("\n", trim($this->logs) );
		if( !empty($logs_line) ){

			$history = array();
			
			foreach( $logs_line as $key => $line ){
				preg_match('/[0-9a-z]{40} [0-9a-z]{40} ([a-z]{1,}) <> ([0-9]{10})+.+commit( \(initial\))?: (.*)/m', $line, $match);
				
				$time = new \DateTime();
				$time->setTimestamp($match[2]);

				$history[] = array(
					"author" 	=> $match[1],
					"timestamp" => $time->format("d-m-Y"),
					"message"	=> $match[4]
				);

			}

			$history = array_reverse( $history );

		}

		return $history;

	}

}

new git();