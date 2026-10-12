<?php

return [
    'labels' => [
        'dashboard' => 'Dashboard',
        'motor' => 'Motor, kategori & lokasi',
        'rental' => 'Penyewaan',
        'pickup' => 'Pengambilan motor',
        'return' => 'Pengembalian motor',
        'payment' => 'Pembayaran',
        'customer' => 'Customer',
        'promo' => 'Promo',
        'review' => 'Review',
        'gallery' => 'Galeri',
        'tour' => 'Paket wisata',
        'report' => 'Laporan',
        'settings' => 'Pengaturan',
        'users' => 'Admin users',
    ],

    'roles' => [
        'super_admin' => ['*'],
        'admin' => ['dashboard', 'motor', 'rental', 'pickup', 'return', 'payment', 'customer', 'promo', 'review', 'gallery', 'tour', 'report'],
        'kasir' => ['dashboard', 'rental', 'payment', 'return'],
        'staff' => ['dashboard', 'motor', 'pickup', 'return'],
    ],
];
