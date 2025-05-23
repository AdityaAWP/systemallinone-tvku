<x-filament-panels::page>
    <div class=" mt-8 bg-white dark:bg-gray-900 rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white mb-4">Profil Saya</h2>

            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <div class="py-3 flex justify-between">
                    <span class="text-gray-800 dark:text-gray-300">Nama</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ Auth::user()->name }}</span>
                </div>
                <div class="py-3 flex justify-between">
                    <span class="text-gray-800 dark:text-gray-300">NPP</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ Auth::user()->npp }}</span>
                </div>
                <div class="py-3 flex justify-between">
                    <span class="text-gray-800 dark:text-gray-300">Email</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ Auth::user()->email }}</span>
                </div>
                <div class="py-3 flex justify-between">
                    <span class="text-gray-800 dark:text-gray-300">Tanggal Lahir</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ Auth::user()->birth }}</span>
                </div>
                <div class="py-3 flex justify-between">
                    <span class="text-gray-800 dark:text-gray-300">Jenis Kelamin</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ Auth::user()->gender }}</span>
                </div>
                <div class="py-3 flex justify-between">
                    <span class="text-gray-800 dark:text-gray-300">No. Telepon</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ Auth::user()->no_phone }}</span>
                </div>
                <div class="py-3 flex justify-between">
                    <span class="text-gray-800 dark:text-gray-300">No. KTP</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ Auth::user()->ktp }}</span>
                </div>
                <div class="py-3 flex justify-between">
                    <span class="text-gray-800 dark:text-gray-300">Pendidikan Terakhir</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ Auth::user()->last_education }}</span>
                </div>
                <div class="py-3 flex justify-between">
                    <span class="text-gray-800 dark:text-gray-300">Alamat</span>
                    <span class="font-medium text-gray-900 dark:text-white text-right">{{ Auth::user()->address }}</span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
