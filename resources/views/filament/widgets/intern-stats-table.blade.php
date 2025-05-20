<x-filament::widget>
    <x-filament::card>
        <h2 class="text-lg font-bold mb-4">Statistik Anak Magang</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[600px] text-sm text-left border border-gray-200 dark:border-gray-700">
                <thead class="bg-gray-100 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 border-b border-gray-300 dark:border-gray-600">Kategori</th>
                        <th class="px-6 py-3 border-b border-gray-300 dark:border-gray-600">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($internData as $item)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="px-6 py-3">{{ $item['label'] }}</td>
                            <td class="px-6 py-3 font-semibold">{{ $item['value'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::card>
</x-filament::widget>
