<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-cube"></i> {l s='Create New Module' mod='rl_aimodulemaker'}
    </div>
    
    <div class="panel-body">
        <div class="row">
            {* Vänster kolumn - Modulformulär *}
            <div class="col-md-6">
                <form id="module-builder-form" class="form-horizontal">
                    <div class="panel">
                        <div class="panel-heading">
                            {l s='Module Information' mod='rl_aimodulemaker'}
                        </div>
                        <div class="panel-body">
                            {* Tekniskt namn *}
                            <div class="form-group">
                                <label class="control-label col-lg-3 required">
                                    {l s='Technical Name' mod='rl_aimodulemaker'}
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="module_name" class="form-control" required 
                                           pattern="^[a-z][a-z0-9_]+$" 
                                           placeholder="mymodule">
                                    <p class="help-block">
                                        {l s='Only lowercase letters, numbers and underscore. Must start with a letter.' mod='rl_aimodulemaker'}
                                    </p>
                                </div>
                            </div>

                            {* Visningsnamn *}
                            <div class="form-group">
                                <label class="control-label col-lg-3 required">
                                    {l s='Display Name' mod='rl_aimodulemaker'}
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="display_name" class="form-control" required>
                                </div>
                            </div>

                            {* Beskrivning *}
                            <div class="form-group">
                                <label class="control-label col-lg-3 required">
                                    {l s='Description' mod='rl_aimodulemaker'}
                                </label>
                                <div class="col-lg-9">
                                    <textarea name="description" class="form-control" rows="3" required></textarea>
                                </div>
                            </div>

                            {* Version *}
                            <div class="form-group">
                                <label class="control-label col-lg-3 required">
                                    {l s='Version' mod='rl_aimodulemaker'}
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="version" class="form-control" required 
                                           pattern="^\d+\.\d+\.\d+$" value="1.0.0">
                                </div>
                            </div>

                            {* Modultyp *}
                            <div class="form-group">
                                <label class="control-label col-lg-3 required">
                                    {l s='Module Type' mod='rl_aimodulemaker'}
                                </label>
                                <div class="col-lg-9">
                                    <select name="type" class="form-control" required>
                                        <option value="payment">{l s='Payment Module' mod='rl_aimodulemaker'}</option>
                                        <option value="shipping">{l s='Shipping Module' mod='rl_aimodulemaker'}</option>
                                        <option value="analytics">{l s='Analytics Module' mod='rl_aimodulemaker'}</option>
                                        <option value="marketplace">{l s='Marketplace Module' mod='rl_aimodulemaker'}</option>
                                        <option value="seo">{l s='SEO Module' mod='rl_aimodulemaker'}</option>
                                        <option value="custom">{l s='Custom Module' mod='rl_aimodulemaker'}</option>
                                    </select>
                                </div>
                            </div>

                            {* AI Model *}
                            <div class="form-group">
                                <label class="control-label col-lg-3 required">
                                    {l s='AI Assistant' mod='rl_aimodulemaker'}
                                </label>
                                <div class="col-lg-9">
                                    <select name="ai_model" class="form-control" required>
                                        {foreach $ai_models as $model}
                                            <option value="{$model.id|escape:'html':'UTF-8'}">
                                                {$model.name|escape:'html':'UTF-8'}
                                            </option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>

                            {* GitHub Integration *}
                            <div class="form-group">
                                <label class="control-label col-lg-3">
                                    {l s='GitHub Integration' mod='rl_aimodulemaker'}
                                </label>
                                <div class="col-lg-9">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="create_github_repo" value="1">
                                            {l s='Create GitHub repository' mod='rl_aimodulemaker'}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {* VS Code Integration *}
                            <div class="form-group">
                                <label class="control-label col-lg-3">
                                    {l s='VS Code Integration' mod='rl_aimodulemaker'}
                                </label>
                                <div class="col-lg-9">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="create_vscode_workspace" value="1">
                                            {l s='Generate VS Code workspace' mod='rl_aimodulemaker'}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {* Höger kolumn - AI Chat *}
            <div class="col-md-6">
                <div class="panel">
                    <div class="panel-heading">
                        {l s='AI Assistant' mod='rl_aimodulemaker'}
                    </div>
                    <div class="panel-body">
                        <div class="ai-chat-container" style="height: 400px; overflow-y: auto;">
                            <div class="chat-messages"></div>
                        </div>
                        <div class="chat-input margin-top-1">
                            <div class="input-group">
                                <input type="text" class="form-control" id="ai-message-input" 
                                       placeholder="{l s='Describe your module or ask questions...' mod='rl_aimodulemaker'}">
                                <span class="input-group-btn">
                                    <button class="btn btn-primary" type="button" id="send-message">
                                        <i class="icon icon-paper-plane"></i> {l s='Send' mod='rl_aimodulemaker'}
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {* Förhandsgranskning *}
                <div class="panel">
                    <div class="panel-heading">
                        {l s='Module Preview' mod='rl_aimodulemaker'}
                    </div>
                    <div class="panel-body">
                        <div class="module-preview"></div>
                        <div class="text-center margin-top-1">
                            <button type="button" class="btn btn-success btn-lg" id="generate-module">
                                <i class="icon icon-magic"></i> {l s='Generate Module' mod='rl_aimodulemaker'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{* Modaler *}
<div class="modal fade" id="generation-progress-modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">{l s='Generating Module' mod='rl_aimodulemaker'}</h4>
            </div>
            <div class="modal-body">
                <div class="progress-info"></div>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped active" style="width: 0%"></div>
                </div>
                <div class="generation-log" style="max-height: 200px; overflow-y: auto;"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="module-complete-modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{l s='Module Generated Successfully' mod='rl_aimodulemaker'}</h4>
            </div>
            <div class="modal-body">
                <div class="generation-summary"></div>
                <div class="text-center margin-top-1">
                    <a href="#" class="btn btn-primary download-module">
                        <i class="icon icon-download"></i> {l s='Download Module' mod='rl_aimodulemaker'}
                    </a>
                    <a href="#" class="btn btn-default view-on-github">
                        <i class="icon icon-github"></i> {l s='View on GitHub' mod='rl_aimodulemaker'}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{* JavaScript för modulbyggaren *}
<script type="text/javascript">
    var moduleBuilderConfig = {
        ajaxUrl: '{$link->getAdminLink('AdminRlAiModuleMaker')|addslashes}',
        generateToken: '{$generate_token|escape:'html':'UTF-8'}',
        translations: {
            error: '{l s='Error' mod='rl_aimodulemaker' js=1}',
            success: '{l s='Success' mod='rl_aimodulemaker' js=1}',
            generating: '{l s='Generating module...' mod='rl_aimodulemaker' js=1}',
            waitingAi: '{l s='Waiting for AI response...' mod='rl_aimodulemaker' js=1}'
        }
    };
</script>