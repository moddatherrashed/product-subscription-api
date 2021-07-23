<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class sendReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder email to the users before 5 days';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // get the users from the data base
        $to_name = 'Diomio';
        $to_email = 'moddather.developer@gmail.com';
        $data = array('boxSize' => '2', 'pizzas' => '2x Hwawi', 'interval' => '1. Montag (16:00 - 18:00)', 'price' => '23.00');
        Mail::send('emails.welcomeemail', $data, function ($message) use ($to_name, $to_email) {
            $message->to($to_email, $to_name)->subject('Willkommen in ABO MIO');
            $message->from('noreply@diomio.ch', 'ABO MIO');
        });
    }
}
