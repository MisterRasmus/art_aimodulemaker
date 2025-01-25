/**
 * Module Builder för AI Module Maker
 * @author Ljustema Sverige AB
 */

class ModuleBuilder {
    constructor(config) {
        this.config = config;
        this.currentStep = 1;
        this.moduleData = {};
        this.generationProgress = 0;
        
        this.initializeComponents();
        this.bindEvents();
    }

    /**
     * Initialisera komponenter
     */
    initializeComponents() {
        // Form elements
        this.form = $('#module-builder-form');
        this.previewContainer = $('.module-preview');
        this.generateButton = $('#generate-module');
        this.progressModal = $('#generation-progress-modal');
        this.progressBar = $('.progress-bar');
        this.progressInfo = $('.progress-info');
        
        // Validering
        this.initializeValidation();
        
        // CodeMirror editors för kodförhandsgranskning
        this.initializeCodeEditors();
    }

    /**
     * Bind events
     */
    bindEvents() {
        // Form events
        this.form.on('change', 'input, select, textarea', () => this.updatePreview());
        this.form.on('submit', (e) => this.handleSubmit(e));
        
        // Generation events
        this.generateButton.on('click', () => this.startGeneration());
        
        // AI Chat integration
        $('.ask-ai-help').on('click', () => this.openAiChat());
        
        // GitHub integration
        $('#create-github-repo').on('change', () => this.toggleGitHubOptions());
        
        // Navigation
        $('.nav-step').on('click', (e) => this.navigateToStep(e));
    }

    /**
     * Initialisera formulärvalidering
     */
    initializeValidation() {
        this.form.validate({
            rules: {
                module_name: {
                    required: true,
                    pattern: /^[a-z][a-z0-9_]+$/,
                    minlength: 3,
                    remote: {
                        url: this.config.checkNameUrl,
                        type: 'post',
                        data: {
                            module_name: () => $('#module_name').val()
                        }
                    }
                },
                display_name: 'required',
                version: {
                    required: true,
                    pattern: /^\d+\.\d+\.\d+$/
                }
            },
            messages: {
                module_name: {
                    pattern: 'Only lowercase letters, numbers and underscore. Must start with a letter.',
                    remote: 'This module name already exists.'
                },
                version: {
                    pattern: 'Use semantic versioning (e.g., 1.0.0)'
                }
            }
        });
    }

    /**
     * Initiera kodeditors
     */
    initializeCodeEditors() {
        $('.code-preview').each((_, element) => {
            CodeMirror.fromTextArea(element, {
                mode: $(element).data('mode') || 'php',
                theme: 'default',
                lineNumbers: true,
                readOnly: true,
                viewportMargin: Infinity
            });
        });
    }

    /**
     * Uppdatera förhandsgranskning
     */
    async updatePreview() {
        this.moduleData = this.form.serializeObject();
        
        try {
            const response = await $.ajax({
                url: this.config.previewUrl,
                method: 'POST',
                data: {
                    action: 'generatePreview',
                    module_data: this.moduleData
                }
            });

            if (response.success) {
                this.updatePreviewContent(response.preview);
            }
        } catch (error) {
            console.error('Preview generation failed:', error);
        }
    }

    /**
     * Starta modulgenerering
     */
    async startGeneration() {
        if (!this.form.valid()) {
            return;
        }

        this.showProgress();
        this.generationProgress = 0;
        
        try {
            await this.generateModule();
        } catch (error) {
            this.handleError(error);
        } finally {
            this.hideProgress();
        }
    }

    /**
     * Generera modul
     */
    async generateModule() {
        const steps = [
            { name: 'Preparing module structure', weight: 10 },
            { name: 'Generating base files', weight: 20 },
            { name: 'Creating controllers', weight: 15 },
            { name: 'Generating templates', weight: 15 },
            { name: 'Setting up database', weight: 10 },
            { name: 'Configuring hooks', weight: 10 },
            { name: 'Setting up GitHub repository', weight: 10 },
            { name: 'Creating documentation', weight: 5 },
            { name: 'Finalizing module', weight: 5 }
        ];

        for (const step of steps) {
            this.updateProgress(step.name);
            await this.executeGenerationStep(step);
            this.generationProgress += step.weight;
            this.updateProgressBar();
        }

        await this.finalizeGeneration();
    }

    /**
     * Utför ett genereringssteg
     */
    async executeGenerationStep(step) {
        const response = await $.ajax({
            url: this.config.generateUrl,
            method: 'POST',
            data: {
                action: 'executeStep',
                step: step.name,
                module_data: this.moduleData
            }
        });

        if (!response.success) {
            throw new Error(`Failed at step "${step.name}": ${response.error}`);
        }

        return response;
    }

    /**
     * Slutför genereringen
     */
    async finalizeGeneration() {
        const response = await $.ajax({
            url: this.config.generateUrl,
            method: 'POST',
            data: {
                action: 'finalizeGeneration',
                module_data: this.moduleData
            }
        });

        if (response.success) {
            this.showSuccess(response.module);
        } else {
            throw new Error(response.error);
        }
    }

    /**
     * UI Updates
     */
    updateProgress(stepName) {
        this.progressInfo.html(`
            <i class="icon icon-cog icon-spin"></i> ${stepName}...
        `);
    }

    updateProgressBar() {
        this.progressBar.css('width', `${this.generationProgress}%`);
    }

    showProgress() {
        this.progressModal.modal('show');
    }

    hideProgress() {
        this.progressModal.modal('hide');
    }

    showSuccess(moduleData) {
        $('#module-complete-modal')
            .find('.generation-summary').html(this.generateSummaryHtml(moduleData))
            .end()
            .modal('show');
    }

    /**
     * Navigering mellan steg
     */
    navigateToStep(event) {
        event.preventDefault();
        const targetStep = $(event.currentTarget).data('step');
        
        if (this.validateCurrentStep()) {
            this.showStep(targetStep);
        }
    }

    showStep(step) {
        this.currentStep = step;
        $('.builder-step').hide();
        $(`#step-${step}`).show();
        this.updateNavigationState();
    }

    validateCurrentStep() {
        const currentFields = $(`#step-${this.currentStep}`).find('input, select, textarea');
        return this.form.validate().element(currentFields);
    }

    updateNavigationState() {
        $('.nav-step').removeClass('active completed');
        $(`.nav-step[data-step="${this.currentStep}"]`).addClass('active');
        
        for (let i = 1; i < this.currentStep; i++) {
            $(`.nav-step[data-step="${i}"]`).addClass('completed');
        }
    }

    /**
     * Hjälpmetoder
     */
    generateSummaryHtml(moduleData) {
        return `
            <div class="generation-summary">
                <h4>Module Generated Successfully!</h4>
                <p><strong>Name:</strong> ${moduleData.name}</p>
                <p><strong>Version:</strong> ${moduleData.version}</p>
                <p><strong>Files Generated:</strong> ${moduleData.fileCount}</p>
                ${moduleData.githubUrl ? `
                    <p><strong>GitHub Repository:</strong> 
                        <a href="${moduleData.githubUrl}" target="_blank">${moduleData.githubUrl}</a>
                    </p>
                ` : ''}
                <div class="alert alert-info">
                    <i class="icon icon-info-circle"></i>
                    Your module has been generated and is ready to use. You can download it now
                    or access it directly through your GitHub repository.
                </div>
            </div>
        `;
    }

    handleError(error) {
        console.error('Generation error:', error);
        $.growl.error({
            message: `Generation failed: ${error.message}`,
            duration: 5000
        });
    }
}

// Initialisera när dokumentet är klart
$(document).ready(() => {
    if ($('#module-builder-form').length) {
        window.moduleBuilder = new ModuleBuilder({
            checkNameUrl: prestashop.urls.base_url + 'checkModuleName',
            previewUrl: prestashop.urls.base_url + 'previewModule',
            generateUrl: prestashop.urls.base_url + 'generateModule',
            translations: {
                error: 'An error occurred',
                success: 'Module generated successfully'
            }
        });
    }
});