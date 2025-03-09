<div class="mt-4">
    <div class="relative">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="bg-white dark:bg-gray-900 px-2 text-gray-500 dark:text-gray-400">
                Atau login dengan
            </span>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('auth.google') }}"
            class="w-full flex items-center justify-center gap-3 py-3 px-5 border border-gray-300 dark:border-gray-700 rounded-lg shadow-sm dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-200 transition duration-300 ease-in-out dark:hover:bg-gray-700 hover:bg-white">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M12.24 10.285V14.4h6.806c-.275 1.765-2.056 5.174-6.806 5.174-4.095 0-7.439-3.389-7.439-7.574s3.345-7.574 7.439-7.574c2.33 0 3.891.989 4.785 1.849l3.254-3.138C18.189 1.186 15.479 0 12.24 0c-6.635 0-12 5.365-12 12s5.365 12 12 12c6.926 0 11.52-4.869 11.52-11.726 0-.788-.085-1.39-.189-1.989H12.24z" />
            </svg>
            <span>Login dengan Google</span>
        </a>
    </div>
</div>