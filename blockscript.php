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
            $arr=get_option('source_keys');
            $val=implode(",",$arr);
            
            ?>
                <h2>Hey</h2>
                <h3>This is a plugin for blocking scripts in website .You can add keywords of the scripts to be blocked.
                </h3>
            <form method="post">
            <input type="text" class="large-text" name="key" placeholder="eg. script.js, style.css etc" value="<?= $val;?>" required><br><br>
                <div style="display: flex;">

        <input type="checkbox" id="switch" name="status" value="enable" <?php if ($_SESSION['status']== 1) echo "checked='checked'"; ?>><label for="switch"></label>
       <br><br><h4>You can enable/disable using toggle<h4>
        <input type="submit" class="btn" name="submit" value="SUBMIT">

        
        </div>
        <style>
    .btn{
        margin-top:35px;
        width:150px;
    }
    input[type=checkbox]{
	height: 0;
	width: 0;
	visibility: hidden;
    
}
h4{
    margin-top:30px;
    position:absolute;
}

label {
	cursor: pointer;
	text-indent: -9999px;
	width: 50px;
	height: 25px;
	background: grey;
	display: block;
	border-radius: 100px;
	position: absolute;
}

label:after {
	content: '';
	position: absolute;
	top: 1.25px;
	left: 1.25px;
	width: 22.5px;
	height: 22.5px;
	background: #fff;
	border-radius: 90px;
	transition: 0.3s;
}

input:checked + label {
	background: blue;
}

input:checked + label:after {
	left: calc(100% - 1.25px);
	transform: translateX(-100%);
}

label:active:after {
	width: 130px;
}
</style>
   <?php
            }

        public function submit_form() {
            if( isset($_POST['submit']) ) {
                // Declaration of variable
                $test =$_POST['key'];
                $keys = explode(',', $test);
                if ( get_option('source_keys')) {
                    update_option( 'source_keys', $keys );
                } else {
                    add_option( 'source_keys', $keys);
                }



            if (isset($_POST['status'])){
                update_option( 'status',1);
                
            $_SESSION['status']=1;
            }else{
                update_option( 'status',0);
            $_SESSION['status']=0;
            }

            ?>
            <script>
                alert("Your settings updated successfully");
            </script>

               <?php

               }
            }

       

       
        

		function __construct() {
            if (get_option('status')==1){
                $_SESSION['status']=1;
            }else{
                $_SESSION['status']=0;
            }
            if ($_SESSION['status']==1){
                $this->init_script_blocker();
                
            }
            add_action('admin_menu' , array( $this, 'add_blockscript_menu'));
            add_action( 'admin_init', array( $this, 'submit_form') );
            
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

        $third_party_script_tags = array();
        $third_party_script_tags=get_option('source_keys');




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
