<div class="md:hidden flex items-center">
    <button onclick="Openbar()" class="p-2 focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="size-6 text-black">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M3.75 5.25h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5" />
        </svg>
    </button>
</div>

<span class="absolute text-white text-4xl top-5 left-4 cursor-pointer lg:hidden" onclick="Openbar()">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
        class="size-6">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M3.75 5.25h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5" />
    </svg>
</span>

<div
    class="sidebar fixed top-0 bottom-0 lg:left-0 left-[-80px] duration-300 w-20 overflow-visible h-screen z-50 bg-[#6F4E37] shadow-xl shadow-black group">
    <div class="text-gray-100 text-xl">
        <div class="relative pb-3 pt-4">
            <div class="text-center">
                <a href="/" class="flex justify-center">
                    <img alt="ROMS" class="w-12 transition-all duration-300 my-2" src="/img/logo-sita-white.png" />
                </a>
            </div>
        </div>
        <hr class="my-5 text-gray-600 mx-2">

        <div class="px-2">
            <!-- Menu Item 1: Beranda -->
            <div class="relative group/menu">
                <a href="/"
                    class="p-3 mt-2 flex items-center justify-center rounded-2xl duration-300 cursor-pointer hover:bg-white relative group/item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6 text-white group-hover/item:text-black">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                </a>

                <!-- Submenu untuk Beranda -->
                <div
                    class="absolute left-20 top-0 bg-white shadow-lg rounded-lg opacity-0 invisible group-hover/menu:opacity-100 group-hover/menu:visible transition-all duration-300 z-50 min-w-[200px]">
                    <div class="flex">
                        <div class="w-1 bg-[#6F4E37]"></div>
                        <div class="py-2 px-4 flex-1">
                            <h3 class="text-[#6F4E37] font-bold text-sm mb-2">Beranda</h3>
                            <a href="/"
                                class="block py-2 px-3 text-gray-700 hover:bg-gray-100 rounded text-sm">Dashboard</a>
                            <a href="#"
                                class="block py-2 px-3 text-gray-700 hover:bg-gray-100 rounded text-sm">Overview</a>
                            <a href="#"
                                class="block py-2 px-3 text-gray-700 hover:bg-gray-100 rounded text-sm">Statistics</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu Item 2: Akun -->
            <div class="relative group/menu">
                <a href="#"
                    class="p-3 mt-2 flex items-center justify-center rounded-2xl duration-300 cursor-pointer hover:bg-white relative group/item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6 text-white group-hover/item:text-black">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </a>

                <!-- Submenu untuk Akun -->
                <div
                    class="absolute left-20 top-0 bg-white shadow-lg rounded-lg opacity-0 invisible group-hover/menu:opacity-100 group-hover/menu:visible transition-all duration-300 z-50 min-w-[200px]">
                    <div class="flex">
                        <div class="w-1 bg-[#6F4E37]"></div>
                        <div class="py-2 px-4 flex-1">
                            <h3 class="text-[#6F4E37] font-bold text-sm mb-2">Akun</h3>
                            <a href="#"
                                class="block py-2 px-3 text-gray-700 hover:bg-gray-100 rounded text-sm">Profil</a>
                            <a href="#"
                                class="block py-2 px-3 text-gray-700 hover:bg-gray-100 rounded text-sm">Pengaturan</a>
                            <a href="#"
                                class="block py-2 px-3 text-gray-700 hover:bg-gray-100 rounded text-sm">Keamanan</a>
                            <a href="#"
                                class="block py-2 px-3 text-gray-700 hover:bg-gray-100 rounded text-sm">Logout</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu Item 3: Postingan -->
            <div class="relative group/menu">
                <a href="#"
                    class="p-3 mt-2 flex items-center justify-center rounded-2xl duration-300 cursor-pointer hover:bg-white relative group/item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6 text-white group-hover/item:text-black">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </a>

                <!-- Submenu untuk Postingan -->
                <div
                    class="absolute left-20 top-0 bg-white shadow-lg rounded-lg opacity-0 invisible group-hover/menu:opacity-100 group-hover/menu:visible transition-all duration-300 z-50 min-w-[200px]">
                    <div class="flex">
                        <div class="w-1 bg-[#6F4E37]"></div>
                        <div class="py-2 px-4 flex-1">
                            <h3 class="text-[#6F4E37] font-bold text-sm mb-2">Postingan</h3>
                            <a href="#"
                                class="block py-2 px-3 text-gray-700 hover:bg-gray-100 rounded text-sm">Semua
                                Postingan</a>
                            <a href="#"
                                class="block py-2 px-3 text-gray-700 hover:bg-gray-100 rounded text-sm">Buat Baru</a>
                            <a href="#"
                                class="block py-2 px-3 text-gray-700 hover:bg-gray-100 rounded text-sm">Draft</a>
                            <a href="#"
                                class="block py-2 px-3 text-gray-700 hover:bg-gray-100 rounded text-sm">Kategori</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function Openbar() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('left-[-80px]');
        sidebar.classList.toggle('left-0');
    }
</script>
