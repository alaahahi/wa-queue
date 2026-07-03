<?php

namespace App\Enums;

enum QueueLogAction: string
{
    case Enqueued = 'enqueued';
    case Assigned = 'assigned';
    case StartedSending = 'started_sending';
    case ApiResponse = 'api_response';
    case Retry = 'retry';
    case Moved = 'moved';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
    case Failover = 'failover';
}
