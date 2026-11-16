<?php
/**
 * Order export for the owner.
 *  format=json -> data used by the "Download Excel" button (builds a real .xlsx in the browser)
 *  format=csv  -> plain CSV download that Excel opens directly (works offline too)
 */
require_once 'includes/functions.php';
require_once 'includes/order_filters.php';
require_role('owner');

$f = order_filters();
$orders = filtered_orders($f);
$itemsByOrder = items_for_orders(array_column($orders, 'id'));

$orderRows = [];
$itemRows = [];
foreach ($orders as $o) {
    $items = $itemsByOrder[$o['id']] ?? [];
    $orderRows[] = [
        'Order Code' => $o['order_code'],
        'Date' => date('Y-m-d H:i', strtotime($o['created_at'])),
        'Customer' => $o['customer_name'],
        'Customer Type' => $o['customer_id'] ? 'Member' : 'Guest',
        'Phone' => $o['customer_phone'] ?? '',
        'Order Type' => ucfirst($o['order_type']),
        'Table' => $o['table_no'] ?? '',
        'Items' => implode(', ', array_map(fn($i) => $i['quantity'] . 'x ' . $i['product_name'], $items)),
        'Subtotal' => (float)$o['subtotal'],
        'Discount' => (float)$o['discount'],
        'Total' => (float)$o['total'],
        'Cashier' => $o['cashier_name'] ?? '',
        'Status' => ucfirst($o['status']),
    ];
    foreach ($items as $i) {
        $itemRows[] = [
            'Order Code' => $o['order_code'],
            'Date' => date('Y-m-d', strtotime($o['created_at'])),
            'Product' => $i['product_name'],
            'Quantity' => (int)$i['quantity'],
            'Unit Price' => (float)$i['unit_price'],
            'Unit Discount' => (float)$i['unit_discount'],
            'Line Total' => (float)$i['line_total'],
            'Status' => ucfirst($o['status']),
        ];
    }
}

$filename = 'kopipiro-orders_' . $f['from'] . '_to_' . $f['to'];

if (($_GET['format'] ?? 'csv') === 'json') {
    header('Content-Type: application/json');
    echo json_encode(['filename' => $filename . '.xlsx', 'orders' => $orderRows, 'items' => $itemRows]);
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows characters correctly
fputcsv($out, $orderRows ? array_keys($orderRows[0]) : ['No orders in this period'], ',', '"', '\\');
foreach ($orderRows as $row) {
    fputcsv($out, $row, ',', '"', '\\');
}
fclose($out);
