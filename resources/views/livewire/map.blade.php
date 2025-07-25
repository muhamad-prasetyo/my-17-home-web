<div>
    <div class="grid grid-cols-1 dark:bg-gray-900 md:grid-cols-12 gap-4" wire:ignore>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    
        <div class="md:col-span-12 bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
            <div id="livewireMapContainer" class="w-full" style="height: 75vh;"></div>
        </div>
    
    
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
        <script>
            let livewireMapInstance = null; 

            function initializeLivewireMap() {
                const mapElement = document.getElementById('livewireMapContainer');
                
                // Jika elemen tidak ada, jangan lakukan apa-apa
                if (!mapElement) return;

                // Jika peta sudah ada di instance ini, jangan buat baru, cukup update marker
                if (livewireMapInstance) {
                    console.log('Livewire map instance already exists, updating markers.');
                    updateLivewireMarkers(livewireMapInstance);
                    return;
                }
                
                // Periksa apakah elemen sudah diinisialisasi oleh Leaflet (mungkin dari skrip lain)
                if (mapElement._leaflet_id) {
                    console.warn('Map container (livewireMapContainer) seems to be already initialized by Leaflet from elsewhere.');
                    // Jika Anda ingin mencoba mengambil alih atau menggunakan instance yang ada:
                    // livewireMapInstance = mapElement._leaflet_map; // Ini berisiko
                    // Jika tidak, jangan lakukan apa-apa untuk menghindari konflik
                    return; 
                }

                console.log('Initializing Livewire map...');
                livewireMapInstance = L.map('livewireMapContainer').setView([-0.089275, 121.921327], 4.5);
    
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(livewireMapInstance);
    
                updateLivewireMarkers(livewireMapInstance);
            }

            function clearLivewireMarkers(map) {
                if (map) {
                    map.eachLayer(function (layer) {
                        if (layer instanceof L.Marker) {
                            map.removeLayer(layer);
                        }
                    });
                }
            }

            function updateLivewireMarkers(map) {
                if (!map) return;
                clearLivewireMarkers(map); // Hapus marker lama sebelum menambahkan yang baru

                const markersData = @json($attendances);
                console.log('Livewire - Attendance Data for Markers:', markersData);
    
                markersData.forEach(marker => {
                    if (marker.latlon_in && typeof marker.latlon_in === 'string' && marker.latlon_in.includes(',')) {
                        const coordinates = marker.latlon_in.split(',');
                        const latitude = parseFloat(coordinates[0]);
                        const longitude = parseFloat(coordinates[1]);

                        if (!isNaN(latitude) && !isNaN(longitude)) {
                            const popupContent = `<b>Nama:</b> ${marker.user ? marker.user.name : 'N/A'}<br>
                                                <b>Jam Masuk:</b> ${marker.time_in ? new Date(marker.date + ' ' + marker.time_in).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) : 'N/A'}<br>
                                                <b>Tanggal:</b> ${marker.date ? new Date(marker.date).toLocaleDateString('id-ID') : 'N/A'}`;
                            L.marker([latitude, longitude])
                                .addTo(map)
                                .bindPopup(popupContent);
                        } else {
                            console.warn(`Livewire - Koordinat tidak valid untuk absensi ID: ${marker.id}, LatLon: ${marker.latlon_in}`);
                        }
                    } else {
                        console.warn(`Livewire - Data latlon_in tidak lengkap atau format salah untuk absensi ID: ${marker.id}. Data: `, marker.latlon_in);
                    }
                });
            }
    
            document.addEventListener('livewire:initialized', function() {
                console.log('Livewire initialized event fired.');
                initializeLivewireMap(); 
            });

            // Hook untuk navigasi SPA Livewire v3+
            document.addEventListener('livewire:navigated', function () {
                console.log('Livewire navigated event fired.');
                // Hapus instance lama jika ada untuk memastikan re-inisialisasi bersih
                if (livewireMapInstance) {
                    livewireMapInstance.remove();
                    livewireMapInstance = null;
                }
                // Pastikan elemen kontainer ada sebelum mencoba inisialisasi
                if (document.getElementById('livewireMapContainer')){
                    initializeLivewireMap();
                }
            });

            // Listener jika data absensi diupdate oleh Livewire
            // Anda perlu mengirim event 'attendancesUpdated' dari komponen PHP Livewire Anda
            // jika $attendances diperbarui setelah load awal.
            // Livewire.on('attendancesUpdated', () => {
            //     console.log('Livewire attendancesUpdated event received.');
            //     if (livewireMapInstance) {
            //         updateLivewireMarkers(livewireMapInstance);
            //     }
            // });

        </script>
        
    </div>
</div>
