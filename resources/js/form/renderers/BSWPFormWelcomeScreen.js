import BSWPFormRenderer from './BSWPFormRenderer';

/**
 * Form renderer class for welcome-screen form type
 */
var BSWPFormWelcomeScreen = function (form) {
    BSWPFormRenderer.call(this, form);
    this.body = document.getElementsByTagName('body')[0];
};

BSWPFormWelcomeScreen.prototype = Object.create(BSWPFormRenderer.prototype);
BSWPFormWelcomeScreen.prototype.constructor = BSWPFormWelcomeScreen;

BSWPFormWelcomeScreen.prototype.scrollTo = function (element, to, duration, callback) {
    var start = element.scrollTop,
        change = to - start,
        startDate = +new Date(),
        easeInOutQuad = function(t, b, c, d) {
            t /= d/2;
            if (t < 1) return c/2*t*t + b;
            t--;
            return -c/2 * (t*(t-2) - 1) + b;
        },
        animateScroll = function() {
            var currentDate = +new Date();
            var currentTime = currentDate - startDate;
            element.scrollTop = parseInt(easeInOutQuad(currentTime, start, change, duration));
            if (currentTime < duration) {
                requestAnimationFrame(animateScroll);
            } else {
                element.scrollTop = 0;
                if (typeof callback == 'function') {
                    callback();
                }
            }
        };
    animateScroll();
};

BSWPFormWelcomeScreen.prototype.showForm = function () {
    this.body.style.paddingTop = '100vh';
    this.body.insertAdjacentHTML('beforeend', this.form.html);
    var self = this;
    setTimeout(function () {
        window.scrollTo(0, 0);
        self.addEvent(window, 'scroll', self.onScrolling.bind(self));
    }, 300);
};

BSWPFormWelcomeScreen.prototype.onScrolling = function () {
    var form = document.querySelector(this.getFormSelector());
    if (! form) return;
    
    var rect = form.getBoundingClientRect();
    if (this.showedUp && rect.bottom < 0) {
        this.close();
        this.removeEvent(window, 'scroll', this.onScrolling);
    }
};

BSWPFormWelcomeScreen.prototype.closeForm = function () {
    var self = this,
        element = document.querySelector(this.getFormSelector());
    if (! element) return;
    this.scrollTo(document.documentElement, element.scrollHeight, 400, function () {
        if (element.parentNode) {
            element.parentNode.removeChild(element);
        }
        self.body.style.paddingTop = null;
    });
};

export default BSWPFormWelcomeScreen;