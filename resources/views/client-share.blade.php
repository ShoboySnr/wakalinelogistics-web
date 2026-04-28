<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $client->name }} - Order Tracking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .brand-accent-bg { background-color: #C1666B; }
        .brand-accent-text { color: #C1666B; }
        .brand-accent-hover:hover { background-color: #a8555a; }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notification {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .updating {
            animation: pulse 1.5s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="brand-accent-bg text-white py-6 shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold">{{ $client->name }}</h1>
                    @if($client->company_name)
                    <p class="text-sm md:text-base opacity-90 mt-1">{{ $client->company_name }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-sm opacity-75">Order Tracking</p>
                    <p class="text-xs opacity-60" id="last-updated">Last updated: Just now</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="container mx-auto px-4 py-6">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl md:text-3xl font-bold text-gray-900" id="stat-total">{{ $stats['total'] }}</div>
                <div class="text-xs md:text-sm text-gray-500 mt-1">Total Orders</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl md:text-3xl font-bold text-yellow-600" id="stat-pending">{{ $stats['pending'] }}</div>
                <div class="text-xs md:text-sm text-gray-500 mt-1">Pending</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl md:text-3xl font-bold text-purple-600" id="stat-in-transit">{{ $stats['in_transit'] }}</div>
                <div class="text-xs md:text-sm text-gray-500 mt-1">In Transit</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl md:text-3xl font-bold text-green-600" id="stat-delivered">{{ $stats['delivered'] }}</div>
                <div class="text-xs md:text-sm text-gray-500 mt-1">Delivered</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl md:text-3xl font-bold text-red-600" id="stat-cancelled">{{ $stats['cancelled'] }}</div>
                <div class="text-xs md:text-sm text-gray-500 mt-1">Cancelled</div>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    <div id="notification-container" class="fixed top-20 right-4 z-50 space-y-2"></div>

    <!-- Orders Container -->
    <div class="container mx-auto px-4 pb-8">
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Your Orders</h2>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500" id="order-count">{{ $orders->count() }} orders</span>
                    <div class="w-2 h-2 rounded-full bg-green-500 updating" id="update-indicator"></div>
                </div>
            </div>
            
            <div id="orders-container">
                @include('partials.client-orders', ['orders' => $orders])
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="bg-white border-t border-gray-200 py-6 mt-8">
        <div class="container mx-auto px-4 text-center text-gray-500 text-sm">
            <p>Powered by Waka Line Logistics Limited</p>
            <p class="mt-1">Real-time order tracking • Auto-updates every 30 seconds</p>
        </div>
    </div>

    <script>
        const token = '{{ $token }}';
        let lastOrderCount = {{ $orders->count() }};
        let updateInterval;

        function showNotification(message, type = 'info') {
            const container = document.getElementById('notification-container');
            const notification = document.createElement('div');
            
            const bgColors = {
                'success': 'bg-green-500',
                'error': 'bg-red-500',
                'info': 'bg-blue-500',
                'warning': 'bg-yellow-500'
            };
            
            notification.className = `notification ${bgColors[type]} text-white px-6 py-3 rounded-lg shadow-lg max-w-sm`;
            notification.textContent = message;
            
            container.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                notification.style.transition = 'all 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        async function updateOrders() {
            const indicator = document.getElementById('update-indicator');
            indicator.classList.add('updating');
            
            try {
                const response = await fetch(`/api/client-share/${token}/orders`);
                const result = await response.json();
                
                if (result.success) {
                    const container = document.getElementById('orders-container');
                    container.innerHTML = result.html;
                    
                    // Update stats
                    document.getElementById('stat-total').textContent = result.stats.total;
                    document.getElementById('stat-pending').textContent = result.stats.pending;
                    document.getElementById('stat-in-transit').textContent = result.stats.in_transit;
                    document.getElementById('stat-delivered').textContent = result.stats.delivered;
                    document.getElementById('stat-cancelled').textContent = result.stats.cancelled;
                    document.getElementById('order-count').textContent = `${result.stats.total} orders`;
                    
                    // Check for changes
                    if (result.stats.total !== lastOrderCount) {
                        if (result.stats.total > lastOrderCount) {
                            const newOrders = result.stats.total - lastOrderCount;
                            showNotification(`${newOrders} new order(s) added!`, 'info');
                        } else {
                            const completed = lastOrderCount - result.stats.total;
                            showNotification(`${completed} order(s) updated!`, 'success');
                        }
                        lastOrderCount = result.stats.total;
                    }
                    
                    // Update timestamp
                    const now = new Date();
                    document.getElementById('last-updated').textContent = 
                        `Last updated: ${now.toLocaleTimeString()}`;
                }
            } catch (error) {
                console.error('Error updating orders:', error);
                showNotification('Failed to update orders', 'error');
            } finally {
                indicator.classList.remove('updating');
            }
        }

        // Start auto-update
        updateInterval = setInterval(updateOrders, 30000);

        // Update on visibility change
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                updateOrders();
            }
        });

        // Initial timestamp
        const now = new Date();
        document.getElementById('last-updated').textContent = 
            `Last updated: ${now.toLocaleTimeString()}`;
    </script>
</body>
</html>
