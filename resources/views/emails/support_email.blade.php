<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harency--Support Email From App Form</title>
</head>
<body>
    <div class="wrap">
        User submitted an issue in App Support Form. Here are the details
        <h4>User Email: {{ $data['email'] }}</h4>
        <h4>Email Subject: {{ $data['subject'] }}</h4>
        <h4>Transaction ID: {{ $data['transaction_id'] ?? 'NA' }}</h4>
        <h4>Details: {{ $data['issue_details'] }}</h4>
        @if(isset($data['attachment']) && ($data['attachment'] != ""))
        <h4>Attachment: {{ public_path('/uploads/users') }}/{{ $data['attachment'] }}</h4>
        @endif
    </div>
</body>
</html>