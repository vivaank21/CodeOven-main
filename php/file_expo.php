<div class="file-explorer">
    <div class="file-explorer-head">
        <div class="file-explorer-title">
            <i class="fas fa-folder-tree explorer-title-icon"></i>
            <span>EXPLORER</span>
        </div>
        <div class="file-explorer-actions">
            <button id="new-file-btn" class="icon-btn" title="New file (Ctrl+N)">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>
    <ul class="file-list" id="file-list">
        <!-- Populated dynamically by js/dashboard.js from the current project -->
        <li class="file-item-empty">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Loading workspace files...</span>
        </li>
    </ul>
</div>
