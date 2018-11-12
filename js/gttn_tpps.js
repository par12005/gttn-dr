jQuery(document).ready(function () {
    
    if (jQuery(':input[name="step"]').length > 0){
        
        // If we're on the first page, hide all the species that have a number
        // higher than the species number.
        if (jQuery(':input[name="step"]')[0].value === 'first_page'){
            hideSpecies();
            // Also hide the species elements again whenever the species 
            // elements change.
            jQuery(document).bind('DOMNodeInserted', function() {
                hideSpecies();
            });
        }
        
        // If we're on the results page, move the data from the '#results' element
        // to the '#display_results' element.
        if (jQuery(':input[name="step"]')[0].value === 'results'){
            jQuery("#edit-results").hide();
            jQuery("#results").hide();
            jQuery("#display_results").html(jQuery("#results").html());
        }
    }
});

/**
 * Hides all the species elements that have a number higher than the species number.
 */
function hideSpecies(){
    // Get the species number.
    var speciesNumber = jQuery(':input[name="species[number]"]')[0].value;
    // Get the species elements.
    var speciesObjects = jQuery("fieldset").filter(function(){ return this.id.match(/^edit-species-[0-9]{1,2}-?-?[0-9]*$/); });
    speciesObjects.each(function() {
        // If the element number is higher than the species number, hide it.
        if (parseInt(this.name) > parseInt(speciesNumber)){
            jQuery(this).hide();
        }
    });
}