<div class="pagination">
    <?php if ($totalPages > 1): ?>
        <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php
            $queryString = http_build_query(array_merge($_GET, ['page' => $i]));
            if ($i == $page):
            ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="?<?= $queryString ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<table>
    <tr>
        <?php
            function sortLink($label, $column, $currentSort, $currentOrder) {
                $newOrder = ($currentSort == $column && $currentOrder == 'ASC') ? 'DESC' : 'ASC';
                $icon = ($currentSort == $column) ? ($currentOrder == 'ASC' ? '▲' : '▼') : '';
                $queryString = http_build_query(array_merge($_GET, ['sort' => $column, 'order' => $newOrder]));
                return "<a class='sort-link' href='?{$queryString}'>{$label} {$icon}</a>";
            }
        ?>
        <th><?= sortLink('Archer', 'ArcherName', $sort, $order) ?></th>
        <th>Division</th>
        <th>Round</th>
        <th>Competition</th>
        <th><?= sortLink('Total Score', 'TotalScore', $sort, $order) ?></th>
        <th>Total X</th>
        <th>Total 10/X</th>
        <th><?= sortLink('Date', 'ScoreDate', $sort, $order) ?></th>
    </tr>
    <?php if ($results && $results->num_rows > 0): ?>
        <?php while ($row = $results->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['ArcherName']) ?></td>
                <td><?= htmlspecialchars($row['DivisionName']) ?></td>
                <td><?= htmlspecialchars($row['RoundName']) ?></td>
                <td><?= htmlspecialchars($row['CompetitionName']) ?></td>
                <td><?= htmlspecialchars($row['TotalScore']) ?></td>
                <td><?= htmlspecialchars($row['TotalX']) ?></td>
                <td><?= htmlspecialchars($row['TotalXor10']) ?></td>
                <td><?= htmlspecialchars($row['ScoreDate']) ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="8">No scores found.</td></tr>
    <?php endif; ?>
</table>

<div class="pagination">
    <?php if ($totalPages > 1): ?>
        <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php
            $queryString = http_build_query(array_merge($_GET, ['page' => $i]));
            if ($i == $page):
            ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="?<?= $queryString ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
        <?php endif; ?>
    <?php endif; ?>
</div>        