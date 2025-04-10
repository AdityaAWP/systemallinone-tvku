<x-filament::widget>
    <x-filament::section>
        <x-slot name="heading">
            Ringkasan Cuti Staff
        </x-slot>
        
        <div class="space-y-4">
            @if($this->getStaffLeaveData()->isEmpty())
                <p class="text-gray-500 text-center py-4">Tidak ada data staf yang tersedia.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Cuti Tahunan</th>
                                <th class="px-4 py-3">Cuti Sakit</th>
                                <th class="px-4 py-3">Pending</th>
                                <th class="px-4 py-3">Cuti Mendatang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->getStaffLeaveData() as $staff)
                                <tr class="border-b hover:bg-gray-50 {{ $staff['has_warning'] ? 'bg-yellow-50' : '' }}">
                                    <td class="px-4 py-3">{{ $staff['name'] }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <span class="mr-2">{{ $staff['casual_used'] }}/{{ $staff['casual_quota'] }}</span>
                                            <div class="w-24 bg-gray-200 rounded-full h-2.5">
                                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ ($staff['casual_used'] / $staff['casual_quota']) * 100 }}%"></div>
                                            </div>
                                            @if($staff['casual_remaining'] <= 3)
                                                <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800">Sisa {{ $staff['casual_remaining'] }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $staff['medical_leaves'] }} hari
                                        @if($staff['medical_leaves'] >= 5)
                                            <span class="ml-1 text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800">Perhatian</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($staff['pending_leaves'] > 0)
                                            <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">{{ $staff['pending_leaves'] }} pengajuan</span>
                                        @else
                                            <span class="text-gray-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($staff['upcoming_leaves']->count() > 0)
                                            <div>
                                                @foreach($staff['upcoming_leaves'] as $leave)
                                                    <div class="text-xs mb-1">
                                                        {{ $leave->from_date->format('d M') }} - {{ $leave->to_date->format('d M') }}
                                                        <span class="font-medium">
                                                            ({{ $leave->days }} hari {{ $leave->leave_type == 'casual' ? 'Tahunan' : ($leave->leave_type == 'medical' ? 'Sakit' : $leave->leave_type) }})
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-500">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            
            <div class="mt-4 text-sm text-gray-600">
                <p>Tips: Karyawan dengan latar kuning memerlukan perhatian (sisa cuti tahunan ≤ 3 atau cuti sakit ≥ 5).</p>
            </div>
        </div>
    </x-filament::section>
</x-filament::widget>