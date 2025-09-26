<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #4b5563 0%, #111827 100%);
        }

        #reader video {
            width: 100% !important;
            height: auto !important;
            border-radius: 0.75rem;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-6 font-sans">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8">
        <h1 class="text-3xl font-extrabold text-center text-gray-900 mb-8">QR Code Scanner</h1>

        <div class="flex flex-col items-center">
            <div class="w-full mb-8 relative">
                <div id="reader" class="w-full rounded-lg overflow-hidden border-4 border-indigo-600"></div>
                <div
                    class="absolute bottom-0 left-0 right-0 text-center bg-indigo-600 bg-opacity-80 text-white py-2 text-sm font-medium">
                    Point your camera at a QR code
                </div>
            </div>
            <div class="flex space-x-4 mb-8">
                <button id="toggleScan"
                    class="bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-8 rounded-full shadow-md">
                    Stop Scan
                </button>
                <button id="resetScan"
                    class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-8 rounded-full shadow-md">
                    Reset Scanner
                </button>
            </div>
            <div class="w-full text-center">
                <p id="result" class="text-gray-700 font-medium">Waiting for scan...</p>
            </div>
        </div>
    </div>

    <!-- ✅ Popup Modal -->
    <div id="popupModal" class="fixed inset-0 hidden items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white w-11/12 max-w-md rounded-2xl shadow-xl p-6 relative">
            <h2 class="text-2xl font-bold mb-4">Ticket Details</h2>
            <div id="popupContent" class="space-y-2 text-gray-700">
                <!-- Ticket info injected here -->
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button id="closePopup"
                    class="px-4 py-2 bg-gray-400 hover:bg-gray-500 text-white rounded-lg">Close</button>
                <button id="sendWhatsapp" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">Send
                    via WhatsApp</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.2.0/html5-qrcode.min.js"></script>
    <script>
        const html5QrCode = new Html5Qrcode("reader");
        let isScanning = false;
        let currentWhatsappUrl = "";

        function displayResult(message, isError = false) {
            const resultDiv = document.getElementById("result");
            resultDiv.textContent = message;
            resultDiv.className = isError ? "text-red-600 font-semibold" : "text-green-600 font-semibold";
        }

        async function fetchInviteDetails(qrCode) {
            try {
                const response = await fetch(`/invite/${encodeURIComponent(qrCode)}`);
                const data = await response.json();
                const invite = data.data; // may exist even on error

                // Determine if error
                const isError = data.status === 'error';
                const message = data.message || (isError ? "Ticket already used!" : "Ticket scanned successfully");

                // Build popup content
                const content = `
            <p class="${isError ? 'text-red-600 font-bold' : 'text-green-600 font-bold'}">${message}</p>
            <p><strong>Name:</strong> ${invite?.name || 'N/A'}</p>
            <p><strong>Company:</strong> ${invite?.company || 'N/A'}</p>
            <p><strong>Invites:</strong> ${invite?.number_of_invites || 'N/A'}</p>
            <p><strong>Table:</strong> <span class="font-bold text-orange-600">${invite?.table || 'N/A'}</span></p>
            <p><strong>Contact:</strong> ${invite?.contact || 'N/A'}</p>
            <p><strong>Status:</strong> ${isError ? "Already Scanned" : "Not Scanned Yet"}</p>
        `;

                document.getElementById("popupContent").innerHTML = content;
                document.getElementById("popupModal").classList.remove("hidden");
                document.getElementById("popupModal").classList.add("flex");

                stopScan();
                displayResult(message, isError);

                // Show or hide WhatsApp button based on ticket status
                const whatsappBtn = document.getElementById("sendWhatsapp");
                if (isError || !data.whatsapp_url) {
                    whatsappBtn.classList.add("hidden"); // hide button
                    currentWhatsappUrl = "";
                } else {
                    whatsappBtn.classList.remove("hidden"); // show button
                    currentWhatsappUrl = data.whatsapp_url;
                }

            } catch (error) {
                displayResult(`Network error: ${error.message}`, true);
            }
        }



        function onScanSuccess(qrCodeMessage) {
            displayResult("Fetching invite details...");
            fetchInviteDetails(qrCodeMessage);
        }

        function onScanError(errorMessage) {
            console.log("Scan error:", errorMessage);
        }

        function startScan() {
            html5QrCode.start({
                    facingMode: "environment"
                }, {
                    fps: 10,
                    qrbox: {
                        width: 250,
                        height: 250
                    }
                },
                onScanSuccess, onScanError
            ).then(() => {
                isScanning = true;
                document.getElementById("toggleScan").textContent = "Stop Scan";
            }).catch(err => {
                displayResult("Failed to start camera", true);
            });
        }

        function stopScan() {
            html5QrCode.stop().then(() => {
                isScanning = false;
                document.getElementById("toggleScan").textContent = "Start Scan";
            }).catch(err => console.error("Stop error:", err));
        }

        // buttons
        document.getElementById("toggleScan").addEventListener("click", () => {
            if (isScanning) stopScan();
            else startScan();
        });

        document.getElementById("resetScan").addEventListener("click", () => {
            if (isScanning) stopScan();
            displayResult("Waiting for scan...");
        });

        document.getElementById("closePopup").addEventListener("click", () => {
            document.getElementById("popupModal").classList.add("hidden");
        });

        document.getElementById("sendWhatsapp").addEventListener("click", () => {
            if (currentWhatsappUrl) {
                window.open(currentWhatsappUrl, "_blank");
            }
        });

        // auto start
        startScan();
    </script>
</body>

</html>
