<!DOCTYPE html>
<html lang="id" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PCM - @yield('title', 'Dashboard')</title>
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                fontFamily: {
                    'sans': ['Poppins', 'system-ui', 'sans-serif'],
                },
                extend: {
                    colors: {
                        primary: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    },
                    spacing: {
                        '18': '4.5rem',
                        '88': '22rem',
                    },
                    animation: {
                        'slide-in': 'slideIn 0.3s ease-out',
                        'slide-out': 'slideOut 0.3s ease-in',
                        'float': 'float 3s ease-in-out infinite',
                        'fade-in': 'fadeIn 0.5s ease-in',
                    },
                    keyframes: {
                        slideIn: {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(0)' },
                        },
                        slideOut: {
                            '0%': { transform: 'translateX(0)' },
                            '100%': { transform: 'translateX(-100%)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' },
                        },
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        .sidebar-transition {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .icon-hover {
            transition: all 0.2s ease-in-out;
        }
        .icon-hover:hover {
            transform: scale(1.1);
        }
        
        /* IMPROVED: Clean active menu style untuk sidebar terbuka */
        .menu-item-active {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(99, 102, 241, 0.1) 100%);
            color: white;
            position: relative;
        }
        .menu-item-active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 60%;
            width: 3px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 0 4px 4px 0;
        }
        
        /* IMPROVED: Active menu style untuk sidebar tertutup */
        .sidebar-compact .menu-item-active {
            background: rgba(59, 130, 246, 0.2);
            border-radius: 12px;
            margin: 0 0.5rem;
        }
        .sidebar-compact .menu-item-active::before {
            display: none;
        }
        
        /* IMPROVED: Hover effect untuk sidebar terbuka */
        .sidebar-menu-item:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(4px);
        }
        
        /* IMPROVED: Hover effect untuk sidebar tertutup */
        .sidebar-compact .sidebar-menu-item:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: scale(1.05);
            margin: 0 0.5rem;
            border-radius: 12px;
        }
        
        /* Custom scrollbar for sidebar */
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: rgba(31, 41, 55, 0.5);
            border-radius: 2px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(75, 85, 99, 0.8);
            border-radius: 2px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(107, 114, 128, 0.8);
        }
        
        /* Ensure Poppins font is applied */
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        /* Floating sidebar background effect */
        .floating-sidebar {
            background: linear-gradient(135deg, rgba(17, 24, 39, 0.95) 0%, rgba(31, 41, 55, 0.9) 100%);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 255, 255, 0.05);
        }
        
        /* Compact sidebar style - IMPROVED & LEBIH KECIL */
        .sidebar-compact {
            width: 4.5rem !important; /* Lebih kecil dari sebelumnya */
        }
        .sidebar-compact .menu-text,
        .sidebar-compact .menu-badge,
        .sidebar-compact .brand-text {
            display: none !important;
        }
        .sidebar-compact .logo-container {
            justify-content: center !important;
            padding: 0.5rem !important;
        }
        .sidebar-compact .logo-container img {
            transform: scale(1.1);
        }
        .sidebar-compact .sidebar-menu-item {
            justify-content: center;
            padding: 0.75rem !important;
            margin: 0.125rem 0.5rem;
            border-radius: 12px;
        }

        /* Font lebih kecil untuk sidebar */
        .sidebar-font-sm {
            font-size: 0.875rem; /* text-sm */
            line-height: 1.25rem;
        }
        
        /* Profile dropdown z-index fix */
        .profile-dropdown {
            z-index: 1000;
        }

        /* Loading animation */
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Notification styles */
        .notification {
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body class="h-full bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 font-sans antialiased">
    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"></div>

    <div class="flex h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
        <!-- Floating Sidebar - LEBIH SEMPIT -->
        <div id="sidebar" class="fixed lg:static inset-y-0 left-0 z-50 w-72 floating-sidebar rounded-r-2xl lg:rounded-2xl lg:mx-3 lg:my-3 text-white sidebar-transition transform -translate-x-full lg:translate-x-0">
            <div class="flex flex-col h-full">
                <!-- Header Sidebar dengan Logo - LEBIH KOMPAK -->
                <div class="p-4 border-b border-gray-700/30"> <!-- Padding lebih kecil -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 logo-container">
                            <!-- Logo Custom - lebih kecil -->
                            <div class="flex items-center justify-center">
                                <img src="{{ asset('images/logo.png') }}" alt="PCM Logo" class="w-10 h-10 object-contain" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDgiIGhlaWdodD0iNDgiIHZpZXdCb3g9IjAgMCA0OCA0OCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiByeD0iMTIiIGZpbGw9InVybCgjZ3JhZGllbnQwX2xpbmVhcl80MV84KSIvPgo8ZGVmcz4KPGxpbmVhckdyYWRpZW50IGlkPSJncmFkaWVudDBfbGluZWFyXzQxXzgiIHgxPSIyNCIgeTE9IjAiIHgyPSIyNCIgeTI9IjQ4IiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+CjxzdG9wIHN0b3AtY29sb3I9IiMzQjgxRjYiLz4KPHN0b3Agb2Zmc2V0PSIxIiBzdG9wLWNvbG9yPSIjOEI1Q0Y2Ii8+CjwvbGluZWFyR3JhZGllbnQ+CjwvZGVmcz4KPHN2ZyB4PSIxMiIgeT0iMTIiIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgZmlsbD0id2hpdGUiPgo8cGF0aCBkPSJNMTIgMkM2LjQ4IDIgMiA2LjQ4IDIgMTJzNC40OCAxMCAxMCAxMCAxMC00LjQ4IDEwLTEwUzE3LjUyIDIgMTIgMnptLTIgMTVsLTUtNSA0LjAxLTQuMDFMMTAgOC45OWw2LTYgNiA2LTIuODQgMi44NEwxOSAxNWwtNCA0LTQtNHoiLz4KPC9zdmc+Cjwvc3ZnPgo='">
                            </div>
                            <div class="menu-text brand-text">
                                <h1 class="text-lg font-bold text-white bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent sidebar-font-sm">PCM</h1> <!-- Font lebih kecil -->
                                <p class="text-gray-400 text-xs sidebar-font-sm">Project Cost Management</p>
                            </div>
                        </div>
                        <!-- Close Button untuk Mobile & Desktop Toggle -->
                        <div class="flex items-center space-x-2">
                            <!-- Desktop Toggle Button -->
                            <button id="sidebarDesktopToggle" class="hidden lg:block p-2 hover:bg-white/5 rounded-lg sidebar-transition" title="Toggle Sidebar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                                </svg>
                            </button>
                            <!-- Close Button untuk Mobile -->
                            <button id="sidebarClose" class="lg:hidden p-2 hover:bg-white/5 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Navigation Menu - LEBIH KOMPAK -->
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto sidebar-scroll"> <!-- Padding lebih kecil -->
                    <!-- Dashboard - Blue -->
                    <a href="{{ route('dev.dashboard') }}" 
                       class="flex items-center px-3 py-2.5 text-gray-300 rounded-xl sidebar-transition sidebar-menu-item group sidebar-font-sm {{ request()->routeIs('dev.dashboard') ? 'menu-item-active' : '' }}"> <!-- Padding & font lebih kecil -->
                        <div class="w-8 h-8 flex items-center justify-center mr-3 icon-hover"> <!-- Icon lebih kecil -->
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <!-- Icon lebih kecil -->
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </div>
                        <span class="font-medium menu-text sidebar-font-sm">Dashboard</span> <!-- Font lebih kecil -->
                        @if(request()->routeIs('dev.dashboard'))
                        <div class="ml-auto w-1.5 h-1.5 bg-blue-400 rounded-full animate-pulse menu-badge"></div> <!-- Badge lebih kecil -->
                        @endif
                    </a>

                    <!-- Master Data - Amber/Gold -->
                    <a href="{{ route('dev.data.index') }}" 
                       class="flex items-center px-3 py-2.5 text-gray-300 rounded-xl sidebar-transition sidebar-menu-item group sidebar-font-sm {{ request()->routeIs('dev.data.*') ? 'menu-item-active' : '' }}">
                        <div class="w-8 h-8 flex items-center justify-center mr-3 icon-hover">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="font-medium menu-text sidebar-font-sm">Master Data</span>
                        <!-- Counter di sidebar untuk Master Data tidak diperlukan -->
                    </a>

                    <!-- Clients - Green -->
                    <a href="{{ route('dev.clients.index') }}" 
                       class="flex items-center px-3 py-2.5 text-gray-300 rounded-xl sidebar-transition sidebar-menu-item group sidebar-font-sm {{ request()->routeIs('dev.clients.*') ? 'menu-item-active' : '' }}">
                        <div class="w-8 h-8 flex items-center justify-center mr-3 icon-hover">
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <span class="font-medium menu-text sidebar-font-sm">Clients</span>
                        <span class="ml-auto text-xs bg-gray-700/50 text-gray-300 px-1.5 py-0.5 rounded-full menu-badge sidebar-font-sm">{{ $sidebarData['clientsCount'] ?? 0 }}</span>
                    </a>

                    <!-- Projects - Purple -->
                    <a href="{{ route('dev.projects.index') }}" 
                       class="flex items-center px-3 py-2.5 text-gray-300 rounded-xl sidebar-transition sidebar-menu-item group sidebar-font-sm {{ (request()->routeIs('dev.projects.*') || request()->routeIs('dev.rab-breakdown.*')) ? 'menu-item-active' : '' }}">
                        <div class="w-8 h-8 flex items-center justify-center mr-3 icon-hover">
                            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                            </svg>
                        </div>
                        <span class="font-medium menu-text sidebar-font-sm">Projects</span>
                        <span class="ml-auto text-xs bg-gray-700/50 text-gray-300 px-1.5 py-0.5 rounded-full menu-badge sidebar-font-sm">{{ $sidebarData['projectsCount'] ?? 0 }}</span>
                    </a>

                    <!-- RAB - Emerald -->
                    <a href="{{ route('dev.rab.index') }}" class="flex items-center px-3 py-2.5 text-gray-300 rounded-xl sidebar-transition sidebar-menu-item group sidebar-font-sm {{ request()->routeIs('dev.rabs.*') || request()->routeIs('dev.rab.*') ? 'menu-item-active' : '' }}">
                        <div class="w-8 h-8 flex items-center justify-center mr-3 icon-hover">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="font-medium menu-text sidebar-font-sm">RAPP</span>
                        <!-- Counter di sidebar untuk RAPP tidak diperlukan -->
                    </a>

                    <!-- Dokumen Proyek Shortcut -->
                    <a href="{{ route('dev.documents.index') }}"
                       class="flex items-center px-3 py-2.5 text-gray-300 rounded-xl sidebar-transition sidebar-menu-item group sidebar-font-sm {{ request()->routeIs('dev.documents.*') ? 'menu-item-active' : '' }}">
                        <div class="w-8 h-8 flex items-center justify-center mr-3 icon-hover">
                            <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                            </svg>
                        </div>
                        <span class="font-medium menu-text sidebar-font-sm">Dokumen Proyek</span>
                        <span class="ml-auto text-xs bg-gray-700/50 text-gray-300 px-1.5 py-0.5 rounded-full menu-badge sidebar-font-sm">Shortcut</span>
                    </a>

                    <!-- Dokumentasi Roles & Permission -->
                    <a href="{{ route('dev.docs.roles-permissions') }}"
                       class="flex items-center px-3 py-2.5 text-gray-300 rounded-xl sidebar-transition sidebar-menu-item group sidebar-font-sm {{ request()->routeIs('dev.docs.*') ? 'menu-item-active' : '' }}">
                        <div class="w-8 h-8 flex items-center justify-center mr-3 icon-hover">
                            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 4h.01M9 3h6a2 2 0 012 2v14a2 2 0 01-2 2H9a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                            </svg>
                        </div>
                        <span class="font-medium menu-text sidebar-font-sm">Dokumentasi</span>
                    </a>

                    <!-- Inbox Notifikasi - Blue -->
                    <a href="{{ route('dev.notifications.inbox') }}" 
                       class="flex items-center px-3 py-2.5 text-gray-300 rounded-xl sidebar-transition sidebar-menu-item group sidebar-font-sm {{ request()->routeIs('dev.notifications.*') ? 'menu-item-active' : '' }}">
                        <div class="w-8 h-8 flex items-center justify-center mr-3 icon-hover">
                            <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-5-5.917V4a1 1 0 10-2 0v1.083A6 6 0 006 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 01-6 0m6 0H9"/>
                            </svg>
                        </div>
                        <span class="font-medium menu-text sidebar-font-sm">Inbox</span>
                        @if(($sidebarData['notificationsUnread'] ?? 0) > 0)
                            <span id="sidebarNotifBadge" class="ml-auto text-xs bg-rose-600/90 text-white px-2 py-0.5 rounded-full menu-badge sidebar-font-sm">
                                {{ $sidebarData['notificationsUnread'] }}
                            </span>
                        @else
                            <span id="sidebarNotifBadge" class="hidden ml-auto text-xs bg-rose-600/90 text-white px-2 py-0.5 rounded-full menu-badge sidebar-font-sm"></span>
                        @endif
                    </a>

                    @php
                        $isHO = auth()->check() && method_exists(auth()->user(), 'isHO') && auth()->user()->isHO();
                    @endphp
                    @if($isHO)
                    <!-- Approval Center - Orange -->
                    <a href="{{ route('dev.approvals.index') }}" 
                       class="flex items-center px-3 py-2.5 text-gray-300 rounded-xl sidebar-transition sidebar-menu-item group sidebar-font-sm {{ request()->routeIs('dev.approvals.*') ? 'menu-item-active' : '' }}">
                        <div class="w-8 h-8 flex items-center justify-center mr-3 icon-hover">
                            <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M12 3l7 4v5c0 5-3 9-7 9s-7-4-7-9V7l7-4z"/>
                            </svg>
                        </div>
                        <span class="font-medium menu-text sidebar-font-sm">Approval Center</span>
                        @if(($sidebarData['approvalsPending'] ?? 0) > 0)
                            <span id="sidebarApprovalBadge" class="ml-auto text-xs bg-amber-500/90 text-white px-2 py-0.5 rounded-full menu-badge sidebar-font-sm">
                                {{ $sidebarData['approvalsPending'] }}
                            </span>
                        @else
                            <span id="sidebarApprovalBadge" class="hidden ml-auto text-xs bg-amber-500/90 text-white px-2 py-0.5 rounded-full menu-badge sidebar-font-sm"></span>
                        @endif
                    </a>
                    @endif

                    <!-- Stock Reports -->
                    <a href="{{ route('dev.stock.rekap') }}"
                       class="flex items-center px-3 py-2.5 text-gray-300 rounded-xl sidebar-transition sidebar-menu-item group sidebar-font-sm {{ request()->routeIs('dev.stock.rekap') ? 'menu-item-active' : '' }}">
                        <div class="w-8 h-8 flex items-center justify-center mr-3 icon-hover">
                            <svg class="w-5 h-5 text-lime-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h10"/>
                            </svg>
                        </div>
                        <span class="font-medium menu-text sidebar-font-sm">Rekap Stock</span>
                    </a>

                    <a href="{{ route('dev.stock.kartu') }}"
                       class="flex items-center px-3 py-2.5 text-gray-300 rounded-xl sidebar-transition sidebar-menu-item group sidebar-font-sm {{ request()->routeIs('dev.stock.kartu') ? 'menu-item-active' : '' }}">
                        <div class="w-8 h-8 flex items-center justify-center mr-3 icon-hover">
                            <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h13M8 12h13M8 17h13M3 7h.01M3 12h.01M3 17h.01"/>
                            </svg>
                        </div>
                        <span class="font-medium menu-text sidebar-font-sm">Kartu Stock</span>
                    </a>

                    <!-- BPP - Red -->
                    <a href="#" class="flex items-center px-3 py-2.5 text-gray-300 rounded-xl sidebar-transition sidebar-menu-item group sidebar-font-sm">
                        <div class="w-8 h-8 flex items-center justify-center mr-3 icon-hover">
                            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5 5.5h.01m4.99 5h.01M2 12a10 10 0 1120 0 10 10 0 01-20 0zm10-6a1 1 0 100 2 1 1 0 000-2zm0 8a1 1 0 100 2 1 1 0 000-2z"/>
                            </svg>
                        </div>
                        <span class="font-medium menu-text sidebar-font-sm">BPP</span>
                        <span class="ml-auto text-xs bg-yellow-500/20 text-yellow-300 px-1.5 py-0.5 rounded-full menu-badge sidebar-font-sm">Soon</span>
                    </a>

                    <!-- User Management - Teal -->
                    <a href="#" class="flex items-center px-3 py-2.5 text-gray-300 rounded-xl sidebar-transition sidebar-menu-item group sidebar-font-sm">
                        <div class="w-8 h-8 flex items-center justify-center mr-3 icon-hover">
                            <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                        </div>
                        <span class="font-medium menu-text sidebar-font-sm">User Management</span>
                        <span class="ml-auto text-xs bg-yellow-500/20 text-yellow-300 px-1.5 py-0.5 rounded-full menu-badge sidebar-font-sm">Soon</span>
                    </a>
                </nav>

                <!-- Footer Sidebar - LEBIH KOMPAK -->
                <div class="p-3 border-t border-gray-700/30"> <!-- Padding lebih kecil -->
                    <div class="text-center">
                        <p class="text-xs text-gray-500 sidebar-font-sm">PCM v1.0</p> <!-- Font lebih kecil -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area dengan spacing untuk floating sidebar -->
        <div class="flex-1 flex flex-col overflow-hidden sidebar-content">
            <!-- Top Header Bar dengan Sidebar Toggle -->
            <header class="bg-white dark:bg-gray-800/80 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700/50 shadow-sm lg:rounded-t-2xl lg:mx-3 lg:mt-3 relative z-30"> <!-- Margin lebih kecil -->
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <!-- Left: Sidebar Toggle + Page Title -->
                        <div class="flex items-center space-x-4">
                            <!-- Sidebar Toggle Button - Visible di semua device -->
                            <button id="sidebarToggle" class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg sidebar-transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                            </button>
                            
                            <!-- Page Title -->
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">@yield('title', 'Dashboard')</h1>
                                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">@yield('subtitle', 'Project Cost Management')</p>
                            </div>
                        </div>

                        <!-- Header Actions -->
                        <div class="flex items-center space-x-1">
                            <!-- Notifications -->
                            <div class="relative">
                                <button id="notifButton" class="relative p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg sidebar-transition" title="Notifications">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-5-5.917V4a1 1 0 10-2 0v1.083A6 6 0 006 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 01-6 0m6 0H9" />
                                    </svg>
                                    <span id="notifBadge" class="hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 text-[10px] font-semibold rounded-full bg-rose-600 text-white flex items-center justify-center"></span>
                                </button>
                                <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-sm font-semibold">Notifikasi</h3>
                                            <button id="notifMarkAll" type="button" class="text-xs text-blue-600 hover:text-blue-700">Tandai semua</button>
                                        </div>
                                        <div id="notifSummary" class="text-xs text-gray-500 dark:text-gray-400 mt-1">0 item</div>
                                        <div class="flex items-center gap-2 mt-3">
                                            <button id="notifFilterAll" type="button" class="px-2.5 py-1 rounded-full text-[11px] font-semibold border bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                                                Semua <span id="notifFilterAllCount" class="ml-1 text-[10px] font-semibold">(0)</span>
                                            </button>
                                            <button id="notifFilterUnread" type="button" class="px-2.5 py-1 rounded-full text-[11px] font-semibold border bg-transparent border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                                                Unread <span id="notifFilterUnreadCount" class="ml-1 text-[10px] font-semibold">(0)</span>
                                            </button>
                                            <button id="notifFilterApproval" type="button" class="px-2.5 py-1 rounded-full text-[11px] font-semibold border bg-transparent border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                                                Approval <span id="notifFilterApprovalCount" class="ml-1 text-[10px] font-semibold">(0)</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div id="notifList" class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700"></div>
                                    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 flex items-center justify-between">
                                        <span>Notifikasi real-time jika tersedia.</span>
                                        <a href="{{ route('dev.notifications.inbox') }}" class="text-blue-600 hover:text-blue-700">Buka Inbox</a>
                                    </div>
                                </div>
                            </div>
                            <!-- Theme Toggle Button -->
                            <button onclick="toggleTheme()" class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg sidebar-transition" title="Toggle Theme">
                                <span id="headerThemeIcon" class="inline-flex items-center justify-center w-5 h-5"></span>
                            </button>

                            <!-- Refresh Button -->
                            <button onclick="refreshData()" class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg sidebar-transition" title="Refresh Data">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>

                            <!-- Profile Dropdown dengan Logout - FIXED Z-INDEX -->
                            <div class="relative profile-dropdown">
                                <button id="profileDropdownButton" class="flex items-center space-x-3 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl sidebar-transition">
                                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div class="text-left hidden md:block">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ auth()->user()->name ?? 'Admin User' }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->role ?? 'Administrator' }}</p>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <!-- Dropdown Menu - FIXED Z-INDEX -->
                                <div id="profileDropdown" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50 hidden profile-dropdown">
                                    <!-- Profile -->
                                    <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        Profile
                                    </a>
                                    <!-- Settings -->
                                    <a href="{{ route('dev.settings.system') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        Settings
                                    </a>
                                    <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                                    <!-- Logout -->
                                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                                        @csrf
                                        <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                            </svg>
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content dengan rounded corners -->
            <main class="flex-1 overflow-auto p-6 bg-white dark:bg-gray-800/50 backdrop-blur-sm lg:rounded-b-2xl lg:mx-3 lg:mb-3 shadow-sm relative z-10"> <!-- Margin lebih kecil -->
                <!-- Success/Error Messages -->
                @if(session('success'))
                    <div class="bg-green-500/10 border border-green-500/20 text-green-700 dark:text-green-300 px-4 py-3 rounded-xl mb-6 flex items-center notification">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-500/10 border border-red-500/20 text-red-700 dark:text-red-300 px-4 py-3 rounded-xl mb-6 flex items-center notification">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div class="bg-yellow-500/10 border border-yellow-500/20 text-yellow-700 dark:text-yellow-300 px-4 py-3 rounded-xl mb-6 flex items-center notification">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        {{ session('warning') }}
                    </div>
                @endif

                @if(session('info'))
                    <div class="bg-blue-500/10 border border-blue-500/20 text-blue-700 dark:text-blue-300 px-4 py-3 rounded-xl mb-6 flex items-center notification">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ session('info') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        // Sidebar functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarDesktopToggle = document.getElementById('sidebarDesktopToggle');
        const sidebarClose = document.getElementById('sidebarClose');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        let isSidebarCompact = false;

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function toggleSidebarCompact() {
            isSidebarCompact = !isSidebarCompact;
            if (isSidebarCompact) {
                sidebar.classList.add('sidebar-compact');
                sidebarDesktopToggle.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7"/></svg>';
            } else {
                sidebar.classList.remove('sidebar-compact');
                sidebarDesktopToggle.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>';
            }
            localStorage.setItem('sidebarCompact', isSidebarCompact);
        }

        // Initialize sidebar state
        document.addEventListener('DOMContentLoaded', function() {
            // Load sidebar compact state
            const savedSidebarState = localStorage.getItem('sidebarCompact');
            if (savedSidebarState === 'true') {
                isSidebarCompact = true;
                sidebar.classList.add('sidebar-compact');
                sidebarDesktopToggle.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7"/></svg>';
            }

            // Event listeners
            sidebarToggle.addEventListener('click', openSidebar);
            sidebarDesktopToggle.addEventListener('click', toggleSidebarCompact);
            sidebarClose.addEventListener('click', closeSidebar);
            sidebarOverlay.addEventListener('click', closeSidebar);

            // Profile dropdown
            const profileDropdownButton = document.getElementById('profileDropdownButton');
            const profileDropdown = document.getElementById('profileDropdown');

            if (profileDropdownButton && profileDropdown) {
                profileDropdownButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('hidden');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function() {
                    profileDropdown.classList.add('hidden');
                });

                // Prevent dropdown from closing when clicking inside
                profileDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }

            // Auto-hide notifications after 5 seconds
            setTimeout(() => {
                const notifications = document.querySelectorAll('.notification');
                notifications.forEach(notification => {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateY(-10px)';
                    setTimeout(() => notification.remove(), 300);
                });
            }, 5000);

            // Notification dropdown
            const notifButton = document.getElementById('notifButton');
            const notifDropdown = document.getElementById('notifDropdown');
            if (notifButton && notifDropdown) {
                notifButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notifDropdown.classList.toggle('hidden');
                });
                document.addEventListener('click', function() {
                    notifDropdown.classList.add('hidden');
                });
                notifDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });

        // Theme functionality
        function setThemeIcon(isDark) {
            const themeIcon = document.getElementById('headerThemeIcon');
            if (!themeIcon) return;

            if (isDark) {
                themeIcon.innerHTML = `
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3c0 .12-.01.24-.01.36a9 9 0 009.8 9.43z" />
                    </svg>
                `;
            } else {
                themeIcon.innerHTML = `
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <circle cx="12" cy="12" r="4" stroke-width="2"></circle>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v2m0 16v2m10-10h-2M4 12H2m16.95 6.95l-1.41-1.41M6.46 6.46 5.05 5.05m13.9 0-1.41 1.41M6.46 17.54l-1.41 1.41" />
                    </svg>
                `;
            }
        }

        function toggleTheme() {
            const html = document.documentElement;
            
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                setThemeIcon(false);
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                setThemeIcon(true);
            }
        }

        // Initialize theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            
            if (savedTheme === 'light') {
                document.documentElement.classList.remove('dark');
                setThemeIcon(false);
            } else {
                document.documentElement.classList.add('dark');
                setThemeIcon(true);
            }
        });

        // Refresh functionality
        function refreshData() {
            // Show loading state
            const refreshBtn = event.currentTarget;
            const originalContent = refreshBtn.innerHTML;
            
            refreshBtn.innerHTML = `
                <svg class="w-5 h-5 loading-spinner" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            `;
            
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + / untuk toggle sidebar
            if (e.ctrlKey && e.key === '/') {
                e.preventDefault();
                if (window.innerWidth >= 1024) {
                    toggleSidebarCompact();
                } else {
                    if (sidebar.classList.contains('-translate-x-full')) {
                        openSidebar();
                    } else {
                        closeSidebar();
                    }
                }
            }
            
            // Escape untuk close sidebar mobile
            if (e.key === 'Escape' && window.innerWidth < 1024) {
                closeSidebar();
            }
        });

        // Responsive behavior
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
                document.body.style.overflow = 'auto';
            } else {
                closeSidebar();
            }
        });

        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('Global error:', e.error);
        });

        // Page transition animation
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.3s ease-in';
            
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
        });

        // Notification realtime + toast
        (function() {
            const feedUrl = '{{ route('dev.notifications.feed') }}';
            const markReadUrl = '{{ route('dev.notifications.read', ['id' => '___ID___']) }}';
            const markAllUrl = '{{ route('dev.notifications.read-all') }}';
            const badgeEl = document.getElementById('notifBadge');
            const listEl = document.getElementById('notifList');
            const summaryEl = document.getElementById('notifSummary');
            const markAllBtn = document.getElementById('notifMarkAll');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            let lastSeenId = localStorage.getItem('notif:lastSeenId') || null;
            let activeFilter = 'all';
            let allItemsCache = [];

              function setBadge(value, approvalsBadge = null) {
                  const badge = Number(value || 0);
                  if (!badgeEl) return;
                  if (badge > 0) {
                      badgeEl.textContent = badge;
                      badgeEl.classList.remove('hidden');
                  } else {
                      badgeEl.classList.add('hidden');
                  }

                  const sidebarBadge = document.getElementById('sidebarNotifBadge');
                  if (sidebarBadge) {
                      if (badge > 0) {
                          sidebarBadge.textContent = badge;
                          sidebarBadge.classList.remove('hidden');
                      } else {
                          sidebarBadge.classList.add('hidden');
                      }
                  }

                  if (approvalsBadge !== null) {
                      const approvalBadgeEl = document.getElementById('sidebarApprovalBadge');
                      if (approvalBadgeEl) {
                          const approvalValue = Number(approvalsBadge || 0);
                          if (approvalValue > 0) {
                              approvalBadgeEl.textContent = approvalValue;
                              approvalBadgeEl.classList.remove('hidden');
                          } else {
                              approvalBadgeEl.classList.add('hidden');
                          }
                      }
                  }

                      const dashboardBadge = document.getElementById('dashboardNotifBadge');
                      if (dashboardBadge) {
                          if (badge > 0) {
                              dashboardBadge.textContent = badge;
                              dashboardBadge.classList.remove('hidden');
                          } else {
                              dashboardBadge.classList.add('hidden');
                          }
                      }

                      if (approvalsBadge !== null) {
                          const dashboardApprovalBadge = document.getElementById('dashboardApprovalBadge');
                          if (dashboardApprovalBadge) {
                              const approvalValue = Number(approvalsBadge || 0);
                              if (approvalValue > 0) {
                                  dashboardApprovalBadge.textContent = approvalValue;
                                  dashboardApprovalBadge.classList.remove('hidden');
                              } else {
                                  dashboardApprovalBadge.classList.add('hidden');
                              }
                          }
                      }
            }

            function updateSummary(items, badge) {
                if (!summaryEl) return;
                if (badge > 0) {
                    summaryEl.textContent = `${badge} belum dibaca`;
                } else {
                    summaryEl.textContent = `${items.length} item`;
                }
            }

            function applyQuickFilter(items) {
                if (activeFilter === 'unread') {
                    return items.filter(item => !item.read_at && !String(item.id || '').startsWith('pending-'));
                }
                if (activeFilter === 'approval') {
                    return items.filter(item => item.type === 'approval' || String(item.id || '').startsWith('pending-'));
                }
                return items;
            }

            function setFilterButtonState() {
                const allBtn = document.getElementById('notifFilterAll');
                const unreadBtn = document.getElementById('notifFilterUnread');
                const approvalBtn = document.getElementById('notifFilterApproval');
                if (!allBtn || !unreadBtn || !approvalBtn) return;

                const activeClass = 'bg-blue-600 text-white border-blue-600 shadow-sm';
                const inactiveClass = 'bg-transparent border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300';

                [allBtn, unreadBtn, approvalBtn].forEach(btn => {
                    btn.classList.remove(...activeClass.split(' '));
                    btn.classList.add(...inactiveClass.split(' '));
                });

                const target = activeFilter === 'unread' ? unreadBtn : activeFilter === 'approval' ? approvalBtn : allBtn;
                target.classList.remove(...inactiveClass.split(' '));
                target.classList.add(...activeClass.split(' '));
            }

            function buildItemHtml(item) {
                const isPending = String(item.id || '').startsWith('pending-');
                const isUnread = !item.read_at && !isPending;
                const readClass = isUnread ? 'bg-blue-50/60 dark:bg-blue-900/20' : '';
                const dotClass = isUnread ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600';
                const unreadPill = isUnread
                    ? `<span class="ml-auto text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">UNREAD</span>`
                    : '';
                const type = item.type || 'system';
                const typeBadge = type === 'approval'
                    ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'
                    : type === 'pending'
                        ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200'
                        : type === 'vendor'
                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'
                            : type === 'stock'
                                ? 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-200'
                                : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200';
                return `
                    <a href="${item.href || '#'}"
                       data-notif-id="${item.id || ''}"
                       data-notif-unread="${isUnread ? '1' : '0'}"
                       class="notif-item block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/60 ${readClass}">
                        <div class="flex items-start gap-3">
                            <span class="mt-1 inline-flex w-2.5 h-2.5 rounded-full ${dotClass}"></span>
                            <div class="flex-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold ${typeBadge}">${type}</span>
                                    <span>${item.time || ''}</span>
                                    ${unreadPill}
                                </div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">${item.title || 'Notifikasi'}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">${item.message || ''}</div>
                            </div>
                        </div>
                    </a>
                `;
            }

            function renderItems(items) {
                if (!listEl) return;
                listEl.innerHTML = '';
                const filtered = applyQuickFilter(items);
                if (!filtered.length) {
                    listEl.innerHTML = `<div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada notifikasi.</div>`;
                    return;
                }
                const groups = filtered.reduce((acc, item) => {
                    const key = item.date || 'unknown';
                    acc[key] = acc[key] || [];
                    acc[key].push(item);
                    return acc;
                }, {});
                Object.keys(groups).forEach(dateKey => {
                    const label = dateKey === 'unknown'
                        ? 'Tanggal tidak diketahui'
                        : new Date(dateKey + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                    listEl.innerHTML += `
                        <div class="px-4 py-2 text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/60 border-y border-gray-100 dark:border-gray-700">
                            ${label}
                        </div>
                    `;
                    groups[dateKey].forEach(item => {
                        listEl.innerHTML += buildItemHtml(item);
                    });
                });
            }

            function showToast(item) {
                const toast = document.createElement('div');
                toast.className = 'fixed top-4 right-4 z-50 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-gray-100';
                toast.innerHTML = `<div class="font-semibold">${item.title || 'Notifikasi'}</div><div class="text-xs text-gray-500 dark:text-gray-400">${item.message || ''}</div>`;
                document.body.appendChild(toast);
                setTimeout(() => {
                    toast.remove();
                }, 3500);
            }
            window.showToast = showToast;

            async function markRead(id, anchorEl) {
                if (!id || String(id).startsWith('pending-')) return;
                try {
                    await fetch(markReadUrl.replace('___ID___', id), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    });
                    if (anchorEl) {
                        anchorEl.dataset.notifUnread = '0';
                        anchorEl.classList.remove('bg-blue-50/60', 'dark:bg-blue-900/20');
                        const dot = anchorEl.querySelector('span');
                        if (dot) {
                            dot.classList.remove('bg-blue-600');
                            dot.classList.add('bg-gray-300', 'dark:bg-gray-600');
                        }
                    }
                    const current = Number(badgeEl?.textContent || 0);
                    if (current > 0) setBadge(current - 1);
                } catch (e) {
                    console.error('Mark read error', e);
                }
            }

            async function markAllRead() {
                try {
                    await fetch(markAllUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
                    setBadge(0);
                    if (listEl) {
                        listEl.querySelectorAll('[data-notif-unread=\"1\"]').forEach(el => {
                            el.dataset.notifUnread = '0';
                            el.classList.remove('bg-blue-50/60', 'dark:bg-blue-900/20');
                            const dot = el.querySelector('span');
                            if (dot) {
                                dot.classList.remove('bg-blue-600');
                                dot.classList.add('bg-gray-300', 'dark:bg-gray-600');
                            }
                        });
                    }
                } catch (e) {
                    console.error('Mark all read error', e);
                }
            }

            async function fetchNotifications() {
                try {
                    const res = await fetch(feedUrl, { headers: { 'Accept': 'application/json' }});
                    const data = await res.json();
                    const items = data.items || [];
                      const badge = Number(data.badge || 0);
                      const approvalsBadge = Number(data.approvals_badge || 0);
                      allItemsCache = items;
                    const unreadCount = items.filter(item => !item.read_at && !String(item.id || '').startsWith('pending-')).length;
                    const approvalCount = items.filter(item => item.type === 'approval' || String(item.id || '').startsWith('pending-')).length;
                    const allCount = items.length;

                    const allCountEl = document.getElementById('notifFilterAllCount');
                    const unreadCountEl = document.getElementById('notifFilterUnreadCount');
                    const approvalCountEl = document.getElementById('notifFilterApprovalCount');
                    if (allCountEl) allCountEl.textContent = `(${allCount})`;
                    if (unreadCountEl) unreadCountEl.textContent = `(${unreadCount})`;
                    if (approvalCountEl) approvalCountEl.textContent = `(${approvalCount})`;

                      setBadge(badge, approvalsBadge);
                    updateSummary(items, badge);
                    renderItems(items);

                    if (items.length) {
                        const newest = items[0].id || null;
                        if (newest && lastSeenId && newest !== lastSeenId) {
                            showToast(items[0]);
                        }
                        if (newest) {
                            lastSeenId = newest;
                            localStorage.setItem('notif:lastSeenId', newest);
                        }
                    }
                } catch (e) {
                    console.error('Notification fetch error', e);
                }
            }
            window.refreshNotifications = fetchNotifications;

            document.addEventListener('DOMContentLoaded', function() {
                fetchNotifications();
                if (markAllBtn) {
                    markAllBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        markAllRead();
                    });
                }

                const filterAll = document.getElementById('notifFilterAll');
                const filterUnread = document.getElementById('notifFilterUnread');
                const filterApproval = document.getElementById('notifFilterApproval');
                if (filterAll && filterUnread && filterApproval) {
                    setFilterButtonState();
                    filterAll.addEventListener('click', function() {
                        activeFilter = 'all';
                        setFilterButtonState();
                        renderItems(allItemsCache);
                    });
                    filterUnread.addEventListener('click', function() {
                        activeFilter = 'unread';
                        setFilterButtonState();
                        renderItems(allItemsCache);
                    });
                    filterApproval.addEventListener('click', function() {
                        activeFilter = 'approval';
                        setFilterButtonState();
                        renderItems(allItemsCache);
                    });
                }

                if (listEl) {
                    listEl.addEventListener('click', function(e) {
                        const anchor = e.target.closest('.notif-item');
                        if (!anchor) return;
                        const isUnread = anchor.dataset.notifUnread === '1';
                        if (isUnread) {
                            markRead(anchor.dataset.notifId, anchor);
                        }
                    });
                }

                const notifButton = document.getElementById('notifButton');
                const notifDropdown = document.getElementById('notifDropdown');
                if (notifButton && notifDropdown) {
                    notifButton.addEventListener('click', function() {
                        if (!notifDropdown.classList.contains('hidden')) return;
                        fetchNotifications();
                    });
                }

                const broadcastDriver = "{{ config('broadcasting.default') }}";
                const wsKey = broadcastDriver === 'reverb'
                    ? "{{ config('broadcasting.connections.reverb.key') }}"
                    : "{{ config('broadcasting.connections.pusher.key') }}";
                const wsCluster = "{{ config('broadcasting.connections.pusher.options.cluster') }}";
                const wsHost = broadcastDriver === 'reverb'
                    ? "{{ config('broadcasting.connections.reverb.options.host') }}"
                    : "{{ config('broadcasting.connections.pusher.options.host') }}";
                const wsPort = broadcastDriver === 'reverb'
                    ? "{{ config('broadcasting.connections.reverb.options.port') }}"
                    : "{{ config('broadcasting.connections.pusher.options.port') }}";
                const wsScheme = broadcastDriver === 'reverb'
                    ? "{{ config('broadcasting.connections.reverb.options.scheme') }}"
                    : "{{ config('broadcasting.connections.pusher.options.scheme') }}";
                const userId = "{{ auth()->id() }}";

                if (wsKey && userId && window.Echo && window.Pusher) {
                    const EchoClass = window.Echo;
                    window.Pusher = window.Pusher || Pusher;
                    window.Echo = new EchoClass({
                        broadcaster: 'pusher',
                        key: wsKey,
                        cluster: wsCluster || 'mt1',
                        wsHost: wsHost || undefined,
                        wsPort: wsPort ? Number(wsPort) : 443,
                        wssPort: wsPort ? Number(wsPort) : 443,
                        forceTLS: wsScheme === 'https',
                        enabledTransports: ['ws', 'wss'],
                    });

                    window.Echo.private(`notifications.${userId}`)
                        .listen('.notification.created', (e) => {
                            fetchNotifications();
                            showToast({
                                title: e.title,
                                message: e.message
                            });
                        });
                }
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>



