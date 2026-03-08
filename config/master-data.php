<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Master Data Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk sistem Master Data Management
    | 
    | File ini berisi semua konfigurasi yang diperlukan untuk modul Master Data
    | termasuk import, export, backup, cache, search, validation, UI, security,
    | dan notifikasi.
    |
    */

    // =========================================================================
    // IMPORT CONFIGURATION
    // =========================================================================
    'import' => [
        'max_file_size' => 10240, // 10MB in KB
        'allowed_mimes' => [
            'xlsx', 'xls', 'csv'
        ],
        'allowed_extensions' => [
            'xlsx', 'xls', 'csv'
        ],
        'chunk_size' => 1000, // Process records in chunks
        'batch_size' => 100, // Records per batch for validation
        'timeout' => 300, // 5 minutes
        'max_rows' => 10000, // Maximum rows per import
        'temp_storage_days' => 7, // Days to keep temporary files
    ],

    // =========================================================================
    // EXPORT CONFIGURATION  
    // =========================================================================
    'export' => [
        'chunk_size' => 1000,
        'timeout' => 300,
        'default_format' => 'xlsx',
        'allowed_formats' => ['xlsx', 'csv', 'pdf'],
        'include_deleted' => false, // Whether to include soft deleted records
        'max_records' => 50000, // Maximum records to export
        'temp_storage_hours' => 24, // Hours to keep exported files
    ],

    // =========================================================================
    // BACKUP & RESTORE CONFIGURATION
    // =========================================================================
    'backup' => [
        'retention_days' => 30,
        'max_backups' => 50,
        'compress' => true,
        'storage_disk' => 'local',
        'backup_path' => 'backups/master-data/',
        'auto_backup' => true,
        'backup_schedule' => 'daily', // daily, weekly, monthly
        'include_audit_logs' => true,
    ],

    // =========================================================================
    // CACHE CONFIGURATION
    // =========================================================================
    'cache' => [
        'enabled' => true,
        'dashboard_stats' => 300, // 5 minutes
        'price_analysis' => 3600, // 1 hour
        'search_results' => 300, // 5 minutes
        'kategori_list' => 86400, // 24 hours - rarely changes
        'item_details' => 1800, // 30 minutes
        'similar_items' => 3600, // 1 hour
        'category_stats' => 7200, // 2 hours
    ],

    // =========================================================================
    // SEARCH CONFIGURATION
    // =========================================================================
    'search' => [
        'max_results' => 1000,
        'default_limit' => 50,
        'fuzzy_match' => true,
        'min_search_length' => 2, // Minimum characters for search
        'max_search_length' => 100, // Maximum characters for search
        'weighted_fields' => [
            'kode_item' => 1.5,
            'uraian_item' => 1.2,
            'kategori' => 1.0,
        ],
        'auto_complete_limit' => 10,
        'advanced_search_timeout' => 30, // seconds
    ],

    // =========================================================================
    // VALIDATION CONFIGURATION
    // =========================================================================
    'validation' => [
        'price_change_threshold' => 0.1, // 10%
        'required_fields' => [
            'kategori', 'uraian_item', 'satuan_1', 'harga_satuan_1'
        ],
        'max_uraian_length' => 500,
        'max_satuan_length' => 20,
        'min_harga' => 0,
        'max_harga' => 9999999999.99,
        'kode_item_pattern' => '/^[A-Z]{2,4}-\d{3,5}$/',
        'allowed_satuan' => ['kg', 'm', 'm2', 'm3', 'pcs', 'unit', 'lot', 'hari', 'orang', 'jam'],
    ],

    // =========================================================================
    // USER INTERFACE CONFIGURATION
    // =========================================================================
    'ui' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
        'per_page_options' => [10, 15, 25, 50, 100],
        'auto_refresh_interval' => 300000, // 5 minutes in milliseconds
        'default_sort_field' => 'kode_item',
        'default_sort_direction' => 'asc',
        'show_filters_by_default' => true,
        'confirm_delete' => true,
        'confirm_bulk_operations' => true,
        'animation_enabled' => true,
        'tooltip_enabled' => true,
    ],

    // =========================================================================
    // SECURITY CONFIGURATION
    // =========================================================================
    'security' => [
        'max_login_attempts' => 5,
        'session_timeout' => 3600, // 1 hour
        'api_rate_limit' => 60, // requests per minute
        'bulk_operation_limit' => 1000, // Max records for bulk operations
        'password_min_length' => 8,
        'password_requires_mixed_case' => true,
        'password_requires_numbers' => true,
        'password_requires_symbols' => false,
        'ip_whitelist' => [
            '127.0.0.1',
            '::1',
            // Tambahkan IP yang diizinkan di sini
        ],
    ],

    // =========================================================================
    // NOTIFICATIONS CONFIGURATION
    // =========================================================================
    'notifications' => [
        'enabled' => true,
        'email_on_error' => true,
        'email_on_completion' => false,
        'slack_webhook' => env('MASTER_DATA_SLACK_WEBHOOK'),
        'log_level' => 'error', // debug, info, warning, error
        'notify_on_bulk_operations' => true,
        'notify_on_system_errors' => true,
        'notify_on_import_completion' => true,
        'admin_email' => env('MASTER_DATA_ADMIN_EMAIL', 'admin@example.com'),
    ],

    // =========================================================================
    // CATEGORIES CONFIGURATION
    // =========================================================================
    'categories' => [
        'material' => 'MATERIAL',
        'jasa' => 'JASA', 
        'alat' => 'ALAT',
        'subkon' => 'SUBKON',
        'sirkulasi' => 'SIRKULASI',
        'site_management' => 'SITE_MANAGEMENT',
    ],

    // =========================================================================
    // PRICE ANALYSIS CONFIGURATION
    // =========================================================================
    'analysis' => [
        'time_ranges' => [
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
        ],
        'significant_change_threshold' => 0.05, // 5%
        'default_time_range' => '30d',
        'max_comparison_items' => 10,
        'trend_calculation_days' => 30,
    ],

    // =========================================================================
    // AUDIT TRAIL CONFIGURATION
    // =========================================================================
    'audit' => [
        'enabled' => true,
        'retention_days' => 365, // 1 year
        'log_ip_address' => true,
        'log_user_agent' => true,
        'log_old_values' => true,
        'log_new_values' => true,
        'max_audit_records' => 100000,
        'auto_cleanup' => true,
    ],

    // =========================================================================
    // BULK OPERATIONS CONFIGURATION
    // =========================================================================
    'bulk_operations' => [
        'max_records' => 1000,
        'timeout' => 300, // 5 minutes
        'allowed_fields' => [
            'satuan_1', 'satuan_2', 'harga_satuan_1', 'harga_satuan_2', 'tanggal_update'
        ],
        'confirm_threshold' => 10, // Ask confirmation for more than X records
        'progress_update_interval' => 2, // seconds
    ],

    // =========================================================================
    // INTEGRITY CHECK CONFIGURATION
    // =========================================================================
    'integrity_check' => [
        'auto_check' => true,
        'check_on_startup' => false,
        'check_duplicates' => true,
        'check_invalid_categories' => true,
        'check_negative_prices' => true,
        'check_missing_fields' => true,
        'auto_fix_issues' => true,
        'report_only' => false,
    ],

    // =========================================================================
    // PERFORMANCE CONFIGURATION
    // =========================================================================
    'performance' => [
        'query_timeout' => 30, // seconds
        'max_memory_limit' => '512M',
        'max_execution_time' => 300, // 5 minutes
        'enable_query_logging' => false,
        'lazy_loading' => true,
        'eager_loading_limit' => 100,
    ],

    // =========================================================================
    // DATABASE CONFIGURATION
    // =========================================================================
    'database' => [
        'connection' => env('DB_CONNECTION', 'mysql'),
        'table_prefix' => '',
        'soft_deletes' => true,
        'timestamps' => true,
        'model_namespace' => 'App\\Models\\',
        'morph_map' => [
            'master_data' => 'App\\Models\\MasterData',
        ],
    ],

    // =========================================================================
    // API CONFIGURATION
    // =========================================================================
    'api' => [
        'enabled' => true,
        'version' => 'v1',
        'prefix' => 'api',
        'middleware' => ['api', 'auth:sanctum'],
        'rate_limit' => 60,
        'throttle' => true,
        'cors_enabled' => true,
        'json_pretty_print' => env('APP_DEBUG', false),
    ],

    // =========================================================================
    // DEBUGGING CONFIGURATION
    // =========================================================================
    'debug' => [
        'enabled' => env('APP_DEBUG', false),
        'log_queries' => false,
        'log_import_process' => true,
        'log_export_process' => true,
        'log_backup_process' => true,
        'show_detailed_errors' => env('APP_DEBUG', false),
        'debug_email' => env('MASTER_DATA_DEBUG_EMAIL'),
    ],

    // =========================================================================
    // LOCALIZATION CONFIGURATION
    // =========================================================================
    'localization' => [
        'default_locale' => 'id',
        'supported_locales' => ['id', 'en'],
        'fallback_locale' => 'en',
        'date_format' => 'd/m/Y',
        'time_format' => 'H:i',
        'datetime_format' => 'd/m/Y H:i',
        'currency' => 'IDR',
        'timezone' => 'Asia/Jakarta',
    ],
];