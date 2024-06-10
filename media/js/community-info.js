var map;
var bsmodal;
var pin;
var tilesURL='https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png';
var mapAttrib='&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, Tiles courtesy of <a href="http://hot.openstreetmap.org/" target="_blank">Humanitarian OpenStreetMap Team</a>';

let mapCreate = function(location) {
  // create map instance
  if (!(typeof map == "object")) {
    map = L.map('map', {
      center: [40,0],
      zoom: 3
    });

    let latlng = location.split(',');
    pin = L.marker(latlng,{ riseOnHover:true,draggable:true });
    pin.addTo(map);
  }
  else {
    map.setZoom(3).panTo([40,0]);
  }
  // create the tile layer with correct attribution
  L.tileLayer(tilesURL, {
    attribution: mapAttrib,
    maxZoom: 19
  }).addTo(map);
}

let registerEvents = function() {
  map.on('click', function(ev) {
    document.getElementById('jform_lat').value = ev.latlng.lat;
    document.getElementById('jform_lng').value = ev.latlng.lng;
    if (typeof pin == "object") {
      pin.setLatLng(ev.latlng);
    }
    else {
      pin = L.marker(ev.latlng,{ riseOnHover:true,draggable:true });
      pin.addTo(map);
      pin.on('drag',function(ev) {
        document.getElementById('jform_lat').value = ev.latlng.lat;
        document.getElementById('jform_lng').value = ev.latlng.lng;
      });
    }
  });
}

/**
 * Opens a bootstrap modal based on the provided ID
 * 
 * @param {String}  modalId    ID of the modal
 * @param {String}  location   The current location (e.g 51.5000,0.0000) 
 */
let openModal = function(modalId, location) {
  let modal      = document.getElementById(modalId);
  let modalBody  = modal.querySelector('.modal-body');
  modalBody.innerHTML  = document.getElementById('template-'+modalId+'-body').innerHTML;

  bsmodal = new bootstrap.Modal(document.getElementById(modalId), {keyboard: false});
  bsmodal.show();

  setTimeout(function(){
    mapCreate(location);
    registerEvents();
  }, 300);    
}

/**
 * Perform an ajax request in json format
 * 
 * @param   {String} location  Coordinates of the current location (e.g 51.5000,0.0000)
 * @param   {String} method    Name of the method in the modules helper file
 * 
 * @returns {Object} Result object
 *          {success: true, status: 200, message: '', messages: {}, data: {}}
 */
let ajaxLocation = async function(location, method) {
  // Create form data
  let formData = new FormData();
  formData.append('format', 'json');

  // Set request parameters
  let parameters = {
    method: 'POST',
    mode: 'same-origin',
    cache: 'default',
    redirect: 'follow',
    referrerPolicy: 'no-referrer-when-downgrade',
    body: formData,
  };

  // Set the URL
  let url = `index.php?option=com_ajax&module=community_info&method=${method}&format=json&current_location=${location}`;

  try {
    // Perform the fetch request
    let response = await fetch(url, parameters);
    let txt = await response.text();

    if (!response.ok) {
      // Network error
      return { success: false, status: response.status, message: response.statusText, messages: {}, data: { error: txt, data: null } };
    }

    // Try parsing JSON response
    try {
      let res = JSON.parse(txt);

      // Handle response containing success field
      if (res.success !== undefined) {
        res.status = response.status;
        if (typeof res.data === 'string') {
          res.data = JSON.parse(res.data);
        }
        return res;
      }
      
      // Handle unexpected JSON structure
      return { success: false, status: response.status, message: "Unexpected response format", messages: {}, data: { error: txt, data: null } };
    } catch (jsonError) {
      // Handle case where JSON parsing fails
      if (txt.includes('Fatal error')) {
        return { success: false, status: response.status, message: response.statusText, messages: {}, data: { error: txt, data: null } };
      } else {
        let [msg, jsonStr] = txt.split('\n{');
        let temp = JSON.parse('{' + jsonStr);
        let data = JSON.parse(temp.data);
        return { success: true, status: response.status, message: msg, messages: temp.messages, data: data };
      }
    }
  } catch (error) {
    // General catch for unexpected errors
    return { success: false, status: 500, message: error.message, messages: {}, data: { error: error.toString(), data: null } };
  }
};

/**
 * Get current position of device
 * 
 * @returns {Promise<String>}   A promise that resolves to the location string (e.g., "51.5000,0.0000")
 */
let getCurrentLocation = function() {
  return new Promise((resolve, reject) => {
    if ('geolocation' in navigator) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const { latitude, longitude } = position.coords;
          resolve(`${latitude},${longitude}`);
        },
        (error) => {
          reject(`Error getting location: ${error.message}`);
        }
      );
    } else {
      reject('Geolocation is not supported by this browser.');
    }
  });
};