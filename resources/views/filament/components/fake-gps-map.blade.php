@php
    $record = $getRecord();
@endphp
@if ($record->latitude && $record->longitude)
    @php
        $lat = $record->latitude;
        $lng = $record->longitude;
        $url = "https://maps.google.com/?q={$lat},{$lng}";
        $osm = "https://staticmap.openstreetmap.de/staticmap.php?center={$lat},{$lng}&zoom=15&size=200x120&markers={$lat},{$lng},red-pushpin";
    @endphp
    <a href="{{ $url }}" target="_blank">
        <img src="{{ $osm }}" alt="map" style="border-radius:8px;width:120px;height:80px;object-fit:cover;display:block;margin:auto;" />
    </a>
@else
    -
@endif 