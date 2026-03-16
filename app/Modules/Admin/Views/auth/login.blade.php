<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Waka Line Logistics</title>
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
        
        .gradient-bg {
            background: linear-gradient(135deg, #2F3437 0%, #3d4448 100%);
        }
        
        .card-shadow {
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .input-focus:focus {
            border-color: #C1666B;
            box-shadow: 0 0 0 3px rgba(193, 102, 107, 0.1);
            outline: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #C1666B 0%, #a8555a 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(193, 102, 107, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .logo-glow {
            filter: drop-shadow(0 4px 12px rgba(193, 102, 107, 0.2));
        }
        
        .decorative-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(193, 102, 107, 0.1);
        }
        
        .circle-1 {
            width: 300px;
            height: 300px;
            top: -150px;
            right: -150px;
        }
        
        .circle-2 {
            width: 200px;
            height: 200px;
            bottom: -100px;
            left: -100px;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        <!-- Decorative Elements -->
        <div class="decorative-circle circle-1"></div>
        <div class="decorative-circle circle-2"></div>
        
        <div class="max-w-md w-full space-y-8 relative z-10 animate-fade-in">
            <!-- Logo and Header -->
            <div class="text-center">
                <div class="flex justify-center mb-8">
                    <a href="/" class="transition-transform hover:scale-105">
                        <img src="{{ asset('assets/img/wakalinelogistics-logo-white.png') }}" alt="Waka Line Logistics" class="h-16 logo-glow">
                    </a>
                </div>
                <h2 class="text-3xl font-bold text-white mb-2">
                    Welcome Back
                </h2>
                <p class="text-gray-300 text-sm">
                    Sign in to access the admin dashboard
                </p>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-2xl card-shadow overflow-hidden">
                <div class="px-8 py-10">
                    <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-6">
                        @csrf
                        
                        @if ($errors->any())
                            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                                <div class="flex items-start">
                                    <svg class="h-5 w-5 text-red-500 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm text-red-700 font-medium">
                                            @foreach ($errors->all() as $error)
                                                {{ $error }}
                                            @endforeach
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Email Input -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                    </svg>
                                </div>
                                <input id="email" name="email" type="email" autocomplete="email" required 
                                       class="input-focus block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 transition-all duration-200" 
                                       placeholder="dave@example.com" value="{{ old('email') }}">
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <input id="password" name="password" type="password" autocomplete="current-password" required 
                                       class="input-focus block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 transition-all duration-200" 
                                       placeholder="Enter your password">
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit" 
                                    class="btn-primary w-full flex justify-center items-center py-3 px-4 border border-transparent text-base font-semibold rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-pink-500">
                                <span>Sign In</span>
                                <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Footer -->
                <div class="px-8 py-4 bg-gray-50 border-t border-gray-100">
                    <p class="text-xs text-center text-gray-500">
                        © {{ date('Y') }} Waka Line Logistics Limited. All rights reserved.
                    </p>
                </div>
            </div>

            <!-- Security Badge -->
            <div class="text-center">
                <div class="inline-flex items-center text-sm text-gray-300">
                    <svg class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Secure Login
                </div>
            </div>
        </div>
    </div>
</body>
</html>
