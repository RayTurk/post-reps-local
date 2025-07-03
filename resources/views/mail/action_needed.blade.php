<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>

    <style>
        @import url(https://fonts.googleapis.com/css?family=Nunito);

        html {
            max-width: 100%;
            overflow-x: hidden;
        }

        body {
            max-width: 100%;
            overflow-x: hidden;
        }
        body {
            margin: 0;
            font-family: "Nunito", sans-serif;
            font-size: 0.9rem;
            font-weight: 400;
            line-height: 1.6;
            color: #212529;
            text-align: left;
            background-color: #f8fafc !important;
        }

        .container {
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
        }

        .text-orange {
            color: #FF9047;
        }

        .text-center {
            text-align: center !important;
        }

        .bg-white {
            background-color: #fff !important;
        }

        .p-3 {
            padding: 1rem !important;
        }

        .text-primary {
            color: #3490dc !important;
        }

        a.link {
            color: #3030b1;
            text-decoration: none;
            cursor: pointer;
        }

        .pt-5 {
            padding-top: 3rem !important;
        }

        .pb-5 {
            padding-bottom: 3rem !important;
        }

        .py-5 {
            padding: 3rem 3rem 3rem 3rem !important;
        }

        h3,
        h4 {
            font-weight: 700;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            margin-top: 0;
            margin-bottom: 0.5rem;
        }


        h4,
        .h4 {
            font-size: 1.35rem;
        }

        .container,
        .container-fluid,
        .container-xl,
        .container-lg,
        .container-md,
        .container-sm {
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
            margin-right: auto;
            margin-left: auto;
        }

    </style>
</head>

<body class="py-5" style="background-color: #f8fafc !important;">

    <h4 style="text-align:center; color:#FF9047;"><strong>URGENT! Action Needed - PostReps Order Incomplete </strong></h4>
    <div class="container p-3" style="background-color: #fff;">
        <p>
            Your order {{$orderNumber}} at {{$address}} is INCOMPLETE and will not be assigned to a route
            until you have completed the order. Often times this is due to payment not being processed correctly.
			Please check your order promptly to prevent any further delays in your service.<br>
            Login to <a href="{{ env('APP_URL') }}" class="link">www.postreps.com</a> at your
            earliest convenience and update your order so that we can get it scheduled.<br>
            If you are having issues completing your order, please contact our office at 208-546-5546.
        </p>

        <p>
            Regards,<br>
            The PostReps Team<br>
			208-546-5546
        </p>

        <hr>
        <p>This is an auto generated email, Do not try reply to this email, replies will not be received. </p>
    </div>

</body>

</html>
