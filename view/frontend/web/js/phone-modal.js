/**
 * Zacatrus Events Phone Modal
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'mage/validation'
], function ($, modal, $t) {
    // Check if modal is properly loaded
    if (typeof modal !== 'function') {
        console.error('Magento modal module not properly loaded');
        // Return a fallback object
        return {
            init: function() {
                console.error('Modal not available');
            },
            show: function() {},
            hide: function() {},
            showError: function() {}
        };
    }
    'use strict';

    return {
        /**
         * Initialize phone modal
         *
         * @param {String} modalId - ID of the modal element
         * @param {String} meetId - Meet ID
         * @param {String|null} prefillPhone - Phone number to pre-fill
         * @param {Function} onSubmit - Callback when form is submitted (phoneNumber, attendeeCount)
         * @param {Number} [maxAttendees=1] - Max attendees per registration (for reading attendeeCount from form)
         * @param {Object} [options] - { conditionsRequired: boolean } - if true, checkbox "accept conditions" must be checked
         */
        init: function (modalId, meetId, prefillPhone, onSubmit, maxAttendees, options) {
            var self = this;
            var $modal = $('#' + modalId);
            maxAttendees = parseInt(maxAttendees, 10) || 1;
            options = options || {};
            var conditionsRequired = !!options.conditionsRequired;

            if ($modal.length === 0) {
                console.error('Phone modal element not found: ' + modalId);
                return;
            }

            // Pre-fill phone number if provided
            if (prefillPhone) {
                $modal.find('input[name="phoneNumber"]').val(prefillPhone);
            }

            // Initialize Magento modal
            var modalOptions = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $t('Phone Number'),
                buttons: []
            };

            var modalInstance = modal(modalOptions, $modal);

            // Handle form submission
            $modal.find('form').on('submit', function (e) {
                e.preventDefault();
                
                var $form = $(this);

                // If conditions are required, check the checkbox
                if (conditionsRequired) {
                    var $acceptCheckbox = $form.find('input[name="acceptConditions"]');
                    if (!$acceptCheckbox.length || !$acceptCheckbox.is(':checked')) {
                        self.showError($modal, $t('You must accept the conditions to register.'));
                        return false;
                    }
                }

                var phoneNumber = $form.find('input[name="phoneNumber"]').val().trim();
                
                // Validate phone number: count digits only, but allow formatting characters (+, (, ), spaces, dashes)
                var digitsOnly = phoneNumber.replace(/[^0-9]/g, ''); // Count only digits
                
                if (digitsOnly.length < 9 || digitsOnly.length > 15) {
                    self.showError($modal, $t('Invalid phone number format. Please enter 9-15 digits (formatting like +, (, ) is allowed).'));
                    return false;
                }

                // Sanitize the phone number: only allow digits, +, (, ), spaces, and dashes
                phoneNumber = phoneNumber.replace(/[^0-9+\-() ]/g, '');

                // Read attendee count (1 to maxAttendees)
                var attendeeCount = 1;
                var $attendeeField = $form.find('select[name="attendeeCount"], input[name="attendeeCount"]');
                if ($attendeeField.length) {
                    attendeeCount = parseInt($attendeeField.val(), 10) || 1;
                    if (attendeeCount < 1 || attendeeCount > maxAttendees) {
                        attendeeCount = Math.max(1, Math.min(attendeeCount, maxAttendees));
                    }
                }

                // Close modal
                $modal.modal('closeModal');

                // Call callback with phone number and attendee count
                if (onSubmit && typeof onSubmit === 'function') {
                    onSubmit(phoneNumber, attendeeCount);
                }

                return false;
            });

            // Handle cancel button
            $modal.find('.phone-cancel, [id^="phone-cancel"]').on('click', function () {
                $modal.modal('closeModal');
            });

            // Store modal instance for later use
            $modal.data('modalInstance', modalInstance);

            return modalInstance;
        },

        /**
         * Show modal
         *
         * @param {String} modalId - ID of the modal element
         */
        show: function (modalId) {
            var $modal = $('#' + modalId);
            if ($modal.length > 0) {
                $modal.modal('openModal');
            }
        },

        /**
         * Hide modal
         *
         * @param {String} modalId - ID of the modal element
         */
        hide: function (modalId) {
            var $modal = $('#' + modalId);
            if ($modal.length > 0) {
                $modal.modal('closeModal');
            }
        },

        /**
         * Show error message in modal
         *
         * @param {jQuery} $modal - Modal jQuery object
         * @param {String} message - Error message
         */
        showError: function ($modal, message) {
            var $errorDiv = $modal.find('.phone-error');
            if ($errorDiv.length === 0) {
                $errorDiv = $('<div class="message message-error error phone-error"></div>');
                $modal.find('form').prepend($errorDiv);
            }
            $errorDiv.text(message).show();
            
            // Auto-hide after 5 seconds
            setTimeout(function () {
                $errorDiv.fadeOut();
            }, 5000);
        }
    };
});

