<?php 
    include 'header.inc';
    include 'settings.php';

    $competitions = $conn->query("SELECT CompetitionID, CompetitionName FROM Competitions ORDER BY CompetitionDate DESC");
    $rounds = $conn->query("SELECT RoundID, RoundName FROM Rounds");
    $divisions = $conn->query("SELECT DivisionID, DivisionName FROM Division");

    $whereClauses = [];
    if (!empty($_GET['competition'])) $whereClauses[] = "s.CompetitionID = " . intval($_GET['competition']);
    if (!empty($_GET['round'])) $whereClauses[] = "s.RoundID = " . intval($_GET['round']);
    if (!empty($_GET['division'])) $whereClauses[] = "c.DivisionID = " . intval($_GET['division']);
    if (!empty($_GET['gender'])) $whereClauses[] = "a.Gender = '" . $conn->real_escape_string($_GET['gender']) . "'";

    $whereSQL = count($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

    $allowedSort = [
        'ArcherName' => 'Archer Name',
        'TotalScore' => 'Total Score',
        'ScoreDate'  => 'Date'
    ];

    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'ScoreDate';
    $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

    if (!array_key_exists($sort, $allowedSort)) $sort = 'ScoreDate';
    $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 25;
    if (!in_array($limit, [10, 25, 50])) $limit = 25;

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $limit;

    $countQuery = "
        SELECT COUNT(*) AS total
        FROM Scores s
        JOIN Archers a ON s.ArcherID = a.ArchersID
        JOIN Categories c ON s.CategoryID = c.CategoryID
        JOIN Division d ON c.DivisionID = d.DivisionID
        JOIN Rounds r ON s.RoundID = r.RoundID
        LEFT JOIN Competitions comp ON s.CompetitionID = comp.CompetitionID
        $whereSQL
    ";
    $countResult = $conn->query($countQuery);
    $totalRows = $countResult ? $countResult->fetch_assoc()['total'] : 0;
    $totalPages = ceil($totalRows / $limit);
    
    $query = "
        SELECT 
            s.ScoreID,
            CONCAT(a.ArcherFName, ' ', a.ArcherLName) AS ArcherName,
            d.DivisionName,
            r.RoundName,
            comp.CompetitionName,
            s.TotalScore,
            s.TotalX,
            s.TotalXor10,
            s.ScoreDate
        FROM Scores s
        JOIN Archers a ON s.ArcherID = a.ArchersID
        JOIN Categories c ON s.CategoryID = c.CategoryID
        JOIN Division d ON c.DivisionID = d.DivisionID
        JOIN Rounds r ON s.RoundID = r.RoundID
        LEFT JOIN Competitions comp ON s.CompetitionID = comp.CompetitionID
        $whereSQL
        ORDER BY $sort $order
        LIMIT $limit OFFSET $offset
    ";
    $results = $conn->query($query);

    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        ob_start();
        include 'table_fragment.php';
        echo ob_get_clean();
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="author"      	content="Janice Yeoh Shu Yi">
    <meta name="description" 	content="Show archery scores">
    <meta name="viewport" 		content="width=device-width, initial-scale=1.0">
    <title>Archery Database - Show Scores</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <h2 class="title">Show Scores</h2>
    <main>
        <form id="filterForm" method="GET" action="">
            <label>Competition:</label>
            <select name="competition">
                <option value="">All</option>
                <?php while ($row = $competitions->fetch_assoc()): ?>
                    <option value="<?= $row['CompetitionID'] ?>" <?= isset($_GET['competition']) && $_GET['competition'] == $row['CompetitionID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['CompetitionName']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Round:</label>
            <select name="round">
                <option value="">All</option>
                <?php while ($row = $rounds->fetch_assoc()): ?>
                    <option value="<?= $row['RoundID'] ?>" <?= isset($_GET['round']) && $_GET['round'] == $row['RoundID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['RoundName']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Division:</label>
            <select name="division">
                <option value="">All</option>
                <?php while ($row = $divisions->fetch_assoc()): ?>
                    <option value="<?= $row['DivisionID'] ?>" <?= isset($_GET['division']) && $_GET['division'] == $row['DivisionID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['DivisionName']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Gender:</label>
            <select name="gender">
                <option value="">All</option>
                <option value="Male" <?= (isset($_GET['gender']) && $_GET['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= (isset($_GET['gender']) && $_GET['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
            </select>

            <label>Sort By:</label>
            <select name="sort">
                <?php foreach ($allowedSort as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($sort == $key ? 'selected' : '') ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="order">
                <option value="ASC" <?= ($order == 'ASC' ? 'selected' : '') ?>>Ascending</option>
                <option value="DESC" <?= ($order == 'DESC' ? 'selected' : '') ?>>Descending</option>
            </select>

            <label>Results per page:</label>
            <select name="limit" id="limit">
                <option value="10" <?= (isset($_GET['limit']) && $_GET['limit'] == 10) ? 'selected' : '' ?>>10</option>
                <option value="25" <?= (!isset($_GET['limit']) || $_GET['limit'] == 25) ? 'selected' : '' ?>>25</option>
                <option value="50" <?= (isset($_GET['limit']) && $_GET['limit'] == 50) ? 'selected' : '' ?>>50</option>
            </select>

            <button type="submit">Show Scores</button>
        </form>
        
        <div id="results-container">
            <?php include 'result.php'; ?>       
        </div>
    </main>
    <?php 
        include 'footer.inc'; 
    ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.querySelector("form");
            const resultsContainer = document.getElementById("results-container");

            form.addEventListener("change", function() {
                const params = new URLSearchParams(new FormData(form));
                fetch("showscore.php?" + params + "&ajax=1")
                    .then(res => res.text())
                    .then(html => {
                        resultsContainer.innerHTML = html;
                    })
                    .catch(err => console.error("Error loading results:", err));
            });
        });
    </script>
</body>
</html>
