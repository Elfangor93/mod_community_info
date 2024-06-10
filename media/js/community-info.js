var map;
var bsmodal;
var pin;
var tilesURL='https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png';
var mapAttrib='&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, Tiles courtesy of <a href="http://hot.openstreetmap.org/" target="_blank">Humanitarian OpenStreetMap Team</a>';

/**
 * Create the leaflet map
 * 
 * @param {String}  location   The current location (e.g 51.5000,0.0000) 
 */
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

/**
 * Register click event on leaflet map
 */
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
 * Activate automatic location service
 */
let autoLoc = function() {
  console.log('autoLoc()');
}

/**
 * Save manual chosen location
 */
let saveLoc = function() {
  console.log('saveLoc()');
}

/**
 * Perform an ajax request in json format
 * 
 * @param   {String}   location   Coordinates of the current location (e.g 51.5000,0.0000)
 * @param   {Interger} module_id  ID of the current module
 * @param   {String}   method     Name of the target method in the module helper class
 * 
 * @returns {Object} Result object
 *          {success: true, status: 200, message: '', messages: {}, data: {}}
 */
let ajaxLocation = async function(location, module_id, method) {
  // Create form data
  let formData = new FormData();
  formData.append('format', 'json')
  formData.append('module_id', module_id);

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

  // Perform the fetch request
  let response = await fetch(url, parameters);
  let txt      = await response.text();

  if (!response.ok) {
    // Catch network error
    return {success: false, status: response.status, message: response.message, messages: {}, data: {error: txt, data:null}};
  }

  let res = null;

  if(txt.startsWith('{"success"')) {
    // Response is of type json --> everything fine
    res = JSON.parse(txt);
    res.status = response.status;
    try {
      res.data = JSON.parse(res.data);
    } catch (e) {
      // no need to parse a json string.
    }
  } else if (txt.includes('Fatal error')) {
    // PHP fatal error occurred
    res = {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: txt, data:null}};
  } else {
    // Response is not of type json --> probably some php warnings/notices
    let split = txt.split('\n{"');
    let temp  = JSON.parse('{"'+split[1]);
    let data  = JSON.parse(temp.data);
    res = {success: true, status: response.status, message: split[0], messages: temp.messages, data: data};
  }

  // Make sure res.data.data.queue is of type array
  if(typeof res.data.data != "undefined" && res.data.data != null && 'queue' in res.data.data) {
    if(res.data.data.queue.constructor !== Array) {
      res.data.data.queue = Object.values(res.data.data.queue);
    }
  }

  return res;
};

/**
 * Get current position of device
 * 
 * @returns {Promise<String>}   A promise that resolves to the location string (e.g., "51.5000,0.0000")
 */
let getCurrentLocation = async function() {
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