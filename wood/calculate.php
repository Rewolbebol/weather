<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Location options with coordinates
$locations = [
    'Bauskas 207A' => [
        'latitude' => 56.90070445004411,
        'longitude' => 24.144126290836965,
    ],
    'Nautrēnu 24' => [
        'latitude' => 56.96230558114238,
        'longitude' => 24.301562251033424,
    ],
];

// Hardcoded Powerplant Output Data for Locations
$powerplantOutput = [
    'Bauskas 207A' => [
        '-18' => 1.61,
        '-15' => 1.58,
        '-14' => 1.58,
        '-13' => 1.61,
        '-12' => 1.42,
        '-10' => 1.71,
        '-7' => 1.69,
        '-6' => 1.64,
        '-5' => 1.69,
        '-4' => 1.65,
        '-3' => 1.66,
        '-2' => 1.62,
        '-1' => 1.64,
        '0' => 1.57,
        '1' => 1.52,
        '2' => 1.45,
        '3' => 1.36,
        '4' => 1.29,
        '5' => 1.18,
        '6' => 1.08,
        '7' => 0.98,
        '8' => 0.88,
        '9' => 0.78,
        '10' => 0.68,
        '11' => 0.61,
        '12' => 0.54,
        '13' => 0.45,
        '14' => 0.36,
        '15' => 0.31,
        '16' => 0.28,
        '17' => 0.25,
        '18' => 0.24,
        '19' => 0.23,
        '20' => 0.37,
    ],
    'Nautrēnu 24' => [
        '-21' => 1.58,
        '-20' => 1.53,
        '-19' => 1.61,
        '-18' => 1.45,
        '-17' => 1.49,
        '-16' => 1.43,
        '-15' => 1.34,
        '-14' => 1.38,
        '-13' => 1.22,
        '-12' => 1.24,
        '-11' => 1.23,
        '-10' => 1.62,
        '-9' => 1.35,
        '-8' => 1.31,
        '-7' => 1.34,
        '-6' => 1.26,
        '-5' => 1.24,
        '-4' => 1.22,
        '-3' => 1.24,
        '-2' => 1.31,
        '-1' => 1.36,
        '0' => 1.27,
        '1' => 1.18,
        '2' => 1.15,
        '3' => 1.12,
        '4' => 1.06,
        '5' => 1.01,
        '6' => 0.98,
        '7' => 0.96,
        '8' => 0.94,
        '9' => 0.94,
        '10' => 0.89,
        '11' => 0.85,
        '12' => 0.8,
        '13' => 0.77,
        '14' => 0.71,
        '15' => 0.68,
        '16' => 0.66,
        '17' => 0.62,
        '18' => 0.59,
        '19' => 0.56,
        '20' => 0.54,
    ],
];

//woodchip parameters
$woodchipEfficiency = 0.7;
$powerplantLosses = 0.12;
// Default timezone in case the API doesn't return one
$defaultTimezone = 'Europe/Riga'; // You can change this to your preferred default

// Input validation and processing
$selectedLocation = null;
$latitude = null;
$longitude = null;
$startDateTime = null;
$endDateTime = null;
$woodchipM3 = null;

if (isset($_POST['woodchip_efficiency'])) {
    $woodchipEfficiency = floatval($_POST['woodchip_efficiency']);
    if ($woodchipEfficiency < 0.6 || $woodchipEfficiency > 0.8) {
        die("Error: Woodchip efficiency must be between 0.6 and 0.8.");
    }
}

// Check if a location is selected
if (isset($_POST['location']) && array_key_exists($_POST['location'], $locations)) {
    $selectedLocation = $_POST['location'];
    $latitude = $locations[$selectedLocation]['latitude'];
    $longitude = $locations[$selectedLocation]['longitude'];
} else {
    die("Error: No location selected.");
}
//get woodchip
if (isset($_POST['woodchip_m3'])) {
    $woodchipM3 = floatval($_POST['woodchip_m3']);
    if ($woodchipM3 <= 0) {
        die("Error: Woodchip volume must be greater than zero.");
    }
} else {
    die("Error: Woodchip volume is required.");
}

// --- New Open-Meteo API Request ---
$url = "https://api.open-meteo.com/v1/forecast?latitude={$latitude}&longitude={$longitude}&hourly=temperature_2m&timezone=auto";

// Using cURL for better error handling
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code != 200) {
    die("API request failed with HTTP code: {$http_code}");
}

$data = json_decode($response, true);

// --- Open-Meteo Error Handling ---
if (!$data || isset($data['error'])) {
    $error_message = isset($data['reason']) ? $data['reason'] : 'Unknown API error';
    die("Weather service error: {$error_message}");
}

// Get timezone from API response, or use default
$timezone = isset($data['timezone']) ? $data['timezone'] : $defaultTimezone;
$targetTimeZone = new DateTimeZone($timezone);

// Function to validate and format date and time
function validateAndFormatDateTime($dateTimeString, $timezone)
{
    $dateTimeString = trim(preg_replace('/\s+/', ' ', $dateTimeString)); //remove multiple spaces

    if (empty($dateTimeString)) {
        throw new Exception("Date and time are required.");
    }

    // Attempt to create DateTime object using stricter parsing
    $dateTime = DateTime::createFromFormat('Y-m-d H:i', $dateTimeString, $timezone);

    if (!$dateTime) {
        // Try a more lenient parse if the strict one fails (for debugging purposes)
        $dateTime = DateTime::createFromFormat('Y-m-d H:i', $dateTimeString, $timezone);
        if (!$dateTime) {
            throw new Exception("Invalid date/time format. Please use YYYY-MM-DD HH:MM format.  Error: " . print_r(DateTime::getLastErrors(), true));
        }
    }
    return $dateTime;
}

// Validate and process start date and time
try {
    $startDateTime = validateAndFormatDateTime($_POST['start_datetime'], $targetTimeZone);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Validate and process end date and time
try {
    $endDateTime = validateAndFormatDateTime($_POST['end_datetime'], $targetTimeZone);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

if ($startDateTime > $endDateTime) {
    die("Error: Start Date and Time must be before End Date and Time.");
}
//change now time to target timezone
$now = new DateTime("now", $targetTimeZone);
if ($startDateTime < $now) {
    die("Error: Start Date and Time cannot be in the past");
}

// ... (rest of your code after date/time processing) ...
// Process and display data
// --- Adapt for Open-Meteo structure ---
$hourlyTimes = $data['hourly']['time'];
$hourlyTemperatures = $data['hourly']['temperature_2m'];

// Group by day and filter data by time range
$grouped = [];
$startTimestamp = $startDateTime->getTimestamp();
$endTimestamp = $endDateTime->getTimestamp();
$totalHours = 0;
$totalPowerplantOutputMW = 0;
$hourlyWoodchipDuration = [];
$totalWoodchipMW = $woodchipM3 * $woodchipEfficiency * (1 - $powerplantLosses);
$allHourlyData = []; //add new array to hold hourly data with cumulative value
$hourlyCalculations = [];

// --- Loop through Open-Meteo data ---
for ($i = 0; $i < count($hourlyTimes); $i++) {
    $timeString = $hourlyTimes[$i];
    $tempValue = $hourlyTemperatures[$i];

    // Parse ISO8601 time string
    try {
        $dt = new DateTime($timeString, $targetTimeZone);
        // Ensure the DateTime object uses the correct target timezone
        $dt->setTimezone($targetTimeZone);
    } catch (Exception $e) {
        // Skip this hour if the date format is invalid
        continue;
    }

    $currentTimestamp = $dt->getTimestamp();
    $dateKey = $dt->format('Y-m-d');

    // Filter by selected time range
    if ($currentTimestamp >= $startTimestamp && $currentTimestamp <= $endTimestamp) {
        // Check if temperature exists and is numeric before rounding, default to 0 otherwise
        // Open-Meteo seems reliable, but keep check for safety
        $temperature = isset($tempValue) && is_numeric($tempValue) ? round($tempValue) : 0;
        // The check for -0 is still relevant after rounding
        if ($temperature == -0) {
            $temperature = 0;
        }
        $powerplantOutputMW = isset($powerplantOutput[$selectedLocation]["{$temperature}"]) ? $powerplantOutput[$selectedLocation]["{$temperature}"] : 0; // Default to 0 if not found

        $grouped[$dateKey][] = [
            'time' => $dt->format('H:i'),
            'temp' => $temperature,
            'timestamp' => $currentTimestamp, // Use the calculated timestamp
            // Add powerplant output to the data
            'powerplantOutput' => $powerplantOutputMW,
        ];
        $allHourlyData[] = [
            'time' => $dt->format('Y-m-d H:i'),
            'temp' => $temperature,
            'powerplantOutput' => $powerplantOutputMW,
            'timestamp' => $currentTimestamp, // Include timestamp
        ];
        $hourlyCalculations[] = [
            'time' => $dt->format('Y-m-d H:i'),
            'powerplantOutput' => $powerplantOutputMW,
        ];
        $totalPowerplantOutputMW += $powerplantOutputMW;
        $totalHours++;
    }
}

// Calculate woodchip end time precisely
$cumulativeWoodchipNeededTotal = 0;
$lastCorrectHourTimestamp = null;
$lastCorrectHour = null; // Track the last correct hour data
$woodchipExhausted = false;
$exhaustedHourIndex = -1; // Initialize the index to -1 (not found)
$currentHourIndex = 0;

foreach ($allHourlyData as $hour) {
    $cumulativeWoodchipNeededTotal += $hour['powerplantOutput'];
    $neededM3 = ($cumulativeWoodchipNeededTotal * (1 / $woodchipEfficiency) * (1 / (1 - $powerplantLosses)));

    if ($neededM3 <= $woodchipM3) {
        $lastCorrectHourTimestamp = DateTime::createFromFormat('Y-m-d H:i', $hour['time'], $targetTimeZone)->getTimestamp();
        $lastCorrectHour = $hour;
    } else {
        $exhaustedHourIndex = $currentHourIndex; // set index where woodchip is exhausted
        $woodchipExhausted = true;
        break; // Stop when woodchip runs out
    }
    $currentHourIndex++;
}

$woodchipEndTime = clone $startDateTime;
if ($lastCorrectHourTimestamp !== null) {
    $woodchipEndTime->setTimestamp($lastCorrectHourTimestamp);
}

// Calculate total duration with decimal precision
$diff = $woodchipEndTime->getTimestamp() - $startDateTime->getTimestamp();
//use floor and add rest of seconds to get two digits.
$hours = floor($diff / 3600);
$minutes = floor(($diff % 3600) / 60);
$seconds = $diff % 60;
$decimalHours = round(($minutes * 60 + $seconds) / 3600, 2);
$formattedWoodchipDuration = number_format($hours + $decimalHours, 2);

?>
<!DOCTYPE html>
<html>

<head>
    <title>Rezultāti</title>
    <link rel="stylesheet" href="style.css">

    <script>
        function showTooltip(event, text) {
            var tooltip = document.getElementById('tooltip');
            tooltip.innerHTML = text;
            tooltip.style.display = 'block';
            tooltip.style.left = (event.pageX + 10) + 'px';
            tooltip.style.top = (event.pageY + 10) + 'px';
        }

        function hideTooltip() {
            var tooltip = document.getElementById('tooltip');
            tooltip.style.display = 'none';
        }

        function toggleTooltip(event, text) {
            var tooltip = document.getElementById('tooltip');
            if (tooltip.style.display === 'block') {
                tooltip.style.display = 'none';
            } else {
                //check if this is touch event to adjust tooltip location
                if (event.touches && event.touches.length > 0) {
                    var touch = event.touches[0];
                    tooltip.style.left = (touch.pageX + 10) + 'px';
                    tooltip.style.top = (touch.pageY + 10) + 'px';
                } else {
                    tooltip.style.left = (event.pageX + 10) + 'px';
                    tooltip.style.top = (event.pageY + 10) + 'px';
                }
                tooltip.innerHTML = text;
                tooltip.style.display = 'block';
            }
            // Prevent the default behavior (e.g., scrolling)
            event.preventDefault();
        }

        document.addEventListener('click', function(event) {
            var tooltip = document.getElementById('tooltip');
            if (tooltip.style.display === 'block' && !tooltip.contains(event.target) && !event.target.closest('.hour')) {
                tooltip.style.display = 'none';
            }
        });
        document.addEventListener('touchstart', function(event) {
            var tooltip = document.getElementById('tooltip');
            if (tooltip.style.display === 'block' && !tooltip.contains(event.target) && !event.target.closest('.hour')) {
                tooltip.style.display = 'none';
            }
        });

        // Add hover event listeners
        document.addEventListener('mouseover', function(event) {
            const hoveredHour = event.target.closest('.hour');
            if (hoveredHour) {
                const text = `Šķeldas daudzums līdz šai vietai: ${hoveredHour.dataset.neededM3} m³ , stundu skaits: ${hoveredHour.dataset.hoursRounded}`;
                showTooltip(event, text);
            }
        });

        document.addEventListener('mouseout', function(event) {
            const hoveredHour = event.target.closest('.hour');
            if (hoveredHour) {
                hideTooltip();
            }
        });
    </script>
</head>

<body>
    <div class="container">
        <div id="tooltip"></div>
        <h1>Temperatūru prognoze</h1>
        <div class="main-content">
            <div class="forecast-results">
                <?php if (isset($selectedLocation)): ?>
                    <p class="forecast-location">Prognoze priekš: <strong><?= $selectedLocation ?></strong></p>
                <?php endif; ?>

                <p class="forecast-period">No <strong>
                        <?= $startDateTime->format('Y-m-d H:i') ?>
                    </strong> līdz <strong>
                        <?= $endDateTime->format('Y-m-d H:i') ?>
                    </strong> </p>

                <!-- Added section to display API Time/Temperature Data -->
                <!-- <div class="api-response-section">
                    <h3>API Time & Temperature Data:</h3>
                    <p>Location from API: Lat: <?= htmlspecialchars($data['latitude'] ?? 'N/A') ?>, Lon: <?= htmlspecialchars($data['longitude'] ?? 'N/A') ?></p>
                    <pre><?php
                        if (isset($hourlyTimes) && isset($hourlyTemperatures) && count($hourlyTimes) === count($hourlyTemperatures)) {
                            for ($i = 0; $i < count($hourlyTimes); $i++) {
                                echo htmlspecialchars($hourlyTimes[$i]) . " -- " . htmlspecialchars($hourlyTemperatures[$i] ?? 'N/A') . " °C\n";
                            }
                        } else {
                            echo "Hourly time/temperature data not available in the expected format.";
                        }
                    ?></pre>
                </div> -->
                <!-- End of added section -->

                <?php if ($totalHours == 0): ?>
                    <p class="no-data">Nav datu priekš tāda intervālā </p>
                <?php endif; ?>
                <?php if ($lastCorrectHour !== null): ?>
                    <p class="woodchip-duration">
                        Ar <strong><?= $woodchipM3 ?></strong> m<sup>3</sup> šķeldas pietiks uz
                        <strong><?= $formattedWoodchipDuration ?></strong> stundām, līdz
                        <strong><?= $woodchipEndTime->format("Y-m-d H:i") ?></strong>.
                    </p>
                    <!-- Woodchip Efficiency Card moved here -->
                    <div class="woodchip-efficiency-container">
                        <div class="woodchip-efficiency-card">
                            <p>Izvēlētais šķeldas efektivitātes koeficients:
                                <strong><?= $woodchipEfficiency ?></strong>
                            </p>
                        </div>
                    </div>

                    <div class="forecast-data">
                        <?php
                        $cumulativeWoodchipNeededTotal = 0;
                        $hoursCount = 0;
                        $startDateTimeForCounting = clone $startDateTime;
                        $currentHourIndex = 0;
                        foreach ($grouped as $date => $hours):
                        ?>
                            <div class="day-container">
                                <div class="date-column">
                                    <?= date('l, F jS', strtotime($date)) ?>
                                </div>
                                <div class="hourly-bar">
                                    <?php
                                    foreach ($hours as $hour):
                                        $currentDateTime = new DateTime();
                                        $currentDateTime->setTimestamp($hour["timestamp"])->setTimezone($targetTimeZone);
                                        $hoursCount = ($currentDateTime->getTimestamp() - $startDateTimeForCounting->getTimestamp()) / 3600;

                                        $cumulativeWoodchipNeededTotal += $hour['powerplantOutput'];
                                        $neededM3 = ($cumulativeWoodchipNeededTotal * (1 / $woodchipEfficiency) * (1 / (1 - $powerplantLosses)));
                                        $neededM3Rounded = round($neededM3, 2);
                                        $hoursRounded = number_format($hoursCount, 2);

                                        $classToApply = "";

                                        if ($currentHourIndex >= $exhaustedHourIndex && $exhaustedHourIndex != -1) {
                                            $classToApply = "woodchip-exhausted";
                                        }
                                    ?>
                                        <div class="hour <?= $classToApply ?>" onclick="toggleTooltip(event,'Šķeldas daudzums līdz šai vietai: <?= $neededM3Rounded ?> m³ , stundu skaits: <?= $hoursRounded ?>')" data-needed-m3="<?= $neededM3Rounded ?>" data-hours-rounded="<?= $hoursRounded ?>">
                                            <div class="hour-time">
                                                <?= $hour['time'] ?>
                                            </div>
                                            <div class="hour-temp">
                                                <?= $hour['temp'] ?>°C
                                            </div>

                                            <div class="hour-powerplant">
                                                <?= $hour['powerplantOutput'] ?> MW
                                            </div>

                                        </div>
                                    <?php
                                        $currentHourIndex++;
                                    endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <a href="index.php" class="new-search-link">Atpakaļ uz izvelni</a>
</body>

</html>
