/**
 * Zacatrus Events Registration JavaScript
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

define([
    'jquery',
    'mage/translate',
    'Zaca_Events/js/phone-modal'
], function ($, $t, phoneModal) {
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

            // Update phone number link handler
            $(document).on('click', '.update-phone-link', function (e) {
                e.preventDefault();
                var $link = $(this);
                var meetId = $link.data('meet-id');
                
                self.showUpdatePhoneModal(meetId, $link);
            });
        },

        /**
         * Register for meet
         */
        register: function (meetId, url, $button, phoneNumber) {
            var self = this;
            var $card = $button.closest('.event-card');
            $card.addClass('loading');
            $button.prop('disabled', true);

            var requestData = {
                meetId: meetId
            };

            // Add phone number if provided
            if (phoneNumber) {
                requestData.phoneNumber = phoneNumber;
            }

            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: requestData,
                success: function (response) {
                    if (response.success) {
                        self.showMessage(response.message, 'success', $card);
                        // Update available slots if provided
                        if (response.availableSlots !== undefined && response.maxSlots !== undefined) {
                            self.updateAvailableSlots($card, response.availableSlots, response.maxSlots);
                        }
                        // Update button to Unregister without reloading
                        self.updateButtonToUnregister($button, $card, meetId, response.status, response.calendarIcalUrl, response.calendarGoogleUrl);
                    } else {
                        // Check if phone number is required
                        if (response.requiresPhone) {
                            $card.removeClass('loading');
                            $button.prop('disabled', false);
                            self.showPhoneModal(meetId, $button, function(submittedPhone) {
                                // Retry registration with phone number
                                self.register(meetId, url, $button, submittedPhone);
                            });
                        } else {
                            self.showMessage(response.message || $t('An error occurred.'), 'error', $card);
                            $card.removeClass('loading');
                            $button.prop('disabled', false);
                        }
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

            //console.log('unregistering from meetId: ' + meetId + ' url: ' + url);
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
                        // Update available slots if provided
                        if (response.availableSlots !== undefined && response.maxSlots !== undefined) {
                            self.updateAvailableSlots($card, response.availableSlots, response.maxSlots);
                        }
                        // Update button to Register without reloading and hide calendar links
                        self.updateButtonToRegister($button, $card, meetId, response.availableSlots);
                        // Remove calendar links when unregistering
                        self.hideCalendarLinks($card);
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
        updateButtonToUnregister: function ($button, $card, meetId, status, calendarIcalUrl, calendarGoogleUrl) {
            var $actions = $card.find('.event-actions');
            var currentUrl = $button.data('url') || '';
            var unregisterUrl = currentUrl;
            
            // Only replace if URL contains 'register' and doesn't already contain 'unregister'
            if (currentUrl.indexOf('register') !== -1 && currentUrl.indexOf('unregister') === -1) {
                unregisterUrl = currentUrl.replace('register', 'unregister');
            }
            
            // Remove registration status if exists
            $actions.find('.registration-status').remove();
            
            // Add registration status
            var statusText = status === 'waitlist' 
                ? $t('You are on the waitlist') 
                : $t('You are registered');
            var statusClass = status === 'waitlist' ? 'status-waitlist' : 'status-confirmed';
            var $statusDiv = $('<div class="registration-row"><div class="registration-status"><span class="' + statusClass + '">' + statusText + '</span></div></div>');
            
            // Update button
            $button.removeClass('register-btn action primary waitlist-btn')
                .addClass('unregister-btn action secondary')
                .text($t('Unregister'))
                .data('url', unregisterUrl)
                .prop('disabled', false);
            
            // Wrap button in registration-row if not already
            if (!$button.closest('.registration-row').length) {
                $button.wrap('<div class="registration-row"></div>');
            }
            
            // Insert status before button's parent (registration-row)
            var $registrationRow = $button.closest('.registration-row');
            $registrationRow.prepend($statusDiv.find('.registration-status'));
            
            // Show or hide calendar links and phone link based on status
            if (status === 'confirmed' && calendarIcalUrl && calendarGoogleUrl) {
                this.showCalendarLinks($card, calendarIcalUrl, calendarGoogleUrl);
                this.showPhoneLink($card, meetId);
            } else {
                this.hideCalendarLinks($card);
                this.hidePhoneLink($card);
            }
            
            $card.removeClass('loading');
        },

        /**
         * Update button to Register after successful unregistration
         */
        updateButtonToRegister: function ($button, $card, meetId, availableSlots) {
            var $actions = $card.find('.event-actions');
            var registerUrl = $button.data('url').replace('unregister', 'register');
            
            // Get available slots from parameter or DOM
            if (availableSlots === undefined) {
                availableSlots = parseInt($card.find('.slots-count').text().split('/')[0].trim());
            }
            
            // Remove registration status and registration-row wrapper
            $actions.find('.registration-status').remove();
            $actions.find('.registration-row').each(function() {
                var $row = $(this);
                $row.contents().unwrap();
            });
            
            // Update button
            $button.removeClass('unregister-btn action secondary waitlist-btn')
                .addClass('register-btn action primary')
                .data('url', registerUrl)
                .prop('disabled', false);
            
            // Update button text based on available slots
            if (availableSlots > 0) {
                $button.removeClass('waitlist-btn').text($t('Register'));
            } else {
                $button.addClass('waitlist-btn').text($t('Join Waitlist'));
            }
            
            // Hide phone link when unregistering
            this.hidePhoneLink($card);
            
            $card.removeClass('loading');
        },

        /**
         * Update available slots display
         */
        updateAvailableSlots: function ($card, availableSlots, maxSlots) {
            var $slotsCount = $card.find('.slots-count');
            if ($slotsCount.length) {
                $slotsCount.text(availableSlots + ' / ' + maxSlots);
                // Update class based on availability
                $slotsCount.removeClass('available full');
                if (availableSlots > 0) {
                    $slotsCount.addClass('available');
                } else {
                    $slotsCount.addClass('full');
                }
            }
        },

        /**
         * Show calendar links
         */
        showCalendarLinks: function ($card, icalUrl, googleUrl) {
            // Remove existing calendar links wrapper (which may contain both calendar links and phone link)
            var $existingWrapper = $card.find('.calendar-links').closest('div').parent();
            if ($existingWrapper.length && $existingWrapper.find('.calendar-links').length) {
                // Only remove if it's the wrapper we created (has calendar-links)
                $existingWrapper.remove();
            } else {
                // Fallback: just remove calendar links
                $card.find('.calendar-links').closest('div').remove();
            }
            
            // Create calendar links HTML (matching template structure)
            var $calendarLinksWrapper = $('<div></div>');
            var $calendarLinks = $('<div class="calendar-links" style="margin-top: 8px;">' +
                '<a href="' + icalUrl + '" style="margin-right: 12px;">' + $t('iCal') + '</a>' +
                '<a href="' + googleUrl + '" target="_blank">' + $t('Add to Google Calendar') + '</a>' +
                '</div>');
            $calendarLinksWrapper.append($calendarLinks);
            
            // Insert after event-actions div (matching template structure where calendar links are outside event-actions)
            var $actions = $card.find('.event-actions');
            if ($actions.length) {
                $actions.after($calendarLinksWrapper);
            } else {
                // Fallback: append to card if event-actions not found
                $card.append($calendarLinksWrapper);
            }
        },

        /**
         * Hide calendar links
         */
        hideCalendarLinks: function ($card) {
            // Remove calendar links and their wrapper div
            $card.find('.calendar-links').closest('div').remove();
        },

        /**
         * Show phone link
         */
        showPhoneLink: function ($card, meetId) {
            // Remove existing phone link
            this.hidePhoneLink($card);
            
            // Get update phone URL - try to get from existing link or construct it
            var updatePhoneUrl = '/events/index/updatephone';
            var $existingLink = $card.find('.update-phone-link');
            if ($existingLink.length) {
                updatePhoneUrl = $existingLink.data('url') || updatePhoneUrl;
            }
            
            // Create phone link HTML
            var $phoneLinkWrapper = $('<div class="update-phone-wrapper" style="margin-top: 8px;"></div>');
            var $phoneLink = $('<a href="#" class="update-phone-link" data-meet-id="' + meetId + '" data-url="' + updatePhoneUrl + '">' + 
                $t('Update Phone Number') + '</a>');
            $phoneLinkWrapper.append($phoneLink);
            
            // Find the calendar links wrapper (parent div that contains calendar-links) and add phone link to it
            var $calendarWrapper = $card.find('.calendar-links').closest('div');
            if ($calendarWrapper.length) {
                // Add phone link to the same wrapper as calendar links (matching template structure)
                $calendarWrapper.append($phoneLinkWrapper);
            } else {
                // If no calendar links, add after event-actions
                var $actions = $card.find('.event-actions');
                if ($actions.length) {
                    $actions.after($phoneLinkWrapper);
                } else {
                    // Fallback: append to card
                    $card.append($phoneLinkWrapper);
                }
            }
        },

        /**
         * Hide phone link
         */
        hidePhoneLink: function ($card) {
            // Remove phone link wrapper
            $card.find('.update-phone-wrapper').remove();
        },

        /**
         * Show phone modal for registration
         */
        showPhoneModal: function (meetId, $button, onSubmitCallback) {
            var self = this;
            var modalId = 'phone-modal-' + meetId;
            
            // Get most recent phone number to pre-fill
            this.getMostRecentPhoneNumber(meetId, function(prefillPhone) {
                // Create modal if it doesn't exist
                var $modal = $('#' + modalId);
                if ($modal.length === 0) {
                    // Create modal HTML
                    var modalHtml = '<div id="' + modalId + '" class="phone-modal" style="display: none;" data-meet-id="' + meetId + '">' +
                        '<form id="phone-form-' + meetId + '" class="phone-form">' +
                        '<fieldset class="fieldset">' +
                        '<div class="field required">' +
                        '<label class="label" for="phone-number-' + meetId + '">' +
                        '<span>' + $t('Phone Number') + '</span>' +
                        '</label>' +
                        '<div class="control">' +
                        '<input type="tel" id="phone-number-' + meetId + '" name="phoneNumber" class="input-text" ' +
                        'placeholder="' + $t('e.g. 618123456') + '" value="' + (prefillPhone || '') + '" />' +
                        '<div class="note">' + $t('Please enter a contact phone number (9-15 digits). Formatting like +, (, ) is allowed.') + '</div>' +
                        '</div>' +
                        '</div>' +
                        '</fieldset>' +
                        '<div class="actions-toolbar">' +
                        '<div class="primary">' +
                        '<button type="submit" class="action primary">' +
                        '<span>' + $t('Submit') + '</span>' +
                        '</button>' +
                        '</div>' +
                        '<div class="secondary">' +
                        '<button type="button" class="action secondary phone-cancel">' +
                        '<span>' + $t('Cancel') + '</span>' +
                        '</button>' +
                        '</div>' +
                        '</div>' +
                        '</form>' +
                        '</div>';
                    $('body').append(modalHtml);
                    $modal = $('#' + modalId);
                } else {
                    // Update pre-filled value
                    $modal.find('input[name="phoneNumber"]').val(prefillPhone || '');
                }

                // Initialize modal
                phoneModal.init(modalId, meetId, prefillPhone, function(phoneNumber) {
                    if (onSubmitCallback && typeof onSubmitCallback === 'function') {
                        onSubmitCallback(phoneNumber);
                    }
                });

                // Show modal
                phoneModal.show(modalId);
            });
        },

        /**
         * Show phone modal for updating existing registration
         */
        showUpdatePhoneModal: function (meetId, $link) {
            var self = this;
            var modalId = 'phone-modal-update-' + meetId;
            var $card = $link.closest('.event-card');
            
            // Get current phone number and most recent from other registrations
            this.getCurrentPhoneNumber(meetId, function(currentPhone) {
                self.getMostRecentPhoneNumber(meetId, function(prefillPhone) {
                    // Use current phone if exists, otherwise use pre-filled from other registrations
                    var phoneToShow = currentPhone || prefillPhone || '';
                    
                    // Create modal if it doesn't exist
                    var $modal = $('#' + modalId);
                    if ($modal.length === 0) {
                        var modalHtml = '<div id="' + modalId + '" class="phone-modal" style="display: none;" data-meet-id="' + meetId + '">' +
                            '<form id="phone-form-update-' + meetId + '" class="phone-form">' +
                            '<fieldset class="fieldset">' +
                            '<div class="field required">' +
                            '<label class="label" for="phone-number-update-' + meetId + '">' +
                            '<span>' + $t('Phone Number') + '</span>' +
                            '</label>' +
                            '<div class="control">' +
                            '<input type="tel" id="phone-number-update-' + meetId + '" name="phoneNumber" class="input-text" ' +
                            'placeholder="' + $t('e.g. 618123456') + '" value="' + phoneToShow + '" />' +
                            '<div class="note">' + $t('Please enter a contact phone number (9-15 digits). Formatting like +, (, ) is allowed.') + '</div>' +
                            '</div>' +
                            '</div>' +
                            '</fieldset>' +
                            '<div class="actions-toolbar">' +
                            '<div class="primary">' +
                            '<button type="submit" class="action primary">' +
                            '<span>' + $t('Update') + '</span>' +
                            '</button>' +
                            '</div>' +
                            '<div class="secondary">' +
                            '<button type="button" class="action secondary phone-cancel">' +
                            '<span>' + $t('Cancel') + '</span>' +
                            '</button>' +
                            '</div>' +
                            '</div>' +
                            '</form>' +
                            '</div>';
                        $('body').append(modalHtml);
                        $modal = $('#' + modalId);
                    } else {
                        $modal.find('input[name="phoneNumber"]').val(phoneToShow);
                    }

                    // Initialize modal
                    phoneModal.init(modalId, meetId, phoneToShow, function(phoneNumber) {
                        self.updatePhoneNumber(meetId, phoneNumber, $card);
                    });

                    // Show modal
                    phoneModal.show(modalId);
                });
            });
        },

        /**
         * Update phone number for existing registration
         */
        updatePhoneNumber: function (meetId, phoneNumber, $card) {
            var self = this;
            var $link = $card.find('.update-phone-link');
            var updateUrl = $link.data('url') || '/events/index/updatephone';
            
            $card.addClass('loading');

            $.ajax({
                url: updateUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    meetId: meetId,
                    phoneNumber: phoneNumber
                },
                success: function (response) {
                    if (response.success) {
                        self.showMessage(response.message, 'success', $card);
                    } else {
                        self.showMessage(response.message || $t('An error occurred.'), 'error', $card);
                    }
                    $card.removeClass('loading');
                },
                error: function () {
                    self.showMessage($t('An error occurred while updating your phone number.'), 'error', $card);
                    $card.removeClass('loading');
                }
            });
        },

        /**
         * Get most recent phone number from other registrations
         */
        getMostRecentPhoneNumber: function (meetId, callback) {
            $.ajax({
                url: '/events/index/getphone',
                type: 'GET',
                dataType: 'json',
                data: {
                    excludeMeetId: meetId
                },
                success: function (response) {
                    if (response.success && response.phoneNumber) {
                        callback(response.phoneNumber);
                    } else {
                        callback(null);
                    }
                },
                error: function () {
                    callback(null);
                }
            });
        },

        /**
         * Get current phone number for this registration
         */
        getCurrentPhoneNumber: function (meetId, callback) {
            $.ajax({
                url: '/events/index/getphone',
                type: 'GET',
                dataType: 'json',
                data: {
                    meetId: meetId
                },
                success: function (response) {
                    if (response.success && response.phoneNumber) {
                        callback(response.phoneNumber);
                    } else {
                        callback(null);
                    }
                },
                error: function () {
                    callback(null);
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

