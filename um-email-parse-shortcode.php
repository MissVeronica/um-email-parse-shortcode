<?php
/**
 * Plugin Name:     Ultimate Member - Email Parse Shortcode
 * Description:     Extension to Ultimate Member for parsing the shortcode "um_show_content" in outgoing notification emails.
 * Version:         1.0.1
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Plugin URI:      https://github.com/MissVeronica/um-email-parse-shortcode
 * Update URI:      https://github.com/MissVeronica/um-email-parse-shortcode
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.8.7
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Email_Parse_Shortcode {

    public $email = '';

    function __construct() {

        add_filter( 'um_email_send_message_content',          array( $this, 'um_email_parse_shortcode' ), 10, 3 );
        add_action( 'um_before_email_notification_sending',   array( $this, 'um_email_parse_shortcode_prepare' ), 10, 3 );
        add_filter( 'um_admin_settings_email_section_fields', array( $this, 'um_admin_settings_email_section_parse_shortcode' ), 10, 2 );
    }

    public function um_email_parse_shortcode( $message, $slug, $args ) {

        if ( UM()->options()->get( $slug . '_parse_shortcode' ) == 1 ) {

            $user = get_user_by( 'email', $this->email );

            if ( ! empty( $user )) {

                $save_userid = false;
                if ( $user->ID != um_user( 'ID' )) {
                    $save_userid = um_user( 'ID' );
                    um_fetch_user( $user->ID );
                }

                remove_shortcode( 'um_show_content', array( 'um\core\Shortcodes', 'um_shortcode_show_content_for_role' ) );
                add_shortcode( 'um_show_content', array( $this, 'um_shortcode_show_content_for_role' ) );

                $message = $this->parse_shortcode( $message );

                if ( ! empty( $save_userid )) {
                    um_fetch_user( $save_userid );
                }
            }
        }

        return $message;
    }

    public function um_email_parse_shortcode_prepare( $email, $template, $args ) {

        $this->email = $email;
    }

    public function um_shortcode_show_content_for_role( $atts = array() , $content = '' ) {

        $a = shortcode_atts( array(
                                    'roles'      => '',
                                    'not'        => '',
                                    'is_profile' => false,
                                   ), $atts );

        $current_user_roles = um_user( 'roles' );

        if ( ! empty( $a['not'] ) && ! empty( $a['roles'] ) ) {
            return $this->parse_shortcode( $content );
        }

        if ( ! empty( $a['not'] ) ) {
            $not_in_roles = explode( ",", $a['not'] );

            if ( is_array( $not_in_roles ) && ( empty( $current_user_roles ) || count( array_intersect( $current_user_roles, $not_in_roles ) ) <= 0 ) ) {
                return $this->parse_shortcode( $content );
            }

        } else {

            $roles = explode( ",", $a['roles'] );

            if ( ! empty( $current_user_roles ) && is_array( $roles ) && count( array_intersect( $current_user_roles, $roles ) ) > 0 ) {
                return $this->parse_shortcode( $content );
            }
        }

        return '';
    }

    public function parse_shortcode( $content ) {

        if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
            return do_shortcode( $content );

        } else {
            return apply_shortcodes( $content );
        }
    }

    public function um_admin_settings_email_section_parse_shortcode( $section_fields, $email_key ) {

        $section_fields[] = array(
                'id'            => $email_key . '_parse_shortcode',
                'type'          => 'checkbox',
                'label'         => __( 'Parse Shortcode - Enable parsing of shortcode "um_show_content"', 'ultimate-member' ),
                'description'   => __( 'Click to enable parsing of the shortcode "um_show_content" in this template for outgoing emails', 'ultimate-member' ),
                'conditional'   => array( $email_key . '_on', '=', 1 ),
            );

        return $section_fields;

    }
}

new UM_Email_Parse_Shortcode();

