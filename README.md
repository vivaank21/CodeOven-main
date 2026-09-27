# CodeOven

CodeOven is a browser-based, multi-language online IDE built with PHP, MySQL, and CodeMirror 5. It lets users write, organize, run, and download multi-file coding projects entirely from the browser — no local setup required beyond a standard PHP/MySQL stack.

---

## ✨ Features

- **Multi-file, multi-project workspace** — create any number of projects, each holding any number of files in any supported language (not limited to a fixed HTML/CSS/JS trio).
- **Full-featured code editor** (CodeMirror 5) — syntax highlighting, code folding, bracket/tag matching, autocomplete, Find/Replace, and Go-to-Line for HTML, CSS, JavaScript, JSON, PHP, Python, C++, Java, XML, SQL, Markdown, and Shell.
- **Real multi-tab editing** — one CodeMirror instance with a separate `Doc` per open tab, so each file keeps its own independent undo history.
- **Live preview** for HTML/CSS/JS projects.
- **Server-side Run** — compiles and executes Python, C++, and Java with a wall-clock timeout, and shows stdout/stderr/exit code/timing in an integrated console panel with an stdin input box.
- **Download Project** — zips the entire project for download in one click.
- **File & project management** — create, rename, and delete files/projects from a file-tree sidebar.
- **User accounts** — signup, login, logout, and forgot-password/OTP verification.
- **Guest mode** — the editor works fully without an account, using the browser's `localStorage`; only Run and Download require logging in.
- **Per-user preferences** — theme (light/dark), layout (horizontal/vertical), word wrap, line numbers, auto-save, and font size, persisted server-side.

## 🛠️ Tech Stack

| Layer          | Technology                                   |
|----------------|-----------------------------------------------|
| Frontend       | HTML, CSS, JavaScript, CodeMirror 5           |
| Backend        | PHP (PDO), JSON REST-style API endpoints      |
| Database       | MySQL / MariaDB                                |
| Code execution | `python3`, `g++`, `javac`/`java` via `proc_open` |

## 📁 Project Structure

```
CodeOven/
├── api/                 # JSON API endpoints (projects, files, run, preferences)
├── includes/            # Shared PHP helpers (auth, db, config, language map)
├── php/                 # Page templates (login, signup, dashboard, editor, etc.)
├── js/                  # Frontend logic (dashboard, login, signup, index)
├── css/                 # Stylesheets
├── codemirror/          # Vendored CodeMirror 5 library
├── editor_db.sql        # Database schema + seed data
├── index.html           # Landing page
└── SETUP_AND_CHANGES.md # Detailed setup notes and changelog
```

## 🚀 Getting Started (XAMPP / WAMP)

1. Copy this project folder into your `htdocs` (XAMPP) or `www` (WAMP) directory.
2. Start **Apache** and **MySQL** from the control panel.
3. In phpMyAdmin, import **`editor_db.sql`** to create the `editor_db` database and tables.
4. Open `includes/config.php` and confirm the `DB_*` constants match your MySQL setup.
5. Enable these PHP extensions in `php.ini`: `pdo_mysql`, `zip`, `mbstring`. Restart Apache.
6. For the Run feature, make sure `python3` (or `python`), a C++ compiler (`g++`), and a JDK (`javac`/`java`) are installed and on your system `PATH`.
7. Visit `http://localhost/<folder>/index.html`.

> ⚠️ **Security note:** `api/run.php` executes code directly on the host machine with a timeout but no CPU/memory/network sandboxing. This is intended for **local, single-user use only** — do not deploy it publicly without adding a real sandbox (e.g., Docker with `--network=none`, or a hosted service like Judge0). See `includes/config.php` for details.

For the full setup guide and changelog, see [`SETUP_AND_CHANGES.md`](./SETUP_AND_CHANGES.md).

## 🗄️ Database Schema

| Table              | Purpose                                              |
|--------------------|-------------------------------------------------------|
| `tbl_users`        | User accounts, password hashes, OTP for password reset |
| `tbl_projects`     | One row per project, owned by a user                  |
| `tbl_files`        | One row per file, linked to a project and user         |
| `tbl_preferences`  | Per-user editor/theme preferences                       |

## 📄 License

This project vendors [CodeMirror 5](https://codemirror.net/5/) (MIT License). See `codemirror/LICENSE` for details. Add your own license terms for the CodeOven application code here.

---

