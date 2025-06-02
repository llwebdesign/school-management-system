<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db.php';

$error = '';
$success = '';

// Create uploads directory if it doesn't exist
$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $fileName = basename($file['name']);
    $targetPath = $uploadDir . time() . '_' . $fileName;
    
    // Validate file size (5MB max)
    if ($file['size'] > 5242880) {
        $error = "File is too large. Maximum size is 5MB.";
    } 
    // Validate file type
    elseif (!in_array($file['type'], ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'])) {
        $error = "Invalid file type. Allowed types: JPEG, PNG, PDF, TXT.";
    }
    else {
        try {
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Insert file record into database
                $stmt = $pdo->prepare("
                    INSERT INTO files (
                        filename, 
                        original_name,
                        file_type, 
                        file_size, 
                        uploaded_by, 
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $targetPath,
                    $fileName,
                    $file['type'],
                    $file['size'],
                    $_SESSION['user_id']
                ]);
                
                $success = "File uploaded successfully!";
            } else {
                $error = "Failed to upload file.";
            }
        } catch (PDOException $e) {
            error_log("File upload error: " . $e->getMessage());
            $error = "Failed to save file information.";
        }
    }
}

// Handle file deletion
if (isset($_POST['delete_file'])) {
    $fileId = (int)$_POST['delete_file'];
    
    try {
        // Get file info
        $stmt = $pdo->prepare("SELECT filename FROM files WHERE id = ? AND uploaded_by = ?");
        $stmt->execute([$fileId, $_SESSION['user_id']]);
        $file = $stmt->fetch();
        
        if ($file) {
            // Delete file from storage
            if (file_exists($file['filename'])) {
                unlink($file['filename']);
            }
            
            // Delete database record
            $stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
            $stmt->execute([$fileId]);
            
            $success = "File deleted successfully.";
        }
    } catch (PDOException $e) {
        error_log("File deletion error: " . $e->getMessage());
        $error = "Failed to delete file.";
    }
}

include 'header.php';
?>

<div class="filesharing-container">
    <!-- Upload Section -->
    <div class="upload-section">
        <h2>Upload File</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="upload-form">
            <div class="form-group">
                <label for="file">Choose File</label>
                <input type="file" id="file" name="file" required>
                <div class="file-info">
                    <p>Maximum file size: 5MB</p>
                    <p>Allowed types: JPEG, PNG, PDF, TXT</p>
                </div>
            </div>
            
            <div class="progress-bar" style="display: none;">
                <div class="progress"></div>
            </div>

            <button type="submit" class="btn">Upload File</button>
        </form>
    </div>

    <!-- Files List -->
    <div class="files-section">
        <h2>My Files</h2>
        
        <div class="files-grid">
            <?php
            try {
                $stmt = $pdo->prepare("
                    SELECT * FROM files 
                    WHERE uploaded_by = ? 
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $files = $stmt->fetchAll();

                if ($files) {
                    foreach ($files as $file) {
                        $icon = match($file['file_type']) {
                            'image/jpeg', 'image/png' => '🖼️',
                            'application/pdf' => '📄',
                            'text/plain' => '📝',
                            default => '📎'
                        };
                        
                        echo "<div class='file-card'>";
                        echo "<div class='file-icon'>{$icon}</div>";
                        echo "<div class='file-info'>";
                        echo "<h3>" . htmlspecialchars($file['original_name']) . "</h3>";
                        echo "<p>Size: " . number_format($file['file_size'] / 1024, 2) . " KB</p>";
                        echo "<p>Uploaded: " . date('M j, Y', strtotime($file['created_at'])) . "</p>";
                        echo "</div>";
                        echo "<div class='file-actions'>";
                        echo "<a href='" . htmlspecialchars($file['filename']) . "' class='btn btn-small' download>Download</a>";
                        echo "<form method='POST' style='display: inline;'>";
                        echo "<input type='hidden' name='delete_file' value='" . $file['id'] . "'>";
                        echo "<button type='submit' class='btn btn-small btn-danger' onclick='return confirm(\"Are you sure?\")'>Delete</button>";
                        echo "</form>";
                        echo "</div>";
                        echo "</div>";
                    }
                } else {
                    echo "<p class='no-files'>No files uploaded yet</p>";
                }
            } catch (PDOException $e) {
                error_log("File list error: " . $e->getMessage());
                echo "<p class='error'>Unable to load files</p>";
            }
            ?>
        </div>
    </div>
</div>

<script>
document.querySelector('.upload-form').addEventListener('submit', function(e) {
    const fileInput = document.querySelector('#file');
    const progressBar = document.querySelector('.progress-bar');
    const progress = document.querySelector('.progress');
    
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        
        // Show progress bar
        progressBar.style.display = 'block';
        
        // Create FormData
        const formData = new FormData();
        formData.append('file', file);
        
        // Send AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'filesharing.php', true);
        
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percent = (e.loaded / e.total) * 100;
                progress.style.width = percent + '%';
            }
        };
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Reload page to show updated file list
                window.location.reload();
            }
        };
        
        xhr.send(formData);
        e.preventDefault();
    }
});
</script>

<?php include 'footer.php'; ?>
