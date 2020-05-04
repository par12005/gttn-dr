jQuery(document).ready(function ($) {
  if (jQuery('input[name="step"]').length > 0){
    var status_block = jQuery(".gttn_tpps-status-block");
    jQuery(".region-sidebar-second").empty();
    status_block.prependTo(".region-sidebar-second");

    jQuery("#progress").css('font-size', '1.5rem');
    jQuery("#progress").css('margin-bottom', '30px');

    if (jQuery('input[name="step"]')[0].value === 'summarypage'){
      jQuery("#gttn_tpps-status").insertAfter(".tgdr_form_status");
      jQuery("#edit-next").on('click', function(){
        jQuery("#gttn_tpps-status").html("<label>Loading... </label><br>This step may take several minutes.");
      });
    }
  }

  var buttons = jQuery('input').filter(function() { return (this.id.match(/map_button/) || this.id.match(/map-button/)); });
  jQuery.each(buttons, function(){
    jQuery(this).attr('type', 'button')
  });
  jQuery("#map_wrapper").hide();

  var preview_buttons = jQuery('input.preview_button');
  jQuery.each(preview_buttons, function() {
    jQuery(this).attr('type', 'button');
    jQuery(this).click(previewFile);
  });

});

function previewFile() {
  var fid;
  if (this.id.match(/fid_(.*)/) !== null) {
    fid = this.id.match(/fid_(.*)/)[1];
    var request = jQuery.post('/gttn-preview-file', {
      fid: fid
    });

    request.done(function (data) {
      if (jQuery('.preview_' + fid).length === 0) {
        jQuery('#fid_' + fid).after(data);
      }
    });
  }
  else {
    return;
  }
}

var maps = {};

function initMap() {
  var mapElements = jQuery('div').filter(function() { return this.id.match(/map_wrapper/); });
  jQuery.each(mapElements, function(){
    var species_name = this.id.match(/(.*)map_wrapper/)[1];
    maps[species_name] = new google.maps.Map(document.getElementById(species_name + 'map_wrapper'), {
      center: {lat:0, lng:0},
      zoom: 5
    });
    maps[species_name + 'markers'] = [];
    maps[species_name + 'total_lat'];
    maps[species_name + 'total_long'];
  });

  var display_regex = /reference\/submission\/GTTN-TGDR.*/g;
  if (window.location.pathname.match(display_regex)) {
    jQuery.fn.updateMap(Drupal.settings.gttn_tpps.tree_info);
  }
}

function clearMarkers(prefix) {
  for (var i = 0; i < maps[prefix + 'markers'].length; i++) {
    maps[prefix + 'markers'][i].setMap(null);
  }
  maps[prefix + 'markers'] = [];
}

function getCoordinates(){
  var species_name;
  if (this.id.match(/(.*)map_button/) !== null){
    species_name = this.id.match(/(.*)map_button/)[1];
  }
  else {
    species_name = "";
  }
  
  var fid, no_header, id_col, lat_col, long_col;
  try{
    if (jQuery('input[name="step"]').length > 0 && jQuery('input[name="step"]')[0].value == 3){
      var species_number;
      if (species_name !== ""){
        species_number = 'species-' + jQuery('div').filter(function() { return this.id.match(new RegExp(species_name + 'species_number')); })[0].innerHTML;
      }
      else {
        species_number = "";
      }
      fid = jQuery('input').filter(function() { return this.name.match(new RegExp('tree-accession\\[?' + species_number + '\\]?\\[file\\]\\[fid\\]')); })[0].value;
      no_header = jQuery('input').filter(function() { return this.name.match(new RegExp('tree-accession\\[?' + species_number + '\\]?\\[file\\]\\[no-header\\]')); })[0].checked;
      var cols = jQuery('select').filter(function() { return this.id.match(new RegExp('edit-tree-accession-?' + species_number + '-file-columns-')); });
      jQuery.each(cols, function(){
        var col_name = this.name.match(new RegExp('tree-accession\\[?' + species_number + '\\]?\\[file\\]\\[columns\\]\\[(.*)\\]'))[1];
        if (jQuery(this)[0].value === "1"){
          id_col = col_name;
        }
        if (jQuery(this)[0].value === "4"){
          lat_col = col_name;
        }
        if (jQuery(this)[0].value === "5"){
          long_col = col_name;
        }
      });
    }
    else {
      fid = jQuery('div').filter(function() { return this.id.match(new RegExp(species_name + 'accession_fid')); })[0].innerHTML;
      no_header = jQuery('div').filter(function() { return this.id.match(new RegExp(species_name + 'accession_no_header')); })[0].innerHTML;
      id_col = jQuery('div').filter(function() { return this.id.match(new RegExp(species_name + 'accession_id_col')); })[0].innerHTML;
      lat_col = jQuery('div').filter(function() { return this.id.match(new RegExp(species_name + 'accession_lat_col')); })[0].innerHTML;
      long_col = jQuery('div').filter(function() { return this.id.match(new RegExp(species_name + 'accession_long_col')); })[0].innerHTML;
    }
    
  }
  catch(err){
    console.log(err);
    return;
  }
  
  if (typeof id_col === 'undefined' || typeof lat_col === 'undefined' || typeof long_col === 'undefined'){
    jQuery("#" + species_name + "map_wrapper").hide();
    return;
  }
  
  var request = jQuery.post('/gttn-accession', {
    fid: fid,
    no_header: no_header,
    id_col: id_col,
    lat_col: lat_col,
    long_col: long_col
  });
  
  request.done(function (data) {
    jQuery.fn.updateMap(data, species_name);
  });
}

jQuery.fn.updateMap = function(locations, prefix = "") {
  jQuery("#" + prefix + "map_wrapper").show();
  var display_regex = /reference\/submission\/GTTN-TGDR.*/g;
  if (jQuery('input[name="step"]').length > 0 && jQuery('input[name="step"]')[0].value == 3){
    jQuery("#" + prefix + "map_wrapper").css({"height": "450px"});
    jQuery("#" + prefix + "map_wrapper").css({"max-width": "800px"});
  }
  else if(jQuery("#gttn_tpps_table_display").length > 0) {
    jQuery("#" + prefix + "map_wrapper").css({"height": "450px"});
  }
  else if (window.location.pathname.match(display_regex)) {
    jQuery("#" + prefix + "map_wrapper").css({"height": "450px"});
  }

  else {
    jQuery("#" + prefix + "map_wrapper").css({"height": "100px"});
  }
  
  clearMarkers(prefix);
  maps[prefix + 'total_lat'] = 0;
  maps[prefix + 'total_long'] = 0;
  timeout = 2000/locations.length;
  
  maps[prefix + 'markers'] = locations.map(function (location, i) {
    maps[prefix + 'total_lat'] += parseInt(location[1]);
    maps[prefix + 'total_long'] += parseInt(location[2]);
    var marker = new google.maps.Marker({
      position: new google.maps.LatLng(location[1], location[2])
    });
    
    var infowindow = new google.maps.InfoWindow({
      content: location[0] + '<br>Location: ' + location[1] + ', ' + location[2]
    });
    
    marker.addListener('click', function() {
      infowindow.open(maps[prefix], maps[prefix + 'markers'][i]);
    });
    return marker;
  });

  if (typeof maps[prefix + 'cluster'] !== 'undefined') {
    maps[prefix + 'cluster'].clearMarkers();
  }

  maps[prefix + 'cluster'] = new MarkerClusterer(maps[prefix], maps[prefix + 'markers'], {imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'});

  var center = new google.maps.LatLng(maps[prefix + 'total_lat']/locations.length, maps[prefix + 'total_long']/locations.length);
  maps[prefix].panTo(center);
};
