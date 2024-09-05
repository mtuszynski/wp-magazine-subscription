<?php

/**
 * Magazine Subscription Order Meta Class
 *
 * This class handles the display and saving of subscription-related meta fields in the WooCommerce order admin panel.
 * It adds custom meta fields for subscriptions and updates subscription information in the database.
 *
 * @package   Magazine_Subscription
 * @subpackage Magazine_Subscription/admin/partials
 */

class Magazine_Subscription_Order_Meta
{
    /**
     * Constructor for the Magazine_Subscription_Order_Meta class.
     *
     * Hooks into WooCommerce actions to display and save custom meta fields in the order admin panel.
     */
    public function __construct()
    {
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_subscription_start_order_meta'), 10, 1);
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'display_subscribe_order_data_in_admin'), 10, 1);
        add_action('woocommerce_process_shop_order_meta', array($this, 'save_subscription_order_data'), 10, 1);
    }

    /**
     * Display subscription start meta field in the WooCommerce order admin panel.
     *
     * This function displays the subscription start issue in the order details under the billing address section.
     *
     * @param WC_Order $order The WooCommerce order object.
     */
    public function display_subscription_start_order_meta($order)
    {
        $subscription_start = get_post_meta($order->get_id(), 'subscription_start', true);
        if ($subscription_start) {
            echo '<p><strong>' . __('Subscription Start Issue') . ':</strong> ' . esc_html($subscription_start) . '</p>';
        }
    }

    /**
     * Display subscription meta fields in the WooCommerce order admin panel.
     *
     * This function adds fields for subscription start, length, end, selected attribute, and category product in the order details section.
     *
     * @param WC_Order $order The WooCommerce order object.
     */
    public function display_subscribe_order_data_in_admin($order)
    {
        $order_id = $order->get_id();
        if (Magazine_Subscription_Helpers::get_subscription_product_from_order($order_id)) {
            echo '<p class="form-field form-field-wide"><label for="subscription_start"><strong>' . __('Subscription Start', 'woocommerce') . ':</strong></label>
    <input type="text" name="subscription_start" id="subscription_start" value="' . esc_attr(get_post_meta($order->get_id(), 'subscription_start', true)) . '" /></p>';

            echo '<p class="form-field form-field-wide"><label for="subscription_length"><strong>' . __('Subscription Length', 'woocommerce') . ':</strong></label>
    <input type="text" name="subscription_length" id="subscription_length" value="' . esc_attr(get_post_meta($order->get_id(), 'subscription_length', true)) . '" /></p>';

            echo '<p class="form-field form-field-wide"><label for="subscription_end"><strong>' . __('Subscription End', 'woocommerce') . ':</strong></label>
    <input type="text" name="subscription_end" id="subscription_end" value="' . esc_attr(get_post_meta($order->get_id(), 'subscription_end', true)) . '" /></p>';

            echo '<p class="form-field form-field-wide"><label for="selected_attribute"><strong>' . __('Selected Attribute', 'woocommerce') . ':</strong></label>
    <input type="text" name="selected_attribute" id="selected_attribute" value="' . esc_attr(get_post_meta($order->get_id(), 'selected_attribute', true)) . '" /></p>';

            echo '<p class="form-field form-field-wide"><label for="category_product"><strong>' . __('Category Product', 'woocommerce') . ':</strong></label>
    <input type="text" name="category_product" id="category_product" value="' . esc_attr(get_post_meta($order->get_id(), 'category_product', true)) . '" /></p>';
        }
    }
    /**
     * Saves subscription data to the order post meta if the order contains a subscription product.
     *
     * This function updates various subscription-related meta fields for the order if a subscription product is present.
     *
     * @param int $order_id The ID of the WooCommerce order.
     */
    function save_subscription_order_data($order_id)
    {
        if (Magazine_Subscription_Helpers::get_subscription_product_from_order($order_id)) {
            if (isset($_POST['subscription_start'])) {
                update_post_meta($order_id, 'subscription_start', sanitize_text_field($_POST['subscription_start']));
            }
            if (isset($_POST['subscription_length'])) {
                update_post_meta($order_id, 'subscription_length', sanitize_text_field($_POST['subscription_length']));
            }
            if (isset($_POST['subscription_end'])) {
                update_post_meta($order_id, 'subscription_end', sanitize_text_field($_POST['subscription_end']));
            }
            if (isset($_POST['selected_attribute'])) {
                update_post_meta($order_id, 'selected_attribute', sanitize_text_field($_POST['selected_attribute']));
            }
            if (isset($_POST['category_product'])) {
                update_post_meta($order_id, 'category_product', sanitize_text_field($_POST['category_product']));
            }
            $this->update_subscription_in_database($order_id);
        }
    }

    /**
     * Save subscription meta fields from the WooCommerce order admin panel.
     *
     * This function saves the custom subscription meta fields when the order is updated in the admin panel.
     *
     * @param int $order_id The ID of the order being updated.
     */
    public function update_subscription_in_database($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $customer_id = $order->get_user_id();
        $user_info = get_userdata($customer_id);
        if (!$user_info) {
            return;
        }

        $subscription_length = get_post_meta($order_id, 'subscription_length', true);
        $attribute_selector = get_post_meta($order_id, 'selected_attribute', true);
        $subscription_start = get_post_meta($order_id, 'subscription_start', true);
        $subscribe_category = get_post_meta($order_id, 'category_product', true);
        $subscription_end = get_post_meta($order_id, 'subscription_end', true);

        global $wpdb;
        $table_name = $wpdb->prefix . 'magazine_subscribe_users';
        $recent_number = Magazine_Subscription_Helpers::get_max_subscription_product_id($subscribe_category);
        $subscribe_left = $subscription_end - $recent_number;

        $data = array(
            'user_id'             => intval($customer_id),
            'user_login'          => sanitize_user($user_info->user_login),
            'user_email'          => sanitize_email($user_info->user_email),
            'order_id'            => intval($order_id),
            'product_name'        => '',
            'subscription_length' => sanitize_text_field($subscription_length),
            'subscription_start'  => sanitize_text_field($subscription_start),
            'subscription_end'    => sanitize_text_field($subscription_end),
            'attribute_selector'  => sanitize_text_field($attribute_selector),
            'subscribe_left'      => intval($subscribe_left)
        );

        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $selected_category_id = Magazine_Subscription_Helpers::get_subscribe_category_id();
            if (Magazine_Subscription_Helpers::products_in_subscribed_category($product_id, $selected_category_id)) {
                $product_name = wc_get_product($product_id)->get_name();
                $data['product_name'] = sanitize_text_field($product_name);
                break;
            }
        }

        $existing_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE order_id = %d",
            $order_id
        ));

        if ($existing_entry) {
            $wpdb->update($table_name, $data, array('id' => $existing_entry->id));
        } else {
            $wpdb->insert($table_name, $data);
        }
    }
}
