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

    $receipt_menu = new KDSParentMenuItem('star_cloudprnt_receipt_printing_menu', 'Receipt Printing', 'Receipt Printing', 'star_cloudprnt_receipt_printing_submenu', [$automatic]);


    $items[] = $receipt_menu;


    return $items;
}
add_filter('dgit_wkds_menu', 'star_cloudprnt_add_actions_to_main_menu', 30, 2);

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
