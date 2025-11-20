jQuery(document).ready(function($) {
    // Podstawowe funkcjonalności frontendu
    console.log('Przyjaciel na Godzinę - frontend loaded');
    
    // Walidacja formularzy
    $('.png-listing-form').on('submit', function(e) {
        var title = $('#listing_title').val();
        var description = $('#listing_description').val();
        var terms = $('#terms_accept').is(':checked');
        
        if (title.length < 5) {
            alert('Tytuł musi mieć co najmniej 5 znaków');
            e.preventDefault();
            return false;
        }
        
        if (description.length < 20) {
            alert('Opis musi mieć co najmniej 20 znaków');
            e.preventDefault();
            return false;
        }
        
        if (!terms) {
            alert('Musisz zaakceptować regulamin');
            e.preventDefault();
            return false;
        }
    });
});