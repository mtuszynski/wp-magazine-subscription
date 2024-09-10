<?php

/**
 * Class Magazine_Subscription_Order_Complete
 *
 * This class handles WooCommerce orders with the "completed" status to manage subscription data.
 * When an order is marked as "completed", this class updates the subscription database and adds the subscription products to the user's account.
 */
class Magazine_Subscription_Order_Complete
{
    /**
     * Magazine_Subscription_Order_Complete constructor.
     *
     * Hooks into WooCommerce's order completion event to trigger subscription handling.
     */
    public function __construct()
    {
        // Hook into WooCommerce's order status change to 'completed'.
        add_action('woocommerce_order_status_completed', array($this, 'handle_subscription_order_complete'));
    }

    /**
     * Handles actions to be taken when a WooCommerce order is marked as completed.
     *
     * This method checks if the order contains a subscription product, updates user meta with subscription details,
     * and updates the subscription records in the custom database table.
     *
     * @param int $order_id The ID of the WooCommerce order.
     */
    public function handle_subscription_order_complete($order_id)
    {
        $order = wc_get_order($order_id);
        $customer_id = $order->get_user_id();
        if (!$customer_id) {
            return;
        }

        $user_info = get_userdata($customer_id);
        $subscription_product = Magazine_Subscription_Helpers::get_subscription_product_from_order($order_id);

        if ($subscription_product) {
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
                'product_name'        => sanitize_text_field(wc_get_product($subscription_product['product_id'])->get_name()),
                'subscription_length' => $subscription_length,
                'subscription_start'  => $subscription_start,
                'subscription_end'    => $subscription_end,
                'attribute_selector'  => $attribute_selector,
                'subscribe_left'      => $subscribe_left
            );

            $existing_entry = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_name WHERE order_id = %d",
                $data['order_id']
            ));

            if ($existing_entry) {
                $wpdb->update($table_name, $data, array('id' => $existing_entry->id));
            } else {
                $wpdb->insert($table_name, $data);
            }

            $this->assign_old_subscription_products_to_user($order_id, $subscribe_category, $subscription_start, $subscription_end, $recent_number);
        }
    }
    private function assign_old_subscription_products_to_user($order_id, $subscribe_category, $subscription_start, $subscription_end, $recent_number)
    {
        $subscription_products = Magazine_Subscription_Helpers::get_products_by_category($subscribe_category);
        $attribute_selector = get_post_meta($order_id, 'selected_attribute', true);
        $attribute = Magazine_Subscription_Helpers::get_attribute_term_id_and_name_by_slug($attribute_selector);
        $attribute_id = $attribute['term_id'];
        $attribute_name = $attribute['attribute_name'];
        if ($subscription_start <= $recent_number) {
            foreach ($subscription_products as $product) {
                $product_id = $product->ID;
                $subscription_product_id = get_post_meta($product_id, 'subscription_product_id', true);
                if ($subscription_product_id >= $subscription_start && $subscription_product_id <= $subscription_end) {
                    Magazine_Subscription_Helpers::add_product_to_order($order_id, $product_id, $attribute_name, $attribute_id);
                }
            }
        }
    }
}
