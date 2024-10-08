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


    function magazine_subscription_active_list_page()
    {
        echo '<h1>' . __('Active subscription', 'magazine-subscription') . '</h1>';
        $this->display_active_subscriptions();
    }
    function magazine_subscription_inactive_list_page() {}
    function magazine_subscription_send_page() {}

    /**
     * Displays the export page for subscribers in the WordPress admin.
     *
     * This method generates a page with a button to export subscription data to a CSV file.
     * If the 'export' query parameter is set to 'csv', it triggers the CSV export and exits.
     */
    function magazine_subscription_export_page()
    {
        echo '<h1>' . __('Export active subscribers to csv file', 'magazine-subscription') . '</h1>';
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            if (ob_get_length()) {
                ob_end_clean();
            }

            $this->export_csv();
            exit;
        }

        echo '<div class="wrap">';
        echo '<h1>' . __('Subscribers Export', 'magazine-subscription') . '</h1>';
        echo '<p><a href="' . admin_url('admin.php?page=magazine-subscription-export-page&export=csv') . '" class="button button-primary">' . __('Export Subscriptions to CSV', 'magazine-subscription') . '</a></p>';
        echo '</div>';
    }

    /**
     * Displays a table of active subscriptions in the WordPress admin area.
     *
     * This function retrieves active subscriptions from the custom database table and displays them in a table format.
     * The table includes details such as username, email, order ID, product name, subscription start, subscription end,
     * attribute selector, and the number of subscriptions left.
     */
    function display_active_subscriptions()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'magazine_subscribe_users';
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE subscribe_left > %d",
            0
        );
        $subscriptions = $wpdb->get_results($query);

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . __('Active Subscriptions', 'magazine-subscription') . '</h1>';

        if ($subscriptions) {
            echo '<table id="MagazineSubscriptionsActive" class="wp-list-table widefat fixed striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>' . __('Username', 'magazine-subscription') . '</th>';
            echo '<th>' . __('Email', 'magazine-subscription') . '</th>';
            echo '<th>' . __('Order ID', 'magazine-subscription') . '</th>';
            echo '<th>' . __('Product Name', 'magazine-subscription') . '</th>';
            echo '<th>' . __('Subscription Start', 'magazine-subscription') . '</th>';
            echo '<th>' . __('Subscription End', 'magazine-subscription') . '</th>';
            echo '<th>' . __('Attribute Selector', 'magazine-subscription') . '</th>';
            echo '<th>' . __('Subscription Left', 'magazine-subscription') . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            foreach ($subscriptions as $subscription) {
                $subscription_start = $subscription->subscription_start;
                $subscription_end = $subscription->subscription_end;
                $order_permalink = admin_url('post.php?post=' . $subscription->order_id . '&action=edit');

                echo '<tr>';
                echo '<td>' . esc_html($subscription->user_login) . '</td>';
                echo '<td>' . esc_html($subscription->user_email) . '</td>';
                echo '<td><a href="' . esc_url($order_permalink) . '">' . esc_html($subscription->order_id) . '</a></td>';
                echo '<td>' . esc_html($subscription->product_name) . '</td>';
                echo '<td>' . esc_html($subscription_start) . '</td>';
                echo '<td>' . esc_html($subscription_end) . '</td>';
                echo '<td>' . esc_html($subscription->attribute_selector) . '</td>';
                echo '<td>' . esc_html($subscription->subscribe_left) . '</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>' . __('No active subscriptions found.', 'magazine-subscription') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Exports subscription data to a CSV file and initiates a download.
     *
     * This method fetches subscription data from the database and outputs it as a CSV file for download.
     */
    private function export_csv()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'magazine_subscribe_users';

        $subscriptions = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE subscribe_left > %d", 0)
        );

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="active_subscriptions.csv"');
        header('Cache-Control: max-age=0');
        header('Pragma: no-cache');

        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        $separator = ';';
        fputcsv($output, array(
            __('Username', 'magazine-subscription'),
            __('Email', 'magazine-subscription'),
            __('Order Id', 'magazine-subscription'),
            __('Product Name', 'magazine-subscription'),
            __('Subscription Start', 'magazine-subscription'),
            __('Subscription End', 'magazine-subscription'),
            __('Attribute Selector', 'magazine-subscription'),
            __('Subscription Left', 'magazine-subscription')
        ), $separator);

        foreach ($subscriptions as $subscription) {
            fputcsv($output, array(
                $subscription->user_login,
                $subscription->user_email,
                $subscription->order_id,
                $subscription->product_name,
                $subscription->subscription_start,
                $subscription->subscription_end,
                $subscription->attribute_selector,
                $subscription->subscribe_left
            ), $separator);
        }

        fclose($output);

        exit();
    }
}
