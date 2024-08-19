<?php
/*
Plugin Name: WooCommerce Subscription List
Plugin URI: http://example.com
Description: A custom plugin to display WooCommerce subscription list in the admin area.
Version: 1.1
Author: Your Name
Author URI: http://example.com
License: GPL2
*/

// Buffer output to prevent unwanted output before headers
ob_start();

// Hook to add admin menu
add_action('admin_menu', 'woocommerce_subscription_list_menu');

// Function to add admin menu item
function woocommerce_subscription_list_menu() {
    add_menu_page(
        'WooCommerce Subscriptions', // Page title
        'Subscriptions List',         // Menu title
        'manage_options',             // Capability
        'woocommerce-subscription-list', // Menu slug
        'display_woocommerce_subscription_list', // Function to display page content
        'dashicons-list-view',        // Icon
        6                             // Position
    );
}

// Function to display the WooCommerce subscription list
function display_woocommerce_subscription_list() {
    // Check if WooCommerce Subscriptions is active
    if (!class_exists('WC_Subscriptions')) {
        echo '<div class="notice notice-error"><p>WooCommerce Subscriptions plugin is not activated.</p></div>';
        return;
    }

    // Include WooCommerce Subscriptions functions
    if (!function_exists('wcs_get_subscription')) {
        include_once(WC_Subscriptions::$path . '/includes/wcs-functions.php');
    }

    // Display the download button and form
    echo '<div class="wrap">';
    echo '<h1>WooCommerce Subscription List</h1>';
    echo '<form method="post" action="">';
    echo '<input type="submit" name="download_subscriptions_csv" class="button button-primary" value="Download CSV" />';
    echo '</form>';

    // Handle the CSV download
    if (isset($_POST['download_subscriptions_csv'])) {
        // Fetch subscriptions
        $args = array(
            'post_type' => 'shop_subscription',
            'post_status' => 'any',
            'posts_per_page' => -1
        );
        $subscriptions = get_posts($args);

        // Prepare CSV headers
        $csv_headers = array(
            'ID',
            'Status',
            'Customer',
            'Email',
            'Amount',
            'Start Date',
            'Next Payment Date'
        );

        ob_end_clean();
        // Open output stream
        $output = fopen('php://output', 'w');
        // Output CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="woocommerce_subscriptions.csv"');

        // Write CSV headers
        fputcsv($output, $csv_headers);

        // Write subscription data
        foreach ($subscriptions as $subscription) {
            $subscription_obj = wcs_get_subscription($subscription->ID);
            if (!$subscription_obj) {
                continue; // Skip if subscription object is not valid
            }
            $customer = $subscription_obj->get_billing_first_name() . ' ' . $subscription_obj->get_billing_last_name();
            $email = $subscription_obj->get_billing_email();
            $amount = $subscription_obj->get_total();
            $csv_data = array(
                $subscription->ID,
                wc_get_order_status_name($subscription->post_status),
                $customer,
                $email,
                $amount,
                $subscription_obj->get_date_to_display('start_date'),
                $subscription_obj->get_date_to_display('next_payment_date')
            );
            fputcsv($output, $csv_data);
        }

        // Close the output stream
        fclose($output);

        // Exit to prevent further execution
        exit;
    }

    // Fetch subscriptions for display in table
    $args = array(
        'post_type' => 'shop_subscription',
        'post_status' => 'any',
        'posts_per_page' => -1
    );
    $subscriptions = get_posts($args);

    // Display the subscription list in a table
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Status</th><th>Customer</th><th>Email</th><th>Amount</th><th>Start Date</th><th>Next Payment Date</th></tr></thead>';
    echo '<tbody>';
    foreach ($subscriptions as $subscription) {
        $subscription_obj = wcs_get_subscription($subscription->ID);
        if (!$subscription_obj) {
            continue; // Skip if subscription object is not valid
        }
        $customer = $subscription_obj->get_billing_first_name() . ' ' . $subscription_obj->get_billing_last_name();
        $email = $subscription_obj->get_billing_email();
        $amount = $subscription_obj->get_total();
        echo '<tr>';
        echo '<td>' . $subscription->ID . '</td>';
        echo '<td>' . wc_get_order_status_name($subscription->post_status) . '</td>';
        echo '<td>' . $customer . '</td>';
        echo '<td>' . $email . '</td>';
        echo '<td>' . wc_price($amount) . '</td>';
        echo '<td>' . $subscription_obj->get_date_to_display('start_date') . '</td>';
        echo '<td>' . $subscription_obj->get_date_to_display('next_payment_date') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}

// Flush buffer to prevent any output before headers
ob_end_flush();
