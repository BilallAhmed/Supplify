<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing('items');

        $mail = (new MailMessage)
            ->subject('Order '.$order->order_number.' received — '.config('app.name'))
            ->greeting('Thanks for your order')
            ->line('We received order **'.$order->order_number.'**.')
            ->line('Payment method: '.$order->payment_method->label().'.')
            ->line('Total: '.Money::format($order->total_cents, $order->currency).'.');

        foreach ($order->items as $item) {
            $mail->line($item->name.' × '.$item->quantity.' — '.Money::format($item->line_total_cents));
        }

        return $mail
            ->line('We will notify you when your order ships.')
            ->salutation('— '.config('app.name'));
    }
}
