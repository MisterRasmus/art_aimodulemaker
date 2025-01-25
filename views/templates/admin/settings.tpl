<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-cog"></i> {l s='AI Module Maker Settings' mod='rl_aimodulemaker'}
    </div>
    
    <div class="panel-body">
        <form id="module-settings-form" class="form-horizontal" method="post">
            {* API Settings Tab *}
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon icon-key"></i> {l s='API Settings' mod='rl_aimodulemaker'}
                </div>
                <div class="panel-body">
                    {* OpenAI Settings *}
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='OpenAI API Key' mod='rl_aimodulemaker'}
                        </label>
                        <div class="col-lg-6">
                            <input type="password" 
                                   name="RLAIMODULEMAKER_OPENAI_API_KEY" 
                                   value="{$currentSettings.openai.key|escape:'html':'UTF-8'}" 
                                   class="form-control">
                        </div>
                        <div class="col-lg-3">
                            <button type="button" class="btn btn-default test-api" data-api="openai">
                                <i class="icon icon-check"></i> {l s='Test Connection' mod='rl_aimodulemaker'}
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='OpenAI Model' mod='rl_aimodulemaker'}
                        </label>
                        <div class="col-lg-6">
                            <select name="RLAIMODULEMAKER_OPENAI_MODEL" class="form-control">
                                <option value="gpt-4" {if $currentSettings.openai.model == 'gpt-4'}selected{/if}>
                                    GPT-4 (Most capable)
                                </option>
                                <option value="gpt-3.5-turbo" {if $currentSettings.openai.model == 'gpt-3.5-turbo'}selected{/if}>
                                    GPT-3.5 Turbo (Faster)
                                </option>
                            </select>
                        </div>
                    </div>

                    {* Claude Settings *}
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Claude API Key' mod='rl_aimodulemaker'}
                        </label>
                        <div class="col-lg-6">
                            <input type="password" 
                                   name="RLAIMODULEMAKER_CLAUDE_API_KEY" 
                                   value="{$currentSettings.claude.key|escape:'html':'UTF-8'}" 
                                   class="form-control">
                        </div>
                        <div class="col-lg-3">
                            <button type="button" class="btn btn-default test-api" data-api="claude">
                                <i class="icon icon-check"></i> {l s='Test Connection' mod='rl_aimodulemaker'}
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Claude Model' mod='rl_aimodulemaker'}
                        </label>
                        <div class="col-lg-6">
                            <select name="RLAIMODULEMAKER_CLAUDE_MODEL" class="form-control">
                                <option value="claude-3-opus-20240229" {if $currentSettings.claude.model == 'claude-3-opus-20240229'}selected{/if}>
                                    Claude-3 Opus (Most capable)
                                </option>
                                <option value="claude-3-sonnet-20240229" {if $currentSettings.claude.model == 'claude-3-sonnet-20240229'}selected{/if}>
                                    Claude-3 Sonnet (Balanced)
                                </option>
                            </select>
                        </div>
                    </div>

                    {* GitHub Settings *}
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='GitHub Token' mod='rl_aimodulemaker'}
                        </label>
                        <div class="col-lg-6">
                            <input type="password" 
                                   name="RLAIMODULEMAKER_GITHUB_TOKEN" 
                                   value="{$currentSettings.github.token|escape:'html':'UTF-8'}" 
                                   class="form-control">
                            <p class="help-block">
                                {l s='Create a token with repo and workflow permissions' mod='rl_aimodulemaker'}
                            </p>
                        </div>
                        <div class="col-lg-3">
                            <button type="button" class="btn btn-default test-api" data-api="github">
                                <i class="icon icon-check"></i> {l s='Test Connection' mod='rl_aimodulemaker'}
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='GitHub Username' mod='rl_aimodulemaker'}
                        </label>
                        <div class="col-lg-6">
                            <input type="text" 
                                   name="RLAIMODULEMAKER_GITHUB_USERNAME" 
                                   value="{$currentSettings.github.username|escape:'html':'UTF-8'}" 
                                   class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='GitHub Organization' mod='rl_aimodulemaker'}
                        </label>
                        <div class="col-lg-6">
                            <input type="text" 
                                   name="RLAIMODULEMAKER_GITHUB_ORG" 
                                   value="{$currentSettings.github.organization|escape:'html':'UTF-8'}" 
                                   class="form-control">
                            <p class="help-block">
                                {l s='Optional: Leave empty to use personal account' mod='rl_aimodulemaker'}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {* General Settings Tab *}
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon icon-wrench"></i> {l s='General Settings' mod='rl_aimodulemaker'}
                </div>
                <div class="panel-body">
                    {* Default Author *}
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Default Author' mod='rl_aimodulemaker'}
                        </label>
                        <div class="col-lg-6">
                            <input type="text" 
                                   name="RLAIMODULEMAKER_DEFAULT_AUTHOR" 
                                   value="{$currentSettings.general.default_author|escape:'html':'UTF-8'}" 
                                   class="form-control">
                        </div>
                    </div>

                    {* Auto Commit *}
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Auto Commit Changes' mod='rl_aimodulemaker'}
                        </label>
                        <div class="col-lg-6">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" 
                                           name="RLAIMODULEMAKER_AUTO_COMMIT" 
                                           value="1" 
                                           {if $currentSettings.general.auto_commit}checked{/if}>
                                    {l s='Automatically commit changes to GitHub' mod='rl_aimodulemaker'}
                                </label>
                            </div>
                        </div>
                    </div>

                    {* Default AI Model *}
                    <div class="form-group">
                        <label class="control-label col-lg-3">
                            {l s='Default AI Model' mod='rl_aimodulemaker'}
                        </label>
                        <div class="col-lg-6">
                            <select name="RLAIMODULEMAKER_DEFAULT_AI" class="form-control">
                                <option value="openai" {if $currentSettings.general.default_ai == 'openai'}selected{/if}>
                                    OpenAI GPT
                                </option>
                                <option value="claude" {if $currentSettings.general.default_ai == 'claude'}selected{/if}>
                                    Anthropic Claude
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {* Submit Button *}
            <div class="panel-footer">
                <button type="submit" class="btn btn-default pull-right" name="submitRlAiSettings">
                    <i class="process-icon-save"></i> {l s='Save Settings' mod='rl_aimodulemaker'}
                </button>
            </div>
        </form>

        {* API Test Results Modal *}
        <div class="modal fade" id="api-test-modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">{l s='API Connection Test' mod='rl_aimodulemaker'}</h4>
                    </div>
                    <div class="modal-body">
                        <div class="api-test-result"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{* JavaScript för inställningar *}
<script type="text/javascript">
    $(document).ready(function() {
        // API-test hantering
        $('.test-api').click(function() {
            var api = $(this).data('api');
            var button = $(this);
            button.prop('disabled', true);
            
            $.ajax({
                url: '{$link->getAdminLink('AdminRlAiSettings')|addslashes}',
                type: 'POST',
                data: {
                    ajax: 1,
                    action: 'testApi',
                    api_type: api
                },
                success: function(response) {
                    var result = JSON.parse(response);
                    showApiTestResult(api, result.success, result.message);
                },
                error: function() {
                    showApiTestResult(api, false, 'Connection failed');
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        });

        function showApiTestResult(api, success, message) {
            var html = '<div class="alert alert-' + (success ? 'success' : 'danger') + '">';
            html += '<h4>' + api.toUpperCase() + ' API Test</h4>';
            html += '<p>' + message + '</p>';
            html += '</div>';

            $('.api-test-result').html(html);
            $('#api-test-modal').modal('show');
        }
    });
</script>