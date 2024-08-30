<?php

/**
 * Magazine_Subscription_Helpers Class
 *
 * This class contains helper functions that are used throughout the
 * Magazine Subscription plugin. These functions are designed to perform
 * common tasks such as formatting dates, retrieving subscription statuses,
 * sanitizing input data, and other utility operations that are frequently
 * required in the plugin's codebase.
 *
 * @package    Magazine_Subscription
 * @subpackage Magazine_Subscription/admin/partials
 * @since      1.0.0
 * @author     MirT <tuszynski.mir@gmail.com>
 */
class Magazine_Subscription_Helpers
{
    /**
     * Retrieves the subscription category ID from the database.
     *
     * This function queries the magazine subscription settings table
     * to get the ID of the selected category for subscriptions.
     *
     * @since 1.0.0
     *
     * @return int|null The ID of the subscription category, or null if not set.
     */
    public static function get_subscribe_category_id()
    {
        global $wpdb;

        $category_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT category_id FROM {$wpdb->prefix}magazine_subscribe_settings WHERE id = %d",
                1
            )
        );

        return $category_id ? intval($category_id) : null;
    }

    /**
     * Retrieve all products from a specific category.
     *
     * @param int $category_id The ID of the category to get products from.
     * @return array Array of product posts.
     */
    public static function get_products_by_category($category_id)
    {
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category_id,
                    'operator' => 'IN',
                ),
            ),
        );

        $products = get_posts($args);

        return $products;
    }

    /**
     * Checks if a product belongs to a specified category.
     *
     * This method verifies whether a given product ID is associated with a specific product category ID.
     *
     * @param int $product_id The ID of the product to check.
     * @param int $selected_category_id The ID of the category to check against.
     * @return bool True if the product is in the specified category, false otherwise.
     */
    public static function products_in_subscribed_category($product_id, $selected_category_id)
    {
        $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
        return in_array($selected_category_id, $product_categories, true);
    }
}
