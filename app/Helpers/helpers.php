<?php
// app/helpers.php

if (!function_exists('highlightText')) {
    function highlightText($text, $search) {
        if (empty($search) || empty($text)) {
            return e($text);
        }
        
        $search = preg_quote($search, '/');
        $pattern = "/($search)/i";
        $replacement = '<span class="search-highlight">$1</span>';
        
        return preg_replace($pattern, $replacement, e($text));
    }
}

if (!function_exists('formatRupiah')) {
    function formatRupiah($number) {
        return 'Rp ' . number_format($number, 0, ',', '.');
    }
}

if (!function_exists('formatNumber')) {
    function formatNumber($number, $decimals = 0) {
        return number_format($number, $decimals, ',', '.');
    }
}

if (!function_exists('getStatusBadge')) {
    function getStatusBadge($status) {
        if ($status) {
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">Aktif</span>';
        } else {
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">Nonaktif</span>';
        }
    }
}

if (!function_exists('getKategoriColor')) {
    function getKategoriColor($kodeKategori) {
        $colors = [
            'MT' => ['bg' => 'bg-blue-500', 'text' => 'text-blue-500', 'border' => 'border-blue-500'],
            'JS' => ['bg' => 'bg-green-500', 'text' => 'text-green-500', 'border' => 'border-green-500'],
            'AT' => ['bg' => 'bg-purple-500', 'text' => 'text-purple-500', 'border' => 'border-purple-500'],
            'HO' => ['bg' => 'bg-yellow-500', 'text' => 'text-yellow-500', 'border' => 'border-yellow-500'],
            'SR' => ['bg' => 'bg-red-500', 'text' => 'text-red-500', 'border' => 'border-red-500'],
            'SB' => ['bg' => 'bg-indigo-500', 'text' => 'text-indigo-500', 'border' => 'border-indigo-500'],
        ];
        
        return $colors[$kodeKategori] ?? $colors['MT'];
    }
}