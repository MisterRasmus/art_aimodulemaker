/**
 * File Explorer för AI Module Maker
 * @author Ljustema Sverige AB
 */

class FileExplorer {
    constructor(config) {
        this.config = config;
        this.currentPath = '';
        this.editors = new Map();
        this.fileTree = null;
        this.selectedNode = null;
        
        this.initializeComponents();
        this.bindEvents();
        this.loadFileTree();
    }

    /**
     * Initialisera komponenter
     */
    initializeComponents() {
        // Initialize file tree
        this.fileTree = new JSTree('#file-tree', {
            core: {
                themes: {
                    name: 'default',
                    dots: false,
                    icons: true
                },
                check_callback: true,
                data: []
            },
            plugins: ['types', 'contextmenu', 'dnd', 'search'],
            types: {
                default: {
                    icon: 'icon-file'
                },
                folder: {
                    icon: 'icon-folder'
                },
                php: {
                    icon: 'icon-php'
                },
                js: {
                    icon: 'icon-js'
                },
                css: {
                    icon: 'icon-css'
                },
                tpl: {
                    icon: 'icon-smarty'
                }
            },
            contextmenu: {
                items: (node) => this.getContextMenu(node)
            }
        });

        // Initialize code editor
        this.initializeCodeEditor();

        // Initialize search
        this.initializeSearch();
    }

    /**
     * Bind events
     */
    bindEvents() {
        // File tree events
        this.fileTree.on('select_node.jstree', (e, data) => this.handleNodeSelect(data.node));
        this.fileTree.on('rename_node.jstree', (e, data) => this.handleNodeRename(data.node));
        this.fileTree.on('move_node.jstree', (e, data) => this.handleNodeMove(data.node));
        this.fileTree.on('delete_node.jstree', (e, data) => this.handleNodeDelete(data.node));

        // Button events
        $('.refresh-files').on('click', () => this.refreshFiles());
        $('.create-file').on('click', () => this.showCreateFileModal());
        $('.create-folder').on('click', () => this.showCreateFolderModal());
        $('.save-file').on('click', () => this.saveCurrentFile());
        
        // Search events
        $('.file-search').on('keyup', _.debounce(() => this.searchFiles(), 300));

        // AI integration
        $('.ask-ai').on('click', () => this.askAiAboutFile());
    }

    /**
     * Ladda filträd
     */
    async loadFileTree() {
        try {
            const response = await $.ajax({
                url: this.config.apiEndpoint,
                method: 'POST',
                data: {
                    action: 'getFileTree',
                    module_id: this.config.moduleId
                }
            });

            if (response.success) {
                this.fileTree.settings.core.data = this.transformFileData(response.files);
                this.fileTree.refresh();
            } else {
                throw new Error(response.error);
            }
        } catch (error) {
            this.handleError('Failed to load file tree:', error);
        }
    }

    /**
     * Transformera fildata till JSTree-format
     */
    transformFileData(files, parentId = '#') {
        return files.map((file, index) => ({
            id: file.path || `node_${index}`,
            parent: parentId,
            text: file.name,
            type: file.type === 'directory' ? 'folder' : this.getFileType(file.name),
            data: {
                path: file.path,
                type: file.type
            },
            state: {
                opened: parentId === '#'
            },
            children: file.children ? this.transformFileData(file.children, file.path) : undefined
        }));
    }

    /**
     * Initiera kodredigerare
     */
    initializeCodeEditor() {
        this.defaultEditor = CodeMirror.fromTextArea(document.getElementById('code-editor'), {
            lineNumbers: true,
            mode: 'php',
            theme: 'default',
            autoCloseBrackets: true,
            matchBrackets: true,
            indentUnit: 4,
            tabSize: 4,
            lineWrapping: true,
            foldGutter: true,
            gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
            extraKeys: {
                'Ctrl-Space': 'autocomplete',
                'Ctrl-S': () => this.saveCurrentFile()
            }
        });
    }

    /**
     * Hantera filval
     */
    async handleNodeSelect(node) {
        if (node.data.type === 'file') {
            await this.openFile(node.data.path);
        }
        this.selectedNode = node;
    }

    /**
     * Öppna fil
     */
    async openFile(path) {
        try {
            const response = await $.ajax({
                url: this.config.apiEndpoint,
                method: 'POST',
                data: {
                    action: 'getFileContent',
                    path: path,
                    module_id: this.config.moduleId
                }
            });

            if (response.success) {
                this.currentPath = path;
                this.setEditorContent(response.content, this.getFileType(path));
                this.updateFileInfo(path);
            } else {
                throw new Error(response.error);
            }
        } catch (error) {
            this.handleError('Failed to open file:', error);
        }
    }

    /**
     * Spara nuvarande fil
     */
    async saveCurrentFile() {
        if (!this.currentPath) return;

        try {
            const response = await $.ajax({
                url: this.config.apiEndpoint,
                method: 'POST',
                data: {
                    action: 'saveFile',
                    path: this.currentPath,
                    content: this.defaultEditor.getValue(),
                    module_id: this.config.moduleId
                }
            });

            if (response.success) {
                $.growl.notice({ message: 'File saved successfully' });
            } else {
                throw new Error(response.error);
            }
        } catch (error) {
            this.handleError('Failed to save file:', error);
        }
    }

    /**
     * Skapa fil/mapp
     */
    async createItem(type, name, content = '') {
        const parentPath = this.selectedNode ? 
            this.selectedNode.data.path : 
            '';

        try {
            const response = await $.ajax({
                url: this.config.apiEndpoint,
                method: 'POST',
                data: {
                    action: 'createItem',
                    type: type,
                    name: name,
                    content: content,
                    parent_path: parentPath,
                    module_id: this.config.moduleId
                }
            });

            if (response.success) {
                await this.refreshFiles();
                $.growl.notice({ message: `${type} created successfully` });
            } else {
                throw new Error(response.error);
            }
        } catch (error) {
            this.handleError(`Failed to create ${type}:`, error);
        }
    }

    /**
     * Kontextmeny för noder
     */
    getContextMenu(node) {
        const items = {};

        if (node.data.type === 'directory') {
            items.create = {
                label: 'Create',
                submenu: {
                    file: {
                        label: 'File',
                        action: () => this.showCreateFileModal()
                    },
                    folder: {
                        label: 'Folder',
                        action: () => this.showCreateFolderModal()
                    }
                }
            };
        }

        items.rename = {
            label: 'Rename',
            action: () => this.fileTree.edit(node)
        };

        items.delete = {
            label: 'Delete',
            action: () => this.confirmDelete(node)
        };

        if (node.data.type === 'file') {
            items.askAi = {
                label: 'Ask AI',
                action: () => this.askAiAboutFile()
            };
        }

        return items;
    }

    /**
     * UI Helpers
     */
    showCreateFileModal() {
        $('#create-item-modal')
            .find('.modal-title').text('Create New File').end()
            .find('[name="item_type"]').val('file').end()
            .modal('show');
    }

    showCreateFolderModal() {
        $('#create-item-modal')
            .find('.modal-title').text('Create New Folder').end()
            .find('[name="item_type"]').val('directory').end()
            .modal('show');
    }

    updateFileInfo(path) {
        $('.file-info').html(`
            <div class="file-path">${path}</div>
            <div class="file-type">Type: ${this.getFileType(path)}</div>
        `);
    }

    /**
     * Integration med AI
     */
    askAiAboutFile() {
        const content = this.defaultEditor.getValue();
        const context = {
            file: this.currentPath,
            content: content,
            type: this.getFileType(this.currentPath)
        };

        $(document).trigger('openAiChat', { context });
    }

    /**
     * Utility functions
     */
    getFileType(filename) {
        const extension = filename.split('.').pop().toLowerCase();
        const typeMap = {
            'php': 'php',
            'js': 'javascript',
            'css': 'css',
            'tpl': 'smarty',
            'html': 'html',
            'xml': 'xml',
            'json': 'javascript',
            'md': 'markdown'
        };
        return typeMap[extension] || 'text';
    }

    setEditorContent(content, mode) {
        this.defaultEditor.setValue(content);
        this.defaultEditor.setOption('mode', mode);
        this.defaultEditor.refresh();
    }

    handleError(message, error) {
        console.error(message, error);
        $.growl.error({
            message: error.message || 'An error occurred',
            duration: 5000
        });
    }

    /**
     * Sökfunktioner
     */
    initializeSearch() {
        const searchBox = $('.file-search');
        searchBox.on('keyup', _.debounce(() => {
            const term = searchBox.val();
            this.fileTree.search(term);
        }, 300));
    }

    searchFiles() {
        const term = $('.file-search').val();
        if (term.length > 2) {
            this.fileTree.search(term);
        } else if (term.length === 0) {
            this.fileTree.clear_search();
        }
    }

    /**
     * Refresh functions
     */
    async refreshFiles() {
        await this.loadFileTree();
        if (this.currentPath) {
            await this.openFile(this.currentPath);
        }
    }
}

// Initialisera när dokumentet är klart
$(document).ready(() => {
    if ($('#file-explorer').length) {
        window.fileExplorer = new FileExplorer({
            apiEndpoint: prestashop.urls.base_url + 'moduleFiles',
            moduleId: $('#file-explorer').data('module-id')
        });
    }
});