<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Attendance Sheet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header h2 {
            margin: 5px 0 0 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
       th, td {
    border: 1px solid #000;
    padding: 4px 6px; /* reduced padding */
    text-align: left;
    line-height: 1.2; /* tighter line height */
}

.signature {
    height: 30px; /* reduced from 50px */
    margin-top: 5px; /* less spacing */
}

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        /* .signature {
            height: 50px;
            margin-top: 10px;
        } */
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 12px;
        }
         .watermark {
            position: absolute;
            top: 50%;
            left: -10%;
            transform: translate(-50%, -50%) rotate(-90deg);
            font-size: 60px;
            color: rgba(0, 48, 135, 0.08);
            z-index: -1;
            font-family: 'Arial', sans-serif;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
       
    <div class="header">
        <div class="watermark">2025 Application

        </div>
           <img src="{{ $logo }}" style="width: 150px;" alt="Institution Logo">


            <h1>FCT College of Nursing Sciences</h1>
        <h1>EXAMINATION ATTENDANCE SHEET</h1>
        <h2>{{ $batch->batchName }} ({{ $batch->batchId }})</h2>
        <p>Hall: {{ $hall->hallName }} | Date: {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <!-- <th width="5%">S/N</th> -->
                <th width="5%">Seat Number</th>
                <th width="15%">Application ID</th>
                <th width="20%">Candidate Name</th>
                <th width="10%">JAMB ID</th>
                <th width="10%">Phone Number</th>
                <th width="10%">Alt. Phone No.</th>
                <th width="15%">Signature</th>
                <!-- <th width="15%">Invigilator's Initial</th>
                <th width="15%">Remarks</th>
            </tr> -->
        </thead>
        <tbody>
            @foreach($records as $index => $record)
            <tr>
                <!-- <td>{{ $index + 1 }}</td> -->
                <td>{{ $record->seatNumber }}</td>
                <td>{{ $record->applicationId }}</td>
                <td>{{ $record->applications->users->firstName }} {{ $record->applications->users->lastName }}</td>
                <td>{{ $record->applications->jambId }}</td>
                <td>{{ $record->applications->users->phoneNumber }}</td>
                <td></td>
                <td>
                    <div class="signature"></div>
                </td>
                <!-- <td></td>
                <td></td> -->
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Printed on: {{ date('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>