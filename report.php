<?php

//die();
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
        'date_query' => array(
            'after' => $from,
            'before' => $to
        )

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
    $printer->add_text_line(wordwrap('Report ran on orders that were marked as completed between the following dates:', $selectedPrinter['columns'], "\n", true));
    $printer->add_new_line(1);
    do_action('star_cloudprnt_after_day_end_report_explanation', $printer);


    do_action('star_cloudprnt_before_day_end_report_dates', $printer);
    $printer->add_text_line(sprintf('From: %s', $from));
    $printer->add_text_line(sprintf('To: %s', $to));
    $printer->add_text_line(str_repeat('-', $selectedPrinter['columns']));
    do_action('star_cloudprnt_after_day_end_report_dates', $printer);

    $printer->add_text_line(sprintf('Orders: %s', count($orders)));

    $printer->add_text_line(sprintf('Orders Amount Total: £%s', number_format($total_order_costs, 2)));

    foreach ($order_costs as $payment_method => $orders) {

        $method_total = 0;
        foreach ($orders as $order_id => $amount) {
            $method_total += $amount;
        }
        $printer->add_text_line(sprintf('  %s: £%s', $gateways[$payment_method]->get_title(), number_format($method_total)));
    }

    $printer->cut();
    $printer->printjob(1);

    die(json_encode([
        $order_costs,
        'date_query' => array(
            'after' => $from,
            'before' => $to
        )
    ]));
}

add_action('wp_ajax_star_cloudprnt_print_end_of_day_report', 'star_cloudprnt_print_end_of_day_report');
