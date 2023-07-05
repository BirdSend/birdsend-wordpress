let disableFormSubmitBtns = (form) => {
    let submitBtns = form.querySelectorAll('button[type=submit], input[type=submit]');
    submitBtns.forEach(btn => {
        let url = new URL(form.action),
            spinner = url.protocol+'//'+url.hostname+'/img/forms/spinner.svg',
            replaceHTML = '<img src="'+spinner+'" style="width: 20px; display: inline;" /> '+btn.innerHTML;
        disableSubmitBtn(btn, replaceHTML);
    });
};

let enableFormSubmitBtns = (form) => {
    let submitBtns = form.querySelectorAll('button[type=submit], input[type=submit]');
    submitBtns.forEach(btn => {
        enableSubmitBtn(btn);
    });
};

let disableSubmitBtn = (btn, replaceHTML) => {
    btn.disabled = true;
    btn.style.cursor = 'progress';
    if (typeof replaceHTML !== 'undefined') {
        let html = encodeURIComponent(btn.innerHTML);
        btn.setAttribute('data-bs-html', html);
        btn.innerHTML = replaceHTML;
    }
};

let enableSubmitBtn = (btn) => {
    let html = decodeURIComponent(btn.getAttribute('data-bs-html'));
    if (html) {
        btn.innerHTML = html;
    }
    btn.style.cursor = '';
    btn.disabled = false;
};

let getDisplayStatsPixelSource = (form) => {
    return '/?bswp_form_display_stats_pixel=1&id=' + form.id;
};

let getDisplayStatsPixelSelector = (form) => {
    return 'img[data-birdsend-form-wp-display-stats="' + form.id + '"]';
};

let getDisplayStatsPixelElement = (form) => {
    return '<img width="1" height="0" data-birdsend-form-wp-display-stats="' + form.id + '" src="' + getDisplayStatsPixelSource(form) + '">';
};

let getSubmissionStatsUrl = (form) => {
    return '/?bswp_form_submission_stats=1&id=' + form.id;
};

export default {
    disableFormSubmitBtns,
    enableFormSubmitBtns,
    disableSubmitBtn,
    enableSubmitBtn,
    getDisplayStatsPixelSource,
    getDisplayStatsPixelSelector,
    getDisplayStatsPixelElement,
    getSubmissionStatsUrl,
};