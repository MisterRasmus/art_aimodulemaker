/**
 * Admin JavaScript för AI Module Maker
 * @author Ljustema Sverige AB
 */

class ArtAiModuleMakerAdmin {
    constructor() {
        this.initializeComponents();
        this.bindEvents();
        this.setupAjaxDefaults();
    }

    /**
     * Initialisera komponenter
     */
    initializeComponents() {
        // Initiera tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Initiera select2 för avancerade dropdown-menyer
        if ($.fn.select2) {
            $('.rl-select2').select2({
                width: '100%',
                dropdownAutoWidth: true
            });
        }

        // Initiera datatabeller
        if ($.fn.dataTable) {
            $('.rl-datatable').dataTable({
                pageLength: 25,
                responsive: true,
                dom: '<"row"<"col-sm-6"l><"col-sm-6"f>><"row"<"col-sm-12"tr>><"row"<"col-sm-5"i><"col-sm-7"p>>',
                language: {
                    search: '',
                    searchPlaceholder: 'Sök...'
                }
            });
        }
    }

    /**
     * Bind events
     */
    bindEvents() {
        // Hantera formulärsubmit
        $(document).on('submit', '.rl-ajax-form', (e) => this.handleFormSubmit(e));

        // Hantera bulk actions
        $(document).on('click', '.bulk-action-btn', (e) => this.handleBulkAction(e));

        // Hantera tab-navigation
        $(document).on('click', '.rl-tab-link', (e) => this.handleTabNavigation(e));

        // Hantera modul-statusändringar
        $(document).on('change', '.module-status-select', (e) => this.handleStatusChange(e));

        // Hantera refresh av data
        $(document).on('click', '.refresh-data', (e) => this.handleDataRefresh(e));

        // Hantera export
        $(document).on('click', '.export-btn', (e) => this.handleExport(e));

        // Hantera bekräftelsedialoger
        $(document).on('click', '[data-confirm]', (e) => this.handleConfirmation(e));
    }

    /**
     * Sätt upp standard Ajax-inställningar
     */
    setupAjaxDefaults() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-Token': prestashop.security.token
            }
        });

        // Global Ajax error handler
        $(document).ajaxError((event, jqXHR, settings, error) => {
            this.handleAjaxError(jqXHR, error);
        });
    }

    /**
     * Hantera formulärsubmit
     */
    async handleFormSubmit(event) {
        event.preventDefault();
        const $form = $(event.currentTarget);
        const $submitButton = $form.find('[type="submit"]');

        try {
            this.showLoading($submitButton);
            
            const formData = new FormData($form[0]);
            const response = await $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            });

            if (response.success) {
                this.showSuccess(response.message);
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else if (response.reload) {
                    window.location.reload();
                }
            } else {
                this.showError(response.error);
            }
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.hideLoading($submitButton);
        }
    }

    /**
     * Hantera bulk-actions
     */
    async handleBulkAction(event) {
        const $button = $(event.currentTarget);
        const action = $button.data('action');
        const selectedIds = this.getSelectedIds();

        if (!selectedIds.length) {
            this.showError('Inga objekt valda');
            return;
        }

        if (!await this.confirm('Är du säker på att du vill ' + action + ' valda objekt?')) {
            return;
        }

        try {
            this.showLoading($button);
            
            const response = await $.ajax({
                url: $button.data('url'),
                type: 'POST',
                data: {
                    action: action,
                    ids: selectedIds
                }
            });

            if (response.success) {
                this.showSuccess(response.message);
                if (response.reload) {
                    window.location.reload();
                }
            } else {
                this.showError(response.error);
            }
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.hideLoading($button);
        }
    }

    /**
     * Hantera tab-navigation
     */
    handleTabNavigation(event) {
        event.preventDefault();
        const $link = $(event.currentTarget);
        const target = $link.attr('href');

        // Uppdatera aktiv tab
        $('.rl-tab-link').removeClass('active');
        $link.addClass('active');

        // Visa rätt innehåll
        $('.rl-tab-content').hide();
        $(target).show();

        // Uppdatera URL om det behövs
        if (history.pushState) {
            history.pushState(null, null, target);
        }
    }

    /**
     * Hantera statusändringar
     */
    async handleStatusChange(event) {
        const $select = $(event.currentTarget);
        const moduleId = $select.data('module-id');
        const newStatus = $select.val();

        try {
            const response = await $.ajax({
                url: $select.data('url'),
                type: 'POST',
                data: {
                    action: 'updateStatus',
                    module_id: moduleId,
                    status: newStatus
                }
            });

            if (response.success) {
                this.showSuccess('Status uppdaterad');
            } else {
                this.showError(response.error);
                $select.val($select.data('original-value'));
            }
        } catch (error) {
            this.showError(error.message);
            $select.val($select.data('original-value'));
        }
    }

    /**
     * Hantera data-uppdatering
     */
    async handleDataRefresh(event) {
        const $button = $(event.currentTarget);
        const target = $button.data('target');

        try {
            this.showLoading($button);
            
            const response = await $.ajax({
                url: $button.data('url'),
                type: 'POST',
                data: { action: 'refresh' }
            });

            if (response.success) {
                $(target).html(response.html);
                this.initializeComponents();
                this.showSuccess('Data uppdaterad');
            } else {
                this.showError(response.error);
            }
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.hideLoading($button);
        }
    }

    /**
     * Hantera export
     */
    handleExport(event) {
        const $button = $(event.currentTarget);
        const url = new URL($button.data('url'), window.location.origin);
        const params = $button.data('params');

        // Lägg till parametrar till URL:en
        if (params) {
            Object.keys(params).forEach(key => {
                url.searchParams.append(key, params[key]);
            });
        }

        // Öppna exportlänk i nytt fönster
        window.open(url.toString(), '_blank');
    }

    /**
     * Hjälpmetoder
     */
    getSelectedIds() {
        return $('.rl-bulk-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
    }

    async confirm(message) {
        return new Promise(resolve => {
            if (confirm(message)) {
                resolve(true);
            } else {
                resolve(false);
            }
        });
    }

    showLoading($element) {
        $element.prop('disabled', true);
        $element.data('original-text', $element.html());
        $element.html('<i class="icon icon-spinner icon-spin"></i> Laddar...');
    }

    hideLoading($element) {
        $element.prop('disabled', false);
        $element.html($element.data('original-text'));
    }

    showSuccess(message) {
        $.growl.notice({ message: message });
    }

    showError(message) {
        $.growl.error({ message: message });
    }

    handleAjaxError(jqXHR, error) {
        let errorMessage = 'Ett fel uppstod';
        
        if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
            errorMessage = jqXHR.responseJSON.error;
        } else if (error) {
            errorMessage = error;
        }

        this.showError(errorMessage);
    }

    /**
     * Format helpers
     */
    formatDate(date) {
        return new Date(date).toLocaleDateString('sv-SE', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

// Initialisera när dokumentet är klart
$(document).ready(() => {
    window.ArtAiModuleMaker = new ArtAiModuleMakerAdmin();
});