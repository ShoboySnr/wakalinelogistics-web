<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Metter 1.0 | Waka Line Logistics</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-P45WNKMZ20"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-P45WNKMZ20');
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
    <script>
        (function() {
            emailjs.init('FQ1fbEP10MYWg6Lwx');
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <style>
        [x-cloak] { display: none !important; }
        * { font-family: Tahoma, sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, #2F3437 0%, #1a1d1f 100%);
        }
        .input-focus:focus {
            border-color: #C1666B;
            box-shadow: 0 0 0 3px rgba(193, 102, 107, 0.1);
        }
        
        #formContentScroll::-webkit-scrollbar {
            width: 8px;
        }
        
        #formContentScroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        #formContentScroll::-webkit-scrollbar-thumb {
            background: #C1666B;
            border-radius: 10px;
        }
        
        #formContentScroll::-webkit-scrollbar-thumb:hover {
            background: #a85559;
        }
        
        #formContentScroll {
            scrollbar-width: thin;
            scrollbar-color: #C1666B #f1f1f1;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#C1666B',
                        secondary: '#2F3437',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white min-h-screen">
    <div class="min-h-screen">
        <div class="gradient-bg py-4 px-4 sm:px-6 lg:px-8">
            <div class="max-w-5xl mx-auto">
                <div class="flex items-center justify-center mb-8">
                    <a href="{{ route('home') }}">
                        <img src="{{ asset('assets/img/wakalinelogistics-logo-white.png') }}" alt="Wakaline Logistics" class="h-12 cursor-pointer hover:opacity-90 transition-opacity">
                    </a>
                </div>
                <div class="text-white mb-10 mt-10 text-center">
                    <h1 class="text-3xl sm:text-4xl font-bold mb-3 flex items-center justify-center gap-3">
                        <span>Metter 1.0</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500 text-gray-900 animate-pulse">
                            BETA
                        </span>
                    </h1>
                    <span class="block text-lg font-normal text-gray-300 mb-3">Smart Bike Courier Delivery Fee Calculator</span>
                    <p class="text-gray-300 text-base sm:text-lg max-w-2xl mx-auto">
                        Our intelligent system instantly calculates your bike courier delivery fee anywhere in Lagos using advanced zone detection.
                    </p>
                    <div class="mt-4 inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full">
                        <span class="text-sm font-medium text-gray-200">Bike Courier Services</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 pb-16">
            <div x-data="deliveryCalculator()" class="bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden">
                <div class="p-6 sm:p-8">
                    <form @submit.prevent="calculateDelivery" class="space-y-5">
                        <div class="grid md:grid-cols-2 gap-5">
                            <div>
                                <label for="pickup_address" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Pickup Location
                                </label>
                                <input 
                                    type="text" 
                                    id="pickup_address"
                                    x-model="pickupAddress"
                                    placeholder="e.g., Ikeja, Ogba, Lekki Phase 1"
                                    class="w-full px-4 py-3.5 border border-gray-300 rounded-lg input-focus outline-none transition-all text-gray-900 placeholder-gray-400"
                                    required
                                    minlength="3"
                                >
                            </div>

                            <div>
                                <label for="delivery_address" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Dropoff Location
                                </label>
                                <input 
                                    type="text" 
                                    id="delivery_address"
                                    x-model="deliveryAddress"
                                    placeholder="e.g., Lekki Phase 1, Surulere, Victoria Island"
                                    class="w-full px-4 py-3.5 border border-gray-300 rounded-lg input-focus outline-none transition-all text-gray-900 placeholder-gray-400"
                                    required
                                    minlength="3"
                                >
                            </div>
                        </div>

                        <button 
                            type="submit"
                            :disabled="loading"
                            class="w-full bg-primary hover:bg-opacity-90 text-white font-semibold py-4 px-6 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-primary/20"
                        >
                            <span x-show="!loading">Calculate with Metter 1.0</span>
                            <span x-show="loading" class="flex items-center justify-center">
                                <svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Checking price, hold on...
                            </span>
                        </button>
                    </form>

                    <div x-show="error" x-cloak class="mt-5 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start space-x-2">
                            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-red-700 text-sm font-medium" x-text="error"></p>
                                <p class="text-red-600 text-xs mt-1">💡 Tip: Use area names like "Ikeja", "Lekki Phase 1", "Surulere", etc.</p>
                            </div>
                        </div>
                    </div>
                </div>


                <div x-show="result" x-cloak class="border-t border-gray-100 p-6 sm:p-8 bg-gray-50">
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-1">Your Metter Reading</h2>
                        <p class="text-sm text-gray-500">Metter 1.0 has analyzed your route and calculated the optimal bike courier delivery fee</p>
                    </div>
                    
                    <div class="space-y-4 mb-6">
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 mt-1">
                                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-500 mb-1">Pickup</p>
                                    <p class="text-sm font-medium text-gray-900 break-words overflow-wrap-anywhere" x-text="result?.pickup || ''"></p>
                                    <p class="text-xs text-primary mt-0.5" x-text="result?.pickup_zone"></p>
                                </div>
                            </div>
                            <div class="my-3 border-l-2 border-dashed border-gray-300 ml-2 h-6"></div>
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 mt-1">
                                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-500 mb-1">Dropoff</p>
                                    <p class="text-sm font-medium text-gray-900 break-words overflow-wrap-anywhere" x-text="result?.delivery || ''"></p>
                                    <p class="text-xs text-primary mt-0.5" x-text="result?.delivery_zone"></p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <p class="text-xs font-medium text-gray-500 mb-1">Distance</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <span x-text="result?.distance_km"></span><span class="text-base ml-1">km</span>
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-primary to-primary/80 rounded-lg p-4 text-white">
                                <p class="text-xs font-medium text-white/80 mb-1">Delivery Fee</p>
                                <p class="text-2xl font-bold">
                                    ₦<span x-text="formatPrice(result?.delivery_fee)"></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <a 
                            href="https://wa.me/2348100665758?text=Hi,%20I%20want%20to%20book%20a%20delivery"
                            target="_blank"
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-4 px-6 rounded-lg transition-all flex items-center justify-center space-x-2 shadow-lg">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                            <span>Book via WhatsApp</span>
                        </a>
                        
                        <button 
                            type="button"
                            onclick="openOrderFormModal();"
                            class="w-full bg-primary hover:bg-opacity-90 text-white font-semibold py-4 px-6 rounded-lg transition-all flex items-center justify-center space-x-2 shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Fill Order Form</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-8 bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Delivery Zones</h3>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="font-semibold text-primary mb-1">Zone A - Mainland (Ikeja/Alimosho)</p>
                        <p class="text-gray-600 text-xs">Ikeja, Alausa, Allen, Oregun, Agege, Ogba, Omole, Iju, Abule Egba, Egbeda, Idimu, Ikotun, Igando, Ayobo, Ipaja, Command, Alagbado, Dopemu, Akute, Lambe, Kay Farms</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="font-semibold text-primary mb-1">Zone B - Mainland (Southwest)</p>
                        <p class="text-gray-600 text-xs">Ketu, Ojota, Maryland, Gbagada, Ogudu, Magodo, Anthony, Palmgroove, Shomolu, Bariga, Ilupeju, Mile 12, Alapere, Kosofe, Fadeyi, Jibowu, Onipanu</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="font-semibold text-primary mb-1">Zone C - Mainland (Central/East)</p>
                        <p class="text-gray-600 text-xs">Yaba, Surulere, Isolo, Festac, Oshodi, Mushin, Apapa, Ajegunle, Ebute Metta, Satellite Town, Alaba, Badagry, Ejigbo, Okota, Ago Palace, Ojuelegba, Lawanson, Costain, Okokomaiko</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="font-semibold text-primary mb-1">Zone D - Island (VI/Ikoyi/Lagos Island)</p>
                        <p class="text-gray-600 text-xs">Ikoyi, Victoria Island, Lagos Island, Marina, CMS, Adeola Odeku, Ozumba Mbadiwe, Bourdillon, Banana Island, Parkview, Obalende, Falomo, Balogun, Idumota, Tinubu</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="font-semibold text-primary mb-1">Zone E - Island (Lekki/Eti-Osa)</p>
                        <p class="text-gray-600 text-xs">Lekki, Ajah, Sangotedo, VGC, Chevron, Ikate, Oniru, Osapa London, Ikota, Elegushi, Epe, Awoyaya, Bogije, Alpha Beach, Ilasan, Igbo Efon, Lakowe, Dangote Refinery, Ibeju-Lekki</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="font-semibold text-primary mb-1">Zone F - Interstate (Ikorodu/Ogun)</p>
                        <p class="text-gray-600 text-xs">Ikorodu, Mowe, Sango Ota, Agbara, Arepo, Redemption Camp, Sagamu, Ogijo, Imota, Ibafo, Magboro, Ojodu, Igbogbo, Ijede, Berger, Kara, Simawa, Lusada</p>
                    </div>
                </div>
            </div>

            <div class="mt-8 bg-gradient-to-br from-secondary to-secondary/90 rounded-xl shadow-lg p-8 text-white">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h3 class="text-2xl font-bold mb-2">Integrate Metter 1.0 API <span class="text-sm font-normal text-primary bg-white/20 px-2 py-1 rounded">v1.0</span></h3>
                        <p class="text-gray-300">Add smart real-time bike courier delivery pricing to your ecommerce platform</p>
                    </div>
                    <svg class="w-12 h-12 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </div>

                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white/10 backdrop-blur rounded-lg p-5">
                        <h4 class="font-semibold text-lg mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 7H7v6h6V7z"/><path fill-rule="evenodd" d="M7 2a1 1 0 012 0v1h2V2a1 1 0 112 0v1h2a2 2 0 012 2v2h1a1 1 0 110 2h-1v2h1a1 1 0 110 2h-1v2a2 2 0 01-2 2h-2v1a1 1 0 11-2 0v-1H9v1a1 1 0 11-2 0v-1H5a2 2 0 01-2-2v-2H2a1 1 0 110-2h1V9H2a1 1 0 010-2h1V5a2 2 0 012-2h2V2zM5 5h10v10H5V5z" clip-rule="evenodd"/>
                            </svg>
                            Quick Quote API
                        </h4>
                        <p class="text-sm text-gray-300 mb-3">Get instant delivery prices for checkout</p>
                        <div class="bg-secondary/50 rounded p-3 font-mono text-xs overflow-x-auto">
                            <span class="text-green-400">POST</span> <span class="text-gray-300">/api/wakalinelogistics/v1/metter/quote</span>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur rounded-lg p-5">
                        <h4 class="font-semibold text-lg mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            Full Details API
                        </h4>
                        <p class="text-sm text-gray-300 mb-3">Get complete delivery information with zones</p>
                        <div class="bg-secondary/50 rounded p-3 font-mono text-xs overflow-x-auto">
                            <span class="text-green-400">POST</span> <span class="text-gray-300">/api/wakalinelogistics/v1/metter/calculate</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur rounded-lg p-5 mb-6">
                    <h4 class="font-semibold mb-3">Example Request</h4>
                    <pre class="bg-secondary/50 rounded p-4 text-xs overflow-x-auto"><code class="text-green-300">{
  "pickup_address": "Ikeja, Lagos",
  "dropoff_address": "Lekki, Lagos"
}</code></pre>
                    <h4 class="font-semibold mt-4 mb-3">Example Response</h4>
                    <pre class="bg-secondary/50 rounded p-4 text-xs overflow-x-auto"><code class="text-blue-300">{
  "success": true,
  "delivery_fee": 6500,
  "distance_km": 34.5,
  "currency": "NGN"
}</code></pre>
                </div>

                <div class="grid md:grid-cols-3 gap-4 mb-6">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary mb-1">400+</div>
                        <div class="text-sm text-gray-300">Lagos Locations</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary mb-1">Real-time</div>
                        <div class="text-sm text-gray-300">Price Calculation</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary mb-1">6 Zones</div>
                        <div class="text-sm text-gray-300">Coverage Areas</div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="https://documenter.getpostman.com/view/53123366/2sBXiestyS" 
                       target="_blank"
                       class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 px-6 rounded-lg transition-all text-center flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M13.527.099C6.955-.744.942 3.9.099 10.473c-.843 6.572 3.8 12.584 10.373 13.428 6.573.843 12.587-3.801 13.428-10.374C24.744 6.955 20.101.943 13.527.099zm2.471 7.485a.855.855 0 0 0-.593.25l-4.453 4.453-.307-.307-.643-.643c4.389-4.376 5.18-4.418 5.996-3.753zm-4.863 4.861l4.44-4.44a.62.62 0 1 1 .847.903l-4.699 4.125-.588-.588zm.33.694l-1.1.238a.06.06 0 0 1-.067-.032.06.06 0 0 1 .01-.073l.645-.645.512.512zm-2.803-.459l1.172-1.172.879.878-1.979.426a.074.074 0 0 1-.085-.039.072.072 0 0 1 .013-.093zm-3.646 6.058a.076.076 0 0 1-.069-.083.077.077 0 0 1 .022-.046h.002l.946-.946 1.222 1.222-2.123-.147zm2.425-1.256a.228.228 0 0 0-.117.256l.203.865a.125.125 0 0 1-.211.117h-.003l-.934-.934-.294-.295 3.762-3.758 1.82-.393.874.874c-1.255 1.102-2.971 2.201-5.1 3.268zm5.279-3.428h-.002l-.839-.839 4.699-4.125a.952.952 0 0 0 .119-.127c-.148 1.345-2.029 3.245-3.977 5.091zm3.657-6.46l-.003-.002a1.822 1.822 0 0 1 2.459-2.684l-1.61 1.613a.119.119 0 0 0 0 .169l1.247 1.247a1.817 1.817 0 0 1-2.093-.343zm2.578 0a1.714 1.714 0 0 1-.271.218h-.001l-1.207-1.207 1.533-1.533c.661.72.637 1.832-.054 2.522zM18.855 6.05a.143.143 0 0 0-.053.157.416.416 0 0 1-.053.45.14.14 0 0 0 .023.197.141.141 0 0 0 .084.03.14.14 0 0 0 .106-.05.691.691 0 0 0 .087-.751.138.138 0 0 0-.194-.033z"/>
                        </svg>
                        <span>View API Documentation</span>
                    </a>
                    <a href="mailto:mywakawakalogistics@gmail.com?subject=Metter API Access Request" 
                       class="flex-1 bg-white/20 hover:bg-white/30 text-white font-semibold py-3 px-6 rounded-lg transition-all text-center flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        <span>Request Access</span>
                    </a>
                </div>
            </div>

            <div :class="showBookingModal ? 'flex' : 'hidden'" 
                 @click.self="showBookingModal = false"
                 class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center p-4 z-50">
                <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-8 relative">
                    <button @click="showBookingModal = false" class="absolute top-4 right-4 text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Book Your Delivery</h3>
                    <p class="text-gray-600 mb-6">Choose how you'd like to place your order</p>
                    
                    <div class="space-y-4">
                        <button 
                            @click="showBookingModal = false; showOrderForm = true"
                            class="w-full bg-primary hover:bg-opacity-90 text-white font-semibold py-4 px-6 rounded-lg transition-all flex items-center justify-center space-x-3 shadow-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Fill Order Form</span>
                        </button>
                        
                        <a 
                            href="https://wa.me/2348100665758?text=Hi,%20I%20want%20to%20make%20a%20delivery%20order"
                            target="_blank"
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-4 px-6 rounded-lg transition-all flex items-center justify-center space-x-3 shadow-lg">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                            <span>Book via WhatsApp</span>
                        </a>
                    </div>
                </div>
            </div>

            <div id="orderFormModal"
                 onclick="if(event.target === this) { closeOrderFormModal(); }"
                 class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center p-4"
                 style="z-index: 9999; overflow-y: hidden;">
                <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full my-8 relative flex flex-col" style="max-height: 90vh;">
                    <button onclick="closeOrderFormModal();" class="absolute top-4 right-4 text-white z-10">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <div class="bg-gradient-to-r from-primary to-primary/80 text-white p-6 rounded-t-xl">
                        <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                            <span>Place a Delivery Order</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-500 text-gray-900">
                                BETA
                            </span>
                        </h2>
                        <div class="flex items-center justify-between">
                            <div id="step1Indicator" class="flex items-center space-x-2 text-white">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center bg-white text-primary">1</div>
                                <span class="text-sm font-medium">Pickup</span>
                            </div>
                            <div id="line1" class="flex-1 h-0.5 mx-2 bg-white/30"></div>
                            <div id="step2Indicator" class="flex items-center space-x-2 text-white/50">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center bg-white/30">2</div>
                                <span class="text-sm font-medium">Dropoff</span>
                            </div>
                            <div id="line2" class="flex-1 h-0.5 mx-2 bg-white/30"></div>
                            <div id="step3Indicator" class="flex items-center space-x-2 text-white/50">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center bg-white/30">3</div>
                                <span class="text-sm font-medium">Package</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto" style="max-height: calc(90vh - 200px);" id="formContentScroll">
                        <form id="orderForm" class="p-6">
                        <div data-step="1" class="space-y-4" style="display: block;">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Pickup Information</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                                <input type="text" x-model="orderForm.senderName" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Full name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Your Phone Number</label>
                                <input type="tel" x-model="orderForm.senderPhone" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="e.g. 08100000000">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Your Email</label>
                                <input type="email" x-model="orderForm.senderEmail" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="email@example.com">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pickup Address</label>
                                <textarea x-model="orderForm.pickupAddress" required rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Full address where we pick up the package"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Area / Landmark</label>
                                <input type="text" x-model="orderForm.pickupArea" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="e.g. Ikeja, near City Mall">
                            </div>
                        </div>

                        <div data-step="2" class="space-y-4" style="display: none;">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Dropoff Information</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Recipient's Name</label>
                                <input type="text" x-model="orderForm.recipientName" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Full name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Recipient's Phone Number</label>
                                <input type="tel" x-model="orderForm.recipientPhone" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="e.g. 08100000000">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dropoff Address</label>
                                <textarea x-model="orderForm.deliveryAddress" required rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Full address where the package should be delivered"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Area / Landmark</label>
                                <input type="text" x-model="orderForm.deliveryArea" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="e.g. Lekki Phase 1, near Shoprite">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Any Other Information (optional)</label>
                                <textarea x-model="orderForm.deliveryNotes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="e.g. Gate code, call before delivery, fragile item, etc."></textarea>
                            </div>
                        </div>

                        <div data-step="3" class="space-y-4" style="display: none;">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Package Details</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">What are you sending?</label>
                                <input type="text" x-model="orderForm.packageDescription" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="e.g. Documents, Food, Electronics">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Package Size</label>
                                <select x-model="orderForm.packageSize" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Select size</option>
                                    <option value="Small (fits in hand)">Small (fits in hand)</option>
                                    <option value="Medium (fits in a bag)">Medium (fits in a bag)</option>
                                    <option value="Large (needs a box)">Large (needs a box)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Pickup Time</label>
                                <select x-model="orderForm.preferredTime" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Select time</option>
                                    <option value="Morning (8am - 11am)">Morning (8am - 11am)</option>
                                    <option value="Midday (11am - 2pm)">Midday (11am - 2pm)</option>
                                    <option value="Afternoon (2pm - 5pm)">Afternoon (2pm - 5pm)</option>
                                    <option value="As soon as possible">As soon as possible</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes (optional)</label>
                                <textarea x-model="orderForm.additionalNotes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Any special instructions"></textarea>
                            </div>
                            <div id="orderFormPricing" class="bg-primary/10 border border-primary/20 rounded-lg p-4">
                                <div id="pricingLoading" class="text-center" style="display: none;">
                                    <p class="text-sm text-gray-600">Checking price, hold on...</p>
                                </div>
                                <div id="pricingResult" style="display: none;">
                                    <div class="space-y-3 mb-3">
                                        <div>
                                            <p class="text-xs text-gray-500 mb-1">Pickup Address:</p>
                                            <p id="formPickupAddress" class="text-sm font-medium text-gray-900"></p>
                                            <p id="formPickupZone" class="text-xs text-primary mt-0.5"></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 mb-1">Dropoff Address:</p>
                                            <p id="formDropoffAddress" class="text-sm font-medium text-gray-900"></p>
                                            <p id="formDropoffZone" class="text-xs text-primary mt-0.5"></p>
                                        </div>
                                    </div>
                                    <div class="border-t border-primary/20 pt-3">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="text-center">
                                                <p class="text-xs text-gray-500 mb-1">Distance</p>
                                                <p id="formDistance" class="text-lg font-bold text-gray-900"><span id="formDistanceValue"></span> km</p>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-xs text-gray-500 mb-1">Delivery Fee</p>
                                                <p id="formDeliveryFee" class="text-lg font-bold text-primary"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-3 border-t border-primary/20">
                                        <p class="text-xs text-gray-500 text-center mx-auto" style="max-width: 80%;">
                                            <svg class="w-3 h-3 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                            Metter 1.0 uses intelligent zone detection and the closest landmark or bus stop to calculate your delivery fee.
                                        </p>
                                    </div>
                                </div>
                                <div id="pricingPlaceholder" class="text-center">
                                    <p class="text-sm text-gray-600">Enter pickup and dropoff addresses to see pricing</p>
                                    <p class="text-xs text-gray-500 mt-2">⚠️ Price must be calculated before submitting order</p>
                                </div>
                            </div>
                        </div>

                        <div id="orderFormSuccessMessage" class="text-center py-8" style="display: none;">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Order Placed Successfully!</h3>
                            <p class="text-gray-600 mb-6">We've received your delivery order. A confirmation email has been sent to you. Our team will contact you shortly to confirm pickup.</p>
                            <button type="button" onclick="closeOrderFormModal()" class="bg-primary hover:bg-opacity-90 text-white font-semibold py-3 px-8 rounded-lg transition-all">Done</button>
                        </div>

                        </form>
                    </div>

                    <div class="border-t bg-white rounded-b-xl">
                        <div id="formActions" class="flex items-center justify-between p-6">
                            <button type="button" id="formBackBtn" onclick="prevFormStep()" class="px-6 py-2 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Back
                            </button>
                            <button type="button" id="formNextBtn" onclick="nextFormStep()" class="px-6 py-2 bg-primary hover:bg-opacity-90 text-white font-semibold rounded-lg transition-all">
                                Next
                            </button>
                            <button type="submit" form="orderForm" id="formSubmitBtn" disabled class="px-6 py-2 bg-primary hover:bg-opacity-90 text-white font-semibold rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed" style="display: none;">
                                <span id="submitBtnText" class="flex items-center justify-center">Submit Order</span>
                                <span id="submitBtnLoading" class="flex items-center justify-center" style="display: none;">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Submitting...
                                </span>
                            </button>
                        </div>

                        <div id="orderFormError" class="hidden mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-red-800 mb-1">Email Service Unavailable</h4>
                                    <p id="orderFormErrorMessage" class="text-sm text-red-700"></p>
                                    <div class="mt-3 flex items-center space-x-4">
                                        <a href="https://wa.me/2348100665758" target="_blank" class="inline-flex items-center text-sm font-medium text-red-800 hover:text-red-900">
                                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                            </svg>
                                            Contact via WhatsApp
                                        </a>
                                        <button type="button" onclick="document.getElementById('orderFormError').classList.add('hidden')" class="text-sm font-medium text-red-800 hover:text-red-900">
                                            Dismiss
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button 
        id="feedbackBtn"
        onclick="openFeedbackForm()"
        class="fixed bottom-6 right-6 bg-primary hover:bg-opacity-90 text-white font-semibold py-3 px-5 rounded-full shadow-2xl transition-all hover:scale-105 flex items-center gap-2 z-50 group"
        style="z-index: 9998;">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
        </svg>
        <span class="hidden sm:inline">Feedback</span>
    </button>

    <script>
        function deliveryCalculator() {
            return {
                pickupAddress: '',
                deliveryAddress: '',
                loading: false,
                error: null,
                result: null,
                showBookingModal: false,
                showOrderForm: false,
                formStep: 1,
                orderFormSuccess: false,
                orderFormSubmitting: false,
                isPriceCalculated: false,
                orderForm: {
                    senderName: '',
                    senderPhone: '',
                    senderEmail: '',
                    pickupAddress: '',
                    pickupArea: '',
                    recipientName: '',
                    recipientPhone: '',
                    deliveryAddress: '',
                    deliveryArea: '',
                    deliveryNotes: '',
                    packageDescription: '',
                    packageSize: '',
                    preferredTime: '',
                    additionalNotes: ''
                },

                init() {
                    this.initAutocomplete('pickup_address');
                    this.initAutocomplete('delivery_address');
                },

                initAutocomplete(elementId) {
                    const input = document.getElementById(elementId);
                    const lagosBounds = new google.maps.LatLngBounds(
                        new google.maps.LatLng(6.3876, 3.0982),
                        new google.maps.LatLng(6.7027, 3.6964)
                    );
                    
                    const autocomplete = new google.maps.places.Autocomplete(input, {
                        bounds: lagosBounds,
                        strictBounds: true,
                        componentRestrictions: { country: 'ng' },
                        fields: ['formatted_address', 'geometry', 'name', 'address_components']
                    });

                    autocomplete.addListener('place_changed', () => {
                        const place = autocomplete.getPlace();
                        if (place.formatted_address) {
                            if (elementId === 'pickup_address') {
                                this.pickupAddress = place.formatted_address;
                            } else if (elementId === 'delivery_address') {
                                this.deliveryAddress = place.formatted_address;
                            }
                        }
                    });
                },

                async calculateDelivery() {
                    this.loading = true;
                    this.error = null;
                    this.result = null;

                    try {
                        const response = await fetch('/metter/calculate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                pickup_address: this.pickupAddress,
                                dropoff_address: this.deliveryAddress
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.result = data.data;
                        } else {
                            this.error = data.message || 'Failed to calculate delivery price';
                        }
                    } catch (error) {
                        this.error = 'An error occurred. Please try again.';
                        console.error('Error:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                formatPrice(price) {
                    return price ? price.toLocaleString() : '0';
                },

                closeOrderForm() {
                    this.showOrderForm = false;
                    this.formStep = 1;
                    this.orderFormSuccess = false;
                    this.orderFormSubmitting = false;
                    this.resetOrderForm();
                },

                resetOrderForm() {
                    this.orderForm = {
                        senderName: '',
                        senderPhone: '',
                        senderEmail: '',
                        pickupAddress: '',
                        pickupArea: '',
                        recipientName: '',
                        recipientPhone: '',
                        deliveryAddress: '',
                        deliveryArea: '',
                        deliveryNotes: '',
                        packageDescription: '',
                        packageSize: '',
                        preferredTime: '',
                        additionalNotes: ''
                    };
                },

                async submitOrderForm() {
                    console.log('submitOrderForm called from Alpine - redirecting to vanilla JS handler');
                },

                bookDelivery() {
                    if (!this.result) return;
                    
                    const message = `Hello! I would like to book a delivery:\n\n` +
                        `📍 Pickup: ${this.result.pickup}\n` +
                        `📍 Delivery: ${this.result.delivery}\n` +
                        `📏 Distance: ${this.result.distance_km} km\n` +
                        `💰 Delivery Fee: ₦${this.formatPrice(this.result.delivery_fee)}\n\n` +
                        `Please confirm availability.`;
                    
                    const whatsappUrl = `https://wa.me/2348100665758?text=${encodeURIComponent(message)}`;
                    window.open(whatsappUrl, '_blank');
                },

                closeOrderForm() {
                    this.orderFormSuccess = false;
                    this.orderFormSubmitting = false;
                    this.isPriceCalculated = false;
                    
                    this.orderForm = {
                        senderName: '',
                        senderPhone: '',
                        senderEmail: '',
                        pickupAddress: '',
                        pickupArea: '',
                        recipientName: '',
                        recipientPhone: '',
                        deliveryAddress: '',
                        deliveryArea: '',
                        deliveryNotes: '',
                        packageDescription: '',
                        packageSize: '',
                        preferredTime: '',
                        additionalNotes: ''
                    };
                    
                    document.getElementById('pricingResult').style.display = 'none';
                    document.getElementById('pricingPlaceholder').style.display = 'block';
                }
            }
        }

        let currentFormStep = 1;

        function showFormStep(step) {
            document.querySelectorAll('[data-step]').forEach(el => {
                el.style.display = 'none';
            });
            
            const currentStep = document.querySelector(`[data-step="${step}"]`);
            if (currentStep) {
                currentStep.style.display = 'block';
            }
            
            const step1 = document.getElementById('step1Indicator');
            const step2 = document.getElementById('step2Indicator');
            const step3 = document.getElementById('step3Indicator');
            const line1 = document.getElementById('line1');
            const line2 = document.getElementById('line2');
            
            [step1, step2, step3].forEach(indicator => {
                if (indicator) {
                    indicator.className = 'flex items-center space-x-2 text-white/50';
                    const circle = indicator.querySelector('div');
                    if (circle) {
                        circle.className = 'w-8 h-8 rounded-full flex items-center justify-center bg-white/30';
                    }
                }
            });
            
            if (step >= 1 && step1) {
                step1.className = 'flex items-center space-x-2 text-white';
                const circle1 = step1.querySelector('div');
                if (circle1) circle1.className = 'w-8 h-8 rounded-full flex items-center justify-center bg-white text-primary';
            }
            
            if (step >= 2) {
                if (line1) line1.className = 'flex-1 h-0.5 mx-2 bg-white';
                if (step2) {
                    step2.className = 'flex items-center space-x-2 text-white';
                    const circle2 = step2.querySelector('div');
                    if (circle2) circle2.className = 'w-8 h-8 rounded-full flex items-center justify-center bg-white text-primary';
                }
            } else {
                if (line1) line1.className = 'flex-1 h-0.5 mx-2 bg-white/30';
            }
            
            if (step >= 3) {
                if (line2) line2.className = 'flex-1 h-0.5 mx-2 bg-white';
                if (step3) {
                    step3.className = 'flex items-center space-x-2 text-white';
                    const circle3 = step3.querySelector('div');
                    if (circle3) circle3.className = 'w-8 h-8 rounded-full flex items-center justify-center bg-white text-primary';
                }
            } else {
                if (line2) line2.className = 'flex-1 h-0.5 mx-2 bg-white/30';
            }
            
            const backBtn = document.getElementById('formBackBtn');
            const nextBtn = document.getElementById('formNextBtn');
            const submitBtn = document.getElementById('formSubmitBtn');
            
            if (backBtn) backBtn.disabled = step === 1;
            if (nextBtn) nextBtn.style.display = step < 3 ? 'block' : 'none';
            if (submitBtn) submitBtn.style.display = step === 3 ? 'block' : 'none';
            
            currentFormStep = step;
        }

        function nextFormStep() {
            if (currentFormStep < 3) {
                const currentStepElement = document.querySelector(`[data-step="${currentFormStep}"]`);
                if (currentStepElement) {
                    const requiredFields = currentStepElement.querySelectorAll('[required]');
                    let allValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            allValid = false;
                            field.classList.add('border-red-500');
                            if (allValid === false && !field.classList.contains('focused')) {
                                field.classList.add('focused');
                                field.focus();
                            }
                        } else {
                            field.classList.remove('border-red-500');
                        }
                    });
                    
                    if (!allValid) {
                        return;
                    }
                }
                
                const nextStep = currentFormStep + 1;
                showFormStep(nextStep);
                
                if (nextStep === 3) {
                    setTimeout(() => {
                        calculateOrderFormPrice();
                    }, 100);
                }
            }
        }

        async function calculateOrderFormPrice() {
            const allTextareas = document.querySelectorAll('#orderFormModal textarea');
            let pickupAddress = '';
            let dropoffAddress = '';
            
            console.log('Total textareas found:', allTextareas.length);
            
            allTextareas.forEach((textarea, index) => {
                const model = textarea.getAttribute('x-model');
                const value = textarea.value;
                console.log(`Textarea ${index}:`, { model, value: value.substring(0, 50) });
                
                if (model === 'orderForm.pickupAddress') {
                    pickupAddress = value?.trim() || '';
                } else if (model === 'orderForm.deliveryAddress') {
                    dropoffAddress = value?.trim() || '';
                }
            });
            
            console.log('Final Pickup Address:', pickupAddress);
            console.log('Final Dropoff Address:', dropoffAddress);
            
            if (!pickupAddress || !dropoffAddress) {
                console.log('Missing addresses - showing placeholder');
                document.getElementById('pricingPlaceholder').style.display = 'block';
                document.getElementById('pricingResult').style.display = 'none';
                document.getElementById('pricingLoading').style.display = 'none';
                return;
            }
            
            document.getElementById('pricingPlaceholder').style.display = 'none';
            document.getElementById('pricingResult').style.display = 'none';
            document.getElementById('pricingLoading').style.display = 'block';
            
            try {
                const response = await fetch('/metter/calculate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        pickup_address: pickupAddress,
                        dropoff_address: dropoffAddress
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('formPickupAddress').textContent = pickupAddress;
                    document.getElementById('formPickupZone').textContent = data.data.pickup_zone;
                    document.getElementById('formDropoffAddress').textContent = dropoffAddress;
                    document.getElementById('formDropoffZone').textContent = data.data.delivery_zone;
                    document.getElementById('formDistanceValue').textContent = data.data.distance_km;
                    document.getElementById('formDeliveryFee').textContent = '₦' + data.data.delivery_fee.toLocaleString();
                    
                    document.getElementById('pricingLoading').style.display = 'none';
                    document.getElementById('pricingResult').style.display = 'block';
                    
                    isPriceCalculated = true;
                    document.getElementById('formSubmitBtn').disabled = false;
                } else {
                    throw new Error(data.message || 'Failed to calculate price');
                }
            } catch (error) {
                console.error('Error calculating price:', error);
                document.getElementById('pricingLoading').style.display = 'none';
                document.getElementById('pricingPlaceholder').style.display = 'block';
                document.getElementById('pricingPlaceholder').innerHTML = '<p class="text-sm text-red-600">Unable to calculate price. Please check addresses.</p>';
            }
        }

        function prevFormStep() {
            if (currentFormStep > 1) {
                showFormStep(currentFormStep - 1);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            showFormStep(1);
        });
        
        let isSubmitting = false;
        let isPriceCalculated = false;

        document.addEventListener('DOMContentLoaded', function() {
            const orderForm = document.getElementById('orderForm');
            if (orderForm) {
                orderForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    if (currentFormStep !== 3) {
                        return false;
                    }
                    
                    if (!isPriceCalculated) {
                        alert('Please calculate the delivery price first');
                        return false;
                    }
                    
                    if (isSubmitting) {
                        return false;
                    }
                    
                    await submitOrderFormVanilla();
                    return false;
                });
            }
        });

        async function submitOrderFormVanilla() {
            isSubmitting = true;
            
            document.getElementById('submitBtnText').style.display = 'none';
            document.getElementById('submitBtnLoading').style.display = 'flex';
            document.getElementById('formSubmitBtn').disabled = true;
            
            const errorContainer = document.getElementById('orderFormError');
            if (errorContainer) {
                errorContainer.classList.add('hidden');
            }
            
            try {
                const deliveryFeeElement = document.getElementById('formDeliveryFee');
                const deliveryFee = deliveryFeeElement ? deliveryFeeElement.textContent : '₦3,500';
                
                const senderName = document.querySelector('[x-model="orderForm.senderName"]')?.value || '';
                const senderPhone = document.querySelector('[x-model="orderForm.senderPhone"]')?.value || '';
                const senderEmail = document.querySelector('[x-model="orderForm.senderEmail"]')?.value || '';
                const pickupAddress = document.querySelector('[x-model="orderForm.pickupAddress"]')?.value || '';
                const pickupArea = document.querySelector('[x-model="orderForm.pickupArea"]')?.value || 'Not provided';
                const recipientName = document.querySelector('[x-model="orderForm.recipientName"]')?.value || '';
                const recipientPhone = document.querySelector('[x-model="orderForm.recipientPhone"]')?.value || '';
                const deliveryAddress = document.querySelector('[x-model="orderForm.deliveryAddress"]')?.value || '';
                const deliveryArea = document.querySelector('[x-model="orderForm.deliveryArea"]')?.value || 'Not provided';
                const deliveryNotes = document.querySelector('[x-model="orderForm.deliveryNotes"]')?.value || 'None';
                const packageDescription = document.querySelector('[x-model="orderForm.packageDescription"]')?.value || '';
                const packageSize = document.querySelector('[x-model="orderForm.packageSize"]')?.value || '';
                const preferredTime = document.querySelector('[x-model="orderForm.preferredTime"]')?.value || '';
                const additionalNotes = document.querySelector('[x-model="orderForm.additionalNotes"]')?.value || 'None';
                
                const templateParams = {
                    sender_name: senderName,
                    sender_phone: senderPhone,
                    sender_email: senderEmail,
                    pickup_address: pickupAddress,
                    pickup_area: pickupArea,
                    recipient_name: recipientName,
                    recipient_phone: recipientPhone,
                    delivery_address: deliveryAddress,
                    delivery_area: deliveryArea,
                    delivery_notes: deliveryNotes,
                    package_description: packageDescription,
                    package_size: packageSize,
                    preferred_time: preferredTime,
                    additional_notes: additionalNotes,
                    price: deliveryFee
                };
                
                await emailjs.send('service_ycnmtd9', 'template_tsx6teo', templateParams);
                
                await emailjs.send('service_ycnmtd9', 'template_lpynyqn', templateParams);
                
                showSuccessMessage();
                
            } catch (error) {
                console.error('EmailJS error:', error);
                
                const errorMessage = document.getElementById('orderFormErrorMessage');
                
                if (error.text && error.text.includes('Gmail_API')) {
                    errorMessage.innerHTML = 'Email service is temporarily unavailable due to authentication issues.<br>Your order details have been logged. Please contact us via WhatsApp to complete your order at <strong>+234 810 066 5758</strong>.<br>We apologize for the inconvenience!';
                } else {
                    errorMessage.innerHTML = 'Failed to send order. Please try again or contact us via WhatsApp at <strong>+234 810 066 5758</strong>.';
                }
                
                errorContainer.classList.remove('hidden');
                errorContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } finally {
                isSubmitting = false;
                document.getElementById('submitBtnText').style.display = 'flex';
                document.getElementById('submitBtnLoading').style.display = 'none';
                document.getElementById('formSubmitBtn').disabled = false;
            }
        }

        function showSuccessMessage() {
            document.querySelectorAll('[data-step]').forEach(el => {
                el.style.display = 'none';
            });
            document.getElementById('formActions').style.display = 'none';
            
            document.getElementById('orderFormSuccessMessage').style.display = 'block';
            
            document.getElementById('orderForm').reset();
            
            isPriceCalculated = false;
            
            document.getElementById('pricingResult').style.display = 'none';
            document.getElementById('pricingPlaceholder').style.display = 'block';
        }

        function openOrderFormModal() {
            const pickupInput = document.getElementById('pickup_address');
            const deliveryInput = document.getElementById('delivery_address');
            
            const pickupAddressTextarea = document.querySelector('[x-model="orderForm.pickupAddress"]');
            const deliveryAddressTextarea = document.querySelector('[x-model="orderForm.deliveryAddress"]');
            
            if (pickupInput && pickupInput.value && pickupAddressTextarea) {
                pickupAddressTextarea.value = pickupInput.value;
                pickupAddressTextarea.dispatchEvent(new Event('input', { bubbles: true }));
            }
            
            if (deliveryInput && deliveryInput.value && deliveryAddressTextarea) {
                deliveryAddressTextarea.value = deliveryInput.value;
                deliveryAddressTextarea.dispatchEvent(new Event('input', { bubbles: true }));
            }
            
            document.getElementById('orderFormModal').classList.remove('hidden');
            document.getElementById('orderFormModal').classList.add('flex');
            
            document.body.style.overflow = 'hidden';
        }

        function closeOrderFormModal() {
            document.getElementById('orderFormSuccessMessage').style.display = 'none';
            
            document.getElementById('formActions').style.display = 'flex';
            
            document.getElementById('orderFormModal').classList.add('hidden');
            document.getElementById('orderFormModal').classList.remove('flex');
            
            document.body.style.overflow = '';
            
            showFormStep(1);
            
            document.getElementById('orderForm').reset();
            isPriceCalculated = false;
        }

        function openFeedbackForm() {
            const tallyFormId = 'Ek1VE2';
            
            window.open(
                `https://tally.so/r/${tallyFormId}`,
                'TallyPopup',
                'width=600,height=700,scrollbars=yes,resizable=yes'
            );
        }
    </script>

    <script async src="https://tally.so/widgets/embed.js"></script>
</body>
</html>
