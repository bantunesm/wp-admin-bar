<?php

/**
 * Plugin Name:         bantunes-wp-admin-bar
 * Plugin URI:          https://github.com/bantunesm/wp-admin-bar.git
 * Description:         Add application, system and environment information to your admin bar
 * Author:              Bruno ANTUNES, Intervalle
 * Author URI:          https://www.brunoantunes.fr
 * License:             GPL2
 */

function add_plugin_styles()
{
    wp_enqueue_style('plugin-styles', plugins_url('assets/bantunes-admin-bar.css', __FILE__));
}

function display_latest_commit($wp_admin_bar)
{
    $latest_commit = shell_exec('git log --pretty="%H" -n1 HEAD');
    $latest_tag = shell_exec('git describe --tags --abbrev=0 --exact-match ' . $latest_commit);
    $remote_url = shell_exec('git config --get remote.origin.url');
    $remote_url_https = str_replace('git@', 'https://', $remote_url);
    $remote_url_https = str_replace(':', '/', $remote_url_https);
    $remote_url_https = rtrim($remote_url_https, '.git') . '/';
    $env = getenv('WP_ENV');
    $short_env = substr($env, 0, 3);
    $php_version = phpversion();

    $git_status = shell_exec('git status --porcelain');
    $has_changes = !empty(trim($git_status));
    $stop_emoji = $has_changes ? "🆕 " : "";

    // Determine the CSS class based on WP_ENV
    $env_class = '';
    switch($env) {
        case 'staging':
            $env_class = 'env-staging';
            break;
        case 'production':
            $env_class = 'env-production';
            break;
        case 'development':
            $env_class = 'env-development';
            break;
    }

    if (!empty($latest_tag)) {
        $wp_admin_bar->remove_node('wp-logo');
        $wp_admin_bar->add_node(array(
            'id'    => 'latest_tag',
            'title' => $stop_emoji . "Version: {$latest_tag} ($short_env)",
            'href'  => $remote_url_https,
            'meta'  => array(
                'class' => 'latest-tag ' . $env_class
            )
        ));

        $parent = 'latest_tag';
    } else {
        $latest_commit_short = shell_exec('git log --pretty="%h" -n1 HEAD');
        if (!empty($latest_commit_short)) {
            $wp_admin_bar->remove_node('wp-logo');
            $wp_admin_bar->add_node(array(
                'id'    => 'latest_commit',
                'title' => $stop_emoji . 'Latest Commit: ' . $latest_commit_short,
                'href'  => $remote_url_https,
                'meta'  => array(
                    'class' => 'latest-commit ' . $env_class
                )
            ));

            $parent = 'latest_commit';
        }
    }

    if (!empty($php_version)) {
        $wp_admin_bar->add_node(array(
            'id'     => 'php_version',
            'title'  => 'PHP Version: ' . $php_version,
            'parent' => $parent
        ));
    }

    $wordpress_version = get_bloginfo('version');
    if (!empty($wordpress_version)) {
        $wp_admin_bar->add_node(array(
            'id'     => 'wordpress_version',
            'title'  => 'WordPress Version: ' . $wordpress_version,
            'parent' => $parent
        ));
    }

}

function remove_wp_logo($wp_admin_bar)
{
    $wp_admin_bar->remove_node('wp-logo');
}

add_action('admin_bar_menu', 'display_latest_commit', 10);
add_action('admin_bar_menu', 'remove_wp_logo', 999);
add_action('admin_enqueue_scripts', 'add_plugin_styles');
