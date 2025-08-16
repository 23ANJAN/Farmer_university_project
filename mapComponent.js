// public/mapComponent.js
// Simple OSM Leaflet map initializer. Include Leaflet CSS/JS in your HTML.
export function initMap({ elId = "map", lat = 20.5937, lng = 78.9629, zoom = 5, markerText = "Location" } = {}) {
  const map = L.map(elId).setView([lat, lng], zoom);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { maxZoom: 19 }).addTo(map);
  L.marker([lat, lng]).addTo(map).bindPopup(markerText);
  return map;
}
