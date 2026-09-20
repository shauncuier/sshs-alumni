<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MessageTemplateRequest;
use App\Models\MessageTemplate;
use App\Support\Paginated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MessageTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $channel = $request->query('channel');

        $templates = MessageTemplate::query()
            ->when($channel, fn ($q) => $q->where('channel', $channel))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/templates/index', [
            'templates' => Paginated::from($templates, fn (MessageTemplate $t): array => [
                'id' => $t->id,
                'key' => $t->key,
                'name' => $t->name,
                'channel' => $t->channel->value,
                'subject' => $t->subject,
                'subject_bn' => $t->subject_bn,
                'body' => $t->body,
                'body_bn' => $t->body_bn,
                'variables' => $t->variables,
                'is_system' => $t->is_system,
            ]),
            'channels' => CampaignChannel::cases(),
            'filters' => [
                'channel' => $channel,
            ],
        ]);
    }

    public function store(MessageTemplateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (empty($validated['key'])) {
            $validated['key'] = Str::slug($validated['name']);
        }

        MessageTemplate::create($validated);

        return redirect()->route('admin.templates.index')
            ->with('success', 'Message template created successfully.');
    }

    public function update(MessageTemplateRequest $request, MessageTemplate $template): RedirectResponse
    {
        $validated = $request->validated();

        $template->update($validated);

        return redirect()->route('admin.templates.index')
            ->with('success', 'Message template updated successfully.');
    }

    public function destroy(MessageTemplate $template): RedirectResponse
    {
        if ($template->is_system) {
            abort(403, 'System templates cannot be deleted.');
        }

        $template->delete();

        return redirect()->route('admin.templates.index')
            ->with('success', 'Message template deleted.');
    }
}
