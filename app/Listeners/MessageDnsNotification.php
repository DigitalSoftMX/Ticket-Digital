<?php

namespace App\Listeners;

use App\Events\MessageDns;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class MessageDnsNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(MessageDns $event)
    {
        $station = $event->getStation();
        Mail::send('emails.dns', ['stn' => $station], function ($s) use ($station) {
            $s->from('contacto@digitalsoft.mx', 'Digitalsoft MX')
                ->to(['marbross72@hotmail.com', 'vicmanare@hotmail.com', 'zuri@digitalsoft.mx'])
                ->subject('Problema con servidor DNS');
        });
    }
}
