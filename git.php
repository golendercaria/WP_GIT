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

	public function __construct(){

		add_action('init', array($this, 'init'), 100 );

	}

	public function init(){
		
		$this->load_git_information();

		$this->parse_git_information();

		pre( $this->current_branch );
		pre( $this->last_logs );

		die();
	}

	public function parse_git_information(){
		$head = end(explode("/", $this->head));
		$this->current_branch = $head;
	}

	public function load_git_information(){


		// retrieve head
		$git_path = ABSPATH . ".git/HEAD";
		if( file_exists($git_path) ){

			$this->head = file_get_contents($git_path);
			preg_match('/ref: (refs\/heads)\/(.*)/m', $this->head, $match);

			$this->ref_head = $match[1] ?? null;
			$this->current_branch = $match[2] ?? null;

		}


		// retrieve logs
		$git_logs_path = ABSPATH . ".git/logs/" . $this->ref_head . "/" . $this->current_branch;
		if( file_exists($git_logs_path) ){
			$this->logs = file_get_contents($git_logs_path);
			$this->parse_logs();
		}


		pre($this->branch );
	

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

		$this->last_logs = $history;

	}

}

new git();