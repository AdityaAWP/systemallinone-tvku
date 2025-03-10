<x-filament::section>
    <div class="flex flex-col md:flex-row gap-6">
        <div class="w-full md:w-1/3 flex flex-col items-center justify-center p-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
            <div class="w-32 h-32 bg-gray-300 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                @if($this->getUser()->avatar)
                    <img src="{{ $this->getUser()->avatar }}" alt="{{ $this->getUser()->name }}" class="w-32 h-32 rounded-full object-cover">
                @else
                    <div class="w-32 h-32 rounded-full flex items-center justify-center bg-primary-500 text-white text-2xl font-bold">
                        {{ substr($this->getUser()->name, 0, 1) }}
                    </div>
                @endif
            </div>
            
            <h2 class="text-2xl font-semibold">{{ $this->getUser()->name }}</h2>
            <p class="text-gray-600 dark:text-gray-400">{{ $this->getUser()->email }}</p>
            
            @if($this->getUser()->position)
                <div class="mt-2 px-4 py-1 bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 rounded-full text-sm">
                    {{ $this->getUser()->position->name }}
                </div>
            @endif
        </div>
        
        <div class="w-full md:w-2/3 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium border-b pb-2 mb-4">Informasi Pribadi</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Telepon</p>
                        <p class="font-medium">{{ $this->getUser()->phone ?? 'Belum diisi' }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Jenis Kelamin</p>
                        <p class="font-medium">{{ $this->getUser()->gender == 'male' ? 'Laki-laki' : ($this->getUser()->gender == 'female' ? 'Perempuan' : 'Belum diisi') }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tanggal Lahir</p>
                        <p class="font-medium">{{ $this->getUser()->birth ? date('d F Y', strtotime($this->getUser()->birth)) : 'Belum diisi' }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nomor KTP</p>
                        <p class="font-medium">{{ $this->getUser()->ktp ?? 'Belum diisi' }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pendidikan Terakhir</p>
                        <p class="font-medium">{{ $this->getUser()->last_education ?? 'Belum diisi' }}</p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Alamat</p>
                        <p class="font-medium">{{ $this->getUser()->address ?? 'Belum diisi' }}</p>
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 rounded-b-lg">
                <a href="{{ \App\Filament\Pages\EditProfile::getUrl() }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-500 dark:hover:text-primary-400 text-sm font-medium">
                    Edit Profil →
                </a>
            </div>
        </div>
    </div>
</x-filament::section>