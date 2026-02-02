<?php

namespace App\Http\Controllers;

use App\Models\Applications;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use BaconQrCode\Renderer\Image\GdImageRenderer; // Explicitly use GD renderer
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\GdImageBackend;
class PDFController extends Controller
{
   public function generateExamSlip($applicationId)
    {
        // Fetch application data with batch information
        $application = Applications::with(['olevelresults', 'batch_relation', 'users', 'jamb', 'application_type'])->findOrFail($applicationId);


$qrContent = route('verify.slip', ['applicationId' => $application->applicationId]);

// Generate SVG QR code
$qrSvg = base64_encode(QrCode::format('svg')->size(150)->generate($qrContent));
$qrBase64 = 'data:image/svg+xml;base64,' . $qrSvg;

$imagePath = storage_path('app/public/images/cons_logo.png');
$imageData = base64_encode(file_get_contents($imagePath));
$imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
$base64Image = 'data:image/' . $imageType . ';base64,' . $imageData;


// Initialize variables with null/default values
$base64Image2 = null;

try {
    // Check if the photo exists and path is valid
    if ($application->photograph && $application->photograph->photoPath) {
        $imagePath2 = storage_path('app/public/' . ltrim($application->photograph->photoPath, '/'));
        
        // Verify file exists and is readable
        if (file_exists($imagePath2) && is_readable($imagePath2)) {
            $imageData2 = file_get_contents($imagePath2);
            
            if ($imageData2 !== false) {
                $imageType2 = strtolower(pathinfo($imagePath2, PATHINFO_EXTENSION));
                // Validate the image type
                if (in_array($imageType2, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $base64Image2 = 'data:image/' . $imageType2 . ';base64,' . base64_encode($imageData2);
                }
            }
        }
    }
} catch (Exception $e) {
   
    $base64Image2 = null;
}
        // Prepare data for the PDF
        $data = [
            'logo' => $base64Image,
            'qrCode' => $qrBase64,
            'fullname' => $application->users->firstName . ' ' .$application->users->lastName . ' ' . $application->users->otherNames,
            'email' => $application->users->email,
            'phoneNumber' => $application->users->phoneNumber,
            'applicationId' => $application->applicationId,
            // 'gender' => $application->gender,
            'gender' => $application->jamb->gender,
            'maritalStatus' => $application->maritalStatus,
            'dateOfBirth' => $application->dateOfBirth,
            'olevelResults' => $application->olevelresults,
            'photoPath' => $application->photograph ? Storage::url($application->photograph->photoPath) : null,
            'batchId' => $application->batch_relation ? $application->batch_relation->batchId : 'N/A',
            'batchName' => $application->batch_relation ? $application->batch_relation->batchName : 'N/A',
            'examDate' => $application->batch_relation ? $application->batch_relation->examDate : 'N/A',
            'examTime' => $application->batch_relation ? $application->batch_relation->examTime : 'N/A',
            'passport' => $base64Image2,
            'stateOfOrigin' => $application->jamb->state,
            'lga' => $application->jamb->lga,
            'jambId' => $application->jamb->jambId,
            'applicationType' => $application->application_type ? $application->application_type->applicationTypeName : 'N/A',
        ];

        // Load the Blade view and generate PDF
        $pdf = Pdf::loadView('pdf.exam-slip', $data)
            ->setPaper('a4')
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'Helvetica',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        // Return the PDF as a stream for download
        
        return $pdf->stream('exam-slip-' . $applicationId . '.pdf');
    }
}