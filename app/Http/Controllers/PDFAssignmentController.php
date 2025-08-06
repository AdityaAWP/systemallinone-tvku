<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Http\Request;
use PDF;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;

class PDFAssignmentController extends Controller
{
    public function single($id)
    {
        $assignment = Assignment::with(['approver'])->find($id);

        if (!$assignment) {
            return redirect()->back()->with('error', 'Assignment not found.');
        }

        $qrCode = null;

        if ($assignment->approval_status === Assignment::STATUS_APPROVED) {
            $urlToXml = route('assignment.xml', ['id' => $assignment->id]);
            $qrCode = base64_encode(QrCode::format('png')->size(120)->generate($urlToXml));
        }

        $createdDate = Carbon::parse($assignment->created_date);
        $year = $createdDate->year;
        $monthRoman = $this->_numberToRoman($createdDate->month);

        $assignmentTypeString = $this->_formatState($assignment, 'type');
        $spp_ket = '/SPP-D/'; // Default
        if ($assignmentTypeString == 'Free') {
            $spp_ket = '/FREE/SPP-D/';
        } elseif ($assignmentTypeString == 'Barter') {
            $spp_ket = '/BARTER/SPP-D/';
        }
        $generatedSppNumber = $assignment->spp_number . $spp_ket . $monthRoman . '/TVKU/' . $year;

        $generatedSpkNumber = $assignment->spk_number . '/SPK/' . $monthRoman . '/TVKU/' . $year;

        $generatedInvoiceNumber = null;
        if ($assignmentTypeString == 'Berbayar') {
            $generatedInvoiceNumber = 'Invoice Nomor I-' . $assignment->spp_number . '/KEU/TVKU/' . $monthRoman . '/' . $year;
        }

        $data = [
            'title'                  => 'Assignment Report',
            'assignment'             => $assignment,
            'qrCode'                 => $qrCode,
            'generatedSppNumber'     => $generatedSppNumber,
            'generatedSpkNumber'     => $generatedSpkNumber,
            'generatedInvoiceNumber' => $generatedInvoiceNumber,
        ];

        $pdf = PDF::loadView('assignmentPDF', $data);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="assignment-'.$id.'.pdf"');
    }

    public function generateXml($id)
    {
        $assignment = Assignment::find($id);

        if (!$assignment || $assignment->approval_status !== Assignment::STATUS_APPROVED) {
            return response('<error>Resource not found or not approved</error>', 404)->header('Content-Type', 'text/xml');
        }

        $xml = new \SimpleXMLElement('<spp/>');

        $xml->addChild('tanggal_dibuat', Carbon::parse($assignment->created_date)->format('d-m-Y'));
        $xml->addChild('jenis', $this->_formatState($assignment, 'type'));
        $xml->addChild('klien', $assignment->client);
        $xml->addChild('nomor_spp', $assignment->spp_number);
        $xml->addChild('nomor_spk', $assignment->spk_number);
        $xml->addChild('invoice', 'Invoice Nomor I-132/SPP-D/XI/TVKU/2021/KEU/TVKU/XI/2021');
        $xml->addChild('keterangan', $assignment->description);
        $xml->addChild('nominal', 'Rp. ' . number_format($assignment->amount, 0, ',', '.'));
        $xml->addChild('beban_marketing', $assignment->marketing_expense ? 'Rp. ' . number_format($assignment->marketing_expense, 0, ',', '.') : '');
        $xml->addChild('deadline', Carbon::parse($assignment->deadline)->format('d F Y'));
        $xml->addChild('prioritas', $this->_formatState($assignment, 'priority'));
        $xml->addChild('disetujui', $this->_formatState($assignment, 'approval_status'));
        $xml->addChild('tanggal_disetujui', $assignment->approved_at ? Carbon::parse($assignment->approved_at)->format('d-m-Y') : '');

        return response($xml->asXML(), 200)->header('Content-Type', 'text/xml');
    }
    
    private function _formatState($model, $field)
    {
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
    }

    private function _numberToRoman($number)
    {
        $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
        $returnValue = '';
        while ($number > 0) {
            foreach ($map as $roman => $int) {
                if ($number >= $int) {
                    $number -= $int;
                    $returnValue .= $roman;
                    break;
                }
            }
        }
        return $returnValue;
    }
}