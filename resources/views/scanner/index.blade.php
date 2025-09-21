<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>QR Code Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #4b5563 0%, #111827 100%);
        }
        #reader__scan_region {
            border-radius: 0.75rem;
            overflow: hidden;
        }
        #reader video {
            width: 100% !important;
            height: auto !important;
            border-radius: 0.75rem;
        }
        .result-card {
            transition: all 0.3s ease-in-out;
        }
        .result-card:hover {
            transform: translateY(-2px);
        }
        .highlight-table {
            background: linear-gradient(90deg, #f97316, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.5rem;
            font-weight: 800;
        }
        .error-message {
            font-size: 1.25rem;
            font-weight: 600;
            animation: shake 0.5s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 font-sans">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 transform transition-all hover:shadow-3xl">
        <h1 class="text-3xl md:text-4xl font-extrabold text-center text-gray-900 mb-8 tracking-tight">QR Code Scanner</h1>

        <div class="flex flex-col items-center">
            <div class="w-full mb-8 relative">
                <div id="reader" class="w-full rounded-lg overflow-hidden border-4 border-indigo-600"></div>
                <div class="absolute bottom-0 left-0 right-0 text-center bg-indigo-600 bg-opacity-80 text-white py-2 text-sm md:text-base font-medium">
                    Point your camera at a QR code
                </div>
            </div>
            <div class="flex space-x-4 mb-8">
                <button id="toggleScan" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-8 rounded-full transition-colors duration-300 shadow-md">
                    Stop Scan
                </button>
                <button id="resetScan" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-8 rounded-full transition-colors duration-300 shadow-md">
                    Reset Scanner
                </button>
            </div>
            <div class="w-full">
                <h4 class="text-xl font-semibold text-gray-800 mb-4 text-center">Scan Result</h4>
                <div id="result" class="bg-gradient-to-r from-green-500 to-green-700 text-white p-6 rounded-2xl text-center text-base md:text-lg break-words shadow-lg result-card">
                    Waiting for scan...
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.2.0/html5-qrcode.min.js"></script>
    <script>
        const html5QrCode = new Html5Qrcode("reader");
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            disableFlip: false
        };
        let isScanning = false;

        // Display result or error
        function displayResult(message, isError = false) {
            const resultDiv = document.getElementById("result");
            resultDiv.innerHTML = isError
                ? `<span class="error-message">${message}</span>`
                : `<span class="result font-medium">${message}</span>`;
            resultDiv.classList.remove(isError ? "from-green-500" : "from-red-500", isError ? "to-green-700" : "to-red-700");
            resultDiv.classList.add(isError ? "from-red-500" : "from-green-500", isError ? "to-red-700" : "to-green-700");
        }

        // Fetch invite details from API
        async function fetchInviteDetails(qrCode) {
            try {
                const response = await fetch(`https://ramadia-grand.test/invite/${encodeURIComponent(qrCode)}`);
                const data = await response.json();

                if (!response.ok || data.status === 'error') {
                    displayResult(data.message || `Error: ${response.statusText}`, true);
                } else {
                    const invite = data.data;
                    const message = `
                        <div class="text-left space-y-2">
                            <p><strong>Name:</strong> ${invite.name}</p>
                            <p><strong>Company:</strong> ${invite.company || 'N/A'}</p>
                            <p><strong>Number of Invites:</strong> ${invite.number_of_invites}</p>
                            <p><strong>Table:</strong> <span class="highlight-table">${invite.table || 'N/A'}</span></p>
                            <p><strong>Contact:</strong> ${invite.contact || 'N/A'}</p>
                            <p><strong>Status:</strong> ${invite.status}</p>
                        </div>
                    `;
                    displayResult(message);
                    stopScan(); // Stop scanning after successful scan
                }
            } catch (error) {
                displayResult(`Network error: ${error.message}`, true);
            }
        }

        // When scan is successful
        function onScanSuccess(qrCodeMessage) {
            displayResult("Fetching invite details...");
            fetchInviteDetails(qrCodeMessage);
        }

        // When scan fails
        function onScanError(errorMessage) {
            console.log('Scan error:', errorMessage);
        }

        // Start scanning
        function startScan() {
            html5QrCode.start(
                { facingMode: "environment" },
                config,
                onScanSuccess,
                onScanError
            ).then(() => {
                isScanning = true;
                document.getElementById("toggleScan").textContent = "Stop Scan";
                document.getElementById("toggleScan").classList.remove("bg-green-500", "hover:bg-green-600");
                document.getElementById("toggleScan").classList.add("bg-red-500", "hover:bg-red-600");
                displayResult("Waiting for scan...");
            }).catch(err => {
                console.error("Camera start error:", err);
                displayResult("Failed to start camera", true);
            });
        }

        // Stop scanning
        function stopScan() {
            html5QrCode.stop().then(() => {
                isScanning = false;
                document.getElementById("toggleScan").textContent = "Start Scan";
                document.getElementById("toggleScan").classList.remove("bg-red-500", "hover:bg-red-600");
                document.getElementById("toggleScan").classList.add("bg-green-500", "hover:bg-green-600");
                if (!document.getElementById("result").innerHTML.includes("Name:")) {
                    displayResult("Scanner stopped");
                }
            }).catch(err => {
                console.error("Stop error:", err);
                displayResult("Error stopping scanner", true);
            });
        }

        // Reset scanner
        function resetScanner() {
            if (isScanning) {
                stopScan();
            }
            displayResult("Waiting for scan...");
        }

        // Toggle scan on button click
        document.getElementById("toggleScan").addEventListener("click", () => {
            if (isScanning) {
                stopScan();
            } else {
                startScan();
            }
        });

        // Reset scanner on button click
        document.getElementById("resetScan").addEventListener("click", () => {
            resetScanner();
        });

        // Start scanner initially
        startScan();

        // Clean up on page unload
        window.addEventListener('unload', () => {
            if (isScanning) {
                html5QrCode.stop().catch(err => console.error("Stop error:", err));
            }
        });
    </script>
</body>
</html>
