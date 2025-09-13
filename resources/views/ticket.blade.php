<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
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
    </style>
</head>

<body style="margin: 0;padding: 0;">

    <div
        style="background-image:url('{{ public_path('storage/ticket_template.jpg') }}');width: 100%;background-repeat: no-repeat;background-position: center;height: 100%;background-size: cover;margin: 0;padding: 0">
        <div style="position: fixed;top:29%;z-index:200;width:100%">
            <h2 style="font-size:25px;color:#b78339;text-align:center">
                <strong>
                    @if ($invite->name != null)
                        {{ $invite->name }}
                    @else
                        Guest
                    @endif
                </strong>
            </h2>
        </div>
        <div class="qr" style="position: absolute; bottom: 10%; left: 5%">
            <img src="{{ public_path('storage/qrcodes/' . $invite->id . '.png') }}"
                style="width: 120px;height: 120px;border-radius:5px" alt="QR Code">
        </div>
    </div>
</body>

</html>
