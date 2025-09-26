<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateInviteTicket;
use App\Models\Invite;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class TicketController extends Controller
{
    public function generateFirstTen()
    {
        // Ensure directory exists
        Storage::makeDirectory('public/qrcodes');

        // Get first 10 invites
        // $invites = Invite::whereBetween('id', [783, 789])->get();
        $invites = Invite::where('id', 8500)->get();

        foreach ($invites as $invite) {
            $this->generateQR($invite);
        }

        return "QR codes for first 10 invites generated!";
    }

    private function generateQR($invite)
    {
        // Create a unique string for this invite
        $uniqueId = "INVITE-{$invite->id}-" . uniqid();

        // Path where QR will be saved
        $qrPath = storage_path("app/public/qrcodes/{$invite->id}.png");

        // Generate QR code with padding + white background
        QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->backgroundColor(255, 255, 255)
            ->generate($uniqueId, $qrPath);

        // Save uniqueId into DB
        $invite->qr_code = $uniqueId;
        $invite->save();
    }

    public function showTicket($id)
    {
        $invite = \App\Models\Invite::findOrFail($id);
        return view('ticket.ticket', compact('invite'));
    }

    public function generateTickets()
    {
        $invites = Invite::limit(10)->get(); // first 10 for testing

        foreach ($invites as $invite) {
            $this->createTicket($invite);
        }

        return "Tickets generated for first 10 invites!";
    }

    public function generateFirstTenTickets()
    {
        try {
            $invites = Invite::whereBetween('id', [551, 580])->get(); // first 10 for testing
            // $invites = Invite::where('id', 1)->get(); // first 10 for testing

            foreach ($invites as $invite) {
                $this->createTicket($invite);
            }

            return "Tickets generated for first 10 invites!";
        } catch (\Exception $e) {
            return "Error generating tickets: " . $e->getMessage();
        }
    }

    private function createTicket($invite)
    {
        try {
            // Generate PDF
            $pdf = Pdf::loadView('ticket', compact('invite'))->setPaper('a4', 'portrait');

            // Sanitize filename
            $nameForFile = $invite->name ? $invite->name : 'Guest';
            $nameForFile = preg_replace('/[^A-Za-z0-9_\- ]/', '', $nameForFile); // remove special chars
            $nameForFile = str_replace(' ', '_', $nameForFile); // replace spaces with underscores
            $nameForFile = trim($nameForFile); // remove trailing/leading whitespace

            $pdfPath = storage_path("app/public/tickets/{$nameForFile}.pdf");

            // Ensure directory exists
            Storage::makeDirectory('public/tickets');

            // Save PDF
            $pdf->save($pdfPath);

            // Update invite status
            $invite->ticket_status = 'Ticket-generated';
            $invite->save();
        } catch (\Exception $e) {
            Log::error("Failed to create ticket for invite ID {$invite->id}: " . $e->getMessage());
        }
    }

    // ==========================================Generate all Tickets ==========================================

    public function generateAllTickets()
    {
        // Get all invites
        // $invites = Invite::all();
        $invites = Invite::limit(4)->get();

        foreach ($invites as $invite) {
            // Dispatch a job for each invite
            GenerateInviteTicket::dispatch($invite);
        }

        return "All ticket generation jobs have been dispatched!";
    }

    // ==========================================QR Scanner ==========================================

    public function scannerPage()
    {
        return view('scanner.index');
    }

    public function verifyQr(Request $request)
    {
        $qrCode = $request->input('qr_code');

        $invite = Invite::where('qr_code', $qrCode)->first();

        if (!$invite) {
            return response()->json(['status' => 'error', 'message' => 'Invalid QR Code']);
        }

        if ($invite->ticket_status === 'Scanned') {
            return response()->json(['status' => 'error', 'message' => 'Ticket already used!']);
        }

        // Update status
        $invite->ticket_status = 'Scanned';
        $invite->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Valid ticket',
            'data' => [
                'name' => $invite->name ?? 'Guest',
                'company' => $invite->company,
                'number_of_invites' => $invite->number_of_invites,
                'table' => $invite->table_no,
                'contact' => $invite->contact,
                'status' => $invite->scan_status
            ]
        ]);
    }

    public function getInviteByQrCode($qr_code)
    {
        $invite = Invite::where('qr_code', $qr_code)->first();

        if (!$invite) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid QR Code'
            ], 404);
        }

        // if already scanned, return details too
        if ($invite->scan_status === '1') {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket already used!',
                'data' => [
                    'name' => $invite->name ?? 'Guest',
                    'company' => $invite->company,
                    'number_of_invites' => $invite->number_of_invites,
                    'table' => $invite->table_no,
                    'contact' => $invite->contact,
                    'status' => $invite->scan_status
                ]
            ], 400);
        }

        // ✅ mark as scanned
        $invite->scan_status = 1;
        $invite->save();

        // ✅ Format phone number
        $phone = preg_replace('/\D/', '', $invite->contact);
        if (substr($phone, 0, 1) === "0") {
            $phone = "94" . substr($phone, 1);
        }

        // ✅ Message with table number
        $message = urlencode("
🎉 You’ve successfully checked in to the Ramadia Grand Opening Ceremony!
🪑 Your table number is *{$invite->table_no}*
✨ We wish you a wonderful evening — please enjoy the celebration! ✨
    ");

        $whatsappUrl = "https://wa.me/{$phone}?text={$message}";

        return response()->json([
            'status' => 'success',
            'message' => 'Valid ticket',
            'whatsapp_url' => $whatsappUrl,
            'data' => [
                'name' => $invite->name ?? 'Guest',
                'company' => $invite->company,
                'number_of_invites' => $invite->number_of_invites,
                'table' => $invite->table_no,
                'contact' => $invite->contact,
                'status' => $invite->scan_status
            ]
        ], 200);
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
