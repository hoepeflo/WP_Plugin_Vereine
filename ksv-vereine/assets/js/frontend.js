(function () {
    'use strict';

    if (typeof ksvVereine === 'undefined') {
        return;
    }

    const root = document.querySelector('[data-ksv-vereine]');
    if (!root) {
        return;
    }

    const listEl = root.querySelector('[data-ksv-list]');
    const emptyEl = root.querySelector('[data-ksv-empty]');
    const mapEl = root.querySelector('[data-ksv-map]');
    const filtersEl = root.querySelector('[data-ksv-filters]');
    const searchInput = root.querySelector('#ksv-search-input');
    const searchBtn = root.querySelector('.ksv-vereine__search-btn');
    const statusEl = root.querySelector('.ksv-vereine__status');

    let map = null;
    let markersLayer = null;
    let searchQuery = '';
    const selectedDisciplines = new Set();

    const suggestionForm = root.querySelector('[data-ksv-suggestion-form]');
    const suggestionVereinSelect = root.querySelector('[data-ksv-suggestion-verein]');
    const suggestionFields = root.querySelector('[data-ksv-suggestion-fields]');
    const suggestionDisciplinesEl = root.querySelector('[data-ksv-suggestion-disciplines]');
    const suggestionStatusEl = root.querySelector('[data-ksv-suggestion-status]');
    const suggestionSubmitBtn = root.querySelector('[data-ksv-suggestion-submit]');

    function initFilters() {
        ksvVereine.disciplines.forEach(function (d) {
            const id = 'ksv-filter-' + d.slug;
            const label = document.createElement('label');
            label.className = 'ksv-vereine__filter-chip';
            label.innerHTML =
                '<input type="checkbox" id="' +
                id +
                '" value="' +
                d.slug +
                '" /> ' +
                escapeHtml(d.name);
            const input = label.querySelector('input');
            input.addEventListener('change', function () {
                if (input.checked) {
                    selectedDisciplines.add(d.slug);
                } else {
                    selectedDisciplines.delete(d.slug);
                }
                loadVereine();
            });
            filtersEl.appendChild(label);
        });
    }

    function initMap() {
        map = L.map(mapEl).setView(
            [ksvVereine.map.lat, ksvVereine.map.lng],
            ksvVereine.map.zoom
        );
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);
        markersLayer = L.layerGroup().addTo(map);
    }

    function buildUrl() {
        const url = new URL(ksvVereine.restUrl);
        if (searchQuery) {
            url.searchParams.set('search', searchQuery);
        }
        selectedDisciplines.forEach(function (slug) {
            url.searchParams.append('disziplinen[]', slug);
        });
        return url.toString();
    }

    function loadVereine() {
        setStatus('');
        fetch(buildUrl(), {
            headers: {
                'X-WP-Nonce': ksvVereine.nonce,
            },
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                if (!data || !Array.isArray(data.vereine)) {
                    renderList([]);
                    updateMap([]);
                    return;
                }
                if (searchQuery && data.search_found === false) {
                    setStatus(ksvVereine.i18n.searchNotFound);
                }
                renderList(data.vereine);
                updateMap(data.vereine);
            })
            .catch(function () {
                setStatus(ksvVereine.i18n.noResults);
                renderList([]);
                updateMap([]);
            });
    }

    function setStatus(message) {
        if (!statusEl) {
            return;
        }
        if (!message) {
            statusEl.hidden = true;
            statusEl.textContent = '';
            return;
        }
        statusEl.hidden = false;
        statusEl.textContent = message;
    }

    function renderList(vereine) {
        listEl.innerHTML = '';
        if (!vereine.length) {
            emptyEl.hidden = false;
            return;
        }
        emptyEl.hidden = true;

        vereine.forEach(function (v) {
            listEl.appendChild(createCard(v));
        });
    }

    function createCard(v) {
        const hasWebsite = v.website && v.website.length > 0;
        const el = document.createElement(hasWebsite ? 'a' : 'article');
        el.className = 'ksv-card' + (hasWebsite ? ' ksv-card--linked' : '');
        el.id = 'ksv-verein-' + v.id;
        el.setAttribute('role', 'listitem');

        if (!hasWebsite) {
            el.setAttribute('tabindex', '-1');
        }

        if (hasWebsite) {
            el.href = v.website;
            el.target = '_blank';
            el.rel = 'noopener noreferrer';
            el.setAttribute(
                'aria-label',
                ksvVereine.i18n.openWebsite.replace('%s', v.name)
            );
        }

        const logo = document.createElement('img');
        logo.className = 'ksv-card__logo';
        logo.src = v.logo_url;
        logo.alt = '';
        logo.width = 100;
        logo.height = 100;
        logo.loading = 'lazy';

        const body = document.createElement('div');
        body.className = 'ksv-card__body';

        const title = document.createElement('h3');
        title.className = 'ksv-card__title';
        title.textContent = v.name;

        const address = document.createElement('p');
        address.className = 'ksv-card__address';
        address.textContent = v.address || '';

        const tags = document.createElement('div');
        tags.className = 'ksv-card__tags';
        (v.disziplinen || []).forEach(function (t) {
            const tag = document.createElement('span');
            tag.className = 'ksv-card__tag';
            tag.textContent = t.name;
            tags.appendChild(tag);
        });

        body.appendChild(title);
        if (v.address) {
            body.appendChild(address);
        }
        if (tags.childNodes.length) {
            body.appendChild(tags);
        }

        if (hasWebsite) {
            const url = document.createElement('p');
            url.className = 'ksv-card__url';
            url.textContent = v.website;
            body.appendChild(url);
        }

        el.appendChild(logo);
        el.appendChild(body);

        return el;
    }

    function updateMap(vereine) {
        markersLayer.clearLayers();
        const bounds = [];
        const withCoords = vereine.filter(function (v) {
            return v.lat !== null && v.lng !== null;
        });

        withCoords.forEach(function (v) {
            const marker = L.marker([v.lat, v.lng]);
            const popupContent =
                '<div class="ksv-popup">' +
                '<strong>' +
                escapeHtml(v.name) +
                '</strong>' +
                (v.address ? '<p>' + escapeHtml(v.address) + '</p>' : '') +
                '<p><a href="#ksv-verein-' +
                v.id +
                '" class="ksv-popup__link" data-ksv-scroll="' +
                v.id +
                '">' +
                escapeHtml(ksvVereine.i18n.toCard) +
                '</a></p>' +
                '</div>';
            marker.bindPopup(popupContent);
            marker.on('popupopen', function () {
                const link = document.querySelector(
                    '.ksv-popup__link[data-ksv-scroll="' + v.id + '"]'
                );
                if (link) {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        scrollToCard(v.id);
                        map.closePopup();
                    });
                }
            });
            markersLayer.addLayer(marker);
            bounds.push([v.lat, v.lng]);
        });

        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [24, 24] });
        } else if (bounds.length === 1) {
            map.setView(bounds[0], Math.max(map.getZoom(), 12));
        }
    }

    function scrollToCard(id) {
        const card = document.getElementById('ksv-verein-' + id);
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            card.focus({ preventScroll: true });
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    searchBtn.addEventListener('click', function () {
        searchQuery = searchInput.value.trim();
        loadVereine();
    });

    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchQuery = searchInput.value.trim();
            loadVereine();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && map) {
            map.closePopup();
        }
    });

    function updateSuggestionClubOptions(vereine) {
        if (!suggestionVereinSelect) {
            return;
        }

        const current = suggestionVereinSelect.value;
        const placeholder =
            ksvVereine.i18n.suggestionSelectPlaceholder || 'Bitte Verein wählen …';
        suggestionVereinSelect.innerHTML =
            '<option value="">' + escapeHtml(placeholder) + '</option>';

        vereine.forEach(function (v) {
            const opt = document.createElement('option');
            opt.value = String(v.id);
            opt.textContent = v.name;
            suggestionVereinSelect.appendChild(opt);
        });

        if (current && vereine.some(function (v) { return String(v.id) === current; })) {
            suggestionVereinSelect.value = current;
        }
    }

    function loadSuggestionClubOptions() {
        fetch(ksvVereine.restUrl, {
            headers: {
                'X-WP-Nonce': ksvVereine.nonce,
            },
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                if (data && Array.isArray(data.vereine)) {
                    updateSuggestionClubOptions(data.vereine);
                }
            })
            .catch(function () {
                /* Dropdown bleibt leer; Nutzer kann erneut laden */
            });
    }

    function initSuggestionDisciplines() {
        if (!suggestionDisciplinesEl) {
            return;
        }

        suggestionDisciplinesEl.innerHTML = '';
        ksvVereine.disciplines.forEach(function (d) {
            const id = 'ksv-suggestion-disc-' + d.slug;
            const label = document.createElement('label');
            label.className = 'ksv-suggestion-form__discipline';
            label.innerHTML =
                '<input type="checkbox" id="' +
                id +
                '" name="disziplinen[]" value="' +
                d.slug +
                '" /> ' +
                escapeHtml(d.name);
            suggestionDisciplinesEl.appendChild(label);
        });
    }

    function fillSuggestionForm(verein) {
        if (!suggestionForm || !verein) {
            return;
        }

        suggestionForm.elements.name.value = verein.name || '';
        suggestionForm.elements.street.value = verein.street || '';
        suggestionForm.elements.zip.value = verein.zip || '';
        suggestionForm.elements.city.value = verein.city || '';
        suggestionForm.elements.website.value = verein.website || '';
        suggestionForm.elements.comment.value = '';
        suggestionForm.elements.contact_email.value = '';

        const slugs = (verein.disziplinen || []).map(function (t) {
            return t.slug;
        });
        suggestionDisciplinesEl.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
            input.checked = slugs.indexOf(input.value) !== -1;
        });
    }

    function setSuggestionFieldsEnabled(enabled) {
        if (suggestionFields) {
            suggestionFields.disabled = !enabled;
        }
        if (suggestionSubmitBtn) {
            suggestionSubmitBtn.disabled = !enabled;
        }
    }

    function setSuggestionStatus(message, isError) {
        if (!suggestionStatusEl) {
            return;
        }
        if (!message) {
            suggestionStatusEl.hidden = true;
            suggestionStatusEl.textContent = '';
            suggestionStatusEl.classList.remove('ksv-suggestion-form__status--error');
            return;
        }
        suggestionStatusEl.hidden = false;
        suggestionStatusEl.textContent = message;
        suggestionStatusEl.classList.toggle('ksv-suggestion-form__status--error', !!isError);
    }

    function getSelectedSuggestionDisciplines() {
        const slugs = [];
        if (!suggestionDisciplinesEl) {
            return slugs;
        }
        suggestionDisciplinesEl.querySelectorAll('input[type="checkbox"]:checked').forEach(function (input) {
            slugs.push(input.value);
        });
        return slugs;
    }

    function initSuggestionForm() {
        if (!suggestionForm || !suggestionVereinSelect) {
            return;
        }

        initSuggestionDisciplines();
        setSuggestionFieldsEnabled(false);

        suggestionVereinSelect.addEventListener('change', function () {
            const id = parseInt(suggestionVereinSelect.value, 10);
            if (!id) {
                setSuggestionFieldsEnabled(false);
                setSuggestionStatus('');
                return;
            }

            fetch(ksvVereine.restUrl, {
                headers: {
                    'X-WP-Nonce': ksvVereine.nonce,
                },
            })
                .then(function (res) {
                    return res.json();
                })
                .then(function (data) {
                    const verein = (data.vereine || []).find(function (v) {
                        return v.id === id;
                    });
                    if (verein) {
                        fillSuggestionForm(verein);
                        setSuggestionFieldsEnabled(true);
                        setSuggestionStatus('');
                    }
                })
                .catch(function () {
                    setSuggestionStatus(ksvVereine.i18n.suggestionError, true);
                    setSuggestionFieldsEnabled(false);
                });
        });

        suggestionForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const id = parseInt(suggestionVereinSelect.value, 10);
            if (!id) {
                setSuggestionStatus(ksvVereine.i18n.suggestionSelectClub, true);
                return;
            }

            const url =
                ksvVereine.suggestionUrl.replace(/\/$/, '') +
                '/' +
                id +
                '/aenderungsvorschlag';

            const payload = {
                name: suggestionForm.elements.name.value.trim(),
                street: suggestionForm.elements.street.value.trim(),
                zip: suggestionForm.elements.zip.value.trim(),
                city: suggestionForm.elements.city.value.trim(),
                website: suggestionForm.elements.website.value.trim(),
                disziplinen: getSelectedSuggestionDisciplines(),
                comment: suggestionForm.elements.comment.value.trim(),
                contact_email: suggestionForm.elements.contact_email.value.trim(),
                website_honeypot: suggestionForm.elements.website_honeypot
                    ? suggestionForm.elements.website_honeypot.value
                    : '',
            };

            setSuggestionStatus(ksvVereine.i18n.suggestionSubmitting, false);
            if (suggestionSubmitBtn) {
                suggestionSubmitBtn.disabled = true;
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': ksvVereine.nonce,
                },
                body: JSON.stringify(payload),
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (result.ok && result.data && result.data.success) {
                        setSuggestionStatus(
                            result.data.message || ksvVereine.i18n.suggestionSuccess,
                            false
                        );
                        suggestionForm.reset();
                        setSuggestionFieldsEnabled(false);
                        suggestionVereinSelect.value = '';
                        return;
                    }

                    const message =
                        (result.data && result.data.message) ||
                        ksvVereine.i18n.suggestionError;
                    setSuggestionStatus(message, true);
                    setSuggestionFieldsEnabled(true);
                })
                .catch(function () {
                    setSuggestionStatus(ksvVereine.i18n.suggestionError, true);
                    setSuggestionFieldsEnabled(true);
                })
                .finally(function () {
                    if (suggestionSubmitBtn && suggestionVereinSelect.value) {
                        suggestionSubmitBtn.disabled = false;
                    }
                });
        });
    }

    initFilters();
    initMap();
    initSuggestionForm();
    loadSuggestionClubOptions();
    loadVereine();
})();
