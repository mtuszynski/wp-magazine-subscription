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
            $product_category = wp_get_post_terms($post_id, 'product_cat', array('fields' => 'ids'));
            $this->send_to_active_subscribers($post_id, $product_category, $subscription_product_id);
        }
    }
    private function send_to_active_subscribers($post_id, $product_category, $subscription_product_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'magazine_subscribe_users';
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "
        SELECT *
        FROM {$table_name}
        WHERE category_subscription_id = %d
        AND %d >= subscription_start
        AND %d <= subscription_end
        ",
                $product_category,
                $subscription_product_id,
                $subscription_product_id
            )
        );
        if (!empty($results)) {
            foreach ($results as $subscriber) {
            }
        }
    }
}
