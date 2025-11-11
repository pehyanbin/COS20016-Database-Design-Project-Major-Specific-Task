<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['action'])) {
        http_response_code(403);
        exit;
    }
    header('Location: login.php?error=Login required');
    exit;
}
include 'settings.php';

// === AJAX: Get Ranges ===
if (isset($_GET['action']) && $_GET['action'] === 'get_ranges') {
    $round_id = intval($_GET['round_id']);
    $stmt = $conn->prepare("
        SELECT r.RangeID, rd.Distance, rd.NumOfArr, rd.TargetFace
        FROM Ranges r
        JOIN RoundDistance rd ON r.RoundDistanceID = rd.RoundDistanceID
        WHERE r.RoundID = ?
        ORDER BY rd.Distance ASC
    ");
    $stmt->bind_param("i", $round_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo '<option value="">-- No ranges --</option>';
        exit;
    }
    echo '<option value="">-- Select Range --</option>';
    while ($row = $result->fetch_assoc()) {
        $label = "{$row['Distance']}m - {$row['TargetFace']} ({$row['NumOfArr']} arrows)";
        echo "<option value='{$row['RangeID']}' data-numarr='{$row['NumOfArr']}'>$label</option>";
    }
    exit;
}

// === AJAX: Get Division & End Info ===
if (isset($_GET['action']) && $_GET['action'] === 'get_division') {
    $archer_id = intval($_GET['archer_id']);
    $stmt = $conn->prepare("SELECT DefaultDivisionID FROM Archers WHERE ArchersID = ?");
    $stmt->bind_param("i", $archer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode(['division_id' => $row['DefaultDivisionID'] ?? 1, 'num_ends' => 12, 'arrows_per_end' => 6]);
    exit;
}

// === MAIN: Insert Score ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $competition_id = intval($_POST['competition_id']);
    $round_id       = intval($_POST['round_id']);
    $range_id       = intval($_POST['range_id']);
    $archer_id      = intval($_POST['archer_id']);
    $division_id    = intval($_POST['division_id']);
    $target_number  = intval($_POST['target_number']);
    $ends           = $_POST['end'];

    // Fetch metadata
    $comp = $conn->query("SELECT * FROM Competitions WHERE CompetitionID = $competition_id")->fetch_assoc();
    $round = $conn->query("SELECT * FROM Rounds WHERE RoundID = $round_id")->fetch_assoc();
    $range = $conn->query("SELECT * FROM Ranges r JOIN RoundDistance rd ON r.RoundDistanceID = rd.RoundDistanceID WHERE r.RangeID = $range_id")->fetch_assoc();
    $archer = $conn->query("SELECT * FROM Archers WHERE ArchersID = $archer_id")->fetch_assoc();
    $category = $conn->query("SELECT * FROM Categories WHERE CategoryID = (SELECT CategoryID FROM Archers WHERE ArchersID = $archer_id LIMIT 1)")->fetch_assoc();

    // Insert Score record
    $stmt = $conn->prepare("INSERT INTO Scores (CompetitionID, RoundID, ArcherID, CategoryID, TargetNumber, ScoreDate) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiiis", $competition_id, $round_id, $archer_id, $category['CategoryID'], $target_number, $comp['CompetitionDate']);
    $stmt->execute();
    $score_id = $conn->insert_id;

    $totalScore = 0; $totalX = 0; $totalX10 = 0;

    foreach ($ends as $endNum => $arrows) {
        $columns = []; $values = []; $types = "iii";
        $endScore = 0; $endX = 0;

        for ($i = 1; $i <= 6; $i++) {
            $score = strtoupper($arrows["arr$i"] ?? 'M');
            $val = ($score === 'X') ? 10 : ($score === 'M' ? 0 : intval($score));
            $columns[] = "Arrow$i";
            $values[] = $val;
            $types .= "i";
            $endScore += $val;
            if ($score === 'X') $endX++;
        }

        $totalScore += $endScore;
        $totalX += $endX;
        if ($endScore >= 50) $totalX10++;

        $columns_str = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $sql = "INSERT INTO End (RangeID, ScoreID, EndNum, $columns_str, TotalEndScore) VALUES (?, ?, ?, $placeholders, ?)";
        $stmt2 = $conn->prepare($sql);
        $params = array_merge([$range_id, $score_id, $endNum], $values, [$endScore]);
        $tmp = [];
        foreach ($params as $k => $v) $tmp[$k] = &$params[$k];
        call_user_func_array([$stmt2, 'bind_param'], array_merge([$types], $tmp));
        $stmt2->execute();
    }

    $upd = $conn->prepare("UPDATE Scores SET TotalScore = ?, TotalX = ?, TotalXor10 = ? WHERE ScoreID = ?");
    $upd->bind_param("iiii", $totalScore, $totalX, $totalX10, $score_id);
    $upd->execute();

    echo "
    <h2 style='background:#4CAF50;color:white;padding:15px;border-radius:5px;text-align:center;'>Score Saved!</h2>
    <p style='background:white;padding:15px;border-left:6px solid #4CAF50;'>
        <strong>Competition:</strong> {$comp['CompetitionName']} ({$comp['CompetitionDate']})<br>
        <strong>Round:</strong> {$round['RoundName']}<br>
        <strong>Range:</strong> {$range['Distance']}m - {$range['TargetFace']}<br>
        <strong>Archer:</strong> {$archer['ArcherFName']} {$archer['ArcherLName']}<br>
        <strong>Total Score:</strong> $totalScore
    </p>
    <a href='score_entry.php' style='background:#2196F3;color:white;padding:10px 20px;border-radius:5px;text-decoration:none;'>Enter Another</a>
    ";
}
?>