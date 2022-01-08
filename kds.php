<?php

// Hook into KDS and add actions
function dgit_wckds_actions_star_cloud_print_reprint($actions) {
    if (!function_exists('star_cloudprnt_setup_order_handler')) {
        return $actions;
    }
    if (get_option('star-cloudprnt-select') !== 'enable') {
        return $actions;
    }

    $actions[] = [
        'label' => 'Print Receipt',
        'order_required' => true,
        'action' => 'dgit_wckds_actions_star_cloud_reprint_handler',
        'action_position' => 'right'
    ];

    return $actions;
}
add_filter('dgit_wckds_actions', 'dgit_wckds_actions_star_cloud_print_reprint', 20);

function dgit_wckds_actions_star_cloud_print_automatic($actions) {
    if (!function_exists('star_cloudprnt_setup_order_handler')) {
        return $actions;
    }
    if (get_option('star-cloudprnt-select') !== 'enable') {
        return $actions;
    }

    $actions[] = [
        'label' => 'Automatic Receipts',
        'state' => (get_option('star-cloudprnt-trigger') == 'status_processing'),
        'order_required' => false,
        'action' => 'dgit_wckds_actions_star_cloud_print_handler',
        'action_position' => 'left'
    ];

    return $actions;
}
add_filter('dgit_wckds_actions', 'dgit_wckds_actions_star_cloud_print_automatic', 10);

function dgit_wckds_actions_star_cloud_print_handler() {

    $data = json_decode(file_get_contents("php://input"), true);

    if (!wp_verify_nonce($data['nonce'], 'kds')) {
        die();
    }

    if (!function_exists('star_cloudprnt_setup_order_handler')) {
        return;
    }
    $state = get_option('star-cloudprnt-trigger');
    if ($state == 'status_processing') {
        update_option('star-cloudprnt-trigger', 'none');
    } elseif ($state == 'none') {
        update_option('star-cloudprnt-trigger', 'status_processing');
    }
    die();
}
add_action('wp_ajax_dgit_wckds_actions_star_cloud_print_handler', 'dgit_wckds_actions_star_cloud_print_handler');

function dgit_wckds_actions_star_cloud_reprint_handler() {

    $data = json_decode(file_get_contents("php://input"), true);

    if (!wp_verify_nonce($data['nonce'], 'kds')) {
        die();
    }

    if (!function_exists('star_cloudprnt_setup_order_handler')) {
        return;
    }
    $data = json_decode(file_get_contents("php://input"), true);
    $order = $data['order'];
    star_cloudprnt_trigger_print($order);
    die();
}
add_action('wp_ajax_dgit_wckds_actions_star_cloud_reprint_handler', 'dgit_wckds_actions_star_cloud_reprint_handler');
