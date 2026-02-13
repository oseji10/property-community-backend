<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admission Letter</title>
  <style>
    body {
      font-family: "Times New Roman", serif;
      font-size: 13px;
      line-height: 1.5;
      margin: 0;
      padding: 20px;
      position: relative;
      background: #fff;
    }
 
    /* Watermark */
    body::before {
      content: "";
      position: absolute;
      top: 50%;
      left: 50%;
      width: 500px;
      height: 500px;
      background: url('{{ $logo }}') no-repeat center center;
      background-size: 60%;
      opacity: 0.05;
      transform: translate(-50%, -50%);
      z-index: 0;
    }

    .container {
      position: relative;
      z-index: 1;
      max-width: 700px;
      margin: 0 auto;
      padding: 30px;
      border: 1px solid #ccc;
    }

    .header {
      text-align: center;
      /* border-bottom: 2px solid #003087; */
      padding-bottom: 5px;
      margin-bottom: 10px;
      margin-top: -20px;
    }

    .header img {
      max-width: 100px;
      display: block;
      margin: 0 auto;
    }

    .header h2 {
      font-size: 18px;
      margin: 5px 0;
      text-transform: uppercase;
    }

    .header h3 {
      font-size: 14px;
      margin: 2px 0;
      font-weight: normal;
    }

    .date {
      text-align: right;
      margin-bottom: 15px;
    }

    .content p {
      margin: 8px 0;
    }

    .content h3 {
      font-size: 14px;
      margin-top: 15px;
      text-decoration: underline;
    }

    ul {
      list-style: none;
      padding-left: 0;
    }

    ul li {
      margin-bottom: 5px;
    }

    .footer {
      margin-top: 30px;
    }

    .footer img {
      width: 100px;
    }

    .signature {
      margin-top: 30px;
      line-height: 1.2;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <img src="{{ $logo }}" alt="College Logo">
      <!-- <h2>FCT College of Nursing Sciences, Gwagwalada – Abuja</h2>
      <h3>National Diploma in Nursing Programme</h3> -->
    </div>

    <div class="date">
      <p><strong>Date:</strong> {{ now()->format('jS F, Y') }}</p>
    </div>

    <div class="content">
      <p><strong>Name:</strong> {{ $student_name }}</p>
      <p><strong>Application Number:</strong> {{ $application_number }}</p>

      <h3>OFFER OF PROVISIONAL ADMISSION FOR {{ $academic_year }} SESSION</h3>

      <p>
        I am pleased to inform you that you have been offered provisional admission into 
        <strong> FCT College of Nursing Sciences, Gwagwalada – Abuja</strong> 
        for the <strong>{{$program_name}}</strong>.
      </p>

      <ul>
        <li><strong>Department:</strong> Nursing</li>
        <li><strong>Duration of Course:</strong> 2 Years</li>
      </ul>

      <h3>Acceptance of Offer</h3>
      <p>This offer is subject to the fulfillment of the following conditions:</p>
      <ul>
        <li><strong>1.</strong> Obtaining the minimum entry qualification (WAEC, GCE, NECO etc.) for your course of study as earlier specified by the College.</li>
        <li><strong>2.</strong> Report to the Provost, FCT College of Nursing Sciences, Gwagwalada, Abuja for registration with original copies of your credentials and evidence of payment of a non-refundable acceptance fee of <strong> N50,000.00</strong> and school fee of <strong>N25,000.00</strong> only through Remita.</li>
        <li><strong>3.</strong> The provisional admission shall only be valid after due interaction, further screening by the College officials, and medical check by the College authority at resumption.</li>
        <li><strong>4.</strong> Provide a reference letter from a reputable public figure to vouch for your good conduct.</li>
      </ul>

      <p>
        <strong>5. </strong>Registration/Resumption starts from <strong>{{ $start_date }}</strong>.<br>
        <strong>6. </strong>Orientation/Lectures commence on <strong>{{ $orientation_date }}</strong>.<br>
        <strong>7. </strong>Failure to resume two weeks after resumption ({{$forfeiture_date}}) means forfeiture of the admission.
      </p>

      <p>
        <strong>8. </strong>Do note that accommodation is based on first-come, first-served basis. At resumption, candidates shall be subjected to compulsory medical check at the College.
      </p>

      <p><strong>9. </strong>Accept my congratulations, please.</p>

      <div class="signature">
        <img src="{{ $registrar_signature }}" alt="Signature" width="10%"><br>
        <strong>Comr. Dr. Deborah J. Yusuf</strong><br>
        Provost,<br>
        College of Nursing Sciences
      </div>
    </div>
  </div>
</body>
</html>
