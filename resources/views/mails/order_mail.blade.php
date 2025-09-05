<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        /* Reset styles for email compatibility */
        body,
        table,
        td,
        div,
        p {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.5;
        }

        body {
            background-color: #f5f5f5;
            padding: 20px 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            background-color: #f8f8f8;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #eeeeee;
        }

        .email-body {
            padding: 30px;
        }

        .order-complete {
            text-align: center;
            margin-bottom: 30px;
        }

        .order-complete svg {
            margin-bottom: 15px;
        }

        .order-complete h3 {
            font-size: 24px;
            color: #333333;
            margin-bottom: 10px;
        }

        .order-complete p {
            color: #666666;
            font-size: 16px;
        }

        .order-info {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }

        .order-info__item {
            display: table-row;
        }

        .order-info__item label,
        .order-info__item span {
            display: table-cell;
            padding: 10px 15px;
            border-bottom: 1px solid #eeeeee;
        }

        .order-info__item label {
            font-weight: bold;
            color: #333333;
            width: 40%;
        }

        .order-info__item span {
            color: #666666;
        }

        .checkout__totals-wrapper {
            margin-top: 30px;
        }

        .checkout__totals-wrapper h3 {
            font-size: 18px;
            color: #333333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eeeeee;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eeeeee;
        }

        th {
            font-weight: bold;
            color: #333333;
        }

        td {
            color: #666666;
        }

        .checkout-totals th {
            width: 30%;
        }

        .checkout-totals tr:last-child {
            border-top: 2px solid #eeeeee;
        }

        .checkout-totals tr:last-child th,
        .checkout-totals tr:last-child td {
            font-weight: bold;
            color: #333333;
            font-size: 18px;
        }

        .email-footer {
            background-color: #f8f8f8;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #999999;
            border-top: 1px solid #eeeeee;
        }

        /* Responsive styles */
        @media screen and (max-width: 600px) {
            .email-body {
                padding: 20px;
            }

            .order-info__item label,
            .order-info__item span {
                display: block;
                width: 100%;
                padding: 8px 0;
            }

            th,
            td {
                padding: 8px 5px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="email-header">
            <!-- Company Logo would go here -->
            <h1>Order Confirmation</h1>
        </div>

        <div class="email-body">
            <div class="order-complete">
                <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="40" cy="40" r="40" fill="#B9A16B"></circle>
                    <path d="M52.9743 35.7612C52.9743 35.3426 52.8069 34.9241 52.5056 34.6228L50.2288 32.346C49.9275 32.0446 49.5089 31.8772 49.0904 31.8772C48.6719 31.8772 48.2533 32.0446 47.952 32.346L36.9699 43.3449L32.048 38.4062C31.7467 38.1049 31.3281 37.9375 30.9096 37.9375C30.4911 37.9375 30.0725 38.1049 29.7712 38.4062L27.4944 40.683C27.1931 40.9844 27.0257 41.4029 27.0257 41.8214C27.0257 42.24 27.1931 42.6585 27.4944 42.9598L33.5547 49.0201L35.8315 51.2969C36.1328 51.5982 36.5513 51.7656 36.9699 51.7656C37.3884 51.7656 37.8069 51.5982 38.1083 51.2969L40.385 49.0201L52.5056 36.8996C52.8069 36.5982 52.9743 36.1797 52.9743 35.7612Z" fill="white"></path>
                </svg>
                <h3>Your order is completed!</h3>
                <p>Thank you. Your order has been received.</p>
            </div>

            <div class="order-info">
                <div class="order-info__item">
                    <label>Order Number</label>
                    <span>{{ $order->order_number }}</span>
                </div>
                <div class="order-info__item">
                    <label>Tracking Number</label>
                    <span>{{ $order->tracking_number }}</span>
                </div>
                <div class="order-info__item">
                    <label>Date</label>
                    <span>{{ \Carbon\Carbon::parse($order->created_at)->format('M j, Y') }}</span>
                </div>
                <div class="order-info__item">
                    <label>Total</label>
                    <span>${{ number_format($order->total_amount, 2) }}</span>
                </div>
                <div class="order-info__item">
                    <label>Payment Method</label>
                    <span>
                        @if($order->payment_method === 'direct_bank_transfer')
                        Direct Bank Transfer
                        @elseif($order->payment_method === 'check_payments')
                        Check Payment
                        @elseif($order->payment_method === 'cash_on_delivery')
                        Cash on Delivery
                        @elseif($order->payment_method === 'paypal')
                        PayPal
                        @else
                        {{ $order->payment_method }}
                        @endif
                    </span>
                </div>
                <div class="order-info__item">
                    <label>Payment Status</label>
                    <span>{{ ucfirst($order->payment_status) }}</span>
                </div>
                <div class="order-info__item">
                    <label>Order Status</label>
                    <span>{{ ucfirst($order->status) }}</span>
                </div>
            </div>

            <div class="checkout__totals-wrapper">
                <h3>Order Details</h3>
                <table class="checkout-cart-items">
                    <thead>
                        <tr>
                            <th>PRODUCT</th>
                            <th>QUANTITY</th>
                            <th>PRICE</th>
                            <th>SUBTOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orderItems as $item)
                        <tr>
                            <td>{{ $item->product->product_name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>${{ number_format($item->price, 2) }}</td>
                            <td>${{ number_format($item->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <table class="checkout-totals">
                    <tbody>
                        <tr>
                            <th>SUBTOTAL</th>
                            <td>${{ number_format($order->subtotal_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>SHIPPING</th>
                            <td>${{ number_format($order->shipping_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>TAX</th>
                            <td>${{ number_format($order->tax_amount, 2) }}</td>
                        </tr>
                        @if($order->discount_amount > 0)
                        <tr>
                            <th>DISCOUNT</th>
                            <td>-${{ number_format($order->discount_amount, 2) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>TOTAL</th>
                            <td>${{ number_format($order->total_amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if($order->notes)
            <div class="order-notes" style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border-radius: 5px;">
                <h4 style="margin-bottom: 10px; color: #333;">Order Notes:</h4>
                <p style="color: #666; margin: 0;">{{ $order->notes }}</p>
            </div>
            @endif
        </div>

        <div class="email-footer">
            <p>If you have any questions about your order, please contact our customer support.</p>
            <p>Thank you for shopping with us!</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>