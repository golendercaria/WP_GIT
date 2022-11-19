<?php
/*
Plugin Name: WPGIT
Plugin URI: https://github.com/golendercaria/WP_GIT
Description: Just for display current repository for developpment team
Version: 1.0.0
Requires at least: any
Requires PHP: 8.0
Author: Yann Vangampelaere
Author URI: https://nouslesdevs.com
License: GPLv2 or later
Text Domain: git
*/

namespace GOL_git;

class git{

	public $current_branch 	= null;
	public $last_logs 		= array();
	public $version 		= "1.0.0";

	public function __construct(){

		add_action( 'init' , array($this, 'init'), 99 );
		add_action( 'admin_bar_menu' , array( $this, 'add_toolbar_items' ), 100);
		add_action( 'admin_enqueue_scripts' , array( $this, 'admin_styles') );
		add_action( 'wp_enqueue_scripts' , array( $this, 'front_styles') );
		add_action( 'wp_footer', array( $this, 'render_to_front' ) );

	}

	public function render_to_front(){
		?>
		<div id="<?= __NAMESPACE__ ?>">
			<button class="refresh"></button>
			<div class="branch"><?= $_SESSION[ __NAMESPACE__ ]["branch"] ?? null; ?></div>
			<div class="logs">
				<?php
					if( !empty($_SESSION[ __NAMESPACE__ ]["logs"]) ){
						foreach( $_SESSION[ __NAMESPACE__ ]["logs"] as $log){
							?>
							<div class="log">
								<div class="author"><?= $log["author"] ?></div>
								<div class="date"><?= $log["date"] ?></div>
								<div class="message"><?= $log["message"] ?></div>
							</div>
							<?php
						}
					}
				?>
			</div>
		</div>
		<?php
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


	function front_styles() {
		wp_enqueue_style(__NAMESPACE__, plugin_dir_url(__FILE__) . 'css/build/app.css',array(), $this->version );
	}

	function admin_styles() {
		wp_enqueue_style(__NAMESPACE__, plugin_dir_url(__FILE__) . 'css/build/admin.css',array(), $this->version );
	}


	public function init(){

		if( !session_id() ){
			session_start();
		}

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
		$git_index_path = ABSPATH . ".git/index";
		$git_head_path = ABSPATH . ".git/HEAD";
		$has_changed = false;

		// get branch
		if( file_exists($git_head_path) && file_exists($git_index_path) ){
			
			$timestamp_last_update_index_file = filemtime($git_index_path);

			// update branch information if file has updated
			if( $_SESSION[ __NAMESPACE__ ]["last_update"] < $timestamp_last_update_index_file ){

				$has_changed = true;
				$_SESSION[ __NAMESPACE__ ]["last_update"] = $timestamp_last_update_index_file;

				// read HEAD file
				$this->head = file_get_contents($git_head_path);
				preg_match('/ref: (refs\/heads)\/(.*)/m', $this->head, $match);

				$this->ref_head 		= $match[1] ?? null;
				$this->current_branch 	= $match[2] ?? null;

				$_SESSION[ __NAMESPACE__ ]["branch"] = $this->current_branch;
			
			}

		}

		// retrieve logs if empty
		if(	$has_changed || empty($_SESSION[ __NAMESPACE__ ]["logs"]) ){

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
					"date" => $time->format("d-m-Y"),
					"message"	=> $match[4]
				);

			}

			$history = array_reverse( $history );

		}

		return $history;

	}

}

new git();