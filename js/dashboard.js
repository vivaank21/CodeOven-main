/**
 * js/dashboard.js
 * CodeOven editor front-end: multi-file tabs, multi-language CodeMirror,
 * project/file management (server-backed when logged in, localStorage-backed
 * guest mode otherwise), live HTML/CSS/JS preview, and Python/C++/Java
 * execution via api/run.php.
 */
(function () {
  'use strict';

  const API = '../api/';
  const LOGGED_IN = !!window.CODEOVEN_LOGGED_IN;

  // ---------------------------------------------------------------------
  // Language / extension mapping (keep in sync with includes/lang.php)
  // ---------------------------------------------------------------------
  const EXT_INFO = {
    html: { mode: 'htmlmixed', label: 'HTML', icon: '🌐', category: 'web' },
    htm: { mode: 'htmlmixed', label: 'HTML', icon: '🌐', category: 'web' },
    css: { mode: 'css', label: 'CSS', icon: '🎨', category: 'web' },
    js: { mode: 'javascript', label: 'JavaScript', icon: '📜', category: 'web' },
    mjs: { mode: 'javascript', label: 'JavaScript', icon: '📜', category: 'web' },
    json: { mode: { name: 'javascript', json: true }, label: 'JSON', icon: '🧾', category: 'web' },
    php: { mode: 'application/x-httpd-php', label: 'PHP', icon: '🐘', category: 'runnable' },
    phtml: { mode: 'application/x-httpd-php', label: 'PHP', icon: '🐘', category: 'runnable' },
    py: { mode: 'python', label: 'Python', icon: '🐍', category: 'runnable' },
    pyw: { mode: 'python', label: 'Python', icon: '🐍', category: 'runnable' },
    cpp: { mode: 'text/x-c++src', label: 'C++', icon: '⚙️', category: 'runnable' },
    cxx: { mode: 'text/x-c++src', label: 'C++', icon: '⚙️', category: 'runnable' },
    cc: { mode: 'text/x-c++src', label: 'C++', icon: '⚙️', category: 'runnable' },
    c: { mode: 'text/x-csrc', label: 'C', icon: '⚙️', category: 'runnable' },
    h: { mode: 'text/x-c++src', label: 'Header', icon: '⚙️', category: 'other' },
    hpp: { mode: 'text/x-c++src', label: 'Header', icon: '⚙️', category: 'other' },
    java: { mode: 'text/x-java', label: 'Java', icon: '☕', category: 'runnable' },
    xml: { mode: 'xml', label: 'XML', icon: '📰', category: 'other' },
    sql: { mode: 'sql', label: 'SQL', icon: '🗄️', category: 'other' },
    md: { mode: 'markdown', label: 'Markdown', icon: '📝', category: 'other' },
    markdown: { mode: 'markdown', label: 'Markdown', icon: '📝', category: 'other' },
    sh: { mode: 'shell', label: 'Shell', icon: '💲', category: 'other' },
    txt: { mode: 'text/plain', label: 'Plain Text', icon: '📄', category: 'other' },
  };
  const DEFAULT_EXT_INFO = { mode: 'text/plain', label: 'Plain Text', icon: '📄', category: 'other' };

  function extOf(filename) {
    const parts = filename.split('.');
    return parts.length > 1 ? parts.pop().toLowerCase() : '';
  }
  function extInfo(filename) {
    return EXT_INFO[extOf(filename)] || DEFAULT_EXT_INFO;
  }

  // ---------------------------------------------------------------------
  // Tiny fetch helpers
  // ---------------------------------------------------------------------
  function parseJsonSafe(response) {
    return response.text().then((text) => {
      try {
        return JSON.parse(text);
      } catch (e) {
        return {
          success: false,
          message: 'Server error (HTTP ' + response.status + '): ' +
            (text ? text.slice(0, 300) : 'empty response'),
        };
      }
    });
  }
  function getJSON(url) {
    return fetch(url, { credentials: 'same-origin' })
      .then(parseJsonSafe)
      .catch((e) => ({ success: false, message: 'Network error: ' + e.message }));
  }
  function postJSON(url, params) {
    const body = new URLSearchParams();
    Object.keys(params || {}).forEach((k) => body.append(k, params[k] == null ? '' : params[k]));
    return fetch(url, { method: 'POST', credentials: 'same-origin', body })
      .then(parseJsonSafe)
      .catch((e) => ({ success: false, message: 'Network error: ' + e.message }));
  }

  // ---------------------------------------------------------------------
  // Guest (localStorage) persistence -- used automatically when not logged in
  // ---------------------------------------------------------------------
  const GUEST_KEY = 'codeoven_guest_v1';
  const STARTER_FILES = {
    'index.html': '<!DOCTYPE html>\n<html lang="en">\n<head>\n  <meta charset="UTF-8">\n  <title>My Project</title>\n  <link rel="stylesheet" href="style.css">\n</head>\n<body>\n  <h1>Hello, CodeOven!</h1>\n  <script src="script.js"><\/script>\n</body>\n</html>\n',
    'style.css': 'body {\n  font-family: Arial, sans-serif;\n  margin: 40px;\n}\n',
    'script.js': "console.log('Hello from JavaScript!');\n",
  };

  function guestReadAll() {
    try { return JSON.parse(localStorage.getItem(GUEST_KEY) || '{}'); } catch (e) { return {}; }
  }
  function guestWriteAll(obj) { localStorage.setItem(GUEST_KEY, JSON.stringify(obj)); }
  function guestEnsureStore() {
    const store = guestReadAll();
    if (!store.projects) store.projects = {};
    if (!Object.keys(store.projects).length) {
      const files = {};
      Object.keys(STARTER_FILES).forEach((name) => {
        files[name] = { content: STARTER_FILES[name], language: extInfo(name).label, updated: Date.now() };
      });
      store.projects['My First Project'] = { files, updated: Date.now() };
    }
    guestWriteAll(store);
    return store;
  }

  // ---------------------------------------------------------------------
  // Unified data layer: server-backed when logged in, guest store otherwise.
  // Every function returns a Promise resolving to { success, ... }.
  // ---------------------------------------------------------------------
  const Data = {
    listProjects() {
      if (LOGGED_IN) return getJSON(API + 'get_projects.php');
      const store = guestEnsureStore();
      const projects = Object.keys(store.projects).map((name) => ({ project_name: name, updated_at: store.projects[name].updated }));
      return Promise.resolve({ success: true, projects });
    },
    newProject(name, template) {
      if (LOGGED_IN) return postJSON(API + 'new_project.php', { project_name: name, template });
      const store = guestEnsureStore();
      if (store.projects[name]) return Promise.resolve({ success: false, message: 'A project with that name already exists.' });
      const files = {};
      if (template !== 'blank') {
        Object.keys(STARTER_FILES).forEach((fname) => { files[fname] = { content: STARTER_FILES[fname], updated: Date.now() }; });
      }
      store.projects[name] = { files, updated: Date.now() };
      guestWriteAll(store);
      return Promise.resolve({ success: true, project_name: name });
    },
    deleteProject(name) {
      if (LOGGED_IN) return postJSON(API + 'delete_project.php', { project_name: name });
      const store = guestEnsureStore();
      delete store.projects[name];
      guestWriteAll(store);
      return Promise.resolve({ success: true });
    },
    renameProject(oldName, newName) {
      if (LOGGED_IN) return postJSON(API + 'rename_project.php', { old_name: oldName, new_name: newName });
      const store = guestEnsureStore();
      if (store.projects[newName]) return Promise.resolve({ success: false, message: 'A project with that name already exists.' });
      store.projects[newName] = store.projects[oldName];
      delete store.projects[oldName];
      guestWriteAll(store);
      return Promise.resolve({ success: true, project_name: newName });
    },
    getFiles(project) {
      if (LOGGED_IN) return getJSON(API + 'get_files.php?project=' + encodeURIComponent(project));
      const store = guestEnsureStore();
      const p = store.projects[project];
      if (!p) return Promise.resolve({ success: false, message: 'Project not found.' });
      const files = Object.keys(p.files).sort().map((fname) => ({ file_name: fname, language: extInfo(fname).label, updated_at: p.files[fname].updated }));
      return Promise.resolve({ success: true, files });
    },
    loadFile(project, file) {
      if (LOGGED_IN) return getJSON(API + 'load_file.php?project=' + encodeURIComponent(project) + '&file=' + encodeURIComponent(file));
      const store = guestEnsureStore();
      const p = store.projects[project];
      if (!p || !p.files[file]) return Promise.resolve({ success: false, message: 'File not found.' });
      return Promise.resolve({ success: true, file: { file_name: file, content: p.files[file].content, language: extInfo(file).label } });
    },
    newFile(project, file, content) {
      if (LOGGED_IN) return postJSON(API + 'new_file.php', { project, file_name: file, content: content || '' });
      const store = guestEnsureStore();
      if (!store.projects[project]) store.projects[project] = { files: {}, updated: Date.now() };
      if (store.projects[project].files[file]) return Promise.resolve({ success: false, message: 'A file with that name already exists.' });
      store.projects[project].files[file] = { content: content || '', updated: Date.now() };
      guestWriteAll(store);
      return Promise.resolve({ success: true, file_name: file });
    },
    saveFile(project, file, content) {
      if (LOGGED_IN) return postJSON(API + 'save_file.php', { project, file_name: file, content });
      const store = guestEnsureStore();
      if (!store.projects[project]) store.projects[project] = { files: {}, updated: Date.now() };
      store.projects[project].files[file] = { content, updated: Date.now() };
      store.projects[project].updated = Date.now();
      guestWriteAll(store);
      return Promise.resolve({ success: true });
    },
    renameFile(project, oldName, newName) {
      if (LOGGED_IN) return postJSON(API + 'rename_file.php', { project, old_name: oldName, new_name: newName });
      const store = guestEnsureStore();
      const p = store.projects[project];
      if (!p || !p.files[oldName]) return Promise.resolve({ success: false, message: 'File not found.' });
      if (p.files[newName]) return Promise.resolve({ success: false, message: 'A file with that name already exists.' });
      p.files[newName] = p.files[oldName];
      delete p.files[oldName];
      guestWriteAll(store);
      return Promise.resolve({ success: true, file_name: newName });
    },
    deleteFile(project, file) {
      if (LOGGED_IN) return postJSON(API + 'delete_file.php', { project, file_name: file });
      const store = guestEnsureStore();
      if (store.projects[project]) delete store.projects[project].files[file];
      guestWriteAll(store);
      return Promise.resolve({ success: true });
    },
    run(project, entryFile, stdin) {
      if (LOGGED_IN) {
        return postJSON(API + 'run.php', { project, entry_file: entryFile, stdin: stdin || '' });
      }
      const store = guestEnsureStore();
      const p = store.projects[project];
      const guestFiles = p ? JSON.stringify(p.files) : '{}';
      return postJSON(API + 'run.php', { project, entry_file: entryFile, stdin: stdin || '', guest_files: guestFiles });
    },
    downloadUrl(project) {
      return API + 'download.php?project=' + encodeURIComponent(project);
    },
    loadPreferences() {
      if (LOGGED_IN) return getJSON(API + 'load_preferences.php');
      let prefs = {};
      try { prefs = JSON.parse(localStorage.getItem('codeoven_guest_prefs') || '{}'); } catch (e) { /* ignore */ }
      return Promise.resolve({ success: true, preferences: Object.assign({ theme: 'light', layout: 'vertical', font_size: 14, last_project: null }, prefs) });
    },
    savePreferences(prefs) {
      if (LOGGED_IN) return postJSON(API + 'save_preferences.php', prefs);
      const current = JSON.parse(localStorage.getItem('codeoven_guest_prefs') || '{}');
      localStorage.setItem('codeoven_guest_prefs', JSON.stringify(Object.assign(current, prefs)));
      return Promise.resolve({ success: true });
    },
  };

  // ---------------------------------------------------------------------
  // Editor state
  // ---------------------------------------------------------------------
  let cm = null; // single CodeMirror instance; tabs swap CodeMirror.Doc objects
  let currentProject = null;
  let openTabs = []; // [{fileName, doc, dirty}]
  let activeFile = null;
  let autoRun = true;
  let saveTimer = null;
  let previewTimer = null;
  let isDarkTheme = false;

  function $(sel) { return document.querySelector(sel); }
  function $all(sel) { return document.querySelectorAll(sel); }

  function getTab(fileName) { return openTabs.find((t) => t.fileName === fileName); }

  // ---------------------------------------------------------------------
  // CodeMirror setup
  // ---------------------------------------------------------------------
  function initEditor() {
    cm = CodeMirror(document.getElementById('code-editor'), {
      value: '',
      mode: 'text/plain',
      theme: 'eclipse',
      lineNumbers: true,
      matchBrackets: true,
      autoCloseBrackets: true,
      autoCloseTags: true,
      lineWrapping: true,
      foldGutter: true,
      styleActiveLine: true,
      gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
      extraKeys: {
        'Ctrl-Space': 'autocomplete',
        'Cmd-Space': 'autocomplete',
        'Ctrl-S': function () { saveActiveFile(); return false; },
        'Cmd-S': function () { saveActiveFile(); return false; },
        'Ctrl-Enter': function () { runProjectOrPreview(); return false; },
        'Cmd-Enter': function () { runProjectOrPreview(); return false; },
        'Ctrl-/': 'toggleComment',
        'Cmd-/': 'toggleComment',
        'Ctrl-F': 'findPersistent',
        'Cmd-F': 'findPersistent',
        'Alt-G': 'jumpToLine',
        'Ctrl-Q': function (c) { c.foldCode(c.getCursor()); },
      },
    });

    cm.on('change', function () {
      if (!activeFile) return;
      const tab = getTab(activeFile);
      if (tab) { tab.dirty = true; renderTabs(); }
      clearTimeout(saveTimer);
      saveTimer = setTimeout(saveActiveFile, 800);

      const info = extInfo(activeFile);
      if (info.category === 'web' && autoRun) {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(updateLivePreview, 500);
      }
    });

    setEmptyState(true);
  }

  function setEmptyState(empty) {
    $('#editor-empty-state').style.display = empty ? 'flex' : 'none';
    $('#code-editor').style.display = empty ? 'none' : 'block';
  }

  function hintForMode(mode) {
    const name = typeof mode === 'string' ? mode : mode.name;
    if (name === 'xml' || name === 'htmlmixed') return CodeMirror.hint && CodeMirror.hint.html;
    if (name === 'css') return CodeMirror.hint && CodeMirror.hint.css;
    if (name === 'javascript') return CodeMirror.hint && CodeMirror.hint.javascript;
    if (name === 'sql') return CodeMirror.hint && CodeMirror.hint.sql;
    return CodeMirror.hint && CodeMirror.hint.anyword;
  }

  // ---------------------------------------------------------------------
  // Tabs
  // ---------------------------------------------------------------------
  function renderTabs() {
    const bar = $('#editor-tabs');
    bar.innerHTML = '';
    openTabs.forEach((tab) => {
      const info = extInfo(tab.fileName);
      const el = document.createElement('div');
      el.className = 'tab' + (tab.fileName === activeFile ? ' active' : '');
      el.innerHTML =
        '<span class="tab-icon">' + info.icon + '</span>' +
        '<span class="tab-name">' + escapeHtml(tab.fileName) + '</span>' +
        (tab.dirty ? '<span class="tab-dot" title="Unsaved changes">&bull;</span>' : '') +
        '<span class="tab-close" title="Close">&times;</span>';
      el.addEventListener('click', (e) => {
        if (e.target.classList.contains('tab-close')) { closeTab(tab.fileName); return; }
        switchTab(tab.fileName);
      });
      bar.appendChild(el);
    });
  }

  function openTab(fileName, content) {
    let tab = getTab(fileName);
    if (!tab) {
      const info = extInfo(fileName);
      const doc = new CodeMirror.Doc(content != null ? content : '', info.mode);
      tab = { fileName, doc, dirty: false };
      openTabs.push(tab);
    } else if (content != null) {
      tab.doc.setValue(content);
    }
    switchTab(fileName);
  }

  function switchTab(fileName) {
    const tab = getTab(fileName);
    if (!tab) return;
    activeFile = fileName;
    setEmptyState(false);
    cm.swapDoc(tab.doc);
    cm.setOption('mode', extInfo(fileName).mode);
    cm.refresh();
    cm.focus();
    renderTabs();
    highlightActiveFileInTree();
    updateRunModeUI();
  }

  function closeTab(fileName) {
    const tab = getTab(fileName);
    if (!tab) return;
    if (tab.dirty && !confirm('"' + fileName + '" has unsaved changes. Close anyway?')) return;
    openTabs = openTabs.filter((t) => t.fileName !== fileName);
    if (activeFile === fileName) {
      activeFile = null;
      if (openTabs.length) switchTab(openTabs[openTabs.length - 1].fileName);
      else { setEmptyState(true); renderTabs(); }
    } else {
      renderTabs();
    }
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  // ---------------------------------------------------------------------
  // File tree
  // ---------------------------------------------------------------------
  function renderFileTree(files) {
    const list = $('#file-list');
    list.innerHTML = '';
    if (!files.length) {
      list.innerHTML = '<li class="file-item-empty">No files yet — click + to add one.</li>';
      return;
    }
    files.forEach((f) => {
      const info = extInfo(f.file_name);
      const li = document.createElement('li');
      li.className = 'file-item' + (f.file_name === activeFile ? ' active' : '');
      li.dataset.name = f.file_name;
      li.innerHTML =
        '<span class="file-icon">' + info.icon + '</span>' +
        '<span class="file-name">' + escapeHtml(f.file_name) + '</span>' +
        '<span class="file-actions">' +
        '<button class="mini-btn" data-act="rename" title="Rename"><i class="fas fa-pen" style="pointer-events:none;"></i></button>' +
        '<button class="mini-btn" data-act="delete" title="Delete"><i class="fas fa-trash-can" style="pointer-events:none;"></i></button>' +
        '</span>';
      li.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-act]');
        const act = btn && btn.dataset ? btn.dataset.act : null;
        if (act === 'rename') { renameFilePrompt(f.file_name); return; }
        if (act === 'delete') { deleteFilePrompt(f.file_name); return; }
        loadAndOpenFile(f.file_name);
      });
      list.appendChild(li);
    });
  }

  function highlightActiveFileInTree() {
    $all('.file-item').forEach((li) => li.classList.toggle('active', li.dataset.name === activeFile));
  }

  function refreshFileTree() {
    return Data.getFiles(currentProject).then((resp) => {
      if (resp.success) renderFileTree(resp.files);
      return resp;
    });
  }

  function loadAndOpenFile(fileName) {
    const tab = getTab(fileName);
    if (tab) { switchTab(fileName); return Promise.resolve(); }
    return Data.loadFile(currentProject, fileName).then((resp) => {
      if (!resp.success) { alert(resp.message || 'Failed to load file.'); return; }
      openTab(fileName, resp.file.content);
    });
  }

  // ---------------------------------------------------------------------
  // File / project actions
  // ---------------------------------------------------------------------
  function saveActiveFile() {
    if (!activeFile || !currentProject) return;
    const tab = getTab(activeFile);
    if (!tab) return;
    const content = tab.doc.getValue();
    Data.saveFile(currentProject, activeFile, content).then((resp) => {
      if (resp.success) { tab.dirty = false; renderTabs(); setStatus('Saved ' + activeFile); }
      else { setStatus('Save failed: ' + (resp.message || 'unknown error'), true); }
    }).catch(() => setStatus('Save failed: network error', true));
  }

  const STARTER_SNIPPETS = {
    py: '# Python script\n\ndef main():\n    print("Hello from Python!")\n\nif __name__ == "__main__":\n    main()\n',
    pyw: '# Python script\n\nprint("Hello from Python!")\n',
    php: '<?php\n// PHP script\n\necho "Hello from PHP!\n";\n',
    phtml: '<?php\n// PHP script\n\necho "Hello from PHP!\n";\n',
    cpp: '#include <iostream>\n\nint main() {\n    std::cout << "Hello from C++!" << std::endl;\n    return 0;\n}\n',
    cxx: '#include <iostream>\n\nint main() {\n    std::cout << "Hello from C++!" << std::endl;\n    return 0;\n}\n',
    cc: '#include <iostream>\n\nint main() {\n    std::cout << "Hello from C++!" << std::endl;\n    return 0;\n}\n',
    c: '#include <stdio.h>\n\nint main() {\n    printf("Hello from C!\\n");\n    return 0;\n}\n',
    java: 'public class Main {\n    public static void main(String[] args) {\n        System.out.println("Hello from Java!");\n    }\n}\n',
    html: '<!DOCTYPE html>\n<html lang="en">\n<head>\n  <meta charset="UTF-8">\n  <title>Document</title>\n</head>\n<body>\n  <h1>Hello World</h1>\n</body>\n</html>\n',
    css: '/* CSS Stylesheet */\nbody {\n  font-family: sans-serif;\n}\n',
    js: '// JavaScript\nconsole.log("Hello from JavaScript!");\n',
  };

  function newFilePrompt() {
    const name = prompt('New file name (with extension), e.g. main.py, index.php, main.cpp, Main.java:');
    if (!name) return;
    const trimmed = name.trim();
    const snippet = STARTER_SNIPPETS[extOf(trimmed)] || '';
    Data.newFile(currentProject, trimmed, snippet).then((resp) => {
      if (!resp.success) return alert(resp.message || 'Could not create file.');
      refreshFileTree().then(() => loadAndOpenFile(resp.file_name || trimmed));
    });
  }

  function renameFilePrompt(fileName) {
    const target = fileName || activeFile;
    if (!target) return alert('Select a file first.');
    const newName = prompt('Rename "' + target + '" to:', target);
    if (!newName || newName === target) return;
    Data.renameFile(currentProject, target, newName.trim()).then((resp) => {
      if (!resp.success) return alert(resp.message || 'Rename failed.');
      const tab = getTab(target);
      if (tab) { tab.fileName = resp.file_name || newName.trim(); if (activeFile === target) activeFile = tab.fileName; }
      refreshFileTree();
      renderTabs();
    });
  }

  function deleteFilePrompt(fileName) {
    const target = fileName || activeFile;
    if (!target) return alert('Select a file first.');
    if (!confirm('Delete "' + target + '"? This cannot be undone.')) return;
    Data.deleteFile(currentProject, target).then((resp) => {
      if (!resp.success) return alert(resp.message || 'Delete failed.');
      closeTabSilently(target);
      refreshFileTree();
    });
  }

  function closeTabSilently(fileName) {
    openTabs = openTabs.filter((t) => t.fileName !== fileName);
    if (activeFile === fileName) {
      activeFile = null;
      if (openTabs.length) switchTab(openTabs[openTabs.length - 1].fileName);
      else { setEmptyState(true); renderTabs(); }
    }
  }

  function saveAsPrompt() {
    if (!activeFile) return alert('Open a file first.');
    const name = prompt('Save current content as new file name:', activeFile);
    if (!name || name === activeFile) return;
    const content = cm.getValue();
    Data.newFile(currentProject, name.trim(), content).then((resp) => {
      if (!resp.success) return alert(resp.message || 'Could not save as.');
      refreshFileTree().then(() => openTab(name.trim(), content));
    });
  }

  function newProjectPrompt() {
    const name = prompt('New project name:');
    if (!name) return;
    const template = confirm('Start from an HTML/CSS/JS template? Cancel = blank project.') ? 'web' : 'blank';
    Data.newProject(name.trim(), template).then((resp) => {
      if (!resp.success) return alert(resp.message || 'Could not create project.');
      loadProjectList().then(() => openProject(resp.project_name || name.trim()));
    });
  }

  function downloadProject() {
    if (!LOGGED_IN) return alert('Please log in to download your project as a .zip.');
    if (!currentProject) return;
    window.location = Data.downloadUrl(currentProject);
  }

  function renameProjectPrompt() {
    if (!currentProject) return alert('No active project to rename.');
    const newName = prompt('Rename project "' + currentProject + '" to:', currentProject);
    if (!newName || newName.trim() === currentProject) return;
    Data.renameProject(currentProject, newName.trim()).then((resp) => {
      if (!resp.success) return alert(resp.message || 'Could not rename project.');
      currentProject = resp.project_name || newName.trim();
      loadProjectList().then(() => openProject(currentProject));
    });
  }

  function deleteProjectPrompt() {
    if (!currentProject) return alert('No active project to delete.');
    if (!confirm('Are you sure you want to delete project "' + currentProject + '" and all its files? This cannot be undone.')) return;
    const target = currentProject;
    Data.deleteProject(target).then((resp) => {
      if (!resp.success) return alert(resp.message || 'Could not delete project.');
      currentProject = null;
      loadProjectList().then((projects) => {
        if (projects && projects.length) {
          openProject(projects[0].project_name);
        } else {
          openTabs = [];
          activeFile = null;
          setEmptyState(true);
          renderTabs();
          $('#file-list').innerHTML = '<li class="file-item-empty">No projects found.</li>';
        }
      });
    });
  }

  // ---------------------------------------------------------------------
  // Projects
  // ---------------------------------------------------------------------
  function loadProjectList() {
    return Data.listProjects().then((resp) => {
      const select = $('#project-select');
      select.innerHTML = '';
      (resp.projects || []).forEach((p) => {
        const opt = document.createElement('option');
        opt.value = p.project_name;
        opt.textContent = p.project_name;
        select.appendChild(opt);
      });
      return resp.projects || [];
    });
  }

  function openProject(name) {
    currentProject = name;
    $('#project-select').value = name;
    openTabs = [];
    activeFile = null;
    setEmptyState(true);
    renderTabs();
    hideConsole();
    return refreshFileTree().then((resp) => {
      if (resp.success && resp.files.length) {
        const entry = resp.files.find((f) => f.file_name.toLowerCase() === 'index.html') || resp.files[0];
        loadAndOpenFile(entry.file_name).then(updateLivePreview);
      }
      Data.savePreferences({ last_project: name });
    });
  }

  // ---------------------------------------------------------------------
  // Live preview (HTML/CSS/JS projects)
  // ---------------------------------------------------------------------
  function getContentFor(fileName) {
    const tab = getTab(fileName);
    if (tab) return Promise.resolve(tab.doc.getValue());
    return Data.loadFile(currentProject, fileName).then((r) => (r.success ? r.file.content : ''));
  }

  function updateLivePreview() {
    if (!currentProject) return;
    Data.getFiles(currentProject).then((resp) => {
      if (!resp.success) return;
      const diskFiles = resp.files || [];
      const fileMap = new Map();
      diskFiles.forEach((f) => fileMap.set(f.file_name, f));
      openTabs.forEach((t) => {
        if (!fileMap.has(t.fileName)) {
          fileMap.set(t.fileName, { file_name: t.fileName });
        }
      });
      const fileList = Array.from(fileMap.values());

      const htmlFile = fileList.find((f) => f.file_name.toLowerCase() === 'index.html') || fileList.find((f) => extOf(f.file_name) === 'html' || extOf(f.file_name) === 'htm');
      const frame = document.getElementById('live-preview');
      if (!htmlFile) {
        if (frame) frame.srcdoc = '';
        return; // not a web project; clear preview
      }
      const cssFiles = fileList.filter((f) => extOf(f.file_name) === 'css');
      const jsFiles = fileList.filter((f) => extOf(f.file_name) === 'js' || extOf(f.file_name) === 'mjs');

      Promise.all([
        getContentFor(htmlFile.file_name),
        Promise.all(cssFiles.map((f) => getContentFor(f.file_name))),
        Promise.all(jsFiles.map((f) => getContentFor(f.file_name))),
      ]).then(([html, cssParts, jsParts]) => {
        const frame = document.getElementById('live-preview');
        // Set content via srcdoc (not contentDocument.write): the parent can
        // assign srcdoc on the iframe element itself without ever needing
        // same-origin access into the frame's document. That lets the
        // sandbox stay allow-scripts-only (no allow-same-origin), so
        // arbitrary user JS running in the preview can't reach out and
        // touch the parent page, cookies, or session.
        frame.srcdoc =
          '<!DOCTYPE html><html><head><style>' + cssParts.join('\n') + '</style></head><body>' +
          html +
          '<script>' + jsParts.join('\n;\n') + '<' + '/script>' +
          '</body></html>';
      });
    });
  }

  // ---------------------------------------------------------------------
  // Authentic VS Code Terminal & Interactive Execution Engine
  // ---------------------------------------------------------------------
  let lastExecutedStdout = '';
  let isTerminalMaximized = false;
  let activeInteractiveSession = null; // { prompts: [], promptIndex: 0, inputs: [], fileName: '', code: '' }

  function showTerminal() {
    const p = $('#console-panel');
    if (p) p.classList.remove('hidden');
    updateTerminalSessionTag();
    focusTerminalInput();
  }
  function hideTerminal() {
    const p = $('#console-panel');
    if (p) p.classList.add('hidden');
  }
  function toggleTerminal() {
    const p = $('#console-panel');
    if (p) p.classList.toggle('hidden');
    if (p && !p.classList.contains('hidden')) {
      updateTerminalSessionTag();
      focusTerminalInput();
    }
  }

  function focusTerminalInput() {
    const input = $('#terminal-interactive-input');
    if (input) {
      setTimeout(() => input.focus(), 50);
    }
  }

  function toggleTerminalSize() {
    const p = $('#console-panel');
    const btn = $('#terminal-maximize-btn');
    if (!p) return;
    isTerminalMaximized = !isTerminalMaximized;
    p.classList.toggle('maximized', isTerminalMaximized);
    if (btn) btn.innerHTML = isTerminalMaximized ? '<i class="fas fa-chevron-down"></i>' : '<i class="fas fa-chevron-up"></i>';
  }

  function openConsoleMode() {
    const previewArea = $('#preview-area');
    if (previewArea) previewArea.classList.add('hidden');
    showTerminal();
  }

  function openPreviewMode() {
    hideTerminal();
    const previewArea = $('#preview-area');
    if (previewArea) previewArea.classList.remove('hidden');
    updateLivePreview();
  }

  function updateRunModeUI() {
    if (!activeFile) return;
    const info = extInfo(activeFile);
    const previewArea = $('#preview-area');
    if (info.category === 'runnable') {
      if (previewArea) previewArea.classList.add('dimmed');
    } else {
      if (previewArea) previewArea.classList.remove('dimmed');
    }
    updateTerminalSessionTag();
  }

  function updateTerminalSessionTag() {
    const tag = $('#term-session-tag');
    if (!tag) return;
    if (!activeFile) {
      tag.textContent = '1: bash';
      return;
    }
    const ext = extOf(activeFile);
    if (ext === 'py' || ext === 'pyw') tag.textContent = '1: python';
    else if (ext === 'cpp' || ext === 'cxx' || ext === 'cc') tag.textContent = '1: g++';
    else if (ext === 'c') tag.textContent = '1: gcc';
    else if (ext === 'php' || ext === 'phtml') tag.textContent = '1: php';
    else if (ext === 'java') tag.textContent = '1: java';
    else tag.textContent = '1: bash';
  }

  function setTerminalStatus(text, isBusy, isError) {
    const dot = $('.term-status-dot');
    const txt = $('#term-status-text');
    if (dot) {
      dot.className = 'term-status-dot' + (isBusy ? ' busy' : '');
      if (isError) dot.style.background = '#f87171';
      else dot.style.background = isBusy ? '#fbbf24' : '#4ade80';
    }
    if (txt) txt.textContent = text || 'Ready';
  }

  function clearTerminal() {
    const stream = $('#vscode-terminal-stream');
    if (stream) stream.innerHTML = '';
    resetTerminalPrompt();
    setTerminalStatus('Ready', false, false);
  }

  function appendTerminalLine(text, cls) {
    const stream = $('#vscode-terminal-stream');
    if (!stream) return;
    const div = document.createElement('div');
    div.className = 'term-stream-line ' + (cls || 'term-stdout');
    div.textContent = text;
    stream.appendChild(div);
    scrollTerminalToBottom();
  }

  function scrollTerminalToBottom() {
    const vp = $('#vscode-terminal-viewport');
    if (vp) vp.scrollTop = vp.scrollHeight;
  }

  function resetTerminalPrompt() {
    activeInteractiveSession = null;
    const promptEl = $('#vscode-prompt-text');
    if (promptEl) {
      promptEl.className = 'vscode-prompt-text';
      promptEl.textContent = 'PS C:\\CodeOven>';
    }
    const input = $('#terminal-interactive-input');
    if (input) {
      input.value = '';
      input.placeholder = '';
    }
    focusTerminalInput();
  }

  // Helper for status notifications across dashboard
  function setStatus(msg, isError) {
    setTerminalStatus(msg, false, !!isError);
  }

  // --- Scan code for input-preceding prompts ONLY (not all print statements) ---
  function scanExpectedPrompts(code, filename) {
    if (!code) return [];
    const ext = extOf(filename || '');
    const prompts = [];

    if (ext === 'cpp' || ext === 'cxx' || ext === 'cc' || ext === 'c') {
      const lines = code.split('\n');
      for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim();
        if (/\b(cin\s*>>|scanf\s*\(|getline\s*\(\s*cin|gets\s*\()/.test(line)) {
          let foundPrompt = null;
          // Check same line first (e.g. cout << "Name: "; cin >> name;)
          const sameLineMatch = line.match(/(?:cout\s*<<\s*["']([^"']+)["']|printf\s*\(\s*["']([^"']+)["'])/);
          if (sameLineMatch) {
            foundPrompt = sameLineMatch[1] || sameLineMatch[2];
          }
          // If not found on same line, look backwards up to 4 lines
          if (!foundPrompt) {
            for (let j = i - 1; j >= Math.max(0, i - 4); j--) {
              const prevLine = lines[j].trim();
              if (/\b(cin\s*>>|scanf\s*\(|getline\s*\(\s*cin)/.test(prevLine)) break;
              const coutMatch = prevLine.match(/(?:cout\s*<<\s*["']([^"']+)["']|printf\s*\(\s*["']([^"']+)["'])/);
              if (coutMatch) {
                foundPrompt = coutMatch[1] || coutMatch[2];
                break;
              }
            }
          }
          prompts.push(foundPrompt || 'Input: ');
        }
      }
    } else if (ext === 'py' || ext === 'pyw') {
      // Python input() already contains the prompt string
      const inputRegex = /input\s*\(\s*(?:["']([^"']*)["'])?\s*\)/g;
      let m;
      let count = 0;
      while ((m = inputRegex.exec(code)) !== null) {
        prompts.push(m[1] || 'Input: ');
        count++;
      }
      if (count === 0 && /\bsys\.stdin\.(?:readline|read)/.test(code)) {
        prompts.push('Input: ');
      }
    } else if (ext === 'java') {
      const lines = code.split('\n');
      for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim();
        if (/\b(?:sc|scanner|in|reader)\s*\.\s*(?:next\w*|readLine)\s*\(|\.readLine\s*\(/.test(line)) {
          let foundPrompt = null;
          const sameLineMatch = line.match(/System\.out\.print(?:ln)?\s*\(\s*["']([^"']*)["']\s*\)/);
          if (sameLineMatch) foundPrompt = sameLineMatch[1];
          if (!foundPrompt) {
            for (let j = i - 1; j >= Math.max(0, i - 4); j--) {
              const prevLine = lines[j].trim();
              if (/\.next\w*\s*\(/.test(prevLine)) break;
              const printMatch = prevLine.match(/System\.out\.print(?:ln)?\s*\(\s*["']([^"']*)["']\s*\)/);
              if (printMatch) {
                foundPrompt = printMatch[1];
                break;
              }
            }
          }
          prompts.push(foundPrompt || 'Input: ');
        }
      }
    } else if (ext === 'php' || ext === 'phtml') {
      const lines = code.split('\n');
      for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim();
        const readlineMatch = line.match(/readline\s*\(\s*["']([^"']*)["']\s*\)/);
        if (readlineMatch) {
          prompts.push(readlineMatch[1] || 'Input: ');
          continue;
        }
        if (/\b(?:fgets\s*\(\s*STDIN|stream_get_line|fscanf\s*\(\s*STDIN)/.test(line)) {
          let foundPrompt = null;
          const sameLineMatch = line.match(/(?:echo|print)\s*\(?["']([^"']*)["']/);
          if (sameLineMatch) foundPrompt = sameLineMatch[1];
          if (!foundPrompt) {
            for (let j = i - 1; j >= Math.max(0, i - 4); j--) {
              const prevLine = lines[j].trim();
              if (/\bfgets\s*\(/.test(prevLine)) break;
              const echoMatch = prevLine.match(/(?:echo|print)\s*\(?["']([^"']*)["']/);
              if (echoMatch) {
                foundPrompt = echoMatch[1];
                break;
              }
            }
          }
          prompts.push(foundPrompt || 'Input: ');
        }
      }
    }

    return prompts;
  }

  // --- Run / Execute in VS Code Terminal ---
  function runProjectOrPreview() {
    if (!activeFile) return;
    const info = extInfo(activeFile);
    saveActiveFile();
    if (info.category === 'runnable') {
      startTerminalExecution(activeFile);
    } else {
      updateLivePreview();
    }
  }

  function startTerminalExecution(fileName) {
    if (!currentProject || !fileName) return;
    showTerminal();

    const tab = getTab(fileName);
    const code = tab ? tab.doc.getValue() : (cm ? cm.getValue() : '');
    const ext = extOf(fileName);

    // Formulate VS Code shell command line echo
    let cmdStr = '';
    if (ext === 'cpp' || ext === 'cxx' || ext === 'cc') {
      cmdStr = 'PS C:\\CodeOven> g++ ' + fileName + ' -o ' + fileName.replace(/\.[^.]+$/, '.exe') + ' && .\\' + fileName.replace(/\.[^.]+$/, '.exe');
    } else if (ext === 'c') {
      cmdStr = 'PS C:\\CodeOven> gcc ' + fileName + ' -o ' + fileName.replace(/\.[^.]+$/, '.exe') + ' && .\\' + fileName.replace(/\.[^.]+$/, '.exe');
    } else if (ext === 'py' || ext === 'pyw') {
      cmdStr = 'PS C:\\CodeOven> python ' + fileName;
    } else if (ext === 'php' || ext === 'phtml') {
      cmdStr = 'PS C:\\CodeOven> php ' + fileName;
    } else if (ext === 'java') {
      cmdStr = 'PS C:\\CodeOven> javac ' + fileName + ' && java ' + fileName.replace(/\.java$/, '');
    } else {
      cmdStr = 'PS C:\\CodeOven> run ' + fileName;
    }

    appendTerminalLine(cmdStr, 'term-cmd-echo');

    // Check for interactive input statements
    const prompts = scanExpectedPrompts(code, fileName);

    if (prompts.length > 0) {
      activeInteractiveSession = {
        prompts: prompts,
        promptIndex: 0,
        inputs: [],
        fileName: fileName,
        code: code,
      };

      setTerminalStatus('Waiting for input...', true, false);
      advanceInteractivePrompt();
      return;
    }

    // Direct execution (non-interactive) - hide prompt line during execution
    hideTerminalPromptLine();
    executeBackendRun(fileName, '', null);
  }

  function hideTerminalPromptLine() {
    const promptLine = $('#vscode-terminal-line');
    if (promptLine) promptLine.style.display = 'none';
  }

  function showTerminalPromptLine() {
    const promptLine = $('#vscode-terminal-line');
    if (promptLine) promptLine.style.display = '';
  }

  function advanceInteractivePrompt() {
    if (!activeInteractiveSession) return;
    const session = activeInteractiveSession;
    if (session.promptIndex < session.prompts.length) {
      const currentPromptText = session.prompts[session.promptIndex];
      const promptEl = $('#vscode-prompt-text');
      if (promptEl) {
        promptEl.className = 'vscode-prompt-text input-active';
        promptEl.textContent = currentPromptText;
      }
      const input = $('#terminal-interactive-input');
      if (input) {
        input.value = '';
        input.placeholder = '';
      }
      showTerminalPromptLine();
      focusTerminalInput();
      scrollTerminalToBottom();
    } else {
      // All inputs collected — hide prompt line, then execute
      hideTerminalPromptLine();
      const combinedStdin = session.inputs.join('\n') + '\n';
      const fileToRun = session.fileName;
      const promptsUsed = session.prompts.slice(); // copy
      activeInteractiveSession = null;
      setTerminalStatus('Running...', true, false);
      executeBackendRun(fileToRun, combinedStdin, promptsUsed);
    }
  }

  let terminalHistory = [];
  let terminalHistoryIndex = -1;

  function handleTerminalInputSubmit(enteredText) {
    if (activeInteractiveSession) {
      // We are in interactive input mode for running program
      const session = activeInteractiveSession;
      const promptText = session.prompts[session.promptIndex] || '';
      appendTerminalLine(promptText + enteredText, 'term-stdout');
      session.inputs.push(enteredText);
      session.promptIndex++;
      advanceInteractivePrompt();
      return;
    }

    // Standard CLI command entered at PS C:\CodeOven>
    appendTerminalLine('PS C:\\CodeOven> ' + enteredText, 'term-cmd-echo');
    const cmd = enteredText.trim();
    const input = $('#terminal-interactive-input');
    if (input) input.value = '';

    if (!cmd) return;

    if (!terminalHistory.length || terminalHistory[terminalHistory.length - 1] !== cmd) {
      terminalHistory.push(cmd);
    }
    terminalHistoryIndex = terminalHistory.length;

    const lower = cmd.toLowerCase();
    if (lower === 'cls' || lower === 'clear') {
      clearTerminal();
    } else if (lower === 'run') {
      runProjectOrPreview();
    } else if (lower === 'help') {
      appendTerminalLine('CodeOven Terminal - Built-in Commands:', 'term-stdout');
      appendTerminalLine('  run           - Compile & execute the active file', 'term-stdout');
      appendTerminalLine('  cls / clear   - Clear the terminal screen (or Ctrl+L)', 'term-stdout');
      appendTerminalLine('  help          - Display this help message', 'term-stdout');
    } else {
      // Try running active file or execute command
      runProjectOrPreview();
    }
  }

  function executeBackendRun(fileName, stdin, promptsUsed) {
    setTerminalStatus('Running...', true, false);

    Data.run(currentProject, fileName, stdin).then((resp) => {
      if (!resp.success) {
        appendTerminalLine(resp.message || 'Execution failed.', 'term-stderr');
        setTerminalStatus('Error', false, true);
        showTerminalPromptLine();
        resetTerminalPrompt();
        return;
      }

      lastExecutedStdout = resp.stdout || '';

      if (resp.stage === 'compile' && resp.stderr) {
        appendTerminalLine('Compilation Error:\n' + resp.stderr, 'term-stderr');
        setTerminalStatus('Compilation Failed', false, true);
      } else {
        if (resp.stdout) {
          let cleanStdout = resp.stdout;
          // Strip ONLY the input-preceding prompt strings (not all print output)
          if (promptsUsed && promptsUsed.length) {
            promptsUsed.forEach((p) => {
              if (p && p !== 'Input: ') {
                const idx = cleanStdout.indexOf(p);
                if (idx !== -1) {
                  const before = cleanStdout.substring(0, idx);
                  const after = cleanStdout.substring(idx + p.length).replace(/^[\r\n]+/, '');
                  cleanStdout = before + after;
                }
              }
            });
          }
          cleanStdout = cleanStdout.replace(/^\s*[\r\n]+/, '').replace(/[\r\n]+\s*$/, '');
          if (cleanStdout) {
            const outputLines = cleanStdout.split(/\r?\n/);
            outputLines.forEach((line) => {
              appendTerminalLine(line, 'term-stdout');
            });
          }
        }
        if (resp.stderr) {
          appendTerminalLine(resp.stderr, 'term-stderr');
        }
        if (!resp.stdout && !resp.stderr) {
          appendTerminalLine('(program exited with no output)', 'term-stdout');
        }

        const statusMsg = resp.timed_out
          ? '[Process timed out after ' + resp.duration_ms + 'ms]'
          : '[Process completed in ' + resp.duration_ms + 'ms with exit code ' + resp.exit_code + ']';
        appendTerminalLine(statusMsg, 'term-system-line');
        setTerminalStatus('Exit ' + resp.exit_code, false, resp.exit_code !== 0);
      }

      showTerminalPromptLine();
      resetTerminalPrompt();
    }).catch((err) => {
      appendTerminalLine('Network error while running code: ' + (err.message || ''), 'term-stderr');
      setTerminalStatus('Network Error', false, true);
      showTerminalPromptLine();
      resetTerminalPrompt();
    });
  }

  // ---------------------------------------------------------------------
  // Edit / View menu commands
  // ---------------------------------------------------------------------
  function toggleWordWrap() { cm.setOption('lineWrapping', !cm.getOption('lineWrapping')); }
  function zoom(delta) {
    const wrapper = cm.getWrapperElement();
    const current = parseInt(window.getComputedStyle(wrapper).fontSize, 10) || 14;
    const newSize = Math.max(9, Math.min(32, current + delta));
    wrapper.style.fontSize = newSize + 'px';
    cm.refresh();
    Data.savePreferences({ font_size: newSize });
  }
  function toggleFoldAtCursor() { cm.foldCode(cm.getCursor()); }
  function gotoLine() { cm.execCommand('jumpToLine'); }

  function toggleTheme() {
    isDarkTheme = !isDarkTheme;
    const theme = isDarkTheme ? 'dracula' : 'eclipse';
    cm.setOption('theme', theme);
    document.body.setAttribute('data-theme', isDarkTheme ? 'dark' : 'light');
    $('#theme-toggle').innerHTML = isDarkTheme ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    Data.savePreferences({ theme: isDarkTheme ? 'dark' : 'light' });
  }

  function toggleLayout() {
    const el = $('#editor-preview-container');
    const horizontal = el.classList.toggle('horizontal');
    el.classList.toggle('vertical', !horizontal);
    Data.savePreferences({ layout: horizontal ? 'horizontal' : 'vertical' });
  }

  // ---------------------------------------------------------------------
  // Menu wiring
  // ---------------------------------------------------------------------
  const ACTIONS = {
    'new-project': newProjectPrompt,
    'rename-project': renameProjectPrompt,
    'delete-project': deleteProjectPrompt,
    'new-file': newFilePrompt,
    save: saveActiveFile,
    'save-as': saveAsPrompt,
    'rename-file': () => renameFilePrompt(),
    'delete-file': () => deleteFilePrompt(),
    download: downloadProject,
    undo: () => cm.undo(),
    redo: () => cm.redo(),
    cut: () => { const t = cm.getSelection(); if (t) { navigator.clipboard.writeText(t); cm.replaceSelection(''); } },
    copy: () => { const t = cm.getSelection(); if (t) navigator.clipboard.writeText(t); },
    paste: () => navigator.clipboard.readText().then((t) => { if (t) cm.replaceSelection(t); }),
    'select-all': () => cm.execCommand('selectAll'),
    find: () => cm.execCommand('findPersistent'),
    replace: () => cm.execCommand('replace'),
    'goto-line': gotoLine,
    'word-wrap': toggleWordWrap,
    'toggle-layout': toggleLayout,
    'zoom-in': () => zoom(2),
    'zoom-out': () => zoom(-2),
    'toggle-fold': toggleFoldAtCursor,
    'toggle-console': toggleTerminal,
    'open-console': openConsoleMode,
    'open-preview': openPreviewMode,
    run: runProjectOrPreview,
    'toggle-autorun': () => { autoRun = !autoRun; alert('Auto-Run (live preview on change) is now ' + (autoRun ? 'ON' : 'OFF')); },
    'clear-console': clearTerminal,
  };

  function bindMenu() {
    $all('.submenu-item[data-action]').forEach((item) => {
      item.addEventListener('click', () => {
        const action = ACTIONS[item.dataset.action];
        if (action) action();
      });
    });
  }

  function bindShortcuts() {
    window.addEventListener('keydown', (e) => {
      const mod = e.ctrlKey || e.metaKey;
      if (mod && e.key.toLowerCase() === 's') {
        e.preventDefault();
        saveActiveFile();
      } else if (mod && e.key === 'Enter') {
        e.preventDefault();
        runProjectOrPreview();
      } else if (mod && (e.key === '`' || e.code === 'Backquote')) {
        e.preventDefault();
        toggleTerminal();
      }
    });
  }

  // ---------------------------------------------------------------------
  // Boot
  // ---------------------------------------------------------------------
  function showFatalError(message) {
    let banner = document.getElementById('codeoven-fatal-banner');
    if (!banner) {
      banner = document.createElement('div');
      banner.id = 'codeoven-fatal-banner';
      banner.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:99999;background:#c0392b;color:#fff;' +
        'padding:10px 16px;font:14px/1.4 sans-serif;text-align:center;box-shadow:0 2px 6px rgba(0,0,0,.3);';
      document.body.prepend(banner);
    }
    banner.textContent = message;
    console.error('[CodeOven]', message);
  }

  document.addEventListener('DOMContentLoaded', function () {
    initEditor();
    bindMenu();
    bindShortcuts();

    $('#new-file-btn').addEventListener('click', newFilePrompt);
    $('#run-button').addEventListener('click', runProjectOrPreview);
    const previewBtn = $('#preview-button');
    if (previewBtn) previewBtn.addEventListener('click', openPreviewMode);
    $('#theme-toggle').addEventListener('click', toggleTheme);
    $('#project-select').addEventListener('change', (e) => openProject(e.target.value));

    // VS Code Terminal Controls
    const termClearBtn = $('#terminal-clear-btn');
    if (termClearBtn) termClearBtn.addEventListener('click', clearTerminal);

    const termCloseBtn = $('#console-close-btn');
    if (termCloseBtn) termCloseBtn.addEventListener('click', hideTerminal);

    const termMaxBtn = $('#terminal-maximize-btn');
    if (termMaxBtn) termMaxBtn.addEventListener('click', toggleTerminalSize);

    const termCopyBtn = $('#terminal-copy-btn');
    if (termCopyBtn) {
      termCopyBtn.addEventListener('click', () => {
        const stream = $('#vscode-terminal-stream');
        const text = stream ? stream.innerText : '';
        if (text) {
          navigator.clipboard.writeText(text).then(() => {
            termCopyBtn.innerHTML = '<i class="fas fa-check" style="color:#4ade80;"></i>';
            setTimeout(() => { termCopyBtn.innerHTML = '<i class="fas fa-copy"></i>'; }, 1500);
          });
        }
      });
    }

    // Viewport click focuses terminal input
    const termViewport = $('#vscode-terminal-viewport');
    if (termViewport) {
      termViewport.addEventListener('click', (e) => {
        if (!window.getSelection().toString()) {
          focusTerminalInput();
        }
      });
    }

    // Interactive Terminal Input
    const termInput = $('#terminal-interactive-input');
    if (termInput) {
      termInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          const val = termInput.value;
          termInput.value = '';
          handleTerminalInputSubmit(val);
        } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'l') {
          e.preventDefault();
          clearTerminal();
        } else if (e.key === 'ArrowUp') {
          if (!activeInteractiveSession && terminalHistory.length > 0) {
            e.preventDefault();
            if (terminalHistoryIndex > 0) {
              terminalHistoryIndex--;
            }
            termInput.value = terminalHistory[terminalHistoryIndex] || '';
          }
        } else if (e.key === 'ArrowDown') {
          if (!activeInteractiveSession) {
            e.preventDefault();
            if (terminalHistoryIndex < terminalHistory.length - 1) {
              terminalHistoryIndex++;
              termInput.value = terminalHistory[terminalHistoryIndex] || '';
            } else {
              terminalHistoryIndex = terminalHistory.length;
              termInput.value = '';
            }
          }
        }
      });
    }

    Data.loadPreferences().then((resp) => {
      if (!resp.success) showFatalError(resp.message || 'Could not load preferences.');
      const prefs = (resp && resp.preferences) || {};
      if (prefs.theme === 'dark') {
        if (!isDarkTheme) toggleTheme();
      } else {
        document.body.setAttribute('data-theme', 'light');
        cm.setOption('theme', 'eclipse');
        $('#theme-toggle').innerHTML = '<i class="fas fa-moon"></i>';
      }
      if (prefs.layout === 'horizontal') toggleLayout();
      if (prefs.font_size) {
        const wrapper = cm.getWrapperElement();
        wrapper.style.fontSize = prefs.font_size + 'px';
        cm.refresh();
      }

      loadProjectList().then((projects) => {
        if (!projects.length) {
          showFatalError(
            'Could not load any projects. This usually means the database isn\'t connected — ' +
            'check includes/config.php and make sure editor_db.sql has been imported, ' +
            'then reload this page. (Open the browser console for details.)'
          );
          return;
        }
        const wanted = prefs.last_project && projects.find((p) => p.project_name === prefs.last_project);
        const target = wanted ? wanted.project_name : (projects[0] && projects[0].project_name);
        if (target) openProject(target);
      });
    }).catch((e) => showFatalError('Startup error: ' + e.message));

    if (!LOGGED_IN) {
      setStatus('Guest mode: work is saved locally in this browser. Log in to sync to your account.');
    }
  });
})();
