<!doctype html>
<!--
 @license
 Copyright 2019 Google LLC. All Rights Reserved.
 SPDX-License-Identifier: Apache-2.0
-->
<!DOCTYPE html>
<html>
  <head>
    <title>Checkout with Google Maps Autocomplete</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" />
    <link rel="stylesheet" href="css/main.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
      (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${
    c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})
          ({
              key: "AIzaSyDQgsaZdEU-elx6jAGkUopVX5Jxf45sgy0",
          });
    </script>
    <script>
      async function initMap() {
        const { Map, InfoWindow } = await google.maps.importLibrary("maps");
        const { Autocomplete } = await google.maps.importLibrary("places");
        const { AdvancedMarkerElement, PinElement} = await google.maps.importLibrary("marker");

        const mapOptions = {
            center: { lat: 28.43268, lng: 77.0459 }, // Initial center coordinates (Gurgaon)
            zoom: 16,
            mapStyle: 'https://maps.googleapis.com/maps/api/js/examples/styles/minimal_hosting.json', // Optional map style
            mapId: "f8b9e6163e48e501"
        };

        const map = new Map(document.getElementById("map"), mapOptions);

        const landmarksSelect = document.getElementById('landmarks');
        const combinedAddressInput = document.getElementById('combined-address');
        let formattedAddress = "";

        const geocoder = new google.maps.Geocoder(); // Initialize geocoder

        let marker = new AdvancedMarkerElement({
              map,
              position: mapOptions.center,
              gmpDraggable: true
            });
        let infoWindow;
        const descriptorMarkers = []; // Array to store landmark markers

        const addressInput = document.getElementById('address-autocomplete');
        const aptSuiteInput = document.getElementById('apt-suite');
        const cityInput = document.getElementById('city');
        const stateProvinceInput = document.getElementById('state-province');
        const zipPostalCodeInput = document.getElementById('zip-postal-code');
        const countryInput = document.getElementById('country');

        const autocomplete = new google.maps.places.Autocomplete(addressInput, {
          fields: ['place_id', 'address_components', 'formatted_address', 'geometry', 'name']
          // You can add restrictions here, e.g., componentRestrictions: { country: 'us' }
        });

        autocomplete.addListener('place_changed', () => {
          const place = autocomplete.getPlace();

          if (!place.geometry) {
            console.error("No details available for input: '" + place.name + "'");
            return;
          }

          marker.position = place.geometry.location;
          // Update map with selected location
          map.setCenter(place.geometry.location);

          // Fill in the form fields
          fillInAddress(place); 
          formattedAddress = place.formatted_address;

          // Get address descriptor landmarks
          addressDescriptorPlaceIdLookup(place.place_id);

          infoWindow = new InfoWindow({
              content: place.name,
              headerDisabled: true     // Hide the header
          });

          infoWindow.open(map, marker); // Open infoWindow by default

        });

        function fillInAddress(place) { 
          // Clear previous values
          aptSuiteInput.value = '';
          cityInput.value = '';
          stateProvinceInput.value = '';
          zipPostalCodeInput.value = '';
          countryInput.value = '';

          // Get each component of the address from the place details
          // and fill the corresponding field on the form.
          for (const component of place.address_components) {
            const componentType = component.types[0];

            switch (componentType) {
              case 'street_number': {
                addressInput.value = `${component.long_name} `;
                break;
              }

              case 'route': {
                addressInput.value += component.short_name;
                break;
              }

              case 'premise': {
                aptSuiteInput.value = component.short_name;
                break;
              }

              case 'subpremise': {
                aptSuiteInput.value = component.short_name;
                break;
              }

              case 'locality':
                cityInput.value = component.long_name;
                break;
              case 'administrative_area_level_1': {
                stateProvinceInput.value = component.short_name;
                break;
              }
              case 'postal_code': {
                zipPostalCodeInput.value = component.long_name;
                break;
              }
              case 'country':
                countryInput.value = component.long_name;
                break;
            }
          }

          M.updateTextFields(); 
        }

        function addressDescriptorPlaceIdLookup(placeId) {
          geocoder.geocode({
            'placeId': placeId,
            'extraComputations': ['ADDRESS_DESCRIPTORS'],
            'fulfillOnZeroResults': true
          }, function(results, status) {
            if (status == 'OK') {
              let addressDescriptor = results[0].address_descriptor;
              if(addressDescriptor) {
                const descriptors = results[0].address_descriptor.landmarks;
                landmarksSelect.innerHTML = '<option value="" disabled selected>Choose your Landmark</option>';
                            
                // Clear existing descriptor markers
                descriptorMarkers.forEach(marker => marker.setMap(null));
                descriptorMarkers.length = 0;
      
                descriptors.forEach((descriptor, index) => {
                  
                  const option = document.createElement('option');
                  option.value = descriptor.display_name; // Assuming landmarks have place_ids
                  option.text = descriptor.spatial_relationship + " " + descriptor.display_name;
                  landmarksSelect.appendChild(option);
      
                  const descriptorMarkerContent = document.createElement('div');
                  if (index === 0) {
                    descriptorMarkerContent.className = 'descriptor-marker highlighted';
                  } else {
                    descriptorMarkerContent.className = 'descriptor-marker';
                  }
                  //increment index for display
                  descriptorMarkerContent.textContent = ++index;
      
                  // Get landmark location
                  geocoder.geocode({ placeId: descriptor.place_id }, (results, status) => {
                    if (status === "OK") {
      
                      // Create infoWindow for landmark marker
                      const landmarkInfoWindow = new InfoWindow({
                        content: descriptor.display_name,
                        headerDisabled: true
                      });
      
                      const _marker = new AdvancedMarkerElement({
                        map: map,
                        position: results[0].geometry.location,
                        content: descriptorMarkerContent
                      });
                      descriptorMarkers.push(_marker); // Add marker to the array
                      
                      // Add mouseover and mouseout listeners for the info window
                      _marker.content.addEventListener("mouseover", () => {
                        landmarkInfoWindow.open(map, _marker);
                      });
                      _marker.content.addEventListener("mouseout", () => {
                        landmarkInfoWindow.close();
                      });
      
                    } else {
                      console.error("Error geocoding landmark:", status);
                    }
                  });
                });

                // Autoselect the first option:
                if (landmarksSelect.options.length > 1) {
                  landmarksSelect.selectedIndex = 1; 
                  updateCombinedAddress();
                  M.FormSelect.init(landmarksSelect); // Re-initialize Materialize select
                }
              } else {
                //clear landmark drop down
                removeOptions(landmarksSelect);
                //clear Address Field
                combinedAddressInput.value = "";
                alert("No Landmarkers available");         
              }

            } else {
              window.alert('Geocode was not successful for the following reason: ' + status);
            }
          });
        }

        // Function to update the combined address field
        function updateCombinedAddress() {
          const address = formattedAddress;
          const landmark = landmarksSelect.options[landmarksSelect.selectedIndex].text;
          //console.log("landmark ==>" + landmark)
          combinedAddressInput.value = `${address}\n${landmark}`;
          M.updateTextFields(); // Update Materialize text fields
        }

        // Add an event listener to the landmarks dropdown to update the combined address
        landmarksSelect.addEventListener('change', () => {
          updateCombinedAddress();
                
          // Highlight the selected landmark marker
          const selectedIndex = landmarksSelect.selectedIndex - 1; 
          descriptorMarkers.forEach((marker, index) => {
            if (selectedIndex === (parseInt(marker.content.textContent)-1)) {
              marker.content.classList.add("highlighted");
            } else {
              marker.content.classList.remove("highlighted");
            }
          });
        });  
        

        // Add marker dragend listener
        marker.addListener('dragend', () => {
            const newPosition = marker.position;
            geocoder.geocode({ 
              location: newPosition, 
              'extraComputations': ["ADDRESS_DESCRIPTORS"],
              'fulfillOnZeroResults': true 
              }, (results, status) => {
              if (status === "OK") {
                //console.log(results)
                if (results[0]) {
                  const place = results[0];
                  // Update the form fields with the new address
                  fillInAddress(place);
                  formattedAddress = place.formatted_address;
                  // Update the combined address field
                  updateCombinedAddress();
                  // You might also want to update the landmarks dropdown here
                  addressDescriptorPlaceIdLookup(place.place_id);
                  map.setCenter(newPosition);
                } else {
                  window.alert("No results found");
                }
              } else {
                window.alert("Geocoder failed due to: " + status);
              }
            });
        });

      }

      function removeOptions(selectElement) {
        var instance = M.FormSelect.getInstance(selectElement); 
        instance.destroy(); // Destroy the Materialize instance

        var i, L = selectElement.options.length - 1;
        for(i = L; i >= 0; i--) {
          selectElement.remove(i);
        }

        // Re-initialize the select
        instance = M.FormSelect.init(selectElement);
      }
    

      initMap();
    </script>
    <style>
        body {
          overflow: hidden; /* Prevent page scrollbars */
          font-family: 'Roboto', sans-serif !important; /* Add Roboto font */
        }

        .row {
          margin: 0; /* Remove default row margins */
        }

        #checkout-form {
          padding: 20px;
          border-right: 1px solid #ccc; /* Add a separator between columns */
          height: 100vh; /* Make the form container take full viewport height */
          overflow-y: auto; /* Add scrollbar to the form container if needed */
          
        }

        .input-field > input, select {
          font-size: 14px !important;
        }
        
        .select-wrapper * {
          font-size: 14px !important;
        }
    
      li>span {
        font-size: 14px !important;
      }

        #map {
          height: 100vh; 
        }

        #textarea-container {
          position: relative;
          display: flex;
          align-items: center;
          font-size: 14px;
        }

        .ad-title-icon {
          position: relative;
          left: 10px;
          top: 50%;
          transform: translateY(-120%);
        }

        #combined-address {
          border: none; /* Remove the default border/outline */
          resize: none; /* Prevent resizing by dragging */
          overflow: hidden; /* Prevent scrollbars from appearing */
          height: 8em; 
          padding-left: 30px
        }

        .descriptor-marker {
          background-color: #4285F4;
          border-radius: 50%;
          color: #FFFFFF;
          font-size: 11px;
          padding: 5px 8px;
          position: relative;
        }

        .highlighted {
          background-color: #FF0000 !important; /* Red background for highlighted marker */
        }
    </style>
  </head>

  <body>
    <div class="row">
      <div class="col s12 m4 l3" id="checkout-form"> 
        <h5>Address Descriptor Demo</h5>

        <div class="row">
          <div class="input-field col s12">
            <input type="text" id="address-autocomplete">
            <label for="address-autocomplete">Address</label>
          </div>
          <div class="input-field col s12">
            <input type="text" id="apt-suite">
            <label for="apt-suite">Apt, Suite, etc (optional)</label>
          </div>
          <div class="input-field col s12">
            <select id="landmarks">
              <option value="" disabled selected>Choose your Landmark</option>
            </select>
            <label for="landmarks">Landmark</label>
          </div>
          <div class="input-field col s12">
            <input type="text" id="city">
            <label for="city">City</label>
          </div>
          <div class="input-field col s6">
            <input type="text" id="state-province">
            <label for="state-province">State/Province</label>
          </div>
          <div class="input-field col s6">
            <input type="text" id="zip-postal-code">
            <label for="zip-postal-code">Zip/Postal code</label>
          </div>
          <div class="input-field col s12">
            <input type="text" id="country">
            <label for="country">Country</label>
          </div>
        </div>

        <button class="btn waves-effect waves-light blue" type="submit" name="action">Checkout
        </button>
        <div id="textarea-container" class="input-field col s12">
            <img class="ad-title-icon" src="https://fonts.gstatic.com/s/i/googlematerialicons/location_pin/v5/24px.svg" alt="">
            <textarea id="combined-address" readonly> </textarea>
        </div>
      </div>

      <div class="col s12 m8 l9"> 
        <div id="map"></div>
      </div>
    </div>
  </body>
</html>