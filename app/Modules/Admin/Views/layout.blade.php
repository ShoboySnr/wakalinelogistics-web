<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Waka Line Logistics Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --brand-dark: #2F3437;
            --brand-accent: #C1666B;
            --brand-accent-hover: #a8555a;
        }
        
        body {
            font-family: Tahoma, Geneva, sans-serif;
        }
        
        .brand-bg { background-color: var(--brand-dark); }
        .brand-accent-bg { background-color: var(--brand-accent); }
        .brand-accent-hover:hover { background-color: var(--brand-accent-hover); }
        .brand-accent-text { color: var(--brand-accent); }
        
        .sidebar-link {
            transition: all 0.2s ease;
        }
        
        .sidebar-link:hover {
            background-color: rgba(193, 102, 107, 0.1);
        }
        
        .sidebar-link.active {
            background-color: rgba(193, 102, 107, 0.1);
            color: var(--brand-accent);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Mobile Menu Button -->
        <button id="mobile-menu-btn" class="md:hidden fixed top-4 left-4 z-50 p-2 rounded-lg bg-gray-800 text-white hover:bg-gray-700 transition-colors">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <!-- Mobile Sidebar Overlay -->
        <div id="mobile-sidebar-overlay" class="md:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="brand-bg w-64 flex-shrink-0 fixed md:static inset-y-0 left-0 z-40 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out md:flex md:flex-col">
            <div class="flex flex-col flex-1 overflow-y-auto">
                <!-- Logo -->
                <div class="flex items-center justify-center h-20 border-b border-gray-700">
                    <a href="{{ route('admin.dashboard') }}" class="logo-link">
                        <img src="{{ asset('assets/img/wakalinelogistics-logo-white.png') }}" alt="Waka Line Logistics" class="h-10">
                    </a>
                </div>
                
                <!-- Navigation Links -->
                <nav class="flex-1 px-4 py-6 space-y-2">
                    <a href="{{ route('admin.dashboard') }}" 
                       class="sidebar-link flex items-center px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="font-medium">Dashboard</span>
                    </a>
                    
                    <a href="{{ route('admin.orders') }}" 
                       class="sidebar-link flex items-center px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="font-medium">Orders</span>
                    </a>
                    
                    <a href="{{ route('admin.riders') }}" 
                       class="sidebar-link flex items-center px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.riders*') ? 'active' : '' }}">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="font-medium">Riders</span>
                    </a>
                    
                    <a href="{{ route('admin.clients') }}" 
                       class="sidebar-link flex items-center px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.clients*') ? 'active' : '' }}">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="font-medium">Clients</span>
                    </a>
                    
                    <a href="{{ route('admin.users.index') }}" 
                       class="sidebar-link flex items-center px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span class="font-medium">Users</span>
                    </a>
                    
                    <a href="{{ route('admin.expenses') }}" 
                       class="sidebar-link flex items-center px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.expenses*') ? 'active' : '' }}">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <span class="font-medium">Expenses</span>
                    </a>
                    
                    <a href="{{ route('admin.transactions') }}" 
                       class="sidebar-link flex items-center px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.transactions*') ? 'active' : '' }}">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        <span class="font-medium">Transactions</span>
                    </a>
                    
                    <!-- Credits Dropdown -->
                    <div x-data="{ open: {{ request()->routeIs('admin.credits*') ? 'true' : 'false' }} }">
                        <button @click="open = !open" 
                                class="sidebar-link w-full flex items-center justify-between px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.credits*') ? 'active' : '' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="font-medium">Credits & Plans</span>
                            </div>
                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        
                        <div x-show="open" x-collapse class="ml-4 mt-2 space-y-1">
                            <a href="{{ route('admin.credits') }}" 
                               class="sidebar-link flex items-center px-4 py-2 text-gray-400 text-sm rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.credits') && !request()->routeIs('admin.credits.*') ? 'active' : '' }}">
                                <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <span class="font-medium">Dashboard</span>
                            </a>
                            
                            <a href="{{ route('admin.credits.plans') }}" 
                               class="sidebar-link flex items-center px-4 py-2 text-gray-400 text-sm rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.credits.plans*') ? 'active' : '' }}">
                                <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <span class="font-medium">Subscription Plans</span>
                            </a>
                            
                            <a href="{{ route('admin.credits.packages') }}" 
                               class="sidebar-link flex items-center px-4 py-2 text-gray-400 text-sm rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.credits.packages*') ? 'active' : '' }}">
                                <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <span class="font-medium">Credit Packages</span>
                            </a>
                            
                            <a href="{{ route('admin.credits.settings') }}" 
                               class="sidebar-link flex items-center px-4 py-2 text-gray-400 text-sm rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.credits.settings*') ? 'active' : '' }}">
                                <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="font-medium">Credit Settings</span>
                            </a>
                        </div>
                    </div>
                    
                    <a href="{{ route('admin.business-intelligence') }}" 
                       class="sidebar-link flex items-center px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.business-intelligence*') ? 'active' : '' }}">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span class="font-medium">Business Intelligence</span>
                    </a>
                    
                    <!-- Settings Dropdown -->
                    <div x-data="{ open: {{ request()->routeIs('admin.settings*') || request()->routeIs('metter*') ? 'true' : 'false' }} }">
                        <button @click="open = !open" 
                                class="sidebar-link w-full flex items-center justify-between px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.settings*') || request()->routeIs('metter*') ? 'active' : '' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="font-medium">Settings</span>
                            </div>
                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        
                        <div x-show="open" x-collapse class="ml-4 mt-2 space-y-1">
                            <a href="{{ route('admin.settings') }}" 
                               class="sidebar-link flex items-center px-4 py-2 text-gray-400 text-sm rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.settings*') && !request()->routeIs('metter*') ? 'active' : '' }}">
                                <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                </svg>
                                <span class="font-medium">General Settings</span>
                            </a>
                            
                            <a href="{{ route('metter.settings') }}" 
                               class="sidebar-link flex items-center px-4 py-2 text-gray-400 text-sm rounded-lg border-l-4 border-transparent {{ request()->routeIs('metter*') ? 'active' : '' }}">
                                <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <span class="font-medium">Metter Settings</span>
                            </a>
                        </div>
                    </div>
                    
                    <a href="{{ route('admin.bikes') }}" 
                       class="sidebar-link flex items-center px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.bikes*') ? 'active' : '' }}">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="font-medium">Bikes</span>
                    </a>
                    
                    <a href="{{ route('admin.communications') }}" 
                       class="sidebar-link flex items-center px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.communications*') ? 'active' : '' }}">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H8m8 0a4 4 0 110-8 4 4 0 010 8zm0 0v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4"/>
                        </svg>
                        <span class="font-medium">Communications</span>
                    </a>
                    
                    <a href="{{ route('admin.job-applications') }}"
                       class="sidebar-link flex items-center px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.job-applications*') ? 'active' : '' }}">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="font-medium">Job Applications</span>
                    </a>

                    <!-- Help Center Dropdown -->
                    <div x-data="{ open: {{ request()->routeIs('admin.help*') ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                                class="sidebar-link w-full flex items-center justify-between px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.help*') ? 'active' : '' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="font-medium">Help Center</span>
                            </div>
                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="ml-4 mt-2 space-y-1">
                            <a href="{{ route('admin.help.tickets') }}"
                               class="sidebar-link flex items-center px-4 py-2 text-gray-400 text-sm rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.help.tickets*') ? 'active' : '' }}">
                                <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <span class="font-medium">Support Tickets</span>
                            </a>
                            <a href="{{ route('admin.help.faqs') }}"
                               class="sidebar-link flex items-center px-4 py-2 text-gray-400 text-sm rounded-lg border-l-4 border-transparent {{ request()->routeIs('admin.help.faqs*') ? 'active' : '' }}">
                                <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="font-medium">FAQs</span>
                            </a>
                        </div>
                    </div>
                </nav>
                
                <!-- User Section -->
                <div class="border-t border-gray-700 p-4">
                    <a href="{{ route('admin.profile') }}" class="flex items-center mb-3 hover:bg-gray-700 rounded-lg p-2 transition-colors">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-gray-600 flex items-center justify-center">
                                <span class="text-white font-semibold text-sm">{{ substr(Auth::user()->name, 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-400">Administrator</p>
                        </div>
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden md:ml-0">
            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
                @if(session('success'))
                    <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-green-700 font-medium">{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-red-700 font-medium">{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
<script>
const mobileMenuBtn = document.getElementById('mobile-menu-btn');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('mobile-sidebar-overlay');

function toggleMobileMenu() {
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

mobileMenuBtn.addEventListener('click', toggleMobileMenu);
overlay.addEventListener('click', toggleMobileMenu);
</script>
</body>
</html>
