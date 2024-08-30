<?php

/**
 *
 * The Magazine_Subscription_Menu class represents the menu and its submenus for the Magazine Subscription plugin in the WordPress admin area.
 *
 * @since      1.0.0
 * @package    Magazine_Subscription
 * @subpackage Magazine_Subscription/admin/partials
 * @author     MirT <tuszynski.mir@gmail.com>
 */
class Magazine_Subscription_Menu
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'magazine_subscription_menu'));
    }
    public function magazine_subscription_menu()
    {
        add_menu_page(
            __('Subscribe', 'magazine-subscription'),
            __('Subscribe', 'magazine-subscription'),
            'manage_options',
            'magazine-subscription',
            array($this, 'magazine_subscription_page'),
            'dashicons-feedback'
        );
        add_submenu_page(
            'magazine-subscription',
            __('Settings', 'magazine-subscription'),
            __('Settings', 'magazine-subscription'),
            'manage_options',
            'magazine-subscription',
            array($this, 'magazine_subscription_page')
        );
        add_submenu_page(
            'magazine-subscription',
            __('Subscribers List', 'magazine-subscription'),
            __('Subscribers List', 'magazine-subscription'),
            'manage_options',
            'magazine-subscription-active-list-page',
            array($this, 'magazine_subscription_active_list_page')
        );
        add_submenu_page(
            'magazine-subscription',
            __('Subscribers Inactive List', 'magazine-subscription'),
            __('Subscribers Inactive List', 'magazine-subscription'),
            'manage_options',
            'magazine-subscription-inactive-list-page',
            array($this, 'magazine_subscription_inactive_list_page')
        );
        add_submenu_page(
            'magazine-subscription',
            __('Subscribers Send', 'magazine-subscription'),
            __('Subscribers Send', 'magazine-subscription'),
            'manage_options',
            'magazine-subscription-send-page',
            array($this, 'magazine_subscription_send_page')
        );
        add_submenu_page(
            'magazine-subscription',
            __('Subscribers Export', 'magazine-subscription'),
            __('Subscribers Export', 'magazine-subscription'),
            'manage_options',
            'magazine-subscription-export-page',
            array($this, 'magazine_subscription_export_page')
        );
    }
    function magazine_subscription_page()
    {
        global $wpdb;

        if (isset($_POST['magazine_subscription_save_settings'])) {
            check_admin_referer('magazine_subscription_save_settings');

            $selected_category = !empty($_POST['magazine_subscription_category']) ? intval($_POST['magazine_subscription_category']) : 0;
            $delete_tables_on_deactivation = isset($_POST['delete_tables_on_deactivation']) ? 1 : 0;
            $existing_setting = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}magazine_subscribe_settings WHERE id = 1");

            if ($existing_setting) {
                $wpdb->update(
                    $wpdb->prefix . 'magazine_subscribe_settings',
                    array(
                        'category_id' => $selected_category,
                        'delete_tables_on_deactivation' => $delete_tables_on_deactivation
                    ),
                    array('id' => 1),
                    array('%d', '%d'),
                    array('%d')
                );
            } else {
                $wpdb->insert(
                    $wpdb->prefix . 'magazine_subscribe_settings',
                    array(
                        'id' => 1,
                        'category_id' => $selected_category,
                        'delete_tables_on_deactivation' => $delete_tables_on_deactivation
                    ),
                    array('%d', '%d', '%d')
                );
            }

?>
            <div class="updated">
                <p><?php _e('Settings saved.', 'magazine-subscription'); ?></p>
            </div>
        <?php
        }

        $settings = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}magazine_subscribe_settings WHERE id = 1");

        ?>
        <h1><?php _e('Subscription Settings', 'magazine-subscription'); ?></h1>
        <form method="post" action="" class="magazine-subscription-form">
            <?php wp_nonce_field('magazine_subscription_save_settings'); ?>

            <div class="form-group">
                <label for="magazine_subscription_category">
                    <?php _e('Select Product Category', 'magazine-subscription'); ?>
                </label>
                <div class="input-field">
                    <?php
                    $args = array(
                        'taxonomy' => 'product_cat',
                        'hide_empty' => false,
                        'orderby' => 'name',
                        'order' => 'ASC',
                        'selected' => $settings ? $settings->category_id : 0,
                        'name' => 'magazine_subscription_category',
                        'class' => 'wc-enhanced-select',
                    );
                    wp_dropdown_categories($args);
                    ?>
                </div>
            </div>

            <div class="form-group">
                <label for="delete_tables_on_deactivation">
                    <?php _e('Delete tables on deactivation', 'magazine-subscription'); ?>
                </label>
                <div class="input-field">
                    <input type="checkbox" name="delete_tables_on_deactivation" value="1"
                        <?php checked(1, $settings ? $settings->delete_tables_on_deactivation : 0); ?>>
                </div>
            </div>

            <div class="form-group">
                <input type="submit" name="magazine_subscription_save_settings" class="button button-primary" value="<?php _e('Save Settings', 'magazine-subscription'); ?>">
            </div>
        </form>
<?php
    }


    function magazine_subscription_active_list_page() {}
    function magazine_subscription_inactive_list_page() {}
    function magazine_subscription_send_page() {}
    function magazine_subscription_export_page() {}
}
