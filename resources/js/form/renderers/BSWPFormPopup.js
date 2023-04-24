import BSWPFormRenderer from './BSWPFormRenderer';

/**
 * Form renderer class for popup form type
 */
var BSWPFormPopup = function (form) {
    BSWPFormRenderer.call(this, form);
    this.body = document.getElementsByTagName('body')[0];
}

BSWPFormPopup.prototype = Object.create(BSWPFormRenderer.prototype);
BSWPFormPopup.prototype.constructor = BSWPFormPopup;

BSWPFormPopup.prototype.showForm = function () {
    this.body.insertAdjacentHTML('beforeend', this.form.html);
};

BSWPFormPopup.prototype.closeForm = function () {
    var element = document.querySelector(this.getFormSelector());
    element.parentNode.removeChild(element);
};

export default BSWPFormPopup;