<?php

defined('ABSPATH') || exit;

/**
 * Class Admin.
 *
 * @since 1.0.0
 */
class RoleBased_Price_For_WooCommerce_Admin {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'handle_delete_role_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'rbpfw_enqueue_select2_assets'));
        add_action('admin_post_rbpfw_save_role_based_pricing', array($this, 'rbpfw_save_role_based_pricing_form'));
        add_action('admin_post_rbpfw_delete_role_based_pricing', array($this, 'rbpfw_delete_role_based_pricing'));

    }
    /**
     * Enqueue admin scripts and styles.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_style('rbpfw_admin-styles', RBP_PLUGIN_URL . 'assets/css/admin.css',null,RBP_VERSION);
        wp_enqueue_script( 'rbpfw_admin-scripts', RBP_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), RBP_VERSION, true );
    }

    function rbpfw_enqueue_select2_assets($screen) {
        if ( 'toplevel_page_role-based-price'===$screen) {
            wp_enqueue_style('rbpfw_select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css',null,RBP_VERSION);
            wp_enqueue_script('rbpfw_select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js', array('jquery'), RBP_VERSION, true);
        }
    }


    /**
    * Add admin menu and submenus.
    *
    * @since 1.0.0
    */
    public function admin_menu() {
        add_menu_page(
            'Role Based Price',
            'Role Based Price',
            'manage_options',
            'role-based-price',
            array($this, 'admin_page'),
            'dashicons-money-alt',
            '58'

        );

        add_submenu_page(
            'role-based-price',
            'Add Role',
            'Add Role',
            'manage_options',
            'add-new-role',
            array($this, 'rbpfw_add_new_role_page')
        );
    }

    /**
     * Display the main admin page.
     *
     * @since 1.0.0
     */
    public function admin_page() {
        $roles = wp_roles()->roles;
        $saved_discounts = [];
        foreach ($roles as $role_id => $role_data) {
            $saved_discounts[$role_id] = get_option("rbpfw_{$role_id}_pricing");
        }

        // Check if editing.
        $edit_role = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
        $edit_discount = $edit_role ? get_option("rbpfw_{$edit_role}_pricing") : false;
        ?>

        <div class="wrap">
            <h1><?php esc_html_e('Role-Based Pricing', 'role-based-price'); ?></h1>

            <button id="open-form-button" class="button button-primary"><?php echo $edit_role ? esc_html__('Edit Role-Based Pricing', 'role-based-price') :  esc_html__('Add Role-Based Pricing', 'role-based-price');?></button>

            <div id="role-based-pricing-form">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php?action=rbpfw_save_role_based_pricing')); ?>">
                    <?php wp_nonce_field('rbpfw_save_role_based_pricing', 'rbpfw_role_based_pricing_nonce'); ?>

                    <input type="hidden" name="rbpfw_action" value="<?php echo esc_attr($edit_role ? 'edit' : 'add'); ?>">
                    <?php if ($edit_role) : ?>
                        <input type="hidden" name="rbpfw_role" value="<?php echo esc_attr($edit_role); ?>">
                    <?php endif; ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="rbpfw_role"><?php esc_html_e('Select User Role', 'role-based-price'); ?></label></th>
                            <td>
                                <select name="rbpfw_role" id="rbpfw_role" required <?php echo $edit_role ? 'disabled' : ''; ?>>
                                    <option value=""><?php esc_html_e('Select a role', 'role-based-price'); ?></option>
                                    <?php foreach ($roles as $role_id => $role_data) : ?>
                                        <option value="<?php echo esc_attr($role_id); ?>" <?php selected($edit_role, $role_id); ?>>
                                            <?php echo esc_html($role_data['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="rbpfw_discount"><?php esc_html_e('Discount Percentage', 'role-based-price'); ?></label></th>
                            <td>
                                <input name="rbpfw_discount" type="number" id="rbpfw_discount" class="small-text" value="<?php echo esc_attr($edit_discount ? $edit_discount['discount'] : ''); ?>" required /> %
                                <span class="description"><?php esc_html_e('Enter discount percentage for selected role.', 'role-based-price'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Apply Discount To', 'role-based-price'); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span><?php esc_html_e('Apply Discount To', 'role-based-price'); ?></span></legend>
                                    <label for="rbpfw_apply_to_category">
                                        <input name="rbpfw_apply_to" type="radio" id="rbpfw_apply_to_category" value="category" <?php checked($edit_discount ? $edit_discount['apply_to'] : 'category', 'category'); ?>>
                                        <?php esc_html_e('Apply to Category', 'role-based-price'); ?>
                                    </label><br>
                                    <label for="rbpfw_apply_to_product">
                                        <input name="rbpfw_apply_to" type="radio" id="rbpfw_apply_to_product" value="product" <?php checked($edit_discount ? $edit_discount['apply_to'] : '', 'product'); ?>>
                                        <?php esc_html_e('Apply to Single Product', 'role-based-price'); ?>
                                    </label><br>
                                </fieldset>
                            </td>
                        </tr>
                        <tr id="category-dropdown" style="<?php echo ($edit_discount && $edit_discount['apply_to'] === 'category') ? 'display:table-row;' : 'display:none;'; ?>">
                            <th scope="row"><label for="rbpfw_categories"><?php esc_html_e('Select Categories', 'role-based-price'); ?></label></th>
                            <td>
                                <?php
                                $product_categories = get_terms(array(
                                    'taxonomy'   => 'product_cat',
                                    'hide_empty' => false,
                                    'orderby'    => 'name',
                                    'order'      => 'ASC',
                                ));

                                if (!empty($product_categories) && !is_wp_error($product_categories)) {
                                    echo '<select name="rbpfw_categories[]" id="rbpfw_categories" class="select2" multiple="multiple">';
                                    foreach ($product_categories as $category) {
                                        echo '<option value="' . esc_attr($category->term_id) . '" ' . (in_array($category->term_id, $edit_discount['categories'] ?? []) ? 'selected' : '') . '>' . esc_html($category->name) . '</option>';
                                    }
                                    echo '</select>';
                                } else {
                                    echo '<p>' . esc_html__('No product categories available', 'role-based-price') . '</p>';
                                }
                                ?>
                                <p>You can add multiple product category if you want.</p>

                            </td>
                        </tr>
                        <tr id="product-dropdown" style="<?php echo ($edit_discount && $edit_discount['apply_to'] === 'product') ? 'display:table-row;' : 'display:none;'; ?>">
                            <th scope="row"><label for="rbpfw_products"><?php esc_html_e('Select Products', 'role-based-price'); ?></label></th>
                            <td>
                                <select name="rbpfw_products[]" id="rbpfw_products" class="select2" multiple="multiple">
                                    <?php
                                    $products = new WP_Query(array(
                                        'post_type' => 'product',
                                        'posts_per_page' => -1,
                                        'orderby' => 'title',
                                        'order' => 'ASC',
                                    ));

                                    if ($products->have_posts()) {
                                        while ($products->have_posts()) {
                                            $products->the_post();
                                            echo '<option value="' . esc_attr(get_the_ID()) . '" ' . (in_array(get_the_ID(), $edit_discount['products'] ?? []) ? 'selected' : '') . '>' . esc_html(get_the_title()) . '</option>';
                                        }
                                    } else {
                                        echo '<option value="">' . esc_html__('No products available', 'role-based-price') . '</option>';
                                    }
                                    wp_reset_postdata();
                                    ?>
                                </select>
                                <p>You can add multiple product if you want.</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button($edit_role ? esc_html__('Update Settings', 'role-based-price') : esc_html__('Save Settings', 'role-based-price')); ?>
                </form>
            </div>

            <hr>

            <h2><?php esc_html_e('Saved Role-Based Discounts', 'role-based-price'); ?></h2>
            <?php if (!empty($saved_discounts)) : ?>
                <table class="widefat fixed striped">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('Role', 'role-based-price'); ?></th>
                        <th><?php esc_html_e('Discount Percentage', 'role-based-price'); ?></th>
                        <th><?php esc_html_e('Applied To', 'role-based-price'); ?></th>
                        <th><?php esc_html_e('Selected Categories/Products', 'role-based-price'); ?></th>
                        <th><?php esc_html_e('Actions', 'role-based-price'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($saved_discounts as $role_id => $settings) : ?>
                        <?php if ($settings) : ?>
                            <tr>
                                <td><?php echo esc_html($roles[$role_id]['name']); ?></td>
                                <td><?php echo esc_html($settings['discount'] . '%'); ?></td>
                                <td><?php echo esc_html(ucfirst($settings['apply_to'])); ?></td>
                                <td>
                                    <?php
                                    if ($settings['apply_to'] === 'category') {
                                        $category_names = array_map(function($cat_id) {
                                            $term = get_term($cat_id, 'product_cat');
                                            return $term ? $term->name : '';
                                        }, $settings['categories']);
                                        echo esc_html(implode(', ', $category_names));
                                    } elseif ($settings['apply_to'] === 'product') {
                                        $product_titles = array_map(function($prod_id) {
                                            $post = get_post($prod_id);
                                            return $post ? $post->post_title : '';
                                        }, $settings['products']);
                                        echo esc_html(implode(', ', $product_titles));
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=role-based-price&role=' . urlencode($role_id))); ?>" class="button" id="edit-btn">
                                        <?php esc_html_e('Edit', 'role-based-price'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin-post.php?action=rbpfw_delete_role_based_pricing&role=' . urlencode($role_id) . '&nonce=' . wp_create_nonce('rbpfw_delete_nonce'))); ?>" class="button button-secondary" onclick="return confirm('<?php esc_html_e('Are you sure you want to delete this discount?', 'role-based-price'); ?>');">
                                        <?php esc_html_e('Delete', 'role-based-price'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No discounts found.', 'role-based-price'); ?></p>
            <?php endif; ?>
        </div>

        <?php
    }

    /**
     * Save role based pricing form data.
     *
     *
     * @since 1.0.0.
     */
    public function rbpfw_save_role_based_pricing_form() {
        if (!isset($_POST['rbpfw_role_based_pricing_nonce']) || !wp_verify_nonce($_POST['rbpfw_role_based_pricing_nonce'], 'rbpfw_save_role_based_pricing')) {
            wp_die(esc_html__('Security check failed', 'role-based-price'));
        }

        $action = isset($_POST['rbpfw_action']) ? sanitize_text_field($_POST['rbpfw_action']) : '';
        $role = isset($_POST['rbpfw_role']) ? sanitize_text_field($_POST['rbpfw_role']) : '';
        $discount = isset($_POST['rbpfw_discount']) ? floatval($_POST['rbpfw_discount']) : 0;
        $apply_to = isset($_POST['rbpfw_apply_to']) ? sanitize_text_field($_POST['rbpfw_apply_to']) : '';
        $categories = isset($_POST['rbpfw_categories']) ? array_map('intval', $_POST['rbpfw_categories']) : [];
        $products = isset($_POST['rbpfw_products']) ? array_map('intval', $_POST['rbpfw_products']) : [];

        if ($action === 'edit' && $role) {
            $existing_discount = get_option("rbpfw_{$role}_pricing");
            if ($existing_discount) {
                $existing_discount = [
                    'discount'  => $discount,
                    'apply_to'  => $apply_to,
                    'categories' => $apply_to === 'category' ? $categories : [],
                    'products'   => $apply_to === 'product' ? $products : [],
                ];
                update_option("rbpfw_{$role}_pricing", $existing_discount);
            }
        } else {
            if ($role) {
                $new_discount = [
                    'discount'  => $discount,
                    'apply_to'  => $apply_to,
                    'categories' => $apply_to === 'category' ? $categories : [],
                    'products'   => $apply_to === 'product' ? $products : [],
                ];
                add_option("rbpfw_{$role}_pricing", $new_discount);
            }
        }

        wp_redirect(admin_url('admin.php?page=role-based-price'));
        exit;
    }


    public function rbpfw_delete_role_based_pricing() {
        // Check the nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'rbpfw_delete_nonce')) {
            wp_die(esc_html__('Security check failed', 'role-based-price'));
        }

        // Check the role parameter
        if (!isset($_GET['role'])) {
            wp_die(esc_html__('Invalid role specified', 'role-based-price'));
        }

        // Sanitize and delete the role-based pricing
        $role_id = sanitize_text_field($_GET['role']);
        delete_option("rbpfw_{$role_id}_pricing");

        // Redirect back to the admin page
        wp_redirect(add_query_arg('page', 'role-based-price', admin_url('admin.php')));
        exit;
    }


    /**
     * Display the Add User page.
     *
     * @since 1.0.0
     */
    public function rbpfw_add_new_role_page() {
        global $wp_roles;
        $roles = $wp_roles->roles;
        // Handle form submission for adding a new role.
        if ( isset( $_POST['rbpfw_add_role_action'] ) && wp_verify_nonce( $_POST['rbpfw_add_role_action'], 'rbpfw_add_role_action' ) ) {
            $role_name = sanitize_text_field( $_POST['role_name'] );
            $role_display_name = sanitize_text_field( $_POST['role_display_name'] );

            if ( ! empty( $role_name ) && ! empty( $role_display_name ) ) {
                // Retrieve capabilities from the 'customer' role.
                $customer_role = get_role( 'customer' );
                $capabilities = $customer_role ? $customer_role->capabilities : array();
                // Add the new role with the same capabilities as 'customer'.
                add_role( $role_name, $role_display_name, $capabilities );
                wp_safe_redirect( admin_url( '/admin.php?page=add-new-role' ) );
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Role added successfully.', 'role-based-price' ) . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please provide both a role name and display name.', 'role-based-price' ) . '</p></div>';
            }
        }

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'User Role', 'role-based-price' ) . '</h1>';
        echo '<button href="#" id="add-new-role-btn" class="page-title-action" >' . esc_html__( 'Add New', 'role-based-price' ) . '</button>';
        echo '<hr class="wp-header-end">';

        // Add New Role Form.
        ?>
        <div id="add-role-form" >
        <h2><?php esc_html_e( 'Add New Role', 'role-based-price' ); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field( 'rbpfw_add_role_action', 'rbpfw_add_role_action' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="role_name"><?php esc_html_e( 'Role Name (ID)', 'role-based-price' ); ?></label></th>
                    <td>
                        <input name="role_name" type="text" id="role_name" class="regular-text" required />
                        <p class="description"><?php esc_html_e('You can\'t use any spaces and number. Only single word and underscores are allowed.', 'role-based-price'); ?></p>
                        <p id="role_name_error" class="description">
                            <?php esc_html_e('Invalid input: Only single words and underscores are allowed, with no spaces or numbers.', 'role-based-price'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="role_display_name"><?php esc_html_e( 'Display Name', 'role-based-price' ); ?></label></th>
                    <td><input name="role_display_name" type="text" id="role_display_name" class="regular-text" required /></td>
                </tr>
            </table>
            <?php submit_button( __( 'Add Role', 'role-based-price' ) ); ?>
        </form>
        <hr />
        </div>
        <?php

        if ( ! empty( $roles ) ) {
            ?>
            <h2><?php esc_html_e( 'Existing Roles', 'role-based-price' ); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th><?php esc_html_e( 'Role Name (ID)', 'role-based-price' ); ?></th>
                    <th><?php esc_html_e( 'Display Name', 'role-based-price' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'role-based-price' ); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ( $roles as $role_id => $role_data ) :
                    $delete_url = wp_nonce_url(
                        admin_url( 'admin.php?page=role-based-price&action=delete&role=' . $role_id ),
                        'delete_role_action_' . $role_id
                    ); ?>
                    <tr>
                        <td><?php echo esc_html( $role_id ); ?></td>
                        <td><?php echo esc_html( $role_data['name'] ); ?></td>
                        <td>
                            <a class="delete-role-link" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this role?', 'role-based-price' ) ); ?>');">
                                <?php esc_html_e( 'Delete', 'role-based-price' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        } else {
            echo '<p>' . esc_html__( 'No roles found.', 'role-based-price' ) . '</p>';
        }

        echo '</div>';
    }

    public function handle_delete_role_actions() {
        // Check if the action is to delete a role.
        if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['role'] ) && isset( $_GET['_wpnonce'] ) ) {
            $role_id = sanitize_text_field( $_GET['role'] );
            $nonce = $_GET['_wpnonce'];

            // Verify nonce.
            if ( wp_verify_nonce( $nonce, 'delete_role_action_' . $role_id ) ) {
                // Check if the role exists before attempting to delete it.
                if ( get_role( $role_id ) ) {
                    remove_role( $role_id );

                    // Add success notice and redirect.
                    add_action( 'admin_notices', function() {
                        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Role deleted successfully.', 'role-based-price' ) . '</p></div>';
                    });

                    wp_safe_redirect( admin_url( 'admin.php?page=add-new-role' ) );
                    exit;
                } else {
                    // Add error notice if the role does not exist.
                    add_action( 'admin_notices', function() {
                        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Role does not exist.', 'role-based-price' ) . '</p></div>';
                    });
                }
            } else {
                // Add error notice if nonce verification fails.
                add_action( 'admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Nonce verification failed.', 'role-based-price' ) . '</p></div>';
                });
            }
        }
    }


}

