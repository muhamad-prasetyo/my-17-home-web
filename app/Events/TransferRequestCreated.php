<?php

namespace App\Events;

use App\Models\TransferRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransferRequestCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TransferRequest $transferRequest;

    public function __construct(TransferRequest $transferRequest)
    {
        $this->transferRequest = $transferRequest;
    }
} 