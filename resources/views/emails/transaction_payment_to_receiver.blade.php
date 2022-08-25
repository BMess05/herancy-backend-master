<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harency--Receipt of Payment</title>
</head>
<body>
    <div class="wrap">
        You have received a payment of amount {{ $data['amount'] }} from {{ $data['sender_user']['name'] }}.
        <h4>Payment by: {{ $data['sender_user']['name'] }}</h4>
        <h4>Request to: {{ $data['receiver_user']['name'] }}</h4>
        <h4>Amount: {{ $data['amount'] }}</h4>
    </div>
</body>
</html>