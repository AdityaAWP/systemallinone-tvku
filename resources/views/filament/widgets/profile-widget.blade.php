<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                @if($user)
                    <div class="flex-shrink-0">
                        <img src="{{ asset('images/profile.png') }}" alt="{{ $user->name }}"
                            class="h-16 w-16 rounded-full object-cover">
                    </div>
                @else
                    <div class="flex-shrink-0 h-16 w-16 rounded-full bg-gray-200">
                        <span class="text-gray-500 text-xl font-medium">{{ substr($user->name, 0, 1) }}</span>
                    </div>
                @endif

                <div>
                    <h2 class="text-lg font-bold text-2xs px-3">{{ $user->name }}</h2>
                    <div class="flex items-center px-3">
                        <p class="text-sm text-gray-500 py-1 pl-2">{{ $user->email }}</p>
                        @if($user->position)
                            <span
                                class="mx-3 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-black border-2 border-black">
                                {{ $user->position->name }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <dl class="border-b border-gray-200">
                    <div class="text-1lg flex font-semibold py-1">Personal Information</div>
                </dl>
                <div class="py-1 flex justify-between text-sm">
                    <dt class="font-semibold text-2xs">Gender</dt>
                    <dd class="text-2xs">{{ $user->gender ?? 'Not specified' }}</dd>
                </div>
                <div class="py-1 flex justify-between text-sm">
                    <dt class="font-semibold text-2xs">Date of Birth</dt>
                    <dd class="text-2xs">{{ $user->birth ? $user->birth->format('d F Y') : 'Not specified' }}</dd>
                </div>
                <div class="py-1 flex justify-between text-sm">
                    <dt class="font-semibold text-2xs">Phone</dt>
                    <dd class="text-2xs">{{ $user->phone ?? 'Not specified' }}</dd>
                </div>
                <div class="py-1 flex justify-between text-sm">
                    <dt class="font-semibold text-2xs">KTP Number</dt>
                    <dd class="text-2xs">{{ $user->ktp ?? 'Not specified' }}</dd>
                </div>
            </div>

            <div>
                <dl class="border-b border-gray-200">
                    <div class="text-1lg flex font-semibold py-1">Additional Information</div>
                </dl>
                <div class="py-1 flex justify-between text-sm">
                    <dt class="font-semibold text-2xs">Last Education</dt>
                    <dd class="text-2xs">{{ $user->last_education ?? 'Not specified' }}</dd>
                </div>
                <div class="py-1 flex justify-between text-sm">
                    <dt class="font-semibold text-2xs">Address</dt>
                    <dd class="text-2xs">{{ $user->address ?? 'Not specified' }}</dd>
                </div>
                <div class="py-1 flex justify-between text-sm">
                    <dt class="font-semibold text-2xs">Account Created</dt>
                    <dd class="text-2xs">{{ $user->created_at->format('d F Y') }}</dd>
                </div>
            </div>
        </div> --}}
    </x-filament::card>
</x-filament::widget>