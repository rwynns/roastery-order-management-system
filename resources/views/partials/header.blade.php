<header class="flex items-center justify-between lg:ml-[150px] p-4 bg-coffee-light shadow mb-6 z-[60]">
    <div class="flex items-center">
        <h1 class="font-Poppins text-xl">ss</h1>
    </div>
    <div class="flex items-center">
        <button class="p-2 text-gray-500 hover:text-gray-700">
            <i class="fas fa-bell">
            </i>
        </button>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="size-6 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
        </svg>
        <form method="POST" action="#" class="inline">
            @csrf
            <button type="submit" class="font-Poppins text-lg">
                Sign Out
            </button>
        </form>
    </div>
</header>
