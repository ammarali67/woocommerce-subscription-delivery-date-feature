<?php
/*
Plugin Name: WooCommerce Subscriptions Delivery Date Feature
Author: Ammar Ali
Description: Add a new field called "Delivery Date" in the product editor for simple subscription products and each variation of variable subscription products, and allows customers to select a delivery date on the cart page.
Version: 1.1
Requires Plugins: woocommerce, woocommerce-subscriptions
Text Domain: woocommerce-subscription-delivery-date-feature
*/

// Add delivery date field to simple subscription products
add_action('woocommerce_product_options_general_product_data', 'add_delivery_date_field');
function add_delivery_date_field() {
    ?>
        <script type="text/javascript">
            // Logic to show Delivery date field only when 'subscription' or 'variable-subscription' is selected
            jQuery(document).ready(function($) {
                // Initially hide the custom field
                $('#woocommerce-product-data #show-sub-vars').hide();

                // Show/hide the custom field based on product type selection
                $('#woocommerce-product-data select#product-type').change(function() {
                    var productType = $(this).val();
                    if (productType === 'subscription' || productType === 'variable-subscription') {
                        $('#show-sub-vars').show();
                    } else {
                        $('#show-sub-vars').hide();
                    }
                });

                // Trigger change event on page load
                $('select#product-type').change();
            });
        </script>
    <?php
        echo '<div class="options_group" id="show-sub-vars">';
        
        // Delivery Frequency
        woocommerce_wp_select(array(
            'id'      => '_delivery_date_period',
            'label'   => __('Delivery Frequency', 'woocommerce'),
            'options' => array(
                'weekly'    => __('Weekly', 'woocommerce'),
                'bi-weekly' => __('Every 2 Weeks', 'woocommerce'),
                'monthly'   => __('Monthly', 'woocommerce'),
            ),
        ));
        
        // Delivery Day
        woocommerce_wp_text_input(array(
            'id'          => '_delivery_date_day',
            'label'       => __('Delivery Day', 'woocommerce'),
            'description' => __('Enter the day of the delivery within the selected frequency. For example, 3 for the 3rd day.', 'woocommerce'),
            'type'        => 'number',
            'custom_attributes' => array(
                'step' => '1',
                'min'  => '1',
            ),
        ));
        
        echo '</div>';
}

// Save the custom fields
add_action('woocommerce_process_product_meta', 'save_delivery_date_field');
function save_delivery_date_field($post_id) {
    $product = wc_get_product($post_id);

        if (isset($_POST['_delivery_date_period'])) {
            $product->update_meta_data('_delivery_date_period', sanitize_text_field($_POST['_delivery_date_period']));
        }
        if (isset($_POST['_delivery_date_day'])) {
            $product->update_meta_data('_delivery_date_day', sanitize_text_field($_POST['_delivery_date_day']));
        }
        $product->save();

}

// Add delivery date field to variable subscription products
add_action('woocommerce_product_after_variable_attributes', 'add_delivery_date_field_to_variations', 10, 3);
function add_delivery_date_field_to_variations($loop, $variation_data, $variation) {
    echo '<div class="options_group form-row form-row-full">';
    
    // Delivery Frequency
    woocommerce_wp_select(array(
        'id'          => '_delivery_date_period[' . $loop . ']',
        'label'       => __('Delivery Frequency', 'woocommerce'),
        'wrapper_class' => 'form-row form-row-full',
        'options'     => array(
            'weekly'    => __('Weekly', 'woocommerce'),
            'bi-weekly' => __('Every 2 Weeks', 'woocommerce'),
            'monthly'   => __('Monthly', 'woocommerce'),
        ),
        'value'       => get_post_meta($variation->ID, '_delivery_date_period', true),
    ));
    
    // Delivery Day
    woocommerce_wp_text_input(array(
        'id'          => '_delivery_date_day[' . $loop . ']',
        'label'       => __('Delivery Day', 'woocommerce'),
        'description' => __('Enter the day of the delivery within the selected frequency. For example, 3 for the 3rd day.', 'woocommerce'),
        'type'        => 'number',
        'wrapper_class' => 'form-row form-row-full',
        'custom_attributes' => array(
            'step' => '1',
            'min'  => '1',
        ),
        'value'       => get_post_meta($variation->ID, '_delivery_date_day', true),
    ));
    
    echo '</div>';
}

// Save delivery date fields for variable subscription products
add_action('woocommerce_save_product_variation', 'save_delivery_date_field_for_variations', 10, 2);
function save_delivery_date_field_for_variations($variation_id, $i) {
    if (isset($_POST['_delivery_date_period'][$i])) {
        $period = sanitize_text_field($_POST['_delivery_date_period'][$i]);
        update_post_meta($variation_id, '_delivery_date_period', $period);
    }
    if (isset($_POST['_delivery_date_day'][$i])) {
        $day = sanitize_text_field($_POST['_delivery_date_day'][$i]);
        update_post_meta($variation_id, '_delivery_date_day', $day);
    }
}

// Add variation meta to AJAX response
add_filter('woocommerce_available_variation', 'add_custom_variation_fields', 10, 3);
function add_custom_variation_fields($variation_data, $product, $variation) {
    $variation_data['_delivery_date_period'] = get_post_meta($variation->get_id(), '_delivery_date_period', true);
    $variation_data['_delivery_date_day'] = get_post_meta($variation->get_id(), '_delivery_date_day', true);
    return $variation_data;
}

// Display recurring dates on the product page
add_action('woocommerce_before_add_to_cart_button', 'display_recurring_dates', 20);
function display_recurring_dates() {
    global $product;
    
    if ($product->is_type('subscription')) {
        $delivery_date_period = $product->get_meta('_delivery_date_period');
        $delivery_date_day = $product->get_meta('_delivery_date_day');

        $dates = calculate_recurring_dates($delivery_date_period, $delivery_date_day);

        echo '<div class="delivery-date-options">';
        echo '<label for="delivery_date">' . __('Select Delivery Date: ', 'woocommerce') . '</label>';
        echo '<select name="delivery_date" id="delivery_date">';
            echo '<option value="">- Please Select -</option>';
        foreach ($dates as $date) {
            echo '<option value="' . $date . '">' . $date . '</option>';
        }
        echo '</select>';
        echo '</div>';
    } elseif ($product->is_type('variable-subscription')) {
        echo '<div class="delivery-date-options" style="display: none;">';
        echo '<label for="delivery_date">' . __('Select Delivery Date: ', 'woocommerce') . '</label>';
        echo '<select name="delivery_date" id="delivery_date">';
        echo '</select>';
        echo '</div>';
    }
}

function calculate_recurring_dates($period, $day) {
    $dates = [];
    $current_date = strtotime('today');
    
    for ($i = 0; $i < 3; $i++) {
        switch ($period) {
            case 'weekly':
                $next_date = strtotime("+$i week", strtotime("next Sunday +$day days"));
                break;
            case 'bi-weekly':
                $next_date = strtotime("+$i fortnight", strtotime("next Sunday +$day days"));
                break;
            case 'monthly':
                $next_date = strtotime("+$i month", strtotime(date('Y-m', $current_date) . "-$day"));
                break;
        }
        $dates[] = date('Y-m-d', $next_date);
    }
    
    return $dates;
}

// Save delivery date metadata to cart item
add_filter('woocommerce_add_cart_item_data', 'save_delivery_date_to_cart_item', 10, 3);
function save_delivery_date_to_cart_item($cart_item_data, $product_id, $variation_id) {

    // Check if delivery date is set in POST data and sanitize it
    if (isset($_POST['delivery_date'])) {
        $cart_item_data['delivery_date'] = sanitize_text_field($_POST['delivery_date']);
    
        $product = wc_get_product($product_id);
        if ($product) {
            $cart_item_data['delivery_date'] = sanitize_text_field($_POST['delivery_date']);
            if ($product->is_type('subscription')) {
                $cart_item_data['_delivery_date_period'] = $product->get_meta('_delivery_date_period', true);
                $cart_item_data['_delivery_date_day'] = $product->get_meta('_delivery_date_day', true);
            }
        }

        // Handle variation products separately
        if ($variation_id) {
            $variation_product = wc_get_product($variation_id);
            if ($variation_product && $product->is_type('variable-subscription')) {
                $cart_item_data['_delivery_date_period'] = $variation_product->get_meta('_delivery_date_period', true);
                $cart_item_data['_delivery_date_day'] = $variation_product->get_meta('_delivery_date_day', true);
            }
        }
    }
    return $cart_item_data;
}

// Display delivery date and metadata in the cart
add_filter('woocommerce_get_item_data', 'display_delivery_date_in_cart', 10, 2);
function display_delivery_date_in_cart($item_data, $cart_item) {
    if (isset($cart_item['delivery_date']) && !empty($cart_item['delivery_date'])) {
        $item_data[] = array(
            'key'   => __('Delivery Date', 'woocommerce'),
            'value' => date("d-F-Y", strtotime(wc_clean($cart_item['delivery_date']))),
        );
    }
    if (isset($cart_item['_delivery_date_period']) && isset($cart_item['_delivery_date_day'])) {
        $delivery_date_period = isset($cart_item['_delivery_date_period']) ? $cart_item['_delivery_date_period'] : '';
        $delivery_date_day = isset($cart_item['_delivery_date_day']) ? $cart_item['_delivery_date_day'] : '';
        $dates = calculate_recurring_dates($delivery_date_period, $delivery_date_day);
        $dropdown = '';
       foreach ($dates as $date) {
            $isSelected = (isset($cart_item['delivery_date']) && $cart_item['delivery_date'] == $date) ? 1 : 0;
            $dropdown .= esc_html($date).'/'.$isSelected."|";
        }
        $item_data[] = array(
            'key'   => __('Select Delivery Date', 'woocommerce'),
            'value' => $dropdown,
        );
        $item_data[] = array(
            'key'   => __('item_id', 'woocommerce'),
            'value' => $cart_item['product_id'],
        );
    }
    return $item_data;
}

// Update delivery date in the cart
add_action('woocommerce_before_calculate_totals', 'update_delivery_date_in_cart');
function update_delivery_date_in_cart($cart) {
    // Check if we are in the admin area and not doing AJAX
    if (is_admin() && !defined('DOING_AJAX')) return;

    // Iterate through cart items
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        // Check if there is a delivery date submitted for the item
        if (isset($_POST['cart_delivery_date'][$cart_item_key])) {
            // Sanitize and update the delivery date
            $delivery_date = sanitize_text_field($_POST['cart_delivery_date'][$cart_item_key]);
            $cart_item['delivery_date'] = $delivery_date;
            // Ensure to update cart contents with the modified item
            $cart->cart_contents[$cart_item_key] = $cart_item;
        }
    }
}

// Save Delivery Date to Order Items
add_action('woocommerce_checkout_create_order_line_item', 'save_delivery_date_to_order_items', 10, 4);
function save_delivery_date_to_order_items($item, $cart_item_key, $values, $order) {
    if (isset($values['delivery_date'])) {
        $item->add_meta_data(__('Delivery Date', 'woocommerce'), $values['delivery_date'], true);
    }
}

// Display Delivery Date in Order Details
add_filter('woocommerce_display_item_meta', 'display_delivery_date_in_order', 10, 3);
function display_delivery_date_in_order($html, $item, $args) {
    if ($item->get_meta('Delivery Date')) {
        $html .= '<p><strong>' . __('Delivery Date', 'woocommerce') . ':</strong> ' . $item->get_meta('Delivery Date') . '</p>';
    }
    return $html;
}

// Display delivery date in the checkout page
add_filter('woocommerce_checkout_cart_item_quantity', 'display_delivery_date_in_checkout', 10, 2);
function display_delivery_date_in_checkout($quantity, $cart_item) {
    if (isset($cart_item['delivery_date'])) {
        $quantity .= '<div class="delivery-date-options">';
        $quantity .= '<label>' . __('Delivery Date', 'woocommerce') . '</label>';
        $quantity .= '<span>' . esc_html($cart_item['delivery_date']) . '</span>';
        $quantity .= '</div>';
    }
    return $quantity;
}

// Enqueue custom scripts
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');
function enqueue_custom_scripts() {
    wp_enqueue_script('variation-change-script', plugin_dir_url(__FILE__) . 'variation-change-script.js', array('jquery'), null, true);
    wp_enqueue_style('subscription-custom-styles', plugin_dir_url(__FILE__) . 'subscription-custom-styles.css');
}

add_action('wp_ajax_update_delivery_date', 'update_delivery_date_callback');
add_action('wp_ajax_nopriv_update_delivery_date', 'update_delivery_date_callback');

//To update upon delivery date selection on cart page
function update_delivery_date_callback() {
    // Check if parameters are set
    if (!isset($_POST['product_id']) || !isset($_POST['delivery_date'])) {
        wp_send_json_error('Missing parameters');
        return;
    }

    $product_id = intval($_POST['product_id']);
    $delivery_date = sanitize_text_field($_POST['delivery_date']);

    // Ensure WooCommerce cart is loaded
    if (!WC()->cart) {
        wp_send_json_error('Cart not available');
        return;
    }

    // Update the custom data in cart
    $updated = false;
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if ($cart_item['product_id'] == $product_id) {
            WC()->cart->cart_contents[$cart_item_key]['delivery_date'] = $delivery_date;
            $updated = true;
            break;
        }
    }

    if ($updated) {
        WC()->cart->calculate_totals();
        wp_send_json_success('Delivery date updated');
    } else {
        wp_send_json_error('Product not found in cart');
    }
}