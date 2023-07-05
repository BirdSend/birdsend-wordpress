import {
    BSWPFormRenderer,
    BSWPFormWelcomeScreen,
    BSWPFormPopup,
    BSWPFormInContent,
    BSWPFormWidget
} from '../renderers/';

window.BSWPFormRenderer = BSWPFormRenderer;
window.BSWPFormWelcomeScreen = BSWPFormWelcomeScreen;
window.BSWPFormPopup = BSWPFormPopup;
window.BSWPFormInContent = BSWPFormInContent;
window.BSWPFormWidget = BSWPFormWidget;

/**
 * BSWPForm class
 *
 * @param {array} forms
 */
var BSWPForm = function (forms) {
    this.forms = forms;
    this.rendererClasses = {
        'welcome-screen': BSWPFormWelcomeScreen,
        'popup': BSWPFormPopup,
    };
    this.renderers = {};
}

BSWPForm.prototype.load = function () {
    this.loadForms();

    let arrayUniqueByKey = (array, key) => [...new Map(array.map(item => [item[key], item])).values()];

    let ics = arrayUniqueByKey(this.forms.ics, 'id'),
        wgs = arrayUniqueByKey(this.forms.wgs, 'id');

    ics.forEach(form => {
        let renderer = new BSWPFormInContent(form);
        if (renderer.checkFilter()) {
            renderer.show();
        } else {
            renderer.closeForm();
        }
    });

    wgs.forEach(form => {
        (new BSWPFormWidget(form)).onShow();
    });
};

BSWPForm.prototype.loadForms = function () {
    if (! this.forms.nics.length) return;
    var self = this;
    this.forms.nics.forEach(function (form) {
        self.run(form);
    });
};

BSWPForm.prototype.run = function (form) {
    var renderer = new this.rendererClasses[form.type](form);
    if (! renderer.checkFilter() || typeof this.renderers[form.type] !== 'undefined') {
        renderer.disableAutoTrigger();
    } else {
        this.renderers[form.type] = renderer;
    }
    renderer.render();
};

export default BSWPForm;