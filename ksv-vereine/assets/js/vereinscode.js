/* global ksvVereinscode, qrcode */
(function () {
    'use strict';

    var box = document.querySelector('.ksv-vereinscode');
    var cfg = window.ksvVereinscode || { i18n: {} };
    if (!box || !cfg.ajaxUrl) {
        return;
    }

    var button = box.querySelector('.ksv-vereinscode__button');
    var meldung = box.querySelector('.ksv-vereinscode__meldung');
    var status = box.querySelector('.ksv-vereinscode__status');
    var ergebnis = box.querySelector('.ksv-vereinscode__ergebnis');
    var codeEl = box.querySelector('.ksv-vereinscode__code');
    var qrEl = box.querySelector('.ksv-vereinscode__qr');
    var druckenBtn = box.querySelector('.ksv-vereinscode__drucken');
    if (!button) {
        return;
    }

    var aktuellerCode = '';
    var aktuellesQr = '';

    function esc(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function format(template, a, b) {
        return String(template || '').replace('%1$s', a).replace('%2$s', b);
    }

    function qrSvg(text) {
        if (typeof qrcode !== 'function') {
            return '';
        }
        var qr = qrcode(0, 'M');
        qr.addData(text);
        qr.make();
        return qr.createSvgTag({ cellSize: 8, margin: 4, scalable: true, alt: cfg.i18n.qrAlt || '' });
    }

    function zeigeMeldung(text, istFehler) {
        if (!meldung) {
            return;
        }
        meldung.textContent = text || '';
        meldung.classList.toggle('ksv-vereinscode__meldung--fehler', !!istFehler);
    }

    button.addEventListener('click', function () {
        var version = parseInt(box.getAttribute('data-version') || '0', 10) || 0;
        if (version > 0 && !window.confirm(cfg.i18n.bestaetigung)) {
            return;
        }

        button.disabled = true;
        zeigeMeldung(cfg.i18n.laedt, false);

        var body = new URLSearchParams();
        body.append('action', cfg.action);
        body.append('post_id', box.getAttribute('data-post-id') || '0');
        body.append('nonce', box.getAttribute('data-nonce') || '');
        body.append('version', String(version));

        fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (res) {
                return res.json().catch(function () {
                    return null;
                });
            })
            .then(function (res) {
                if (!res || !res.success || !res.data || !res.data.code) {
                    var text = res && res.data && res.data.message ? res.data.message : cfg.i18n.fehler;
                    zeigeMeldung(text, true);
                    return;
                }

                aktuellerCode = String(res.data.code);
                aktuellesQr = qrSvg(aktuellerCode);

                box.setAttribute('data-version', String(res.data.version));
                if (status) {
                    status.textContent = format(cfg.i18n.status, String(res.data.version), String(res.data.erstellt || ''));
                }
                button.textContent = cfg.i18n.zuruecksetzen;
                button.classList.remove('button-primary');

                codeEl.textContent = aktuellerCode;
                qrEl.innerHTML = aktuellesQr;
                ergebnis.hidden = false;
                zeigeMeldung(cfg.i18n.erzeugt, false);
            })
            .catch(function () {
                zeigeMeldung(cfg.i18n.fehler, true);
            })
            .then(function () {
                button.disabled = false;
            });
    });

    if (druckenBtn) {
        druckenBtn.addEventListener('click', function () {
            if (!aktuellerCode) {
                return;
            }

            var fenster = window.open('', '_blank', 'width=800,height=900');
            if (!fenster) {
                window.alert(cfg.i18n.popup);
                return;
            }

            var verein = box.getAttribute('data-verein') || '';
            var html = '<!doctype html><html lang="de"><head><meta charset="utf-8">'
                + '<title>' + esc(cfg.i18n.druckTitel) + '</title>'
                + '<style>'
                + 'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;color:#000;text-align:center;margin:2cm 1.5cm;}'
                + 'h1{font-size:20pt;margin:0 0 .4em;}h2{font-size:16pt;font-weight:normal;margin:0 0 1.2em;}'
                + '.qr{width:9cm;height:9cm;margin:0 auto 1em;}.qr svg{width:100%;height:100%;}'
                + '.code{font-family:"Courier New",monospace;font-size:30pt;font-weight:bold;letter-spacing:.08em;margin:.3em 0 1em;}'
                + 'p{font-size:12pt;line-height:1.5;max-width:15cm;margin:0 auto .8em;}'
                + '</style></head><body>'
                + '<h1>' + esc(cfg.i18n.druckTitel) + '</h1>'
                + '<h2>' + esc(verein) + '</h2>'
                + '<div class="qr">' + aktuellesQr + '</div>'
                + '<div class="code">' + esc(aktuellerCode) + '</div>'
                + '<p>' + esc(cfg.i18n.druckHinweis) + '</p>'
                + '<p><em>' + esc(cfg.i18n.druckVertraulich) + '</em></p>'
                + '</body></html>';

            fenster.document.open();
            fenster.document.write(html);
            fenster.document.close();
            fenster.focus();
            window.setTimeout(function () {
                fenster.print();
            }, 300);
        });
    }
})();
