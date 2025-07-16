<?php
include '../includes/db.php';
include '../includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$published_exams = $conn->query("SELECT * FROM exams WHERE published=1 ORDER BY start_time ASC");
?>
<div class="container mt-5">
    <h2 class="mb-4">Student Dashboard</h2>
    <h4>Published Exams</h4>
    <div class="table-responsive mb-5">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-primary">
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Duration (min)</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($published_exams && $published_exams->num_rows > 0):
                    while ($exam = $published_exams->fetch_assoc()):
                        $exam_id = $exam['id'];
                        $res = $conn->query("SELECT * FROM results WHERE exam_id=$exam_id AND user_id=$user_id");
                        $has_result = ($res && $res->num_rows > 0);
                        $score = $has_result ? $res->fetch_assoc()['score'] : null;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                        <td><?php echo htmlspecialchars($exam['description']); ?></td>
                        <td><?php echo htmlspecialchars($exam['start_time']); ?></td>
                        <td><?php echo htmlspecialchars($exam['end_time']); ?></td>
                        <td><?php echo htmlspecialchars($exam['duration']); ?></td>
                        <td>
                            <?php if ($has_result): ?>
                                <span class="badge bg-success">Completed</span>
                            <?php else: ?>
                                <span class="badge bg-primary">Available</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$has_result): ?>
                                <a href="take_exam.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-primary">Start</a>
                            <?php else: ?>
                                <span class="badge bg-success">Score: <?php echo htmlspecialchars($score); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile;
                else: ?>
                    <tr><td colspan="7" class="text-center">No published exams.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html> 