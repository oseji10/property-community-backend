<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Examination Slip</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      font-size: 13px;
      color: #2d2d2d;
      margin: 0;
      padding: 25px;
      background-color: #f4f7fa;
    }

    .container {
      background: #fff;
      border: 2px solid #003087;
      border-radius: 14px;
      padding: 30px;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
      position: relative;
    }

    .header {
      text-align: center;
      border-bottom: 2px solid #003087;
      padding-bottom: 20px;
      margin-bottom: 25px;
    }

    .header img.logo {
      max-width: 140px;
    }

    .header h1 {
      font-size: 26px;
      color: #003087;
      margin: 10px 0 5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .header h2 {
      font-size: 18px;
      color: #444;
      margin: 0;
      font-weight: 500;
    }

    .exam-flex {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 20px;
      margin-bottom: 30px;
    }

    .exam-details {
      flex: 1;
      background: #f8fbff;
      border-left: 4px solid #003087;
      padding: 20px;
      border-radius: 8px;
      font-size: 16px;
    }

    .exam-details h3 {
      font-size: 18px;
      color: #003087;
      margin-bottom: 15px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .exam-details div {
      margin-bottom: 10px;
      font-size: 15px;
    }

    .exam-details strong {
      color: #003087;
    }

    .photo-placeholder {
      width: 140px;
      height: 160px;
      border: 2px solid #003087;
      border-radius: 10px;
      overflow: hidden;
      background-color: #f9f9f9;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      flex-shrink: 0;
    }

    .photo-placeholder img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .section {
      margin-bottom: 30px;
    }

    .section h3 {
      font-size: 18px;
      color: #003087;
      margin-bottom: 15px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .details-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;
    }

    .details-grid div {
      background: #f8fbff;
      padding: 12px 14px;
      border-radius: 6px;
      border: 1px solid #dce6f5;
    }

    .details-grid strong {
      color: #003087;
    }

    table.candidate-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    .candidate-table th {
      background-color: #003087;
      color: #fff;
      text-transform: uppercase;
      font-size: 16px;
    }

     table.olevel-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    .olevel-table th,
    .olevel-table td {
      border: 1px solid #dce6f5;
      padding: 10px;
      text-align: center;
    }

    .olevel-table th {
      background-color: #003087;
      color: #fff;
      text-transform: uppercase;
      font-size: 13px;
    }

    .olevel-table td {
      background-color: #fafafa;
      font-size: 13px;
    }

    .signature-section {
      margin-top: 40px;
      font-size: 14px;
    }

    .signature-section div {
      margin: 12px 0;
    }

    .line {
      display: inline-block;
      border-bottom: 1px solid #000;
      width: 220px;
      margin-left: 10px;
    }

    .footer {
      text-align: center;
      border-top: 2px solid #003087;
      margin-top: 25px;
      padding-top: 10px;
      font-size: 11px;
      color: #666;
      font-style: italic;
    }

    .watermark {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-25deg);
      font-size: 55px;
      font-weight: 700;
      color: rgba(0, 48, 135, 0.05);
      z-index: 0;
      text-transform: uppercase;
      letter-spacing: 2px;
      white-space: nowrap;
    }

    @media print {
      body {
        background: #fff;
        padding: 0;
      }
      .container {
        box-shadow: none;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="watermark">2025 Application</div>

    <div class="header">
      <img src="{{ $logo }}" class="logo" alt="Institution Logo">
      <h1>FCT College of Nursing Sciences</h1>
      <h2>2025 Application Examination Slip</h2>
    </div>

    <table width="100%" style="margin-bottom: 20px;">
      <tr>
        <td style="text-align: left; font-size: 14px;">
            <div class="exam-details">
        <h3>Examination Details</h3>
        <div style="font-weight: bold; font-size: 20px;"><strong>Application ID:</strong> {{ $applicationId }}</div>
        <div style="font-weight: bold; font-size: 20px;"><strong>JAMB ID:</strong> {{ $jambId ?? 'N/A' }}</div>
        <div style="font-weight: bold; font-size: 20px;"><strong>Batch ID:</strong> {{ $batchId }}</div>
        <div style="font-weight: bold; font-size: 20px;"><strong>Exam Date:</strong> {{ $examDate !== 'N/A' ? \Carbon\Carbon::parse($examDate)->format('l, jS F Y') : 'N/A' }}</div>
        <div style="font-weight: bold; font-size: 20px;"><strong>Exam Time:</strong> {{ $examTime ? \Carbon\Carbon::parse($examTime)->format('h:i A') : 'N/A' }}</div>
      </div>
        </td>
        <td style="text-align: right; font-size: 14px;">
      <div class="photo-placeholder">
  @if($passport)
    <img src="{{ $passport }}" alt="Candidate Photo">
  @else
    <span style="display:block; text-align:center; font-size:12px; color:#666; padding-top:65px;">
      No Photo
    </span>
  @endif
</div>

        </td>
      </tr>
    </table>
    <!-- <div class="exam-flex">
      <div class="exam-details">
        <h3>Examination Details</h3>
        <div><strong>Application ID:</strong> {{ $applicationId }}</div>
        <div><strong>JAMB ID:</strong> {{ $jambId ?? 'N/A' }}</div>
        <div><strong>Batch ID:</strong> {{ $batchId }}</div>
        <div><strong>Exam Date:</strong> {{ $examDate !== 'N/A' ? \Carbon\Carbon::parse($examDate)->format('l, jS F Y') : 'N/A' }}</div>
        <div><strong>Exam Time:</strong> {{ $examTime ? \Carbon\Carbon::parse($examTime)->format('h:i A') : 'N/A' }}</div>
      </div>

      <div class="photo-placeholder">
        <img src="{{ $passport }}" alt="Candidate Photo">
      </div>
    </div> -->

    <!-- <div class="section">
      <h3>Candidate Information</h3>
      <div class="details-grid">
        <div><strong>Full Name:</strong> {{ $fullname }}</div>
        <div><strong>Email:</strong> {{ $email }}</div>
        <div><strong>Phone:</strong> {{ $phoneNumber }}</div>
        <div><strong>Gender:</strong> {{ $gender }}</div>
        <div><strong>Marital Status:</strong> {{ $maritalStatus }}</div>
        <div><strong>Date of Birth:</strong> {{ $dateOfBirth }}</div>
        <div><strong>State of Origin:</strong> {{ $stateOfOrigin ?? 'N/A' }}</div>
      </div>
    </div> -->

    <table class="candidate-table">
      <tr>
          <td colspan="2" style="text-align: left; padding: 10px 0; color: #003087; ">
            <h3>CANDIDATE INFORMATION</h3>
          </td>
        </tr>
        <tr>
        <td style="width: 50%; padding-right: 10px; vertical-align: top;">
          <div class="section">
            <div class="details-grid">
              <div style="font-weight: bold; font-size: 20px;"><strong>Name:</strong> {{ $fullname ?? 'N/A' }}</div>
              <div style="font-weight: bold; font-size: 20px;"><strong>Email:</strong> {{ $email }}</div>
              <div style="font-weight: bold; font-size: 20px;"><strong>Date of Birth:</strong> {{ $dateOfBirth }}</div>
              <div style="font-weight: bold; font-size: 20px;"><strong>State of Origin:</strong> {{ $stateOfOrigin ?? 'N/A' }}</div>
                <div style="font-weight: bold; font-size: 20px;"><strong>Application Type:</strong> {{ $applicationType }}</div>
            </div>
          </div>
        </td>

        <td style="width: 50%; padding-right: 10px; vertical-align: top;">
          <div class="section">
            <!-- <h3>Next of Kin</h3> -->
            <div class="details-grid">
              
              <div style="font-weight: bold; font-size: 20px;"><strong>Phone:</strong> {{ $phoneNumber }}</div>
              <div style="font-weight: bold; font-size: 20px;"><strong>Gender:</strong> {{ $gender }}</div>
              <div style="font-weight: bold; font-size: 20px;"><strong>Marital Status:</strong> {{ $maritalStatus }}</div>
              <div style="font-weight: bold; font-size: 20px;"><strong>LGA:</strong> {{ $lga ?? 'N/A' }}</div>
            </div>
          </div>
        </td>
      </tr>
    </table>

    <div class="section">
      <h3>O'Level Results</h3>
      <table class="olevel-table">
        <thead>
          <tr>
            <th style="font-weight: bold; font-size: 20px;">Subject</th>
            <th style="font-weight: bold; font-size: 20px;">Grade</th>
            <th style="font-weight: bold; font-size: 20px;">Exam Year</th>
            <th style="font-weight: bold; font-size: 20px;">Exam Type</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($olevelResults as $result)
          <tr>
            <td style="font-weight: bold; font-size: 20px;">{{ $result->subject }}</td>
            <td style="font-weight: bold; font-size: 20px;">{{ $result->grade }}</td>
            <td style="font-weight: bold; font-size: 20px;">{{ $result->examYear }}</td>
            <td style="font-weight: bold; font-size: 20px;">{{ $result->examType }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="signature-section">
      
     
      
    </div>
    <table width="100%" style="margin-top: 20px;">
      <tr>
        <td style="text-align: left; font-size: 16px; color: #555;">
          <div>Hall Name <span class="line"></span></div>
        </td>
        <td style="text-align: right; font-size: 16px; color: #555;">
           <div>Seat Number <span class="line"></span></div>
        </td>
        <td style="text-align: right; font-size: 16px; color: #555;">
          <div>Sign <span class="line"></span></div>
        </td>
      </tr>
    </table>

    <table width="100%" style="margin-top: 40px;">
      <tr>
        <td style="text-align: left; font-size: 12px; color: #555;">
          <strong>Note:</strong> Please bring this slip to the examination venue. <b>Also, ensure you arrive at least 30 minutes before your scheduled exam time.</b>
          <!-- <strong>Note:</strong> Please bring this slip along with a valid ID to the examination center. -->
        </td>
        <td style="text-align: right; font-size: 12px; color: #555;">
          For inquiries, contact <strong>08082775076 (WhatsApp only)</strong>
        </td>
      </tr>
    </table>

    <div class="footer">
      Generated on {{ date('F j, Y') }}
    </div>
  </div>
</body>
</html>
