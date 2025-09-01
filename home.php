
<?php
include 'connection.php';

$isc = $main = $top = '';
$message = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $value = $_POST[$action] ?? '';
    $mcid = $_POST['MCID'] ?? '';

    if ($value) {
        if ($action === 'isc') {
            $value_safe = escapeshellarg($value);
            $command = "\"C:\\Program Files\\TeamViewer\\TeamViewer.exe\" -i $value_safe --Password Pts0123680039";
            exec($command);
            exec('exit');
            $message = "TeamViewer connection opened for ISC: " . htmlspecialchars($value);
        } 

        elseif (in_array($action, ['main', 'top'])) {
            $url = 'http://' . escapeshellarg($value . ':18080');
            $command = 'start ' . $url;
            exec($command);
            $message = strtoupper($action) . " browser window opened at: http://" . htmlspecialchars($value) . ':18080';
        } 

        elseif (in_array($action,['cctv'])){
            $url = 'http://' . escapeshellarg($value);
            $command = 'start ' . $url;
            exec($command);
            $message = strtoupper($action) . " browser window opened at: http://" . htmlspecialchars($value);
        }
        elseif ($action === 'cctv_stream') {
            $url = escapeshellarg($_POST['cctv_stream'] ?? '');
            $vlcPath = '"C:\\Program Files (x86)\\VideoLAN\\VLC\\vlc.exe"';
            $command = 'start "" ' . $vlcPath . ' --play-and-exit ' . $url;
            exec($command);
            $message = "VLC opened with URL: " . htmlspecialchars($_POST['cctv_stream'] ?? '');
        }
        elseif ($action === 'main_stream') {
            $url = escapeshellarg($_POST['main_stream'] ?? '');
            $vlcPath = '"C:\\Program Files (x86)\\VideoLAN\\VLC\\vlc.exe"';
            $command = 'start "" ' . $vlcPath . ' --play-and-exit ' . $url;
            exec($command);
            $message = "VLC opened with URL: " . htmlspecialchars($_POST['main_stream'] ?? '');
        }
        elseif ($action === 'top_stream') {
            $url = escapeshellarg($_POST['top_stream'] ?? '');
            $vlcPath = '"C:\\Program Files (x86)\\VideoLAN\\VLC\\vlc.exe"';
            $command = 'start "" ' . $vlcPath . ' --play-and-exit ' . $url;
            exec($command);
            $message = "VLC opened with URL: " . htmlspecialchars($_POST['top_stream'] ?? '');
        }

        else {
            $message = "Invalid action.";
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?MCID=" . urlencode($mcid));
        exit;

        } 
        else {
        $message = strtoupper($action) . " value not provided.";
    }
}


// Fetch data from DB
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$mcid = filter_input(INPUT_GET, 'MCID', FILTER_SANITIZE_STRING);

if ($mcid) {
    $stmt = $conn->prepare("SELECT * FROM egm WHERE MCID = ?");
    $stmt->bind_param("s", $mcid);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();


            $isc  = htmlspecialchars($row['ISC'], ENT_QUOTES, 'UTF-8');
            $main = htmlspecialchars($row['MAIN'] , ENT_QUOTES, 'UTF-8');
            $top  = htmlspecialchars($row['TOP'] , ENT_QUOTES, 'UTF-8'); 
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
    $message = "Please enter a valid MCID.";
}


$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Remote Connect</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        input[readonly] {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
        }
        .form-group {
            margin-bottom: 12px;
        }
        .button-group button {
            margin-right: 10px;
        }
        .message {
            margin-top: 15px;
            color: green;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>

    <h2>Remote Control Launcher</h2>

    <!-- MCID Search Form -->
    <form method="get" action="">
        <div class="form-group">
            <label for="MCID">Machine Number (MCID):</label>
            <input type="text" id="MCID" name="MCID" value="<?= $mcid ?? '' ?>">
            <button type="submit">Submit</button>
        </div>
    </form>

    <!-- Show inputs and action buttons only if data exists -->
    <?php if ($isc || $main || $top): ?>
    <form method="post">
        <!-- Hidden MCID for reference in POST -->
        <input type="hidden" name="MCID" value="<?= $mcid ?>">

        <div class="form-group">
            <label for="isc">ISC IP:</label>
            <input type="text" id="isc" name="isc" value="<?= $isc ?>" readonly>
            <button type="submit" name="action" value="isc">Open ISC</button>
        </div>

        <div class="form-group">
            <label for="main">MAIN IP:</label>
            <input type="text" id="main" name="main" value="<?= $main ?>" readonly>
            <button type="submit" name="action" value="main">MAIN SITE</button>
             <button type="submit" name="action" value="main_stream">MAIN STREAM</button>
              <input type="text" id="main_stream" name="main_stream" value="<?= $main_stream ?>" hidden>
        </div>

        <div class="form-group">
            <label for="top">TOP IP:</label>
            <input type="text" id="top" name="top" value="<?= $top ?>" readonly>
            <button type="submit" name="action" value="top">TOP SITE</button>
             <input type="text" id="top_stream" name="top_stream" value="<?= $top_stream ?>" hidden>
            <button type="submit" name="action" value="top_stream">TOP STREAM</button>
        </div>
     <div class="form-group">
    <label for="cctv">CCTV IP:</label>
    <input type="text" id="cctv" name="cctv" value="<?= $cctv ?>" readonly>
    <button type="submit" name="action" value="cctv">CCTV SITE</button>
    <input type="text" id="cctv_stream" name="cctv_stream" value="<?= $cctv_stream ?>" hidden>
    <button type="submit" name="action" value="cctv_stream">CCTV STREAM</button>
</div>
    </form>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

</body>
</html>
