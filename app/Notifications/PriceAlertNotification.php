<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PriceAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product,
        public string $type,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->line($this->message())
            ->line('Product: '.$this->product->name)
            ->line('Current price: ¥'.number_format($this->product->current_price))
            ->action('View product', route('products.show', $this->product->id));
    }

    private function subject(): string
    {
        return match ($this->type) {
            'price_drop' => 'Watchlist price drop alert',
            'price_change' => 'Watchlist price change alert',
            'new_product' => 'New product alert',
            default => 'Watchlist alert',
        };
    }

    private function message(): string
    {
        return match ($this->type) {
            'price_drop' => 'A watched product reached your target price.',
            'price_change' => 'A watched product changed by your configured threshold.',
            'new_product' => 'A new product matched your new product notification settings.',
            default => 'A watched product matched your notification settings.',
        };
    }
}
