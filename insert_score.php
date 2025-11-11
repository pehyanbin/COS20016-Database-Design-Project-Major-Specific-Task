<?php
include 'settings.php';


// Get Ranges for a Round
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

    if (!$result || $result->num_rows === 0) {
        echo '<option value="">-- No ranges available --</option>';
        exit;
    }

    echo '<option value="">-- Select Range --</option>';
    while ($row = $result->fetch_assoc()) {
        $label = "{$row['Distance']}m - {$row['TargetFace']} ({$row['NumOfArr']} arrows)";
        echo "<option value='{$row['RangeID']}' data-numarr='{$row['NumOfArr']}'>$label</option>";
    }
    exit;
}

// Score Entry)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $competition_id = intval($_POST['competition_id']);
    $round_id       = intval($_POST['round_id']);
    $range_id       = intval($_POST['range_id']);
    $archer_id      = intval($_POST['archer_id']);
    $division_id    = intval($_POST['division_id']);
    $target_number  = intval($_POST['target_number']);
    $ends           = $_POST['end'];

    // Fetch Competition 
    $competition_res = $conn->prepare("SELECT * FROM Competitions WHERE CompetitionID = ?");
    $competition_res->bind_param("i", $competition_id);
    $competition_res->execute();
    $competition_result = $competition_res->get_result();
    if (!$competition_result || $competition_result->num_rows === 0) die("Invalid competition ID.");
    $competition = $competition_result->fetch_assoc();
    $score_date = $competition['CompetitionDate'];

    // Fetch Round
    $round_res = $conn->prepare("SELECT * FROM Rounds WHERE RoundID = ?");
    $round_res->bind_param("i", $round_id);
    $round_res->execute();
    $round_result = $round_res->get_result();
    $round = $round_result->fetch_assoc();

    // Fetch Range 
    $range_stmt = $conn->prepare("
        SELECT r.*, rd.Distance, rd.NumOfArr, rd.TargetFace
        FROM Ranges r
        JOIN RoundDistance rd ON r.RoundDistanceID = rd.RoundDistanceID
        WHERE r.RangeID = ?
    ");
    $range_stmt->bind_param("i", $range_id);
    $range_stmt->execute();
    $range_result = $range_stmt->get_result();
    $range = $range_result->fetch_assoc();

    // Fetch Archer 
    $archer_stmt = $conn->prepare("SELECT * FROM Archers WHERE ArchersID = ?");
    $archer_stmt->bind_param("i", $archer_id);
    $archer_stmt->execute();
    $archer_result = $archer_stmt->get_result();
    $archer = $archer_result->fetch_assoc();
    if (!$archer) die("Archer not found.");

    // Compute Archer Age 
    if (empty($archer['DateOfBirth'])) die("Archer's date of birth missing. Cannot determine category.");
    $dob = new DateTime($archer['DateOfBirth']);
    $today = new DateTime();
    $age = $dob->diff($today)->y;

    // Determine Age Class by Age
    if ($age >= 70) {
        $ageClass = '70+';
    } elseif ($age >= 60) {
        $ageClass = '60+';
    } elseif ($age >= 50) {
        $ageClass = '50+';
    } elseif ($age < 14) {
        $ageClass = 'Under 14';
    } elseif ($age < 16) {
        $ageClass = 'Under 16';
    } elseif ($age < 18) {
        $ageClass = 'Under 18';
    } elseif ($age < 21) {
        $ageClass = 'Under 21';
    } else {
        $ageClass = 'Open';
    }

    // Fetch Category 
    $cat_stmt = $conn->prepare("
        SELECT c.*, d.DivisionName
        FROM Categories c
        JOIN Division d ON c.DivisionID = d.DivisionID
        WHERE c.DivisionID = ?
          AND c.Class = ?
          AND c.Gender = ?
        LIMIT 1
    ");
    $cat_stmt->bind_param("iss", $division_id, $ageClass, $archer['Gender']);
    $cat_stmt->execute();
    $category_result = $cat_stmt->get_result();
    if (!$category_result || $category_result->num_rows === 0) die("No matching category found.");
    $category = $category_result->fetch_assoc();
    $category_id = $category['CategoryID'];

    // Initialize Totals 
    $totalScore = 0;
    $totalX = 0;
    $totalX10 = 0;

    // Insert Score 
    $score_stmt = $conn->prepare("
        INSERT INTO Scores 
        (ArcherID, RoundID, RangesID, CompetitionID, ScoreDate, CategoryID, TargetNum, TotalScore, TotalX, TotalXor10, IsApproved)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $score_stmt->bind_param(
        "iiiisiiiii",
        $archer_id, $round_id, $range_id, $competition_id, $score_date,
        $category_id, $target_number, $totalScore, $totalX, $totalX10
    );
    $score_stmt->execute();
    $score_id = $score_stmt->insert_id;

    //Helper Function 
    function val($s) {
        $s = strtoupper(trim($s));
        if ($s === 'M') return 0;
        if ($s === 'X') return 10;
        return (int)$s;
    }

    // Loop Through Ends 
    foreach ($ends as $endNum => $arrs) {
        $totalEnd = 0;
        $arrScores = [];

        foreach ($arrs as $key => $value) {
            $v = strtoupper(trim($value));
            $num = val($v);
            $totalEnd += $num;

            if ($v === 'X') $totalX++;
            if ($v === 'X' || $v === '10') $totalX10++;

            $arrScores[$key] = $v;
        }

        $totalScore += $totalEnd;
        $numArrows = max(array_map(function($k){ return (int)filter_var($k, FILTER_SANITIZE_NUMBER_INT); }, array_keys($arrScores)));

        // Fill missing arrows with 0
        for ($i = 1; $i <= $numArrows; $i++) {
            if (!isset($arrScores["arr$i"])) $arrScores["arr$i"] = 0;
        }

        // Build dynamic SQL for End table 
        $columns = $placeholders = [];
        $params = [$range_id, $score_id, $endNum];
        for ($i = 1; $i <= $numArrows; $i++) {
            $columns[] = "Arr{$i}Score";
            $placeholders[] = "?";
            $params[] = $arrScores["arr$i"];
        }
        $columns_str = implode(',', $columns);
        $placeholders_str = implode(',', $placeholders);

        $sql = "INSERT INTO End (RangeID, ScoreID, EndNum, $columns_str, TotalEndScore) VALUES (?, ?, ?, $placeholders_str, ?)";
        $stmt2 = $conn->prepare($sql);
        $params[] = $totalEnd;

        $types = "iii" . str_repeat("s", $numArrows) . "i";
        $tmp = [];
        foreach ($params as $key => $value) {
            $tmp[$key] = &$params[$key]; 
        }
        array_unshift($tmp, $types);
        call_user_func_array([$stmt2, 'bind_param'], $tmp);

        $stmt2->execute();
    }

    // Update Total Scores 
    $upd = $conn->prepare("UPDATE Scores SET TotalScore = ?, TotalX = ?, TotalXor10 = ? WHERE ScoreID = ?");
    $upd->bind_param("iiii", $totalScore, $totalX, $totalX10, $score_id);
    $upd->execute();

    // Confirmation Output 
    echo "
        <h2 style=\"
            background-color: #4CAF50; 
            color: white; 
            padding: 15px 20px; 
            border-radius: 5px; 
            text-align: center; 
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        \">Score Saved Successfully!</h2>

        <p style=\"
            background-color: #ffffff; 
            border-left: 6px solid #4CAF50; 
            padding: 15px 20px; 
            border-radius: 5px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            line-height: 1.6;
        \">
            <strong>Competition:</strong> {$competition['CompetitionName']} ({$competition['CompetitionDate']})<br>
            <strong>Round:</strong> {$round['RoundName']}<br>
            <strong>Range:</strong> {$range['Distance']}m - {$range['TargetFace']} ({$range['NumOfArr']} arrows)<br>
            <strong>Archer:</strong> {$archer['ArcherFName']} {$archer['ArcherLName']}<br>
            <strong>Target Number:</strong> $target_number<br>
            <strong>Category:</strong> {$category['DivisionName']} - {$category['Class']} {$category['Gender']}<br>
            <strong>Total Score:</strong> $totalScore
        </p>

        <a href='score_entry.php' style=\"
            display: inline-block; 
            text-decoration: none; 
            color: white; 
            background-color: #2196F3; 
            padding: 10px 20px; 
            border-radius: 5px; 
            transition: background-color 0.3s ease;
        \">Enter Another Score</a>
    ";
}
?>
