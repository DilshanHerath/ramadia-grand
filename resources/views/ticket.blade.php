<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Allura&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0%;
            padding: 0;
        }

        .ticket {
            width: 600px;
            margin: 0;
        }

        h1 {
            color: #b8860b;
        }

        .qr img {
            width: 200px;
            height: 200px;
            margin-top: 20px;
        }

        .details {
            font-size: 16px;
            margin-top: 15px;
        }

        .allura-regular {
            font-family: "Allura", cursive;
            font-weight: 400;
            font-style: normal;
        }
    </style>
</head>

<body style="margin: 0;padding: 0;">

    <div
        style="background-image:url('{{ public_path('storage/Invitation.png') }}');width: 100%;background-repeat: no-repeat;background-position: center;height: 100%;background-size: cover;margin: 0;padding: 0">
        <div style="position: fixed;top:28%;z-index:200;width:100%">
            <h2 class="allura-regular"
                style="font-size:32px;color:#b78339;text-align:center;text-transform: capitalize!important;">
                @if ($invite->name != null)
                    {{ $invite->name }}
                @else
                    Guest
                @endif
            </h2>
        </div>
        <div class="qr" style="position: absolute; bottom: 10%; left: 5%">
            <img src="{{ public_path('storage/qrcodes/' . $invite->id . '.png') }}"
                style="width: 120px;height: 120px;border-radius:5px" alt="QR Code">
        </div>
    </div>
</body>

</html>
