<?php
global $conn;
require_once '../config.php';


// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

$query = "SELECT * FROM users WHERE username = '" . $_SESSION['username'] . "'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$id = $row['id'];


$username = $_SESSION['username'];
$isNewTab = true;
$tab = [
    'id' => null,
    'song_name' => '',
    'artist_name' => '',
    'difficulty' => '',
    'tuning' => 'Standard',
    'capo' => '',
    'file_path' => '',
    'author_id' => $row['id'],
];
$tabContent = '';

// Check if editing existing tab
if (isset($_GET['id'])) {
    $tabId = (int)$_GET['id'];
    $tabQuery = mysqli_query($conn, "SELECT * FROM tabs WHERE id = $tabId");

    if (mysqli_num_rows($tabQuery) > 0) {
        $tab = mysqli_fetch_assoc($tabQuery);
        $isNewTab = false;

        // Check if user has permission to edit
        if ($tab['author_id'] != $id && $_SESSION['role_id'] != 1) {
            header("Location: tabs.php?error=permission-denied");
            exit;
        }

        // Get tab content from file
        $tabFilePath = "../uploads/tabs/{$tab['file_path']}";
        if (file_exists($tabFilePath)) {
            $tabContent = file_get_contents($tabFilePath);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $songName = mysqli_real_escape_string($conn, $_POST['song_name']);
    $artistName = mysqli_real_escape_string($conn, $_POST['artist_name']);
    $difficulty = mysqli_real_escape_string($conn, $_POST['difficulty']);
    $tuning = mysqli_real_escape_string($conn, $_POST['tuning']);
    $capo = mysqli_real_escape_string($conn, $_POST['capo']);
    $tabContent = $_POST['tab_content'];

    // Sanitize tab content (allow only safe characters while preserving formatting)
    $tabContent = htmlspecialchars_decode(htmlspecialchars($tabContent, ENT_QUOTES, 'UTF-8'));

    $uploadsDir = "../uploads/tabs/";


    // Generate file name
    $fileName = $isNewTab ?
        sanitizeFileName($songName . '_' . $artistName . '_' . time() . '.txt') :
        $tab['file_path'];

    // Save tab content to file
    file_put_contents($uploadsDir . $fileName, $tabContent);

    if ($isNewTab) {
        // Insert new tab
        $query = "INSERT INTO tabs (song_name, artist_name, difficulty, tuning, capo, file_path, author_id) 
                  VALUES ('$songName', '$artistName', '$difficulty', '$tuning', '$capo', '$fileName', '$id')";
        mysqli_query($conn, $query);
        $tabId = mysqli_insert_id($conn);
        header("Location: view_tab.php?id=$tabId&success=created");
    } else {
        // Update existing tab
        $query = "UPDATE tabs SET 
                  song_name = '$songName', 
                  artist_name = '$artistName', 
                  difficulty = '$difficulty', 
                  tuning = '$tuning', 
                  capo = '$capo' 
                  WHERE id = {$tab['id']}";
        mysqli_query($conn, $query);
        header("Location: view_tab.php?id={$tab['id']}&success=updated");
    }
    exit;
}

// Helper function to sanitize file names
function sanitizeFileName($name) {
    // Replace spaces with underscores
    $name = str_replace(' ', '_', $name);
    // Remove any character that is not alphanumeric, underscore, dash or dot
    $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $name);
    // Ensure the name is not too long
    return substr($name, 0, 100);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isNewTab ? 'Create New Tab' : 'Edit Tab: ' . htmlspecialchars($tab['song_name']) ?> - Guitar Master</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-brown': '#5c3d2e',
                        'dark-brown': '#3e2f1c',
                        'light-brown': '#8b6f47',
                        'warm-orange': '#f97316',
                        'soft-orange': '#fed7aa'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            padding-top: 76px;
            background-color: #fef7ed;
        }

        .sidebar {
            min-height: calc(100vh - 76px);
            transition: all 0.3s;
            background-color: #5c3d2e;
            width: 250px;
            position: fixed;
            top: 76px;
            left: 0;
            bottom: 0;
            z-index: 100;
        }

        .sidebar.collapsed {
            margin-left: -250px;
        }

        .main-content {
            transition: all 0.3s;
            margin-left: 250px;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        .nav-link-custom {
            color: #fed7aa !important;
            transition: all 0.3s;
        }

        .nav-link-custom:hover {
            background-color: #3e2f1c !important;
            color: #ffffff !important;
        }

        .nav-link-custom.active {
            background-color: #f97316 !important;
            color: #ffffff !important;
        }

        /* Tab editor styles */
        #tab_content {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.2;
            white-space: pre;
            overflow-x: auto;
            min-height: 400px;
            resize: vertical;
        }

        .tab-editor-toolbar {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            border-bottom: none;
            border-radius: 0.25rem 0.25rem 0 0;
            padding: 10px;
        }

        .tab-editor-toolbar .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .tab-editor-toolbar .btn-group {
            margin-right: 10px;
        }

        .tab-preview {
            font-family: 'Courier New', monospace;
            white-space: pre;
            overflow-x: auto;
            font-size: 14px;
            line-height: 1.2;
            padding: 20px;
            background-color: white;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            min-height: 400px;
        }

        .tab-section {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
        }

        .tab-section.tab {
            background-color: #f8f9fa;
            border-left: 4px solid #5c3d2e;
        }

        .tab-section.chord {
            background-color: #e8f4f8;
            border-left: 4px solid #0d6efd;
        }

        .tab-section.lyrics {
            background-color: #f8f0e8;
            border-left: 4px solid #f97316;
            font-family: Arial, sans-serif;
            white-space: pre-wrap;
        }

        .tab-section.comment {
            background-color: #f0f8e8;
            border-left: 4px solid #198754;
            white-space: pre-wrap;
        }

        .btn-primary {
            background-color: #5c3d2e;
            border-color: #5c3d2e;
        }

        .btn-primary:hover {
            background-color: #3e2f1c;
            border-color: #3e2f1c;
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-orange {
            background-color: #f97316;
            border-color: #f97316;
            color: white;
        }

        .btn-orange:hover {
            background-color: #ea580c;
            border-color: #ea580c;
            color: white;
        }

        .form-control:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 0.2rem rgba(249, 115, 22, 0.25);
        }

        .nav-tabs .nav-link.active {
            color: #5c3d2e;
            border-color: #ced4da #ced4da #fff;
            font-weight: bold;
        }

        .nav-tabs .nav-link {
            color: #6c757d;
        }

        .nav-tabs .nav-link:hover {
            color: #5c3d2e;
            border-color: #e9ecef #e9ecef #ced4da;
        }
    </style>
</head>
<body>

<div id="header-container"> <?php include "header.php" ?>></div>


<div class="d-flex">
    <?php include "sidebar.php" ?>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-4" id="mainContent">
        <!-- Sidebar Toggle Button -->
        <button class="btn sidebar-toggle mb-4" style="background-color: #5c3d2e; color: white; border: none;" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" style="color: #5c3d2e;">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="tabs.php" style="color: #5c3d2e;">Tabs</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?= $isNewTab ? 'Create New Tab' : 'Edit: ' . htmlspecialchars($tab['song_name']) ?>
                </li>
            </ol>
        </nav>

        <!-- Tab Form -->
        <form method="post" id="tabForm">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0 fw-bold" style="color: #5c3d2e;">
                        <i class="fas fa-<?= $isNewTab ? 'plus' : 'edit' ?> me-2"></i>
                        <?= $isNewTab ? 'Create New Tab' : 'Edit Tab' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="song_name" class="form-label">Song Name</label>
                            <input type="text" class="form-control" id="song_name" name="song_name"
                                   value="<?= htmlspecialchars($tab['song_name']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="artist_name" class="form-label">Artist Name</label>
                            <input type="text" class="form-control" id="artist_name" name="artist_name"
                                   value="<?= htmlspecialchars($tab['artist_name']) ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="difficulty" class="form-label">Difficulty</label>
                            <select class="form-select" id="difficulty" name="difficulty">
                                <option value="" <?= $tab['difficulty'] === '' ? 'selected' : '' ?>>Select Difficulty</option>
                                <option value="Beginner" <?= $tab['difficulty'] === 'Beginner' ? 'selected' : '' ?>>Beginner</option>
                                <option value="Intermediate" <?= $tab['difficulty'] === 'Intermediate' ? 'selected' : '' ?>>Intermediate</option>
                                <option value="Advanced" <?= $tab['difficulty'] === 'Advanced' ? 'selected' : '' ?>>Advanced</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="tuning" class="form-label">Tuning</label>
                            <select class="form-select" id="tuning" name="tuning">
                                <option value="Standard" <?= $tab['tuning'] === 'Standard' ? 'selected' : '' ?>>Standard (E A D G B E)</option>
                                <option value="Drop D" <?= $tab['tuning'] === 'Drop D' ? 'selected' : '' ?>>Drop D (D A D G B E)</option>
                                <option value="Half Step Down" <?= $tab['tuning'] === 'Half Step Down' ? 'selected' : '' ?>>Half Step Down (Eb Ab Db Gb Bb Eb)</option>
                                <option value="Full Step Down" <?= $tab['tuning'] === 'Full Step Down' ? 'selected' : '' ?>>Full Step Down (D G C F A D)</option>
                                <option value="Open G" <?= $tab['tuning'] === 'Open G' ? 'selected' : '' ?>>Open G (D G D G B D)</option>
                                <option value="Open D" <?= $tab['tuning'] === 'Open D' ? 'selected' : '' ?>>Open D (D A D F# A D)</option>
                                <option value="DADGAD" <?= $tab['tuning'] === 'DADGAD' ? 'selected' : '' ?>>DADGAD (D A D G A D)</option>
                                <option value="Custom" <?= $tab['tuning'] === 'Custom' ? 'selected' : '' ?>>Custom</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="capo" class="form-label">Capo (Optional)</label>
                            <select class="form-select" id="capo" name="capo">
                                <option value="" <?= $tab['capo'] === '' ? 'selected' : '' ?>>No Capo</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= $tab['capo'] == $i ? 'selected' : '' ?>>
                                        Capo <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Editor -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 p-0">
                    <ul class="nav nav-tabs" id="tabEditorTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="editor-tab" data-bs-toggle="tab" data-bs-target="#editor-pane"
                                    type="button" role="tab" aria-controls="editor-pane" aria-selected="true">
                                <i class="fas fa-edit me-2"></i>Editor
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview-pane"
                                    type="button" role="tab" aria-controls="preview-pane" aria-selected="false">
                                <i class="fas fa-eye me-2"></i>Preview
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="help-tab" data-bs-toggle="tab" data-bs-target="#help-pane"
                                    type="button" role="tab" aria-controls="help-pane" aria-selected="false">
                                <i class="fas fa-question-circle me-2"></i>Help
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content" id="tabEditorContent">
                        <!-- Editor Tab -->
                        <div class="tab-pane fade show active" id="editor-pane" role="tabpanel" aria-labelledby="editor-tab">
                            <div class="tab-editor-toolbar">
                                <div class="d-flex flex-wrap">
                                    <div class="btn-group me-2 mb-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertTemplate('standard')">
                                            <i class="fas fa-guitar me-1"></i>Standard Tab
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertTemplate('chord')">
                                            <i class="fas fa-border-all me-1"></i>Chord Diagram
                                        </button>
                                    </div>

                                    <div class="btn-group me-2 mb-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertSection('tab')">
                                            [Tab]
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertSection('chord')">
                                            [Chord]
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertSection('lyrics')">
                                            [Lyrics]
                                        </button>
                                    </div>

                                    <div class="btn-group me-2 mb-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertSection('verse')">
                                            [Verse]
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertSection('chorus')">
                                            [Chorus]
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertSection('bridge')">
                                            [Bridge]
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <textarea class="form-control rounded-0 rounded-bottom" id="tab_content" name="tab_content" rows="20"><?= htmlspecialchars($tabContent) ?></textarea>
                        </div>

                        <!-- Preview Tab -->
                        <div class="tab-pane fade" id="preview-pane" role="tabpanel" aria-labelledby="preview-tab">
                            <div class="tab-preview" id="tab_preview"></div>
                        </div>

                        <!-- Help Tab -->
                        <div class="tab-pane fade p-4" id="help-pane" role="tabpanel" aria-labelledby="help-tab">
                            <h5 class="mb-3">Tab Editor Help</h5>

                            <div class="mb-4">
                                <h6>Section Headers</h6>
                                <p>Use section headers to organize your tab:</p>
                                <pre class="bg-light p-2 rounded">[Verse]
[Chorus]
[Bridge]
[Solo]
[Intro]
[Outro]</pre>
                            </div>

                            <div class="mb-4">
                                <h6>Tab Notation</h6>
                                <p>Standard guitar tab notation uses 6 lines representing the strings:</p>
                                <pre class="bg-light p-2 rounded">e|-------|
B|-------|
G|-------|
D|-------|
A|-------|
E|-------|</pre>
                                <p>Numbers on the lines represent frets. For example:</p>
                                <pre class="bg-light p-2 rounded">e|---0---|
B|---1---|
G|---0---|
D|---2---|
A|---3---|
E|-------|</pre>
                            </div>

                            <div class="mb-4">
                                <h6>Chord Diagrams</h6>
                                <p>Use chord diagrams to show finger positions:</p>
                                <pre class="bg-light p-2 rounded">    Am
e|---0---|
B|---1---|
G|---2---|
D|---2---|
A|---0---|
E|-------|</pre>
                            </div>

                            <div class="mb-4">
                                <h6>Lyrics with Chords</h6>
                                <p>Place chords above lyrics:</p>
                                <pre class="bg-light p-2 rounded">[Lyrics]
    G           D           Em          C
Verse lyrics go here with chords above them
    G           D                C
More lyrics with the chords positioned above</pre>
                            </div>

                            <div class="mb-4">
                                <h6>Special Notation</h6>
                                <p>Common tab notation symbols:</p>
                                <ul>
                                    <li><code>h</code> - hammer-on (e.g., <code>5h7</code>)</li>
                                    <li><code>p</code> - pull-off (e.g., <code>7p5</code>)</li>
                                    <li><code>b</code> - bend (e.g., <code>7b9</code>)</li>
                                    <li><code>/</code> - slide up (e.g., <code>5/7</code>)</li>
                                    <li><code>\</code> - slide down (e.g., <code>7\5</code>)</li>
                                    <li><code>~</code> - vibrato</li>
                                    <li><code>x</code> - muted string</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex justify-content-between">
                <a href="<?= $isNewTab ? 'tabs.php' : 'view_tab.php?id=' . $tab['id'] ?>" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i><?= $isNewTab ? 'Create Tab' : 'Save Changes' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tab Templates Modal -->
<div class="modal fade" id="tabTemplatesModal" tabindex="-1" aria-labelledby="tabTemplatesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tabTemplatesModalLabel">Tab Templates</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header">Standard Tab</div>
                            <div class="card-body">
                                    <pre class="mb-0">e|-------|
B|-------|
G|-------|
D|-------|
A|-------|
E|-------|</pre>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-sm btn-primary" onclick="insertTemplate('standard'); $('#tabTemplatesModal').modal('hide');">
                                    Insert
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header">Chord Diagram</div>
                            <div class="card-body">
                                    <pre class="mb-0">    Am
e|---0---|
B|---1---|
G|---2---|
D|---2---|
A|---0---|
E|-------|</pre>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-sm btn-primary" onclick="insertTemplate('chord'); $('#tabTemplatesModal').modal('hide');">
                                    Insert
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Sidebar toggle
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    }

    // Tab editor functions
    const tabContent = document.getElementById('tab_content');
    const tabPreview = document.getElementById('tab_preview');

    // Update preview when switching to preview tab
    document.getElementById('preview-tab').addEventListener('click', updatePreview);

    function updatePreview() {
        const content = tabContent.value;
        let html = '';

        // Process content to identify sections
        const lines = content.split('\n');
        let currentSection = {
            type: 'tab',
            content: []
        };

        for (const line of lines) {
            // Check if line is a section header (enclosed in [])
            const sectionMatch = line.match(/^\s*\[(.*?)\]\s*$/);
            if (sectionMatch) {
                // Save previous section if not empty
                if (currentSection.content.length > 0) {
                    html += renderSection(currentSection);
                }

                const sectionType = sectionMatch[1].toLowerCase();
                // Determine section type
                if (sectionType.includes('chord')) {
                    currentSection = { type: 'chord', content: [] };
                } else if (sectionType.includes('lyric') || sectionType.includes('verse') ||
                    sectionType.includes('chorus') || sectionType.includes('bridge')) {
                    currentSection = { type: 'lyrics', content: [] };
                } else if (sectionType.includes('tab')) {
                    currentSection = { type: 'tab', content: [] };
                } else {
                    currentSection = { type: 'comment', title: sectionMatch[1], content: [] };
                }

                // Add the section header
                currentSection.content.push(line);
            } else {
                currentSection.content.push(line);
            }
        }

        // Add the last section
        if (currentSection.content.length > 0) {
            html += renderSection(currentSection);
        }

        tabPreview.innerHTML = html;
    }

    function renderSection(section) {
        const content = section.content.join('\n');
        return `<div class="tab-section ${section.type}">${escapeHtml(content)}</div>`;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Insert template at cursor position
    function insertTemplate(type) {
        let template = '';

        switch (type) {
            case 'standard':
                template = 'e|-------|-------|-------|-------|\n' +
                    'B|-------|-------|-------|-------|\n' +
                    'G|-------|-------|-------|-------|\n' +
                    'D|-------|-------|-------|-------|\n' +
                    'A|-------|-------|-------|-------|\n' +
                    'E|-------|-------|-------|-------|\n';
                break;
            case 'chord':
                template = '    Am\n' +
                    'e|---0---|\n' +
                    'B|---1---|\n' +
                    'G|---2---|\n' +
                    'D|---2---|\n' +
                    'A|---0---|\n' +
                    'E|-------|\n';
                break;
        }

        insertAtCursor(tabContent, template);
    }

    // Insert section header at cursor position
    function insertSection(type) {
        let header = '';

        switch (type) {
            case 'tab':
                header = '[Tab]\n';
                break;
            case 'chord':
                header = '[Chord]\n';
                break;
            case 'lyrics':
                header = '[Lyrics]\n';
                break;
            case 'verse':
                header = '[Verse]\n';
                break;
            case 'chorus':
                header = '[Chorus]\n';
                break;
            case 'bridge':
                header = '[Bridge]\n';
                break;
        }

        insertAtCursor(tabContent, header);
    }

    // Helper function to insert text at cursor position
    function insertAtCursor(textarea, text) {
        const startPos = textarea.selectionStart;
        const endPos = textarea.selectionEnd;
        const scrollTop = textarea.scrollTop;

        textarea.value = textarea.value.substring(0, startPos) + text + textarea.value.substring(endPos, textarea.value.length);
        textarea.focus();
        textarea.selectionStart = startPos + text.length;
        textarea.selectionEnd = startPos + text.length;
        textarea.scrollTop = scrollTop;
    }

    // Form validation
    document.getElementById('tabForm').addEventListener('submit', function(e) {
        const songName = document.getElementById('song_name').value.trim();
        const artistName = document.getElementById('artist_name').value.trim();
        const tabContent = document.getElementById('tab_content').value.trim();

        if (!songName || !artistName) {
            e.preventDefault();
            alert('Please enter both song name and artist name.');
            return false;
        }

        if (!tabContent) {
            e.preventDefault();
            alert('Please enter tab content.');
            return false;
        }

        return true;
    });
</script>
</body>
</html>