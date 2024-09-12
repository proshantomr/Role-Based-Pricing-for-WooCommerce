<?php

if (!defined('ABSPATH')) {
    exit();
}

class Role_Based_Price_For_Woocommerce
{

    /**
     * File.
     *
     * @var string $file File path.
     *
     * @since 1.0.0
     */
    public string $file;

    /**
     * Plugin Version.
     *
     * @var string $version Plugin version.
     *
     * @since 1.0.0
     */
    public string $version;

    /**
     * Constructor.
     *
     * @param string $file Plugin file path.
     * @param string $version Plugin version.
     * @since 1.0.0
     */
    public function __construct($file, $version = '1.0.0')
    {
        $this->file = $file;
        $this->version = $version;
        $this->define_constants();
        $this->init_hooks();
        register_activation_hook($this->file, array($this, 'activation_hook'));
        register_deactivation_hook($this->file, array($this, 'deactivation_hook'));

    }

    /**
     * Define constants.
     *
     * @return void
     * @since 1.0.0
     */
    public function define_constants()
    {
        define('RBP_VERSION', $this->version);
        define('RBP_PLUGIN_DIR', plugin_dir_path($this->file));
        define('RBP_PLUGIN_URL', plugin_dir_url($this->file));
        define('RBP_PLUGIN_BASENAME', plugin_basename($this->file));
    }

    /**
     * Activation hook.
     *
     * @return void
     * @since 1.0.0
     */
    public function activation_hook()
    {
        // Activation logic here.
    }

    /**
     * Deactivation hook.
     *
     * @return void
     * @since 1.0.0
     */
    public function deactivation_hook()
    {
        // Deactivation logic here.
    }

    /**
     * Initialize hooks.
     *
     * @return void
     * @since 1.0.0
     */
    public function init_hooks()
    {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        if (is_admin()) {
            new RoleBased_Price_For_WooCommerce_Admin();
        }
        add_filter('woocommerce_get_price_html', [$this, 'custom_price_display'], 100, 2);
        add_action('woocommerce_before_calculate_totals', [$this, 'adjust_cart_item_price'], 20);


    }

    public function load_textdomain()
    {
        load_plugin_textdomain('role-based-price', false, dirname(plugin_basename($this->file)) . '/languages');
    }

    public function get_role_based_pricing($product_id)
    {
        $user = wp_get_current_user();
        $roles = $user->roles;

        foreach ($roles as $role) {
            $pricing_data = get_option("rbpfw_{$role}_pricing");
            if ($pricing_data) {
                if ($pricing_data['apply_to'] === 'product' && in_array($product_id, $pricing_data['products'])) {
                    return $pricing_data;
                }

                if ($pricing_data['apply_to'] === 'category') {
                    $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
                    if (array_intersect($product_categories, $pricing_data['categories'])) {
                        return $pricing_data;
                    }
                }
            }
        }

        return false;
    }

    public function apply_role_based_discount($product)
    {
        // Ensure $product is a WC_Product object.
        if (!is_a($product, 'WC_Product')) {
            return false;
        }
        // Fetch the role-based pricing data.
        $pricing_data = $this->get_role_based_pricing($product->get_id());
        $regular_price = floatval($product->get_regular_price());
        $sale_price = floatval($product->get_sale_price());
        $role_based_discount = 0;

        if ($pricing_data) {
            $discount = intval($pricing_data['discount']);
            // Apply the discount to the regular price.
            $discount_price = floatval($regular_price - ($regular_price * ($discount / 100)));
            if ($sale_price && $sale_price < $discount_price) {
                $role_based_discount = $sale_price;
            } else {
                $role_based_discount = $discount_price;
            }

        } elseif ($sale_price) {
            $role_based_discount = $sale_price;
        } else {
            $role_based_discount = $regular_price;
        }

        return $role_based_discount;
    }

    public function custom_price_display($price_html, $product)
    {
        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();
        $role_based_discounted_price = $this->apply_role_based_discount($product);
        // Display logic to ensure the smallest price is shown.
        if ($sale_price && $sale_price < $role_based_discounted_price) {
            // Show the sale price if it's lower than the discounted price.
            $price_html = '<del>' . wc_price($regular_price) . '</del> ';
            $price_html .= '<ins>' . wc_price($sale_price) . '</ins>';
        } elseif ($role_based_discounted_price < $regular_price) {
            // Show the discounted price if it's lower than the regular price (and sale price doesn't exist or is higher).
            $price_html = '<del>' . wc_price($regular_price) . '</del> ';
            $price_html .= '<ins>' . wc_price($role_based_discounted_price) . '</ins>';
        } else {
            // Default to showing the regular price.
            $price_html = '<ins>' . wc_price($regular_price) . '</ins>';
        }

        return $price_html;
    }

    public function adjust_cart_item_price($cart)
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $discounted_price = $this->apply_role_based_discount($product);
            // Set the price in the cart.
            $cart_item['data']->set_price($discounted_price);
        }
    }


}
