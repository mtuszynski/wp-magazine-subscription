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

    /**
     * Retrieves the unique subscription category IDs from products within a specific category.
     *
     * This function loops through all products in a specified category and extracts the 
     * 'category_product' meta field. It returns an array of unique category IDs, with values
     * converted to integers.
     *
     * @param int $category_id The ID of the product category to retrieve products from.
     *
     * @return array An array of unique subscription category IDs (as integers).
     */
    public static function get_category_products_meta($category_id)
    {
        $products = self::get_products_by_category($category_id);
        $unique_categories = array();

        foreach ($products as $product) {
            $subscribe_category = get_post_meta($product->ID, 'category_product', true);
            if (!empty($subscribe_category) && !in_array($subscribe_category, $unique_categories)) {
                $unique_categories[] = intval($subscribe_category);
            }
        }

        return $unique_categories;
    }

    /**
     * Checks if a product is in any of the given subscribed categories.
     *
     * @param int   $product_id           The ID of the product.
     * @param array $category_ids         An array of category IDs to check against.
     *
     * @return bool  True if the product is in at least one of the given categories, false otherwise.
     */
    public static function is_product_in_subscribed_categories($product_id, $category_ids)
    {
        if (empty($category_ids)) {
            return false;
        }

        foreach ($category_ids as $category_id) {
            if (self::products_in_subscribed_category($product_id, $category_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves the highest subscription_product_id from products in a given category, including variations.
     *
     * @param int $category_id ID of the product category.
     * @return int The highest subscription_product_id.
     */
    public static function get_max_subscription_product_id($category_id)
    {
        $products = self::get_products_by_category($category_id);
        $max_subscription_product_id = 0;

        foreach ($products as $product) {
            $subscription_product_id = get_post_meta($product->ID, 'subscription_product_id', true);
            if ($subscription_product_id > $max_subscription_product_id) {
                $max_subscription_product_id = $subscription_product_id;
            }
        }

        return $max_subscription_product_id;
    }

    /**
     * Retrieves a subscription product from an order.
     *
     * This method examines all products in an order and returns the first product 
     * that belongs to the subscription category, along with its details.
     *
     * @param int $order_id The ID of the WooCommerce order.
     * @return array|false An array with product details if a subscription product is found, false otherwise.
     */
    public static function get_subscription_product_from_order($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $selected_category_id = self::get_subscribe_category_id();

            if (self::products_in_subscribed_category($product_id, $selected_category_id)) {
                return array(
                    'product_id' => $product_id,
                    'category_product' => get_post_meta($product_id, 'category_product', true),
                    'subscription_length' => get_post_meta($product_id, 'subscription_length', true),
                    'selected_attribute' => get_post_meta($product_id, 'selected_attribute', true)
                );
            }
        }

        return false;
    }
    /**
     * Get the attribute term ID and attribute name (taxonomy) based on the slug.
     *
     * @param string $attribute_slug The slug of the attribute term.
     * @return array|false An array with 'term_id' and 'attribute_name' if found, false otherwise.
     */
    public static function get_attribute_term_id_and_name_by_slug($attribute_slug)
    {
        $attribute_taxonomies = wc_get_attribute_taxonomies();

        if (!$attribute_taxonomies) {
            return false;
        }
        foreach ($attribute_taxonomies as $tax) {
            $taxonomy = wc_attribute_taxonomy_name($tax->attribute_name);

            if (!taxonomy_exists($taxonomy)) {
                continue;
            }
            $term = get_term_by('slug', $attribute_slug, $taxonomy);

            if ($term && !is_wp_error($term)) {
                return array(
                    'term_id'        => $term->term_id,
                    'attribute_name' => $tax->attribute_name
                );
            }
        }
        return false;
    }

    /**
     * Retrieves the variation ID based on the product and attribute term.
     *
     * @param int    $product_id         The product ID.
     * @param string $attribute_name     The attribute taxonomy name.
     * @param int    $attribute_term_id  The attribute term ID.
     *
     * @return int|false  The variation ID or false if not found.
     */
    public static function get_product_variation_id($product_id, $attribute_name, $attribute_term_id)
    {
        $product = wc_get_product($product_id);
        $term = get_term_by('id', $attribute_term_id, 'pa_' . sanitize_title($attribute_name));

        if (!$term) {
            return false;
        }

        $attribute_value = $term->slug;

        if ($product && $product->is_type('variable')) {
            $available_variations = $product->get_available_variations();
            foreach ($available_variations as $variation) {
                $attribute_key = 'attribute_pa_' . sanitize_title($attribute_name);
                if (isset($variation['attributes'][$attribute_key]) && $variation['attributes'][$attribute_key] == $attribute_value) {
                    return $variation['variation_id'];
                }
            }
        }
        return false;
    }

    /**
     * Adds a product (either simple or variable) to an order and ensures download access is granted.
     *
     * @param int    $order_id           The order ID.
     * @param int    $product_id         The product ID.
     * @param string $attribute_name     The attribute taxonomy name.
     * @param int    $attribute_value    The attribute term ID or value.
     *
     * @return void
     */
    public static function add_product_to_order($order_id, $product_id, $attribute_name, $attribute_value)
    {
        $order = wc_get_order($order_id);
        $product = wc_get_product($product_id);

        if (!$order || !$product) {
            return;
        }

        if ($product->is_type('variable')) {
            $variation_id = self::get_product_variation_id($product_id, $attribute_name, $attribute_value);
            if (!$variation_id) {
                return;
            }

            foreach ($order->get_items() as $item) {
                if ($item->get_variation_id() == $variation_id) {
                    return;
                }
            }

            $variation_product = wc_get_product($variation_id);
            $variation_product->set_price(0);
            $order->add_product($variation_product, 1);
            $order->calculate_totals();
            $order->save();
            $order->update_status('completed');

            $downloads = $variation_product->get_downloads();
            foreach ($downloads as $download_id => $download) {
                wc_downloadable_file_permission($download_id, $variation_product->get_id(), $order);
            }
        } else {
            foreach ($order->get_items() as $item) {
                if ($item->get_product_id() == $product_id) {
                    return;
                }
            }

            $product->set_price(0);
            $order->add_product($product, 1);
            $order->calculate_totals();
            $order->save();
            $order->update_status('completed');
            $downloads = $product->get_downloads();
            foreach ($downloads as $download_id => $download) {
                wc_downloadable_file_permission($download_id, $product->get_id(), $order);
            }
        }
    }
}
