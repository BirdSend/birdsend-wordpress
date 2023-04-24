(()=>{"use strict";var __webpack_modules__={670:(e,t,o)=>{o.d(t,{Z:()=>n});var r=o(72),i=o(617);class n{constructor(e){this.form=e}static create(e){return e.preventDefault(),new this(e.target)}submit(){var e=this,t=new XMLHttpRequest;t.onreadystatechange=function(){if(4==this.readyState&&200==this.status){var t=JSON.parse(this.responseText);if(t.captcha)return e.showCaptchaPopup();if(i.Z.enableFormSubmitBtns(e.form),void 0===t.message&&t.error)return alert(t.error);var o=document.createElement("div"),r=+new Date;o.id="bs-message"+r,o.className="bs-message",e.form.parentNode.insertBefore(o,e.form),e.form.style.display="none",document.getElementById("bs-message"+r).innerHTML=t.message}},t.open(this.form.method,this.form.action,!0),t.setRequestHeader("X-Requested-With","XMLHttpRequest"),t.send(new FormData(this.form))}showCaptchaPopup(){r.Z.add(window,"message",this.onCaptchaSuccess),window._bsCaptchaPopup={form:this.form},document.body.insertAdjacentHTML("beforeend",this.modalCss+"\n"+this.modalTemplate);let e=document.createElement("iframe");e.frameborder="0",e.style.height="460px",e.src=this.resolveCaptchaUrl;let t=document.getElementById("bs-form-modal-captcha");t.querySelector(".bs-modal-body").appendChild(e),t.insertAdjacentHTML("afterend",this.modalBackdrop),t.style.display="block",r.Z.add(t,"click",this.closeModal)}onCaptchaSuccess(e){r.Z.remove(window,"message",this);let t=window._bsCaptchaPopup.form,o=new URL(t.action),i=o.protocol+"//"+o.hostname;if(o.port&&!["80","443"].includes(o.port)&&(i+=":"+o.port),e.origin!==i)return;let s=t.querySelector("input[type=hidden][name=g-recaptcha-response]");s?s.value=e.data:t.insertAdjacentHTML("beforeend",'<input type="hidden" name="g-recaptcha-response" value="'+e.data+'" />');let a=new n(t);a.closeModal(),a.submit()}closeModal(){let e=document.getElementById("bs-modal-css"),t=document.getElementById("bs-form-modal-captcha"),o=document.body.querySelector(".bs-modal-backdrop");e&&e.parentNode.removeChild(e),t&&t.parentNode.removeChild(t),o&&o.parentNode.removeChild(o)}get resolveCaptchaUrl(){let e=new URL(this.form.action),t={callback:"parent.postMessage"},o=e.protocol+"//"+e.hostname,r=Object.keys(t).map((e=>"data["+e+"]="+t[e])).join("&");return e.port&&!["80","443"].includes(e.port)&&(o+=":"+e.port),o+"/subscribe/resolve-captcha?"+r}get modalTemplate(){return'<div class="bs-modal" id="bs-form-modal-captcha" style="display: none;">            <div class="bs-modal-dialog">                <div class="bs-modal-content">                    <div class="bs-modal-body">                    </div>                </div>            </div>        </div>'}get modalBackdrop(){return'<div class="bs-modal-backdrop"></div>'}get modalCss(){return'<style id="bs-modal-css">.bs-modal{position:fixed;top:0;left:0;z-index:5000050;display:none;width:100%;height:100%;overflow-x:hidden;overflow-y:auto;outline:none}.bs-modal-dialog{position:relative;width:auto;margin:.5rem;pointer-events:none;display:flex;align-items:center;min-height:calc(100% - 1rem)}.bs-modal-content{position:relative;display:flex;flex-direction:column;width:100%;pointer-events:auto;background-color:#fff;background-clip:padding-box;border:1px solid rgba(0,0,0,.2);border-radius:.3rem;outline:0}.bs-modal-body{position:relative;flex:1 1 auto;padding:.25rem}.bs-modal-body iframe{width:100%;height:100%;display:block;border:0;padding:0;margin:0}.bs-modal-backdrop{position:fixed;top:0;left:0;z-index:5000040;width:100vw;height:100vh;background-color:#000;opacity:.5}@media (min-width:576px){.bs-modal-dialog{max-width:500px;margin:1.75rem auto;min-height:calc(100% - 3.5rem)}}</style>'}}},943:(e,t,o)=>{function r(e,t){for(var o=t.querySelectorAll("a.bs-branding-url"),r=0;r<o.length;r++)o[r].href=o[r].href+encodeURI(e.location.href)}o.d(t,{Z:()=>r})},72:(e,t,o)=>{o.d(t,{Z:()=>r});const r={add:(e,t,o)=>{e.addEventListener?e.addEventListener(t,o.bind(void 0),!1):e.attachEvent&&e.attachEvent("on"+t,o)},remove:(e,t,o)=>{e.removeEventListener?e.removeEventListener(t,o,!1):e.attachEvent&&e.detachEvent("on"+t,o)}}},617:(e,t,o)=>{o.d(t,{Z:()=>s});let r=(e,t)=>{if(e.disabled=!0,e.style.cursor="progress",void 0!==t){let o=encodeURIComponent(e.innerHTML);e.setAttribute("data-bs-html",o),e.innerHTML=t}},i=e=>{let t=decodeURIComponent(e.getAttribute("data-bs-html"));t&&(e.innerHTML=t),e.style.cursor="",e.disabled=!1},n=e=>"/?bswp_form_display_stats_pixel=1&id="+e.id;const s={disableFormSubmitBtns:e=>{e.querySelectorAll("button[type=submit], input[type=submit]").forEach((t=>{let o=new URL(e.action),i='<img src="'+o.protocol+"//"+o.hostname+'/img/forms/spinner.svg" style="width: 20px; display: inline;" /> '+t.innerHTML;r(t,i)}))},enableFormSubmitBtns:e=>{e.querySelectorAll("button[type=submit], input[type=submit]").forEach((e=>{i(e)}))},disableSubmitBtn:r,enableSubmitBtn:i,getDisplayStatsPixelSource:n,getDisplayStatsPixelSelector:e=>'img[data-birdsend-form-wp-display-stats="'+e.id+'"]',getDisplayStatsPixelElement:e=>'<img width="1" height="0" data-birdsend-form-wp-display-stats="'+e.id+'" src="'+n(e)+'">',getSubmissionStatsUrl:e=>"/?bswp_form_submission_stats=1&id="+e.id}},17:(__unused_webpack_module,__webpack_exports__,__webpack_require__)=>{__webpack_require__.d(__webpack_exports__,{Z:()=>__WEBPACK_DEFAULT_EXPORT__});var _inc_BSWPPostSubmitMessage__WEBPACK_IMPORTED_MODULE_0__=__webpack_require__(670),_inc_branding_formatter__WEBPACK_IMPORTED_MODULE_3__=__webpack_require__(943),_inc_event_handler__WEBPACK_IMPORTED_MODULE_1__=__webpack_require__(72),_inc_helpers__WEBPACK_IMPORTED_MODULE_2__=__webpack_require__(617);const LAST_SUBMITTED="su",LAST_SHOWN="sh",LAST_CLOSED="c";var BSWPFormRenderer=function(e){this.form=e,this.triggers=this.form.triggers,this.showWhen=this.triggers.show_when,this.smartExit=this.triggers.smart_exit[this.showWhen]||!1,this.ruleInput=this.triggers.rule_inputs[this.showWhen],this.showFilterSets=this.getFilterSets("show"),this.hideFilterSets=this.getFilterSets("hide").concat(this.getFilterSets("hide_more")),this.showedUp=!1,this.autoTrigger=!0};BSWPFormRenderer.prototype.getFilterSets=function(e){var t=this.triggers.filters[e]||[];return t.constructor!==Array&&(t=[t]),t},BSWPFormRenderer.prototype.disableAutoTrigger=function(){return this.autoTrigger=!1,this},BSWPFormRenderer.prototype.getFormSelector=function(){return"#bs-form-"+this.form.id+":not(.bs-widget)"},BSWPFormRenderer.prototype.render=function(){if(this.addEventBtnLinks(),this.autoTrigger)return(this.smartExit||"exit"==this.showWhen)&&this.applySmartExit(),"time-on-page"==this.showWhen?this.timeOnPage():"scroll-page"==this.showWhen?this.scrollPage():void 0},BSWPFormRenderer.prototype.addEvent=_inc_event_handler__WEBPACK_IMPORTED_MODULE_1__.Z.add,BSWPFormRenderer.prototype.removeEvent=_inc_event_handler__WEBPACK_IMPORTED_MODULE_1__.Z.remove,BSWPFormRenderer.prototype.addEventFormSubmit=function(){for(var e=this,t=document.querySelectorAll(e.getFormSelector()+" form"),o=0;o<t.length;o++)this.addEvent(t[o],"submit",(function(t){_inc_helpers__WEBPACK_IMPORTED_MODULE_2__.Z.disableFormSubmitBtns(t.target),e.updateLastEvents(LAST_SUBMITTED),e.countSubmissions(),e.form.is_post_submit_message&&_inc_BSWPPostSubmitMessage__WEBPACK_IMPORTED_MODULE_0__.Z.create(t).submit()}))},BSWPFormRenderer.prototype.addEventCloseBtn=function(){for(var e=document.querySelectorAll(".bs-closer-btn, .bs-popup-close-btn"),t=0;t<e.length;t++)this.addEvent(e[t],"click",this.close.bind(this))},BSWPFormRenderer.prototype.addEventBtnLinks=function(){for(var e=this,t=document.querySelectorAll('a[data-birdsend-form="'+this.form.id+'"], a[data-birsend-form="'+this.form.id+'"]'),o=0;o<t.length;o++)this.addEvent(t[o],"click",(function(t){t.preventDefault(),e.show(!0)}))},BSWPFormRenderer.prototype.applySmartExit=function(){var e=this;this.addEvent(document,"mouseout",(function(t){if(!(t=t||window.event).target.tagName||"input"!=t.target.tagName.toLowerCase()){var o=Math.max(document.documentElement.clientWidth,window.innerWidth||0);t.clientX>=o-50||t.clientY>=50||t.relatedTarget||t.toElement||e.showedUp||e.show()}}),!1)},BSWPFormRenderer.prototype.timeOnPage=function(){var e=this,t=1e3*this.ruleInput.value;setTimeout((function(){e.showedUp||e.show()}),t)},BSWPFormRenderer.prototype.scrollPage=function(){var e=this,t=this.ruleInput.value;this.addEvent(window,"scroll",(function(){var o,r,i,n;o=window.innerHeight||(document.documentElement||document.body).clientHeight,r=Math.max(document.body.scrollHeight,document.documentElement.scrollHeight,document.body.offsetHeight,document.documentElement.offsetHeight,document.body.clientHeight,document.documentElement.clientHeight),i=window.pageYOffset||(document.documentElement||document.body.parentNode||document.body).scrollTop,n=r-o,Math.floor(i/n*100)>=t&&!e.showedUp&&e.show()}))},BSWPFormRenderer.prototype.updateLastEvents=function(e){var t=this.getLastEvents();void 0===t[this.form.id]&&(t[this.form.id]={}),t[this.form.id][e]=Math.floor((new Date).getTime()/1e3);var o=Object.keys(t).map((function(e){return e+":"+Object.keys(t[e]).map((function(o){return o+":"+t[e][o]})).join(";")})).join(",");o=encodeURIComponent(o);var r=new Date;r.setTime(r.getTime()+15768e7),document.cookie="bs-last-events=["+o+"]; expires="+r.toGMTString()+"; path=/",localStorage.setItem("bs.lastEvents",o)},BSWPFormRenderer.prototype.getLastEvents=function(){var e=this.getCookie("bs-last-events");e&&e.includes("]")||(e=localStorage.getItem("bs.lastEvents"));var t={};return decodeURIComponent(e||"").replace(/[\[\]]/g,"").split(",").forEach((function(e){if(e){var o=e.split(":"),r=o.shift();o.join(":").split(";").forEach((function(e){var o=e.split(":");void 0===t[r]&&(t[r]={}),t[r][o.shift()]=o.pop()}))}})),t},BSWPFormRenderer.prototype.flushLastEvents=function(){this.getLastEvents()&&(document.cookie="bs-last-events=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/",localStorage.removeItem("bs.lastEvents"))},BSWPFormRenderer.prototype.getCookie=function(e){var t=("; "+document.cookie).split("; "+e+"=");if(2==t.length)return t.pop().split(";").shift()},BSWPFormRenderer.prototype.checkFilter=function(){return this.passesFilterSets("show",!0)&&!this.passesFilterSets("hide",!1)},BSWPFormRenderer.prototype.passesFilterSets=function(e,t){var o=this[e+"FilterSets"]||[];if(!o.length)return t;for(var r=[],i=0;i<o.length;i++)if(Object.values(o[i].filters).filter((function(e){return e.active})).length){var n=this.passesFilterSet(o[i]);if(!0===n)return!0;"boolean"==typeof n&&r.push(n)}return r.length?r.filter((function(e){return e})).length>0:t},BSWPFormRenderer.prototype.passesFilterSet=function(e){if(!Object.values(e.filters).length)return!0;for(var t=e.match,o=Object.values(e.filters),r=this.getLastEvents()[this.form.id]||{},i={"submitted-in-days":LAST_SUBMITTED,"closed-in-days":LAST_CLOSED,"shown-in-days":LAST_SHOWN},n=Object.keys(i),s=[],a=0;a<o.length;a++){var d=o[a].filter;if(o[a].active&&n.includes(d.name)){var l=!1;if(Object.keys(i).includes(d.name)){var c=r[i[d.name]]||0;if(!c)continue;var m=Math.floor((new Date).getTime()/1e3);l=Math.floor((m-c)/1e3/60/60/24)<=(d.inputs.value||0)}if(!l&&"all"==t)return!1;if(l&&"any"==t)return!0;s.push(l)}}return s.length?s.filter((function(e){return e})).length>0:"skipped"},BSWPFormRenderer.prototype.show=function(e){(e||this.checkFilter())&&(this.showForm(),this.onShow())},BSWPFormRenderer.prototype.onShow=function(){this.showedUp=!0,this.addEventCloseBtn(),this.addEventFormSubmit(),this.updateLastEvents(LAST_SHOWN),this.runFormScripts(),this.addDisplayStatsPixel()},BSWPFormRenderer.prototype.runFormScripts=function(){for(var elms=document.querySelectorAll(this.getFormSelector()),i=0;i<elms.length;i++){for(var scripts=elms[i].getElementsByTagName("script"),j=0;j<scripts.length;j++)eval(scripts[j].text);(0,_inc_branding_formatter__WEBPACK_IMPORTED_MODULE_3__.Z)(window,elms[i])}},BSWPFormRenderer.prototype.showForm=function(){},BSWPFormRenderer.prototype.close=function(){this.closeForm(),this.updateLastEvents(LAST_CLOSED)},BSWPFormRenderer.prototype.closeForm=function(){},BSWPFormRenderer.prototype.addDisplayStatsPixel=function(){document.querySelector(_inc_helpers__WEBPACK_IMPORTED_MODULE_2__.Z.getDisplayStatsPixelSelector(this.form))||document.body.insertAdjacentHTML("beforeend",_inc_helpers__WEBPACK_IMPORTED_MODULE_2__.Z.getDisplayStatsPixelElement(this.form))},BSWPFormRenderer.prototype.countSubmissions=function(){fetch(_inc_helpers__WEBPACK_IMPORTED_MODULE_2__.Z.getSubmissionStatsUrl(this.form))};const __WEBPACK_DEFAULT_EXPORT__=BSWPFormRenderer}},__webpack_module_cache__={};function __webpack_require__(e){var t=__webpack_module_cache__[e];if(void 0!==t)return t.exports;var o=__webpack_module_cache__[e]={exports:{}};return __webpack_modules__[e](o,o.exports,__webpack_require__),o.exports}__webpack_require__.d=(e,t)=>{for(var o in t)__webpack_require__.o(t,o)&&!__webpack_require__.o(e,o)&&Object.defineProperty(e,o,{enumerable:!0,get:t[o]})},__webpack_require__.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t);var __webpack_exports__={};(()=>{var e=__webpack_require__(17),t=function(t){e.Z.call(this,t),this.body=document.getElementsByTagName("body")[0]};(t.prototype=Object.create(e.Z.prototype)).constructor=t,t.prototype.scrollTo=function(e,t,o,r){var i=e.scrollTop,n=t-i,s=+new Date,a=function(){var t,d,l,c=+new Date-s;e.scrollTop=parseInt((t=c,d=i,l=n,(t/=o/2)<1?l/2*t*t+d:-l/2*(--t*(t-2)-1)+d)),c<o?requestAnimationFrame(a):(e.scrollTop=0,"function"==typeof r&&r())};a()},t.prototype.showForm=function(){this.body.style.paddingTop="100vh",this.body.insertAdjacentHTML("beforeend",this.form.html);var e=this;setTimeout((function(){window.scrollTo(0,0),e.addEvent(window,"scroll",e.onScrolling.bind(e))}),300)},t.prototype.onScrolling=function(){var e=document.querySelector(this.getFormSelector());if(e){var t=e.getBoundingClientRect();this.showedUp&&t.bottom<0&&(this.close(),this.removeEvent(window,"scroll",this.onScrolling))}},t.prototype.closeForm=function(){var e=this,t=document.querySelector(this.getFormSelector());t&&this.scrollTo(document.documentElement,t.scrollHeight,400,(function(){t.parentNode&&t.parentNode.removeChild(t),e.body.style.paddingTop=null}))};const o=t;var r=function(t){e.Z.call(this,t),this.body=document.getElementsByTagName("body")[0]};(r.prototype=Object.create(e.Z.prototype)).constructor=r,r.prototype.showForm=function(){this.body.insertAdjacentHTML("beforeend",this.form.html)},r.prototype.closeForm=function(){var e=document.querySelector(this.getFormSelector());e.parentNode.removeChild(e)};const i=r;var n=function(e){this.form=e,this.triggers=this.form.triggers,this.showFilterSets=this.getFilterSets("show"),this.hideFilterSets=this.getFilterSets("hide").concat(this.getFilterSets("hide_more")),this.showedUp=!1,this.autoTrigger=!0};(n.prototype=Object.create(e.Z.prototype)).constructor=n,n.prototype.showForm=function(){for(var e=document.querySelectorAll(this.getFormSelector()),t=0;t<e.length;t++)e[t].parentNode.style.removeProperty("display")},n.prototype.onShow=function(){this.showedUp=!0,this.addEventFormSubmit(),this.runFormScripts(),this.addDisplayStatsPixel()},e.Z.prototype.closeForm=function(){for(var e=document.querySelectorAll(this.getFormSelector()),t=0;t<e.length;t++)e[t].parentNode.removeChild(e[t])};const s=n;var a=function(e){this.form=e};(a.prototype=Object.create(e.Z.prototype)).constructor=a,a.prototype.getFormSelector=function(){return"#bs-form-"+this.form.id+".bs-widget"},a.prototype.onShow=function(){this.showedUp=!0,this.addEventFormSubmit(),this.runFormScripts(),this.addDisplayStatsPixel()};const d=a;window.BSWPFormRenderer=e.Z,window.BSWPFormWelcomeScreen=o,window.BSWPFormPopup=i,window.BSWPFormInContent=s,window.BSWPFormWidget=d;var l=function(e){this.forms=e,this.rendererClasses={"welcome-screen":o,popup:i},this.renderers={}};l.prototype.load=function(){this.loadForms();let e=(e,t)=>[...new Map(e.map((e=>[e[t],e]))).values()],t=e(this.forms.ics,"id"),o=e(this.forms.wgs,"id");t.forEach((e=>{let t=new s(e);t.checkFilter()?t.show():t.closeForm()})),o.forEach((e=>{new d(e).onShow()}))},l.prototype.loadForms=function(){if(this.forms.nics.length){var e=this;this.forms.nics.forEach((function(t){e.run(t)}))}},l.prototype.run=function(e){var t=new this.rendererClasses[e.type](e);t.checkFilter()&&void 0===this.renderers[e.type]?this.renderers[e.type]=t:t.disableAutoTrigger(),t.render()};const c=l;window.BSWPForm=c,window.bswpFormLoader=function(e){new c(e).load()},bswpFormLoader(_bswpForms)})()})();