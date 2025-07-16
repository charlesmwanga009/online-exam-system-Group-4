<?php
include '../includes/db.php';
include '../includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}
$message = "";
$edit_mode = false;
$edit_question = null;
$edit_options = [];
// Handle delete
if (isset($_GET['delete']) && isset($_GET['exam_id'])) {
    $qid = intval($_GET['delete']);
    $conn->query("DELETE FROM options WHERE question_id=$qid");
    if ($conn->query("DELETE FROM questions WHERE id=$qid")) {
        $message = "<div class='alert alert-success'>Question deleted successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error deleting question.</div>";
    }
}
// Handle edit load
if (isset($_GET['edit']) && isset($_GET['exam_id'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['edit']);
    $qres = $conn->query("SELECT * FROM questions WHERE id=$edit_id");
    if ($qres && $qres->num_rows > 0) {
        $edit_question = $qres->fetch_assoc();
        if ($edit_question['question_type'] == 'mcq') {
            $ores = $conn->query("SELECT * FROM options WHERE question_id=$edit_id ORDER BY id ASC");
            while ($o = $ores->fetch_assoc()) {
                $edit_options[] = $o;
            }
        }
    }
}
// Handle add/edit form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['exam_id'])) {
    $exam_id = intval($_POST['exam_id']);
    $question_type = $_POST['question_type'];
    $question_text = $_POST['question_text'];
    if (isset($_POST['edit_id']) && $_POST['edit_id']) {
        // Update existing question
        $edit_id = intval($_POST['edit_id']);
        $sql = "UPDATE questions SET question_text=?, question_type=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $question_text, $question_type, $edit_id);
        if ($stmt->execute()) {
            if ($question_type === 'mcq') {
                // Update options
                $options = [$_POST['option1'], $_POST['option2'], $_POST['option3'], $_POST['option4']];
                $correct_option = intval($_POST['correct_option']);
                // Delete old options
                $conn->query("DELETE FROM options WHERE question_id=$edit_id");
                $all_ok = true;
                for ($i = 0; $i < 4; $i++) {
                    $is_correct = ($i + 1) === $correct_option ? 1 : 0;
                    $opt_sql = "INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)";
                    $opt_stmt = $conn->prepare($opt_sql);
                    $opt_stmt->bind_param("isi", $edit_id, $options[$i], $is_correct);
                    if (!$opt_stmt->execute()) {
                        $all_ok = false;
                    }
                }
                if ($all_ok) {
                    $message = "<div class='alert alert-success'>MCQ question updated successfully!</div>";
                } else {
                    $message = "<div class='alert alert-danger'>Question updated, but error saving options.</div>";
                }
            } else {
                // Written: just update question
                $conn->query("DELETE FROM options WHERE question_id=$edit_id");
                $message = "<div class='alert alert-success'>Written question updated successfully!</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Error updating question: " . htmlspecialchars($stmt->error) . "</div>";
        }
    } else {
        // Insert new question
        $sql = "INSERT INTO questions (exam_id, question_text, question_type) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $exam_id, $question_text, $question_type);
        if ($stmt->execute()) {
            $question_id = $stmt->insert_id;
            if ($question_type === 'mcq') {
                $options = [$_POST['option1'], $_POST['option2'], $_POST['option3'], $_POST['option4']];
                $correct_option = intval($_POST['correct_option']);
                $all_ok = true;
                for ($i = 0; $i < 4; $i++) {
                    $is_correct = ($i + 1) === $correct_option ? 1 : 0;
                    $opt_sql = "INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)";
                    $opt_stmt = $conn->prepare($opt_sql);
                    $opt_stmt->bind_param("isi", $question_id, $options[$i], $is_correct);
                    if (!$opt_stmt->execute()) {
                        $all_ok = false;
                    }
                }
                if ($all_ok) {
                    $message = "<div class='alert alert-success'>MCQ question and options added successfully!</div>";
                } else {
                    $message = "<div class='alert alert-danger'>Question added, but error saving options.</div>";
                }
            } else {
                $message = "<div class='alert alert-success'>Written question added successfully!</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Error: " . htmlspecialchars($stmt->error) . "</div>";
        }
    }
}
// Fetch all exams for dropdown
$exams = $conn->query("SELECT id, title FROM exams ORDER BY start_time DESC");
?>
<div class="container mt-5">
    <h2 class="mb-4">Manage Questions</h2>
    <?php echo $message; ?>
    <form method="get" class="mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Select Exam</label>
                <select name="exam_id" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Choose Exam --</option>
                    <?php while ($exam = $exams->fetch_assoc()): ?>
                        <option value="<?php echo $exam['id']; ?>" <?php if(isset($_GET['exam_id']) && $_GET['exam_id'] == $exam['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($exam['title']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </form>

    <?php if (isset($_GET['exam_id']) && $_GET['exam_id']): ?>
    <div class="card shadow mb-4">
        <div class="card-body">
            <h4><?php echo $edit_mode ? 'Edit' : 'Add'; ?> Question</h4>
            <form method="post">
                <input type="hidden" name="exam_id" value="<?php echo intval($_GET['exam_id']); ?>">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="edit_id" value="<?php echo intval($_GET['edit']); ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Question Type</label>
                    <select name="question_type" class="form-select" id="question_type" required onchange="toggleOptions()" <?php if($edit_mode) echo 'disabled'; ?>>
                        <option value="mcq" <?php if($edit_mode && $edit_question['question_type']=='mcq') echo 'selected'; ?>>Multiple Choice</option>
                        <option value="written" <?php if($edit_mode && $edit_question['question_type']=='written') echo 'selected'; ?>>Written</option>
                    </select>
                    <?php if($edit_mode): ?><input type="hidden" name="question_type" value="<?php echo htmlspecialchars($edit_question['question_type']); ?>"><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label">Question Text</label>
                    <textarea name="question_text" class="form-control" rows="2" required><?php echo $edit_mode ? htmlspecialchars($edit_question['question_text']) : ''; ?></textarea>
                </div>
                <div id="mcq_options">
                    <?php
                    $opt_vals = ["", "", "", ""];
                    $correct_val = 1;
                    if ($edit_mode && $edit_question['question_type'] == 'mcq' && count($edit_options) == 4) {
                        foreach ($edit_options as $i => $opt) {
                            $opt_vals[$i] = $opt['option_text'];
                            if ($opt['is_correct']) $correct_val = $i+1;
                        }
                    }
                    ?>
                    <div class="mb-3">
                        <label class="form-label">Option 1</label>
                        <input type="text" name="option1" class="form-control" value="<?php echo htmlspecialchars($opt_vals[0]); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Option 2</label>
                        <input type="text" name="option2" class="form-control" value="<?php echo htmlspecialchars($opt_vals[1]); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Option 3</label>
                        <input type="text" name="option3" class="form-control" value="<?php echo htmlspecialchars($opt_vals[2]); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Option 4</label>
                        <input type="text" name="option4" class="form-control" value="<?php echo htmlspecialchars($opt_vals[3]); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correct Option</label>
                        <select name="correct_option" class="form-select">
                            <option value="1" <?php if($correct_val==1) echo 'selected'; ?>>Option 1</option>
                            <option value="2" <?php if($correct_val==2) echo 'selected'; ?>>Option 2</option>
                            <option value="3" <?php if($correct_val==3) echo 'selected'; ?>>Option 3</option>
                            <option value="4" <?php if($correct_val==4) echo 'selected'; ?>>Option 4</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-success"><?php echo $edit_mode ? 'Update' : 'Add'; ?> Question</button>
                <?php if($edit_mode): ?>
                    <a href="?exam_id=<?php echo intval($_GET['exam_id']); ?>" class="btn btn-secondary ms-2">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <script>
    function toggleOptions() {
        var type = document.getElementById('question_type').value;
        document.getElementById('mcq_options').style.display = (type === 'mcq') ? 'block' : 'none';
    }
    document.addEventListener('DOMContentLoaded', function() {
        toggleOptions();
    });
    </script>

    <!-- List of existing questions -->
    <h4 class="mb-3">Existing Questions</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-primary">
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>Question</th>
                    <th>Options / Answer</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $qid = 1;
                $questions = $conn->query("SELECT * FROM questions WHERE exam_id=" . intval($_GET['exam_id']) . " ORDER BY id ASC");
                if ($questions && $questions->num_rows > 0):
                    while ($q = $questions->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $qid++; ?></td>
                    <td><?php echo strtoupper(htmlspecialchars($q['question_type'])); ?></td>
                    <td><?php echo htmlspecialchars($q['question_text']); ?></td>
                    <td>
                        <?php if ($q['question_type'] == 'mcq'):
                            $opts = $conn->query("SELECT * FROM options WHERE question_id=" . intval($q['id']));
                            while ($opt = $opts->fetch_assoc()): ?>
                                <div><?php echo ($opt['is_correct'] ? '<b>' : '') . htmlspecialchars($opt['option_text']) . ($opt['is_correct'] ? ' <span class=\'badge bg-success\'>Correct</span></b>' : ''); ?></div>
                            <?php endwhile;
                        else:
                            echo '<em>Written answer</em>';
                        endif; ?>
                    </td>
                    <td>
                        <a href="?exam_id=<?php echo intval($_GET['exam_id']); ?>&edit=<?php echo $q['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="?exam_id=<?php echo intval($_GET['exam_id']); ?>&delete=<?php echo $q['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this question?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile;
                else: ?>
                <tr><td colspan="5" class="text-center">No questions found for this exam.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
</body>
</html> 