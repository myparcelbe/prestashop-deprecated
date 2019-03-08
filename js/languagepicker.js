
$( document ).ready(function() {
    if (['#lang=FR', '#lang=NL'].indexOf(window.location.hash) > -1) {
        $('#' + window.location.hash.split('=')[1].toUpperCase()).click();
    } else {
        $('#NL').click();
    }
});

/*
 * Load the correct language
 */
$(document).on("click",".languagepicker > li", function () {
    var clickedLanguage = $(this).attr('id'); /* Get the id of the selected language */
    if (typeof window.history !== 'undefined' && typeof window.history.replaceState === 'function') {
        window.history.replaceState(null,null,'#lang=' + clickedLanguage);
    }
    var lowerCase = clickedLanguage.toLowerCase();

    $(this).prependTo('ul.languagepicker'); /* set the selected language at first option */
    $(".all-content").load("language/"+ lowerCase +"_"+ clickedLanguage +".html");
});
