<?php
include '../includes/db.php';
include '../includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}
// Fetch all exams for dropdown
$exams = $conn->query("SELECT id, title FROM exams ORDER BY start_time DESC");
?>
<div class="container mt-5">
    <h2 class="mb-4">View Exam Results</h2>
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
        <h4>Results for Selected Exam</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Score</th>
                        <th>Taken At</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $exam_id = intval($_GET['exam_id']);
                $results = $conn->query("SELECT r.*, u.full_name, u.email FROM results r JOIN users u ON r.user_id = u.id WHERE r.exam_id = $exam_id ORDER BY r.taken_at DESC");
                $i = 1;
                $score_data = [];
                if ($results && $results->num_rows > 0):
                    while ($row = $results->fetch_assoc()):
                        $score_data[] = $row['score'];
                ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['score']); ?></td>
                            <td><?php echo htmlspecialchars($row['taken_at']); ?></td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr><td colspan="5" class="text-center">No results found for this exam.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($score_data)): ?>
        <div class="my-5">
            <h5>Score Distribution</h5>
            <canvas id="scoreChart" height="100"></canvas>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        const ctx = document.getElementById('scoreChart').getContext('2d');
        const scores = <?php echo json_encode($score_data); ?>;
        // Group scores into bins (0-10, 11-20, ... 91-100)
        const bins = Array(10).fill(0);
        scores.forEach(s => {
            let idx = Math.floor(s / 10);
            if (idx > 9) idx = 9;
            bins[idx]++;
        });
        const labels = [
            '0-10', '11-20', '21-30', '31-40', '41-50',
            '51-60', '61-70', '71-80', '81-90', '91-100'
        ];
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Number of Students',
                    data: bins,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)'
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true, precision: 0 }
                }
            }
        });
        </script>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html> 