<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center space-x-4">
            @if($user)
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/profile.png') }}" alt="{{ $user->name }}" class="h-16 w-16 rounded-full object-cover">
                </div>
            @else
                <div class="flex-shrink-0 h-16 w-16 rounded-full bg-gray-200">
                    <span class="text-gray-500 text-xl font-medium">{{ substr($user->name, 0, 1) }}</span>
                </div>
            @endif
            
            <div>
                <h2 class="text-lg font-bold text-gray-900 px-3">{{ $user->name }}</h2>
                <div class="mt-1 flex items-center px-3">
                <p class="text-sm text-gray-500 py-1 pl-2">{{ $user->email }}</p>
                    @if($user->position)
                        <span class="mx-3 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-black border-2 border-black">
                            {{ $user->position->name }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-1lg text-black font-semibold">Personal Information</h3>
                <dl class="mt-2 divide-y divide-gray-200">
                    <div class="py-3 flex justify-between text-sm">
                        <dt class="font-semibold text-gray-900">Gender</dt>
                        <dd class="text-gray-900">{{ $user->gender ?? 'Not specified' }}</dd>
                    </div>
                    <div class="py-3 flex justify-between text-sm">
                        <dt class="font-semibold text-gray-900">Date of Birth</dt>
                        <dd class="text-gray-900">{{ $user->birth ? $user->birth->format('d F Y') : 'Not specified' }}</dd>
                    </div>
                    <div class="py-3 flex justify-between text-sm">
                        <dt class="font-semibold text-gray-900">Phone</dt>
                        <dd class="text-gray-900">{{ $user->phone ?? 'Not specified' }}</dd>
                    </div>
                    <div class="py-3 flex justify-between text-sm">
                        <dt class="font-semibold text-gray-900">KTP Number</dt>
                        <dd class="text-gray-900">{{ $user->ktp ?? 'Not specified' }}</dd>
                    </div>
                </dl>
            </div>
            
            <div>
                <h3 class="text-1lg text-black font-semibold">Additional Information</h3>
                <dl class="mt-2 divide-y divide-gray-200">
                    <div class="py-3 flex justify-between text-sm">
                        <dt class="font-semibold text-gray-900">Last Education</dt>
                        <dd class="text-gray-900">{{ $user->last_education ?? 'Not specified' }}</dd>
                    </div>
                    <div class="py-3 flex justify-between text-sm">
                        <dt class="font-semibold text-gray-900">Address</dt>
                        <dd class="text-gray-900">{{ $user->address ?? 'Not specified' }}</dd>
                    </div>
                    <div class="py-3 flex justify-between text-sm">
                        <dt class="font-semibold text-gray-900">Account Created</dt>
                        <dd class="text-gray-900">{{ $user->created_at->format('d F Y') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>