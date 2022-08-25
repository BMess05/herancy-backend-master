<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harency--Sent Payment Request</title>
</head>
<body>
    <div class="wrap">
        Your request for payment request of amount {{ $data['amount'] }} from {{ $data['receiver_name'] }} is successfully sent.
        <h4>Requested by: {{ $data['sender_user']['name'] }}</h4>
        <h4>Request for sending money to: {{ $data['receiver_name'] }}</h4>
        <h4>Amount: {{ $data['amount'] }}</h4>
    </div>
</body>
</html>