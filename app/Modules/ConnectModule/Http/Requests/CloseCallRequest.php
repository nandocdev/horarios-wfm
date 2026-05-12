<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ciscoIps = config('contact-center.cisco_webhook_ips', []);

        return empty($ciscoIps) || in_array($this->ip(), $ciscoIps, true);
    }

    public function rules(): array
    {
        return [
            'call_id' => ['required', 'string', 'exists:call_records,cisco_call_id'],
            'end_timestamp' => ['required', 'date_format:Y-m-d\TH:i:s\Z'],
            'call_status' => ['required', 'in:closed,transferred,abandoned'],
        ];
    }
}
