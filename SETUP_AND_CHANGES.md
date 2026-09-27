# CodeOven — Setup & What Changed

## Quick setup (XAMPP / WAMP)

1. Copy this whole folder into your `htdocs` (XAMPP) or `www` (WAMP) directory.
2. Start Apache and MySQL from the XAMPP/WAMP control panel.
3. In phpMyAdmin, import **`editor_db.sql`** (this creates a fresh `editor_db`
   database — it will drop and recreate the old tables, so back up first if
   you have existing data you care about).
4. Open `includes/config.php` and check the `DB_*` constants match your
   MySQL setup (defaults: host `127.0.0.1`, user `root`, empty password —
   the standard XAMPP defaults).
5. Make sure these PHP extensions are enabled in `php.ini`: `pdo_mysql`,
   `zip` (for the "Download Project" button), `mbstring`. Restart Apache
   after editing `php.ini`.
6. For code execution, make sure these are installed and on your system
   `PATH`: `python3` (or `python` — see `includes/config.php`), a C++
   compiler (`g++`, e.g. via MinGW on Windows), and a JDK (`javac`/`java`).
   Test each with `--version` in a terminal to confirm they resolve first.
7. Visit `http://localhost/<folder>/index.html`.

## Security note on Run

`api/run.php` compiles/runs code directly on your machine with a wall-clock
timeout but no CPU/memory/network sandboxing. That's fine for local,
single-user use. Don't deploy this as-is to a public server — see the
comment block at the top of `includes/config.php` for what a safe
production version would need (containerized execution, e.g. Docker or a
hosted sandbox like Judge0).

## What changed in this pass

**Data model:** the old fixed "one HTML + one CSS + one JS per file_name"
storage was replaced with a generic `tbl_projects` / `tbl_files` model, so a
project can now contain any number of files in any supported language.

**Backend (`includes/`, `api/`):** rewritten around this new model —
`get_projects`, `new_project`, `rename_project`, `delete_project`,
`get_files`, `load_file`, `new_file`, `save_file`, `rename_file`,
`delete_file`, `download` (zips the whole project), `run` (new — compiles
and executes Python/C++/Java with a timeout), `load_preferences` /
`save_preferences`.

**Front end (`php/`, `js/dashboard.js`, `css/dashboard.css`):** the editor
now supports real multiple open tabs (one CodeMirror instance, one
`CodeMirror.Doc` per tab, swapped in on click — this keeps independent
undo history per file), a project switcher, a file tree with rename/delete,
syntax highlighting + folding + bracket/tag matching + autocomplete for
HTML/CSS/JS/JSON/PHP/Python/C++/Java/XML/SQL/Markdown/Shell, Find/Replace/
Go to Line, and an integrated console panel (stdout/stderr, exit code,
timing, stdin input box, Clear button) for Run.

**Removed:** `js/dashboard1.js` and `js/api_integration.js` — these were
dead/duplicate code. `dashboard1.js` wasn't loaded by any page.
`api_integration.js` contained a second, conflicting client-only
localStorage file system that silently overrode the server API calls in
`dashboard.js`, which is why saving/loading was unreliable before. Their
useful ideas (offline/guest support) were folded into the new
`js/dashboard.js` as an explicit, non-conflicting "guest mode" instead:
if you're not logged in, the editor still fully works, but files are kept
in the browser's `localStorage` rather than the database, and Run /
Download require logging in (execution and zipping happen server-side).

**Bug fixes:** a stray literal `\n` was being printed into the page inside
a `<script>` tag in the old `dashboard.php`; the unused, silently-failing
mysqli connection in `includes/db.php` was removed (everything already
used PDO); `api/download.php` and the C++ run path no longer trigger fatal
errors/linker errors in common situations (missing `zip` extension;
multiple independent `.cpp` files with their own `main()` in one project).
`tbl_users.otp_code`/`otp_expiry` (used by the existing forgot-password/OTP
flow) were preserved in the new schema.

## Known limitations / good next steps

- C++/Java execution assumes standard library only — no package managers.
- Java: the entry file's class name is auto-detected from `public class X`;
  make sure it matches the filename, as `javac` requires.
- No per-user resource quotas beyond the timeout — fine for solo/local use.
- The file tree currently shows a single flat list (no subfolders).
