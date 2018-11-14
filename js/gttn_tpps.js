jQuery(document).ready(function ($) {
    
    function Organism(){
        var organism_add = jQuery('#edit-organism-add');
        var organism_remove = jQuery('#edit-organism-remove');
        var number_object = jQuery('#edit-organism div input:hidden');
        var organism_number = number_object[0].value;
        var organisms = jQuery('#edit-organism div div.form-type-textfield');
        
        jQuery('#edit-organism-number').hide();
        organisms.hide();
        
        if (organism_number > 0){
            for (var i = 0; i < organism_number; i++){
                jQuery(organisms[i]).show();
            }

            for (var i = organism_number; i < 5; i++){
                jQuery(organisms[i]).hide();
            }
        }
        
        organism_add.attr('type', 'button');
        organism_remove.attr('type', 'button');

        organism_add.on('click', function(){
            if (organism_number < 5){
                organism_number++;
                number_object[0].value = organism_number;
                
                for (var i = 0; i < organism_number; i++){
                    jQuery(organisms[i]).show();
                }

                for (var i = organism_number; i < 5; i++){
                    jQuery(organisms[i]).hide();
                }
            }
        });
        
        organism_remove.on('click', function(){
            if (organism_number > 1){
                organism_number--;
                number_object[0].value = organism_number;
                
                for (var i = 0; i < organism_number; i++){
                    jQuery(organisms[i]).show();
                }

                for (var i = organism_number; i < 5; i++){
                    jQuery(organisms[i]).hide();
                }
            }
        });
        
    }
    
    jQuery("#edit-step").hide();
    
    if (jQuery("#edit-step").length > 0){
        jQuery(".gttn_tpps-status-block").prependTo(".region-sidebar-second");

        jQuery("#progress").css('font-size', '1.5rem');
        jQuery("#progress").css('margin-bottom', '30px');
        
        if (jQuery("#edit-step")[0].value === 'Hellopage'){
            Organism();
        }
        
        if (jQuery("#edit-step")[0].value === 'summarypage'){
            jQuery("#gttn_tpps-status").insertAfter(".tgdr_form_status");
            jQuery("#edit-next").on('click', function(){
                jQuery("#gttn_tpps-status").html("<label>Loading... </label><br>This step may take several minutes.");
            });
        }
    }
    
    jQuery("#map_wrapper").hide();
});

var map;
var total_lat;
var total_long;
var markers = [];

function initMap() {
    map = new google.maps.Map(document.getElementById('map_wrapper'), {
        center: {lat: 0, lng: 0},
        zoom: 5
    });
}

function clearMarkers() {
    for (var i = 0; i < markers.length; i++) {
        markers[i].setMap(null);
    }
    markers = [];
}

function addMarkerWithTimeout(location, time) {
    window.setTimeout(function() {
        var index = markers.push(new google.maps.Marker({
            position: new google.maps.LatLng(location[1], location[2]),
            map: map,
            animation: google.maps.Animation.DROP,
            title: location[0],
        })) - 1;
        markers[index].addListener('click', toggleBounce);
    }, time);
}

function toggleBounce(){
    if (this.getAnimation() !== null){
        this.setAnimation(null);
    }
    else {
        this.setAnimation(google.maps.Animation.BOUNCE);
    }
}

jQuery.fn.updateMap = function(locations) {
    jQuery("#map_wrapper").show();
    clearMarkers();
    total_lat = 0;
    total_long = 0;
    timeout = 2000/locations.length;

    for (var i = 0; i < locations.length; i++) {
        total_lat += parseInt(locations[i][1]);
        total_long += parseInt(locations[i][2]);
        addMarkerWithTimeout(locations[i], timeout*i);
    }
    var center = new google.maps.LatLng(total_lat/locations.length, total_long/locations.length);
    map.panTo(center);
};