<div style="padding: 1rem;">
    <h3>User: {{ $user->name }}</h3>
    <p>Email: {{ $user->email }}</p>
    <p>Device: {{ $record->device_info }}</p>
    <p>IP: {{ $record->ip_address }}</p>
    <p>Lokasi: {{ $record->latitude }}, {{ $record->longitude }}</p>
    <p>Waktu Deteksi: {{ $record->detected_at }}</p>
    <p>Total Pelanggaran Fake GPS: <b>{{ $count }}</b></p>
    <p>Status: <b style="color:{{ $banned ? 'red' : 'green' }}">{{ $banned ? 'BANNED' : 'ACTIVE' }}</b></p>
    @if ($count >= 3 && !$banned)
        <p style="color:red;font-weight:bold;">User ini sudah 3x fake GPS. Klik tombol Ban User untuk blokir otomatis.</p>
    @endif
</div> 