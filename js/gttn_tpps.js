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
    
    var buttons = jQuery('input').filter(function() { return this.id.match(/map_button/); });
    jQuery.each(buttons, function(){
        jQuery(this).click(getCoordinates);
    });
    jQuery("#map_wrapper").hide();
});

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
    console.log(maps);
}

function clearMarkers(prefix) {
    for (var i = 0; i < maps[prefix + 'markers'].length; i++) {
        maps[prefix + 'markers'][i].setMap(null);
    }
    maps[prefix + 'markers'] = [];
}

function addMarkerWithTimeout(location, time, prefix) {
    window.setTimeout(function() {
        var index = maps[prefix + 'markers'].push(new google.maps.Marker({
            position: new google.maps.LatLng(location[1], location[2]),
            map: maps[prefix],
            animation: google.maps.Animation.DROP,
            title: location[0],
        })) - 1;
        maps[prefix + 'markers'][index].addListener('click', toggleBounce);
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

function getCoordinates(){
    var species_name = this.id.match(/(.*)map_button/)[1];
    var fid = jQuery('div').filter(function() { return this.id.match(new RegExp(species_name + 'accession_fid')); })[0].innerHTML;
    var no_header = jQuery('div').filter(function() { return this.id.match(new RegExp(species_name + 'accession_no_header')); })[0].innerHTML;
    var id_col = jQuery('div').filter(function() { return this.id.match(new RegExp(species_name + 'accession_id_col')); })[0].innerHTML;
    var lat_col = jQuery('div').filter(function() { return this.id.match(new RegExp(species_name + 'accession_lat_col')); })[0].innerHTML;
    var long_col = jQuery('div').filter(function() { return this.id.match(new RegExp(species_name + 'accession_long_col')); })[0].innerHTML;
    console.log(fid);
    console.log(no_header);
    console.log(id_col);
    console.log(lat_col);
    console.log(long_col);
    
    var request = jQuery.post('gttn-accession', {
        fid: fid,
        no_header: no_header,
        id_col: id_col,
        lat_col: lat_col,
        long_col: long_col
    });
    
    request.done(function (data) {
        console.log(jQuery.fn.updateMap(data, species_name));
    });
}

jQuery.fn.updateMap = function(locations, prefix = "") {
    jQuery("#" + prefix + "map_wrapper").show();
    clearMarkers(prefix);
    maps[prefix + 'total_lat'] = 0;
    maps[prefix + 'total_long'] = 0;
    timeout = 2000/locations.length;

    for (var i = 0; i < locations.length; i++) {
        maps[prefix + 'total_lat'] += parseInt(locations[i][1]);
        maps[prefix + 'total_long'] += parseInt(locations[i][2]);
        addMarkerWithTimeout(locations[i], timeout*i, prefix);
    }
    var center = new google.maps.LatLng(maps[prefix + 'total_lat']/locations.length, maps[prefix + 'total_long']/locations.length);
    maps[prefix].panTo(center);
    console.log(jQuery("#" + prefix + "map_wrapper"));
};