@extends('layouts.main')

@section('content')
    <div class="flex-1 flex flex-col overflow-hidden duration-1000">
        <div class="grid grid-cols-3 grid-rows-2 gap-4 p-4">
            <!-- Card 1: Total Pendapatan -->
            <div class="bg-white rounded-2xl p-6 hover:shadow-lg transition-all duration-300 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-500 mb-1">Total Pendapatan</span>
                        <h1 class="font-bold text-2xl text-gray-800">Rp 20.000.000</h1>
                        <div class="mt-2 flex items-center">
                            <span class="text-xs font-medium text-green-500 flex items-center">
                                <i data-lucide="trending-up" class="w-3 h-3 mr-1"></i>
                                +12.5%
                            </span>
                            <span class="text-xs text-gray-500 ml-2">dari bulan lalu</span>
                        </div>
                    </div>
                    <div class="bg-amber-50 p-4 rounded-full">
                        <i data-lucide="hand-coins" class="w-10 h-10 text-amber-500"></i>
                    </div>
                </div>
            </div>

            <!-- Card 2: Total Transaksi -->
            <div class="bg-white rounded-2xl p-6 hover:shadow-lg transition-all duration-300 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-500 mb-1">Total Transaksi</span>
                        <h1 class="font-bold text-2xl text-gray-800">245</h1>
                        <div class="mt-2 flex items-center">
                            <span class="text-xs font-medium text-blue-500 flex items-center">
                                <i data-lucide="trending-up" class="w-3 h-3 mr-1"></i>
                                +8.2%
                            </span>
                            <span class="text-xs text-gray-500 ml-2">dari bulan lalu</span>
                        </div>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-full">
                        <i data-lucide="shopping-cart" class="w-10 h-10 text-blue-500"></i>
                    </div>
                </div>
            </div>

            <!-- Card 3: Total Produk Terjual -->
            <div class="bg-white rounded-2xl p-6 hover:shadow-lg transition-all duration-300 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-500 mb-1">Total Produk Terjual</span>
                        <h1 class="font-bold text-2xl text-gray-800">1,250</h1>
                        <div class="mt-2 flex items-center">
                            <span class="text-xs font-medium text-green-500 flex items-center">
                                <i data-lucide="trending-up" class="w-3 h-3 mr-1"></i>
                                +15.3%
                            </span>
                            <span class="text-xs text-gray-500 ml-2">dari bulan lalu</span>
                        </div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-full">
                        <i data-lucide="package-check" class="w-10 h-10 text-green-500"></i>
                    </div>
                </div>
            </div>

            <!-- Card 4: Total Pelanggan -->
            <div class="bg-white rounded-2xl p-6 hover:shadow-lg transition-all duration-300 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-500 mb-1">Total Pelanggan</span>
                        <h1 class="font-bold text-2xl text-gray-800">124</h1>
                        <div class="mt-2 flex items-center">
                            <span class="text-xs font-medium text-purple-500 flex items-center">
                                <i data-lucide="trending-up" class="w-3 h-3 mr-1"></i>
                                +3.7%
                            </span>
                            <span class="text-xs text-gray-500 ml-2">dari bulan lalu</span>
                        </div>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-full">
                        <i data-lucide="users" class="w-10 h-10 text-purple-500"></i>
                    </div>
                </div>
            </div>

            <!-- Card 5: Total Produk Tersedia -->
            <div class="bg-white rounded-2xl p-6 hover:shadow-lg transition-all duration-300 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-500 mb-1">Total Produk Tersedia</span>
                        <h1 class="font-bold text-2xl text-gray-800">45</h1>
                        <div class="mt-2 flex items-center">
                            <span class="text-xs font-medium text-teal-500 flex items-center">
                                <i data-lucide="check-circle" class="w-3 h-3 mr-1"></i>
                                Stok tersedia
                            </span>
                        </div>
                    </div>
                    <div class="bg-teal-50 p-4 rounded-full">
                        <i data-lucide="package" class="w-10 h-10 text-teal-500"></i>
                    </div>
                </div>
            </div>

            <!-- Card 6: Total Produk Kehabisan Stok -->
            <div class="bg-white rounded-2xl p-6 hover:shadow-lg transition-all duration-300 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-500 mb-1">Produk Kehabisan Stok</span>
                        <h1 class="font-bold text-2xl text-gray-800">8</h1>
                        <div class="mt-2 flex items-center">
                            <span class="text-xs font-medium text-red-500 flex items-center">
                                <i data-lucide="alert-circle" class="w-3 h-3 mr-1"></i>
                                Perlu restock
                            </span>
                        </div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-full">
                        <i data-lucide="package-x" class="w-10 h-10 text-red-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="mt-2 p-4">
            <!-- Row 1: Monthly Revenue & Top Products -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <!-- Monthly Revenue Chart -->
                <div class="bg-white rounded-2xl p-6 hover:shadow-lg transition-all duration-300 border border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Pendapatan Bulanan</h2>
                    <div class="h-64">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Top Products Chart -->
                <div class="bg-white rounded-2xl p-6 hover:shadow-lg transition-all duration-300 border border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Produk Terlaris</h2>
                    <div class="h-64">
                        <canvas id="topProductsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Row 2: Payment Methods, Product Categories, Customer Segmentation -->
            <div class="grid grid-cols-3 gap-4">
                <!-- Payment Methods Chart -->
                <div class="bg-white rounded-2xl p-6 hover:shadow-lg transition-all duration-300 border border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Metode Pembayaran</h2>
                    <div class="h-64">
                        <canvas id="paymentMethodsChart"></canvas>
                    </div>
                </div>

                <!-- Product Categories Chart -->
                <div class="bg-white rounded-2xl p-6 hover:shadow-lg transition-all duration-300 border border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Kategori Produk Terlaris</h2>
                    <div class="h-64">
                        <canvas id="productCategoriesChart"></canvas>
                    </div>
                </div>

                <!-- Customer Segmentation Chart -->
                <div class="bg-white rounded-2xl p-6 hover:shadow-lg transition-all duration-300 border border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Segmentasi Pelanggan</h2>
                    <div class="h-64">
                        <canvas id="customerSegmentationChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <!-- Initialize Charts -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // 1. Monthly Revenue Chart (Line Chart)
                const revenueCtx = document.getElementById('revenueChart').getContext('2d');
                const revenueChart = new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov',
                            'Dec'
                        ],
                        datasets: [{
                            label: 'Pendapatan (Rp)',
                            data: [12500000, 14200000, 13800000, 15700000, 16300000, 15900000, 17200000,
                                18500000, 19100000, 18700000, 19800000, 20000000
                            ],
                            backgroundColor: 'rgba(253, 230, 138, 0.2)',
                            borderColor: 'rgb(251, 191, 36)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp ' + (value / 1000000) + ' jt';
                                    }
                                }
                            }
                        }
                    }
                });

                // 2. Top Products Chart (Bar Chart)
                const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
                const topProductsChart = new Chart(topProductsCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Arabica Premium', 'Robusta Gold', 'Luwak Special', 'Aceh Gayo',
                            'Toraja Blend'
                        ],
                        datasets: [{
                            label: 'Unit Terjual',
                            data: [420, 375, 290, 245, 210],
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.7)',
                                'rgba(59, 130, 246, 0.6)',
                                'rgba(59, 130, 246, 0.5)',
                                'rgba(59, 130, 246, 0.4)',
                                'rgba(59, 130, 246, 0.3)'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });

                // 3. Payment Methods Chart (Pie Chart)
                const paymentMethodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
                const paymentMethodsChart = new Chart(paymentMethodsCtx, {
                    type: 'pie',
                    data: {
                        labels: ['QRIS', 'Transfer Bank', 'Kartu Kredit', 'Tunai', 'E-Wallet'],
                        datasets: [{
                            data: [35, 25, 20, 15, 5],
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.7)',
                                'rgba(16, 185, 129, 0.7)',
                                'rgba(236, 72, 153, 0.7)',
                                'rgba(245, 158, 11, 0.7)',
                                'rgba(139, 92, 246, 0.7)'
                            ],
                            borderWidth: 1,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                align: 'center'
                            }
                        }
                    }
                });

                // 4. Product Categories Chart (Donut Chart)
                const productCategoriesCtx = document.getElementById('productCategoriesChart').getContext('2d');
                const productCategoriesChart = new Chart(productCategoriesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Arabica', 'Robusta', 'Blend', 'Single Origin', 'Luwak'],
                        datasets: [{
                            data: [40, 25, 15, 12, 8],
                            backgroundColor: [
                                'rgba(16, 185, 129, 0.7)',
                                'rgba(14, 165, 233, 0.7)',
                                'rgba(168, 85, 247, 0.7)',
                                'rgba(251, 191, 36, 0.7)',
                                'rgba(239, 68, 68, 0.7)'
                            ],
                            borderWidth: 1,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'right',
                                align: 'center'
                            }
                        }
                    }
                });

                // 5. Customer Segmentation Chart (Donut Chart)
                const customerSegmentationCtx = document.getElementById('customerSegmentationChart').getContext('2d');
                const customerSegmentationChart = new Chart(customerSegmentationCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Member', 'Non-member', 'Reseller', 'Corporate'],
                        datasets: [{
                            data: [45, 30, 15, 10],
                            backgroundColor: [
                                'rgba(139, 92, 246, 0.7)',
                                'rgba(59, 130, 246, 0.7)',
                                'rgba(236, 72, 153, 0.7)',
                                'rgba(251, 191, 36, 0.7)'
                            ],
                            borderWidth: 1,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'right',
                                align: 'center'
                            }
                        }
                    }
                });
            });
        </script>
    </div>
@endsection
