/**
 * Zacatrus Events Registration JavaScript
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        /**
         * Initialize registration handlers
         */
        init: function () {
            var self = this;

            // Register button handler
            $(document).on('click', '.register-btn', function (e) {
                e.preventDefault();
                var $button = $(this);
                var eventId = $button.data('event-id');
                var url = $button.data('url');
                
                self.register(eventId, url, $button);
            });

            // Unregister button handler
            $(document).on('click', '.unregister-btn', function (e) {
                e.preventDefault();
                var $button = $(this);
                var eventId = $button.data('event-id');
                var url = $button.data('url');
                
                self.unregister(eventId, url, $button);
            });
        },

        /**
         * Register for event
         */
        register: function (eventId, url, $button) {
            var $card = $button.closest('.event-card');
            $card.addClass('loading');
            $button.prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: {
                    eventId: eventId
                },
                success: function (response) {
                    if (response.success) {
                        self.showMessage(response.message, 'success', $card);
                        // Reload page to update the card
                        setTimeout(function () {
                            location.reload();
                        }, 1500);
                    } else {
                        self.showMessage(response.message || $t('An error occurred.'), 'error', $card);
                        $card.removeClass('loading');
                        $button.prop('disabled', false);
                    }
                },
                error: function () {
                    self.showMessage($t('An error occurred while processing your registration.'), 'error', $card);
                    $card.removeClass('loading');
                    $button.prop('disabled', false);
                }
            });
        },

        /**
         * Unregister from event
         */
        unregister: function (eventId, url, $button) {
            var $card = $button.closest('.event-card');
            $card.addClass('loading');
            $button.prop('disabled', true);

            if (!confirm($t('Are you sure you want to unregister from this event?'))) {
                $card.removeClass('loading');
                $button.prop('disabled', false);
                return;
            }

            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: {
                    eventId: eventId
                },
                success: function (response) {
                    if (response.success) {
                        self.showMessage(response.message, 'success', $card);
                        // Reload page to update the card
                        setTimeout(function () {
                            location.reload();
                        }, 1500);
                    } else {
                        self.showMessage(response.message || $t('An error occurred.'), 'error', $card);
                        $card.removeClass('loading');
                        $button.prop('disabled', false);
                    }
                },
                error: function () {
                    self.showMessage($t('An error occurred while processing your unregistration.'), 'error', $card);
                    $card.removeClass('loading');
                    $button.prop('disabled', false);
                }
            });
        },

        /**
         * Show message
         */
        showMessage: function (message, type, $container) {
            type = type || 'info';
            var $message = $('<div class="message ' + type + '">' + message + '</div>');
            
            // Remove existing messages
            $container.find('.message').remove();
            
            // Add new message at the top of the card
            $container.prepend($message);
            
            // Scroll to message if needed
            $('html, body').animate({
                scrollTop: $message.offset().top - 100
            }, 300);
            
            // Auto-hide after 5 seconds
            setTimeout(function () {
                $message.fadeOut(function () {
                    $(this).remove();
                });
            }, 5000);
        }
    };
});

