<div class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-100">Informasi File</h4>
            <div class="mt-2 space-y-2">
                <div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Nama File:</span>
                    <p class="text-sm font-medium">{{ $record->original_filename }}</p>
                </div>
                
                <div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Jenis File:</span>
                    <p class="text-sm font-medium">{{ $record->file_type_display }}</p>
                </div>
                
                <div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Ukuran:</span>
                    <p class="text-sm font-medium">{{ $record->file_size_formatted }}</p>
                </div>
                
                <div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $record->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 
                           ($record->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' : 
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100') }}">
                        {{ ucfirst($record->status) }}
                    </span>
                </div>
            </div>
        </div>
        
        <div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-100">Informasi Upload</h4>
            <div class="mt-2 space-y-2">
                <div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Diupload Oleh:</span>
                    <p class="text-sm font-medium">{{ $record->uploader->name }}</p>
                </div>
                
                <div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Tanggal Upload:</span>
                    <p class="text-sm font-medium">{{ $record->created_at->format('d M Y H:i') }}</p>
                </div>
                
                @if($record->title)
                <div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Judul:</span>
                    <p class="text-sm font-medium">{{ $record->title }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    @if($record->description)
    <div>
        <h4 class="font-semibold text-gray-900 dark:text-gray-100">Deskripsi</h4>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $record->description }}</p>
    </div>
    @endif
    
    <div>
        <h4 class="font-semibold text-gray-900 dark:text-gray-100">Path File</h4>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 font-mono">{{ $record->file_path }}</p>
    </div>
</div>