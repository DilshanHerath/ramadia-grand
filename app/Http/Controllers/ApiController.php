<?php

namespace App\Http\Controllers;

use App\Models\Invite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    public function getInviteByQrCode(string $qrCode): JsonResponse
    {
        try {
            $invite = Invite::where('qr_code', $qrCode)->first();

            if (!$invite) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invite not found'
                ], 404);
            }

            if ($invite->scan_status) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Already scanned QR',
                    'table_no' => $invite->table_no
                ], 409); // 409 Conflict for already processed
            }

            // Mark as scanned
            $invite->scan_status = true;
            $invite->save();

            // Task 2: Send WhatsApp message here (added in next section)
            $this->redirectToWhatsapp();
            return response()->json([
                'status' => 'success',
                'data' => $invite
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching the invite'
            ], 500);
        }
    }

    public function redirectToWhatsapp()
    {
        Log::info("Redirecting to WhatsApp...");
        $phone = "947696622981";
        $message = urlencode("Hello European Experts! I would like to book a service.");

        $url = "https://api.whatsapp.com/send?phone={$phone}&text={$message}";

        return redirect()->away($url);
    }
}
