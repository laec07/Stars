/**
 * Fase 8 — Búsqueda global con Ctrl+K / Cmd+K
 *
 * Carga en cada página vía layouts/app.blade.php.
 * Patrón: presiona Ctrl+K → modal con search input → resultados en vivo.
 *
 * Características:
 *   - Debounce 200ms para evitar requests excesivas
 *   - Navegación con teclado: ↑ ↓ Enter Esc
 *   - Recientes en localStorage (últimas 5 visitas)
 *   - Cierre al hacer click fuera o botón ×
 *   - Animación de entrada/salida
 */
(function ($) {
    'use strict';

    if (window.GlobalSearch && window.GlobalSearch._initialized) return;

    var $modal = null;
    var $input = null;
    var $resultsContainer = null;
    var $hintFooter = null;
    var debounceTimer = null;
    var currentResults = [];   // array plano de resultados navegables
    var activeIndex = -1;
    var lastQuery = '';
    var RECENT_KEY = 'globalSearch.recent';
    var RECENT_MAX = 5;

    var GlobalSearch = {
        _initialized: false,

        Init: function () {
            if (this._initialized) return;
            this._initialized = true;
            this.BuildModal();
            this.BindGlobalKeys();
            this.BindTopbarButton();
        },

        BuildModal: function () {
            // Si ya existe, salir
            if (document.getElementById('globalSearchModal')) {
                this.CacheRefs();
                return;
            }

            var html =
                '<div class="gsearch-modal" id="globalSearchModal" aria-hidden="true">' +
                    '<div class="gsearch-backdrop" data-gsearch-close></div>' +
                    '<div class="gsearch-panel" role="dialog" aria-label="Búsqueda global">' +
                        '<div class="gsearch-header">' +
                            '<i class="fas fa-search gsearch-icon"></i>' +
                            '<input type="text" class="gsearch-input" id="gsearchInput"' +
                                ' placeholder="Buscar paciente, ficha o acción…"' +
                                ' autocomplete="off" spellcheck="false">' +
                            '<button type="button" class="gsearch-close-btn" data-gsearch-close aria-label="Cerrar">' +
                                '<i class="fas fa-times"></i>' +
                            '</button>' +
                        '</div>' +
                        '<div class="gsearch-results" id="gsearchResults">' +
                            '<div class="gsearch-empty">' +
                                '<i class="fas fa-keyboard"></i>' +
                                '<div>Empieza a escribir para buscar pacientes, fichas o acciones…</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="gsearch-footer">' +
                            '<span><kbd>↑</kbd><kbd>↓</kbd> navegar</span>' +
                            '<span><kbd>Enter</kbd> abrir</span>' +
                            '<span><kbd>Esc</kbd> cerrar</span>' +
                            '<span class="ml-auto" id="gsearchStatus"></span>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            $('body').append(html);
            this.CacheRefs();
            this.BindInternalEvents();
            this.RenderInitial();
        },

        CacheRefs: function () {
            $modal = $('#globalSearchModal');
            $input = $('#gsearchInput');
            $resultsContainer = $('#gsearchResults');
            $hintFooter = $('#gsearchStatus');
        },

        BindGlobalKeys: function () {
            var self = this;
            $(document).on('keydown', function (e) {
                // Ctrl+K / Cmd+K → abrir
                if ((e.ctrlKey || e.metaKey) && e.key && e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    self.Open();
                    return;
                }
                // Esc → cerrar si está abierto
                if (e.key === 'Escape' && $modal && $modal.hasClass('open')) {
                    self.Close();
                    return;
                }
                if ($modal && $modal.hasClass('open')) {
                    if (e.key === 'ArrowDown') { e.preventDefault(); self.MoveActive(+1); }
                    else if (e.key === 'ArrowUp')   { e.preventDefault(); self.MoveActive(-1); }
                    else if (e.key === 'Enter')     { e.preventDefault(); self.OpenActive(); }
                }
            });
        },

        BindTopbarButton: function () {
            // Si existe el botón en topbar, conectarlo
            $(document).on('click', '[data-global-search-trigger]', function (e) {
                e.preventDefault();
                GlobalSearch.Open();
            });
        },

        BindInternalEvents: function () {
            var self = this;

            $modal.on('click', '[data-gsearch-close]', function () {
                self.Close();
            });

            $input.on('input', function () {
                var q = $(this).val();
                if (debounceTimer) clearTimeout(debounceTimer);
                if (!q || q.length < 2) {
                    self.RenderInitial();
                    return;
                }
                $hintFooter.text('Buscando…');
                debounceTimer = setTimeout(function () { self.DoSearch(q); }, 200);
            });

            // Click en un resultado
            $modal.on('click', '.gsearch-item', function () {
                var idx = parseInt($(this).data('idx'), 10);
                if (!isNaN(idx)) self.OpenIndex(idx);
            });

            // Hover marca como activo
            $modal.on('mouseenter', '.gsearch-item', function () {
                var idx = parseInt($(this).data('idx'), 10);
                if (!isNaN(idx)) self.SetActive(idx, false);
            });
        },

        Open: function () {
            if (!$modal) this.BuildModal();
            $modal.addClass('open').attr('aria-hidden', 'false');
            $('body').addClass('gsearch-no-scroll');
            setTimeout(function () { $input.trigger('focus'); }, 50);
            // Si hay query previa, restaurarla
            if (lastQuery) {
                $input.val(lastQuery).select();
                this.DoSearch(lastQuery);
            } else {
                this.RenderInitial();
            }
        },

        Close: function () {
            if (!$modal) return;
            $modal.removeClass('open').attr('aria-hidden', 'true');
            $('body').removeClass('gsearch-no-scroll');
        },

        DoSearch: function (q) {
            lastQuery = q;
            var self = this;
            $.ajax({
                url: (window.JsManager ? JsManager.BaseUrl() : '') + '/global-search',
                data: { q: q },
                dataType: 'json',
                success: function (json) {
                    if (!json || json.status != '1' || !json.data) {
                        self.RenderError('Error al buscar');
                        return;
                    }
                    self.RenderResults(json.data);
                },
                error: function () { self.RenderError('Error de conexión'); }
            });
        },

        RenderInitial: function () {
            var recents = this.GetRecents();
            if (!recents.length) {
                $resultsContainer.html(
                    '<div class="gsearch-empty">' +
                        '<i class="fas fa-keyboard"></i>' +
                        '<div>Empieza a escribir para buscar pacientes, fichas o acciones…</div>' +
                    '</div>'
                );
                currentResults = [];
                activeIndex = -1;
                $hintFooter.text('');
                return;
            }
            // Mostrar recientes
            var html = '<div class="gsearch-group"><div class="gsearch-group-title"><i class="fas fa-clock-rotate-left"></i> Recientes</div>';
            currentResults = [];
            recents.forEach(function (r) {
                currentResults.push(r);
                html += GlobalSearch.RenderItem(r, currentResults.length - 1);
            });
            html += '</div>';
            $resultsContainer.html(html);
            activeIndex = -1;
            $hintFooter.text(currentResults.length + ' recientes');
        },

        RenderError: function (msg) {
            $resultsContainer.html(
                '<div class="gsearch-empty gsearch-error">' +
                    '<i class="fas fa-exclamation-triangle"></i>' +
                    '<div>' + msg + '</div>' +
                '</div>'
            );
            $hintFooter.text('');
            currentResults = [];
            activeIndex = -1;
        },

        RenderResults: function (data) {
            var html = '';
            currentResults = [];

            // Pacientes
            if (data.patients && data.patients.length) {
                html += '<div class="gsearch-group"><div class="gsearch-group-title"><i class="fas fa-users"></i> Pacientes <span class="gsearch-group-count">' + data.patients.length + '</span></div>';
                data.patients.forEach(function (p) {
                    var item = {
                        type:  'patient',
                        icon:  'fa-user',
                        label: p.name,
                        sub:   GlobalSearch.PatientSubtitle(p),
                        url:   p.url
                    };
                    currentResults.push(item);
                    html += GlobalSearch.RenderItem(item, currentResults.length - 1);
                });
                html += '</div>';
            }

            // Fichas
            if (data.fichas && data.fichas.length) {
                html += '<div class="gsearch-group"><div class="gsearch-group-title"><i class="fas fa-folder-open"></i> Fichas clínicas <span class="gsearch-group-count">' + data.fichas.length + '</span></div>';
                data.fichas.forEach(function (f) {
                    var label = f.diagnostico ? f.diagnostico : 'Ficha #' + f.id;
                    var sub   = (f.patient_name ? f.patient_name : '—') +
                                (f.fecha ? ' · ' + GlobalSearch.FormatDate(f.fecha) : '') +
                                (f.motivo_consulta ? ' · ' + f.motivo_consulta : '');
                    var item = {
                        type:  'ficha',
                        icon:  'fa-folder-open',
                        label: label,
                        sub:   sub,
                        url:   f.url
                    };
                    currentResults.push(item);
                    html += GlobalSearch.RenderItem(item, currentResults.length - 1);
                });
                html += '</div>';
            }

            // Acciones
            if (data.actions && data.actions.length) {
                html += '<div class="gsearch-group"><div class="gsearch-group-title"><i class="fas fa-bolt"></i> Acciones</div>';
                data.actions.forEach(function (a) {
                    var item = {
                        type:  'action',
                        icon:  a.icon,
                        label: a.label,
                        sub:   a.sub,
                        url:   a.url
                    };
                    currentResults.push(item);
                    html += GlobalSearch.RenderItem(item, currentResults.length - 1);
                });
                html += '</div>';
            }

            if (!currentResults.length) {
                html =
                    '<div class="gsearch-empty">' +
                        '<i class="fas fa-search"></i>' +
                        '<div>No se encontraron resultados para "<strong>' + GlobalSearch.Escape(data.query) + '</strong>"</div>' +
                    '</div>';
                $hintFooter.text('');
            } else {
                $hintFooter.text(currentResults.length + ' resultado(s)');
            }

            $resultsContainer.html(html);
            // Activar el primer item automáticamente para que Enter funcione
            if (currentResults.length) this.SetActive(0, true);
        },

        RenderItem: function (item, idx) {
            var typeColor = {
                'patient': 'gsearch-type-patient',
                'ficha':   'gsearch-type-ficha',
                'action':  'gsearch-type-action'
            }[item.type] || '';
            return (
                '<div class="gsearch-item ' + typeColor + '" data-idx="' + idx + '">' +
                    '<div class="gsearch-item-icon"><i class="fas ' + item.icon + '"></i></div>' +
                    '<div class="gsearch-item-body">' +
                        '<div class="gsearch-item-label">' + GlobalSearch.Escape(item.label) + '</div>' +
                        (item.sub ? '<div class="gsearch-item-sub">' + GlobalSearch.Escape(item.sub) + '</div>' : '') +
                    '</div>' +
                    '<i class="fas fa-arrow-right gsearch-item-arrow"></i>' +
                '</div>'
            );
        },

        PatientSubtitle: function (p) {
            var parts = [];
            if (p.phone)      parts.push(p.phone);
            if (p.tax_number) parts.push('DPI ' + p.tax_number);
            if (p.email)      parts.push(p.email);
            return parts.join(' · ');
        },

        SetActive: function (idx, scrollIntoView) {
            if (idx < 0 || idx >= currentResults.length) return;
            activeIndex = idx;
            $modal.find('.gsearch-item').removeClass('active');
            var $el = $modal.find('.gsearch-item[data-idx="' + idx + '"]');
            $el.addClass('active');
            if (scrollIntoView && $el.length) {
                var el = $el[0];
                if (el.scrollIntoView) el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        },

        MoveActive: function (delta) {
            if (!currentResults.length) return;
            var next = activeIndex + delta;
            if (next < 0) next = currentResults.length - 1;
            if (next >= currentResults.length) next = 0;
            this.SetActive(next, true);
        },

        OpenActive: function () {
            if (activeIndex >= 0) this.OpenIndex(activeIndex);
        },

        OpenIndex: function (idx) {
            var item = currentResults[idx];
            if (!item || !item.url) return;
            this.AddRecent(item);
            this.Close();
            window.location.href = item.url;
        },

        // -------- Recientes en localStorage --------
        GetRecents: function () {
            try {
                var raw = window.localStorage.getItem(RECENT_KEY);
                return raw ? JSON.parse(raw) : [];
            } catch (e) { return []; }
        },
        AddRecent: function (item) {
            try {
                var list = this.GetRecents();
                // Quitar duplicados por url
                list = list.filter(function (r) { return r.url !== item.url; });
                list.unshift({
                    type:  item.type,
                    icon:  item.icon,
                    label: item.label,
                    sub:   item.sub,
                    url:   item.url
                });
                if (list.length > RECENT_MAX) list.length = RECENT_MAX;
                window.localStorage.setItem(RECENT_KEY, JSON.stringify(list));
            } catch (e) {}
        },

        // -------- Util --------
        Escape: function (str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        },
        FormatDate: function (d) {
            if (!d) return '';
            try {
                var dt = new Date(d);
                if (isNaN(dt.getTime())) return d;
                return dt.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
            } catch (e) { return d; }
        }
    };

    window.GlobalSearch = GlobalSearch;

    $(document).ready(function () {
        GlobalSearch.Init();
    });

})(jQuery);
