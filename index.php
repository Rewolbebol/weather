<!DOCTYPE html>
<html>

<head>
    <title>Pareģošana</title>
    <link rel="stylesheet" href="style.css">
    <!-- Include flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Style for invalid input */
        input.invalid, select.invalid {
            border: 2px solid red;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Šķeldas pateriņa kalkulators</h1>
        <form action="calculate.php" method="post">
            <label for="location">Izvēlies KM:</label>
            <select id="location" name="location" required>
                <option value="">-- Izvēlies --</option>
                <option value="Bauskas 207A">Bauskas 207A</option>
                <option value="Nautrēnu 24">Nautrēnu 24</option>
            </select>
            <label for="woodchip_m3">Šķeldas daudzums (m<sup>3</sup>):</label>
            <input type="number" id="woodchip_m3" name="woodchip_m3" min="0" step="0.01" required>
            <div class="date-time-group">
                <div>
                    <label for="start_date">Sākuma datums (YYYY-MM-DD):</label>
                    <!-- Add class to input for flatpickr -->
                    <input type="text" id="start_date" name="start_date" class="flatpickr-input flatpickr-date" required
                        readonly="readonly">
                </div>
                <div>
                    <label for="start_time">Sākuma laiks (HH:MM):</label>
                    <!-- Add class to input for flatpickr -->
                    <input type="text" id="start_time" name="start_time" class="flatpickr-input flatpickr-time" required
                        readonly="readonly">
                </div>
            </div>
            <div class="date-time-group">
                <div>
                    <label for="end_date">Beigu datums (YYYY-MM-DD):</label>
                    <!-- Add class to input for flatpickr -->
                    <input type="text" id="end_date" name="end_date" class="flatpickr-input flatpickr-date" required
                        readonly="readonly">
                </div>
                <div>
                    <label for="end_time">Beigu laiks (HH:MM):</label>
                    <!-- Add class to input for flatpickr -->
                    <input type="text" id="end_time" name="end_time" class="flatpickr-input flatpickr-time" required
                        readonly="readonly">
                </div>
            </div>

            <button type="submit">Pareģot</button>
        </form>

    </div>
    <!-- Include flatpickr JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr(".flatpickr-date", {
            dateFormat: "Y-m-d",
            minDate: "today",
            onChange: function (selectedDates, dateStr, instance) {
                // Update the actual input value, no need for hidden input anymore.
                instance.input.value = dateStr;
                // Remove the invalid class if it exists
                instance.input.classList.remove('invalid');
            },
        });

        flatpickr(".flatpickr-time", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            onChange: function (selectedDates, dateStr, instance) {
                // Update the actual input value, no need for hidden input anymore.
                instance.input.value = dateStr;
                // Remove the invalid class if it exists
                instance.input.classList.remove('invalid');
            },
        });

        // Get the form and add a submit event listener
        const form = document.querySelector('form');
        form.addEventListener('submit', function (event) {
            // Check for invalid fields
            const invalidInputs = form.querySelectorAll('input.invalid, select.invalid');
            if (invalidInputs.length > 0) {
                // Prevent form submission
                event.preventDefault();
                // Show a general error message or highlight the invalid fields
                alert('Lūdzu, aizpildiet visus laukus pareizi.');
            }
        });
        //add event listeners for inputs to check if they are empty and add class invalid.
        document.querySelectorAll('input[type="text"],input[type="number"],select').forEach(input => {
            input.addEventListener('input', () => {
                if (input.value.trim() === '') {
                    input.classList.add('invalid');
                } else {
                    input.classList.remove('invalid');
                }
            });
        });

        //add event listeners to select to check if value is selected.
        document.getElementById('location').addEventListener('change', function() {
             if (this.value === '') {
                this.classList.add('invalid');
            } else {
                 this.classList.remove('invalid');
            }
        });
    </script>
</body>

</html>
