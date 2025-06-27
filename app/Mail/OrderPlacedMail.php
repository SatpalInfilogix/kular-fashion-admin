<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderPlacedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function build()
    {
        if (is_object($this->order) && isset($this->order->id)) {
            \Log::info('Building order mail for order ID: ' . $this->order->id);
        } else {
            \Log::warning('Order object is missing or invalid in OrderPlacedMail.', ['order' => $this->order]);
        }

        return $this->subject('Your Order Confirmation: ' . ($this->order->unique_order_id ?? ''))
                    ->view('emails.order_placed');
    }
}
