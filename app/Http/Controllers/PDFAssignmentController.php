<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Http\Request;
use PDF;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PDFAssignmentController extends Controller
{
    public function single($id)
    {
        $assignment = Assignment::with(['approver'])->find($id);

        if (!$assignment) {
            return redirect()->back()->with('error', 'Assignment not found.');
        }

        // Initialize qrCode variable as null.
        $qrCode = null;

        // Check if the assignment is approved. If so, generate the QR code.
        if ($assignment->approval_status === Assignment::STATUS_APPROVED) {
            $urlToXml = route('assignment.xml', ['id' => $assignment->id]);
            $qrCode = base64_encode(QrCode::format('png')->size(120)->generate($urlToXml));
        }

        // Pass all data, including the potentially null qrCode, to the view.
        $data = [
            'title'      => 'Assignment Report',
            'assignment' => $assignment,
            'qrCode'     => $qrCode
        ];

        $pdf = PDF::loadView('assignmentPDF', $data);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="assignment-'.$id.'.pdf"');
    }

    /**
     * This endpoint remains protected. It will only return XML for approved assignments.
     */
    public function generateXml($id)
    {
        $assignment = Assignment::find($id);

        if (!$assignment || $assignment->approval_status !== Assignment::STATUS_APPROVED) {
            return response('<error>Resource not found or not approved</error>', 404)->header('Content-Type', 'text/xml');
        }

        // (The rest of the XML generation logic remains the same)
        $xml = new \SimpleXMLElement('<spp/>');
        
        $formatState = function ($model, $field) {
            switch ($field) {
                case 'type':
                    return match ($model->type) {
                        Assignment::TYPE_FREE => 'Free',
                        Assignment::TYPE_PAID => 'Berbayar',
                        Assignment::TYPE_BARTER => 'Barter',
                        default => $model->type,
                    };
                case 'priority':
                    return match ($model->priority) {
                        Assignment::PRIORITY_NORMAL => 'Biasa',
                        Assignment::PRIORITY_IMPORTANT => 'Penting',
                        Assignment::PRIORITY_VERY_IMPORTANT => 'Sangat Penting',
                        default => 'Biasa',
                    };
                case 'approval_status':
                     return match ($model->approval_status) {
                        Assignment::STATUS_APPROVED => 'Disetujui',
                        Assignment::STATUS_PENDING => 'Menunggu',
                        Assignment::STATUS_DECLINED => 'Ditolak',
                        default => $model->approval_status,
                    };
                default:
                    return '';
            }
        };

        $xml->addChild('tanggal_dibuat', \Carbon\Carbon::parse($assignment->created_date)->format('d-m-Y'));
        $xml->addChild('jenis', $formatState($assignment, 'type'));
        $xml->addChild('klien', $assignment->client);
        $xml->addChild('nomor_spp', $assignment->spp_number);
        $xml->addChild('nomor_spk', $assignment->spk_number);
        $xml->addChild('invoice', 'Invoice Nomor I-132/SPP-D/XI/TVKU/2021/KEU/TVKU/XI/2021');
        $xml->addChild('keterangan', $assignment->description);
        $xml->addChild('nominal', 'Rp. ' . number_format($assignment->amount, 0, ',', '.'));
        $xml->addChild('beban_marketing', $assignment->marketing_expense ? 'Rp. ' . number_format($assignment->marketing_expense, 0, ',', '.') : '');
        $xml->addChild('deadline', \Carbon\Carbon::parse($assignment->deadline)->format('d F Y'));
        $xml->addChild('prioritas', $formatState($assignment, 'priority'));
        $xml->addChild('disetujui', $formatState($assignment, 'approval_status'));
        $xml->addChild('tanggal_disetujui', $assignment->approved_at ? \Carbon\Carbon::parse($assignment->approved_at)->format('d-m-Y') : '');

        return response($xml->asXML(), 200)->header('Content-Type', 'text/xml');
    }
}