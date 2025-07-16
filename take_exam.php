<?php
include '../includes/db.php';
include '../includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}
if (!isset($_GET['exam_id'])) {
    echo '<div class="container mt-5"><div class="alert alert-danger">No exam selected.</div></div>';
    exit();
}
$exam_id = intval($_GET['exam_id']);
$user_id = $_SESSION['user_id'];
$now = date('Y-m-d H:i:s');
// Fetch exam (only published)
$exam = $conn->query("SELECT * FROM exams WHERE id=$exam_id AND published=1")->fetch_assoc();
if (!$exam) {
    echo '<div class="container mt-5"><div class="alert alert-danger">This exam is not published yet.</div></div>';
    exit();
}
// Prevent multiple attempts
$already = $conn->query("SELECT * FROM results WHERE exam_id=$exam_id AND user_id=$user_id");
if ($already && $already->num_rows > 0) {
    echo '<div class="container mt-5"><div class="alert alert-info">You have already submitted this exam. <a href=\'dashboard.php\'>Go to Dashboard</a></div></div>';
    exit();
}
// Fetch questions
$questions = $conn->query("SELECT * FROM questions WHERE exam_id=$exam_id ORDER BY id ASC");
$submitted = false;
$score = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = true;
    $total_mcq = 0;
    $correct_mcq = 0;
    if ($questions && $questions->num_rows > 0) {
        mysqli_data_seek($questions, 0); // reset pointer
        while ($q = $questions->fetch_assoc()) {
            $qid = $q['id'];
            $ans = isset($_POST['q_' . $qid]) ? $_POST['q_' . $qid] : null;
            $answer_text = null;
            if ($q['question_type'] == 'mcq') {
                $total_mcq++;
                // Save answer
                $conn->query("INSERT INTO answers (user_id, question_id, answer_text, submitted_at) VALUES ($user_id, $qid, '" . $conn->real_escape_string($ans) . "', '$now')");
                // Check if correct
                $opt = $conn->query("SELECT is_correct FROM options WHERE id=" . intval($ans));
                if ($opt && $opt->num_rows > 0 && $opt->fetch_assoc()['is_correct']) {
                    $correct_mcq++;
                }
            } else {
                $answer_text = $ans;
                $conn->query("INSERT INTO answers (user_id, question_id, answer_text, submitted_at) VALUES ($user_id, $qid, '" . $conn->real_escape_string($answer_text) . "', '$now')");
            }
        }
    }
    // Calculate score (MCQ only, as percentage)
    $score = $total_mcq > 0 ? round(($correct_mcq / $total_mcq) * 100, 2) : 0;
    // Save result
    $conn->query("INSERT INTO results (user_id, exam_id, score, taken_at) VALUES ($user_id, $exam_id, $score, '$now')");
}
?>
<div class="container mt-5">
    <h2 class="mb-4">Take Exam: <?php echo htmlspecialchars($exam['title']); ?></h2>
    <?php if ($submitted): ?>
        <div class="alert alert-success">Your answers have been submitted!<br>Score (MCQ): <b><?php echo $score; ?>%</b><br><a href="dashboard.php">Back to Dashboard</a></div>
    <?php else: ?>
    <div class="alert alert-info mb-4">
        <b>Time Remaining: <span id="timer"></span></b>
    </div>
    <form id="examForm" method="post" action="">
        <?php if ($questions && $questions->num_rows > 0):
            mysqli_data_seek($questions, 0);
            $qnum = 1;
            while ($q = $questions->fetch_assoc()): ?>
                <div class="mb-4">
                    <b>Q<?php echo $qnum++; ?>:</b> <?php echo htmlspecialchars($q['question_text']); ?>
                    <?php if ($q['question_type'] == 'mcq'):
                        $opts = $conn->query("SELECT * FROM options WHERE question_id=" . intval($q['id']));
                        while ($opt = $opts->fetch_assoc()): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="q_<?php echo $q['id']; ?>" value="<?php echo $opt['id']; ?>" id="opt_<?php echo $opt['id']; ?>">
                                <label class="form-check-label" for="opt_<?php echo $opt['id']; ?>">
                                    <?php echo htmlspecialchars($opt['option_text']); ?>
                                </label>
                            </div>
                        <?php endwhile;
                    else: ?>
                        <textarea class="form-control mt-2" name="q_<?php echo $q['id']; ?>" rows="2" placeholder="Your answer..."></textarea>
                    <?php endif; ?>
                </div>
            <?php endwhile;
        else: ?>
            <div class="alert alert-warning">No questions found for this exam.</div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Submit Exam</button>
    </form>
    <script>
    // Timer logic
    let duration = <?php echo (int)$exam['duration']; ?> * 60; // seconds
    let timerDisplay = document.getElementById('timer');
    function updateTimer() {
        let min = Math.floor(duration / 60);
        let sec = duration % 60;
        timerDisplay.textContent = `${min}:${sec.toString().padStart(2, '0')}`;
        if (duration <= 0) {
            clearInterval(timerInterval);
            document.getElementById('examForm').submit();
        }
        duration--;
    }
    updateTimer();
    let timerInterval = setInterval(updateTimer, 1000);
    </script>
    <?php endif; ?>
</div>
</body>
</html> 