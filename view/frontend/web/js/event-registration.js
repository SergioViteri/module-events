/**
 * Zacatrus Events Registration JavaScript
 *
 * @category    Zacatrus
 * @package     Zaca_Events
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
                var meetId = $button.data('meet-id');
                var url = $button.data('url');
                
                self.register(meetId, url, $button);
            });

            // Unregister button handler
            $(document).on('click', '.unregister-btn', function (e) {
                e.preventDefault();
                var $button = $(this);
                var meetId = $button.data('meet-id');
                var url = $button.data('url');
                
                self.unregister(meetId, url, $button);
            });
        },

        /**
         * Register for meet
         */
        register: function (meetId, url, $button) {
            var self = this;
            var $card = $button.closest('.event-card');
            $card.addClass('loading');
            $button.prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: {
                    meetId: meetId
                },
                success: function (response) {
                    if (response.success) {
                        self.showMessage(response.message, 'success', $card);
                        // Update button to Unregister without reloading
                        self.updateButtonToUnregister($button, $card, meetId, response.status);
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
         * Unregister from meet
         */
        unregister: function (meetId, url, $button) {
            var self = this;
            var $card = $button.closest('.event-card');
            $card.addClass('loading');
            $button.prop('disabled', true);

            if (!confirm($t('Are you sure you want to unregister from this meet?'))) {
                $card.removeClass('loading');
                $button.prop('disabled', false);
                return;
            }

            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: {
                    meetId: meetId
                },
                success: function (response) {
                    if (response.success) {
                        self.showMessage(response.message, 'success', $card);
                        // Update button to Register without reloading
                        self.updateButtonToRegister($button, $card, meetId);
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
         * Update button to Unregister after successful registration
         */
        updateButtonToUnregister: function ($button, $card, meetId, status) {
            var $actions = $card.find('.event-actions');
            var unregisterUrl = $button.data('url').replace('register', 'unregister');
            
            // Remove registration status if exists
            $actions.find('.registration-status').remove();
            
            // Add registration status
            var statusText = status === 'waitlist' 
                ? $t('You are on the waitlist') 
                : $t('You are registered');
            var statusClass = status === 'waitlist' ? 'status-waitlist' : 'status-confirmed';
            var $statusDiv = $('<div class="registration-status"><span class="' + statusClass + '">' + statusText + '</span></div>');
            
            // Update button
            $button.removeClass('register-btn action primary')
                .addClass('unregister-btn action secondary')
                .text($t('Unregister'))
                .data('url', unregisterUrl)
                .prop('disabled', false);
            
            // Insert status before button
            $button.before($statusDiv);
            
            $card.removeClass('loading');
        },

        /**
         * Update button to Register after successful unregistration
         */
        updateButtonToRegister: function ($button, $card, meetId) {
            var $actions = $card.find('.event-actions');
            var registerUrl = $button.data('url').replace('unregister', 'register');
            var availableSlots = parseInt($card.find('.slots-count').text().split('/')[0].trim());
            
            // Remove registration status
            $actions.find('.registration-status').remove();
            
            // Update button
            $button.removeClass('unregister-btn action secondary waitlist-btn')
                .addClass('register-btn action primary')
                .data('url', registerUrl)
                .prop('disabled', false);
            
            // Update button text based on available slots
            if (availableSlots > 0) {
                $button.text($t('Register'));
            } else {
                $button.addClass('waitlist-btn').text($t('Join Waitlist'));
            }
            
            $card.removeClass('loading');
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

