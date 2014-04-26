<?php
/**
 * Plugin Name: WP Airlink
 * Plugin URI: 
 * Description: A WordPress hosted Airlink
 * Version: 0.1
 * Author: jackreichert
 * Author URI: http://www.jackreichert.com
 * License: GPL2
 */
 
 $airlink = new WP_Airlink();
 
 class WP_Airlink {
	public function __construct() {
		// create post type
		add_action( 'init', array($this, 'airlink_post_type'), 0 );
		add_action( 'init', array($this, 'airlink_addview'), 0 );
		add_action( 'admin_menu', array($this, 'register_my_custom_submenu_page' ));
		add_action( 'wp_head', array( $this, 'check_url') ); # serve the file
	}
	 
	 public function check_url() {  
		global $wp_query;

		// skip if not asset file
		if (get_post_type($wp_query->posts[0]->ID) != 'wp_airlink') {  
			return false;  
		} 
		
		 header( 'Location: ' .get_post_meta($wp_query->posts[0]->ID, 'url', true));
		 exit;
	 }
	
	public function airlink_addview() {
		if ( (FALSE !== strpos($_SERVER['REQUEST_URI'], '/wp_airlink')) && ($_GET['h'] === get_option('airlink_hash') || current_user_can('edit_posts')) ) {
			if (isset($_GET['a'])) {
				switch($_GET['a']) {
					case 'c':
						self::addLink();
						break;
					case 'r':
						$count = (isset($_GET['c'])) ? intval($_GET['c']) : 5;
						self::showLinks($count);
						break;
				}
			}
		} 
	}
	
	private function addLink(){
		$url = esc_url_raw($_GET['u'], array('http','https'));
		$parse = parse_url($url);
		$title = isset($_GET['t']) ? urldecode($_GET['t']) : $parse['host'];
		
		$new_link = array(
			'post_title' 	=> $title,
			'post_type' 	=> 'wp_airlink',
			'post_status'	=> 'publish'
		);
		$id = wp_insert_post($new_link);
		add_post_meta($id, 'url', $url, true);
		
		if ( 'ssl' === $_GET['p'] ) {
			wp_redirect( $url );
			exit();
		} else {
			die("alert(document.title + ' successfully added!');");
		}
	}
	
	private function showLinks($posts_per_page = 5) { 
		$args = array(
			'posts_per_page'   => $posts_per_page,
			'orderby'          => 'post_date',
			'order'            => 'DESC',
			'post_type'        => 'wp_airlink'
		);
		$wp_airlinks = get_posts($args);
		
		if (count($wp_airlinks) > 0) :
?>
			<ul style="margin:1em;padding:0">
				<?php foreach ($wp_airlinks as $link) : ?>
					<li style="border:1px solid #cdcdcd;padding:1em;margin:1em;list-style:none;text-align:center"><p><a href="<?php echo esc_url_raw(get_post_meta($link->ID, 'url', true)); ?>"><?php echo $link->post_title; ?></a></p><p style="color:#ababab"><?php echo get_post_meta($link->ID, 'url', true); ?> - <?php echo (strtotime('midnight yesterday - 1 second') > get_the_time('U', $link->ID)) ? get_the_time('F j, Y', $link->ID) : get_the_time('g:i a', $link->ID); ?></p></li>
				<?php endforeach; ?>
			</ul>
			<div style="text-align: center;">
				<button><a href="<?php echo get_bloginfo('url') . "/wp_airlink?a=r&h=" . get_option('airlink_hash'); ?>&c=10">10 Links</a></button>
				<button><a href="<?php echo get_bloginfo('url') . "/wp_airlink?a=r&h=" . get_option('airlink_hash'); ?>&c=20">20 Links</a></button>
				<button><a href="<?php echo get_bloginfo('url') . "/wp_airlink?a=r&h=" . get_option('airlink_hash'); ?>&c=50">50 Links</a></button>
				<button><a href="<?php echo get_bloginfo('url') . "/wp_airlink?a=r&h=" . get_option('airlink_hash'); ?>&c=-1">All</a></button>
			</div>
<?php 	else : ?>
			<p>No links found</p>
<?php
		endif;
		die();
	}
	
	public function register_my_custom_submenu_page() {
		add_submenu_page( 
	          'edit.php?post_type=wp_airlink' 
	        , 'WP Airlinks Manager' 
	        , 'Manager'
	        , 'edit_posts'
	        , 'wp-airlinks'
	        , array($this, 'wp_airlinks_callback')
	    );
	
	}
	
	public function wp_airlinks_callback() {
		$hasSSL = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
		$addLink = "javascript:(function(){var wpalurl ='" . get_bloginfo('url') . "/wp_airlink?a=c&h=" . get_option('airlink_hash') . "&u='+encodeURIComponent(window.location.href)+'&t='+encodeURIComponent(document.title);if('https:'===window.location.protocol){window.location.href=wpalurl+'&p=ssl'}else{document.body.appendChild(document.createElement('script')).src=wpalurl}}());"; ?>
		<h2>Are you installed?</h2>
		<?php if ('' == get_option('airlink_hash')) {
			add_option('airlink_hash', hash('crc32b', get_bloginfo('url').time('U')));
		} ?>
		<h3>Add links:</h3>
		<p class="pressthis"><a href="<?php echo get_bloginfo('url') . "/wp_airlink?a=r&h=" . get_option('airlink_hash'); ?>"><span>View links</span></a>
			&nbsp;
			<a href="<?php echo $addLink; ?>" oncontextmenu="if(window.navigator.userAgent.indexOf('WebKit')!=-1||window.navigator.userAgent.indexOf('MSIE')!=-1){jQuery('.pressthis-code').show().find('textarea').focus().select();return false;}"><span>Add link</span></a>
		</p>
		
		<div class="pressthis-code" style="display:none;width:90%">
			<p class="description">If your bookmarks toolbar is hidden: copy the code below, open your Bookmarks manager, create new bookmark, type "Add link" into the name field and paste the code into the URL field.</p>
			<p><textarea rows="5" style="width:90%" readonly="readonly"><?php echo $addLink; ?></textarea></p>
		</div>
<?php
	}
 
	 // Register Custom Post Type
	public function airlink_post_type() {
	
		$labels = array(
			'name'                => 'Airlinks',
			'singular_name'       => 'Airlink',
			'menu_name'           => 'Airlinks',
			'parent_item_colon'   => '',
			'all_items'           => 'All Airlinks',
			'view_item'           => 'View link',
			'add_new_item'        => 'Add New Airlink',
			'add_new'             => 'Add New',
			'edit_item'           => 'Edit Airlink',
			'update_item'         => 'Update Airlink',
			'search_items'        => 'Search Airlink',
			'not_found'           => 'Not found',
			'not_found_in_trash'  => 'Not found in Trash',
		);
		$args = array(
			'label'               => 'wp_airlink',
			'description'         => 'Share & save links across browsers',
			'labels'              => $labels,
			'supports'            => array( 'title', 'author', 'custom-fields', ),
			'taxonomies'          => array( 'category', 'post_tag' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'menu_position'       => 5,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
		);
		register_post_type( 'wp_airlink', $args );
	
	}
 }