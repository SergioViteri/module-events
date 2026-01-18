/**
 * Zacatrus Events RequireJS Configuration
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

var config = {
    map: {
        '*': {
            'zacatrusEventsRegistration': 'Zaca_Events/js/event-registration'
        }
    },
    shim: {
        'Zaca_Events/js/phone-modal': {
            deps: ['jquery', 'Magento_Ui/js/modal/modal']
        },
        'Zaca_Events/js/event-registration': {
            deps: ['jquery', 'mage/translate', 'Zaca_Events/js/phone-modal']
        }
    }
};

