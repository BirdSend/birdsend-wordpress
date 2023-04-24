import BSWPPostSubmitMessage from '../inc/BSWPPostSubmitMessage';
import BSWPBrandingFormatter from '../inc/branding-formatter';
import EventHandler from '../inc/event-handler';
import helpers from '../inc/helpers';

// Events
const LAST_SUBMITTED = 'su';
const LAST_SHOWN = 'sh';
const LAST_CLOSED = 'c';

/**
 * Form renderer base class
 */
var BSWPFormRenderer = function (form) {
    this.form = form;

    this.triggers = this.form.triggers;
    this.showWhen = this.triggers.show_when;
    this.smartExit = this.triggers.smart_exit[this.showWhen] || false;
    this.ruleInput = this.triggers.rule_inputs[this.showWhen];
    this.showFilterSets = this.getFilterSets('show');
    this.hideFilterSets = this.getFilterSets('hide').concat(this.getFilterSets('hide_more'));

    this.showedUp = false;

    // auto-show form based on form triggers/filters
    this.autoTrigger = true;
};

BSWPFormRenderer.prototype.getFilterSets = function (type) {
    var filterSets = this.triggers.filters[type] || [];
    if (filterSets.constructor !== Array) {
        filterSets = [filterSets];
    }
    return filterSets;
};

BSWPFormRenderer.prototype.disableAutoTrigger = function () {
    this.autoTrigger = false;
    return this;
};

BSWPFormRenderer.prototype.getFormSelector = function () {
    return '#bs-form-' + this.form.id + ':not(.bs-widget)';
};

BSWPFormRenderer.prototype.render = function () {
    this.addEventBtnLinks();

    if (! this.autoTrigger) {
        return;
    }

    if (this.smartExit || this.showWhen == 'exit') {
        this.applySmartExit();
    }
    
    if (this.showWhen == 'time-on-page') {
        return this.timeOnPage();
    }

    if (this.showWhen == 'scroll-page') {
        return this.scrollPage();
    }
};

BSWPFormRenderer.prototype.addEvent = EventHandler.add;

BSWPFormRenderer.prototype.removeEvent = EventHandler.remove;

BSWPFormRenderer.prototype.addEventFormSubmit = function () {
    var self = this,
        forms = document.querySelectorAll(self.getFormSelector() + ' form')
    for (var i = 0; i < forms.length; i++) {
        this.addEvent(forms[i], 'submit', function (e) {
            helpers.disableFormSubmitBtns(e.target);
            self.updateLastEvents(LAST_SUBMITTED);
            self.countSubmissions();

            if (self.form.is_post_submit_message) {
                BSWPPostSubmitMessage.create(e).submit();
            }
        });
    }
};

BSWPFormRenderer.prototype.addEventCloseBtn = function () {
    var closeBtns = document.querySelectorAll('.bs-closer-btn, .bs-popup-close-btn');
    for (var i = 0; i < closeBtns.length; i++) {
        this.addEvent(closeBtns[i], 'click', this.close.bind(this));
    }
};

BSWPFormRenderer.prototype.addEventBtnLinks = function () {
    var self = this,
        btnLinks = document.querySelectorAll('a[data-birdsend-form="' + this.form.id + '"], a[data-birsend-form="' + this.form.id + '"]');
    for (var i = 0; i < btnLinks.length; i++) {
        this.addEvent(btnLinks[i], 'click', function (e) {
            e.preventDefault(); self.show(true);
        });
    }
};

BSWPFormRenderer.prototype.applySmartExit = function () {
    var self = this;
    this.addEvent(document, 'mouseout', function (e) {
        e = e || window.event;
        
        if ( e.target.tagName && e.target.tagName.toLowerCase() == 'input' ) {
            return;
        }
        
        var viewportWidth = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
        if ( (e.clientX >= (viewportWidth - 50)) || e.clientY >= 50 ) {
            return;
        }
        
        var from = e.relatedTarget || e.toElement;
        if ( !from && ! self.showedUp) {
            self.show();
        }
    }, false);
};

BSWPFormRenderer.prototype.timeOnPage = function () {
    var self = this,
        timing = this.ruleInput.value * 1000;
    
    setTimeout(function () {
        if (! self.showedUp) {
            self.show();
        }
    }, timing);
};

BSWPFormRenderer.prototype.scrollPage = function () {
    var self = this,
        percentage = this.ruleInput.value,
        getDocHeight = function () {
            return Math.max(
                document.body.scrollHeight, document.documentElement.scrollHeight,
                document.body.offsetHeight, document.documentElement.offsetHeight,
                document.body.clientHeight, document.documentElement.clientHeight
            )
        },
        scrolledPercent = function () {
            var winheight = window.innerHeight || (document.documentElement || document.body).clientHeight;
            var docheight = getDocHeight();
            var scrollTop = window.pageYOffset || (document.documentElement || document.body.parentNode || document.body).scrollTop;
            var trackLength = docheight - winheight;
            var scrolledPercent = Math.floor(scrollTop/trackLength * 100) // gets percentage scrolled (ie: 80 or NaN if tracklength == 0)
            return scrolledPercent;
        };
    this.addEvent(window, 'scroll', function () {
        var value = scrolledPercent();
        if (value >= percentage && ! self.showedUp) {
            self.show();
        }
    });
};

BSWPFormRenderer.prototype.updateLastEvents = function (eventName) {
    var lastEvents = this.getLastEvents();
    if (typeof lastEvents[this.form.id] == 'undefined') lastEvents[this.form.id] = {};
    lastEvents[this.form.id][eventName] = Math.floor((new Date()).getTime() / 1000);

    var lastEventsStr = Object.keys(lastEvents).map(function (id) {
        return id+':'+Object.keys(lastEvents[id]).map(function (evt) {
            return evt+':'+lastEvents[id][evt];
        }).join(';')
    }).join(',');

    lastEventsStr = encodeURIComponent(lastEventsStr);

    var date = new Date(), days = 5 * 365;
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = 'bs-last-events=[' + lastEventsStr + ']; expires=' + date.toGMTString() + '; path=/';

    localStorage.setItem('bs.lastEvents', lastEventsStr);
};

BSWPFormRenderer.prototype.getLastEvents = function () {
    var lastEventsStr = this.getCookie('bs-last-events');
    if (! lastEventsStr || ! lastEventsStr.includes(']')) {
        lastEventsStr = localStorage.getItem('bs.lastEvents');
    }

    var lastEvents = {};
    decodeURIComponent(lastEventsStr || '').replace(/[\[\]]/g, '').split(',').forEach(function (formEvt) {
        if (! formEvt) return;
        var split = formEvt.split(':'),
            id = split.shift();
        split.join(':').split(';').forEach(function (evt) {
            var evtSplit = evt.split(':');
            if (typeof lastEvents[id] == 'undefined') lastEvents[id] = {};
            lastEvents[id][evtSplit.shift()] = evtSplit.pop();
        });
    });

    return lastEvents;
};

BSWPFormRenderer.prototype.flushLastEvents = function () {
    if (! this.getLastEvents()) {
        return;
    }
    document.cookie = 'bs-last-events=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/';
    localStorage.removeItem('bs.lastEvents');
};

BSWPFormRenderer.prototype.getCookie = function (name) {
    var value = '; ' + document.cookie;
    var parts = value.split('; ' + name + '=');
    if (parts.length == 2) return parts.pop().split(';').shift();
};

// check if the form passes form triggers/filters (for auto-trigger)
BSWPFormRenderer.prototype.checkFilter = function () {
    return this.passesFilterSets('show', true) && ! this.passesFilterSets('hide', false);
};

BSWPFormRenderer.prototype.passesFilterSets = function (type, defResult) {
    var filterSets = this[type + 'FilterSets'] || [];
    if (! filterSets.length) return defResult;
    
    var checkResults = [];
    for (var i = 0; i < filterSets.length; i++) {
        var activeFilters = Object.values(filterSets[i].filters).filter(function (filter) { return filter.active; });
        if (! activeFilters.length) continue;

        var passes = this.passesFilterSet(filterSets[i]);
        if (passes === true) {
            return true;
        }

        if (typeof passes === 'boolean') {
            checkResults.push(passes);
        }
    }
    
    if (! checkResults.length) return defResult;
    return checkResults.filter(function (result) { return result; }).length > 0;
};

BSWPFormRenderer.prototype.passesFilterSet = function (filterSet) {
    if (! Object.values(filterSet.filters).length) return true;

    var match = filterSet.match,
        filters = Object.values(filterSet.filters),
        lastEvents = this.getLastEvents()[this.form.id] || {},
        lastEventKeys = { 'submitted-in-days': LAST_SUBMITTED, 'closed-in-days': LAST_CLOSED, 'shown-in-days': LAST_SHOWN },
        filtersChecked = Object.keys(lastEventKeys),
        checkResults = [];

    for (var i = 0; i < filters.length; i++) {
        var filter = filters[i].filter, active = filters[i].active;

        if (! active || ! filtersChecked.includes(filter.name)) continue;
        var passes = false;

        if (Object.keys(lastEventKeys).includes(filter.name)) {
            var lastEventTime = lastEvents[lastEventKeys[filter.name]] || 0;
            if (! lastEventTime) continue;
            var now = Math.floor((new Date()).getTime() / 1000);
            passes = Math.floor((now - lastEventTime) / 1000 / 60 / 60 / 24) <= (filter.inputs.value || 0);
        }

        if (! passes && match == 'all') {
            return false;
        }

        if (passes && match == 'any') {
            return true;
        }

        checkResults.push(passes);
    }

    if (! checkResults.length) {
        return 'skipped'; // all the filters are skipped (not checkable)
    }

    return checkResults.filter(function (result) { return result; }).length > 0;
};

BSWPFormRenderer.prototype.show = function (skipFilterCheck) {
    if (! skipFilterCheck && ! this.checkFilter()) {
        return;
    }
    this.showForm();
    this.onShow();
};

BSWPFormRenderer.prototype.onShow = function () {
    this.showedUp = true;
    this.addEventCloseBtn();
    this.addEventFormSubmit();
    this.updateLastEvents(LAST_SHOWN);
    this.runFormScripts();
    this.addDisplayStatsPixel();
};

BSWPFormRenderer.prototype.runFormScripts = function () {
    var elms = document.querySelectorAll(this.getFormSelector())
    for (var i = 0; i < elms.length; i ++) {
        var scripts = elms[i].getElementsByTagName('script');
        for (var j = 0; j < scripts.length; j++) { eval(scripts[j].text); }
        BSWPBrandingFormatter(window, elms[i]);
    }
};

BSWPFormRenderer.prototype.showForm = function () {};

BSWPFormRenderer.prototype.close = function () {
    this.closeForm();
    this.updateLastEvents(LAST_CLOSED);
};

BSWPFormRenderer.prototype.closeForm = function () {};

BSWPFormRenderer.prototype.addDisplayStatsPixel = function () {
    if (! document.querySelector(helpers.getDisplayStatsPixelSelector(this.form))) {
        document.body.insertAdjacentHTML('beforeend', helpers.getDisplayStatsPixelElement(this.form));
    }
};

BSWPFormRenderer.prototype.countSubmissions = function () {
    fetch(helpers.getSubmissionStatsUrl(this.form));
};

export default BSWPFormRenderer;