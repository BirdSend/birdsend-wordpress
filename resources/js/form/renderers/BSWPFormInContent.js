import BSWPFormRenderer from './BSWPFormRenderer';

/**
 * Form renderer class for in-content form type
 */
var BSWPFormInContent = function (form) {
    this.form = form;

    this.triggers = this.form.triggers;
    this.showFilterSets = this.getFilterSets('show');
    this.hideFilterSets = this.getFilterSets('hide').concat(this.getFilterSets('hide_more'));

    this.showedUp = false;

    // auto-show form based on form triggers/filters
    this.autoTrigger = true;
};

BSWPFormInContent.prototype = Object.create(BSWPFormRenderer.prototype);
BSWPFormInContent.prototype.constructor = BSWPFormInContent;

BSWPFormInContent.prototype.showForm = function () {
    var elms = document.querySelectorAll(this.getFormSelector())
    for (var i = 0; i < elms.length; i ++) {
        elms[i].parentNode.style.removeProperty('display');
    }
};

BSWPFormInContent.prototype.onShow = function () {
    this.showedUp = true;
    this.addEventFormSubmit();
    this.runFormScripts();
    this.addDisplayStatsPixel();
};

BSWPFormRenderer.prototype.closeForm = function () {
    var elms = document.querySelectorAll(this.getFormSelector())
    for (var i = 0; i < elms.length; i ++) {
        elms[i].parentNode.removeChild(elms[i]);
    }
};

export default BSWPFormInContent;