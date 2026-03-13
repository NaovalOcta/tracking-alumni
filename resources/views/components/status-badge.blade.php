@php
    $colors = match ($status) {
        'belum_dilacak' => 'bg-gray-100 text-gray-700',
        'sedang_dilacak' => 'bg-blue-100 text-blue-700',
        'auto_verified' => 'bg-green-100 text-green-700',
        'needs_audit' => 'bg-yellow-100 text-yellow-700',
        'not_found' => 'bg-red-100 text-red-700',
        'insufficient_data' => 'bg-orange-100 text-orange-700',
        default => 'bg-gray-100 text-gray-700',
    };

    $labels = match ($status) {
        'belum_dilacak' => 'Belum Dilacak',
        'sedang_dilacak' => 'Sedang Dilacak',
        'auto_verified' => 'Terverifikasi',
        'needs_audit' => 'Perlu Review',
        'not_found' => 'Tidak Ditemukan',
        'insufficient_data' => 'Data Kurang',
        default => $status,
    };
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colors }}">
    {{ $labels }}
</span>
