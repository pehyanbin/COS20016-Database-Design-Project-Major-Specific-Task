<?php
include_once 'header.inc';
include 'settings.php';

// Fetch data
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

    <!-- Step 1: Selection -->
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
                <option value="">-- Select Target Number --</option>
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
            </select>

            <label>Division</label>
            <select name="division_id" required>
                <option value="">-- Select Division --</option>
                <?php while($div = $divisions->fetch_assoc()): ?>
                    <option value="<?= $div['DivisionID'] ?>"><?= $div['DivisionName'] ?></option>
                <?php endwhile; ?>
            </select>

            <button type="button" class="next-btn" onclick="goToScoreEntry()">Next</button>
        </form>
    </div>

    <!-- Step 2: Enter Scores -->
    <div id="step2" class="step">
        <button type="button" class="back-btn" onclick="backToStep1()">Back</button>
        <form id="scoreForm" action="insert_score.php" method="post">
            <input type="hidden" name="competition_id">
            <input type="hidden" name="round_id">
            <input type="hidden" name="range_id">
            <input type="hidden" name="archer_id">
            <input type="hidden" name="target_number">
            <input type="hidden" name="division_id">

            <div id="endsContainer"></div>

            <button type="button" class="submit-btn" onclick="validateAndSubmit()">Submit Score</button>
        </form>
    </div>

    <script>
    function loadRanges(roundID) {
        const rangeSelect = document.getElementById('rangeSelect');
        if (!roundID) {
            rangeSelect.innerHTML = '<option value="">-- Select Round first --</option>';
            rangeSelect.disabled = true;
            return;
        }
        rangeSelect.innerHTML = '<option value="">Loading ranges...</option>';
        fetch('insert_score.php?action=get_ranges&round_id=' + roundID)
            .then(res => res.text())
            .then(html => {
                rangeSelect.innerHTML = html || '<option value="">-- No ranges found --</option>';
                rangeSelect.disabled = false;
            }).catch(() => {
                rangeSelect.innerHTML = '<option value="">Error loading ranges</option>';
            });
    }

    function goToScoreEntry() {
        const form = document.getElementById('selectionForm');
        if (!form.checkValidity()) { form.reportValidity(); return; }

        const selectedRange = form.range_id.selectedOptions[0];
        const numArrows = parseInt(selectedRange.dataset.numarr || 0);
        if (!numArrows || numArrows <= 0) { alert("Invalid range or missing arrow count."); return; }

        const arrowsPerEnd = 6;
        const totalEnds = Math.ceil(numArrows / arrowsPerEnd);

        const sf = document.getElementById('scoreForm');
        ['competition_id', 'round_id', 'archer_id', 'division_id', 'range_id', 'target_number'].forEach(name => {
            sf[name].value = form[name].value;
        });

        const container = document.getElementById('endsContainer');
        container.innerHTML = '';

        for (let e = 1; e <= totalEnds; e++) {
            const arrowsThisEnd = (e === totalEnds) ? (numArrows - arrowsPerEnd*(e-1)) : arrowsPerEnd;
            let block = document.createElement('div');
            block.className = 'end-block';
            block.innerHTML = `<h3>End ${e}</h3>`;

            for (let a = 1; a <= arrowsThisEnd; a++) {
                block.innerHTML += `<input readonly class="arrow-input" name='end[${e}][arr${a}]' id='end${e}arr${a}' placeholder='-' required>`;
            }

            block.innerHTML += scoreButtonsHTML(e, arrowsThisEnd);
            block.innerHTML += `<button type='button' class='clear-arrow-btn' onclick='clearArrow(${e}, ${arrowsThisEnd})'>Delete</button>`;
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
            alert("Please fill in all arrow scores before submitting.");
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
