<?php

/**
 * Class Magazine_Subscription_Product_Meta
 *
 * This class is responsible for adding custom meta fields to WooCommerce products and saving the data entered into these fields.
 * 
 * @since      1.0.0
 * @package    Magazine_Subscription
 * @subpackage Magazine_Subscription/admin/partials
 * @author     MirT <tuszynski.mir@gmail.com>
 */
class Magazine_Subscription_Product_Meta
{
    /**
     * Constructor for the class.
     *
     * Registers actions for adding custom meta fields to the product general data tab in the WooCommerce product edit screen
     * and for saving those fields when the product is saved.
     */
    public function __construct()
    {
        add_action('woocommerce_product_options_general_product_data', array($this, 'magazine_subscribe_product_meta'));
        add_action('woocommerce_process_product_meta', array($this, 'save_magazine_subscribe_product_meta'));
        add_action('add_meta_boxes', array($this, 'add_send_subscription_checkbox'));
    }

    /**
     * Displays custom subscription meta fields in the product edit page.
     *
     * This function adds custom fields to the WooCommerce product edit page 
     * for products that belong to the subscription category. These fields include:
     * - Subscription Length (in months)
     * - Category Product dropdown
     * - Attribute Selector dropdown
     * - Subscription Product ID (only displayed if the product is not in the subscription category)
     *
     * @global WP_Post $post The current post object.
     */
    public function magazine_subscribe_product_meta()
    {
        global $post;
        $product_id = $post->ID;
        $selected_category_id = Magazine_Subscription_Helpers::get_subscribe_category_id();

        if (Magazine_Subscription_Helpers::products_in_subscribed_category($product_id, $selected_category_id)) { ?>
            <div class="options_group">
                <div class="subscription-form">
                    <?php
                    woocommerce_wp_text_input(
                        array(
                            'id'                => 'subscription_length',
                            'label'             => __('Subscription Length (months)', 'magazine-subscription'),
                            'placeholder'       => __('e.g. 12', 'magazine-subscription'),
                            'desc_tip'          => 'true',
                            'description'       => __('Enter the subscription length in months.', 'magazine-subscription'),
                            'type'              => 'number',
                            'custom_attributes' => array(
                                'step' => '1',
                                'min'  => '1'
                            )
                        )
                    );

                    $args = array(
                        'taxonomy'   => 'product_cat',
                        'hide_empty' => false,
                        'orderby'    => 'name',
                        'order'      => 'ASC',
                        'name'       => 'category_product',
                        'class'      => 'wc-enhanced-select',
                        'selected'   => get_post_meta($product_id, 'category_product', true),
                    );
                    ?>
                    <select id="category_product" name="category_product" class="wc-enhanced-select">
                        <?php
                        $categories = get_terms(array(
                            'taxonomy'   => 'product_cat',
                            'hide_empty' => false,
                            'orderby'    => 'name',
                            'order'      => 'ASC',
                        ));

                        foreach ($categories as $category) {
                            $selected = (get_post_meta($product_id, 'category_product', true) == $category->term_id) ? 'selected="selected"' : '';
                            echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                        }
                        ?>
                    </select>
                    <?php

                    $attribute_taxonomies = wc_get_attribute_taxonomies();
                    $taxonomy_terms = array();

                    if ($attribute_taxonomies) :
                        foreach ($attribute_taxonomies as $tax) :
                            if (taxonomy_exists(wc_attribute_taxonomy_name($tax->attribute_name))) :
                                $taxonomy_terms[$tax->attribute_name] = get_terms(wc_attribute_taxonomy_name($tax->attribute_name), array('orderby' => 'name', 'hide_empty' => 0));
                            endif;
                        endforeach;
                    endif;

                    if (!empty($taxonomy_terms)) :
                    ?>
                        <select id="attribute_selector" name="attribute_selector" class="attribute-selector">
                            <option value=""><?php _e('Select an Attribute', 'magazine-subscription'); ?></option>
                            <?php
                            foreach ($taxonomy_terms as $attribute_name => $terms) {
                                echo '<optgroup label="' . esc_attr($attribute_name) . '">';
                                foreach ($terms as $term) {
                                    $selected = (get_post_meta($product_id, 'selected_attribute', true) == $term->slug) ? 'selected="selected"' : '';
                                    echo '<option value="' . esc_attr($term->slug) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
                                }
                                echo '</optgroup>';
                            }
                            ?>
                        </select>
                    <?php
                    endif;
                    ?>
                </div>
            </div>
<?php
        } else if() {
            woocommerce_wp_text_input(
                array(
                    'id'                => 'subscription_product_id',
                    'label'             => __('Subscription Product ID', 'woocommerce'),
                    'placeholder'       => __('e.g. 123', 'woocommerce'),
                    'desc_tip'          => 'true',
                    'description'       => __('Enter the product ID subscription number.', 'woocommerce'),
                    'type'              => 'number',
                    'custom_attributes' => array(
                        'step' => '1'
                    )
                )
            );
        }
    }

    /**
     * Saves custom meta fields when the product is saved.
     *
     * @param int $post_id The product ID.
     */
    public function save_magazine_subscribe_product_meta($post_id)
    {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['subscription_length'])) {
            $subscription_length = sanitize_text_field($_POST['subscription_length']);
            update_post_meta($post_id, 'subscription_length', $subscription_length);
        }

        if (isset($_POST['category_product'])) {
            $category_product = intval($_POST['category_product']);
            update_post_meta($post_id, 'category_product', $category_product);
        }

        if (isset($_POST['attribute_selector'])) {
            $selected_attribute = sanitize_text_field($_POST['attribute_selector']);
            update_post_meta($post_id, 'selected_attribute', $selected_attribute);
        }
        if (isset($_POST['subscription_product_id'])) {
            $subscription_product_id = intval($_POST['subscription_product_id']);
            update_post_meta($post_id, 'subscription_product_id', $subscription_product_id);
        }
    }

    /**
     * Adds a "Send Subscriptions" checkbox to the product edit page.
     * The checkbox is displayed only if the product belongs to a subscription category.
     */
    public function add_send_subscription_checkbox()
    {
        global $post;
        $selected_category_id = Magazine_Subscription_Helpers::get_subscribe_category_id();
        $products_from_cat = Magazine_Subscription_Helpers::get_category_products_meta($selected_category_id);

        $is_in_subscribed_category = false;
        foreach ($products_from_cat as $category_id) {
            if (Magazine_Subscription_Helpers::products_in_subscribed_category($post->ID, $category_id)) {
                $is_in_subscribed_category = true;
                break;
            }
        }

        if ($is_in_subscribed_category) {
            add_meta_box(
                'send_subscription_meta_box',
                __('Send Subscriptions', 'magazine-subscription'),
                array($this, 'display_send_subscription_checkbox'),
                'product',
                'side',
                'default'
            );
        }
    }

    /**
     * Displays the "Send Subscriptions" checkbox in the product edit page sidebar.
     * 
     * @param WP_Post $post The current post object (product).
     */
    function display_send_subscription_checkbox($post)
    {
        echo '<label for="send_subscriptions">';
        echo '<input type="checkbox" id="send_subscriptions" name="send_subscriptions" value="1" />';
        echo __('Send Subscriptions Now', 'magazine-subscription');
        echo '</label>';
    }
}
