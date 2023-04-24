/**
 * Birdsend form loader
 *
 * @author Ahmad
 */

import BSWPForm from './loaders/BSWPForm';

window.BSWPForm = BSWPForm;

/**
 * Form loader function
 */
window.bswpFormLoader = function (f) {
    new BSWPForm(f).load();
};

(function () { bswpFormLoader(_bswpForms); })();