<?php
$title = 'Meal Plan';
session_start();
require_once '../config/database.php';
require_once '../includes/Session.php';
require_once '../models/User.php';

$user = new User($conn);

Session::init();

if (!Session::isLoggedIn() || Session::getUserRole() !== 'student') {
    header('Location: /HMS/');
    exit();
}

$profileStatus = $user->getProfileStatus($_SESSION['slug']);

if ($profileStatus === 'not_updated') {
    header('Location: /HMS/profiles/student/');
    exit();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get student's current credits
$slug = Session::getSlug();
$credit_sql = "SELECT mc.credits 
              FROM student_meal_credits mc 
              INNER JOIN student_profiles sp ON mc.student_id = sp.id 
              WHERE sp.slug = :slug";
$stmt = $conn->prepare($credit_sql);
$stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$credits = $result['credits'] ?? 0;

// Get today's menu
$today_day = date('w'); // 0 (Sunday) to 6 (Saturday)
$menu_sql = "SELECT meal_type, items FROM meal_menu WHERE day_of_week = :day";
$stmt = $conn->prepare($menu_sql);
$stmt->bindValue(':day', $today_day, PDO::PARAM_INT);
$stmt->execute();
$menu_items = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $menu_items[$row['meal_type']] = explode(',', $row['items']);
}

// Get meal statistics for current month
$current_month = date('m');
$current_year = date('Y');
$stats_sql = "SELECT total_meals, total_cost FROM meal_statistics 
             WHERE student_id = (SELECT id FROM student_profiles WHERE slug = :slug)
             AND month = :month AND year = :year";
$stmt = $conn->prepare($stats_sql);
$stmt->bindValue(':slug', Session::getSlug(), PDO::PARAM_STR);
$stmt->bindValue(':month', $current_month, PDO::PARAM_INT);
$stmt->bindValue(':year', $current_year, PDO::PARAM_INT);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
// Calculate total meals and total cost for the current month
$stats_sql = "SELECT SUM(total_meals) as total_meals, SUM(total_cost) as total_cost
             FROM meal_statistics
             WHERE student_id = (SELECT id FROM student_profiles WHERE slug = :slug)
             AND month = :month AND year = :year";
$stmt = $conn->prepare($stats_sql);
$stmt->bindValue(':slug', Session::getSlug(), PDO::PARAM_STR);
$stmt->bindValue(':month', $current_month, PDO::PARAM_INT);
$stmt->bindValue(':year', $current_year, PDO::PARAM_INT);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_meals'] = $stats['total_meals'] ?? 0;
$stats['total_cost'] = $stats['total_cost'] ?? 0;

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Meal Plan Calendar -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Meal Plan Calendar - <span id="current-month"></span></h5>
                </div>
                <div class="card-body">
                    <!-- Credit Display Section -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-0">Credits: <span id="credit-display"><?php echo htmlspecialchars($credits); ?></span> Taka</h5>
                            <div class="progress mt-2" style="height: 10px;">
                                <div id="credit-progress" class="progress-bar bg-success" role="progressbar" style="width: 100%;"></div>
                            </div>
                        </div>
                        <button class="btn btn-success" id="recharge-button">Recharge</button>
                    </div>

                    <!-- Meal Selection Summary -->
                    <div class="alert alert-info justify-content-between align-items-center mb-3" id="meal-summary" style="display: flex;">
                        <span>Selected Meals: <strong id="selected-meals-count">0</strong></span>
                        <span>Total Cost: <strong id="total-cost">0</strong> Taka</span>
                    </div>

                    <!-- Calendar Section -->
                    <div id="calendar" class="table-responsive my-4">
                        <div id="calendar-loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <table class="table table-bordered text-center" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Sun</th>
                                    <th>Mon</th>
                                    <th>Tue</th>
                                    <th>Wed</th>
                                    <th>Thu</th>
                                    <th>Fri</th>
                                    <th>Sat</th>
                                </tr>
                            </thead>
                            <tbody id="calendar-body">
                                <!-- Calendar days dynamically generated -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Additional Options -->
                    <div class="d-flex justify-content-between align-items-center">
                        <button class="btn btn-outline-primary" id="select-all-button">Select Remaining Days</button>
                        <form method="POST" action="meal_status.php" class="d-inline">
                            <input type="hidden" id="schedule-input" name="schedule">
                            <input type="hidden" id="cancel-input" name="cancel_dates">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <button type="submit" class="btn btn-primary" id="submit-schedule">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meal Menu Section -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Today's Menu</h5>
                </div>
                <div class="card-body">
                    <div class="meal-time mb-4">
                        <h6 class="text-muted">Lunch (12:30 PM - 2:30 PM)</h6>
                        <ul class="list-unstyled">
                            <?php if (isset($menu_items['lunch'])): ?>
                                <?php foreach ($menu_items['lunch'] as $item): ?>
                                    <li>• <?php echo htmlspecialchars(trim($item)); ?></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>Menu not available</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="meal-time">
                        <h6 class="text-muted">Dinner (7:30 PM - 9:30 PM)</h6>
                        <ul class="list-unstyled">
                            <?php if (isset($menu_items['dinner'])): ?>
                                <?php foreach ($menu_items['dinner'] as $item): ?>
                                    <li>• <?php echo htmlspecialchars(trim($item)); ?></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>Menu not available</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Meal Statistics -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Meal Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total Meals</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($stats['total_meals']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Cost Per Meal</span>
                        <span class="fw-bold">৳50</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>This Month's Cost</span>
                        <span class="fw-bold">৳<?php echo htmlspecialchars($stats['total_cost']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    // Meal Calendar functionality
    class MealCalendar {
        constructor() {
            this.calendarBody = document.getElementById("calendar-body");
            this.creditDisplay = document.getElementById("credit-display");
            this.creditProgress = document.getElementById("credit-progress");
            this.selectedMealsCount = document.getElementById("selected-meals-count");
            this.totalCost = document.getElementById("total-cost");
            this.currentMonthDisplay = document.getElementById("current-month");
            this.selectAllButton = document.getElementById("select-all-button");
            this.scheduleInput = document.getElementById("schedule-input");
            this.cancelInput = document.getElementById("cancel-input");
            this.calendarLoading = document.getElementById("calendar-loading");
            this.calendarTable = document.querySelector("#calendar table");

            this.today = new Date();
            this.firstDay = new Date(
                this.today.getFullYear(),
                this.today.getMonth(),
                1
            );
            this.lastDay = new Date(
                this.today.getFullYear(),
                this.today.getMonth() + 1,
                0
            );

            this.totalCredits = parseFloat(this.creditDisplay.innerText);
            this.mealCost = 50; // Cost per meal in Taka
            this.cancelRefund = 50; // Refund amount when canceling (changed from 100 to 50)
            this.selectedDates = new Set();
            this.minimumBalance = 0; // Minimum balance required
            this.scheduledMeals = new Set(); // Track scheduled meals
            this.virtualCredits = 0; // Track credits from cancellations

            this.init();
        }

        init() {
            this.displayCurrentMonth();
            this.renderCalendar();
            this.setupEventListeners();
            this.loadScheduledMeals();
        }

        displayCurrentMonth() {
            this.currentMonthDisplay.innerText = this.today.toLocaleDateString(
                "en-US", {
                    month: "long",
                    year: "numeric",
                }
            );
        }

        renderCalendar() {
            this.calendarBody.innerHTML = "";
            let day = 1;

            for (let week = 0; week < 6; week++) {
                const row = document.createElement("tr");

                for (let dayOfWeek = 0; dayOfWeek < 7; dayOfWeek++) {
                    const cell = document.createElement("td");

                    if (
                        (week === 0 && dayOfWeek < this.firstDay.getDay()) ||
                        day > this.lastDay.getDate()
                    ) {
                        cell.innerHTML = "";
                    } else {
                        const date = new Date(
                            this.today.getFullYear(),
                            this.today.getMonth(),
                            day
                        );
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        const isPast = date < today;
                        const isToday = date.getTime() === today.getTime();

                        if (isPast && !this.scheduledMeals.has(day)) {
                            // Past date with no meal scheduled
                            cell.innerHTML = `<div class="p-1 text-danger">
                        <i class="fa-solid fa-circle-xmark"></i>
                        </div>`;
                            cell.style.backgroundColor = "#f8f9fa";
                            cell.style.pointerEvents = "none";
                        } else if (isPast && this.scheduledMeals.has(day)) {
                            // Past date with a meal scheduled
                            cell.innerHTML = `<div class="p-1 text-secondary">
                        <i class="fa-solid fa-circle-check"></i>
                        </div>`;
                            cell.style.backgroundColor = "#f8f9fa";
                            cell.style.pointerEvents = "none";
                        } else if (isToday && this.scheduledMeals.has(day)) {
                            // Today's date with a meal scheduled
                            cell.innerHTML = `<div class="p-1 text-success">
                        <i class="fa-solid fa-circle-check"></i>
                        </div>`;
                            cell.style.backgroundColor = "#f8f9fa";
                            cell.style.pointerEvents = "none";
                        } else if (isToday && !this.scheduledMeals.has(day)) {
                            // Today's date with no meal scheduled
                            cell.innerHTML = `<div class="p-1 text-secondary">
                        <i class="fa-solid fa-circle-xmark"></i>
                        </div>`;
                            cell.style.backgroundColor = "#f8f9fa";
                            cell.style.pointerEvents = "none";
                        } else {
                            // Future date
                            cell.innerHTML = `<div class="p-1">
                        <strong>${day}</strong>
                        </div>`;
                            cell.style.cursor = "pointer";
                            cell.dataset.date = day;

                            if (this.scheduledMeals.has(day)) {
                                // Future date with a meal scheduled
                                this.markDateAsScheduled(cell, day, isToday);
                            }
                        }
                        day++;
                    }
                    row.appendChild(cell);
                }
                this.calendarBody.appendChild(row);
                if (day > this.lastDay.getDate()) break;
            }
        }

        setupEventListeners() {
            this.calendarBody.addEventListener("click", (event) => {
                const cell = event.target.closest("td");
                if (cell && cell.dataset.date) {
                    const day = parseInt(cell.dataset.date, 10);
                    this.toggleDateSelection(day, cell);
                }
            });

            this.selectAllButton.addEventListener("click", () =>
                this.selectRemainingDays()
            );

            document.getElementById("recharge-button").addEventListener("click", () => {
                window.location.href = "recharge.php";
            });

            document.querySelector('form').addEventListener('submit', (event) => {
                event.preventDefault();
                const cancelDates = [];
                const scheduleDates = [];
                this.selectedDates.forEach(day => {
                    if (this.scheduledMeals.has(day)) {
                        const dateStr = `${this.today.getFullYear()}-${String(this.today.getMonth() + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                        cancelDates.push(dateStr);
                    } else {
                        scheduleDates.push(day);
                    }
                });
                document.getElementById('cancel-input').value = JSON.stringify(cancelDates);
                document.getElementById('schedule-input').value = JSON.stringify(scheduleDates);
                event.target.submit();
            });
        }

        toggleDateSelection(day, cell) {
            const date = new Date(this.today.getFullYear(), this.today.getMonth(), day);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const isPast = date < today;
            const isToday = date.getTime() === today.getTime();
            const now = new Date();
            const cutoffTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 22, 0, 0);
            const tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000);

            if (isPast) {
                this.showAlert("Cannot select past dates.");
                return;
            }

            if (now > cutoffTime &&
                date.getDate() === tomorrow.getDate() &&
                date.getMonth() === tomorrow.getMonth() &&
                date.getFullYear() === tomorrow.getFullYear()) {
                this.showAlert("Cannot select next day's meal after 10 PM.");
                return;
            }

            if (this.scheduledMeals.has(day)) {
                // Mark meal for cancellation
                if (this.selectedDates.has(day)) {
                    this.selectedDates.delete(day);
                    cell.innerHTML = `<div class="p-1 text-primary"><i class="fa-solid fa-circle-check"></i></div>`;
                    // Remove virtual credit when un-canceling
                    this.virtualCredits -= this.cancelRefund;
                    this.updateCredits();
                } else {
                    this.selectedDates.add(day);
                    cell.innerHTML = `<div class="p-1 text-warning"><i class="fa-solid fa-circle-minus"></i></div>`;
                    // Add virtual credit when canceling
                    this.virtualCredits += this.cancelRefund;
                    this.updateCredits();
                }
            } else {
                // Schedule meal - check both real and virtual credits
                const totalAvailableCredits = this.totalCredits + this.virtualCredits;
                const creditsAfterSelection = totalAvailableCredits - (this.getNetSelectedCount() + 1) * this.mealCost;

                if (creditsAfterSelection < this.minimumBalance) {
                    this.showAlert("Insufficient balance for selecting this date.");
                    return;
                }

                if (this.selectedDates.has(day)) {
                    this.selectedDates.delete(day);
                    cell.innerHTML = `<div class="p-1"><strong>${day}</strong></div>`;
                } else {
                    this.selectedDates.add(day);
                    cell.innerHTML = `<div class="p-1 ${isToday ? "text-success" : "text-success"}">
                    <i class="fa-solid fa-circle-check"></i>
                </div>`;
                }
                this.updateCredits();
            }
        }

        getNetSelectedCount() {
            let newSelections = 0;
            let cancellations = 0;

            this.selectedDates.forEach(day => {
                if (this.scheduledMeals.has(day)) {
                    cancellations++;
                } else {
                    newSelections++;
                }
            });

            return newSelections - cancellations;
        }

        calculateTotalCost() {
            let cost = 0;
            this.selectedDates.forEach(day => {
                if (!this.scheduledMeals.has(day)) {
                    cost += this.mealCost;
                }
            });
            return cost;
        }

        selectRemainingDays() {
            const totalAvailableCredits = this.totalCredits + this.virtualCredits;
            const availableDays = Math.floor(
                (totalAvailableCredits - this.minimumBalance) / this.mealCost
            );
            if (availableDays <= 0) {
                this.showAlert("Insufficient balance for selecting remaining days.");
                return;
            }

            // Clear current selections
            this.selectedDates.clear();
            this.virtualCredits = 0; // Reset virtual credits when selecting all

            // Get all future dates that are not already scheduled
            const futureDates = [];
            let day = 1;
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const now = new Date();
            const cutoffTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 22, 0, 0);
            const tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000);

            for (let week = 0; week < 6; week++) {
                for (let dayOfWeek = 0; dayOfWeek < 7; dayOfWeek++) {
                    if (
                        (week === 0 && dayOfWeek < this.firstDay.getDay()) ||
                        day > this.lastDay.getDate()
                    ) {
                        continue;
                    }
                    const date = new Date(
                        this.today.getFullYear(),
                        this.today.getMonth(),
                        day
                    );

                    if (date > today && // Only allow future dates (not today or past)
                        !this.scheduledMeals.has(day) && // Not already scheduled
                        !(now > cutoffTime && // Not after cutoff time for tomorrow
                            date.getDate() === tomorrow.getDate() &&
                            date.getMonth() === tomorrow.getMonth() &&
                            date.getFullYear() === tomorrow.getFullYear())) {
                        futureDates.push({
                            day,
                            isToday: false // Since we're blocking current day, this will always be false
                        });
                    }
                    day++;
                }
            }

            // Select earliest available dates within budget
            futureDates.sort((a, b) => a.day - b.day);
            for (let i = 0; i < Math.min(availableDays, futureDates.length); i++) {
                const {
                    day,
                    isToday
                } = futureDates[i];
                this.selectedDates.add(day);
                const cell = this.calendarBody.querySelector(`td[data-date="${day}"]`);
                if (cell) {
                    cell.innerHTML = `<div class="p-1 ${isToday ? "text-success" : "text-primary"}">
                    <i class="fa-solid fa-circle-check"></i>
                </div>`;
                }
            }

            this.updateCredits();
        }

        updateCredits() {
            const totalCost = this.calculateTotalCost();
            const totalAvailableCredits = this.totalCredits + this.virtualCredits;
            const remainingCredits = totalAvailableCredits - totalCost;

            // Display the sum of real and virtual credits
            const displayCredits = Math.max(0, remainingCredits);
            this.creditDisplay.innerText = displayCredits;

            // Calculate progress based on total available credits
            const progressPercent = totalAvailableCredits > 0 ?
                (displayCredits / totalAvailableCredits) * 100 : 0;
            this.creditProgress.style.width = `${progressPercent}%`;

            if (remainingCredits < this.minimumBalance) {
                this.creditProgress.classList.remove("bg-success");
                this.creditProgress.classList.add("bg-danger");
            } else {
                this.creditProgress.classList.remove("bg-danger");
                this.creditProgress.classList.add("bg-success");
            }
            // Update selected meals count and total cost
            let newSelections = 0;
            let cancellations = 0;
            this.selectedDates.forEach(day => {
                if (this.scheduledMeals.has(day)) {
                    cancellations++;
                } else {
                    newSelections++;
                }
            });

            this.selectedMealsCount.innerText = newSelections;
            this.totalCost.innerText = totalCost;

            // Store selected dates for form submission
            const selectedDatesArray = Array.from(this.selectedDates);
            this.scheduleInput.value = JSON.stringify(selectedDatesArray);
        }

        loadScheduledMeals() {
            // Fetch scheduled meals from the server
            fetch("get_scheduled_meals.php")
                .then((response) => response.json())
                .then((data) => {
                    this.scheduledMeals = new Set(data.scheduled_days);
                    this.renderCalendar(); // Re-render calendar with scheduled meals
                    this.calendarLoading.style.display = "none";
                    this.calendarTable.style.display = "table";
                })
                .catch((error) => {
                    console.error("Error loading scheduled meals:", error);
                    this.calendarLoading.innerHTML = `<div class="alert alert-danger">Failed to load calendar. Please try again later.</div>`;
                });
        }

        markDateAsScheduled(cell, day, isToday) {
            const date = new Date(this.today.getFullYear(), this.today.getMonth(), day);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const isPast = date < today;

            let colorClass = "text-primary";
            if (isPast) {
                colorClass = "text-secondary";
            } else if (isToday) {
                colorClass = "text-success";
            }

            cell.innerHTML = `<div class="p-1 ${colorClass}">
        <i class="fa-solid fa-circle-check"></i>
        ${isPast ? `<small>${day}</small>` : ""}
    </div>`;
        }

        showAlert(message) {
            const mealSummery = document.getElementById("meal-summary");
            const existingAlert = document.querySelector(".alert-danger");

            // Remove existing alert if present
            if (existingAlert) {
                existingAlert.remove();
                clearTimeout(existingAlert.timeout);
            }

            const alertDiv = document.createElement("div");
            mealSummery.style.display = "none";

            alertDiv.className = "alert alert-danger justify-content-between align-items-center mb-3 d-flex";
            alertDiv.innerHTML = `${message}`;

            mealSummery.parentNode.insertBefore(alertDiv, mealSummery);

            alertDiv.timeout = setTimeout(() => {
                alertDiv.remove();
                mealSummery.style.display = "flex";
            }, 3000);
        }
    }

    // Initialize calendar when DOM is loaded
    document.addEventListener("DOMContentLoaded", () => {
        new MealCalendar();
    });
</script>

<?php require_once '../includes/footer.php'; ?>