{extends file="./helpers/view/view.tpl"}

{block name="override_tpl"}
<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-cogs"></i> {l s='AI Module Maker' mod='rl_aimodulemaker'}
    </div>

    <div class="panel-body">
        {if isset($api_status)}
            <div class="row margin-bottom-1">
                <div class="col-md-12">
                    <div class="alert {if $api_status.all_configured}alert-success{else}alert-warning{/if}">
                        <h4>{l s='API Status' mod='rl_aimodulemaker'}</h4>
                        <ul class="list-unstyled">
                            <li>
                                <i class="icon {if $api_status.openai}icon-check text-success{else}icon-times text-danger{/if}"></i>
                                OpenAI API: {if $api_status.openai}{l s='Configured' mod='rl_aimodulemaker'}{else}{l s='Not Configured' mod='rl_aimodulemaker'}{/if}
                            </li>
                            <li>
                                <i class="icon {if $api_status.claude}icon-check text-success{else}icon-times text-danger{/if}"></i>
                                Claude API: {if $api_status.claude}{l s='Configured' mod='rl_aimodulemaker'}{else}{l s='Not Configured' mod='rl_aimodulemaker'}{/if}
                            </li>
                            <li>
                                <i class="icon {if $api_status.github}icon-check text-success{else}icon-times text-danger{/if}"></i>
                                GitHub API: {if $api_status.github}{l s='Configured' mod='rl_aimodulemaker'}{else}{l s='Not Configured' mod='rl_aimodulemaker'}{/if}
                            </li>
                        </ul>
                        {if !$api_status.all_configured}
                            <p>
                                <a href="{$link->getAdminLink('AdminRlAiSettings')|escape:'html':'UTF-8'}" class="btn btn-default">
                                    <i class="icon icon-cog"></i> {l s='Configure APIs' mod='rl_aimodulemaker'}
                                </a>
                            </p>
                        {/if}
                    </div>
                </div>
            </div>
        {/if}

        <div class="row">
            {foreach $moduleActions as $action}
                <div class="col-md-4">
                    <div class="panel">
                        <div class="panel-heading">
                            <i class="icon icon-{$action.icon}"></i> {$action.title|escape:'html':'UTF-8'}
                        </div>
                        <div class="panel-body">
                            <p>{$action.description|escape:'html':'UTF-8'}</p>
                            <a href="{$action.link|escape:'html':'UTF-8'}" class="btn btn-primary">
                                {l s='Go' mod='rl_aimodulemaker'}
                            </a>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>

        {if isset($module_stats)}
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <i class="icon icon-bar-chart"></i> {l s='Module Statistics' mod='rl_aimodulemaker'}
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="well">
                                        <h4>{l s='Total Modules' mod='rl_aimodulemaker'}</h4>
                                        <span class="badge badge-info">{$module_stats.total|intval}</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="well">
                                        <h4>{l s='In Development' mod='rl_aimodulemaker'}</h4>
                                        <span class="badge badge-warning">{$module_stats.development|intval}</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="well">
                                        <h4>{l s='In Testing' mod='rl_aimodulemaker'}</h4>
                                        <span class="badge badge-info">{$module_stats.testing|intval}</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="well">
                                        <h4>{l s='In Production' mod='rl_aimodulemaker'}</h4>
                                        <span class="badge badge-success">{$module_stats.production|intval}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        {/if}

        {if isset($recent_activity)}
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <i class="icon icon-clock-o"></i> {l s='Recent Activity' mod='rl_aimodulemaker'}
                        </div>
                        <div class="panel-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{l s='Date' mod='rl_aimodulemaker'}</th>
                                        <th>{l s='Module' mod='rl_aimodulemaker'}</th>
                                        <th>{l s='Action' mod='rl_aimodulemaker'}</th>
                                        <th>{l s='Details' mod='rl_aimodulemaker'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach $recent_activity as $activity}
                                        <tr>
                                            <td>{$activity.date_add|escape:'html':'UTF-8'}</td>
                                            <td>{$activity.module_name|escape:'html':'UTF-8'}</td>
                                            <td>{$activity.action|escape:'html':'UTF-8'}</td>
                                            <td>{$activity.details|escape:'html':'UTF-8'}</td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        {/if}
    </div>
</div>
{/block}