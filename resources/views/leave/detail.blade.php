@extends('layouts.app')

@section('title', 'Detail Permintaan Cuti')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Detail Permintaan Cuti</h4>
                </div>
                
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Informasi Karyawan</h5>
                            <p><strong>Nama:</strong> {{ $leave->user->name }}</p>
                            <p><strong>Departemen:</strong> {{ $leave->user->department ?? '-' }}</p>
                            <p><strong>Posisi:</strong> {{ $leave->user->position ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Status Permintaan</h5>
                            <p>
                                <strong>Status:</strong> 
                                <span class="badge 
                                    @if($leave->status == 'approved') bg-success
                                    @elseif($leave->status == 'rejected') bg-danger
                                    @else bg-warning text-dark @endif">
                                    {{ ucfirst($leave->status) }}
                                </span>
                            </p>
                            <p><strong>Tanggal Pengajuan:</strong> {{ $leave->created_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Detail Cuti</h5>
                            <p><strong>Jenis Cuti:</strong> {{ $leave->leave_type }}</p>
                            <p><strong>Tanggal Mulai:</strong> {{ $leave->from_date->format('d M Y') }}</p>
                            <p><strong>Tanggal Selesai:</strong> {{ $leave->to_date->format('d M Y') }}</p>
                            <p><strong>Jumlah Hari:</strong> {{ $leave->days }} hari</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Persetujuan</h5>
                            <p>
                                <strong>Manager:</strong> 
                                @if($leave->approval_manager === null)
                                    <span class="badge bg-secondary">Menunggu</span>
                                @elseif($leave->approval_manager)
                                    <span class="badge bg-success">Disetujui</span>
                                @else
                                    <span class="badge bg-danger">Ditolak</span>
                                @endif
                            </p>
                            <p>
                                <strong>HRD:</strong> 
                                @if($leave->approval_hrd === null)
                                    <span class="badge bg-secondary">Menunggu</span>
                                @elseif($leave->approval_hrd)
                                    <span class="badge bg-success">Disetujui</span>
                                @else
                                    <span class="badge bg-danger">Ditolak</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5>Alasan Cuti</h5>
                        <div class="p-3 bg-light rounded">
                            {{ $leave->reason }}
                        </div>
                    </div>

                    @if($leave->status == 'rejected' && $leave->rejection_reason)
                    <div class="mb-4">
                        <h5>Alasan Penolakan</h5>
                        <div class="p-3 bg-light rounded border border-danger">
                            {{ $leave->rejection_reason }}
                        </div>
                    </div>
                    @endif

                    @if($leave->attachment)
                    <div class="mb-4">
                        <h5>Lampiran</h5>
                        <a href="{{ asset('storage/' . $leave->attachment) }}" 
                           target="_blank" 
                           class="btn btn-outline-primary">
                            <i class="fas fa-file-download"></i> Download Lampiran
                        </a>
                    </div>
                    @endif
                </div>

                <div class="card-footer bg-light">
                    <a href="{{ url('/') }}" class="btn btn-primary">
                        <i class="fas fa-home"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection