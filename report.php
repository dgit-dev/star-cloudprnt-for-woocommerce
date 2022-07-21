<?php

function star_cloudprnt_print_end_of_day_report() {
    $selectedPrinter = star_cloudprnt_get_printer();
    $file = STAR_CLOUDPRNT_PRINTER_PENDING_SAVE_PATH . star_cloudprnt_get_os_path("/report_" . uniqid() . "_" . time() . "." . $selectedPrinter['format']);

    $printer = star_cloudprnt_command_generator($selectedPrinter, $file);

    $from = date('Y-m-d H:i:s', strtotime('today midnight'));
    $to = date('Y-m-d H:i:s', strtotime('tomorrow midnight'));

    $orders = wc_get_orders([
        'limit' => -1,
        'status' => 'wc-completed',
        'type' => 'shop_order',
        'date_query' => [
            'after' => $from,
            'before' => $to
        ]
    ]);

    $refunded_orders = wc_get_orders([
        'limit' => -1,
        'status' => 'wc-refunded',
        'type' => 'shop_order',
        'date_query' => [
            'after' => $from,
            'before' => $to
        ]
    ]);

    $order_costs = [];
    $total_order_costs = 0;
    $gateways = WC()->payment_gateways()->payment_gateways();

    foreach ($orders as $order) {
        if (!isset($order_costs[$order->get_payment_method()])) {
            $order_costs[$order->get_payment_method()] = [];
        }
        $order_costs[$order->get_payment_method()][$order->get_id()] = $order->get_total();
        $total_order_costs += $order->get_total();
    }

    do_action('star_cloudprnt_before_day_end_report_title', $printer);
    $printer->set_font_magnification(2, 2);
    $printer->add_text_line('Day End Report');
    $printer->set_font_magnification(1, 1);
    do_action('star_cloudprnt_after_day_end_report_title', $printer);

    do_action('star_cloudprnt_before_day_end_report_explanation', $printer);
    $printer->add_text_line(wordwrap('Report ran on completed orders between the following dates:', $selectedPrinter['columns'], "\n", true));
    $printer->add_new_line(1);
    do_action('star_cloudprnt_after_day_end_report_explanation', $printer);


    do_action('star_cloudprnt_before_day_end_report_dates', $printer);
    $printer->add_text_line(sprintf('From: %s', $from));
    $printer->add_text_line(sprintf('To: %s', $to));

    $printer->add_text_line(str_repeat('-', $selectedPrinter['columns']));

    do_action('star_cloudprnt_after_day_end_report_dates', $printer);

    $printer->add_text_line(sprintf('Completed Orders: %s', count($orders)));

    $printer->add_text_line(sprintf('Orders Amount Total: £%s', number_format($total_order_costs, 2)));

    foreach ($order_costs as $payment_method => $orders) {

        $method_total = 0;
        foreach ($orders as $order_id => $amount) {
            $method_total += $amount;
        }
        $printer->add_text_line(sprintf('  %s: £%s', $gateways[$payment_method]->get_title(), number_format($method_total, 2)));
    }

    $printer->add_text_line(str_repeat('-', $selectedPrinter['columns']));

    if (count($refunded_orders) > 0) {
        $printer->add_text_line(wordwrap(sprintf('%s Order(s) Refunded', count($refunded_orders)), $selectedPrinter['columns'], "\n", true));
        $printer->add_new_line(1);
        foreach ($refunded_orders as $refunded_order) {
            $printer->add_text_line(sprintf('Order %s (£%s)', $refunded_order->get_id(), number_format($refunded_order->get_total_refunded(), 2)), count($refunded_orders));
            $printer->add_text_line(sprintf('- %s', $refunded_order->get_refunds()[0]->get_reason()));
        }
    } else {
        $printer->add_text_line('No orders were refunded');
    }

    do_action('star_cloudprnt_after_refund_list', $printer);

    $printer->add_text_line(str_repeat('-', $selectedPrinter['columns']));
    $printer->add_text_line('Report printed at:');
    $printer->add_text_line(sprintf('  %s', wp_date('d/m/Y H:i:s')));

    $printer->cut();

    $printer->printjob(1);
}

function star_cloudprnt_print_end_of_day_report_locations() {
    $selectedPrinter = star_cloudprnt_get_printer();
    $file = STAR_CLOUDPRNT_PRINTER_PENDING_SAVE_PATH . star_cloudprnt_get_os_path("/report_" . uniqid() . "_" . time() . "." . $selectedPrinter['format']);

    $printer = star_cloudprnt_command_generator($selectedPrinter, $file);


    $from = date('Y-m-d H:i:s', strtotime('today midnight'));
    $to = date('Y-m-d H:i:s', strtotime('tomorrow midnight'));

    $locations = dgit_wsl_get_locations();

    $printed = 0;

    foreach ($locations as $location_id => $location) {
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => 'wc-completed',
            'type' => 'shop_order',
            'date_query' => [
                'after' => $from,
                'before' => $to
            ],
            '_dgit_wsl_location_id' => [
                'value' => $location_id,
                'compare' => '='
            ]
        ]);

        $refunded_orders = wc_get_orders([
            'limit' => -1,
            'status' => 'wc-refunded',
            'type' => 'shop_order',
            'date_query' => [
                'after' => $from,
                'before' => $to
            ],
            '_dgit_wsl_location_id' => [
                'value' => $location_id,
                'compare' => '='
            ]
        ]);

        $order_costs = [];
        $total_order_costs = 0;
        $total_orders = 0;
        $gateways = WC()->payment_gateways()->payment_gateways();

        foreach ($orders as $order) {
            if (!isset($order_costs[$order->get_payment_method()])) {
                $order_costs[$order->get_payment_method()] = [];
            }
            $order_costs[$order->get_payment_method()][$order->get_id()] = $order->get_total();
            $total_order_costs += $order->get_total();
            $total_orders++;
        }

        foreach ($refunded_orders as $refunded_order) {
            $total_orders++;
        }

        if ($total_orders == 0) {
            continue;
        }

        $printed++;

        do_action('star_cloudprnt_before_day_end_report_title', $printer);
        $printer->set_font_magnification(2, 2);
        $printer->add_text_line('Day End Report');
        $printer->set_font_magnification(1, 1);
        do_action('star_cloudprnt_after_day_end_report_title', $printer);

        do_action('star_cloudprnt_before_day_end_report_explanation', $printer);
        $printer->add_text_line(wordwrap('Report ran on completed orders between the following dates:', $selectedPrinter['columns'], "\n", true));
        $printer->add_new_line(1);
        do_action('star_cloudprnt_after_day_end_report_explanation', $printer);

        do_action('star_cloudprnt_before_day_end_report_dates', $printer);
        $printer->add_text_line(sprintf('Location: %s', $location));
        $printer->add_text_line(sprintf('From: %s', $from));
        $printer->add_text_line(sprintf('To: %s', $to));

        $printer->add_text_line(str_repeat('-', $selectedPrinter['columns']));

        do_action('star_cloudprnt_after_day_end_report_dates', $printer);

        $printer->add_text_line(sprintf('Completed Orders: %s', count($orders)));

        $printer->add_text_line(sprintf('Orders Amount Total: £%s', number_format($total_order_costs, 2)));

        foreach ($order_costs as $payment_method => $orders) {

            $method_total = 0;
            foreach ($orders as $order_id => $amount) {
                $method_total += $amount;
            }
            $printer->add_text_line(sprintf('  %s: £%s', $gateways[$payment_method]->get_title(), number_format($method_total, 2)));
        }

        $printer->add_text_line(str_repeat('-', $selectedPrinter['columns']));

        if (count($refunded_orders) > 0) {
            $printer->add_text_line(wordwrap(sprintf('%s Order(s) Refunded', count($refunded_orders)), $selectedPrinter['columns'], "\n", true));
            $printer->add_new_line(1);
            foreach ($refunded_orders as $refunded_order) {
                $printer->add_text_line(sprintf('Order %s (£%s)', $refunded_order->get_id(), number_format($refunded_order->get_total_refunded(), 2)), count($refunded_orders));
                $printer->add_text_line(sprintf('- %s', $refunded_order->get_refunds()[0]->get_reason()));
            }
        } else {
            $printer->add_text_line('No orders were refunded');
        }

        do_action('star_cloudprnt_after_refund_list', $printer);

        $printer->add_text_line(str_repeat('-', $selectedPrinter['columns']));
        $printer->add_text_line('Report printed at:');
        $printer->add_text_line(sprintf('  %s', wp_date('d/m/Y H:i:s')));

        $printer->cut();
    }

    if ($printed > 0) {
        $printer->printjob(1);
    } else {
        $printer->set_font_magnification(2, 2);
        $printer->add_text_line('Day End Report');
        $printer->set_font_magnification(1, 1);
        $printer->add_new_line(1);
        $printer->add_text_line(wordwrap('No locations took any payments today', $selectedPrinter['columns'], "\n", true));
        $printer->add_new_line(1);
        $printer->cut();
        $printer->printjob(1);
    }
}
function add_printer_report_action() {
    if (defined('DGIT_WSL_DIR')) {
        add_action('wp_ajax_star_cloudprnt_print_end_of_day_report', 'star_cloudprnt_print_end_of_day_report_locations');
    } else {
        add_action('wp_ajax_star_cloudprnt_print_end_of_day_report', 'star_cloudprnt_print_end_of_day_report');
    }
}
add_action('init', 'add_printer_report_action');
