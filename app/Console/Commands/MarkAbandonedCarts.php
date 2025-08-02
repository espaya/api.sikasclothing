<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Illuminate\Console\Command;

class MarkAbandonedCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mark-abandoned-carts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $abandoned = Cart::where('created_at', '<', now()->subDays(2))
            ->where('checkout_at') // optional
            ->get();
        
            foreach($abandoned as $item)
            {
                // send email notification
            }
    }
}
