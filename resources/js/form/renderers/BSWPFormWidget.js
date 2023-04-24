import BSWPFormRenderer from './BSWPFormRenderer';

/**
 * Form renderer class for in-content form type
 */
var BSWPFormWidget = function (form) {
    this.form = form;
};

BSWPFormWidget.prototype = Object.create(BSWPFormRenderer.prototype);
BSWPFormWidget.prototype.constructor = BSWPFormWidget;

BSWPFormWidget.prototype.getFormSelector = function () {
    return '#bs-form-' + this.form.id + '.bs-widget';
};

BSWPFormWidget.prototype.onShow = function () {
    this.showedUp = true;
    this.addEventFormSubmit();
    this.runFormScripts();
    this.addDisplayStatsPixel();
};

export default BSWPFormWidget;