{extends file="./helpers/list/list_content.tpl"}

{block name="td_content"}
    {if $key == 'status'}
        <span class="badge badge-{if $tr.status == 'production'}success
            {elseif $tr.status == 'testing'}warning
            {elseif $tr.status == 'development'}info
            {else}default{/if}">
            {$tr.status|escape:'html':'UTF-8'}
        </span>
    {elseif $key == 'github_repo'}
        {if $tr.github_repo}
            <a href="{$tr.github_repo|escape:'html':'UTF-8'}" target="_blank" class="btn btn-default btn-xs">
                <i class="icon icon-github"></i> {l s='View on GitHub' mod='art_aimodulemaker'}
            </a>
        {else}
            <span class="text-muted">-</span>
        {/if}
    {elseif $key == 'actions'}
        <div class="btn-group">
            <a href="{$link->getAdminLink('AdminArtAiModuleMaker')|escape:'html':'UTF-8'}&amp;id_module={$tr.id|intval}&amp;updatemodule" 
               class="btn btn-default btn-xs" title="{l s='Edit' mod='art_aimodulemaker'}">
                <i class="icon icon-pencil"></i>
            </a>
            
            <a href="{$link->getAdminLink('AdminArtAiModuleMaker')|escape:'html':'UTF-8'}&amp;id_module={$tr.id|intval}&amp;duplicatemodule" 
               class="btn btn-default btn-xs" title="{l s='Duplicate' mod='art_aimodulemaker'}">
                <i class="icon icon-copy"></i>
            </a>
            
            <a href="{$link->getAdminLink('AdminArtAiModuleMaker')|escape:'html':'UTF-8'}&amp;id_module={$tr.id|intval}&amp;exportmodule" 
               class="btn btn-default btn-xs" title="{l s='Export' mod='art_aimodulemaker'}">
                <i class="icon icon-download"></i>
            </a>

            {if $tr.github_repo}
                <a href="#" class="btn btn-default btn-xs js-sync-github" data-module-id="{$tr.id|intval}" 
                   title="{l s='Sync with GitHub' mod='art_aimodulemaker'}">
                    <i class="icon icon-refresh"></i>
                </a>
            {/if}

            <a href="{$link->getAdminLink('AdminArtAiModuleMaker')|escape:'html':'UTF-8'}&amp;id_module={$tr.id|intval}&amp;deletemodule" 
               class="btn btn-danger btn-xs" 
               onclick="return confirm('{l s='Are you sure you want to delete this module?' mod='art_aimodulemaker' js=1}');" 
               title="{l s='Delete' mod='art_aimodulemaker'}">
                <i class="icon icon-trash"></i>
            </a>
        </div>

        <div class="btn-group margin-top-1">
            <a href="#" class="btn btn-default btn-xs js-module-versions" data-module-id="{$tr.id|intval}" 
               title="{l s='Version History' mod='art_aimodulemaker'}">
                <i class="icon icon-history"></i> {l s='Versions' mod='art_aimodulemaker'}
            </a>

            <a href="#" class="btn btn-default btn-xs js-module-ai-chat" data-module-id="{$tr.id|intval}" 
               title="{l s='AI Chat' mod='art_aimodulemaker'}">
                <i class="icon icon-comments"></i> {l s='AI Chat' mod='art_aimodulemaker'}
            </a>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="after"}
    {* Modal för versionshistorik *}
    <div class="modal fade" id="version-history-modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">{l s='Version History' mod='art_aimodulemaker'}</h4>
                </div>
                <div class="modal-body">
                    <div class="version-history-content"></div>
                </div>
            </div>
        </div>
    </div>

    {* Modal för AI-chat *}
    <div class="modal fade" id="ai-chat-modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">{l s='AI Assistant' mod='art_aimodulemaker'}</h4>
                </div>
                <div class="modal-body">
                    <div class="chat-messages"></div>
                    <div class="chat-input margin-top-1">
                        <div class="input-group">
                            <input type="text" class="form-control" id="ai-message-input" 
                                   placeholder="{l s='Type your message...' mod='art_aimodulemaker'}">
                            <span class="input-group-btn">
                                <button class="btn btn-primary" type="button" id="send-message">
                                    <i class="icon icon-paper-plane"></i> {l s='Send' mod='art_aimodulemaker'}
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
{/block}