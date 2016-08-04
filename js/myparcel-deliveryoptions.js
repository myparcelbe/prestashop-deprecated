
/**
 * Helper function to chain new DOM elements
 *
 * @param {Object} parent - DOM node to append this child to
 * @param {string} type - DOM element type, for example: div
 * @param {string} className - optional class for this element
 * @param {string} innerHTML - optional text for this element
 */
function chainElement(parent, type, className, innerHTML)
{
    var elem = document.createElement(type);

    if('undefined' !== typeof className && '' != className) { elem.className = className; }
    if('undefined' !== typeof innerHTML && '' != innerHTML) { elem.innerHTML = innerHTML; }
    if(null !== parent)                                     { parent.appendChild(elem);   }

    return elem;
}

/**
 * Helper function to chain multiple new DOM elements resulting in a location list item
 *
 * @param {Object} parent - DOM node to append this child to
 * @param {Date} date - Date to display on the left
 * @param {string} timeHTML - Time to display in the middle
 * @param {number} priceValue - Additional costs of the delivery type
 * @param {string} priceComment - Delivery type
 * @param {string} retailLocation - JSON encoded address of the pickup location
 * @param {boolean} newLocation - If this is not a new location, the day will not be printed
 */
function chainLocation(parent, date, timeHTML, priceValue, priceComment, retailLocation, newLocation)
{
    var weekDayShort = ['Zo','Ma','Di','Wo','Do','Vr','Za'],
        dayHTML = weekDayShort[date.getDay()],
        dateHTML = ('0' + date.getDate()).slice(-2) + '-' + ('0' + (date.getMonth() + 1)).slice(-2);

    var locationLi = chainElement(parent, 'li');
    var locationName = chainElement(locationLi, 'div');
    if(newLocation)
    {
        chainElement(locationName, 'strong', '', dayHTML);
        chainElement(locationName, 'br');
        chainElement(locationName, 'small', '', dateHTML);
    }
    var locationP = chainElement(locationLi, 'p', '', timeHTML);
    if(0 != priceValue)
    {
        var priceHTML = priceComments[priceComment] + '&nbsp;+&nbsp;&euro;&nbsp;' + (priceValue / 100).toFixed(2);
        chainElement(locationP, 'span', 'price_comment', priceHTML);
    }

    // store all relevant delivery information in data attributes
    locationLi.setAttribute('data-date', date.toISOString().slice(0, 10));
    locationLi.setAttribute('data-type', priceComment);
    locationLi.setAttribute('data-comment', timeHTML);
    locationLi.setAttribute('data-retail', retailLocation);

    return locationLi;
}

/**
 * Populate the pickup div with locations
 *
 * @param {string} postcode
 * @param {string} houseNumber
 */
function fillPickup(postcode, houseNumber)
{
    if (!postcode) {
        $('#pickup .locations').html('Geen PakjeGemak locaties gevonden.');
        $('#show_pickup_inputs').click();
        return;
    }

    $.getJSON(MYPARCEL_BPOST_AJAX_URL, {
        't' : 'pickup',
        'p': postcode,
        's': Math.max(1, houseNumber)
    }, function(data){
        if(data && 'undefined' !== typeof data['data'])
        {
            var weekDays   = ['monday',  'tuesday', 'wednesday', 'thursday',  'friday',  'saturday', 'sunday'],
                weekDaysNL = ['Maandag', 'Dinsdag', 'Woensdag',  'Donderdag', 'Vrijdag', 'Zaterdag', 'Zondag'],
                currentLocationCode = 0;

            $('#pickup .locations').html('');

            // loop through all returned pickup locations and display the delivery options grouped by location
            $.each(data['data'], function(key, value){
                // create location DOM node
                var newLocation = (currentLocationCode != value['location_code']);

                if(newLocation)
                {
                    // print dark header
                    currentLocationCode = value['location_code'];
                    locationDiv = chainElement(null, 'div', 'location');

                    var locationH4 = chainElement(locationDiv, 'h4', '', value['location']);
                    locationH4.className = 'dark';
                    chainElement(locationH4, 'div', 'opening_info', '( i )');
                    chainElement(locationH4, 'span', '', (value['distance'] / 1000).toFixed(1) + ' km');

                    locationUl = chainElement(locationDiv, 'ul');
                }

                // prepare values
                var date = new Date(value['date']),
                    timeHTML = 'vanaf ' + value['start_time'].substr(0, 5),
                    retailLocation = '{'
                        + '"name":"' + value['location'] + '",'
                        + '"postcode":"' + value['postcode'] + '",'
                        + '"street":"' + value['street'] + '",'
                        + '"house_number":"' + value['street_number'] + '",'
                        + '"town":"' + value['city'] + '"'
                    + '}';
                    // because of old IE support, we do not rely on JSON.stringify() or external libraries

                var locationLi = chainLocation(locationUl, date, timeHTML, value['price'], value['price_comment'], retailLocation, newLocation);

                if(newLocation)
                {
                    var openingHours = chainElement(locationLi, 'table');

                    for(var i = 0, l = weekDays.length; i < l; i++)
                    {
                        var openingHTML = value['opening_hours'][weekDays[i]].join('<br/>').replace('-', ' - ');
                        if('' == openingHTML)
                        {
                            openingHTML = 'Gesloten';
                        }
                        var openingTr = chainElement(openingHours, 'tr');
                        chainElement(openingTr, 'th', '', weekDaysNL[i]);
                        chainElement(openingTr, 'td', '', openingHTML);
                    }

                    var openingTr = chainElement(openingHours, 'tr');
                    var openingComment = chainElement(openingTr, 'td');
                                         chainElement(openingComment, 'p', '', value['comment']);
                    openingComment.colSpan = 2;

                    $('#pickup .locations').append(locationDiv);
                }
            });

            $('#pickup .location:gt(4)').hide();
            $('#show_more_pickups').show();

            setObservers();
        }
        else // show error and custom input field
        {
            $('#pickup .locations').html('Geen PakjeGemak locaties gevonden.');
            $('#show_pickup_inputs').click();
        }
    });
}

/**
 * Populate the delivery div and/or pickup div with loading spinners
 *
 * @param {boolean} delivery
 * @param {boolean} pickup
 */
function showSpinners(delivery, pickup)
{
    var loadingImage = '<img class="loading" src="' + MYPARCEL_AJAX_LOADING_ICON_URL + '" />';

    if(delivery) $('#delivery .locations').html(loadingImage);
    if(pickup) $('#pickup .locations').html(loadingImage);
}

/**
 * Close the overlay properly
 */
function closeOverlay()
{
    $('#delivery_inputs, #pickup_inputs').hide();
    $('#show_delivery_inputs, #show_pickup_inputs').show();
    showSpinners(true, true);
    $('.delivery_options_overlay_close').click();
}

/**
 * Set click/hover observers on dynamically added DOM elements
 */
function setObservers()
{
    $('.locations li').on('click', function(){
        // populate form
        /*$('#delivery_type').val($(this).attr('data-type'));
        $('#delivery_date').val($(this).attr('data-date'));
        $('#delivery_remark').val($(this).attr('data-comment'));*/
        var retailLocation = $.parseJSON($(this).attr('data-retail'));
        $('#company').val(retailLocation.name);
        $('#postcode').val(retailLocation.postcode);
        $('#city').val(retailLocation.town);
        var house_number = retailLocation.house_number;
        var street = retailLocation.street;
        $('#address1').val(street + ' ' + house_number);

        // Set country select box
        var country_box = $('#id_country');
        country_box.val(3);
        try{
            country_box.trigger('change');
        }catch(ex){
            // Possbile IE 11
           $('#add_address').submit();
        }

        //updateDeliverySelection();
        closeOverlay();
    });
    $('.locations li').hover(function(){
        $(this).addClass('hover');
    }, function(){
        $(this).removeClass('hover');
    });
    $('.opening_info').hover(function(){
        $(this).parent().next().find('table').show();
    }, function(){
        //$(this).parent().next().find('table').hide();
    });
    $('.location').mouseleave(function(){
        $('#pickup table').hide();
    });
    $('#pickup table').on('click', function(e){
        // prevent bubble to li click and close
        e.stopPropagation();
    });
}

/**
 * Visualise the data from the hidden form fields
 */
function updateDeliverySelection()
{
    // check if delivery options are chosen
    if('' != $('#delivery_date').val())
    {
        // update visual
        var visualPgAddress = '';
        if($('#PgAddress-postcode').val() != '')
        {
            visualPgAddress = '<br/>' + $('#PgAddress-name').val() + ' ' + $('#PgAddress-town').val() + ', ' + $('#PgAddress-street').val() + ' ' + $('#PgAddress-house_number').val();
        }
        var visualWeekdays = ['Maandag', 'Dinsdag', 'Woensdag',  'Donderdag', 'Vrijdag', 'Zaterdag', 'Zondag'];
        var visualDate = new Date($('#delivery_date').val().slice(0,10));
        var visualDay = visualDate.getDay() - 1;
        var visualDatum = ('0' + visualDate.getDate()).slice(-2) + '-' + ('0' + (visualDate.getMonth() + 1)).slice(-2) + '-' + visualDate.getFullYear();
        var visualHTMLh = priceComments[$('#delivery_type').val()];
        var visualHTMLp = visualWeekdays[visualDay] + ' ' + visualDatum + ', ' + $('#delivery_remark').val() + visualPgAddress;
        $('#delivery_select').hide();
        $('#delivery_visual h3').html(visualHTMLh);
        $('#delivery_visual p').html(visualHTMLp);
        $('#delivery_visual').show();
    }
    else // no delivery options
    {
        // clear delivery options
        $('#delivery_type').val('standard');
        $('#delivery_date').val('');
        $('#delivery_remark').val('');
        $('input[id^="PgAddress-"]').val('');

        // update visual
        $('#delivery_select').show();
        $('#delivery_visual h3, #delivery_visual p').html('');
        $('#delivery_visual').hide();
    }
    $('#delivery_type').change();
}

var priceComments = {
    standard: 'Standaard',
    earlymorning: 'Ochtend vroeg',
    morning: 'Ochtend',
    evening: 'Avond',
    retail: 'PakjeGemak',
    retailexpress: 'PakjeGemak Express'
};

$(function(){
    // init overlay
    $('#delivery_options_overlay').popup({
        outline: true,
        focusdelay: 337,
        vertical: 'center',
        horizontal: 'center',
        scrolllock: true,
        beforeopen: function(){
            // inject already entered postcode + house_number from the shipping address form
            var postcode = $('#postcode').val(),
                houseNumber = '';

            $('#postcode').val(postcode);
            $('#delivery_house_number').val(houseNumber);
            $('#pickup_input').val(postcode);

            // load initial data into the overlay
            showSpinners(true, true);
            fillPickup(postcode, houseNumber);

            // delivery location refresh based on typing in input fields with a 1 second tineout
            $('#delivery_postcode, #delivery_house_number').on('keyup', function(){
                if('undefined' != typeof delivery_timeout)
                {
                    clearTimeout(delivery_timeout);
                }
                delivery_timeout = setTimeout(function(){
                    var alnum = /[^A-Z0-9]/g;
                    var postcode = $('#postcode').val().toUpperCase().replace(alnum, '');
                    var houseNumber = '';
                    $('#postcode').val(postcode);

                    $('#ToAddress-postcode').val(postcode);
                    $('#ToAddress-house_number').val(houseNumber).change(); // trigger change
                    $('#pickup_input').val(postcode);

                    // load new data into the overlay
                    showSpinners(true, true);
                    fillPickup(postcode, Math.max(1, houseNumber));
                }, 1000);
            });

            // pickup location refresh based on typing in input field
            $('#pickup_input').on('keyup', function(){
                if('undefined' != typeof pickup_timeout)
                {
                    clearTimeout(pickup_timeout);
                }
                pickup_timeout = setTimeout(function(){
                    // load new data into the overlay - pickup only
                    showSpinners(false, true);
                    fillPickup($('#pickup_input').val(), 1);
                }, 1000);
            });
        }
    });

    // monitor custom input links inside the overlay
    $('#show_delivery_inputs').on('click', function(e){
        e.preventDefault();
        $('#delivery_inputs').show();
        $(this).hide();
        $('html, body, #delivery_options_overlay_wrapper').animate({ scrollTop: 0 }, 337);
    });
    $('#show_pickup_inputs').on('click', function(e){
        e.preventDefault();
        $('#pickup_inputs').show();
        $(this).hide();
        $('html, body, #delivery_options_overlay_wrapper').animate({ scrollTop: 0 }, 337);
    });
    $('#show_more_pickups').on('click', function(e){
        e.preventDefault();
        $('#pickup .location').show();
        $(this).hide();
    });

    // delete chosen delivery option data
   /* $('#delivery_delete').click(function(){
        $('#delivery_date').val('');
        updateDeliverySelection();
    });*/

    // trigger the delivery visualisation in case the form is already populated
    //updateDeliverySelection();

    // tab switch for responsive design
    $('#delivery_options_overlay .mobile h3.white').on('click', function(){
        $(this).parent().parent().parent().children().show();
        //$(this).parent().parent().hide();
    });
    // activate responsive design based on screen width
    $(window).on('resize', function(){
        if($(window).width() < 850) {
            $('#delivery_options_overlay').addClass('mobile');
            //$('#pickup').hide();
        } else {
            $('#delivery_options_overlay').removeClass('mobile');
            $('#delivery, #pickup').show();
        }
    }).resize();
});
