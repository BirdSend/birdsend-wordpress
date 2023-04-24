export default function (win, elm) {
    var brandingLinks = elm.querySelectorAll('a.bs-branding-url');
    for (var i = 0; i < brandingLinks.length; i++) {
        brandingLinks[i].href = brandingLinks[i].href + encodeURI(win.location.href);
    }
}