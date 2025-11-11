<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=Login required');
    exit;
}
?>
<?php 
include 'header.inc';
include 'settings.php';

// Fetch dropdown data
$competitions = $conn->query("SELECT CompetitionID, CompetitionName FROM Competitions ORDER BY CompetitionDate DESC");
$rounds = $conn->query("SELECT RoundID, RoundName FROM Rounds");
$divisions = $conn->query("SELECT DivisionID, DivisionName FROM Division");

// Build filters
$whereClauses = [];
if (!empty($_GET['competition'])) $whereClauses[] = "s.CompetitionID = " . intval($_GET['competition']);
if (!empty($_GET['round'])) $whereClauses[] = "s.RoundID = " . intval($_GET['round']);
if (!empty($_GET['division'])) $whereClauses[] = "c.DivisionID = " . intval($_GET['division']);
if (!empty($_GET['gender'])) $whereClauses[] = "a.Gender = '" . $conn->real_escape_string($_GET['gender']) . "'";

$whereSQL = count($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Sorting
$allowedSort = ['ArcherName'=>'Archer Name','TotalScore'=>'Total Score','ScoreDate'=>'Date'];
$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $allowedSort) ? $_GET['sort'] : 'ScoreDate';
$order = strtoupper($_GET['order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

// Pagination
$limit = intval($_GET['limit'] ?? 25);
if (!in_array($limit, [10,25,50])) {
    $limit = 25;
}
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Count total
$countQuery = "SELECT COUNT(*) AS total FROM Scores s JOIN Archers a ON s.ArcherID = a.ArchersID JOIN Categories c ON s.CategoryID = c.CategoryID JOIN Division d ON c.DivisionID = d.DivisionID JOIN Rounds r ON s.RoundID = r.RoundID LEFT JOIN Competitions comp ON s.CompetitionID = comp.CompetitionID $whereSQL";
$totalRows = $conn->query($countQuery)->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Main query
$query = "
  SELECT 
    CONCAT(a.ArcherFName, ' ', a.ArcherLName) AS ArcherName,
    d.DivisionName,
    r.RoundName,
    comp.CompetitionName,
    s.TotalScore,
    s.TotalX,
    s.TotalXor10,
    DATE(comp.CompetitionDate) AS ScoreDate
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Show Scores</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container">
    <h2>View Scores</h2>

    <form method="get" class="filter-form">
        <select name="competition">
            <option value="">All Competitions</option>
            <?php while($c = $competitions->fetch_assoc()): ?>
            <option value="<?= $c['CompetitionID'] ?>" <?= ($_GET['competition']??'')==$c['CompetitionID']?'selected':'' ?>>
                <?= htmlspecialchars($c['CompetitionName']) ?>
            </option>
            <?php endwhile; ?>
        </select>

        <select name="round">
            <option value="">All Rounds</option>
            <?php mysqli_data_seek($rounds, 0); while($r = $rounds->fetch_assoc()): ?>
            <option value="<?= $r['RoundID'] ?>" <?= ($_GET['round']??'')==$r['RoundID']?'selected':'' ?>>
                <?= htmlspecialchars($r['RoundName']) ?>
            </option>
            <?php endwhile; ?>
        </select>

        <select name="division">
            <option value="">All Divisions</option>
            <?php mysqli_data_seek($divisions, 0); while($d = $divisions->fetch_assoc()): ?>
            <option value="<?= $d['DivisionID'] ?>" <?= ($_GET['division']??'')==$d['DivisionID']?'selected':'' ?>>
                <?= htmlspecialchars($d['DivisionName']) ?>
            </option>
            <?php endwhile; ?>
        </select>

        <select name="gender">
            <option value="">All Genders</option>
            <option value="Male" <?= ($_GET['gender']??'')=='Male'?'selected':'' ?>>Male</option>
            <option value="Female" <?= ($_GET['gender']??'')=='Female'?'selected':'' ?>>Female</option>
        </select>

        <select name="sort">
            <?php foreach($allowedSort as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $sort==$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>

        <select name="order">
            <option value="DESC" <?= $order=='DESC'?'selected':'' ?>>High to Low</option>
            <option value="ASC" <?= $order=='ASC'?'selected':'' ?>>Low to High</option>
        </select>

        <select name="limit">
            <option value="10" <?= $limit==10?'selected':'' ?>>10</option>
            <option value="25" <?= $limit==25?'selected':'' ?>>25</option>
            <option value="50" <?= $limit==50?'selected':'' ?>>50</option>
        </select>

        <button type="submit">Filter</button>
        <a href="showscore.php" class="clear-btn">Clear</a>
    </form>

    <div class="section">
        <p><strong><?= $totalRows ?> scores found</strong></p>
        <?php if ($results && $results->num_rows > 0): ?>
        <table>
            <tr>
                <th>Archer</th><th>Division</th><th>Round</th><th>Competition</th>
                <th>Total</th><th>X</th><th>X+10</th><th>Date</th>
            </tr>
            <?php while($row = $results->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['ArcherName']) ?></td>
                <td><?= htmlspecialchars($row['DivisionName']) ?></td>
                <td><?= htmlspecialchars($row['RoundName']) ?></td>
                <td><?= htmlspecialchars($row['CompetitionName']) ?></td>
                <td><?= $row['TotalScore'] ?></td>
                <td><?= $row['TotalX'] ?></td>
                <td><?= $row['TotalXor10'] ?></td>
                <td><?= $row['ScoreDate'] ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
        <p>No scores found.</p>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$page-1])) ?>">Prev</a>
            <?php endif;
            for ($i = $start; $i <= $end; $i++):
                $q = http_build_query(array_merge($_GET, ['page'=>$i]));
                echo $i == $page ? "<span class='current'>$i</span>" : "<a href='?$q'>$i</a>";
            endfor;
            if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$page+1])) ?>">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php include 'footer.inc'; ?>
</body>
</html>