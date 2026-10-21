<?php
include __DIR__ . '/connect.php';
include __DIR__ . '/session.php';

$CATEGORIES = [
    ['id' => 'all',         'label' => 'All',         'color' => '#2A1A1D'],
    ['id' => 'food',        'label' => 'Food',        'color' => '#E0777D'],
    ['id' => 'clothing',    'label' => 'Clothing',    'color' => '#8E3B46'],
    ['id' => 'electronics', 'label' => 'Electronics', 'color' => '#4C86A8'],
    ['id' => 'education',   'label' => 'Education',   'color' => '#477890'],
    ['id' => 'beauty',      'label' => 'Beauty',      'color' => '#C8A87A'],
];

function _normalize_product($row) {
    return [
        'id'            => 'p' . $row['id'],
        'db_id'         => (int)$row['id'],
        'name'          => $row['name'],
        'seller'        => $row['shop_name'] ?? '',
        'seller_id'     => 's' . ($row['shop_id'] ?? 0),
        'cat'           => $row['category']   ?? 'food',
        'price'         => (float)$row['price'],
        'rating'        => (float)$row['rating'],
        'reviews'       => (int)($row['reviews_count'] ?? 0),
        'tag'           => $row['tag']        ?? null,
        'illu'          => $row['illu_type']  ?? 'jar',
        'palette'       => [$row['palette_1'] ?? '#E1DD8F', $row['palette_2'] ?? '#C8C36A'],
        'description'   => $row['description'] ?? '',
        'quantity'      => (int)($row['quantity'] ?? 0),
        'status'        => $row['status']        ?? 'live',
        'product_image' => $row['product_image'] ?? null,
    ];
}

function _normalize_shop($row) {
    $fc = (int)($row['followers_count'] ?? 0);
    return [
        'id'          => 's' . $row['id'],
        'db_id'       => (int)$row['id'],
        'name'        => $row['name'],
        'tag'         => 'Featured · ' . ucfirst($row['type'] ?? 'food'),
        'cat'         => $row['type']         ?? 'food',
        'followers'   => $fc >= 1000 ? round($fc / 1000, 1) . 'k' : (string)$fc,
        'products'    => (int)($row['products_count'] ?? 0),
        'rating'      => (float)$row['rating'],
        'accent'      => $row['accent_color'] ?? '#E0777D',
        'avatar'      => $row['avatar']       ?? '??',
        'pattern'     => $row['pattern']      ?? 'dots',
        'cover_image' => $row['cover_image']  ?? null,
    ];
}

$sellers_result = mysqli_query($conn,
    'SELECT s.*, (SELECT COUNT(*) FROM products p WHERE p.shop_id = s.id AND p.status = "live") AS products_count
     FROM shops s WHERE s.status = "active" ORDER BY s.id'
);
$SELLERS = [];
while ($row = mysqli_fetch_assoc($sellers_result)) {
    $SELLERS[] = _normalize_shop($row);
}

$products_result = mysqli_query($conn,
    'SELECT p.*, s.name AS shop_name FROM products p
     JOIN shops s ON p.shop_id = s.id WHERE p.status = "live" AND s.status = "active" ORDER BY p.id'
);
$PRODUCTS = [];
while ($row = mysqli_fetch_assoc($products_result)) {
    $PRODUCTS[] = _normalize_product($row);
}

$ORDERS = [];
$u = current_user();

if ($u) {
    $uid = (int)$u['id'];
    $step_map = [
        'pending'          => 1,
        'accepted'         => 2,
        'packed'           => 3,
        'out_for_delivery' => 4,
        'delivered'        => 5,
        'cancelled'        => -1,
        'cancel_requested' => 2,
    ];

    $orders_result = mysqli_query($conn,
        "SELECT o.id, o.customer_id, o.status, o.order_date, o.total,
                COUNT(ol.id) AS items, MAX(s.name) AS seller
         FROM orders o
         LEFT JOIN order_line ol ON ol.order_id = o.id
         LEFT JOIN products p ON p.id = ol.product_id
         LEFT JOIN shops s ON s.id = p.shop_id
         WHERE o.customer_id = $uid
         GROUP BY o.id, o.customer_id, o.status, o.order_date, o.total
         ORDER BY o.order_date DESC"
    );
    if ($orders_result) {
        while ($row = mysqli_fetch_assoc($orders_result)) {
            $ORDERS[] = [
                'id'     => 'LD-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT),
                'db_id'  => $row['id'],
                'date'   => date('M j, Y', strtotime($row['order_date'])),
                'status' => ucwords(str_replace('_', ' ', $row['status'])),
                'total'  => (float)$row['total'],
                'items'  => (int)$row['items'],
                'seller' => $row['seller'] ?? '—',
                'step'   => $step_map[$row['status']] ?? 1,
            ];
        }
    }
}

$COURIER_DELIVERIES = [];
$COURIER_HISTORY    = [];
$AVAILABLE_ORDERS   = [];

if ($u && $u['role'] === 'courier') {
    $uid = (int)$u['id'];

    $del_result = mysqli_query($conn,
        "SELECT * FROM deliveries WHERE courier_id = $uid AND status != 'delivered' ORDER BY assigned_at DESC"
    );
    if ($del_result) {
        while ($row = mysqli_fetch_assoc($del_result)) {
            $COURIER_DELIVERIES[] = [
                'id'       => 'DEL-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT),
                'db_id'    => (int)$row['id'],
                'addr'     => $row['dropoff_address'] ?? '—',
                'dist'     => ($row['distance_km'] ?? '0') . ' km',
                'payout'   => (float)$row['payout'],
                'status'   => $row['status'],
                'time'     => date('H:i', strtotime($row['assigned_at'])),
                'items'    => 1,
                'customer' => 'Customer',
                'eta'      => ($row['eta_minutes'] ?? '30') . ' min',
            ];
        }
    }

    $hist_result = mysqli_query($conn,
        "SELECT * FROM deliveries WHERE courier_id = $uid AND status = 'delivered' ORDER BY delivered_at DESC LIMIT 30"
    );
    if ($hist_result) {
        while ($row = mysqli_fetch_assoc($hist_result)) {
            $COURIER_HISTORY[] = [
                'id'     => 'DEL-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT),
                'date'   => date('M j', strtotime($row['delivered_at'])),
                'payout' => (float)$row['payout'],
                'rating' => (int)($row['rating'] ?? 5),
            ];
        }
    }

    $ao_result = mysqli_query($conn,
        'SELECT o.id, o.order_date, o.total, "—" AS delivery_address, COUNT(ol.id) AS item_count
         FROM orders o LEFT JOIN order_line ol ON ol.order_id = o.id
         WHERE o.status = "pending" GROUP BY o.id, o.order_date, o.total ORDER BY o.order_date ASC'
    );
    if ($ao_result) {
        while ($row = mysqli_fetch_assoc($ao_result)) {
            $AVAILABLE_ORDERS[] = [
                'id'     => 'LD-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT),
                'db_id'  => (int)$row['id'],
                'addr'   => $row['delivery_address'],
                'total'  => (float)$row['total'],
                'items'  => (int)$row['item_count'],
                'payout' => round(max(3.0, (float)$row['total'] * 0.10), 2),
                'time'   => date('M j · H:i', strtotime($row['order_date'])),
            ];
        }
    }
}

$ADMIN_USERS = [];
if ($u && $u['role'] === 'admin') {
    $au_result = mysqli_query($conn,
        'SELECT id, name, email, role, status,
                DATE_FORMAT(created_at, "%b %d") AS joined,
                (SELECT COUNT(*) FROM orders o WHERE o.customer_id = users.id) AS orders
         FROM users ORDER BY created_at DESC'
    );
    if ($au_result) {
        while ($row = mysqli_fetch_assoc($au_result)) {
            $ADMIN_USERS[] = [
                'id'     => 'U-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT),
                'name'   => $row['name'],
                'email'  => $row['email'],
                'role'   => ucfirst($row['role']),
                'joined' => $row['joined'],
                'orders' => (int)$row['orders'],
                'status' => $row['status'],
            ];
        }
    }
}

function find_product($id, $products) {
    foreach ($products as $p) {
        if ($p['id'] === $id) return $p;
    }
    return null;
}

function find_seller($id, $sellers) {
    foreach ($sellers as $s) {
        if ($s['id'] === $id) return $s;
    }
    return null;
}

function find_seller_by_name($name, $sellers) {
    foreach ($sellers as $s) {
        if ($s['name'] === $name) return $s;
    }
    return null;
}

function find_category($id, $categories) {
    foreach ($categories as $c) {
        if ($c['id'] === $id) return $c;
    }
    return null;
}
