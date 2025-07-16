<?php
include '../includes/db.php';
include '../includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $duration = $_POST['duration'];
    $created_by = $_SESSION['user_id'];

    $sql = "INSERT INTO exams (title, description, start_time, end_time, duration, created_by) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $title, $description, $start_time, $end_time, $duration, $created_by);
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Exam created successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error: " . htmlspecialchars($stmt->error) . "</div>";
    }
}
if (isset($_GET['publish']) && isset($_GET['exam_id'])) {
    $exam_id = intval($_GET['exam_id']);
    $conn->query("UPDATE exams SET published=1 WHERE id=$exam_id");
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'success',title:'Exam Published!'}).then(()=>{window.location='create_exam.php';});</script>";
    exit();
}
// Fetch all exams
$exams = $conn->query("SELECT * FROM exams ORDER BY start_time DESC");
?>
<div class="container mt-5">
    <h2 class="mb-4">Create New Exam</h2>
    <?php echo $message; ?>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Exam Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Start Time</label>
                <input type="datetime-local" name="start_time" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">End Time</label>
                <input type="datetime-local" name="end_time" class="form-control" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Duration (minutes)</label>
            <input type="number" name="duration" class="form-control" min="1" required>
        </div>
        <button type="submit" class="btn btn-primary">Create Exam</button>
        <a href="dashboard.php" class="btn btn-secondary ms-2">Back to Dashboard</a>
    </form>

    <hr class="my-5">
    <h3 class="mb-3">Existing Exams</h3>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-primary">
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Duration (min)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($exams && $exams->num_rows > 0): ?>
                    <?php while ($exam = $exams->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($exam['title']); ?></td>
                            <td><?php echo htmlspecialchars($exam['description']); ?></td>
                            <td><?php echo htmlspecialchars($exam['start_time']); ?></td>
                            <td><?php echo htmlspecialchars($exam['end_time']); ?></td>
                            <td><?php echo htmlspecialchars($exam['duration']); ?></td>
                            <td>
                                <a href="create_exam.php?publish=1&exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-success" <?php if($exam['published']) echo 'disabled'; ?>><?php echo $exam['published'] ? 'Published' : 'Publish'; ?></a>
                                <a href="#" class="btn btn-sm btn-warning disabled">Edit</a>
                                <a href="#" class="btn btn-sm btn-danger disabled">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">No exams found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html> 