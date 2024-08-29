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
 * @subpackage Magazine_Subscription/includes
 * @since      1.0.0
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
}
