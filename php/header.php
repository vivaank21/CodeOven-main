<header class="header">
    <div class="header-left">
        <!-- Official CodeOven Brand Logo with Accessible Label -->
        <a href="../index.html" class="brand-logo logo" aria-label="CodeOven Cloud IDE" title="CodeOven — The Next-Gen Web IDE &amp; Live Playground">
            <div class="logo-icon-box">
                <i class="fas fa-fire-burner"></i>
            </div>
            <div class="logo-text">Code<span class="logo-accent">Oven</span></div>
        </a>

        <!-- Desktop Menu Bar -->
        <ul class="nav-menu" role="menubar" aria-label="Editor Menu Bar">
            <li class="nav-item" tabindex="0" role="menuitem" aria-haspopup="true">
                <span><i class="fas fa-folder-open nav-icon"></i> File</span>
                <div class="submenu">
                    <div class="submenu-item" data-action="new-project"><i class="fas fa-plus"></i> New Project <span class="shortcut">Alt+N</span></div>
                    <div class="submenu-item" data-action="rename-project"><i class="fas fa-pen"></i> Rename Project</div>
                    <div class="submenu-item" data-action="delete-project"><i class="fas fa-trash"></i> Delete Project</div>
                    <div class="submenu-divider"></div>
                    <div class="submenu-item" data-action="new-file"><i class="fas fa-file-circle-plus"></i> New File</div>
                    <div class="submenu-item" data-action="save"><i class="fas fa-floppy-disk"></i> Save <span class="shortcut">Ctrl+S</span></div>
                    <div class="submenu-item" data-action="save-as"><i class="fas fa-floppy-disk"></i> Save As...</div>
                    <div class="submenu-item" data-action="rename-file"><i class="fas fa-pen-to-square"></i> Rename File</div>
                    <div class="submenu-item" data-action="delete-file"><i class="fas fa-trash-can"></i> Delete File</div>
                    <div class="submenu-divider"></div>
                    <div class="submenu-item" data-action="download"><i class="fas fa-cloud-arrow-down"></i> Download (.zip)</div>
                </div>
            </li>
            <li class="nav-item" tabindex="0" role="menuitem" aria-haspopup="true">
                <span><i class="fas fa-pen-nib nav-icon"></i> Edit</span>
                <div class="submenu">
                    <div class="submenu-item" data-action="undo"><i class="fas fa-rotate-left"></i> Undo <span class="shortcut">Ctrl+Z</span></div>
                    <div class="submenu-item" data-action="redo"><i class="fas fa-rotate-right"></i> Redo <span class="shortcut">Ctrl+Y</span></div>
                    <div class="submenu-divider"></div>
                    <div class="submenu-item" data-action="cut"><i class="fas fa-scissors"></i> Cut <span class="shortcut">Ctrl+X</span></div>
                    <div class="submenu-item" data-action="copy"><i class="fas fa-copy"></i> Copy <span class="shortcut">Ctrl+C</span></div>
                    <div class="submenu-item" data-action="paste"><i class="fas fa-paste"></i> Paste <span class="shortcut">Ctrl+V</span></div>
                    <div class="submenu-item" data-action="select-all"><i class="fas fa-object-group"></i> Select All <span class="shortcut">Ctrl+A</span></div>
                    <div class="submenu-divider"></div>
                    <div class="submenu-item" data-action="find"><i class="fas fa-magnifying-glass"></i> Find <span class="shortcut">Ctrl+F</span></div>
                    <div class="submenu-item" data-action="replace"><i class="fas fa-arrow-right-arrow-left"></i> Replace <span class="shortcut">Ctrl+H</span></div>
                    <div class="submenu-item" data-action="goto-line"><i class="fas fa-list-ol"></i> Go to Line <span class="shortcut">Alt+G</span></div>
                </div>
            </li>
            <li class="nav-item" tabindex="0" role="menuitem" aria-haspopup="true">
                <span><i class="fas fa-desktop nav-icon"></i> View</span>
                <div class="submenu">
                    <div class="submenu-item" data-action="word-wrap"><i class="fas fa-text-slash"></i> Toggle Word Wrap</div>
                    <div class="submenu-item" data-action="toggle-layout"><i class="fas fa-table-columns"></i> Toggle Layout (H/V)</div>
                    <div class="submenu-item" data-action="zoom-in"><i class="fas fa-magnifying-glass-plus"></i> Zoom In <span class="shortcut">Ctrl++</span></div>
                    <div class="submenu-item" data-action="zoom-out"><i class="fas fa-magnifying-glass-minus"></i> Zoom Out <span class="shortcut">Ctrl+-</span></div>
                    <div class="submenu-item" data-action="toggle-fold"><i class="fas fa-compress"></i> Fold/Unfold Code</div>
                    <div class="submenu-divider"></div>
                    <div class="submenu-item" data-action="toggle-console"><i class="fas fa-terminal"></i> Toggle Console</div>
                    <div class="submenu-item" data-action="open-console"><i class="fas fa-terminal"></i> Open Terminal View</div>
                    <div class="submenu-item" data-action="open-preview"><i class="fas fa-globe"></i> Open Live Preview</div>
                </div>
            </li>
            <li class="nav-item" tabindex="0" role="menuitem" aria-haspopup="true">
                <span><i class="fas fa-play nav-icon"></i> Run</span>
                <div class="submenu">
                    <div class="submenu-item" data-action="run"><i class="fas fa-play"></i> Run Active Code <span class="shortcut">Ctrl+↵</span></div>
                    <div class="submenu-item" data-action="toggle-autorun"><i class="fas fa-bolt"></i> Toggle Auto-Run (HTML)</div>
                    <div class="submenu-item" data-action="clear-console"><i class="fas fa-eraser"></i> Clear Terminal</div>
                </div>
            </li>
        </ul>
    </div>

    <div class="header-actions">
        <!-- Project Selector -->
        <div class="project-selector-wrapper">
            <i class="fas fa-box-archive project-icon"></i>
            <select id="project-select" class="project-select" title="Switch active project" aria-label="Select Project"></select>
            <i class="fas fa-chevron-down select-chevron"></i>
        </div>

        <!-- Quick Execution & Tools -->
        <div class="action-buttons">
            <button id="run-button" class="run-btn" title="Run / Compile (Ctrl+Enter)" aria-label="Run Code">
                <span class="run-icon"><i class="fas fa-play"></i></span>
                <span class="run-label">Run</span>
            </button>
            <button id="preview-button" class="preview-btn" title="Open Live Preview" aria-label="Open Live Preview">
                <i class="fas fa-eye"></i>
                <span>Preview</span>
            </button>
            <button id="theme-toggle" class="theme-toggle" title="Toggle dark/light theme" aria-label="Toggle Theme">
                <i class="fas fa-moon"></i>
            </button>
        </div>

        <!-- User Authentication & Profile Buttons -->
        <div class="user-menu">
            <div class="user-chip" title="Account: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?>">
                <div class="user-avatar">
                    <i class="fas fa-user-ninja"></i>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></span>
                    <span class="user-badge"><?php echo isset($_SESSION['user_id']) ? 'CLOUD SYNC' : 'GUEST'; ?></span>
                </div>
            </div>

            <div class="auth-buttons-group">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button type="button" class="header-btn btn-profile" onclick="window.location.href='profile.php'" title="View and edit developer profile">
                        <i class="fas fa-user-gear"></i>
                        <span>Profile</span>
                    </button>
                    <button type="button" class="header-btn btn-logout logout-btn auth-action-btn" onclick="window.location.href='logout.php'" title="Sign out of account">
                        <i class="fas fa-arrow-right-from-bracket"></i>
                        <span>Logout</span>
                    </button>
                <?php else: ?>
                    <button type="button" class="header-btn btn-login login-btn-header auth-action-btn" onclick="window.location.href='login.php'" title="Sign in to save and sync projects">
                        <i class="fas fa-right-to-bracket"></i>
                        <span>Log In</span>
                    </button>
                    <button type="button" class="header-btn btn-signup signup-btn-header" onclick="window.location.href='signup.php'" title="Create a free CodeOven account">
                        <i class="fas fa-user-plus"></i>
                        <span>Create Account</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
