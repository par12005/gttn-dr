jQuery(document).ready(function () {
    
    if (jQuery(':input[name="step"]').length > 0){
        //console.log(jQuery("#edit-step")[0].value);
        
        if (jQuery(':input[name="step"]')[0].value === 'results'){
            jQuery("#edit-results").hide();
            jQuery("#results").hide();
            jQuery("#display_results").html(jQuery("#results").html());
            //console.log("results");
        }
    }
});