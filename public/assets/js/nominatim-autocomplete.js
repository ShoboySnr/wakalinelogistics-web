/**
 * Address autocomplete using Google Maps Places API.
 * Requires the Google Maps JS API (with libraries=places) to be loaded.
 *
 * Uses a retry loop so it works whether Google Maps loads synchronously
 * or asynchronously before the function is called.
 *
 * Usage:
 *   nominatimAutocomplete(inputElement, onSelectCallback);
 *
 * onSelectCallback(address, lat, lon) is called when the user picks a result.
 */
function nominatimAutocomplete(input, onSelect) {
    function tryInit() {
        if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
            setTimeout(tryInit, 150);
            return;
        }

        var autocomplete = new google.maps.places.Autocomplete(input, {
            componentRestrictions: { country: 'ng' },
            fields: ['formatted_address', 'geometry', 'name'],
        });

        input.setAttribute('autocomplete', 'off');

        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            if (!place.geometry) return;

            var address = place.formatted_address || place.name || input.value;
            var lat = place.geometry.location.lat();
            var lon = place.geometry.location.lng();

            if (onSelect) onSelect(address, lat, lon);
        });
    }

    tryInit();
}
