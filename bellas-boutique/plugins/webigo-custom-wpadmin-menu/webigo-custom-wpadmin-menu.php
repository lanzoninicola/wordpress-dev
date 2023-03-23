<?php

/**
 * @link              https://webigo.com.br
 * @since             1.0.0
 * @package           Webigo
 *
 * @wordpress-plugin
 * Plugin Name:       Webigo - Custom WPAdmin Menu
 * Plugin URI:        https://webigo.com.br
 * Description:       Enable a new menu called Seu Espaço that contains all post type and custom settings page created with the plugin Pods Framework.
 * Version:           1.0.0
 * Author:            Webigo
 * Author URI:        https://webigo.com.br
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       webigo_custom_wpmenu
 * Domain Path:       /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;


if (!defined('WEGO_CUSTOM_WPMENU_DOMAIN')) {
    define('WEGO_CUSTOM_WPMENU_DOMAIN', 'webigo_custom_wpmenu');
}

if (!defined('WEGO_CUSTOM_WPMENU_MENU_NAME')) {
    define('WEGO_CUSTOM_WPMENU_MENU_NAME', 'Seu Espaço');
}


/*******************************************
 * 
 *  Remove menu items for certain roles
 * 
 ********************************************/
// require_once( get_pl() . DIRECTORY_SEPARATOR . 'webigo-custom-wpadmin-menu');

require_once plugin_dir_path( __DIR__ ) . '/webigo-custom-wpadmin-menu/includes/class-hide-admin-menu.php';

$hide_admin_menu = new Webigo_Hide_Admin_Menu();
// var_dump($hide_admin_menu);die;
$hide_admin_menu->run();


/*******************************************
 * 
 *  Check if the Pod plugin is active
 * 
 ********************************************/

add_action('admin_init', 'wego_custom_wpmenu_check_activation');

function wego_custom_wpmenu_check_activation()
{

    $pods_plugin = 'pods/init.php';
    $current_active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

    // is this plugin active?
    if (!in_array($pods_plugin, $current_active_plugins)) {
        // deactivate the plugin
        deactivate_plugins(plugin_basename(__FILE__));
        // unset activation notice
        unset($_GET['activate']);

        // display notice
        add_action(
            'admin_notices',
            function () {
                $message = sprintf(
                    esc_html__('%s requires %s to be installed and activated: %s', WEGO_CUSTOM_WPMENU_DOMAIN),
                    '<strong>Webigo Custom WPAdmin Menu</strong>',
                    '<strong>Pods Framework</strong>',
                    '<strong>https://wordpress.org/plugins/pods/</strong>',
                );
                $html = sprintf('<div class="notice notice-warning">%s</div>', wpautop($message));
                echo wp_kses_post($html);
            }
        );
    }
}


/*******************************************
 * 
 *  Adding the capapility of Pod administrator to "Editor" WP role 
 * 
 ********************************************/
add_filter('pods_admin_capabilities', 'wego_custom_wpmenu_change_pods_admin_capabilities', 10, 2);

// define the pods_admin_capabilities callback 
function wego_custom_wpmenu_change_pods_admin_capabilities($pods_admin_capabilities, $cap)
{

    // change Pods filter only to specific Custom Settings Page
    if (!isset($_GET['page']) || substr($_GET['page'], 0, 13) != 'pods-settings') {
       return $pods_admin_capabilities;
    }

    // adding to Editor role the admin capapility of Pod
    // because a Custom Settings Page is restricted to Admin users
    if (isset($pods_admin_capabilities)) {
        if (is_array($pods_admin_capabilities)) {
            array_push($pods_admin_capabilities, 'edit_posts');
        }
    }

    return $pods_admin_capabilities;
};


/*******************************************
 * 
 *  Adding Seu Espaço Menu
 * 
 ********************************************/
add_action('admin_menu', 'wego_custom_wpmenu_add_main_menu');

function wego_custom_wpmenu_add_main_menu()
{
    add_menu_page(
        __(WEGO_CUSTOM_WPMENU_MENU_NAME, WEGO_CUSTOM_WPMENU_DOMAIN),
        __(WEGO_CUSTOM_WPMENU_MENU_NAME, WEGO_CUSTOM_WPMENU_DOMAIN),
        'edit_posts',
        WEGO_CUSTOM_WPMENU_DOMAIN,
        function () {
?>
        <div id="wego-wpadmin-menu-container" style="display: flex; flex-direction: column; margin-block: 2rem;">
            <h1><?php echo esc_html('Bem vindo no teu espaço pessoal') ?></h1>
            <div class="wego-wpadmin-wrapper">
                <p class="wego-wpadmin-p"><?php echo esc_html('Neste espaço você pode gerenciar seu site, adicionando ou atualizando as informações que lhe serão propostas.') ?></p>
                <p class="wego-wpadmin-p"><?php echo esc_html('Bom trabalho.') ?></p>
            </div>
            <style>
                .wego-wpadmin-p {
                    font-size: 1.2rem;
                    letter-spacing: 1px;
                    line-height: 120%;
                }

                .wego-wpadmin-wrapper {
                    margin-top: 1rem;
                }
            </style>
        </div>

<?php
        },
        'dashicons-admin-generic',
        105
    );

    // https://developer.wordpress.org/reference/functions/add_submenu_page/
    // Inside menu created with add_menu_page()
    // If you are attempting to add a submenu page to a menu page created via add_menu_page() 
    // the first submenu page will be a duplicate of the parent add_menu_page().
    add_submenu_page(
        WEGO_CUSTOM_WPMENU_DOMAIN,
        __('Configurações', 'textdomain'),
        __('Configurações', 'textdomain'),
        'edit_posts',
        WEGO_CUSTOM_WPMENU_DOMAIN
    );
}


/*******************************************
 * 
 *  Adding Pods Customs Settings Page related to the customer in the Seu Espaço menu
 * 
 ********************************************/

add_action('admin_menu', 'wego_custom_wpmenu_add_post_type_to_menu');

function wego_custom_wpmenu_add_post_type_to_menu()
{
    $all_pods = pods_api()->load_pods();

    $valid_pods = ['post_type', 'settings'];

    foreach ($all_pods as $key => $single_pod) {

        $menu_label = $single_pod['label'];
        $name = $single_pod['name'];

         // var_dump($single_pod['type']);
         // var_dump($menu_label);
         // var_dump($name);
         // var_dump('========================');


        if (in_array($single_pod['type'], $valid_pods)) {

            $menu_label = $single_pod['label'];
            $name = $single_pod['name'];
			
			// Set the menu slug based on pod type
            if ($single_pod['type'] === 'post_type') {
                $menu_slug = 'edit.php?post_type=' . $name . '';
            }
            if ($single_pod['type'] === 'settings') {
                $menu_slug = 'admin.php?page=pods-settings-' . $name . '';
            }

			// Get the pod options
            $options = $single_pod['options'];
			
            // Check if the single pod should added to the custom menu "Seu Espaço"
            $should_added_to_menu = '0';
			
			// Edit Pod --> Admin UI --> Menu location
			// If "Menu Location" option is "Add a submenu item to Appearance"
			// The item is added to the "Seu Espaço" custom menu group
			if ($options['menu_location'] == "appearances") {
				$should_added_to_menu = '1';
			}

            if ('1' == $should_added_to_menu) {
                add_submenu_page(
                    WEGO_CUSTOM_WPMENU_DOMAIN,
                    __($menu_label, WEGO_CUSTOM_WPMENU_DOMAIN),
                    __($menu_label, WEGO_CUSTOM_WPMENU_DOMAIN),
                    'edit_posts',
                    $menu_slug
                );
            } // end if

        } // end if

    } // end foreach

    // die;
}
