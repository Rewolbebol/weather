<!DOCTYPE html>
<html>
<head>
    <title>Pareģošana</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.min.css"/>
    <style>
        /* Style for invalid input */
        input.invalid,
        select.invalid {
            border: 2px solid red;
        }

        /* Mobile-friendly styles */
        body {
            font-family: sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 90%; /* Adjust max-width for smaller screens */
            margin: 20px auto;
            padding: 15px;
        }

        h1 {
            text-align: center;
            font-size: 1.5em; /* Slightly smaller heading on mobile */
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="number"],
        input[type="text"],
        select {
            width: 100%; /* Full width for form elements */
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .date-time-group{
            width: 100%;
        }
        .date-time-group div {
            width: 100%;
        }

        button[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%; /* Full width for button */
        }

        button[type="submit"]:hover {
            background-color: #45a049;
        }
        /*media query for screens smaller than 768px */
        @media (min-width: 768px) {
        .container {
            max-width: 600px;
        }
            h1{
                font-size: 2em;
            }
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
            <label for="woodchip_efficiency">Šķeldas efektivitāte:</label>
            <select id="woodchip_efficiency" name="woodchip_efficiency" required>
                <option value="0.6">0.6</option>
                <option value="0.7" selected>0.7</option>
                <option value="0.8">0.8</option>
            </select>
            <div class="date-time-group">
                <div>
                    <label for="start_date">Sākuma datums un laiks (YYYY-MM-DD HH:MM):</label>
                    <input type="text" id="start_datetime" name="start_datetime" class="datetimepicker-input" required readonly="readonly">
                </div>
            </div>
            <div class="date-time-group">
                <div>
                    <label for="end_date">Beigu datums un laiks (YYYY-MM-DD HH:MM):</label>
                    <input type="text" id="end_datetime" name="end_datetime" class="datetimepicker-input" required readonly="readonly">
                </div>
            </div>
            <button type="submit">Pareģot</button>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js"></script>
    <script>
        $(document).ready(function() {
            $.datetimepicker.setLocale('lv');
            $('#start_datetime').datetimepicker({
                format: 'Y-m-d H:i',
                minDate: new Date(),
                onShow: function(ct) {
                    this.setOptions({
                        maxDate: $('#end_datetime').val() ? $('#end_datetime').val() : false
                    })
                }
            });
            $('#end_datetime').datetimepicker({
                format: 'Y-m-d H:i',
                minDate: new Date(),
                onShow: function(ct) {
                    this.setOptions({
                        minDate: $('#start_datetime').val() ? $('#start_datetime').val() : false
                    })
                }
            });
            // Get the form and add a submit event listener
            const form = document.querySelector('form');
            form.addEventListener('submit', function(event) {
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
            document.querySelectorAll('input[type="text"], input[type="number"], select').forEach(input => {
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
            //add event listeners to select to check if value is selected.
            document.getElementById('woodchip_efficiency').addEventListener('change', function() {
                if (this.value === '') {
                    this.classList.add('invalid');
                } else {
                    this.classList.remove('invalid');
                }
            });
        });
    </script>
</body>
</html>
