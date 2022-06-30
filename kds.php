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
        'state' => (get_option('star-cloudprnt-trigger') == 'thankyou'),
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
    if ($state == 'thankyou') {
        update_option('star-cloudprnt-trigger', 'none');
    } elseif ($state == 'none') {
        update_option('star-cloudprnt-trigger', 'thankyou');
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
    $order = $data['order'];
    star_cloudprnt_trigger_print($order);
    die();
}
add_action('wp_ajax_dgit_wckds_actions_star_cloud_reprint_handler', 'dgit_wckds_actions_star_cloud_reprint_handler');


// NEW KDS ACTIONS

function star_cloudprnt_toggle_automatic_receipt_printing() {
    if (!wp_verify_nonce($_POST['nonce'], 'dgit-wkds')) {
        echo json_encode(['success' => false, 'error' => 'Invalid nonce']);
        die();
    }

    if (!function_exists('star_cloudprnt_setup_order_handler')) {
        echo json_encode(['success' => false, 'Missing order handler']);
        die();
    }

    $state = get_option('star-cloudprnt-trigger');
    if ($state == 'thankyou') {
        update_option('star-cloudprnt-trigger', 'none');
    } elseif ($state == 'none') {
        update_option('star-cloudprnt-trigger', 'thankyou');
    }

    echo json_encode(['success' => true, 'state' => $state == 'none']);
    die();
}
add_action('wp_ajax_star_cloudprnt_toggle_automatic_receipt_printing', 'star_cloudprnt_toggle_automatic_receipt_printing');

function star_cloudprnt_add_actions_to_main_menu($items, $menu_context) {
    if (!defined('DGIT_WKDS_FILE')) {
        return $items;
    }

    if ($menu_context !== 'main') {
        return $items;
    }

    $automatic = new KDSCheckboxMenuItem('star_cloudprnt_automatic_receipts', 'Automatic Receipts', 'automatic_receipts', get_option('star-cloudprnt-trigger') == 'thankyou');
    $automatic->setCallback('star_cloudprnt_toggle_automatic_receipt_printing');

    $eod_report = new KDSButtonMenuItem('star_cloudprnt_eod_report', 'Print End of Day Report', __('Are you sure you want to print the end of day report?', DGIT_WKDS_TEXT_DOMAIN));
    $eod_report->setCallback('star_cloudprnt_kds_print_end_of_day_report');

    $clear_queue = new KDSButtonMenuItem('star_cloudprnt_clear_queue', 'Clear Print Queue', __('Are you sure you want to clear the queue?', DGIT_WKDS_TEXT_DOMAIN));
    $clear_queue->setCallback('star_cloudprnt_kds_clear_queue');

    $receipt_menu = new KDSParentMenuItem('star_cloudprnt_receipt_printing_menu', 'Receipt Printing', 'Receipt Printing', 'star_cloudprnt_receipt_printing_submenu', [$automatic, $eod_report, $clear_queue]);


    $items[] = $receipt_menu;


    return $items;
}
add_filter('dgit_wkds_menu', 'star_cloudprnt_add_actions_to_main_menu', 30, 2);

function star_cloudprnt_kds_print_end_of_day_report() {
    if (!wp_verify_nonce($_POST['nonce'], 'dgit-wkds')) {
        echo json_encode(['success' => false, 'error' => 'Invalid nonce']);
        die();
    }

    if (!function_exists('star_cloudprnt_setup_order_handler')) {
        echo json_encode(['success' => false, 'Missing order handler']);
        die();
    }

    star_cloudprnt_print_end_of_day_report();

    echo json_encode(['success' => true]);
    die();
}
add_action('wp_ajax_star_cloudprnt_kds_print_end_of_day_report', 'star_cloudprnt_kds_print_end_of_day_report');

function star_cloudprnt_kds_clear_queue() {
    if (!wp_verify_nonce($_POST['nonce'], 'dgit-wkds')) {
        echo json_encode(['success' => false, 'error' => 'Invalid nonce']);
        die();
    }

    if (!function_exists('star_cloudprnt_setup_order_handler')) {
        echo json_encode(['success' => false, 'Missing order handler']);
        die();
    }

    $printer_mac = get_option('star-cloudprnt-printer-select');
    star_cloudprnt_queue_clear_list($printer_mac);

    echo json_encode(['success' => true]);
    die();
}
add_action('wp_ajax_star_cloudprnt_kds_clear_queue', 'star_cloudprnt_kds_clear_queue');

function star_cloudprnt_add_actions_to_floating_menu($items, $menu_context) {
    if (!defined('DGIT_WKDS_FILE')) {
        return $items;
    }

    if ($menu_context !== 'floating') {
        return $items;
    }

    $reprint = new KDSButtonMenuItem('star_cloudprnt_reprint_receipt', 'Reprint Receipt');
    $reprint->setCallback('star_cloudprnt_print_kds_receipt');


    $items[] = $reprint;


    return $items;
}
add_filter('dgit_wkds_menu', 'star_cloudprnt_add_actions_to_floating_menu', 30, 2);


function star_cloudprnt_print_kds_receipt() {

    if (!wp_verify_nonce($_POST['nonce'], 'dgit-wkds')) {
        echo json_encode(['success' => false, 'error' => 'Invalid nonce']);
        die();
    }

    if (!function_exists('star_cloudprnt_setup_order_handler')) {
        echo json_encode(['success' => false, 'Missing order handler']);
        die();
    }

    $order = $_POST['order'];

    if (!$order) {
        echo json_encode(['success' => false, 'Missing order number']);
        die();
    }

    star_cloudprnt_trigger_print($order);

    echo json_encode(['success' => true]);
    die();
}
add_action('wp_ajax_star_cloudprnt_print_kds_receipt', 'star_cloudprnt_print_kds_receipt');
