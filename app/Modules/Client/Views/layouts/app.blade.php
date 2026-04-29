<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Client Dashboard') - Waka Line Logistics</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
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
        
        [x-cloak] { display: none !important; }
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
                    <a href="{{ route('client.dashboard') }}" class="logo-link">
                        <img src="{{ asset('assets/img/wakalinelogistics-logo-white.png') }}" alt="Waka Line Logistics" class="h-10">
                    </a>
                </div>
                
                <!-- Navigation Links -->
                <nav class="flex-1 px-4 py-6 space-y-2">
                    <a href="{{ route('client.dashboard') }}" 
                       class="sidebar-link flex items-center px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="font-medium">Dashboard</span>
                    </a>
                    
                    <a href="{{ route('client.orders') }}" 
                       class="sidebar-link flex items-center px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('client.orders*') ? 'active' : '' }}">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="font-medium">Orders</span>
                    </a>
                    
                    <a href="{{ route('client.profile') }}" 
                       class="sidebar-link flex items-center px-4 py-3 text-gray-300 rounded-lg border-l-4 border-transparent {{ request()->routeIs('client.profile') ? 'active' : '' }}">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="font-medium">Profile</span>
                    </a>
                </nav>
                
                <!-- User Section -->
                <div class="border-t border-gray-700 p-4">
                    <a href="{{ route('client.profile') }}" class="flex items-center mb-3 hover:bg-gray-700 rounded-lg p-2 transition-colors">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-gray-600 flex items-center justify-center">
                                <span class="text-white font-semibold text-sm">{{ substr(Auth::guard('client')->user()->name, 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ Auth::guard('client')->user()->name }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ Auth::guard('client')->user()->email }}</p>
                        </div>
                    </a>
                    <form method="POST" action="{{ route('client.logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span class="font-medium">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center px-4 md:px-6">
               
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 md:p-6">
                @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-sidebar-overlay');

        mobileMenuBtn?.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        });

        overlay?.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
    </script>
</body>
</html>
