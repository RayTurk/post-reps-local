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

    <h4 style="text-align:center; color:#FF9047;"><strong>{{$order->title}}</strong></h4>
    <div class="container p-3" style="background-color: #fff;">
        <h4><strong>{{ $order->agent ? $order->agent->user->name : '' }}</strong></h4>
        <p>{{ $order->office ? $order->office->user->name : '' }}</p>
        <p>Notice for {{$order->type}} order #{{ $order->order_number }}
            at:
        </p>
        <h4 class="text-primary"><strong>{{ $order->address }}</strong></h4>

        <p>
            @if ($order->service_date_type == 1)
            <strong>Date of service</strong>: RUSH ORDER<br>
            @else
            <strong>Date of service</strong>: {{ $order->service_date->format('m/d/Y') }}<br>
            @endif
            <strong>Order status</strong>: {{ ucfirst($order->getStatus()) }} <br>
            <strong>Order total</strong>: ${{ number_format((float)$order->total, 2, '.', ''); }} <br>
            <strong>Order comments</strong>: {!!$order->comment!!} <br>
            <strong>Installer comments</strong>: {!!$order->installer_comments!!}
        </p>

        <p>
            DID YOU KNOW YOU HAVE ACCESS TO PHOTOS WHEN A JOB IS COMPLETE? For additional infomation, you can verify the status, look at photos, and other order details by clicking the link below. <br>
            <a href="{{ env('APP_URL') }}/order/status">Orders Page</a>
        </p>

        @if ($order->installer_status == 'complete')
            <p>
                WE WANT YOUR FEEDBACK! 
				Help us improve our services by telling us how we did.<br>
                <strong><a href="{{ env('APP_URL') }}/order/{{$order->feedback_link}}">LEAVE FEEBACK HERE</a></strong>
            </p>
        @endif

        <p>Regards,</p>
        <p>The PostReps Team</p>
		<p>(208)546-5546</p>

        <hr>
        <p>This is an auto generated email, Do not reply to this email, replies will not be received. If you need to
            contact us, please give us a call or visit us online at <a href="{{ env('APP_URL') }}"
                class="link">{{ env('APP_URL') }}</a>
        </p>
    </div>

</body>

</html>
