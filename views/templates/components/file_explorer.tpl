<div class="file-explorer-component">
    <div class="panel">
        <div class="panel-heading">
            <div class="row">
                <div class="col-md-6">
                    <i class="icon icon-folder-open"></i> {l s='Module Files' mod='rl_aimodulemaker'}
                </div>
                <div class="col-md-6 text-right">
                    <div class="btn-group">
                        <button class="btn btn-default btn-xs refresh-files">
                            <i class="icon icon-refresh"></i> {l s='Refresh' mod='rl_aimodulemaker'}
                        </button>
                        <button class="btn btn-default btn-xs create-file">
                            <i class="icon icon-plus"></i> {l s='New File' mod='rl_aimodulemaker'}
                        </button>
                        <button class="btn btn-default btn-xs create-folder">
                            <i class="icon icon-folder"></i> {l s='New Folder' mod='rl_aimodulemaker'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="panel-body">
            <div class="file-tree" style="height: 400px; overflow-y: auto;">
                {* Filträdet laddas dynamiskt via JavaScript *}
            </div>
        </div>
    </div>

    {* Modal för att skapa ny fil/mapp *}
    <div class="modal fade" id="create-item-modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">{l s='Create New Item' mod='rl_aimodulemaker'}</h4>
                </div>
                <div class="modal-body">
                    <form id="create-item-form">
                        <div class="form-group">
                            <label>{l s='Name' mod='rl_aimodulemaker'}</label>
                            <input type="text" class="form-control" name="item_name" required>
                        </div>
                        <div class="form-group file-content-group" style="display: none;">
                            <label>{l s='Initial Content' mod='rl_aimodulemaker'}</label>
                            <textarea class="form-control" name="item_content" rows="10"></textarea>
                        </div>
                        <input type="hidden" name="item_type">
                        <input type="hidden" name="current_path">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Cancel' mod='rl_aimodulemaker'}</button>
                    <button type="button" class="btn btn-primary" id="create-item-submit">{l s='Create' mod='rl_aimodulemaker'}</button>
                </div>
            </div>
        </div>
    </div>

    {* Modal för att visa/redigera fil *}
    <div class="modal fade" id="edit-file-modal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title file-name"></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <div id="code-editor" style="height: 400px;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="pull-left">
                        <button type="button" class="btn btn-default ask-ai">
                            <i class="icon icon-comments"></i> {l s='Ask AI' mod='rl_aimodulemaker'}
                        </button>
                    </div>
                    <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Cancel' mod='rl_aimodulemaker'}</button>
                    <button type="button" class="btn btn-primary save-file">{l s='Save' mod='rl_aimodulemaker'}</button>
                </div>
            </div>
        </div>
    </div>
</div>

{* Styles för filutforskaren *}
<style>
    .file-explorer-component .file-tree {
        font-family: monospace;
    }

    .file-explorer-component .file-item {
        padding: 5px;
        cursor: pointer;
        border-radius: 3px;
        margin: 2px 0;
    }

    .file-explorer-component .file-item:hover {
        background: #f8f9fa;
    }

    .file-explorer-component .file-item.selected {
        background: #e3f2fd;
    }

    .file-explorer-component .folder-content {
        margin-left: 20px;
    }

    .file-explorer-component .icon {
        margin-right: 5px;
    }
</style>

{* JavaScript för filutforskaren *}
<script type="text/javascript">
    class FileExplorerComponent {
        constructor(element, config) {
            this.element = element;
            this.config = config;
            this.currentPath = '';
            this.editor = null;
            
            this.initializeElements();
            this.bindEvents();
            this.loadFileTree();
            this.initializeCodeEditor();
        }

        initializeElements() {
            this.fileTree = this.element.find('.file-tree');
            this.createFileBtn = this.element.find('.create-file');
            this.createFolderBtn = this.element.find('.create-folder');
            this.refreshBtn = this.element.find('.refresh-files');
        }

        bindEvents() {
            this.createFileBtn.on('click', () => this.showCreateModal('file'));
            this.createFolderBtn.on('click', () => this.showCreateModal('folder'));
            this.refreshBtn.on('click', () => this.loadFileTree());
            
            $('#create-item-submit').on('click', () => this.createItem());
            $('.save-file').on('click', () => this.saveFile());
            $('.ask-ai').on('click', () => this.askAi());

            // Delegera händelser för dynamiskt skapade element
            this.fileTree.on('click', '.file-item', (e) => {
                const item = $(e.currentTarget);
                if (item.data('type') === 'file') {
                    this.openFile(item.data('path'));
                } else {
                    this.toggleFolder(item);
                }
            });
        }

        async loadFileTree() {
            try {
                const response = await $.ajax({
                    url: this.config.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'getFileTree',
                        module_id: this.config.moduleId
                    }
                });

                if (response.success) {
                    this.fileTree.html(this.buildFileTreeHtml(response.files));
                } else {
                    this.showError(response.error);
                }
            } catch (error) {
                this.showError(error.message);
            }
        }

        buildFileTreeHtml(files, level = 0) {
            let html = '';
            for (const file of files) {
                const indent = '&nbsp;'.repeat(level * 4);
                const icon = file.type === 'file' ? 'file-text' : 'folder';
                
                html += `
                    <div class="file-item" data-type="${file.type}" data-path="${file.path}">
                        ${indent}<i class="icon icon-${icon}"></i>${file.name}
                    </div>
                `;

                if (file.type === 'folder' && file.children) {
                    html += `<div class="folder-content" style="display: none;">`;
                    html += this.buildFileTreeHtml(file.children, level + 1);
                    html += `</div>`;
                }
            }
            return html;
        }

        initializeCodeEditor() {
            // Initialisera CodeMirror eller annan kodeditor
            this.editor = CodeMirror(document.getElementById('code-editor'), {
                mode: 'php',
                theme: 'default',
                lineNumbers: true,
                autoCloseBrackets: true,
                matchBrackets: true,
                indentUnit: 4,
                tabSize: 4,
                lineWrapping: true,
                extraKeys: {
                    "Ctrl-Space": "autocomplete"
                }
            });
        }

        async openFile(path) {
            try {
                const response = await $.ajax({
                    url: this.config.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'getFileContent',
                        path: path,
                        module_id: this.config.moduleId
                    }
                });

                if (response.success) {
                    $('#edit-file-modal .file-name').text(path);
                    this.editor.setValue(response.content);
                    this.editor.setOption('mode', this.getEditorMode(path));
                    $('#edit-file-modal').modal('show');
                    this.currentPath = path;
                } else {
                    this.showError(response.error);
                }
            } catch (error) {
                this.showError(error.message);
            }
        }

        getEditorMode(path) {
            const ext = path.split('.').pop().toLowerCase();
            const modes = {
                'php': 'php',
                'js': 'javascript',
                'css': 'css',
                'tpl': 'smarty',
                'html': 'html',
                'xml': 'xml',
                'json': 'javascript',
                'md': 'markdown'
            };
            return modes[ext] || 'text';
        }

        async saveFile() {
            try {
                const response = await $.ajax({
                    url: this.config.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'saveFile',
                        path: this.currentPath,
                        content: this.editor.getValue(),
                        module_id: this.config.moduleId
                    }
                });

                if (response.success) {
                    showSuccessMessage(this.config.translations.fileSaved);
                    $('#edit-file-modal').modal('hide');
                } else {
                    this.showError(response.error);
                }
            } catch (error) {
                this.showError(error.message);
            }
        }

        async createItem() {
            const form = $('#create-item-form');
            const data = {
                action: 'createItem',
                type: form.find('[name="item_type"]').val(),
                name: form.find('[name="item_name"]').val(),
                content: form.find('[name="item_content"]').val(),
                path: form.find('[name="current_path"]').val(),
                module_id: this.config.moduleId
            };

            try {
                const response = await $.ajax({
                    url: this.config.ajaxUrl,
                    method: 'POST',
                    data: data
                });

                if (response.success) {
                    $('#create-item-modal').modal('hide');
                    this.loadFileTree();
                    showSuccessMessage(this.config.translations.itemCreated);
                } else {
                    this.showError(response.error);
                }
            } catch (error) {
                this.showError(error.message);
            }
        }

        toggleFolder(folderItem) {
            const content = folderItem.next('.folder-content');
            content.slideToggle();
            const icon = folderItem.find('.icon');
            icon.toggleClass('icon-folder icon-folder-open');
        }

        showCreateModal(type) {
            const form = $('#create-item-form');
            form.find('[name="item_type"]').val(type);
            form.find('[name="current_path"]').val(this.currentPath);
            form.find('.file-content-group').toggle(type === 'file');
            $('#create-item-modal .modal-title').text(
                type === 'file' ? this.config.translations.newFile : this.config.translations.newFolder
            );
            $('#create-item-modal').modal('show');
        }

        askAi() {
            const selectedCode = this.editor.getSelection() || this.editor.getValue();
            // Trigga AI chat-komponenten med den valda koden
            $(document).trigger('openAiChat', {
                context: {
                    code: selectedCode,
                    file: this.currentPath
                }
            });
        }

        showError(message) {
            showErrorMessage(message);
        }
    }

    // Initialisera komponenten
    $(document).ready(function() {
        $('.file-explorer-component').each(function() {
            new FileExplorerComponent($(this), {
                ajaxUrl: '{$link->getAdminLink('AdminRlAiModuleMaker')|addslashes}',
                moduleId: '{$module_id|intval}',
                translations: {
                    fileSaved: '{l s='File saved successfully' mod='rl_aimodulemaker' js=1}',
                    itemCreated: '{l s='Item created successfully' mod='rl_aimodulemaker' js=1}',
                    newFile: '{l s='Create New File' mod='rl_aimodulemaker' js=1}',
                    newFolder: '{l s='Create New Folder' mod='rl_aimodulemaker' js=1}'
                }
            });
        });
    });
</script>