<?php
include 'connection.php';

$isc = $main = $top = $cctv = $cctv_stream = $main_stream = $top_stream = '';
$message = '';

function getVLCPath() {
    $paths = [
        'C:\\Program Files\\VideoLAN\\VLC\\vlc.exe',
        'C:\\Program Files (x86)\\VideoLAN\\VLC\\vlc.exe',
        'vlc'  // fallback if vlc is in PATH
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            error_log("VLC found at: $path");
            return $path;
        }
    }
    error_log("VLC executable not found in all paths");
    return null;
}

$vlcPath = getVLCPath();

function getTeamViewerPath() {
    $paths = [
        'C:\\Program Files\\TeamViewer\\TeamViewer.exe',
        'C:\\Program Files (x86)\\TeamViewer\\TeamViewer.exe',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            return '"' . $path . '"';
        }
    }

    return null;
}

$teamViewerPath = getTeamViewerPath();

// Your existing connection checks and POST handling here...

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $mcid = $_POST['MCID'] ?? '';

    if ($action === 'update') {
        // Your existing update logic here...
    }

    $value = $_POST[$action] ?? '';

    if ($value) {
        if ($action === 'isc') {
            $value_safe = escapeshellarg($value);
            if ($teamViewerPath) {
                $command = "$teamViewerPath -i $value_safe --Password Pts0123680039";
                exec($command);
                $message = "TeamViewer connection opened for ISC: " . htmlspecialchars($value);
            } else {
                $message = "Error: TeamViewer executable not found on server.";
                error_log($message);
            }
        } else {
            switch ($action) {
                case 'main':
                case 'top':
                case 'cctv':
                    $url = 'http://' . $value . ':18080';
                    $command = 'start ' . escapeshellarg($url);
                    exec($command);
                    $message = strtoupper($action) . " site opened at: " . htmlspecialchars($value);
                    break;

case 'cctv_stream':
case 'main_stream':
case 'top_stream':
    if ($vlcPath) {
        $url = $_POST[$action] ?? '';
        if ($url) {
            $arguments = escapeshellarg($url) . " --intf=qt --no-video-title-show --play-and-exit";

            if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
                // Correct quoting for Windows 'start' command
                pclose(popen('start "" "' . $vlcPath . '" ' . $arguments, "r"));
            } else {
                exec("$vlcPath $arguments > /dev/null 2>&1 &");
            }

            $message = strtoupper($action) . " stream launched in VLC.";
        } else {
            $message = "Error: No stream URL provided.";
        }
    } else {
        $message = "Error: VLC executable not found.";
    }
    break;
                default:
                    $message = "⚠️ Invalid action requested.";
                    break;
            }
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?MCID=" . urlencode($mcid) . "&message=" . urlencode($message));
        exit;
    } else {
        $message = strtoupper($action) . " value not provided.";
    }
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $mcid = $_POST['MCID'] ?? '';

    if ($action === 'update') {
        // Sanitize inputs (basic trimming)
        $isc = trim($_POST['isc'] ?? '');
        $main = trim($_POST['main'] ?? '');
        $top = trim($_POST['top'] ?? '');
        $cctv = trim($_POST['cctv'] ?? '');
        $cctv_stream = trim($_POST['cctv_stream'] ?? '');
        $main_stream = trim($_POST['main_stream'] ?? '');
        $top_stream = trim($_POST['top_stream'] ?? '');

        // Prepare update query
        $stmt = $conn->prepare("UPDATE masterlist SET ISC=?, MAIN=?, TOP=?, CCTV=?, CCTV_STREAM=?, MAIN_STREAM=?, TOP_STREAM=? WHERE MCID=?");

        if ($stmt) {
            $stmt->bind_param(
                "ssssssss",
                $isc,
                $main,
                $top,
                $cctv,
                $cctv_stream,
                $main_stream,
                $top_stream,
                $mcid
            );

                       if ($stmt->execute()) {
                $message = "Record updated successfully for MCID: " . htmlspecialchars($mcid);
                // Clear form values to empty after successful update
                $isc = $main = $top = $cctv = $cctv_stream = $main_stream = $top_stream = '';
            } else {
                $message = "Update failed: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $message = "Failed to prepare update statement: " . $conn->error;
        }

        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?MCID=" . urlencode($mcid) . "&message=" . urlencode($message));
        exit;
    }

    // Other POST actions (isc, main, top, cctv, streams)
    $value = $_POST[$action] ?? '';

if ($value) {
    if ($action === 'isc') {
        $value_safe = escapeshellarg($value);
        $command = "$teamViewerPath -i $value_safe --Password Pts0123680039";
        exec($command);
        $message = "TeamViewer connection opened for ISC: " . htmlspecialchars($value);
    } else {
        switch ($action) {
            case 'main':
            case 'top':
            case 'cctv':
                $url = 'http://' . $value . ':18080';
                $command = 'start ' . escapeshellarg($url);
                exec($command);
                $message = strtoupper($action) . " site opened at: " . htmlspecialchars($value);
                break;

            case 'cctv_stream':
            case 'main_stream':
            case 'top_stream':
               if ($vlcPath) {
            $url = $_POST[$submit] ?? '';
            $command = 'start "" ' . $vlcPath . ' --play-and-exit ' . escapeshellarg($url);
            exec($command);
            $message = strtoupper($action) . " stream launched in VLC.";
        } else {
            $message = "Error: VLC executable not found on server.";
        }
                break;

            default:
                $message = "⚠️ Invalid action requested.";
                break;
        }
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?MCID=" . urlencode($mcid) . "&message=" . urlencode($message));
    exit;
} else {
    $message = strtoupper($action) . " value not provided.";
}
}

// Fetch data from DB for GET request
$mcid = filter_input(INPUT_GET, 'MCID', FILTER_SANITIZE_STRING);
$message = $_GET['message'] ?? '';

if ($mcid) {
    $stmt = $conn->prepare("SELECT * FROM masterlist WHERE MCID = ?");
    $stmt->bind_param("s", $mcid);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            $isc  = htmlspecialchars($row['ISC'], ENT_QUOTES, 'UTF-8');
            $main = htmlspecialchars($row['MAIN'], ENT_QUOTES, 'UTF-8');
            $top  = htmlspecialchars($row['TOP'], ENT_QUOTES, 'UTF-8');
            $cctv = htmlspecialchars($row['CCTV'], ENT_QUOTES, 'UTF-8');
            $cctv_stream = htmlspecialchars($row['CCTV_STREAM'], ENT_QUOTES, 'UTF-8');
            $main_stream = htmlspecialchars($row['MAIN_STREAM'], ENT_QUOTES, 'UTF-8');
            $top_stream = htmlspecialchars($row['TOP_STREAM'], ENT_QUOTES, 'UTF-8');
        } else {
            $message = "No record found for MCID: " . htmlspecialchars($mcid);
        }
        $result->free();
    } else {
        $message = "Error executing query: " . $stmt->error;
    }

    $stmt->close();
} else {
    if (empty($message)) {
        $message = "Please enter a valid MCID.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Remote Connect</title>
    <style>
       .center-button {
    text-align: left;
    margin-top: 20px;
}

#disableReadonlyBtn {
    padding: 7px 20px;
    border: none;
    background-color: #28a745;
    color: white;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    font-size: 16px;
}

#disableReadonlyBtn:hover {
    background-color: #218838;
}
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f8;
            padding: 30px;
            margin: 0;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .container {
            max-width: 700px;
            margin: auto;
            background: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 15px;
        }

        .form-group label {
            flex: 0 0 120px;
            font-weight: bold;
        }

        .form-group input[type="text"] {
            flex: 1;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
            transition: border 0.3s;
        }

        .form-group input[readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }

        .form-group input:focus {
            border-color: #007bff;
            outline: none;
        }
        #update{
             padding: 7px 14px;
            border: none;
            background-color: #007bff;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 5px;
            transition: background-color 0.3s;
            margin-right: 10px;
            float: right;
        }

        .form-group button {
            padding: 7px 14px;
            border: none;
            background-color: #007bff;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 5px;
            transition: background-color 0.3s;
            margin-right: 10px;
        }

        .form-group button:hover {
            background-color: #0056b3;
        }

        .stream-section {
            margin-top: 25px;
            padding: 15px;
            background: #f1f1f1;
            border-radius: 6px;
        }

        .stream-section label {
            font-weight: bold;
            display: inline-block;
            margin-right: 10px;
        }

        .message {
            margin-top: 20px;
            padding: 10px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
        }

        .hidden {
            display: none;
        }

        @media (max-width: 600px) {
            .form-group {
                flex-direction: column;
                align-items: flex-start;
            }

            .form-group input,
            .form-group button {
                width: 100%;
                margin-top: 8px;
            }

            .form-group input {
                margin-right: 0;
            }
            .hidden {
    display: none;
}

        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Remote Control Launcher</h2>

        <!-- MCID Search Form -->
        <form method="get" action="">
            <div class="form-group">
                <label for="MCID">Machine ID:</label>
                <input type="text" id="MCID" name="MCID" value="<?= htmlspecialchars($mcid) ?>" required>
                <button type="submit">Search</button>
            </div>
        </form>

        <?php if ($mcid): ?>
        <form method="post" id="controlForm">
            <input type="hidden" name="MCID" value="<?= htmlspecialchars($mcid) ?>">

            <div class="form-group">
                <label for="isc">ISC:</label>
                <input type="text" id="isc" name="isc" value="<?= $isc ?>" readonly>
            <a href="teamviewer8://control?device=<?= $isc ?>&--password=Pts0123680039">Connect via TeamViewer</a>
            </div>

            <div class="form-group">
                <label for="main">MAIN:</label>
                <input type="text" id="main" name="main" value="<?= $main ?>" readonly>
                <button type="button" onclick="window.open('http://<?= htmlspecialchars($main) ?>:18080', '_blank')">Open</button>
            </div>

            <div class="form-group">
                <label for="top">TOP:</label>
                <input type="text" id="top" name="top" value="<?= $top ?>" readonly>
                <button type="button" onclick="window.open('http://<?= htmlspecialchars($top) ?>:18080', '_blank')">Open</button>
            </div>

            <div class="form-group">
                <label for="cctv">CCTV:</label>
                <input type="text" id="cctv" name="cctv" value="<?= $cctv ?>" readonly>
                <button type="button" onclick="window.open('http://<?= htmlspecialchars($cctv) ?>', '_blank')">Open</button>
            </div>

            <div class="stream-section">
                <div class="form-group">
                    <label for="cctv_stream">CCTV Stream:</label>
                    <input type="text" id="cctv_stream" name="cctv_stream" value="<?= $cctv_stream ?>" readonly>
                    <button type="submit" name="action" value="cctv_stream">Play</button>
                </div>

                <div class="form-group">
                    <label for="main_stream">MAIN Stream:</label>
                    <input type="text" id="main_stream" name="main_stream" value="<?= $main_stream ?>" readonly>
                    <button type="submit" name="action" value="main_stream">Play</button>
                </div>

                <div class="form-group">
                    <label for="top_stream">TOP Stream:</label>
                    <input type="text" id="top_stream" name="top_stream" value="<?= $top_stream ?>" readonly>
                    <button type="submit" name="action" value="top_stream">Play</button>
                </div>
            </div>

            <div class="center-button">
                <button id="disableReadonlyBtn" type="button">Edit</button>
                <button type="submit" name="action" value="update" id="update" style="display:none;">Update</button>
            </div>

        </form>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
    const disableBtn = document.getElementById('disableReadonlyBtn');
    const updateBtn = document.getElementById('update');
    const inputs = document.querySelectorAll('#controlForm input[type="text"][readonly]');

    disableBtn.addEventListener('click', () => {
        inputs.forEach(input => {
            input.readOnly = false;
            input.style.backgroundColor = '#fff';
            input.style.cursor = 'text';
        });
        disableBtn.disabled = true; // Optional: disable edit button after click
        updateBtn.style.display = 'inline-block'; // Show Update button
    });

    // After update success, disable inputs and hide Update button again
    const message = <?= json_encode($message) ?>;
    const inputsAll = document.querySelectorAll('#controlForm input[type="text"]');

    if (message.includes("Record updated successfully")) {
        inputsAll.forEach(input => {
            input.readOnly = true;
            input.style.backgroundColor = '#e9ecef';
            input.style.cursor = 'not-allowed';

        });

        if (disableBtn) {
            disableBtn.disabled = false;
        }
        if (updateBtn) {
            updateBtn.style.display = 'none';
        }
    }
});
</script>


</body>
</html>
