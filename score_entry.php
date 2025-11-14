<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=Login required');
    exit;
}
?>
<?php include_once 'header.inc'; ?>
<?php include 'settings.php'; ?>

<?php
$competitions = $conn->query("SELECT * FROM Competitions ORDER BY CompetitionDate DESC");
$rounds = $conn->query("SELECT RoundID, RoundName FROM Rounds ORDER BY RoundName");
$archers = $conn->query("SELECT * FROM Archers ORDER BY ArcherFName, ArcherLName");
$divisions = $conn->query("SELECT * FROM Division ORDER BY DivisionName");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Archery Score Entry</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body class="score-entry">

    <h2>Archery Score Entry</h2>

    <div id="step1" class="step active">
        <form id="selectionForm">
            <label>Competition</label>
            <select name="competition_id" required>
                <option value="">-- Select Competition --</option>
                <?php while($c=$competitions->fetch_assoc()): ?>
                <option value="<?= $c['CompetitionID'] ?>"><?= $c['CompetitionName'] ?> (<?= $c['CompetitionDate'] ?>)</option>
                <?php endwhile; ?>
            </select>

            <label>Round</label>
            <select name="round_id" id="roundSelect" required onchange="loadRanges(this.value)">
                <option value="">-- Select Round --</option>
                <?php while($r=$rounds->fetch_assoc()): ?>
                <option value="<?= $r['RoundID'] ?>"><?= $r['RoundName'] ?></option>
                <?php endwhile; ?>
            </select>

            <label>Range (Distance)</label>
            <select name="range_id" id="rangeSelect" required disabled>
                <option value="">-- Select Round first --</option>
            </select>

            <label>Archer</label>
            <select name="archer_id" required>
                <option value="">-- Select Archer --</option>
                <?php while($a=$archers->fetch_assoc()): ?>
                <option value="<?= $a['ArchersID'] ?>"><?= $a['ArcherFName'].' '.$a['ArcherLName'] ?></option>
                <?php endwhile; ?>
            </select>

            <label>Target Number</label>
            <select name="target_number" required>
                <?php for($i=1; $i<=50; $i++): ?>
                <option value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
            </select>

            <button type="button" onclick="proceedToStep2()">Next: Enter Scores</button>
        </form>
    </div>

    <div id="step2" class="step">
        <div id="scoreInputContainer"></div>
        <form id="scoreForm" action="insert_score.php" method="POST">
            <input type="hidden" name="competition_id" id="form_competition_id">
            <input type="hidden" name="round_id" id="form_round_id">
            <input type="hidden" name="range_id" id="form_range_id">
            <input type="hidden" name="archer_id" id="form_archer_id">
            <input type="hidden" name="target_number" id="form_target_number">
            <input type="hidden" name="division_id" id="form_division_id">
            <div id="hiddenInputs"></div>
            <div style="margin-top:20px;">
                <button type="button" onclick="backToStep1()">Back</button>
                <button type="button" onclick="validateAndSubmit()">Submit Score</button>
            </div>
        </form>
    </div>

    <script>
    function loadRanges(round_id) {
        const rangeSelect = document.getElementById('rangeSelect');
        if (!round_id) {
            rangeSelect.innerHTML = '<option value="">-- Select Round first --</option>';
            rangeSelect.disabled = true;
            return;
        }
        rangeSelect.disabled = true;
        rangeSelect.innerHTML = '<option>Loading...</option>';

        fetch(`insert_score.php?action=get_ranges&round_id=${round_id}`)
            .then(r => r.text())
            .then(html => {
                rangeSelect.innerHTML = html;
                rangeSelect.disabled = false;
            });
    }

    function proceedToStep2() {
        const comp = document.querySelector('[name="competition_id"]').value;
        const round = document.querySelector('[name="round_id"]').value;
        const range = document.querySelector('[name="range_id"]').value;
        const archer = document.querySelector('[name="archer_id"]').value;
        const target = document.querySelector('[name="target_number"]').value;

        if (!comp || !round || !range || !archer || !target) {
            alert("Please fill all fields.");
            return;
        }

        document.getElementById('form_competition_id').value = comp;
        document.getElementById('form_round_id').value = round;
        document.getElementById('form_range_id').value = range;
        document.getElementById('form_archer_id').value = archer;
        document.getElementById('form_target_number').value = target;

        fetch(`insert_score.php?action=get_division&archer_id=${archer}`)
            .then(r => r.json())
            .then(data => {
                document.getElementById('form_division_id').value = data.division_id;
                buildScoreInput(data.num_ends, data.arrows_per_end);
            });
    }

    function buildScoreInput(numEnds, arrowsPerEnd) {
        const container = document.getElementById('scoreInputContainer');
        const hidden = document.getElementById('hiddenInputs');
        container.innerHTML = '';
        hidden.innerHTML = '';

        for (let e = 1; e <= numEnds; e++) {
            const block = document.createElement('div');
            block.className = 'end-block';
            block.innerHTML = `<h3>End ${e}</h3><div class="arrows"></div>`;
            const arrowsDiv = block.querySelector('.arrows');

            for (let a = 1; a <= arrowsPerEnd; a++) {
                arrowsDiv.innerHTML += `<input type="text" class="arrow-input" name="end[${e}][arr${a}]" id="end${e}arr${a}" placeholder="-" required maxlength="1">`;
                hidden.innerHTML += `<input type="hidden" name="end[${e}][score${a}]" id="hidden_end${e}arr${a}">`;
            }

            block.innerHTML += scoreButtonsHTML(e, arrowsPerEnd);
            block.innerHTML += `<button type="button" class="clear-arrow-btn" onclick="clearArrow(${e}, ${arrowsPerEnd})">Delete</button>`;
            container.appendChild(block);
        }

        document.getElementById('step1').classList.remove('active');
        document.getElementById('step2').classList.add('active');
    }

    function scoreButtonsHTML(end, arrowsThisEnd){
        const scores=['M','1','2','3','4','5','6','7','8','9','10','X'];
        let html="<div class='score-buttons'>";
        scores.forEach(s=>{
            html+=`<button type='button' onclick='fillScore(${end},${arrowsThisEnd},"${s}")'>${s}</button>`;
        });
        html+="</div>";
        return html;
    }

    function fillScore(end, arrowsThisEnd, val){
        for(let i=1;i<=arrowsThisEnd;i++){
            let input=document.getElementById(`end${end}arr${i}`);
            if(input.value===''){input.value=val;return;}
        }
        alert("All arrows entered for End "+end);
    }

    function clearArrow(end, arrowsThisEnd){
        for(let i=arrowsThisEnd;i>=1;i--){
            let input=document.getElementById(`end${end}arr${i}`);
            if(input.value!==''){input.value='';return;}
        }
    }

    function validateAndSubmit() {
        const inputs = document.querySelectorAll('.arrow-input');
        let allFilled = true;
        inputs.forEach(input => {
            if (input.value.trim() === '') {
                allFilled = false;
                input.classList.add('missing');
            } else {
                input.classList.remove('missing');
            }
        });
        if (!allFilled) {
            alert("Please fill in all arrow scores.");
            return;
        }
        document.getElementById('scoreForm').submit();
    }

    function backToStep1(){
        document.getElementById('step2').classList.remove('active');
        document.getElementById('step1').classList.add('active');
    }
    </script>

    <?php include_once 'footer.inc'; ?>
</body>
</html>