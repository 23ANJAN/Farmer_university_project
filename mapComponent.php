<?php
// mapComponent.php
// No session or DB logic here—this is just the map component.
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Map Component</title>
    <!-- Materialize CSS is optional; remove if not needed -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <!-- Google Fonts: Roboto (optional) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" />
    <!-- Your main CSS (if any) -->
    <!-- <link rel="stylesheet" href="css/main.css"> -->
    <script>
      // Load the Google Maps API using the new libraries import pattern
      (g => {
        var h, a, k, p = "The Google Maps JavaScript API", c = "google", l = "importLibrary", q = "__ib__", m = document, b = window;
        b = b[c] || (b[c] = {}); 
        var d = b.maps || (b.maps = {}), r = new Set, e = new URLSearchParams, u = () => h || (h = new Promise(async (f, n) => {
          a = m.createElement("script");
          e.set("libraries", [...r] + "");
          for (k in g) e.set(k.replace(/[A-Z]/g, t => "_" + t[0].toLowerCase()), g[k]);
          e.set("callback", c + ".maps." + q);
          a.src = `https://maps.googleapis.com/maps/api/js?` + e;
          d[q] = f;
          a.onerror = () => h = n(Error(p + " could not load."));
          a.nonce = m.querySelector("script[nonce]")?.nonce || "";
          m.head.append(a);
        }));
        d[l] ? console.warn(p + " only loads once. Ignoring:", g) : d[l] = (f, ...n) => r.add(f) && u().then(() => d[l](f, ...n));
      })({
          key: "INSERT_YOUR_API_KEY" // Replace with your API key
      });
    </script>
    <script>
      async function initMap() {
        // Import required libraries
        const { Map, InfoWindow } = await google.maps.importLibrary("maps");
        const { Autocomplete } = await google.maps.importLibrary("places");
        // Set up the map options (change center and zoom as needed)
        const mapOptions = {
          center: { lat: 28.43268, lng: 77.0459 },
          zoom: 16,
        };
        // Create the map
        const map = new Map(document.getElementById("map"), mapOptions);
        
        // Create a draggable marker at the center
        const marker = new google.maps.Marker({
          map: map,
          position: mapOptions.center,
          draggable: true
        });
        
        // Create an info window that shows when a place is selected
        const infoWindow = new InfoWindow({
          content: "Drag the marker or search for an address."
        });
        infoWindow.open(map, marker);
        
        // Set up Autocomplete on the input with id "address-autocomplete"
        const input = document.getElementById("address-autocomplete");
        const autocomplete = new Autocomplete(input, {
          fields: ["formatted_address", "geometry", "name", "place_id"]
        });
        autocomplete.addListener("place_changed", () => {
          const place = autocomplete.getPlace();
          if (!place.geometry) {
            console.error("No geometry for input: " + place.name);
            return;
          }
          // Move marker and center map on selected location
          marker.setPosition(place.geometry.location);
          map.setCenter(place.geometry.location);
          infoWindow.setContent(place.formatted_address);
          infoWindow.open(map, marker);
        });
        
        // Optional: update marker's position when dragged
        marker.addListener("dragend", () => {
          const pos = marker.getPosition();
          map.setCenter(pos);
        });
      }
    </script>
    <style>
      /* Set the map container size */
      #map {
        height: 300px;
        width: 100%;
      }
    </style>
  </head>
  <body onload="initMap()">
    <div class="container">
      <!-- An input field for Autocomplete -->
      <div class="input-field">
        <input id="address-autocomplete" type="text" placeholder="Enter an address">
      </div>
      <!-- The map container -->
      <div id="map"></div>
    </div>
  </body>
</html>
