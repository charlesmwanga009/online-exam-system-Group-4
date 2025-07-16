<?php
include 'includes/db.php';
include 'includes/header.php';
$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    try {
        $sql = "INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $username, $password, $full_name, $email, $role);
        $stmt->execute();
        $success = true;
    } catch (mysqli_sql_exception $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $message = "duplicate";
        } else {
            $message = "Error: " . $e->getMessage();
        }
    }
}
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body">
                    <h2 class="mb-4 text-center">Register</h2>
                    <form method="post">
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Role</label>
                            <select name="role" class="form-control" required>
                                <option value="student">Student</option>
                                <option value="admin">Admin/Teacher</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if($success): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
  icon: 'success',
  title: 'Registration Successful!',
  text: 'You can now log in.',
  timer: 2000,
  showConfirmButton: false
}).then(() => {
  window.location.href = 'index.php';
});
setTimeout(function(){ window.location.href = 'index.php'; }, 2000);
</script>
<?php elseif($message === 'duplicate'): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
  icon: 'error',
  title: 'Registration Failed',
  text: 'This email or username is already registered. Please use a different one.'
});
</script>
<?php elseif($message): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
  icon: 'error',
  title: 'Registration Failed',
  text: <?php echo json_encode($message); ?>
});
</script>
<?php endif; ?>
</body>
</html> 