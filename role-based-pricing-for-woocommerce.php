<?php
/**
 * Plugin Name:       Role Based Pricing for WooCommerce
 * Plugin URI:        https://woocopilot.com/plugins/role-based-pricing-for-woocommerce/
 * Description:       The "Role Based Pricing for WooCommerce" plugin customizes product prices by user role, boosting sales and loyalty with tailored pricing for wholesalers, VIPs, and more.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.2
 * Author:            WooCopilot
 * Author URI:        https://woocopilot.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       role-based-price
 * Domain Path:       /languages
 */

/**
Role Based Price for WooCommerce is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Role Based Price for WooCommerce is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Role Based Price for WooCommerce. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

defined( 'ABSPATH' ) || exit;

// Including classes.
require_once __DIR__ . '/includes/class-role-based-pricing-for-woocommerce.php';
require_once __DIR__ . '/includes/class-admin.php';

/**
 * Initializing plugin.
 *
 * @since 1.0.0
 * @return object Plugin object.
 */
function Role_Based_Price_For_Woocommerce() {
    return new Role_Based_Price_For_Woocommerce( __FILE__, '1.0.0' );
}
Role_Based_Price_For_Woocommerce();
