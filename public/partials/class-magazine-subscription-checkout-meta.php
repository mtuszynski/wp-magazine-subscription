<?php

class Magazine_Subscription_Checkout_Meta
{
    public function __construct()
    {
        add_action('woocommerce_before_checkout_billing_form', array($this, 'add_subscription_start_field'));
        add_action('woocommerce_checkout_process', array($this, 'validate_subscription_start_field'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_subscription_start_field'));
        add_action('woocommerce_checkout_order_processed', array($this, 'save_subscription_data_to_order'), 10, 1);
        add_action('woocommerce_email_order_meta', array($this, 'email_subscription_start_order_meta'), 10, 4);
        add_action('woocommerce_order_details_after_order_table_items', array($this, 'subscribe_order_details'));
    }

    /**
     * Retrieves the latest subscription end issue number for a given user.
     *
     * This function queries the database to find the maximum `subscription_end` value for the specified user.
     * It only returns subscription end numbers that are greater than the given `recent_number`, ensuring that
     * it provides the latest active subscription end issue.
     *
     * @param int $user_id The ID of the user for whom the subscription end is being retrieved.
     * @param int $recent_number The most recent issue number available for sale (used to filter outdated subscriptions).
     *
     * @return int|null The latest subscription end issue number for the user, or null if no subscription is found.
     */
    private function get_user_subscription_end($user_id, $recent_number)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'magazine_subscribe_users';
        $query = $wpdb->prepare(
            "SELECT MAX(subscription_end) FROM $table_name 
            WHERE user_id = %d 
            AND subscription_end > %d",
            $user_id,
            $recent_number
        );

        return $wpdb->get_var($query);
    }
    /**
     * Displays the subscription start form field in the checkout.
     *
     * This function outputs a message indicating the currently available issue for sale,
     * followed by a WooCommerce form field allowing the user to select the issue number 
     * from which they want to start their subscription. The selection is restricted to 
     * a range of issues (from three issues before to three issues after the most recent one).
     *
     * @param int $recent_number The most recent issue number currently available for sale.
     * @param WC_Checkout $checkout The WooCommerce checkout object.
     * @param int $default_value The default value for the subscription start field.
     */
    private function show_subscription_start_form($recent_number, $checkout, $default_value)
    {
        echo '<p>' . sprintf(__('The currently available issue for sale is: %d', 'magazine-subscription'), $recent_number) .  '</p>';
        woocommerce_form_field('subscription_start', array(
            'type'          => 'number',
            'class'         => array('subscription-start-field form-row-wide'),
            'label'         => sprintf(__('Select the issue from which you want to start your subscription - you can choose an issue between %d and %d:', 'magazine-subscription'), ($recent_number - 3), ($recent_number + 3)),
            'custom_attributes' => array(
                'step' => '1',
                'min' => $recent_number - 3,
                'max' => $recent_number + 3
            ),
            'default'       => $default_value,
            'value'         => $default_value
        ), $checkout->get_value('subscription_start'));
    }

    /**
     * Adds the subscription start field to the checkout page if the cart contains a subscription product.
     *
     * @param WC_Checkout $checkout The WooCommerce checkout object.
     */
    public function add_subscription_start_field($checkout)
    {
        $selected_category_id = Magazine_Subscription_Helpers::get_subscribe_category_id();
        $has_subscription_product = false;
        $product_id = null; // Initialize product ID

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id_candidate = $cart_item['product_id'];
            if (Magazine_Subscription_Helpers::products_in_subscribed_category($product_id_candidate, $selected_category_id)) {
                $has_subscription_product = true;
                $product_id = $product_id_candidate; // Set the product ID to the matching product
                break;
            }
        }

        if ($has_subscription_product && $product_id) {
            $subscribe_category = get_post_meta($product_id, 'category_product', true);
            $recent_number = Magazine_Subscription_Helpers::get_max_subscription_product_id($subscribe_category);
            $default_value = $checkout->get_value('subscription_start') ? $checkout->get_value('subscription_start') : $recent_number + 1;
            echo '<div id="magazine_subscription_start_field"><h5>' . __('Subscription Start', 'magazine-subscription') . '</h5>';

            $customer_id = get_current_user_id();

            if ($customer_id) {
                $subscription_end = $this->get_user_subscription_end($customer_id, $recent_number);

                if ($subscription_end) {
                    echo '<p>' . sprintf(__('The currently available issue for sale is: %d', 'magazine-subscription'), $recent_number) .  '</p>';
                    echo '<p>' . sprintf(__('Dear Reader, you have an active subscription up to issue %d. By making a purchase, the new subscription will start from issue %d.', 'magazine-subscription'), $subscription_end, ($subscription_end + 1)) . '</p>';
                    echo '<input type="hidden" name="subscription_start" value="' . ($subscription_end + 1) . '">';
                } else {
                    $default_value = $recent_number + 1;
                    $this->show_subscription_start_form($recent_number, $checkout, $default_value);
                }
            } else {
                $default_value = $recent_number + 1;
                $this->show_subscription_start_form($recent_number, $checkout, $default_value);
            }

            echo '</div>';
        }
    }
    /**
     * Validates the subscription start field during the checkout process.
     * Ensures that the subscription start is valid based on the recent issue and the user's existing subscription (if any).
     */
    public function validate_subscription_start_field()
    {
        if (isset($_POST['subscription_start'])) {
            $subscription_start = intval($_POST['subscription_start']);
            $selected_category_id = Magazine_Subscription_Helpers::get_subscribe_category_id();
            $has_subscription_product = false;
            $recent_number = null;

            foreach (WC()->cart->get_cart() as $cart_item) {
                $product_id = $cart_item['product_id'];
                if (Magazine_Subscription_Helpers::products_in_subscribed_category($product_id, $selected_category_id)) {
                    $has_subscription_product = true;
                    $subscribe_category = get_post_meta($product_id, 'category_product', true);
                    $recent_number = Magazine_Subscription_Helpers::get_max_subscription_product_id($subscribe_category);
                    break;
                }
            }

            $customer_id = get_current_user_id();

            if ($customer_id && $has_subscription_product) {
                $subscription_end = $this->get_user_subscription_end($customer_id, $recent_number);

                if ($subscription_end) {
                    $expected_start = $subscription_end + 1;
                    if ($subscription_start !== $expected_start) {
                        wc_add_notice(
                            sprintf(__('For an active subscription, the starting issue must be %d.', 'magazine-subscription'), $expected_start),
                            'error'
                        );
                    }
                } elseif ($subscription_start < ($recent_number - 3) || $subscription_start > ($recent_number + 3)) {
                    wc_add_notice(
                        sprintf(__('The starting issue of the subscription must be between %d and %d.', 'magazine-subscription'), $recent_number - 3, $recent_number + 3),
                        'error'
                    );
                }
            }
        }
    }
    /**
     * Saves the subscription start field to the order's post meta.
     *
     * This function updates the 'subscription_start' meta field for the given order (post) ID,
     * if the 'subscription_start' field is present in the POST request.
     *
     * @param int $post_id The ID of the order (post) to which the subscription start field is saved.
     */
    public function save_subscription_start_field($post_id)
    {
        if (!empty($_POST['subscription_start'])) {
            update_post_meta($post_id, 'subscription_start', intval($_POST['subscription_start']));
        }
    }


    /**
     * Saves subscription data to the order post meta.
     *
     * This function processes the order and checks if it contains a subscription product. 
     * If the product is a subscription, it calculates the start and end dates, as well as 
     * other related meta data, and saves them to the order's post meta.
     *
     * @param int $order_id The ID of the WooCommerce order.
     */
    public function save_subscription_data_to_order($order_id)
    {
        $order = wc_get_order($order_id);
        $customer_id = $order->get_user_id();
        $subscription_product = Magazine_Subscription_Helpers::get_subscription_product_from_order($order_id);

        if ($subscription_product) {
            $subscription_start = get_post_meta($order_id, 'subscription_start', true);
            $subscribe_category = $subscription_product['category_product'];
            $subscription_length = $subscription_product['subscription_length'];
            $attribute_selector = $subscription_product['selected_attribute'];

            if ($subscription_start && $subscription_length && $attribute_selector) {

                $previous_order = $this->get_previous_orders($customer_id, $subscribe_category, $attribute_selector);
                if ($previous_order) {
                    $subscription_start = $previous_order['subscription_end'] + 1;
                }

                $subscription_end = $subscription_start + $subscription_length - 1;
                update_post_meta($order_id, 'subscription_start', $subscription_start);
                update_post_meta($order_id, 'subscription_length', $subscription_length);
                update_post_meta($order_id, 'subscription_end', $subscription_end);
                update_post_meta($order_id, 'selected_attribute', $attribute_selector);
                update_post_meta($order_id, 'category_product', $subscribe_category);
            }
        }
    }

    /**
     * Retrieves the most recent subscription order details for a customer that matches the specified category and attribute.
     *
     * This function searches through the customer's completed orders to find the most recent subscription order that
     * matches the provided category and attribute. It returns the subscription details of the matching order.
     *
     * @param int $customer_id The ID of the customer whose orders are being checked.
     * @param string $new_order_category The category of the new subscription order to match.
     * @param string $new_order_attribute The attribute of the new subscription order to match.
     * @return array|false An associative array containing the subscription details if a matching order is found,
     *                     or false if no matching order is found.
     */
    private function get_previous_orders($customer_id, $new_order_category, $new_order_attribute)
    {
        $orders = wc_get_orders(array(
            'customer' => $customer_id,
            'status' => 'completed',
            'limit' => -1
        ));

        foreach ($orders as $order) {
            $order_id = $order->get_id();
            $subscription_start = get_post_meta($order_id, 'subscription_start', true);
            $subscription_length = get_post_meta($order_id, 'subscription_length', true);
            $subscription_end = get_post_meta($order_id, 'subscription_end', true);
            $selected_attribute = get_post_meta($order_id, 'selected_attribute', true);
            $category_product = get_post_meta($order_id, 'category_product', true);

            $recent_number = Magazine_Subscription_Helpers::get_max_subscription_product_id($category_product);

            if ($subscription_start && $subscription_length && $subscription_end && $selected_attribute && $category_product && $recent_number <= $subscription_end) {
                if ($category_product == $new_order_category && $selected_attribute == $new_order_attribute) {
                    return array(
                        'subscription_start' => $subscription_start,
                        'subscription_length' => $subscription_length,
                        'subscription_end' => $subscription_end,
                        'selected_attribute' => $selected_attribute,
                        'category_product' => $category_product,
                    );
                }
            }
        }
        return false;
    }
    /**
     * Adds subscription start information to the email order meta.
     *
     * This function displays the subscription start issue in the order email, either in plain text or HTML format.
     * It retrieves the subscription start issue from the order meta and includes it in the email.
     *
     * @param WC_Order $order The order object.
     * @param bool $sent_to_admin Whether the email is being sent to the admin.
     * @param bool $plain_text Whether the email is in plain text format.
     * @param WC_Email $email The email object.
     */
    public function email_subscription_start_order_meta($order, $sent_to_admin, $plain_text, $email)
    {
        $subscription_start = get_post_meta($order->get_id(), 'subscription_start', true);

        if ($subscription_start) {
            if ($plain_text) {
                echo "\n" . __('Subscription Start Issue', 'magazine-subscription') . ': ' . esc_html($subscription_start) . "\n";
            } else {
                echo '<p><strong>' . __('Subscription Start Issue', 'magazine-subscription') . ':</strong> ' . esc_html($subscription_start) . '</p>';
            }
        }
    }

    /**
     * Displays subscription details in the order details section on the frontend.
     *
     * This method outputs subscription-related details such as the start issue, length, end issue, and selected attribute
     * of a subscription when viewing the order details on the frontend. It is hooked to 'woocommerce_order_details_after_order_table_items'
     * to ensure it appears after the order items table on the order details page.
     *
     * @param WC_Order $order The WooCommerce order object.
     */
    public function subscribe_order_details($order)
    {
        $subscription_start = get_post_meta($order->get_id(), 'subscription_start', true);
        $subscription_length = get_post_meta($order->get_id(), 'subscription_length', true);
        $subscription_end = get_post_meta($order->get_id(), 'subscription_end', true);
        $selected_attribute = get_post_meta($order->get_id(), 'selected_attribute', true);

        if ($subscription_start) : ?>
            <tr>
                <th scope="row"><?php echo __('Subscription Start Issue', 'magazine-subscription'); ?></th>
                <td><?php echo esc_html($subscription_start); ?></td>
            </tr>
        <?php
        endif;

        if ($subscription_length) :
        ?>
            <tr>
                <th scope="row"><?php echo __('Subscription Length', 'magazine-subscription'); ?></th>
                <td><?php echo esc_html($subscription_length); ?></td>
            </tr>
        <?php
        endif;

        if ($subscription_end) :
        ?>
            <tr>
                <th scope="row"><?php echo __('Subscription End Issue', 'magazine-subscription'); ?></th>
                <td><?php echo esc_html($subscription_end); ?></td>
            </tr>
        <?php
        endif;

        if ($selected_attribute) :
        ?>
            <tr>
                <th scope="row"><?php echo __('Selected Attribute', 'magazine-subscription'); ?></th>
                <td><?php echo esc_html($selected_attribute); ?></td>
            </tr>
<?php
        endif;
    }
}
