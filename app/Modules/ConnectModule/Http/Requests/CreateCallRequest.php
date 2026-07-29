<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Http\Requests;

use App\Shared\Support\CallQueueCache;
use Illuminate\Foundation\Http\FormRequest;

class CreateCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ciscoIps = config('contact-center.cisco_webhook_ips', []);

        return empty($ciscoIps) || in_array($this->ip(), $ciscoIps, true);
    }

    public function rules(): array
    {
        $queues = app(CallQueueCache::class)->names();

        $queueRule = ['required', 'string'];
        if (! empty($queues)) {
            $queueRule[] = 'in:'.implode(',', $queues);
        }

        return [
            'call_id' => 'required|string|unique:call_records,cisco_call_id',
            'queue_name' => $queueRule,
            'ani' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'timestamp' => 'required|date_format:Y-m-d\TH:i:s\Z',
        ];
    }
}
