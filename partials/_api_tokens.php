<?php
    $user = BackendAuth::getUser();
    $tokens = $user->api_tokens()->orderBy('created_at', 'desc')->get();
    $newToken = $newToken ?? null;
?>

<div id="apiTokensContainer">

    <?php if ($newToken): ?>
        <div class="callout callout-success" style="margin-bottom: 20px;">
            <div class="header">
                <i class="icon-check"></i>
                <h3>Token Created</h3>
                <p>Copy this token now. You will not be able to see it again.</p>
            </div>
            <div style="margin-top: 10px;">
                <div class="input-group">
                    <input
                        type="text"
                        class="form-control"
                        value="<?= e($newToken) ?>"
                        id="newTokenValue"
                        readonly
                        style="font-family: monospace; font-size: 13px;"
                    />
                    <span class="input-group-btn">
                        <button
                            type="button"
                            class="btn btn-default"
                            onclick="var el = document.getElementById('newTokenValue'); el.select(); document.execCommand('copy'); $.wn.flashMsg({text: 'Token copied!', class: 'success'});"
                        >
                            <i class="icon-copy"></i> Copy
                        </button>
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="form-group" style="margin-bottom: 20px;">
        <h5 style="margin-bottom: 15px;">Create New Token</h5>
        <div class="row">
            <div class="col-md-4">
                <label class="control-label">Token Name</label>
                <input
                    type="text"
                    name="token_name"
                    class="form-control"
                    placeholder="e.g. CI Pipeline, My Script"
                />
            </div>
            <div class="col-md-4">
                <label class="control-label">Expires At (optional)</label>
                <input
                    type="date"
                    name="token_expires_at"
                    class="form-control"
                />
            </div>
            <div class="col-md-4">
                <label class="control-label">&nbsp;</label>
                <div>
                    <button
                        type="button"
                        class="btn btn-primary"
                        data-request="onCreateApiToken"
                        data-load-indicator="Creating token..."
                    >
                        <i class="icon-plus"></i> Create Token
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if ($tokens->count()): ?>
        <h5 style="margin-bottom: 10px;">Existing Tokens</h5>
        <div class="control-list list-scrollable">
            <table class="table data">
                <thead>
                    <tr>
                        <th><span>Name</span></th>
                        <th><span>Created</span></th>
                        <th><span>Last Used</span></th>
                        <th><span>Expires</span></th>
                        <th style="width: 100px;"><span></span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tokens as $token): ?>
                        <tr>
                            <td><strong><?= e($token->name) ?></strong></td>
                            <td><?= e($token->created_at->format('Y-m-d H:i')) ?></td>
                            <td><?= $token->last_used_at ? e($token->last_used_at->diffForHumans()) : '<span class="text-muted">Never</span>' ?></td>
                            <td>
                                <?php if ($token->expires_at): ?>
                                    <?php if ($token->isExpired()): ?>
                                        <span class="text-danger"><?= e($token->expires_at->format('Y-m-d')) ?> (expired)</span>
                                    <?php else: ?>
                                        <?= e($token->expires_at->format('Y-m-d')) ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Never</span>
                                <?php endif; ?>
                            </td>
                            <td class="nolink">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-danger"
                                    data-request="onRevokeApiToken"
                                    data-request-data="token_id: <?= $token->id ?>"
                                    data-request-confirm="Are you sure you want to revoke this token? This cannot be undone."
                                    data-load-indicator="Revoking..."
                                >
                                    Revoke
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted" style="margin-top: 10px;">No API tokens created yet.</p>
    <?php endif; ?>

</div>
