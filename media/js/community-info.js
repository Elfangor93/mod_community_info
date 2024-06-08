var map;
var bsmodal;
var pin;
var tilesURL='https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png';
var mapAttrib='&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, Tiles courtesy of <a href="http://hot.openstreetmap.org/" target="_blank">Humanitarian OpenStreetMap Team</a>';

let mapCreate = function() {
  // create map instance
  if (!(typeof map == "object")) {
    map = L.map('map', {
      center: [40,0],
      zoom: 3
    });
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
 * @param {String}  modalId   ID of the modal
 */
let openModal = function(modalId) {
  let modal      = document.getElementById(modalId);
  let modalBody  = modal.querySelector('.modal-body');
  modalBody.innerHTML  = document.getElementById('template-'+modalId+'-body').innerHTML;

  bsmodal = new bootstrap.Modal(document.getElementById(modalId), {keyboard: false});
  bsmodal.show();

  setTimeout(function(){
    mapCreate();
    registerEvents();
  }, 300);    
}