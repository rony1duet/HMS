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

// Process meal cancellations first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_dates'])) {
    $student_id = Session::getUserId();
    $cancel_dates = json_decode($_POST['cancel_dates'], true);

    if (is_array($cancel_dates)) {
        foreach ($cancel_dates as $meal_date) {
            try {
                // Begin transaction
                $conn->beginTransaction();

                // Extract day from meal date
                $day = date('j', strtotime($meal_date));
                $month = date('m', strtotime($meal_date));
                $year = date('Y', strtotime($meal_date));
                $day_column = 'day' . $day;

                // Check if meal exists and is confirmed
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

                if ($stmt->fetch()) {
                    // Update meal status to cancelled
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

                    // Refund credits
                    $meal_cost = 50.00;
                    $refund_sql = "UPDATE student_meal_credits 
                                   SET credits = credits + :meal_cost 
                                   WHERE student_id = :student_id";
                    $stmt = $conn->prepare($refund_sql);
                    $stmt->bindValue(':meal_cost', $meal_cost, PDO::PARAM_STR);
                    $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
                    $stmt->execute();

                    // Update meal statistics
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
                }

                $conn->commit();
            } catch (Exception $e) {
                $conn->rollBack();
                error_log('Meal Cancellation Error: ' . $e->getMessage());
            }
        }
    }
}

// Process meal scheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule'])) {
    $student_id = Session::getUserId();
    $schedule = json_decode($_POST['schedule'], true);

    // Validate JSON decoding
    if (json_last_error() !== JSON_ERROR_NONE) {
        $_SESSION['error_message'] = 'Invalid JSON data.';
        header('Location: index.php');
        exit();
    }

    // Validate schedule data
    if (!is_array($schedule) || empty($schedule)) {
        $_SESSION['error_message'] = 'Invalid meal schedule data.';
        header('Location: index.php');
        exit();
    }

    // Validate each day in schedule
    foreach ($schedule as $day) {
        if (!is_numeric($day) || $day < 1 || $day > 31) {
            $_SESSION['error_message'] = 'Invalid day in meal schedule.';
            header('Location: index.php');
            exit();
        }
    }

    $current_month = date('m');
    $current_year = date('Y');

    try {
        // Begin transaction
        $conn->beginTransaction();

        // Get student's current credits and validate credit record
        $credit_sql = "SELECT credits, last_recharge FROM student_meal_credits WHERE student_id = :student_id FOR UPDATE";
        $stmt = $conn->prepare($credit_sql);
        $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            throw new Exception('Student meal credit record not found. Please contact support.');
        }

        $credits = $result['credits'];

        // Validate credit amount
        if ($credits <= 0) {
            throw new Exception('Your credit balance is zero. Please recharge your account.');
        }

        // Calculate and validate total cost
        $meal_cost = 50.00; // Cost per meal in Taka
        $total_cost = number_format(count($schedule) * $meal_cost, 2, '.', '');

        // Validate total cost
        if ($total_cost <= 0) {
            throw new Exception('Invalid meal cost calculation.');
        }

        // Validate meal dates
        $current_date = date('Y-m-d');
        foreach ($schedule as $day) {
            $meal_date = date('Y-m-d', strtotime("$current_year-$current_month-$day"));
            if ($meal_date < $current_date) {
                throw new Exception('Cannot schedule meals for past dates.');
            }
        }

        if ($credits < $total_cost) {
            throw new Exception('Insufficient credits. Please recharge your account.');
        }

        // Deduct credits
        $update_credits_sql = "UPDATE student_meal_credits 
                              SET credits = credits - :total_cost 
                              WHERE student_id = :student_id";
        $stmt = $conn->prepare($update_credits_sql);
        $stmt->bindValue(':total_cost', $total_cost, PDO::PARAM_STR);
        $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();

        // Insert meal schedules with validation
        $update_sql = "INSERT INTO meal_schedules (student_id, month, year) 
                       VALUES (:student_id, :month, :year) 
                       ON DUPLICATE KEY UPDATE updated_at = NOW()";
        $stmt = $conn->prepare($update_sql);
        $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindValue(':month', $current_month, PDO::PARAM_INT);
        $stmt->bindValue(':year', $current_year, PDO::PARAM_INT);
        $stmt->execute();

        // Update the days in the schedule
        foreach ($schedule as $day) {
            $day_column = 'day' . $day;
            $update_day_sql = "UPDATE meal_schedules 
                               SET $day_column = 1 
                               WHERE student_id = :student_id 
                               AND month = :month 
                               AND year = :year";
            $stmt = $conn->prepare($update_day_sql);
            $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->bindValue(':month', $current_month, PDO::PARAM_INT);
            $stmt->bindValue(':year', $current_year, PDO::PARAM_INT);
            $stmt->execute();
        }

        // Update meal statistics
        $stats_sql = "INSERT INTO meal_statistics (student_id, month, year, total_meals, total_cost) 
                      VALUES (:student_id, :month, :year, :meal_count, :total_cost) 
                      ON DUPLICATE KEY UPDATE 
                      total_meals = CASE 
                          WHEN total_meals + :meal_count < 0 THEN 0 
                          ELSE total_meals + :meal_count 
                      END,
                      total_cost = CASE 
                          WHEN total_cost + :total_cost < 0 THEN 0 
                          ELSE total_cost + :total_cost 
                      END";

        $meal_count = count($schedule);
        $stmt = $conn->prepare($stats_sql);
        $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindValue(':month', $current_month, PDO::PARAM_INT);
        $stmt->bindValue(':year', $current_year, PDO::PARAM_INT);
        $stmt->bindValue(':meal_count', $meal_count, PDO::PARAM_INT);
        $stmt->bindValue(':total_cost', $total_cost, PDO::PARAM_STR);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        $_SESSION['success_message'] = 'Meal schedule updated successfully!';
    } catch (Exception $e) {
        // Log the error
        error_log('Meal Schedule Error: ' . $e->getMessage());

        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error_message'] = $e->getMessage();
    } catch (PDOException $e) {
        // Log database errors
        error_log('Database Error: ' . $e->getMessage());

        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error_message'] = 'A database error occurred. Please try again later.';
    }

    header('Location: index.php');
    exit();
}

header('Location: index.php');
exit();
