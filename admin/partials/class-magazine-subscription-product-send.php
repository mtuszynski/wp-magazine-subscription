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
            // Checkbox is checked - execute necessary actions
        }
    }
}
