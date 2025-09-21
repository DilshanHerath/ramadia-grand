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
        // $invites = Invite::whereBetween('id', [719, 731])->get();
        $invites = Invite::where('id', 70000)->get();

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
                'status' => $invite->ticket_status
            ]
        ]);
    }

    public function getInviteByQrCode($qr_code)
    {
        Log::info('QR Code Scanned: ' . $qr_code);
        $invite = Invite::where('qr_code', $qr_code)->first();

        if (!$invite) {
            Log::error('Invalid QR Code: ' . $qr_code);
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid QR Code'
            ], 404);
        }

        if ($invite->ticket_status === 'Scanned') {
            Log::warning('Ticket already used: ' . $qr_code);
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket already used!'
            ], 400);
        }

        $invite->ticket_status = 'Scanned';
        $invite->save();

        Log::info('Ticket scanned successfully: ' . $qr_code);
        return response()->json([
            'status' => 'success',
            'message' => 'Valid ticket',
            'data' => [
                'name' => $invite->name ?? 'Guest',
                'company' => $invite->company,
                'number_of_invites' => $invite->number_of_invites,
                'table' => $invite->table_no,
                'contact' => $invite->contact,
                'status' => $invite->ticket_status
            ]
        ], 200);
    }
}
