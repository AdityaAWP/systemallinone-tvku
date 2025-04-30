@extends('layouts.app')

@section('title', 'Detail Permintaan Cuti')

@section('content')
<div class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-gray-800 to-blue-700 text-white px-6 py-5">
                <h2 class="text-2xl font-semibold flex items-center">
                    <img src="{{ asset('images/tvku-logo.png') }}" alt="TVKU Logo" class="h-8 w-auto mr-3">
                    Detail Permintaan Cuti
                </h2>
            </div>

            <!-- Body -->
            <div class="p-6 md:p-8">
                <!-- Employee Info & Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-blue-700 border-b border-gray-200 pb-2">Informasi Karyawan</h3>
                        <ul class="space-y-3">
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span class="w-36 font-medium">Nama</span>
                                <span>: {{ $leave->user->name }}</span>
                            </li>
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span class="w-36 font-medium">Departemen</span>
                                <span>: {{ $leave->user->division->name ?? '-' }}</span>
                            </li>
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span class="w-36 font-medium">Posisi</span>
                                <span>: {{ $leave->user->roles->first()->name ?? '-' }}</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-blue-700 border-b border-gray-200 pb-2">Status Permintaan</h3>
                        <ul class="space-y-3">
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="w-36 font-medium">Status</span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xm font-medium
                                    @if($leave->status == 'approved') bg-green-100 text-green-800
                                    @elseif($leave->status == 'rejected') bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ ucfirst($leave->status) }}
                                </span>
                            </li>
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="w-36 font-medium">Diajukan pada</span>
                                <span>: {{ $leave->created_at->format('d M Y H:i') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-gray-200 my-8"></div>

                <!-- Leave Detail & Approvals -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-blue-700 border-b border-gray-200 pb-2">Detail Cuti</h3>
                        <ul class="space-y-3">
                            <li class="flex items-center">
                                <span class="w-36 font-medium">Jenis Cuti</span>
                                <span>: {{ $leave->leave_type }}</span>
                            </li>
                            <li class="flex items-center">
                                <span class="w-36 font-medium">Tanggal Mulai</span>
                                <span>: {{ $leave->from_date->format('d M Y') }}</span>
                            </li>
                            <li class="flex items-center">
                                <span class="w-36 font-medium">Tanggal Selesai</span>
                                <span>: {{ $leave->to_date->format('d M Y') }}</span>
                            </li>
                            <li class="flex items-center">
                                <span class="w-36 font-medium">Jumlah Hari</span>
                                <span>: {{ $leave->days }} hari</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-blue-700 border-b border-gray-200 pb-2">Persetujuan</h3>
                        <ul class="space-y-3">
                            <li class="flex items-center">
                                <span class="w-36 font-medium">Manager</span>
                                @if($leave->approval_manager === null)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xm font-medium bg-gray-100 text-gray-800">
                                        Menunggu
                                    </span>
                                @elseif($leave->approval_manager)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xm font-medium bg-green-100 text-green-800">
                                        Disetujui
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xm font-medium bg-red-100 text-red-800">
                                        Ditolak
                                    </span>
                                @endif
                            </li>
                            <li class="flex items-center">
                                <span class="w-36 font-medium">HRD</span>
                                @if($leave->approval_hrd === null)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xm font-medium bg-gray-100 text-gray-800">
                                        Menunggu
                                    </span>
                                @elseif($leave->approval_hrd)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xm font-medium bg-green-100 text-green-800">
                                        Disetujui
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xm font-medium bg-red-100 text-red-800">
                                        Ditolak
                                    </span>
                                @endif
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Leave Reason -->
                <div class="mt-8 space-y-4">
                    <h3 class="text-lg font-bold text-blue-700 border-b border-gray-200 pb-2">Alasan Cuti</h3>
                    <div class="bg-gray-50 border-l-4 border-blue-500 rounded-md p-4">
                        {{ $leave->reason }}
                    </div>
                </div>

                <!-- Rejection Reason -->
                @if($leave->status == 'rejected' && $leave->rejection_reason)
                <div class="mt-8 space-y-4">
                    <h3 class="text-lg font-bold text-red-600 border-b border-gray-200 pb-2">Alasan Penolakan</h3>
                    <div class="bg-red-50 border-l-4 border-red-500 rounded-md p-4">
                        {{ $leave->rejection_reason }}
                    </div>
                </div>
                @endif

                <!-- Attachment -->
                @if($leave->attachment)
                <div class="mt-8 space-y-4">
                    <h3 class="text-lg font-bold text-blue-700 border-b border-gray-200 pb-2">Lampiran</h3>
                    <a href="{{ asset('storage/' . $leave->attachment) }}" target="_blank" 
                       class="inline-flex items-center px-4 py-2 border border-blue-600 rounded-lg text-blue-600 bg-white hover:bg-blue-50 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download Lampiran
                    </a>
                </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
                <a href="{{ url('/') }}" 
                   class="inline-flex items-center px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
@endsection