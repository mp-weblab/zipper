// assets/js/script.js
(function($){
    $(document).ready(function(){
        $('#zipper-check-all').on('change', function(){
            $('.zipper-plugin-checkbox').prop('checked', $(this).is(':checked'));
        });

        // If you want future AJAX behaviours, zipperData is available (ajaxUrl + nonce)
        // For now we're using classic form POST/GET as designed.
    });
})(jQuery);

