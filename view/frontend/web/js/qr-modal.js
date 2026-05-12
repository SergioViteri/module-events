define(['jquery'], function ($) {
    'use strict';

    var $overlay;
    var $title;
    var $date;
    var $image;
    var initialized = false;

    function open(qrUrl, eventName, eventDate) {
        if (!qrUrl) {
            return;
        }
        $title.text(eventName || '');
        $date.text(eventDate || '');
        if ($date.text().length) {
            $date.show();
        } else {
            $date.hide();
        }
        $image.attr('src', qrUrl);
        $overlay.removeAttr('hidden').addClass('is-open');
        $('body').addClass('qr-modal-open');
    }

    function close() {
        $overlay.attr('hidden', 'hidden').removeClass('is-open');
        $image.attr('src', '');
        $('body').removeClass('qr-modal-open');
    }

    return {
        init: function () {
            if (initialized) {
                return;
            }
            $overlay = $('#qr-modal');
            if (!$overlay.length) {
                return;
            }
            $title = $overlay.find('.qr-modal-title');
            $date = $overlay.find('.qr-modal-date');
            $image = $overlay.find('.qr-modal-image');

            $(document).on('click', '.qr-button', function (e) {
                e.preventDefault();
                var $btn = $(this);
                open(
                    $btn.attr('data-qr-url'),
                    $btn.attr('data-event-name'),
                    $btn.attr('data-event-date')
                );
            });

            $overlay.on('click', function (e) {
                if (e.target === $overlay.get(0)) {
                    close();
                }
            });

            $overlay.on('click', '.qr-modal-close', function (e) {
                e.preventDefault();
                close();
            });

            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' && $overlay.hasClass('is-open')) {
                    close();
                }
            });

            initialized = true;
        }
    };
});
