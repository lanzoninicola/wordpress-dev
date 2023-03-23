<?php


class Webigo_Hide_Admin_Menu
{

    private $menus_to_hide = ['painel', 'bricks', 'posts', 'páginas', 'ferramentas'];

    private $target_roles = ['shop_manager', 'editor'];

    private $is_admin_area = false;

    private $is_valid_user_logged = false;

    private $user_logged;

    private $user_logged_role;

    private $menu_list;
    // private $show_menu_list = true; // discover mode: setting true shows all menu values

    public function run()
    {
        add_action('admin_menu', array($this, 'init_checks'), 900);
        add_action('admin_menu', array($this, 'build_menu_schema'), 910);
        add_action('admin_menu', array($this, 'add_setup_menu'), 915);
        add_action('admin_menu', array($this, 'hide_menus'), 920);
    }

    public function init_checks()
    {
        $this->should_admin_area();
        $this->should_current_user_logged();
        $this->should_valid_user_logged();
        $this->get_user_logged_roles();
    }

    private function should_admin_area()
    {
        $this->is_admin_area = is_admin();
    }


    private function should_current_user_logged()
    {
        $this->user_logged = wp_get_current_user();
    }

    private function should_valid_user_logged()
    {
        if (isset($this->user_logged) & $this->user_logged->exists()) {
            $this->is_valid_user_logged = true;
        }
    }

    private function get_user_logged_roles()
    {
        if ($this->is_valid_user_logged) {
            $user_roles = (array) $this->user_logged->roles;
            $this->user_logged_role = $user_roles[0];
        }
    }

    public function build_menu_schema()
    {
        if (!$this->is_admin_area || !$this->is_valid_user_logged) {
            return;
        }
        // start he hiding menu items process
        $wp_admin_menu = $GLOBALS['menu'];

        $this->menu_list = array();

        // build a menu schema
        foreach ($wp_admin_menu as $key => $wp_admin_menu_item) {
            /**
             *      $wp_admin_menu_item[0] = menu label
             *      $wp_admin_menu_item[2] = menu slug
             */

            $menu_item_label = strtolower($wp_admin_menu_item[0]);

            if (substr($wp_admin_menu_item[2], 0, 9) === 'separator') {
                $menu_item_label = 'separator';
            }

            $this->menu_list[$menu_item_label] = array('hidden' => false, 'slug' => $wp_admin_menu_item[2]);
        }
    }

    public function hide_menus()
    {
        if (!$this->is_admin_area || !$this->is_valid_user_logged) {
            return;
        }
        // hide the menu
        foreach ($this->menu_list as $label => $menu_data) {

            if (in_array($label, $this->menus_to_hide)) {

                if (in_array($this->user_logged_role, $this->target_roles)) {
                    $this->menu_list[$label]['hidden'] = true;
                    $has_menu_removed = remove_menu_page($menu_data['slug']);
                }
                // if(!$has_menu_removed) {
                //     echo esc_html('<h1>Menu ' . $label . ' has not be removed</h1>');
                // }
            }
        }
    }

    public function add_setup_menu()
    {
        add_submenu_page(
            WEGO_CUSTOM_WPMENU_DOMAIN,
            __('Admin Menu List'),
            __('Admin Menu List'),
            'manage_options',
            'webigo_hide_menu_setup',
            array($this, 'load_admin_template')
        );
    }

    public function load_admin_template()
    {
        echo '<div class="webigo-menu-container">';
        
        echo '<div class="webigo-menu-target-roles">';
        echo '<p><strong>Current target roles: ' . implode(", ", $this->target_roles)  .'</strong></p>';
        echo '</div>';
        
        echo '<div class="webigo-menu-wrapper"">';

        foreach ($this->menu_list as $label => $menu_data) {
            $is_menu_hidden = $menu_data['hidden'] == 1 ? 1 : 0;

            $current_menu_visibility_status = $menu_data['hidden'] == 1 ? 'hidden' : 'shown';

            echo '<div class="webigo-menu-item" data-menu-hidden="' . $is_menu_hidden .'">';
            
            echo sprintf('<p><strong>Menu label:</strong> %s</p>', $label);
            echo '<p><strong>Menu slug:</strong> ' . esc_html($menu_data['slug']) . '</p>';
            echo '<p><strong>Current Visibility Status:</strong> ' . esc_html($current_menu_visibility_status) . '</p>';
            echo '</div>';
        }

        echo '</div>';

        echo '</div>';

        $style = '
                    .webigo-menu-target-roles p {
                        text-transform: uppercase;
                        letter-spacing: 1px;
                    }

                    .webigo-menu-wrapper {
                        display: flex;
                        flex-wrap: wrap;
                        width: 90%;
                        margin-top: 1rem;
                    }

                    .webigo-menu-item {
                        width: 33%;
                        padding: 1.15rem; 1rem;
                    }

                    .webigo-menu-item[data-menu-hidden="1"] {
                        color: red;
                    }

                    .webigo-menu-item[data-menu-hidden="0"] {
                        color: yellowgreen;
                    }

                    .webigo-menu-item p {
                        line-height: 1;
                        margin: 0.15rem;
                        text-transform: lowercase;
                        font-family: "Courier New";
                        
                    }
                ';


        echo '<style>' . $style . '</style>';


        // var_dump($menu_list);
        // die;


    }
}
