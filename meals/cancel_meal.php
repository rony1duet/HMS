<?php
session_start();
require_once '../config/database.php';
require_once '../includes/Session.php';

Session::init();

if (!Session::isLoggedIn() || Session::getUserRole() !== 'student') {
    header('Location: /HMS/');
    exit();
}

// Verify CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = 'Invalid request. Please try again.';
        header('Location: index.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meal_date'])) {
    $student_id = Session::getUserId();
    $meal_date = $_POST['meal_date'];

    // Validate meal date
    if (!strtotime($meal_date)) {
        $_SESSION['error_message'] = 'Invalid meal date.';
        header('Location: index.php');
        exit();
    }

    // Check if it's past 10:00 PM for next day cancellation
    $current_time = new DateTime();
    $meal_datetime = new DateTime($meal_date);
    $cutoff_time = new DateTime($current_time->format('Y-m-d') . ' 22:00:00');
    $next_day = clone $current_time;
    $next_day->modify('+1 day');

    if (
        $current_time > $cutoff_time &&
        $meal_datetime->format('Y-m-d') === $next_day->format('Y-m-d')
    ) {
        $_SESSION['error_message'] = 'Cannot cancel meals for tomorrow after 9:30 PM.';
        header('Location: index.php');
        exit();
    }

    try {
        // Begin transaction
        $conn->beginTransaction();

        // Extract day from meal date
        $day = date('j', strtotime($meal_date));
        $month = date('m', strtotime($meal_date));
        $year = date('Y', strtotime($meal_date));
        $day_column = 'day' . $day;

        // Check if meal exists, is confirmed, and hasn't been served yet
        $current_time = new DateTime();
        $meal_date = new DateTime($meal_date);
        $meal_cutoff = clone $meal_date;
        $meal_cutoff->setTime(21, 30); // 9:30 PM cutoff

        if ($current_time > $meal_cutoff) {
            throw new Exception('Cannot cancel meal after serving time (9:30 PM). Please cancel before 9:30 PM.');
        }

        $check_sql = "SELECT id FROM meal_schedules 
                      WHERE student_id = :student_id 
                      AND month = :month 
                      AND year = :year 
                      AND $day_column = 1 
                      FOR UPDATE";
        $stmt = $conn->prepare($check_sql);
        $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindValue(':month', $month, PDO::PARAM_INT);
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->execute();

        if (!$stmt->fetch()) {
            throw new Exception('No confirmed meal found for the selected date.');
        }

        // Update meal status to cancelled (set day column to 0)
        $update_sql = "UPDATE meal_schedules 
                       SET $day_column = 0, updated_at = NOW() 
                       WHERE student_id = :student_id 
                       AND month = :month 
                       AND year = :year";
        $stmt = $conn->prepare($update_sql);
        $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindValue(':month', $month, PDO::PARAM_INT);
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->execute();

        // Refund credits to student meal credits
        $meal_cost = 50.00; // Cost per meal in Taka
        $refund_sql = "UPDATE student_meal_credits 
                       SET credits = credits + :meal_cost 
                       WHERE student_id = :student_id";
        $stmt = $conn->prepare($refund_sql);
        $stmt->bindValue(':meal_cost', $meal_cost, PDO::PARAM_STR);
        $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();

        // Update meal statistics
        // Month and year are already set above, no need to recalculate
        $stats_sql = "UPDATE meal_statistics 
                      SET total_meals = total_meals - 1,
                          total_cost = total_cost - :meal_cost 
                      WHERE student_id = :student_id 
                      AND month = :month 
                      AND year = :year";
        $stmt = $conn->prepare($stats_sql);
        $stmt->bindValue(':meal_cost', $meal_cost, PDO::PARAM_STR);
        $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindValue(':month', $month, PDO::PARAM_INT);
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        $_SESSION['success_message'] = 'Meal cancelled successfully. 50 Taka has been credited to your account.';
    } catch (Exception $e) {
        // Log the error
        error_log('Meal Cancellation Error: ' . $e->getMessage());

        // Rollback transaction on error
        $conn->rollBack();
        $_SESSION['error_message'] = $e->getMessage();
    } catch (PDOException $e) {
        // Log database errors
        error_log('Database Error: ' . $e->getMessage());

        // Rollback transaction on error
        $conn->rollBack();
        $_SESSION['error_message'] = 'A database error occurred. Please try again later.';
    }

    header('Location: index.php');
    exit();
}

header('Location: index.php');
exit();
