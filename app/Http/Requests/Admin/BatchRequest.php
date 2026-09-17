<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\BatchStatus;
use App\Models\Batch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Creating or editing a batch.
 *
 * `slug` and `members_count` are absent on purpose: the slug is derived from
 * the SSC year and the count is a cache maintained by MemberObserver. Neither
 * is something an administrator should be able to post.
 */
class BatchRequest extends FormRequest
{
    /**
     * Authorization is the controller's, via BatchPolicy — a Batch Coordinator
     * may hold `batches.edit` and still be out of reach of this batch.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $batch = $this->route('batch');
        $id = $batch instanceof Batch ? $batch->id : null;

        return [
            'name' => ['required', 'string', 'max:80'],
            'name_bn' => ['nullable', 'string', 'max:80'],
            'ssc_year' => [
                'required',
                'integer',
                // The school opened in 1976, so the earliest SSC cohort is
                // roughly 1981. The upper bound tracks the calendar rather
                // than a hard-coded year.
                'min:1981',
                'max:'.((int) date('Y') + 1),
                Rule::unique('batches', 'ssc_year')->ignore($id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'description_bn' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', new Enum(BatchStatus::class)],
        ];
    }
}
