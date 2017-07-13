<?php
/*
Plugin Name: NT Redirect
Plugin URI: http://hutruc.com/wordpress/wordpress-plugins/nt-redirect-tao-trang-chuyen-huong-cho-lien-ket-ngoai.html
Description: Make redirect page and automatically insert 'rel=nofollow' and 'target=_blank' to all the external links.
Version: 1.1
Author: Trung Huynh
Author URI: http://hutruc.com
Author Email: contact@hutruc.com
Tags: redirect, redirect link, redirect for external link, external link, rel, nofollow. rel nofollow, link, target _blank
License: GPL2
*/

if ( ! class_exists( 'NT_Redirect_Plugin' ) ) {

  class NT_Redirect_Plugin {

		public $_name;
		public $page_title;
		public $page_name;
		public $page_id;
		public $nofollow;
		public $white_list;
		public $ex_ext;
		public $target;

		public function __construct() {

			$this->_name      = 'nt_redirect';
			$this->page_title = 'Redirect';
			$this->page_name  = 'redirect';
			$this->page_id    = '0';
			$this->nofollow   = '1';
			$this->white_list = $_SERVER['HTTP_HOST'];
			$this->ex_ext     = 'png|bmp|gif';
			$this->target     = '1';
			$this->auto       = '1';
			$this->time       = '5';
			$this->ads1       = 'ads1';
			$this->ads2       = 'ads2';
			$this->css        = '.nt-redict-btn a { background: #1abc9c; border-bottom: 3px solid #16a085; border-radius: 3px; padding: .5em 1em; color: #fff; font-weight: bold; text-decoration: none; margin: .5em 1em .5em 0;}
.nt-redict-btn a:hover { background: #16a085; color: #fff;}
.nt-redict-btn .cancel { background: #e67e22; border-color: #d35400; }
.nt-redict-btn .cancel:hover { background: #d35400; }';

			register_activation_hook( __FILE__, array( $this, 'nt_redirect_activate' ) );
			register_deactivation_hook( __FILE__, array( $this, 'nt_redirect_deactivate' ) );
			register_uninstall_hook( __FILE__, array( $this, 'nt_redirect_uninstall' ) );

			add_filter( 'parse_query', array( $this, 'nt_redirect_query_parser' ) );
			add_filter( 'the_posts', array( $this, 'nt_redirect_page_filter' ) );
			add_filter( 'plugin_action_links', array( $this, 'nt_redirect_settings_link' ), 10, 2 );
			add_action( 'admin_menu', array( $this, 'nt_redirect_modify_menu' ) );
			add_action( 'plugins_loaded', array( $this, 'nt_redirect_init' ) );

		}

		public function nt_redirect_activate() {

			global $wpdb;

			delete_option( $this->_name . '_page_title' ); add_option( $this->_name . '_page_title', $this->page_title, '', 'yes' );
			delete_option( $this->_name . '_page_name' ); add_option( $this->_name . '_page_name', $this->page_name, '', 'yes' );

			if ( ! get_option( $this->_name . '_nofollow' ) )
				add_option( $this->_name . '_nofollow', $this->nofollow, '', 'yes' );

			if ( ! get_option( $this->_name . '_white_list' ) )
				add_option( $this->_name . '_white_list', $this->white_list, '', 'yes' );

			if ( ! get_option( $this->_name . '_ex_ext' ) )
				add_option( $this->_name . '_ex_ext', $this->ex_ext, '', 'yes' );

			if ( ! get_option( $this->_name . '_target' ) )
				add_option( $this->_name . '_target', $this->target, '', 'yes' );

			if ( ! get_option( $this->_name . '_auto' ) )
				add_option( $this->_name . '_auto', $this->auto, '', 'yes' );

			if ( ! get_option( $this->_name . '_time' ) )
				add_option( $this->_name . '_time', $this->time, '', 'yes' );

			if ( ! get_option( $this->_name . '_css' ) )
				add_option( $this->_name . '_css', $this->css, '', 'yes' );	

			if ( ! get_option( $this->_name . '_ads1' ) )
				add_option( $this->_name . '_ads1', $this->css, '', 'yes' );			

			if ( ! get_option( $this->_name . '_ads2' ) )
				add_option( $this->_name . '_ads2', $this->css, '', 'yes' );			

			$the_page = get_page_by_title( $this->page_title );

			if ( ! $the_page ) {

				$_p = array();

				$_p['post_title'] 	= $this->page_title;
				$_p['post_content'] 	= __( 'This text may be overridden by the plugin. You shouldn\'t edit it.', 'nt-redirect' );
				$_p['post_status'] 	= 'publish';
				$_p['post_type'] 	= 'page';
				$_p['comment_status'] 	= 'closed';
				$_p['ping_status'] 	= 'closed';
				$_p['post_category'] 	= array(1);

				$this->page_id = wp_insert_post( $_p );

			} else {

				$this->page_id         = $the_page->ID;
				$the_page->post_status = 'publish';
				$this->page_id         = wp_update_post( $the_page );

			}

			delete_option( $this->_name . '_page_id' );
			add_option( $this->_name . '_page_id', $this->page_id );

			update_post_meta( $this->page_id, '_wp_page_template', plugin_basename(__FILE__) );

		}

		public function nt_redirect_deactivate() {

			$this->nt_redirect_delete_page();
			$this->nt_redirect_delete_options();

		}

		public function nt_redirect_uninstall() {

			$this->nt_redirect_delete_page( true );
			$this->nt_redirect_delete_options();

		}

		function nt_redirect_modify_menu() {

			add_options_page( __( 'NT Redirect Options', 'nt-redirect' ), __( 'NT Redirect', 'nt-redirect' ), 'manage_options', $this->_name, array( $this, 'nt_redirect_options' ) );

		}

		function nt_redirect_options() {

			global $_REQUEST;

			echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div><h2>' . __( 'NT Redirect Options', 'nt-redirect' ) . '</h2>';

				if ( $_REQUEST['submit'] )
					$this->nt_redirect_update();

				$this->nt_redirect_option_page();

			echo '</div>';

		}

		function nt_redirect_update() {

			global $_REQUEST;

			$nofollow = $_REQUEST[$this->_name . '_nofollow'];
			$white_list = esc_html( stripslashes( $_REQUEST[$this->_name . '_white_list'] ) );
			$ex_ex = esc_attr( stripslashes( $_REQUEST[$this->_name . '_ex_ext'] ) );
			$target = $_REQUEST[$this->_name . '_target'];
			$auto = $_REQUEST[$this->_name . '_auto'];
			$time = $_REQUEST[$this->_name . '_time'];
			$css = esc_attr( stripslashes( $_REQUEST[$this->_name . '_css'] ) );
			$ads1 = stripslashes( $_REQUEST[$this->_name . '_ads1'] ) ;
			$ads2 = stripslashes( $_REQUEST[$this->_name . '_ads2']  );
			$slug = $_REQUEST[$this->_name . '_page_name'];

			update_option( $this->_name . '_nofollow', $nofollow );
			update_option( $this->_name . '_white_list', $white_list );
			update_option( $this->_name . '_ex_ext', $ex_ex );
			update_option( $this->_name . '_target', $target );
			update_option( $this->_name . '_auto', $auto );
			update_option( $this->_name . '_time', $time );
			update_option( $this->_name . '_css', $css );
			update_option( $this->_name . '_ads1', $ads1 );
			update_option( $this->_name . '_ads2', $ads2 );
			update_option( $this->_name . '_page_name', $slug );
			
			$up_slug = array(
				'ID'           => get_option( $this->_name . '_page_id' ),
				'post_name' => $slug
			);
			wp_update_post( $up_slug );
			
		}

		function nt_redirect_option_page() {

			?>
			<form method="post" action="<?php echo $location;?>">

				<?php wp_nonce_field('update-options'); ?>

				<table class="form-table">
					<tbody>

						<tr valign="top">
							<th scope="row"><?php _e( 'Add Rel Nofollow', 'nt-redirect' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php _e( 'Add Rel Nofollow', 'nt-redirect' ); ?></span></legend>
									<label for="<?php echo $this->_name . '_nofollow'; ?>">
										<input name="<?php echo $this->_name . '_nofollow'; ?>" type="checkbox" id="<?php echo $this->_name . '_nofollow'; ?>" value="1" <?php checked( get_option( $this->_name . '_nofollow' ), 1 ); ?>>
										<?php _e( 'Add rel="nofllow" attribute to link if domain not in white list', 'nt-redirect' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php _e( 'Add Target', 'nt-redirect' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php _e( 'Add Target', 'nt-redirect' ); ?></span></legend>
									<label for="<?php echo $this->_name . '_target'; ?>">
										<input name="<?php echo $this->_name . '_target'; ?>" type="checkbox" id="<?php echo $this->_name . '_target'; ?>" value="1" <?php checked( get_option( $this->_name . '_target' ), 1 ); ?>>
										<?php _e( 'Add target="_blank" attribute to link if domain not in white list', 'nt-redirect' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php _e( 'Domain White List', 'nt-redirect' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php _e( 'Domain White List', 'nt-redirect' ); ?></span></legend>
									<p>
										<label for="<?php echo $this->_name . '_white_list'; ?>"><?php _e( 'Domain white list (separated by a new line)', 'nt-redirect' ); ?></label>
									</p>
									<p>
										<textarea name="<?php echo $this->_name . '_white_list'; ?>" rows="10" cols="50" id="<?php echo $this->_name . '_white_list'; ?>" class="large-text code"><?php echo get_option( $this->_name . '_white_list' ); ?></textarea>
									</p>
									<p class="description"><?php _e( '<strong>Ex: </strong>domain1.com<br />domain2.net<br />domain3.org', 'nt-redirect' ); ?></p>
								</fieldset>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><label for="<?php echo $this->_name . '_ex_ext'; ?>"><?php _e( 'No redirect extension', 'nt-redirect' ); ?></label></th>
							<td>
								<input name="<?php echo $this->_name . '_ex_ext'; ?>" type="text" id="<?php echo $this->_name . '_ex_ext'; ?>" value="<?php echo get_option( $this->_name . '_ex_ext' ); ?>" class="regular-text code">
								<p class="description"><?php _e( 'No redirect if Url ends with this extension (separated by | )<br /><br /><b>Note:</b> Still using "Add Rel Nofollow", "Add Target" option<br /><br /><strong>Ex: </strong>png|bmp|gif', 'nt-redirect' ); ?></p>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php _e( 'Auto Redirect', 'nt-redirect' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php _e( 'Auto Redirect', 'nt-redirect' ); ?></span></legend>
									<label for="<?php echo $this->_name . '_auto'; ?>">
										<input name="<?php echo $this->_name . '_auto'; ?>" type="checkbox" id="<?php echo $this->_name . '_auto'; ?>" value="1" <?php checked( get_option( $this->_name . '_auto' ), 1 ); ?>>
										<?php _e( 'Auto redirect to link after ', 'nt-redirect' ); ?>
										<input name="<?php echo $this->_name . '_time'; ?>" type="text" id="<?php echo $this->_name . '_time'; ?>" value="<?php echo get_option( $this->_name . '_time' ); ?>" class="regular-text code" style="width:3em">
										<?php _e( ' second(s)', 'nt-redirect' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php _e( 'Custom css', 'nt-redirect' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php _e( 'Custom css', 'nt-redirect' ); ?></span></legend>
									<p>
										<textarea name="<?php echo $this->_name . '_css'; ?>" rows="10" cols="50" id="<?php echo $this->_name . '_css'; ?>" class="large-text code"><?php echo get_option( $this->_name . '_css' ); ?></textarea>
									</p>
								</fieldset>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><?php _e( 'Custom slug', 'nt-redirect' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php _e( 'Custom slug', 'nt-redirect' ); ?></span></legend>
									<label for="<?php echo $this->_name . '_page_name'; ?>">
										<input name="<?php echo $this->_name . '_page_name'; ?>" type="text" id="<?php echo $this->_name . '_page_name'; ?>" value="<?php echo get_option( $this->_name . '_page_name' ); ?>" class="regular-text code">
										<?php _e( 'Custom slug', 'nt-redirect' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><?php _e( 'Custom Ads #1', 'nt-redirect' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php _e( 'Custom Ads #1', 'nt-redirect' ); ?></span></legend>
									<p>
										<textarea name="<?php echo $this->_name . '_ads1'; ?>" rows="10" cols="50" id="<?php echo $this->_name . '_ads1'; ?>" class="large-text code"><?php echo get_option( $this->_name . '_ads1' ); ?></textarea>
									</p>
								</fieldset>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><?php _e( 'Custom Ads #2', 'nt-redirect' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php _e( 'Custom Ads #2', 'nt-redirect' ); ?></span></legend>
									<p>
										<textarea name="<?php echo $this->_name . '_ads2'; ?>" rows="10" cols="50" id="<?php echo $this->_name . '_ads2'; ?>" class="large-text code"><?php echo get_option( $this->_name . '_ads2' ); ?></textarea>
									</p>
								</fieldset>
							</td>
						</tr>						
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Changes', 'nt-redirect' ) ?>">
				</p>

			</form>
			<?php

		}

		public function nt_redirect_query_parser( $q ) {

			if ( isset( $q->query_vars['page_id'] ) AND ( intval( $q->query_vars['page_id'] ) == $this->page_id ) ) {
				$q->set( $this->_name . '_page_is_called', true );
			} elseif ( isset( $q->query_vars['pagename'] ) AND ( ( $q->query_vars['pagename'] == $this->page_name ) OR ( $_pos_found = strpos( $q->query_vars['pagename'], $this->page_name . '/' ) === 0 ) ) )  {
				$q->set( $this->_name . '_page_is_called', true );
			} else {
				$q->set( $this->_name . '_page_is_called', false );
			}

		}

		function nt_redirect_page_filter( $posts ) {

			global $wp_query;

			$name    = $posts[0]->post_name;

			if ( $name == get_option( $this->_name . '_page_name' ) ) {

				$url = $_SERVER['QUERY_STRING'];
				preg_match( '/url=([^&]+)/', $url, $url );
				$url = htmlspecialchars_decode( trim( urldecode( $url[1] ) ) );

				if ( $url ) {

					add_action('wp_head', array($this,'nt_redirect_head'));

					$output = '<div class="nt-redirect">';
					$output .= '<div class="nt-redirect-ads1">' . get_option( $this->_name . '_ads1' ) . '</div>';
					$output .= '<p class="message">';
					$output .= __( 'You are preparing to leave ', 'nt-redirect' );
					$output .= '<strong>' . get_bloginfo( 'name' ) . '</strong>';
					$output .= __( ' and move to the path: ', 'nt-redirect' );
					$output .= '<strong>' . $url . '</strong>';
					$output .= __( ' within ', 'nt-redirect' );
					$output .= __( ' <strong><span id="delay">', 'nt-redirect' );
					$output .= get_option( $this->_name . '_time' );
					$output .= __( '</span></strong> ', 'nt-redirect' );
					$output .= __( ' second(s) ... ', 'nt-redirect' );
					$output .= __( 'Do you want to go to this link?', 'nt-redirect' );
					$output .= '</p>';
					$output .= '<span class="nt-redict-btn"><a class="cancel" href="' . $_SERVER['HTTP_REFERER'] . '">';
					$output .= __( 'Back', 'nt-redirect' );
					$output .= '</a>';
					$output .= '<a class="forward" rel="nofollow" href="' . $url . '">';
					$output .= __( 'Yes (<span id="delay_footer">', 'nt_redirect' );
					$output .= get_option( $this->_name . '_time' );
					$output .= __( '</span>)', 'nt-redirect' );
					$output .= '</a></span>';
					$output .= '<div class="nt-redirect-ads2">' . get_option( $this->_name . '_ads2' ) . '</div>';
					$output .= '</div>';
					
					if ( get_option( $this->_name . '_auto' ) == 1 ) {
						$output .= '<script type="text/javascript">';
						$output .= '<!--
							x = ' . get_option( $this->_name . '_time' ) . ' + 1;
							function nt_redirect_countdown()
							{
							  x--;
								if(x > -1)
								{
									document.getElementById("delay_footer").innerHTML = x;
									document.getElementById("delay").innerHTML = x;
									setTimeout(\'nt_redirect_countdown()\', 1000);
								}
							}
							nt_redirect_countdown();
							//-->';
						$output .= '</script>';
					}

					$posts[0]->post_title 	= __( 'Redirecting', 'nt-redirect' );
					$posts[0]->post_content = $output;

				}

			} else {

				add_filter( 'the_content', array( $this, 'ntr_redirect' ) );
				add_filter( 'comment_text', array( $this, 'ntr_redirect' ) );

			}

			return $posts;

		}

		function nt_redirect_head() {

			$url = $_SERVER['QUERY_STRING'];
			preg_match( '/url=([^&]+)/', $url, $url );
			$url = htmlspecialchars_decode( trim( urldecode( $url[1] ) ) );
			$nohttp = eregi( '^(sop|ftp|mms|rtmp)\://', $url );
			$time = get_option( $this->_name . '_time' );
			if ( $nohttp || $time == 0 ) { wp_redirect( $url ); exit(); }			
			if ( get_option( $this->_name . '_auto' ) == 1 ) { $meta = '<meta http-equiv="refresh" content="' . $time . '; ' . $url . '"/>'; }			
			$css = '<style type="text/css" media="all">' . get_option( $this->_name . '_css' ) . '</style>';
			echo $meta  . $css;

		}

		private function nt_redirect_delete_page( $hard = true ) {

			global $wpdb;

			$id = get_option( $this->_name . '_page_id' );

			if ( $id && $hard == true )
				wp_delete_post( $id, true );
			elseif ( $id && $hard == false )
				wp_delete_post( $id );

		}

		private function nt_redirect_delete_options() {

			delete_option( $this->_name . '_page_title' );
			delete_option( $this->_name . '_page_name' );
			delete_option( $this->_name . '_page_id' );

		}

		function ntr_redirect( $content ) {

			$regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>";
			if ( preg_match_all( "/$regexp/siU", $content, $matches, PREG_SET_ORDER ) ) {

				if( ! empty( $matches ) ) {

					$white_list = get_option( $this->_name . '_white_list' );
					$white_list = preg_split( '/(\r\n|\n)/', $white_list );;
					$ex_ext = get_option( $this->_name . '_ex_ext' );

					$check = get_permalink( get_option( $this->_name . '_page_id' ) );

					for ( $i = 0; $i < count( $matches ); $i++ ) {

						$attrib_old = $matches[$i][0];
						$target = '';
						$nofollow = '';

						if ( get_option( $this->_name . '_target' ) ) {

							$pattern = '/target\s*=\s*"\s*_blank\s*"/';
							preg_match( $pattern, $attrib_old, $match, PREG_OFFSET_CAPTURE );

							if ( count( $match ) < 1 )
								$target = ' target="_blank" ';

						}

						if ( get_option( $this->_name . '_nofollow' ) ) {

							$pattern = '/rel\s*=\s*"\s*[n|d]ofollow\s*"/';
							preg_match( $pattern, $attrib_old, $match, PREG_OFFSET_CAPTURE);
							if ( count( $match ) < 1 )
								$nofollow = ' rel="nofollow" ';

						}

						$pattern ='/<a[^>]+href="([^"]+)"[^>]*>/miU';
						preg_match( $pattern, $attrib_old, $links );
						$url = @parse_url( $links[1] );

						if ( preg_match('/^[a-z]+\:\/\//i', $links[1] ) && $_SERVER['HTTP_HOST'] != $url['host'] && ! in_array( $url['host'], $white_list ) ) {

							$attrib = rtrim( $attrib_old, '>' );
							$attrib .= $nofollow . $target . '>';

							if( ! $ex_ext || ! preg_match( "/\.({$ex_ext})$/miU", $url['path'] ) ) {

								$q = strpos( $check, '?' );
								if ( $q === false ) {
									$attrib = preg_replace( '/href="([^"]+)"/miU', 'href="' . $check . '?url=' . urlencode( $links[1] ) . '"', $attrib );
								} else {
									$attrib = preg_replace( '/href="([^"]+)"/miU', 'href="' . $check . '&url=' . urlencode( $links[1] ) . '"', $attrib );
								}

							}

							$content = str_replace( $attrib_old, $attrib, $content );

						}

					}
				}

			}

			$content = str_replace(']]>', ']]&gt;', $content);
			return $content;

		}

		function nt_redirect_settings_link( $links, $file ) {

			static $nt_redirect_plugin;

		    if ( ! $nt_redirect_plugin )
		    	$nt_redirect_plugin = plugin_basename(__FILE__);

    		if ( $file == $nt_redirect_plugin ) {
        		$settings_link = '<a href="options-general.php?page=' . $this->_name . '">' . __( 'Settings' ) . '</a>';
        		array_unshift( $links, $settings_link );
    		}

			return $links;

		}

		function nt_redirect_init() {
			load_plugin_textdomain( 'nt-redirect', false, dirname( plugin_basename(__FILE__) ) . '/lang' );
		}

	}

}

$nt_redirect = new NT_Redirect_Plugin();
?>
