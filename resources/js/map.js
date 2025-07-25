/**
 * Map functionality for Office Location
 */
document.addEventListener('DOMContentLoaded', function() {
    // Check if map container exists
    const mapContainer = document.getElementById('map');
    if (!mapContainer) return;

    // Initialize map
    const map = L.map('map').setView([-6.2088, 106.8456], 11); // Jakarta coordinates
    
    // Add tile layer (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Fetch office locations from API
    fetch('/api/offices')
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.data || data.data.length === 0) {
                console.warn('No office locations found');
                return;
            }
            
            // Process office data
            const offices = data.data;
            const bounds = L.latLngBounds();
            
            offices.forEach(office => {
                // Skip if no valid coordinates
                if (!office.latitude || !office.longitude) {
                    console.warn(`Office ${office.name} has invalid coordinates`);
                    return;
                }
                
                const lat = parseFloat(office.latitude);
                const lng = parseFloat(office.longitude);
                
                // Skip if coordinates are invalid
                if (isNaN(lat) || isNaN(lng)) {
                    console.warn(`Office ${office.name} has invalid coordinates format`);
                    return;
                }
                
                // Add marker for this office
                const marker = L.marker([lat, lng]).addTo(map);
                
                // Create popup content
                const popupContent = `
                    <div class="text-base font-bold">${office.name}</div>
                    <div class="text-sm">${office.address || 'Alamat tidak tersedia'}</div>
                    <div class="text-sm">Radius: ${office.radius_meter} meter</div>
                    <div class="text-sm">Tipe: ${office.office_type || 'Standard'}</div>
                `;
                
                marker.bindPopup(popupContent);
                
                // Add circle to show radius
                const circle = L.circle([lat, lng], {
                    color: '#4f46e5',
                    fillColor: '#818cf8',
                    fillOpacity: 0.15,
                    radius: parseInt(office.radius_meter) || 100
                }).addTo(map);
                
                // Extend bounds to include this location
                bounds.extend([lat, lng]);
            });
            
            // Fit map to bounds of all offices
            if (bounds.isValid()) {
                map.fitBounds(bounds, {
                    padding: [50, 50]
                });
            }
        })
        .catch(error => {
            console.error('Error fetching office locations:', error);
        });
}); 