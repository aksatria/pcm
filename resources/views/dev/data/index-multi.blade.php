{{-- resources/views/dev/data/index-multi.blade.php --}}
@extends('layouts.dev')

@section('title', 'Data Master - Multi Table')
@section('subtitle', 'Manajemen 6 Kategori Data: Material, Jasa, Alat, Head Office, Sirkulasi, SubKon')

@section('content')
@php
    $isHO = auth()->check() && (
        (auth()->user()->is_admin ?? false) ||
        (method_exists(auth()->user(), 'isHO') && auth()->user()->isHO())
    );
@endphp
<style>
    /* Enhanced Table Design - PROFESSIONAL VERSION */
    .table-container {
        margin-bottom: 1.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        overflow: hidden;
        background: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .table-container:hover {
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
    }

    /* Professional Table Header dengan background warna */
    .table-header {
        color: white;
        padding: 1rem 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        border-bottom: none;
        font-size: 0.875rem;
    }

    .table-header:hover {
        filter: brightness(110%);
    }

    .dark .table-header {
        color: white;
    }

    .table-title {
        font-size: 0.95rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .table-stats {
        display: flex;
        gap: 0.75rem;
        font-size: 0.7rem;
    }

    .stat-item {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        border: 1px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(10px);
    }

    /* Professional Color Scheme untuk Header */
    .header-mt { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
    .header-js { background: linear-gradient(135deg, #10b981, #047857); }
    .header-at { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .header-ho { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .header-sr { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .header-sb { background: linear-gradient(135deg, #6366f1, #4f46e5); }

    /* Enhanced Professional Table Styles */
    .data-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
        font-size: 0.8rem;
        table-layout: fixed;
    }

    .data-table th {
        background: #f8fafc;
        font-weight: 600;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #475569;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.75rem 0.75rem;
        text-align: left;
        white-space: nowrap;
    }

    .data-table td {
        padding: 0.75rem 0.75rem;
        border-bottom: 1px solid #f1f5f9;
        color: #374151;
        transition: all 0.2s ease;
        vertical-align: top;
        line-height: 1.4;
        font-size: 0.8rem;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Striped rows untuk readability yang lebih baik */
    .data-table tbody tr:nth-child(even) {
        background: #fafbfc;
    }

    .data-table tbody tr:hover {
        background: #f8fafc;
    }

    /* Dark mode improvements */
    .dark .data-table {
        background: #1f2937;
    }

    .dark .data-table th {
        background: #374151;
        color: #e5e7eb;
        border-bottom-color: #4b5563;
    }

    .dark .data-table td {
        border-bottom-color: #374151;
        color: #f3f4f6;
    }

    .dark .data-table tbody tr:nth-child(even) {
        background: #374151;
    }

    .dark .data-table tbody tr:hover {
        background: #4b5563;
    }

    /* Enhanced Status Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .status-active {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .status-inactive {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .dark .status-active {
        background: #166534;
        color: #dcfce7;
        border-color: #22c55e;
    }

    .dark .status-inactive {
        background: #991b1b;
        color: #fef2f2;
        border-color: #ef4444;
    }

    /* Enhanced Action Buttons */
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 0.375rem;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
        text-decoration: none;
        font-size: 0.75rem;
    }

    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
    }

    .btn-edit {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .btn-edit:hover {
        background: #bfdbfe;
        color: #1e40af;
    }

    .btn-delete {
        background: #fef2f2;
        color: #dc2626;
    }

    .btn-delete:hover {
        background: #fecaca;
        color: #b91c1c;
    }

    .btn-approve {
        background: #dcfce7;
        color: #16a34a;
    }

    .btn-approve:hover {
        background: #bbf7d0;
        color: #15803d;
    }

    .dark .btn-edit {
        background: #1e3a8a;
        color: #dbeafe;
    }

    .dark .btn-approve {
        background: #14532d;
        color: #dcfce7;
    }

    .dark .btn-edit:hover {
        background: #1e40af;
    }

    .dark .btn-delete {
        background: #7f1d1d;
        color: #fecaca;
    }

    .dark .btn-delete:hover {
        background: #991b1b;
    }

    /* ==================== ENHANCED KODE STYLING - IMPROVED ==================== */
    .kode-badge {
        font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', 'Source Code Pro', monospace;
        font-size: 0.75rem !important;
        font-weight: 600;
        background: #f1f5f9;
        color: #1e40af;
        padding: 0.375rem 0.5rem;
        border-radius: 0.375rem;
        border: 1px solid #e2e8f0;
        display: inline-block;
        min-width: 80px;
        text-align: center;
        letter-spacing: 0.5px;
    }

    .dark .kode-badge {
        background: #1e3a8a;
        color: #dbeafe;
        border-color: #374151;
    }

    /* Enhanced Harga Styling */
    .harga-value {
        font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', 'Source Code Pro', monospace;
        font-weight: 600;
        color: #059669;
        font-size: 0.75rem;
    }

    .dark .harga-value {
        color: #34d399;
    }

    /* Enhanced Empty State */
    .empty-state {
        padding: 2rem 1.5rem;
        text-align: center;
        color: #6b7280;
        font-size: 0.875rem;
    }

    .empty-state svg {
        color: #9ca3af;
        margin-bottom: 0.75rem;
        width: 3rem;
        height: 3rem;
    }

    .dark .empty-state {
        color: #9ca3af;
    }

    .dark .empty-state svg {
        color: #6b7280;
    }

    /* Enhanced Table Content */
    .table-content {
        transition: all 0.3s ease;
    }

    .table-content.collapsed {
        display: none;
    }

    /* Improved scrollbar untuk tabel */
    .table-scroll-container {
        overflow-x: auto;
        border-radius: 0 0 0.5rem 0.5rem;
    }

    .table-scroll-container::-webkit-scrollbar {
        height: 6px;
    }

    .table-scroll-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 0 0 0.5rem 0.5rem;
    }

    .table-scroll-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .table-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .dark .table-scroll-container::-webkit-scrollbar-track {
        background: #374151;
    }

    .dark .table-scroll-container::-webkit-scrollbar-thumb {
        background: #4b5563;
    }

    .dark .table-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #6b7280;
    }

    /* Text readability improvements */
    .text-readable {
        color: #374151;
        line-height: 1.4;
        font-size: 0.8rem;
    }

    .dark .text-readable {
        color: #f3f4f6;
    }

    .text-secondary {
        color: #6b7280;
        font-size: 0.8rem;
    }

    .dark .text-secondary {
        color: #d1d5db;
    }

    /* ==================== PERBAIKAN QUICK EDIT - NO SCROLL & FIXED ==================== */
    .quick-edit-row {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%) !important;
        border: 2px solid #3b82f6 !important;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15) !important;
        position: relative;
    }

    .quick-edit-row::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, #3b82f6, #60a5fa);
    }

    /* PERBAIKAN: Quick Edit Responsive - No Horizontal Scroll */
    .quick-edit-row td {
        vertical-align: middle !important;
        padding: 0.5rem 0.375rem !important;
        height: auto !important;
        max-width: 100% !important;
        overflow: hidden !important;
    }

    .quick-edit-input {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
        padding: 0.375rem 0.5rem !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.25rem !important;
        font-size: 0.75rem !important;
        background: white !important;
        color: #1f2937 !important;
        font-weight: 500 !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
        resize: vertical !important;
    }

    .quick-edit-input:focus {
        outline: none !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1) !important;
        background: white !important;
    }

    .quick-edit-input:hover {
        border-color: #9ca3af !important;
    }

    /* PERBAIKAN TEXTAREA - NO SCROLL */
    .quick-edit-textarea {
        min-height: 50px !important;
        max-height: 70px !important;
        height: 50px !important;
        resize: vertical !important;
        line-height: 1.3 !important;
        font-family: inherit !important;
        overflow-y: auto !important;
        width: 100% !important;
        max-width: 100% !important;
        white-space: normal !important;
        overflow-wrap: anywhere !important;
    }

    .dark .quick-edit-row {
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%) !important;
        border-color: #60a5fa !important;
    }

    .dark .quick-edit-input {
        background: #111827 !important;
        border-color: #374151 !important;
        color: #f9fafb !important;
    }

    .dark .quick-edit-input:focus {
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.2) !important;
        background: #111827 !important;
    }

    /* Quick Edit Select khusus */
    .quick-edit-select {
        cursor: pointer !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e") !important;
        background-position: right 0.375rem center !important;
        background-repeat: no-repeat !important;
        background-size: 0.875rem !important;
        padding-right: 1.5rem !important;
    }

    /* Quick Edit Action Buttons yang lebih elegant */
    .quick-edit-actions {
        display: flex !important;
        gap: 0.25rem !important;
        justify-content: flex-start !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
        min-width: 0 !important;
    }

    .btn-quick-save {
        background: linear-gradient(135deg, #10b981, #059669) !important;
        color: white !important;
        border: none !important;
        box-shadow: 0 1px 3px rgba(16, 185, 129, 0.3) !important;
        width: 1.75rem !important;
        height: 1.75rem !important;
    }

    .btn-quick-save:hover {
        background: linear-gradient(135deg, #059669, #047857) !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 4px rgba(16, 185, 129, 0.4) !important;
    }

    .btn-quick-cancel {
        background: linear-gradient(135deg, #6b7280, #4b5563) !important;
        color: white !important;
        border: none !important;
        box-shadow: 0 1px 3px rgba(107, 114, 128, 0.3) !important;
        width: 1.75rem !important;
        height: 1.75rem !important;
    }

    .btn-quick-cancel:hover {
        background: linear-gradient(135deg, #4b5563, #374151) !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 4px rgba(107, 114, 128, 0.4) !important;
    }

    /* Loading state yang lebih smooth */
    .quick-edit-loading {
        position: relative !important;
        overflow: hidden !important;
    }

    .quick-edit-loading::after {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: -100% !important;
        width: 100% !important;
        height: 100% !important;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent) !important;
        animation: shimmer 1.5s infinite !important;
    }

    .quick-edit-done {
        border-color: #10b981 !important;
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%) !important;
    }

    .dark .quick-edit-done {
        border-color: #34d399 !important;
        background: linear-gradient(135deg, #064e3b 0%, #065f46 100%) !important;
    }

    @keyframes shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }

    /* Harga input styling khusus */
    .quick-edit-harga {
        font-weight: 600 !important;
        color: #059669 !important;
        text-align: right !important;
        font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace !important;
    }

    .dark .quick-edit-harga {
        color: #34d399 !important;
    }

    /* Status badge dalam quick edit */
    .quick-edit-status {
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        font-size: 0.65rem !important;
    }

    /* ==================== PERBAIKAN LEBAR KOLOM KODE - CONSISTENT ==================== */
    .data-table th:nth-child(1),
    .data-table td:nth-child(1) {
        width: 50px !important;
        min-width: 50px;
        max-width: 50px;
    }

    .data-table th:nth-child(2),
    .data-table td:nth-child(2) {
        width: 110px !important;
        min-width: 110px;
        max-width: 120px;
    }

    .data-table th:nth-child(3),
    .data-table td:nth-child(3) {
        min-width: 180px;
        max-width: 250px;
    }

    .data-table th:nth-child(4),
    .data-table td:nth-child(4) {
        width: 80px !important;
        min-width: 80px;
        max-width: 80px;
    }

    .data-table th:nth-child(5),
    .data-table td:nth-child(5) {
        width: 120px !important;
        min-width: 120px;
        max-width: 120px;
    }

    .data-table th:nth-child(6),
    .data-table td:nth-child(6) {
        width: 100px !important;
        min-width: 100px;
        max-width: 100px;
    }

    .data-table th:nth-child(7),
    .data-table td:nth-child(7) {
        width: 110px !important;
        min-width: 110px;
        max-width: 110px;
    }

    /* ==================== ENHANCED TABLE RESPONSIVENESS ==================== */
    @media (max-width: 768px) {
        .data-table th:nth-child(2),
        .data-table td:nth-child(2) {
            width: 90px !important;
            min-width: 90px;
        }
        
        .data-table th:nth-child(3),
        .data-table td:nth-child(3) {
            max-width: 150px;
            min-width: 120px;
        }
        
        .kode-badge {
            font-size: 0.7rem !important;
            padding: 0.25rem 0.375rem;
            min-width: 70px;
        }
        
        .quick-edit-input {
            padding: 0.3rem !important;
            font-size: 0.7rem !important;
        }

        .quick-edit-textarea {
            min-height: 45px !important;
            max-height: 60px !important;
            height: 45px !important;
            font-size: 0.7rem !important;
        }

        .data-table th,
        .data-table td {
            padding: 0.5rem 0.375rem;
        }

        .data-table th:nth-child(1),
        .data-table td:nth-child(1) {
            width: 40px !important;
            min-width: 40px;
        }

        .data-table th:nth-child(4),
        .data-table td:nth-child(4) {
            width: 70px !important;
            min-width: 70px;
        }

        .data-table th:nth-child(5),
        .data-table td:nth-child(5) {
            width: 100px !important;
            min-width: 100px;
        }

        .data-table th:nth-child(6),
        .data-table td:nth-child(6) {
            width: 85px !important;
            min-width: 85px;
        }

        .data-table th:nth-child(7),
        .data-table td:nth-child(7) {
            width: 95px !important;
            min-width: 95px;
        }
    }

    /* ==================== ENHANCED HOVER STATES ==================== */
    .data-table tbody tr {
        transition: all 0.2s ease;
    }

    .data-table tbody tr:hover {
        background: #f8fafc !important;
        transform: translateY(-1px);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .dark .data-table tbody tr:hover {
        background: #374151 !important;
    }

    /* ==================== LOADING ANIMATION ==================== */
    .loading-row {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }

    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .dark .loading-row {
        background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
        background-size: 200% 100%;
    }

    /* ==================== ENHANCED EMPTY STATES ==================== */
    .enhanced-empty-state {
        padding: 2rem 1.5rem;
        text-align: center;
        background: #fafbfc;
        border-radius: 0.5rem;
        margin: 0.75rem;
        font-size: 0.875rem;
    }

    .dark .enhanced-empty-state {
        background: #1f2937;
    }

    /* ==================== NOTIFICATION STYLES ==================== */
    .custom-notification {
        position: fixed;
        top: 0.75rem;
        right: 0.75rem;
        z-index: 1000;
        padding: 0.75rem;
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border-left-width: 3px;
        max-width: 350px;
        animation: slideIn 0.3s ease-out;
        font-size: 0.875rem;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* KEEP ALL EXISTING STYLES - Semua style asli tetap dipertahankan */
    .stats-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 1px solid #e2e8f0;
        height: 100%;
        background: white;
        border-radius: 0.375rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        padding: 1rem;
        position: relative;
        overflow: hidden;
    }

    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: currentColor;
        opacity: 0.7;
    }

    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border-color: #cbd5e1;
    }

    .dark .stats-card {
        background: #1f2937;
        border-color: #374151;
    }

    .dark .stats-card:hover {
        border-color: #4b5563;
    }

    .color-mt { color: #3b82f6; }
    .color-js { color: #10b981; }
    .color-at { color: #8b5cf6; }
    .color-ho { color: #f59e0b; }
    .color-sr { color: #ef4444; }
    .color-sb { color: #6366f1; }

    .text-mt { color: #3b82f6; }
    .text-js { color: #10b981; }
    .text-at { color: #8b5cf6; }
    .text-ho { color: #f59e0b; }
    .text-sr { color: #ef4444; }
    .text-sb { color: #6366f1; }

    .btn-primary {
        background: #3b82f6;
        color: white;
        padding: 0.5rem 0.875rem;
        border-radius: 0.375rem;
        font-weight: 500;
        font-size: 0.8rem;
        transition: all 0.2s ease;
        border: 1px solid #3b82f6;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        cursor: pointer;
    }

    .btn-primary:hover {
        background: #2563eb;
        border-color: #2563eb;
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
        padding: 0.5rem 0.875rem;
        border-radius: 0.375rem;
        font-weight: 500;
        font-size: 0.8rem;
        transition: all 0.2s ease;
        border: 1px solid #6b7280;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        cursor: pointer;
    }

    .btn-secondary:hover {
        background: #4b5563;
        border-color: #4b5563;
        transform: translateY(-1px);
    }

    .btn-success {
        background: #10b981;
        color: white;
        padding: 0.5rem 0.875rem;
        border-radius: 0.375rem;
        font-weight: 500;
        font-size: 0.8rem;
        transition: all 0.2s ease;
        border: 1px solid #10b981;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        cursor: pointer;
    }

    .btn-success:hover {
        background: #059669;
        border-color: #059669;
        transform: translateY(-1px);
    }

    .btn-warning {
        background: #f59e0b;
        color: white;
        padding: 0.5rem 0.875rem;
        border-radius: 0.375rem;
        font-weight: 500;
        font-size: 0.8rem;
        transition: all 0.2s ease;
        border: 1px solid #f59e0b;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        cursor: pointer;
    }

    .btn-warning:hover {
        background: #d97706;
        border-color: #d97706;
        transform: translateY(-1px);
    }

    .form-input {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.8rem;
        transition: all 0.2s ease;
        background: white;
        color: #374151;
    }

    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        background: white;
    }

    .form-select {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.8rem;
        background: white;
        transition: all 0.2s ease;
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.5rem center;
        background-repeat: no-repeat;
        background-size: 1.25em 1.25em;
        padding-right: 2.5rem;
        color: #374151;
    }

    .form-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .dark .form-input,
    .dark .form-select {
        background: #374151;
        border-color: #4b5563;
        color: white;
    }

    .dark .form-input:focus,
    .dark .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        background: #374151;
    }

    .search-highlight {
        background-color: #fff3cd;
        font-weight: 500;
        padding: 0.125rem 0.25rem;
        border-radius: 0.25rem;
        font-size: 0.8rem;
    }

    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .header-title {
        flex: 1;
        min-width: 180px;
    }

    .header-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .header-actions {
            flex-direction: column;
            align-items: stretch;
        }
        
        .header-buttons {
            justify-content: stretch;
        }
        
        .header-buttons .btn {
            flex: 1;
            justify-content: center;
        }
    }

    .search-filter-container {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        padding: 1.25rem;
        margin-bottom: 1.25rem;
    }

    .dark .search-filter-container {
        background: #1f2937;
        border-color: #374151;
    }

    .search-filter-grid {
        display: grid;
        grid-template-columns: 1fr auto auto auto;
        gap: 0.75rem;
        align-items: end;
    }

    @media (max-width: 1024px) {
        .search-filter-grid {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.375rem;
    }

    .dark .form-label {
        color: #d1d5db;
    }

    .dropdown-container {
        position: relative;
        display: inline-block;
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 0.375rem;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        min-width: 180px;
        z-index: 50;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-8px);
        transition: all 0.2s ease;
    }

    .dropdown-container:hover .dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dark .dropdown-menu {
        background: #374151;
        border-color: #4b5563;
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        color: #374151;
        text-decoration: none;
        transition: all 0.2s ease;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
        cursor: pointer;
        font-size: 0.8rem;
    }

    .dropdown-item:hover {
        background: #f3f4f6;
    }

    .dark .dropdown-item {
        color: #d1d5db;
    }

    .dark .dropdown-item:hover {
        background: #4b5563;
    }

    .stats-card-content {
        color: #374151;
        font-size: 0.875rem;
    }

    .dark .stats-card-content {
        color: #d1d5db;
    }

    /* Bulk Action Modal Styles */
    .bulk-action-modal {
        max-width: 450px;
    }

    .bulk-action-form {
        padding: 1.25rem;
    }

    .bulk-action-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
        margin-top: 1.25rem;
    }
</style>

<div class="space-y-4">
    <!-- Enhanced Header Section -->
    <div class="header-actions">
        <div class="header-title">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Data Master</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm">Kelola data material, jasa, alat, dan lainnya</p>
        </div>
        <div class="header-buttons">
            <!-- Button Download Template -->
            <a href="{{ route('dev.data.downloadTemplate') }}" class="btn-secondary">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Download Template
            </a>

            <!-- Button Import Excel (yang sudah ada) -->
            <button onclick="openImportModal()" class="btn-success">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                </svg>
                Import Excel
            </button>

            <button onclick="openQuickAdd()" class="btn-primary">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Quick Add
            </button>

            <a href="{{ route('dev.data.create') }}" class="btn-primary">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Data
            </a>

            @if(auth()->check() && auth()->user()->isHO())
                <a href="{{ route('dev.data.export.all', request()->query()) }}" class="btn-secondary">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export Semua (Excel)
                </a>
            @endif
        </div>
    </div>

    <!-- Enhanced Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        @foreach($kategoriList as $kode => $nama)
            @php
                $stats = $statistics[$kode] ?? ['total_items' => 0, 'total_harga' => 0, 'average_harga' => 0, 'active_items' => 0];
                $warnaKategori = [
                    'MT' => ['color' => 'color-mt', 'text' => 'text-mt', 'icon' => 'fas fa-cube'],
                    'JS' => ['color' => 'color-js', 'text' => 'text-js', 'icon' => 'fas fa-hands-helping'],
                    'AT' => ['color' => 'color-at', 'text' => 'text-at', 'icon' => 'fas fa-tools'],
                    'HO' => ['color' => 'color-ho', 'text' => 'text-ho', 'icon' => 'fas fa-building'],
                    'SR' => ['color' => 'color-sr', 'text' => 'text-sr', 'icon' => 'fas fa-exchange-alt'],
                    'SB' => ['color' => 'color-sb', 'text' => 'text-sb', 'icon' => 'fas fa-hard-hat']
                ];
                $warna = $warnaKategori[$kode] ?? $warnaKategori['MT'];
            @endphp
            <div class="stats-card {{ $warna['color'] }}" onclick="scrollToTable('{{ $kode }}')">
                <div class="stats-card-content">
                    <div class="flex items-center justify-between mb-2">
                        <div class="{{ $warna['text'] }}">
                            <i class="{{ $warna['icon'] }} text-lg"></i>
                        </div>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $stats['total_items'] }}</span>
                    </div>
                    <h3 class="font-semibold text-xs mb-1 text-gray-900 dark:text-white">{{ $nama }}</h3>
                    <div class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                        <div class="flex items-center gap-1">
                            <i class="fas fa-check-circle text-xs"></i>
                            <span>{{ $stats['active_items'] }} Aktif</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <i class="fas fa-times-circle text-xs"></i>
                            <span>{{ $stats['total_items'] - $stats['active_items'] }} Nonaktif</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Enhanced Search and Filter -->
    <div class="search-filter-container">
        <form id="globalSearchForm" method="GET">
            <div class="search-filter-grid">
                <!-- Search Input -->
                <div class="form-group">
                    <label class="form-label">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" 
                               name="search" 
                               class="form-input pl-9"
                               placeholder="Cari kode, uraian, spesifikasi..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" id="globalStatusFilter">
                        <option value="">Semua Status</option>
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <!-- Sort Options -->
                <div class="form-group">
                    <label class="form-label">Urutkan</label>
                    <select name="sort" class="form-select" id="globalSort">
                        <option value="kode_asc" {{ request('sort') == 'kode_asc' ? 'selected' : '' }}>Kode A-Z</option>
                        <option value="kode_desc" {{ request('sort') == 'kode_desc' ? 'selected' : '' }}>Kode Z-A</option>
                        <option value="uraian_asc" {{ request('sort') == 'uraian_asc' ? 'selected' : '' }}>Uraian A-Z</option>
                        <option value="uraian_desc" {{ request('sort') == 'uraian_desc' ? 'selected' : '' }}>Uraian Z-A</option>
                        <option value="harga_low" {{ request('sort') == 'harga_low' ? 'selected' : '' }}>Harga Terendah</option>
                        <option value="harga_high" {{ request('sort') == 'harga_high' ? 'selected' : '' }}>Harga Tertinggi</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="form-group">
                    <label class="form-label invisible">Aksi</label>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary flex-1 justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Terapkan
                        </button>
                        @if(request()->hasAny(['search', 'status', 'sort']))
                            <a href="{{ route('dev.data.index') }}" class="btn-secondary justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Enhanced Individual Tables dengan header berwarna profesional -->
    @foreach($kategoriList as $kode => $nama)
        @php
            $data = $dataByKategori[$kode] ?? [];
            $hasSearchResults = request('search') && ($kategoriCounts[$kode] ?? 0) > 0;
            $stats = $statistics[$kode] ?? ['total_items' => 0, 'total_harga' => 0, 'average_harga' => 0, 'active_items' => 0];
            
            // Warna header yang sesuai dengan kategori
            $headerClasses = [
                'MT' => 'header-mt',
                'JS' => 'header-js', 
                'AT' => 'header-at',
                'HO' => 'header-ho',
                'SR' => 'header-sr',
                'SB' => 'header-sb'
            ];
            $headerClass = $headerClasses[$kode] ?? 'header-mt';
        @endphp
        
        <div class="table-container {{ $hasSearchResults ? 'ring-1 ring-blue-500 ring-opacity-50' : '' }}" id="table-{{ $kode }}">
            <!-- Header dengan background berwarna -->
            <div class="table-header {{ $headerClass }} {{ $loop->first ? '' : 'collapsed' }}" 
                 onclick="toggleTable('{{ $kode }}')">
                <div class="table-title">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-white">{{ $nama }}</span>
                    <span class="bg-white bg-opacity-20 text-white px-2.5 py-1 rounded-full text-[11px] font-medium">
                        {{ $stats['total_items'] }} data
                    </span>
                </div>
                
                <div class="table-stats">
                    <span class="stat-item">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $stats['active_items'] }} Aktif
                    </span>
                    <span class="stat-item">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $stats['total_items'] - $stats['active_items'] }} Nonaktif
                    </span>
                </div>
            </div>
            
            <div class="table-content {{ $loop->first ? '' : 'collapsed' }}" id="content-{{ $kode }}">
                <div class="p-0">
                    <!-- Table Controls -->
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2 p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                        <div class="flex flex-wrap gap-2">
                            <select class="form-select bulk-action-select w-32 text-sm" data-kategori="{{ $kode }}">
                                <option value="">Aksi Massal</option>
                                <option value="activate">Aktifkan</option>
                                <option value="deactivate">Nonaktifkan</option>
                                <option value="delete">Hapus</option>
                                <option value="move_category">Pindah Kategori</option>
                                <option value="update_harga">Edit Harga</option>
                            </select>
                            <button class="btn-primary apply-bulk-action flex items-center gap-1 text-sm" data-kategori="{{ $kode }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Terapkan
                            </button>
                        </div>
                        <div>
                            @if(auth()->check() && auth()->user()->isHO())
                                <a href="{{ route('dev.data.export.by-kategori', array_merge(['kategori' => $kode], request()->query())) }}" 
                                   class="btn-primary flex items-center gap-1 text-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Export {{ $kode }}
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Enhanced Data Table dengan scroll container - TANPA PAGINATION -->
                    <div class="table-scroll-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="w-12">
                                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 select-all" 
                                               data-kategori="{{ $kode }}">
                                    </th>
                                    <th class="w-28">Kode</th>
                                    <th>Uraian</th>
                                    <th class="w-20">Satuan</th>
                                    <th class="w-32">Harga</th>
                                    <th class="w-24">Status</th>
                                    <th class="w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data as $item)
                                    <tr class="{{ !$item->status ? 'bg-yellow-50 dark:bg-yellow-900/10' : '' }}"
                                        id="row-{{ $item->id }}"
                                        ondblclick="enableQuickEdit({{ $item->id }})">
                                        <td class="text-center">
                                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 item-checkbox" 
                                                   value="{{ $item->id }}" data-kategori="{{ $kode }}">
                                        </td>
                                        <td>
                                            <span class="kode-badge">
                                                {{ $item->kode }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-readable" id="uraian-{{ $item->id }}">
                                                @if(request('search'))
                                                    {!! highlightText($item->uraian, request('search')) !!}
                                                @else
                                                    {{ \Illuminate\Support\Str::limit($item->uraian, 80) }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="font-medium text-gray-700 dark:text-gray-300" id="satuan-{{ $item->id }}">
                                            {{ $item->satuan }}
                                        </td>
                                        <td class="text-right" id="harga-{{ $item->id }}">
                                            <span class="harga-value">
                                                Rp {{ number_format($item->harga, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td id="status-{{ $item->id }}">
                                            <div class="flex flex-col gap-1">
                                                <span class="status-badge {{ $item->status ? 'status-active' : 'status-inactive' }}">
                                                    {{ $item->status ? 'Aktif' : 'Nonaktif' }}
                                                </span>
                                                @if($item->delete_status === 'pending')
                                                    <span class="status-badge bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                                        Pending Delete
                                                    </span>
                                                @elseif($item->delete_status === 'rejected')
                                                    <span class="status-badge bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                                                        Delete Ditolak
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex space-x-1">
                                                <button onclick="enableQuickEdit({{ $item->id }})" 
                                                        class="action-btn btn-edit" title="Quick Edit">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </button>
                                                <a href="{{ route('dev.data.edit', $item->id ) }}" 
                                                   class="action-btn btn-edit" title="Edit Detail">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    </svg>
                                                </a>
                                                @if($item->delete_status === 'pending')
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300" title="Menunggu approval">
                                                        Pending
                                                    </span>
                                                    @if($isHO)
                                                        <form action="{{ route('dev.data.approve-delete', $item->id) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="action-btn btn-approve" title="Approve Delete">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('dev.data.reject-delete', $item->id) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="action-btn btn-delete" title="Reject Delete">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @else
                                                    <form action="{{ route('dev.data.destroy', $item->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="action-btn btn-delete" 
                                                                title="Hapus"
                                                                onclick="return confirm('Hapus data {{ $item->kode }}?')">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="enhanced-empty-state">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-16 h-16 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <p class="text-base font-semibold mb-1 text-gray-600 dark:text-gray-400">Tidak ada data {{ $nama }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-500 mb-3">Data akan muncul setelah ditambahkan</p>
                                                <a href="{{ route('dev.data.create') }}" class="btn-primary text-sm">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                    Tambah Data Pertama
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Quick Add Modal - DIPERBAIKI: Input satuan bebas dan opsi tambah lagi -->
<div id="quickAddModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md mx-4">
        <div class="p-5">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3">Tambah Data Cepat</h3>
            <form id="quickAddForm" method="POST" action="{{ route('dev.data.quickStore') }}">
                @csrf
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori</label>
                        <select name="kode_kategori" class="form-select text-sm" required>
                            <option value="">Pilih Kategori</option>
                            @foreach($kategoriList as $kode => $nama)
                                <option value="{{ $kode }}">{{ $nama }} ({{ $kode }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Uraian</label>
                        <textarea name="uraian" class="form-input text-sm" rows="2" required></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Satuan</label>
                            <input type="text" name="satuan" class="form-input text-sm" placeholder="cth: unit, kg, m" required>
                            <p class="text-xs text-gray-500 mt-1">Isi satuan sesuai kebutuhan</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Harga</label>
                            <input type="number" name="harga" class="form-input text-sm" required>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="add_another" name="add_another" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="add_another" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Tambah data lagi setelah ini</label>
                    </div>
                </div>
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="closeQuickAdd()" class="btn-secondary text-sm">Batal</button>
                    <button type="submit" class="btn-primary text-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md mx-4">
        <div class="p-5">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3">Import Data Excel</h3>
            <form id="importForm" method="POST" action="{{ route('dev.data.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">File Excel</label>
                        <input type="file" name="file" class="form-input text-sm" accept=".xlsx,.xls" required>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-2 rounded-lg">
                        <p class="text-xs text-blue-700 dark:text-blue-300">
                            <strong>Format file harus sesuai:</strong><br>
                            Kolom: Kode, Uraian, Spesifikasi, Satuan, Harga, Status
                        </p>
                    </div>
                </div>
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="closeImportModal()" class="btn-secondary text-sm">Batal</button>
                    <button type="submit" class="btn-primary text-sm">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Action Modal -->
<div id="bulkActionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md mx-4 bulk-action-modal">
        <div class="p-5">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3" id="bulkActionTitle">Aksi Massal</h3>
            <form id="bulkActionForm" class="bulk-action-form">
                <div id="bulkActionContent">
                    <!-- Content will be dynamically loaded here -->
                </div>
                <div class="bulk-action-buttons">
                    <button type="button" onclick="closeBulkActionModal()" class="btn-secondary text-sm">Batal</button>
                    <button type="submit" class="btn-primary text-sm">Terapkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // ==================== TABLE TOGGLE FUNCTIONALITY ====================
    function toggleTable(kode) {
        const content = document.getElementById(`content-${kode}`);
        content.classList.toggle('collapsed');
    }

    function scrollToTable(kode) {
        const element = document.getElementById(`table-${kode}`);
        if (element) {
            const content = document.getElementById(`content-${kode}`);
            if (content.classList.contains('collapsed')) {
                toggleTable(kode);
            }
            
            element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            element.classList.add('ring-1', 'ring-blue-500', 'ring-opacity-50');
            setTimeout(() => {
                element.classList.remove('ring-1', 'ring-blue-500', 'ring-opacity-50');
            }, 2000);
        }
    }

    // ==================== MODAL FUNCTIONS ====================
    function openQuickAdd() {
        document.getElementById('quickAddModal').classList.remove('hidden');
    }

    function closeQuickAdd() {
        document.getElementById('quickAddModal').classList.add('hidden');
        document.getElementById('quickAddForm').reset();
    }

    function openImportModal() {
        document.getElementById('importModal').classList.remove('hidden');
    }

    function closeImportModal() {
        document.getElementById('importModal').classList.add('hidden');
    }

    function openBulkActionModal(action, kategori) {
        const modal = document.getElementById('bulkActionModal');
        const title = document.getElementById('bulkActionTitle');
        const content = document.getElementById('bulkActionContent');
        const form = document.getElementById('bulkActionForm');
        
        const actionTitles = {
            'move_category': 'Pindah Kategori',
            'update_harga': 'Edit Harga Massal'
        };
        
        title.textContent = actionTitles[action] || 'Aksi Massal';
        
        let formContent = '';
        
        if (action === 'move_category') {
            formContent = `
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori Baru</label>
                        <select name="new_kategori" class="form-select text-sm" required>
                            <option value="">Pilih Kategori</option>
                            @foreach($kategoriList as $kode => $nama)
                                <option value="{{ $kode }}">{{ $nama }} ({{ $kode }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-2 rounded-lg">
                        <p class="text-xs text-yellow-700 dark:text-yellow-300">
                            <strong>Perhatian:</strong> Data yang dipindahkan akan mendapatkan kode baru sesuai kategori tujuan.
                        </p>
                    </div>
                </div>
            `;
        } else if (action === 'update_harga') {
            formContent = `
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Harga Baru</label>
                        <input type="number" name="new_harga" class="form-input text-sm" placeholder="Masukkan harga baru" required>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-2 rounded-lg">
                        <p class="text-xs text-blue-700 dark:text-blue-300">
                            Harga baru akan diterapkan pada semua data yang dipilih.
                        </p>
                    </div>
                </div>
            `;
        }
        
        content.innerHTML = formContent;
        form.dataset.action = action;
        form.dataset.kategori = kategori;
        modal.classList.remove('hidden');
    }

    function closeBulkActionModal() {
        document.getElementById('bulkActionModal').classList.add('hidden');
        document.getElementById('bulkActionForm').reset();
    }

    // ==================== FIXED QUICK EDIT FUNCTIONALITY ====================
    function enableQuickEdit(id) {
        const row = document.getElementById(`row-${id}`);
        const uraian = document.getElementById(`uraian-${id}`).textContent.trim();
        const satuan = document.getElementById(`satuan-${id}`).textContent.trim();
        const harga = document.getElementById(`harga-${id}`).querySelector('.harga-value').textContent
            .replace('Rp ', '').replace(/\./g, '').trim();
        const status = document.getElementById(`status-${id}`).querySelector('.status-badge').textContent.trim();
        
        const checkbox = row.querySelector('.item-checkbox');
        const isChecked = checkbox.checked;
        const kategori = checkbox.dataset.kategori;
        
        row.classList.add('quick-edit-row');
        row.innerHTML = `
            <td class="text-center">
                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 item-checkbox" 
                       value="${id}" data-kategori="${kategori}" ${isChecked ? 'checked' : ''}>
            </td>
            <td>
                <span class="kode-badge bg-blue-100 text-blue-800 border-blue-200">
                    ${row.querySelector('.kode-badge').textContent}
                </span>
            </td>
            <td>
                <textarea class="quick-edit-input quick-edit-textarea" rows="2" 
                          placeholder="Masukkan uraian item...">${escapeHtml(uraian)}</textarea>
            </td>
            <td>
                <input type="text" class="quick-edit-input" 
                       value="${escapeHtml(satuan)}" 
                       placeholder="cth: unit, kg, m">
            </td>
            <td>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                        <span class="text-gray-500 text-xs">Rp</span>
                    </div>
                    <input type="number" class="quick-edit-input quick-edit-harga pl-8 text-right" 
                           value="${harga}" 
                           min="0" 
                           step="1000">
                </div>
            </td>
            <td>
                <select class="quick-edit-input quick-edit-select quick-edit-status">
                    <option value="1" ${status === 'Aktif' ? 'selected' : ''}>Aktif</option>
                    <option value="0" ${status === 'Nonaktif' ? 'selected' : ''}>Nonaktif</option>
                </select>
            </td>
            <td>
                <div class="quick-edit-actions">
                    <button onclick="saveQuickEdit(${id})" 
                            class="action-btn btn-quick-save" 
                            title="Simpan Perubahan">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                    <button onclick="cancelQuickEdit(${id})" 
                            class="action-btn btn-quick-cancel" 
                            title="Batalkan">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </td>
        `;

        setTimeout(() => {
            const uraianInput = row.querySelector('textarea');
            if (uraianInput) {
                uraianInput.focus();
                uraianInput.select();
            }
        }, 100);

        const inputs = row.querySelectorAll('.quick-edit-input');
        inputs.forEach(input => {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.ctrlKey) {
                    saveQuickEdit(id);
                    e.preventDefault();
                }
                if (e.key === 'Escape') {
                    cancelQuickEdit(id);
                    e.preventDefault();
                }
            });
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function cancelQuickEdit(id) {
        const row = document.getElementById(`row-${id}`);
        row.classList.remove('quick-edit-loading', 'quick-edit-row');
        location.reload();
        showNotification('Edit dibatalkan', 'info');
    }

    function renderUpdatedRow(id, payload) {
        const row = document.getElementById(`row-${id}`);
        if (!row) return;

        const kodeBadge = row.querySelector('.kode-badge');
        const kode = kodeBadge ? kodeBadge.textContent.trim() : '';
        const itemCheckbox = row.querySelector('.item-checkbox');
        const kategori = itemCheckbox ? itemCheckbox.dataset.kategori : '';
        const wasChecked = itemCheckbox ? itemCheckbox.checked : false;
        const editUrl = "{{ route('dev.data.edit', ':id') }}".replace(':id', id);

        const formattedHarga = new Intl.NumberFormat('id-ID').format(Number(payload.harga || 0));
        const statusText = payload.status ? 'Aktif' : 'Nonaktif';
        const statusClass = payload.status ? 'status-active' : 'status-inactive';

        row.className = payload.status ? 'quick-edit-done' : 'bg-yellow-50 dark:bg-yellow-900/10 quick-edit-done';
        row.setAttribute('ondblclick', `enableQuickEdit(${id})`);
        row.innerHTML = `
            <td class="text-center">
                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 item-checkbox"
                       value="${id}" data-kategori="${kategori}" ${wasChecked ? 'checked' : ''}>
            </td>
            <td>
                <span class="kode-badge">${escapeHtml(kode)}</span>
            </td>
            <td>
                <div class="text-readable" id="uraian-${id}">${escapeHtml(payload.uraian)}</div>
            </td>
            <td class="font-medium text-gray-700 dark:text-gray-300" id="satuan-${id}">
                ${escapeHtml(payload.satuan)}
            </td>
            <td class="text-right" id="harga-${id}">
                <span class="harga-value">Rp ${formattedHarga}</span>
            </td>
            <td id="status-${id}">
                <div class="flex flex-col gap-1">
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </div>
            </td>
            <td>
                <div class="flex space-x-1 items-center whitespace-nowrap">
                    <button onclick="enableQuickEdit(${id})" class="action-btn btn-edit" title="Quick Edit">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <a href="${editUrl}" class="action-btn btn-edit" title="Edit Detail">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7s-8.268-2.943-9.542-7z"/>
                        </svg>
                    </a>
                </div>
            </td>
        `;
    }

    // ==================== FIXED SAVE QUICK EDIT - PERBAIKI STATUS ====================
    function saveQuickEdit(id) {
        const row = document.getElementById(`row-${id}`);
        const inputs = row.querySelectorAll('.quick-edit-input');
        const uraian = inputs[0].value.trim();
        const satuan = inputs[1].value.trim();
        const harga = inputs[2].value;
        
        // PERBAIKAN: Konversi status ke boolean dengan benar
        const statusSelect = inputs[3];
        const status = statusSelect.value === '1';

        if (!uraian) {
            showNotification('Uraian tidak boleh kosong', 'warning');
            inputs[0].focus();
            return;
        }

        if (!satuan) {
            showNotification('Satuan tidak boleh kosong', 'warning');
            inputs[1].focus();
            return;
        }

        if (!harga || harga < 0) {
            showNotification('Harga harus diisi dengan angka positif', 'warning');
            inputs[2].focus();
            return;
        }

        const url = "{{ route('dev.data.update', ':id') }}".replace(':id', id);
        
        console.log('🚀 Quick Update - Data:', { uraian, satuan, harga, status });

        row.classList.add('quick-edit-loading');
        
        const formData = new FormData();
        formData.append('uraian', uraian);
        formData.append('satuan', satuan);
        formData.append('harga', parseFloat(harga));
        formData.append('status', status);
        formData.append('_method', 'PUT');
        formData.append('_token', '{{ csrf_token() }}');

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => {
            console.log('📨 Response status:', response.status);
            
            const contentType = response.headers.get('content-type');
            
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                return response.text().then(text => {
                    console.error('❌ Non-JSON response:', text.substring(0, 200));
                    throw new Error('Server returned HTML instead of JSON');
                });
            }
        })
        .then(data => {
            row.classList.remove('quick-edit-loading');
            
            if (data.success) {
                // Then try to render the updated row content in-place.
                try {
                    renderUpdatedRow(id, { uraian, satuan, harga, status });
                } catch (err) {
                    console.error('Render updated row failed:', err);
                }

                showNotification('Data berhasil diperbarui', 'success');
            } else {
                showNotification('❌ ' + (data.message || 'Terjadi kesalahan'), 'error');
            }
        })
        .catch(error => {
            row.classList.remove('quick-edit-loading');
            console.error('❌ Fetch Error:', error);
            showNotification('❌ Gagal menyimpan: ' + error.message, 'error');
        });
    }

    // ==================== NOTIFICATION SYSTEM ====================
    function showNotification(message, type = 'info') {
        const existingNotifications = document.querySelectorAll('.custom-notification');
        existingNotifications.forEach(notif => notif.remove());
        
        const notification = document.createElement('div');
        notification.className = `custom-notification fixed top-3 right-3 z-50 p-3 rounded-lg shadow-lg border-l-3 ${
            type === 'success' ? 'bg-green-50 border-green-500 text-green-700 dark:bg-green-900/20 dark:border-green-400 dark:text-green-300' :
            type === 'error' ? 'bg-red-50 border-red-500 text-red-700 dark:bg-red-900/20 dark:border-red-400 dark:text-red-300' :
            type === 'warning' ? 'bg-yellow-50 border-yellow-500 text-yellow-700 dark:bg-yellow-900/20 dark:border-yellow-400 dark:text-yellow-300' :
            'bg-blue-50 border-blue-500 text-blue-700 dark:bg-blue-900/20 dark:border-blue-400 dark:text-blue-300'
        }`;
        
        notification.innerHTML = `
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${
                        type === 'success' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>' :
                        type === 'error' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>' :
                        type === 'warning' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>' :
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
                    }
                </svg>
                <span class="font-medium text-sm">${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    // ==================== KEYBOARD SHORTCUTS ====================
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'e') {
            const selectedRow = document.querySelector('.data-table tbody tr:hover');
            if (selectedRow && !selectedRow.classList.contains('quick-edit-row')) {
                const id = selectedRow.id.replace('row-', '');
                if (id) {
                    enableQuickEdit(parseInt(id));
                    e.preventDefault();
                }
            }
        }
        
        if (e.key === 'Escape') {
            const editingRow = document.querySelector('.quick-edit-row');
            if (editingRow) {
                const id = editingRow.id.replace('row-', '');
                cancelQuickEdit(parseInt(id));
                e.preventDefault();
            }
        }
        
        if (e.key === 'Enter' && e.ctrlKey) {
            const editingRow = document.querySelector('.quick-edit-row');
            if (editingRow) {
                const id = editingRow.id.replace('row-', '');
                saveQuickEdit(parseInt(id));
                e.preventDefault();
            }
        }
    });

    // ==================== BULK ACTIONS ====================
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.select-all').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const kategori = this.dataset.kategori;
                const checkboxes = document.querySelectorAll(`.item-checkbox[data-kategori="${kategori}"]`);
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
        });

        document.querySelectorAll('.apply-bulk-action').forEach(button => {
            button.addEventListener('click', function() {
                const kategori = this.dataset.kategori;
                const actionSelect = document.querySelector(`.bulk-action-select[data-kategori="${kategori}"]`);
                const selectedAction = actionSelect.value;
                const selectedItems = Array.from(document.querySelectorAll(`.item-checkbox[data-kategori="${kategori}"]:checked`))
                    .map(cb => cb.value);

                if (selectedItems.length === 0) {
                    showNotification('Pilih data terlebih dahulu', 'warning');
                    return;
                }

                if (!selectedAction) {
                    showNotification('Pilih aksi terlebih dahulu', 'warning');
                    return;
                }

                if (['move_category', 'update_harga'].includes(selectedAction)) {
                    openBulkActionModal(selectedAction, kategori);
                } else {
                    if (confirm(`Yakin ingin ${selectedAction} ${selectedItems.length} data?`)) {
                        performBulkAction(selectedAction, kategori, selectedItems);
                    }
                }
            });
        });

        document.getElementById('bulkActionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const action = this.dataset.action;
            const kategori = this.dataset.kategori;
            const selectedItems = Array.from(document.querySelectorAll(`.item-checkbox[data-kategori="${kategori}"]:checked`))
                .map(cb => cb.value);
            
            const formData = new FormData(this);
            formData.append('action', action);
            selectedItems.forEach(id => formData.append('ids[]', id));

            performBulkAction(action, kategori, selectedItems, formData);
            closeBulkActionModal();
        });

        document.getElementById('globalStatusFilter').addEventListener('change', function() {
            document.getElementById('globalSearchForm').submit();
        });

        document.getElementById('globalSort').addEventListener('change', function() {
            document.getElementById('globalSearchForm').submit();
        });

        document.addEventListener('click', function(e) {
            if (e.target.id === 'quickAddModal') closeQuickAdd();
            if (e.target.id === 'importModal') closeImportModal();
            if (e.target.id === 'bulkActionModal') closeBulkActionModal();
        });

        document.getElementById('quickAddForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const addAnother = document.getElementById('add_another').checked;
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    if (addAnother) {
                        const selectedCategory = formData.get('kode_kategori');
                        this.reset();
                        document.querySelector('select[name="kode_kategori"]').value = selectedCategory;
                        document.querySelector('textarea[name="uraian"]').focus();
                    } else {
                        closeQuickAdd();
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error: ' + error, 'error');
            });
        });

        document.getElementById('importForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeImportModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error: ' + error, 'error');
            });
        });

        enhanceEmptyStates();
    });

    function enhanceEmptyStates() {
        const emptyStates = document.querySelectorAll('.empty-state');
        emptyStates.forEach(state => {
            if (!state.classList.contains('enhanced-empty-state')) {
                state.classList.add('enhanced-empty-state');
            }
        });
    }

    function performBulkAction(action, kategori, selectedItems, additionalData = null) {
    const formData = additionalData || new FormData();
    if (!additionalData) {
        formData.append('action', action);
        selectedItems.forEach(id => formData.append('ids[]', id));
    }

    // PERBAIKAN: Gunakan route yang benar untuk bulk action by kategori
    const url = `/dev/data/bulk-action-by-kategori/${kategori}`;

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error: ' + error, 'error');
    });
}
</script>

@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showNotification('{{ session('success') }}', 'success');
    });
</script>
@endif

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showNotification('Terjadi kesalahan: {{ $errors->first() }}', 'error');
    });
</script>
@endif
@endsection

