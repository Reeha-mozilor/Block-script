<?php
/*
Plugin Name:  Block script sample
Plugin URI:   http://yourdomain.com
Description:  plugin to block script
Version:      1.0.0
Author:       Reeha
Author URI:   http://yourdomain.com
*/


// If this file is called directly, abort.


defined('ABSPATH') or die('You can\t access file');



if ( ! class_exists( 'Script_Blocker' ) ) {

	class Script_Blocker {
        public function add_blockscript_menu() {
            add_menu_page(
                __( 'script blocker' ),
                __('Block script'),
                'manage_options',
                'block_script_settings',
                array($this , 'block_script' ),
                'dashicons-block-default',
                '100'           
            );
        }
        public function block_script() {
            ?>
                <h2>Hey</h2>
                <h3>This is a plugin for blocking scripts in website .You can add keywords of the scripts to be blocked.
                </h3>
            <form method="post">
                <input type="text" class="large-text" name="keyword" placeholder="Enter the keywords"><br><br>
                <div style="display: flex;">
                <input type="submit" class="btn btn-primary" name="submit" value="Save">
            </form> 
            <form method="post">
                <input type="submit" name="enable" value="Enable">
        </form>
        <form method="post">
                <input type="submit" name="disable" value="Disable">
        </form>
        </div>                 
            <?php
        }

        public function submit_form() {
            if( isset($_POST['submit']) ) {
                // Declaration of variable
                $keyword = $_POST['keyword'];

                global $wpdb;
            $table_name = 'form_tb';
    
            
            $sql = $wpdb->insert( $table_name,
                                array(
                                    'keyword' => $keyword,
                                ),
                                array(
                                    '%s'
                                )
            );
            ?>
            <script>
                alert("Keyword added successfully");
            </script>

               <?php

               }
            }
        public function enable(){
            if( isset($_POST['enable']) ) {

            

            global $wpdb;
            $table_name = 'form_tb';
            $update = $wpdb->update( $table_name,
                                array(
                                    'status' => 1
                                ),
                                array(
                                    'status' => 0
                                )
            );
            ?>
            <script>
                alert("Blockscript enabled successfully");
            </script>
            <?php

               }

        }

        public function disable(){
            if( isset($_POST['disable']) ) {

            

            global $wpdb;
            $table_name = 'form_tb';
            $update = $wpdb->update( $table_name,
                                array(
                                    'status' => 0
                                ),
                                array(
                                    'status' => 1
                                )
            );

            ?>
            <script>
                alert("Blockscript disabled successfully");
            </script>
            <?php

    
               }

        }
        

		function __construct() {
            global $wpdb;
            $status=0;

            
            $table_name = 'form_tb';
            $result = $wpdb->get_results( "SELECT status FROM $table_name" );
            foreach( $result as  $print ) { 
                if ($print->status!=0){
                    $status=1;

                }
            }
            if ($status==1){
                $this->init_script_blocker();
                add_action( 'shutdown', array( $this, 'end_buffer' ), 999 );
            }
            add_action('admin_menu' , array( $this, 'add_blockscript_menu'));
            add_action( 'admin_init', array( $this, 'submit_form') );
            add_action( 'admin_init', array( $this, 'enable') );
            add_action( 'admin_init', array( $this, 'disable') );
            
		}

		public function init_script_blocker() {
            add_action( 'template_redirect', array( $this, 'start_buffer' ) );
            add_action( 'shutdown', array( $this, 'end_buffer' ), 999 );
        
    }

        public function start_buffer() {
            ob_start( array( $this, 'init' ) );
		}

        public function end_buffer() {
			if ( ob_get_length() ) {
				ob_end_flush();
			}
		}

        public function init( $buffer ) {
			$buffer = $this->replace_scripts( $buffer );
			return $buffer;

		}
        public function replace_scripts( $buffer ) {

        global $wpdb;

        $table_name = 'form_tb';

        $result = $wpdb->get_results( "SELECT * FROM $table_name" );
        $third_party_script_tags = array();
        $i=0;
        foreach( $result as  $print ) { 
            $third_party_script_tags[$i] = $print->keyword;
            $i++;
        }




			$script_pattern = '/(<script.*?>)(\X*?)<\/script>/i';
			$index          = 0;
			if ( preg_match_all(
				$script_pattern,
				$buffer,
				$matches,
				PREG_PATTERN_ORDER
			) ) {

				foreach ( $matches[1] as $key => $script_open ) {
					if (
						strpos( $script_open, 'application/ld+json' )
						!== false
					) {
						continue;
					}
					$total_match = $matches[0][ $key ];
					$content     = $matches[2][ $key ];

					// if there is inline script here, it has some content
					if ( ! empty( $content ) ) {
						$found = $this->strpos_arr(
							$content,
							$third_party_script_tags
						);

						
					}
					$script_src_pattern= '/<script [^>]*?src=[\'"](http:\/\/|https:\/\/|\/\/)([\w.,;@?^=%&:()\/~+#!\-*]*?)[\'"].*?>/i';
					if ( preg_match_all(
						$script_src_pattern,
						$total_match,
						$src_matches,
						PREG_PATTERN_ORDER
					)
					) {
						$script_src_matches = ( isset( $src_matches[2] ) && is_array( $src_matches[2] ) ) ? $src_matches[2] : array();
						if ( ! empty( $script_src_matches ) ) {
							foreach ( $src_matches[2] as $src_key => $script_src ) {
								$script_src = $src_matches[1][ $src_key ] . $src_matches[2][ $src_key ];
								$found      = $this->strpos_arr(
									$script_src,
									$third_party_script_tags
								);

								if ( $found !== false ) {
									$new      = $total_match;
									$new      = $this->replace_script_type_attribute( $new );
									$buffer   = str_replace( $total_match, $new, $buffer );
								}
							}
						}
					}
				}
			}
			return $buffer;
		}
        public function replace_script_type_attribute( $script ) {
            

			$script_type = 'text/plain';

			if ( preg_match( '/<script[^\>]*?\>/m', $script ) ) {
				$changed = true;
				if ( preg_match( '/<script.*(type=(?:"|\')(.*?)(?:"|\')).*?>/', $script ) && preg_match( '/<script.*(type=(?:"|\')text\/javascript(.*?)(?:"|\')).*?>/', $script ) ) {
					preg_match( '/<script.*(type=(?:"|\')text\/javascript(.*?)(?:"|\')).*?>/', $script, $output_array );
					$re = preg_quote( $output_array[1], '/' );
					if ( ! empty( $output_array ) ) {

						$script = preg_replace( '/' . $re . '/', 'type="' . $script_type . '"' . ' ' . $replace, $script, 1 );

					}
				} else {

					$script = str_replace( '<script', '<script type="' . $script_type . '"' . ' ' . $replace, $script );

				}
			}
			return $script;
		}
        private function strpos_arr( $haystack, $needle ) {
			if ( empty( $haystack ) ) {
				return false;
			}

			if ( ! is_array( $needle ) ) {
				$needle = array( $needle );
			}
			foreach ( $needle as $key => $value ) {

				if ( is_array( $value ) ) {

					foreach ( $value as $data ) {

						if ( strlen( $data ) === 0 ) {
							continue;
						}
						if ( ( $pos = strpos( $haystack, $data ) ) !== false ) {
							return ( is_numeric( $key ) ) ? $data : $key;
						}
					}
				} else {

					if ( strlen( $value ) === 0 ) {
						continue;
					}
					if ( ( $pos = strpos( $haystack, $value ) ) !== false ) {
						return ( is_numeric( $key ) ) ? $value : $key;
					}
				}
			}

			return false;


		
    
    
    }
    
}}
new Script_Blocker();
