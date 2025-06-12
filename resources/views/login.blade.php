<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ROMS Coffee</title>
    @vite('resources/css/app.css')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body
    class="min-h-screen bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50 flex items-center justify-center p-4 font-['Inter']">

    <!-- Login Container -->
    <div class="relative w-full max-w-md">
        <!-- Main Card -->
        <div
            class="bg-white/80 backdrop-blur-lg rounded-3xl shadow-xl border border-white/20 p-8 relative overflow-hidden">

            <!-- Header -->
            <div class="text-center mb-8">
                <!-- Coffee Cup Icon -->
                <div class="relative inline-block mb-4">
                    <div
                        class="w-20 h-20 rounded-2xl flex items-center justify-center transform hover:scale-105 transition-transform duration-300">
                        <img src="{{ asset('images/beans.png') }}" alt="beans coffee" class="w-16 h-16">
                    </div>
                </div>

                <h1
                    class="text-3xl font-bold bg-gradient-to-r from-[#6F4E37] to-amber-700 bg-clip-text text-transparent mb-2">
                    Selamat Datang!
                </h1>
            </div>

            <!-- Login Form -->
            <form method="POST" action="#" class="space-y-6">
                @csrf

                <!-- Email Field -->
                <div class="space-y-2">
                    <label class="flex items-center text-sm font-medium text-gray-700">
                        <svg class="w-4 h-4 mr-2 text-amber-600" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" />
                        </svg>
                        Email
                    </label>
                    <div class="relative">
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition-all duration-200 text-gray-900 placeholder-gray-500"
                            placeholder="nama@gmail.com">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>
                    </div>
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="space-y-2">
                    <label class="flex items-center text-sm font-medium text-gray-700">
                        <svg class="w-4 h-4 mr-2 text-amber-600" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12,17A2,2 0 0,0 14,15C14,13.89 13.1,13 12,13A2,2 0 0,0 10,15A2,2 0 0,0 12,17M18,8A2,2 0 0,1 20,10V20A2,2 0 0,1 18,22H6A2,2 0 0,1 4,20V10C4,8.89 4.9,8 6,8H7V6A5,5 0 0,1 12,1A5,5 0 0,1 17,6V8H18M12,3A3,3 0 0,0 9,6V8H15V6A3,3 0 0,0 12,3Z" />
                        </svg>
                        Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                            class="w-full px-4 py-3 pr-12 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition-all duration-200 text-gray-900 placeholder-gray-500"
                            placeholder="••••••••">
                        <button type="button" id="togglePassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3  rounded-r-xl transition-colors duration-200">
                            <!-- Eye Icon (Show) -->
                            <svg id="eyeIcon" class="w-5 h-5 text-gray-400 hover:text-gray-600" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <!-- Eye Slash Icon (Hide) -->
                            <svg id="eyeSlashIcon" class="w-5 h-5 text-gray-400 hover:text-gray-600 hidden"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="remember"
                            class="w-4 h-4 text-amber-600 bg-gray-100 border-gray-300 rounded focus:ring-amber-500 focus:ring-2">
                        <span class="text-sm text-gray-600">Ingat saya</span>
                    </label>
                    <a href="#" class="text-sm text-amber-600 hover:text-amber-700 font-medium transition-colors">
                        Lupa password?
                    </a>
                </div>

                <!-- Login Button -->
                <button type="submit"
                    class="w-full bg-[#6F4E37] text-white font-semibold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200 flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M10,17V14H3V10H10V7L15,12L10,17M10,2H19A2,2 0 0,1 21,4V20A2,2 0 0,1 19,22H10A2,2 0 0,1 8,20V18H10V20H19V4H10V6H8V4A2,2 0 0,1 10,2Z" />
                    </svg>
                    <span>Masuk</span>
                </button>
            </form>

            <!-- Register Link -->
            <div class="text-center mt-6">
                <p class="text-gray-600 text-sm">
                    Belum punya akun?
                    <a href="#" class="text-amber-600 hover:text-amber-700 font-medium transition-colors">
                        Daftar sekarang
                    </a>
                </p>
            </div>
        </div>

        <!-- Coffee Quote -->
        <div class="mt-6 text-center">
            <div class="inline-block px-6 py-3 bg-white/60 backdrop-blur-sm rounded-2xl border border-amber-200/50">
                <p class="text-amber-800 text-sm font-medium italic">
                    "Stressed, blessed, and coffee obsessed."
                </p>
            </div>
        </div>
    </div>

    <!-- JavaScript untuk Toggle Password -->
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        const eyeSlashIcon = document.getElementById('eyeSlashIcon');

        togglePassword.addEventListener('click', function() {
            // Toggle password visibility
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Toggle icon visibility
            if (type === 'password') {
                eyeIcon.classList.remove('hidden');
                eyeSlashIcon.classList.add('hidden');
            } else {
                eyeIcon.classList.add('hidden');
                eyeSlashIcon.classList.remove('hidden');
            }
        });
    </script>
</body>

</html>
