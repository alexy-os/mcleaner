<?php

/**
 * Plugin Name: MCleaner
 * Plugin URI:  https://wordpress.org/plugins/mcleaner/
 * Description: This plugin contains a set of rules for disabling various unnecessary WordPress features to clean up the HTML view as much as possible and speed up page loading. This is an experimental version and some rules may affect the functionality of your site, so uncomment the rules that are not needed for your project.
 * Version:     1.0
 * Author:      Alexy_OS
 * Author URI:  https://github.com/alexy-os/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: mcleaner
 * Requires at least: 5.0
 * Requires PHP: 5.2.4
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
if (!defined('ABSPATH')) {
    die('Invalid request.');
}

if (!class_exists('MCleaner')) :
    class MCleaner
    {

        private function __construct()
        {
        }

        public static function init_actions()
        {

            /**
             * This snippet contains a set of rules for disabling various unnecessary WordPress features to clean up the HTML view as much as possible and speed up page loading.
             *
             * !This is an experimental version and some rules may affect the functionality of your site, so uncomment the rules that are not needed for your project.
             * 
             * @link https://gist.github.com/alexy-os/2e58481087d689d410a01710b2338dd2
             *
             * @package mcleaner
             */

            /** ==================================
             * Disable the Gutenberg block editor.
             * ===================================
             */
            add_filter('use_block_editor_for_post_type', '__return_false', 10);

            /**
             * Remove the Gutenberg block library CSS from loading on the frontend.
             */
            function remove_wp_block_library_css()
            {
                wp_dequeue_style('wp-block-library');
                wp_dequeue_style('wp-block-library-theme');
                wp_dequeue_style('wc-blocks-style'); // Remove WooCommerce block CSS
                wp_deregister_style('wp-block-library');
            }
            add_action('wp_enqueue_scripts', 'remove_wp_block_library_css', 100);

            /** ==================================
             * W3C Validate
             * ===================================
             */
            /**
             * Remove trailing slash from meta
             *
             * This function uses output buffering to replace trailing slash from the output
             * of wp_head action. It removes the closing slash from meta tags to make the
             * HTML code valid.
             *
             * @return void
             */
            function w3c_fix_trailing_slash()
            {
                ob_start(function ($output) {
                    return preg_replace('/\s*\/>/', '>', $output);
                });
            }
            add_action('wp', 'w3c_fix_trailing_slash');

            /**
             * Remove unnecessary attributes from HTML code to fix validation errors
             */
            function w3c_fix_types_error()
            {
                // Start output buffering and apply callback function to strip attributes
                ob_start(function ($fix_w3c_type) {
                    $fix_w3c_type = str_replace(array('type="text/javascript"', 'type="application/javascript"', "type='text/javascript'"), '', $fix_w3c_type);
                    $fix_w3c_type = str_replace(array('type="text/css"', "type='text/css'"), '', $fix_w3c_type);
                    $fix_w3c_type = str_replace(array('frameborder="0"', "frameborder='0'"), '', $fix_w3c_type);
                    $fix_w3c_type = str_replace(array('scrolling="no"', "scrolling='no'"), '', $fix_w3c_type);
                    return $fix_w3c_type;
                });
            }
            add_action('template_redirect', 'w3c_fix_types_error');

            /** ==================================
             * Removing unnecessary code
             * ===================================
             */
            // Remove the inline styles created by add_theme_support('custom-background')
            add_action('wp_head', 'remove_custom_background_style');
            function remove_custom_background_style()
            {
                ob_start(function ($output) {
                    $pattern = '/<style\s+id="custom-background-css"[^>]*>.*?<\/style>/is';
                    return preg_replace($pattern, '', $output);
                });
            }

            /**
             * Remove links
             */
            remove_action('wp_head', 'rsd_link'); // Removes the RSD link for a deleted publication
            remove_action('wp_head', 'wlwmanifest_link'); // Removes the Windows link for the Live Writer
            remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0); // Removes the short link
            remove_action('template_redirect', 'wp_shortlink_header', 11, 0); // Removes the short link header
            remove_action('wp_head', 'wp_generator'); // Removes information about the version of WordPress
            remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0); // Removes links to the previous and next articles
            remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0); // Removes links to the previous and next articles
            remove_action('wp_head', 'index_rel_link'); // Removes the index link
            remove_action('wp_head', 'start_post_rel_link', 10, 0); // Removes the start link
            remove_action('wp_head', 'parent_post_rel_link', 10, 0); // Removes the parent link

            /** ===================================================
             *  SET disables
             * ====================================================
             */
            /**
             * Remove the "ver" parameter from scripts and styles.
             *
             * @param string $src The script or style URL.
             * @return string The URL without the "ver" parameter.
             */
            function remove_wp_ver_css_js($src)
            {
                if (strpos($src, 'ver=')) {
                    $src = remove_query_arg('ver', $src);
                }
                return $src;
            }
            add_filter('style_loader_src', 'remove_wp_ver_css_js', 9999);
            add_filter('script_loader_src', 'remove_wp_ver_css_js', 9999);

            // Deregister the 'classic-theme-styles' script with priority 20
            add_action('wp_enqueue_scripts', 'mcleaner_child_deregister_styles', 20);
            function mcleaner_child_deregister_styles()
            {
                wp_dequeue_style('classic-theme-styles');
            }

            // Remove the 'wp_enqueue_global_styles' and 'wp_global_styles_render_svg_filters' actions on 'init' action
            function mcleaner_remove_global_css()
            {
                remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
                remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
            }
            add_action('init', 'mcleaner_remove_global_css');

            /**
             * Remove the DNS prefetch.
             */
            remove_action('wp_head', 'wp_resource_hints', 2);

            /**
             * Disables pings.
             */
            function mcleaner_remove_x_pingback($headers)
            {
                unset($headers['X-Pingback']);
                return $headers;
            }

            /**
             * Disables comment styles.
             */
            function mcleaner_remove_recent_comments_style()
            {
                global $wp_widget_factory;
                remove_action('wp_head', array($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style'));
            }

            /** =============================================
             * Disables the RSS feed.
             * ==============================================
             */
            /**
             * Removes RSS feed links of the entry and comments,
             * removes category and archive RSS feed links,
             * disables the RSS feed,
             * removes the DNS prefetch,
             * disables pings and comment styles.
             */

            // Removes RSS feed links of the entry and comments
            remove_action('wp_head', 'feed_links', 2);

            // Removes category and archive RSS feed links
            remove_action('wp_head', 'feed_links_extra', 3);

            /**
             * Disables various links in the WordPress head section
             */
            function fb_disable_feed()
            {
                wp_redirect(get_option('siteurl'));
            }
            // Disable RSS feed
            add_action('do_feed', 'fb_disable_feed', 1);
            add_action('do_feed_rdf', 'fb_disable_feed', 1);
            add_action('do_feed_rss', 'fb_disable_feed', 1);
            add_action('do_feed_rss2', 'fb_disable_feed', 1);
            add_action('do_feed_atom', 'fb_disable_feed', 1);

            /** ============================================
             * Disable the REST API.
             * =============================================
             */
            add_filter('rest_enabled', '__return_false');

            /**
             * Disabling REST API events
             * 
             * !If you want to disable Rest Api completely, uncomment this block
             */
            /*remove_action( 'init','rest_api_init' );
            remove_action( 'rest_api_init', 'rest_api_default_filters', 10, 1 );
            remove_action( 'parse_request', 'rest_api_loaded' );*/

            /**
             * Remove the REST API-related links from the page header.
             */
            remove_action('wp_head', 'rest_output_link_wp_head', 10, 0);
            remove_action('template_redirect', 'rest_output_link_header', 11, 0);

            /**
             * Remove the REST API-related cookies.
             */
            remove_action('auth_cookie_malformed', 'rest_cookie_collect_status');
            remove_action('auth_cookie_expired', 'rest_cookie_collect_status');
            remove_action('auth_cookie_bad_username', 'rest_cookie_collect_status');
            remove_action('auth_cookie_bad_hash', 'rest_cookie_collect_status');
            remove_action('auth_cookie_valid', 'rest_cookie_collect_status');

            /**
             * Remove the REST API-related filters.
             */
            remove_filter('rest_authentication_errors', 'rest_cookie_check_errors', 100);

            /**
             * Remove the REST API-related oEmbed functionality.
             */
            remove_action('rest_api_init', 'wp_oembed_register_route');
            remove_filter('rest_pre_serve_request', '_oembed_rest_pre_serve_request', 10, 4);
            remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
            remove_action('wp_head', 'wp_oembed_add_host_js');

            /** ================================================
             * Disable emoji support.
             * =================================================
             */
            function disable_emojis_tinymce($plugins)
            {
                if (is_array($plugins)) {
                    return array_diff($plugins, array('wpemoji'));
                } else {
                    return array();
                }
            }

            function disable_wp_emojis()
            {
                // Remove emojis from the head section of the site
                remove_action('wp_head', 'print_emoji_detection_script', 7);
                remove_action('wp_print_styles', 'print_emoji_styles');

                // Remove emojis from the content of posts, pages, and comments
                remove_filter('the_content_feed', 'wp_staticize_emoji');
                remove_filter('comment_text_rss', 'wp_staticize_emoji');
                remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

                // Remove emojis from the TinyMCE editor
                add_filter('tiny_mce_plugins', 'disable_emojis_tinymce');
            }

            add_action('init', 'disable_wp_emojis');

            /** ==================================================
             * MINIFY HTML OUTPUT
             * ===================================================
             */
            function mcleaner_init_minify_html()
            {
                ob_start('mcleaner_minify_html_output');
            }
            add_action('init', 'mcleaner_init_minify_html', 1);

            function mcleaner_minify_html_output($buffer)
            {
                if (substr(ltrim($buffer), 0, 5) == '<?xml')
                    return ($buffer);
                $minify_javascript = get_option('minify_javascript');
                $minify_html_comments = get_option('minify_html_comments');
                $minify_html_utf8 = get_option('minify_html_utf8');
                if ($minify_html_utf8 == 'yes' && mb_detect_encoding($buffer, 'UTF-8', true))
                    $mod = '/u';
                else
                    $mod = '/s';
                $buffer = str_replace(array(chr(13) . chr(10), chr(9)), array(chr(10), ''), $buffer);
                $buffer = str_ireplace(array('<script', '/script>', '<pre', '/pre>', '<textarea', '/textarea>', '<style', '/style>'), array('M1N1FY-ST4RT<script', '/script>M1N1FY-3ND', 'M1N1FY-ST4RT<pre', '/pre>M1N1FY-3ND', 'M1N1FY-ST4RT<textarea', '/textarea>M1N1FY-3ND', 'M1N1FY-ST4RT<style', '/style>M1N1FY-3ND'), $buffer);
                $split = explode('M1N1FY-3ND', $buffer);
                $buffer = '';
                for ($i = 0; $i < count($split); $i++) {
                    $ii = strpos($split[$i], 'M1N1FY-ST4RT');
                    if ($ii !== false) {
                        $process = substr($split[$i], 0, $ii);
                        $asis = substr($split[$i], $ii + 12);
                        if (substr($asis, 0, 7) == '<script') {
                            $split2 = explode(chr(10), $asis);
                            $asis = '';
                            for ($iii = 0; $iii < count($split2); $iii++) {
                                if ($split2[$iii])
                                    $asis .= trim($split2[$iii]) . chr(10);
                                if ($minify_javascript != 'no')
                                    if (strpos($split2[$iii], '//') !== false && substr(trim($split2[$iii]), -1) == ';')
                                        $asis .= chr(10);
                            }
                            if ($asis)
                                $asis = substr($asis, 0, -1);
                            if ($minify_html_comments != 'no')
                                $asis = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $asis);
                            if ($minify_javascript != 'no')
                                $asis = str_replace(array(';' . chr(10), '>' . chr(10), '{' . chr(10), '}' . chr(10), ',' . chr(10)), array(';', '>', '{', '}', ','), $asis);
                        } else if (substr($asis, 0, 6) == '<style') {
                            $asis = preg_replace(array('/\>[^\S ]+' . $mod, '/[^\S ]+\<' . $mod, '/(\s)+' . $mod), array('>', '<', '\\1'), $asis);
                            if ($minify_html_comments != 'no')
                                $asis = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $asis);
                            $asis = str_replace(array(chr(10), ' {', '{ ', ' }', '} ', '( ', ' )', ' :', ': ', ' ;', '; ', ' ,', ', ', ';}'), array('', '{', '{', '}', '}', '(', ')', ':', ':', ';', ';', ',', ',', '}'), $asis);
                        }
                    } else {
                        $process = $split[$i];
                        $asis = '';
                    }
                    $process = preg_replace(array('/\>[^\S ]+' . $mod, '/[^\S ]+\<' . $mod, '/(\s)+' . $mod), array('>', '<', '\\1'), $process);
                    if ($minify_html_comments != 'no')
                        $process = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->' . $mod, '', $process);
                    $buffer .= $process . $asis;
                }
                $buffer = str_replace(array(chr(10) . '<script', chr(10) . '<style', '*/' . chr(10), 'M1N1FY-ST4RT'), array('<script', '<style', '*/', ''), $buffer);
                $minify_html_xhtml = get_option('minify_html_xhtml');
                $minify_html_relative = get_option('minify_html_relative');
                $minify_html_scheme = get_option('minify_html_scheme');
                if ($minify_html_xhtml == 'yes' && strtolower(substr(ltrim($buffer), 0, 15)) == '<!doctype html>')
                    $buffer = str_replace(' />', '>', $buffer);
                if ($minify_html_relative == 'yes')
                    $buffer = str_replace(array('https://' . $_SERVER['HTTP_HOST'] . '/', 'http://' . $_SERVER['HTTP_HOST'] . '/', '//' . $_SERVER['HTTP_HOST'] . '/'), array('/', '/', '/'), $buffer);
                if ($minify_html_scheme == 'yes')
                    $buffer = str_replace(array('http://', 'https://'), '//', $buffer);
                return ($buffer);
            }
        }
    }

    add_action('plugins_loaded', array('MCleaner', 'init_actions'));

endif;
