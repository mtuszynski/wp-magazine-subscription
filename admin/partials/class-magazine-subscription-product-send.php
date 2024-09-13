<?php

/**
 * Class Magazine_Subscription_Product_Send
 *
 * Handles the checking of the 'Send Subscriptions' checkbox for WooCommerce products.
 * It checks if the checkbox was selected when a product post is saved and performs necessary actions.
 */
class Magazine_Subscription_Product_Send
{
    /**
     * Constructor.
     *
     * Initializes the class by adding an action hook to the 'save_post' hook.
     */
    public function __construct()
    {
        add_action('save_post', array($this, 'check_send_subscription_checkbox'), 10, 2);
    }

    /**
     * Checks if the 'Send Subscriptions' checkbox was selected when a product post is saved.
     *
     * This method is hooked to the 'save_post' action and is executed when a post is saved.
     * It verifies if the post type is 'product', the post status is 'publish', and if the checkbox
     * is checked. If all conditions are met, it will execute the code to handle the subscription sending.
     *
     * @param int    $post_id The ID of the post being saved.
     * @param WP_Post $post   The post object being saved.
     *
     * @return void
     */
    function check_send_subscription_checkbox($post_id, $post)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ($post->post_type != 'product') {
            return;
        }

        if ($post->post_status != 'publish') {
            return;
        }

        if (isset($_POST['send_subscriptions']) && $_POST['send_subscriptions'] == '1') {
            $subscription_product_id = get_post_meta($post_id, 'subscription_product_id', true);
            $product_categories = wp_get_post_terms($post_id, 'product_cat', array('fields' => 'ids'));
            $this->send_to_active_subscribers($post_id, $product_categories, $subscription_product_id);
        }
    }
    private function send_to_active_subscribers($post_id, $product_categories, $subscription_product_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'magazine_subscribe_users';
        if (is_array($product_categories)) {
            $placeholders = implode(',', array_fill(0, count($product_categories), '%d'));
            $query = $wpdb->prepare(
                "SELECT * FROM $table_name 
            WHERE category_subscription_id IN ($placeholders)
            AND subscription_start <= %d 
            AND subscription_end >= %d",
                array_merge($product_categories, [$subscription_product_id, $subscription_product_id])
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM $table_name 
            WHERE category_subscription_id = %d 
            AND subscription_start <= %d 
            AND subscription_end >= %d",
                $product_categories,
                $subscription_product_id,
                $subscription_product_id
            );
        }

        $results = $wpdb->get_results($query, ARRAY_A);
        if (!empty($results)) {
            foreach ($results as $subscriber) {
                $order_id = $subscriber['order_id'];
                $product_id = $post_id;
                $attribute_selector = $subscriber['attribute_selector'];
                $attribute = Magazine_Subscription_Helpers::get_attribute_term_id_and_name_by_slug($attribute_selector);
                $attribute_id = $attribute['term_id'];
                $attribute_name = $attribute['attribute_name'];
                $subscribe_left = $subscriber['subscribe_left'];
                $subscription_end = $subscriber['subscription_end'];
                $recent_number = Magazine_Subscription_Helpers::get_max_subscription_product_id($product_categories);
                Magazine_Subscription_Helpers::add_product_to_order($order_id, $product_id, $attribute_name, $attribute_id);
                $subscribe_left = $subscription_end - $recent_number;

                if ($subscribe_left < 0) {
                    $subscribe_left = 0;
                }

                $wpdb->update(
                    $table_name,
                    ['subscribe_left' => $subscribe_left],
                    ['id' => $subscriber['id']],
                    ['%d'],
                    ['%d']
                );
            }
        }
    }
}
