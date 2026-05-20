(function ($) {
    'use strict';

    let frame;

    $('#ksv_logo_select').on('click', function (e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Logo auswählen',
            button: { text: 'Übernehmen' },
            multiple: false,
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#ksv_logo_id').val(attachment.id);
            const url = attachment.sizes && attachment.sizes.thumbnail
                ? attachment.sizes.thumbnail.url
                : attachment.url;
            $('.ksv-logo-preview').html(
                '<img src="' + url + '" alt="" width="100" height="100" />'
            );
        });

        frame.open();
    });

    $('#ksv_logo_remove').on('click', function (e) {
        e.preventDefault();
        $('#ksv_logo_id').val('');
        $('.ksv-logo-preview').html(
            '<span class="ksv-logo-placeholder">Kein Logo</span>'
        );
    });
})(jQuery);
