<?php

namespace App\Jobs;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerateInviteTicket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $invite;

    /**
     * Create a new job instance.
     */
    public function __construct($invite)
    {
        $this->invite = $invite;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Generate QR
        $uniqueId = "INVITE-{$this->invite->id}-" . uniqid();
        $qrPath = storage_path("app/public/qrcodes/{$this->invite->id}.png");

        QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->backgroundColor(255, 255, 255)
            ->generate($uniqueId, $qrPath);

        $this->invite->qr_code = $uniqueId;
        $this->invite->save();

        // Generate PDF ticket
        $pdf = Pdf::loadView('ticket', ['invite' => $this->invite])
            ->setPaper('a4', 'portrait');
        $nameForFile = $this->invite->name ?: 'Guest';
        $pdfPath = storage_path("app/public/tickets/{$nameForFile}.pdf");
        $pdf->save($pdfPath);

        // Update status
        $this->invite->ticket_status = 'Ticket-generated';
        $this->invite->save();
    }
}
