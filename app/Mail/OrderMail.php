<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use App\Models\OrderItem;

class OrderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */

    public $order;
    public $orderItems;
    public $user;

    public function __construct($user, $order, $orderItems)
    {
        $this->user = $user;
        $this->order = $order;
        $this->orderItems = $orderItems;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Order is Completed!',
        );
    }

    public function build()
    {
        return $this->subject('Order Confirmation - #' . $this->order->tracking_number)
                    ->view('emails.order-confirmation')
                    ->with([
                        'order' => $this->order,
                        'orderItems' => $this->orderItems,
                        'user' => $this->user
                    ]);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.order_mail',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
