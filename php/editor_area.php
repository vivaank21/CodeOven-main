<div class="editor-area">
    <div class="editor-tabs-bar">
        <div class="editor-tabs" id="editor-tabs">
            <!-- Populated dynamically: one tab per open file -->
        </div>
    </div>

    <div class="editor-container">
        <div id="code-editor"></div>
        <div class="editor-empty-state" id="editor-empty-state">
            <div class="empty-state-card">
                <div class="empty-state-icon">
                    <i class="fas fa-code"></i>
                </div>
                <h3>No Active File</h3>
                <p>Select a file from the explorer on the left or create a new file to start coding.</p>
                <div class="empty-state-shortcuts">
                    <div class="shortcut-item"><kbd>Ctrl</kbd> + <kbd>S</kbd> <span>Save changes</span></div>
                    <div class="shortcut-item"><kbd>Ctrl</kbd> + <kbd>Enter</kbd> <span>Run code in terminal</span></div>
                    <div class="shortcut-item"><kbd>Ctrl</kbd> + <kbd>F</kbd> <span>Find / Search</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Authentic VS Code Terminal Panel -->
    <div class="vscode-terminal-panel hidden" id="console-panel">
        <div class="vscode-terminal-header">
            <div class="vscode-terminal-tabs">
                <button class="term-tab-btn" data-panel="problems">PROBLEMS <span class="term-counter-pill">0</span></button>
                <button class="term-tab-btn" data-panel="output">OUTPUT</button>
                <button class="term-tab-btn" data-panel="debug">DEBUG CONSOLE</button>
                <button class="term-tab-btn active" data-panel="terminal">
                    <i class="fas fa-terminal term-tab-icon"></i>
                    <span>TERMINAL</span>
                    <span class="term-session-tag" id="term-session-tag">1: bash</span>
                </button>
            </div>

            <div class="vscode-terminal-actions">
                <span class="term-status-pill" id="term-status-pill">
                    <span class="term-status-dot"></span>
                    <span id="term-status-text">Ready</span>
                </span>
                <button id="terminal-clear-btn" class="term-tool-btn" title="Clear Terminal (Ctrl+L / Trash)">
                    <i class="fas fa-trash-can"></i>
                </button>
                <button id="terminal-copy-btn" class="term-tool-btn" title="Copy All Output">
                    <i class="fas fa-copy"></i>
                </button>
                <button id="terminal-maximize-btn" class="term-tool-btn" title="Toggle Size">
                    <i class="fas fa-chevron-up"></i>
                </button>
                <button id="console-close-btn" class="term-tool-btn" title="Close Panel">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Terminal Viewport (Clicking anywhere focuses the terminal input) -->
        <div class="vscode-terminal-viewport" id="vscode-terminal-viewport">
            <div class="vscode-terminal-stream" id="vscode-terminal-stream">
                <!-- Executed outputs, program prompts, and outputs streamed here in pure white -->
            </div>

            <!-- Active interactive prompt & inline input cursor -->
            <div class="vscode-terminal-line" id="vscode-terminal-line">
                <span class="vscode-prompt-text" id="vscode-prompt-text">PS C:\CodeOven&gt;</span>
                <div class="vscode-input-wrapper">
                    <input type="text" id="terminal-interactive-input" autocomplete="off" spellcheck="false" autofocus>
                </div>
            </div>
        </div>
    </div>
</div>
