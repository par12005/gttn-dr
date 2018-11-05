jQuery(document).ready(function () {
    
    function Species(){
        
        var species_add = jQuery('#edit-species-add');
        var species_remove = jQuery('#edit-species-remove');
        var species_number = jQuery('#edit-species-number')[0].value;
        var species = jQuery('#edit-species').children('div').children('fieldset');
        
        jQuery('#edit-species-number').hide();
        species.hide();
        
        if (species_number > 0){
            for (var i = 0; i < species_number; i++){
                jQuery(species[i]).show();
            }

            for (var i = species_number; i < 5; i++){
                jQuery(species[i]).hide();
            }
        }
        
        species_add.attr('type', 'button');
        species_remove.attr('type', 'button');

        species_add.on('click', function(){
            if (species_number < 5){
                species_number++;
                jQuery('#edit-species-number')[0].value = species_number;
                
                for (var i = 0; i < species_number; i++){
                    jQuery(species[i]).show();
                }

                for (var i = species_number; i < 5; i++){
                    jQuery(species[i]).hide();
                }
            }
        });
        
        species_remove.on('click', function(){
            if (species_number > 1){
                species_number--;
                jQuery('#edit-species-number')[0].value = species_number;
                
                for (var i = 0; i < species_number; i++){
                    jQuery(species[i]).show();
                }

                for (var i = species_number; i < 5; i++){
                    jQuery(species[i]).hide();
                }
            }
        });
    }
    
    if (jQuery(':input[name="step"]').length > 0){
        //console.log(jQuery("#edit-step")[0].value);
        
        if (jQuery(':input[name="step"]')[0].value === 'first_page'){
            Species();
        }
        else if (jQuery(':input[name="step"]')[0].value === 'results'){
            jQuery("#edit-results").hide();
            jQuery("#results").hide();
            jQuery("#display_results").html(jQuery("#results").html());
            //console.log("results");
        }
    }
});