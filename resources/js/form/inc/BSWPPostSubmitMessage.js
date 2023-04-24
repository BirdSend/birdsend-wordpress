import EventHandler from './event-handler';
import helpers from './helpers';

export default class BSWPPostSubmitMessage {
    constructor(form) {
        this.form = form;
    }

    static create(evt) {
        evt.preventDefault();
        return new this(evt.target);
    }

    submit() {
        var self = this,
            xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var response = JSON.parse(this.responseText);
                if (response.captcha) {
                    return self.showCaptchaPopup()
                }
                helpers.enableFormSubmitBtns(self.form);
                if (typeof response.message === 'undefined' && response.error) {
                    return alert(response.error);
                }
                var bsMsg = document.createElement('div'), t = + new Date();
                bsMsg.id = 'bs-message'+t; bsMsg.className = 'bs-message';
                self.form.parentNode.insertBefore(bsMsg, self.form);
                self.form.style.display = 'none';
                document.getElementById('bs-message'+t).innerHTML = response.message;
            }
        };
        xhr.open(this.form.method, this.form.action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(new FormData(this.form));
    }

    showCaptchaPopup() {
        EventHandler.add(window, 'message', this.onCaptchaSuccess)
        
        window._bsCaptchaPopup = { form: this.form };
        document.body.insertAdjacentHTML('beforeend', this.modalCss+"\n"+this.modalTemplate);
        
        let iframe = document.createElement('iframe');
        iframe.frameborder = '0'; iframe.style.height = '460px'; iframe.src = this.resolveCaptchaUrl;

        let modal = document.getElementById('bs-form-modal-captcha'),
            modalBody = modal.querySelector('.bs-modal-body');
        
        modalBody.appendChild(iframe);
        modal.insertAdjacentHTML('afterend', this.modalBackdrop);
        modal.style.display = 'block';

        EventHandler.add(modal, 'click', this.closeModal);
    }

    onCaptchaSuccess(event) {
        EventHandler.remove(window, 'message', this);

        let form = window._bsCaptchaPopup.form,
            url = new URL(form.action),
            action = url.protocol+'//'+url.hostname;

        if (!!url.port && ! ['80', '443'].includes(url.port)) {
            action += ':'+url.port;
        }

        if (event.origin !== action) {
            return;
        }
        
        let hiddenToken = form.querySelector('input[type=hidden][name=g-recaptcha-response]');
        if (! hiddenToken) {
            form.insertAdjacentHTML('beforeend', '<input type="hidden" name="g-recaptcha-response" value="'+event.data+'" />');
        } else {
            hiddenToken.value = event.data;
        }

        let handler = new BSWPPostSubmitMessage(form);
        handler.closeModal(); handler.submit();
    }

    closeModal() {
        let style = document.getElementById('bs-modal-css'),
            modal = document.getElementById('bs-form-modal-captcha'),
            backdrop = document.body.querySelector('.bs-modal-backdrop');
        style && style.parentNode.removeChild(style);
        modal && modal.parentNode.removeChild(modal);
        backdrop && backdrop.parentNode.removeChild(backdrop);
    }

    get resolveCaptchaUrl() {
        let url = new URL(this.form.action),
            params = { callback: 'parent.postMessage' },
            action = url.protocol+'//'+url.hostname,
            queryStr = Object.keys(params).map(key => 'data['+key+']='+params[key]).join('&');

        if (!!url.port && ! ['80', '443'].includes(url.port)) {
            action += ':'+url.port;
        }

        return action+'/subscribe/resolve-captcha?'+queryStr;
    }

    get modalTemplate() {
        return '<div class="bs-modal" id="bs-form-modal-captcha" style="display: none;">\
            <div class="bs-modal-dialog">\
                <div class="bs-modal-content">\
                    <div class="bs-modal-body">\
                    </div>\
                </div>\
            </div>\
        </div>';
    }

    get modalBackdrop() {
        return '<div class="bs-modal-backdrop"></div>';
    }

    get modalCss() {
        return '<style id="bs-modal-css">.bs-modal{position:fixed;top:0;left:0;z-index:5000050;display:none;width:100%;height:100%;overflow-x:hidden;overflow-y:auto;outline:none}.bs-modal-dialog{position:relative;width:auto;margin:.5rem;pointer-events:none;display:flex;align-items:center;min-height:calc(100% - 1rem)}.bs-modal-content{position:relative;display:flex;flex-direction:column;width:100%;pointer-events:auto;background-color:#fff;background-clip:padding-box;border:1px solid rgba(0,0,0,.2);border-radius:.3rem;outline:0}.bs-modal-body{position:relative;flex:1 1 auto;padding:.25rem}.bs-modal-body iframe{width:100%;height:100%;display:block;border:0;padding:0;margin:0}.bs-modal-backdrop{position:fixed;top:0;left:0;z-index:5000040;width:100vw;height:100vh;background-color:#000;opacity:.5}@media (min-width:576px){.bs-modal-dialog{max-width:500px;margin:1.75rem auto;min-height:calc(100% - 3.5rem)}}</style>';
    }
}