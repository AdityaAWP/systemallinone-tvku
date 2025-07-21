<div class="mb-6 space-y-6">
    <div class="text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Silakan masuk menggunakan akun magang Anda.
            <a href="/intern/login"
                class="font-medium text-primary-600 underline hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                Masuk Akun Magang
            </a>
        </p>
    </div>

    <div>
        <a href="{{ route('auth.google') }}"
            class="flex w-full items-center justify-center gap-3 rounded-lg border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 shadow-sm transition duration-300 ease-in-out hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M12.24 10.285V14.4h6.806c-.275 1.765-2.056 5.174-6.806 5.174-4.095 0-7.439-3.389-7.439-7.574s3.345-7.574 7.439-7.574c2.33 0 3.891.989 4.785 1.849l3.254-3.138C18.189 1.186 15.479 0 12.24 0c-6.635 0-12 5.365-12 12s5.365 12 12 12c6.926 0 11.52-4.869 11.52-11.726 0-.788-.085-1.39-.189-1.989H12.24z" />
            </svg>
            <span>Login dengan Google</span>
        </a>
    </div>

    <div class="relative">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="bg-white px-2 text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                Atau
            </span>
        </div>
    </div>
</div>