@if (session('error'))
<div x-data="{ shown: true }" x-init="setTimeout(() => shown = false, 5000)" x-show="shown" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
    <strong class="font-bold">Error!</strong>
    <span class="block sm:inline">{{ session('error') }}</span>
</div>
@endif

@if (session('success'))
<div x-data="{ shown: true }" x-init="setTimeout(() => shown = false, 5000)" x-show="shown" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
    <strong class="font-bold">Success!</strong>
    <span class="block sm:inline">{{ session('success') }}</span>
</div>
@endif