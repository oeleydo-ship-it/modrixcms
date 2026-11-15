<?php

namespace Modrix\Newsletter\Http\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modrix\Newsletter\Models\Subscriber;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscribersController
{
    public function index(Request $request): Response
    {
        $status = $request->query('status', 'all');
        $search = $request->query('q');
        $tag = $request->query('tag');

        $subscribers = Subscriber::query()
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->where(fn ($sub) => $sub->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
            ->when($tag, fn ($q) => $q->whereJsonContains('tags', $tag))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Subscriber $s): array => [
                'id' => $s->id,
                'email' => $s->email,
                'name' => $s->name ?? '',
                'status' => $s->status,
                'tags' => $s->tags ?? [],
                'source' => $s->source ?? '',
                'joined' => $s->created_at?->diffForHumans(),
            ]);

        $counts = [
            'all' => Subscriber::query()->count(),
            'subscribed' => Subscriber::query()->where('status', 'subscribed')->count(),
            'pending' => Subscriber::query()->where('status', 'pending')->count(),
            'unsubscribed' => Subscriber::query()->where('status', 'unsubscribed')->count(),
        ];

        return Inertia::render('plugin:modrix/newsletter/SubscribersIndex', [
            'subscribers' => $subscribers,
            'counts' => $counts,
            'filters' => [
                'status' => $status ?: 'all',
                'q' => $search ?: '',
                'tag' => $tag ?: '',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:200', Rule::unique('nl_subscribers', 'email')],
            'name' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'string', 'max:300'],
            'status' => ['nullable', 'in:subscribed,pending,unsubscribed'],
        ]);

        $status = $data['status'] ?? 'subscribed';

        Subscriber::query()->create([
            'email' => strtolower(trim($data['email'])),
            'name' => $data['name'] ?? null,
            'status' => $status,
            'source' => 'admin',
            'tags' => $this->parseTags($data['tags'] ?? ''),
            'token' => Str::random(48),
            'confirmed_at' => $status === 'subscribed' ? now() : null,
            'unsubscribed_at' => $status === 'unsubscribed' ? now() : null,
        ]);

        return back()->with('status', 'Subscriber added.');
    }

    public function update(Request $request, Subscriber $subscriber): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:subscribed,pending,unsubscribed'],
            'tags' => ['nullable', 'string', 'max:300'],
        ]);

        $subscriber->update([
            'name' => $data['name'] ?? '',
            'status' => $data['status'],
            'tags' => $this->parseTags($data['tags'] ?? ''),
            'confirmed_at' => $data['status'] === 'subscribed' ? ($subscriber->confirmed_at ?? now()) : $subscriber->confirmed_at,
            'unsubscribed_at' => $data['status'] === 'unsubscribed' ? now() : null,
        ]);

        return back()->with('status', 'Subscriber updated.');
    }

    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bulk' => ['required', 'string', 'max:100000'],
            'tag' => ['nullable', 'string', 'max:80'],
        ]);

        $tag = ! empty($data['tag']) ? Str::slug($data['tag']) : null;
        $lines = preg_split('/\R/', trim($data['bulk'])) ?: [];
        $added = 0;

        foreach (array_slice($lines, 0, 2000) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', str_getcsv($line));
            $email = null;
            $name = null;

            foreach ($parts as $part) {
                if (filter_var($part, FILTER_VALIDATE_EMAIL)) {
                    $email = strtolower($part);
                } elseif ($name === null && $part !== '') {
                    $name = Str::limit($part, 120, '');
                }
            }

            if ($email === null) {
                continue;
            }

            $existing = Subscriber::query()->where('email', $email)->first();

            if ($existing === null) {
                Subscriber::query()->create([
                    'email' => $email,
                    'name' => $name,
                    'status' => 'subscribed',
                    'source' => 'import',
                    'tags' => $tag ? [$tag] : [],
                    'token' => Str::random(48),
                    'confirmed_at' => now(),
                ]);
                $added++;
            } elseif ($tag && ! in_array($tag, $existing->tags ?? [], true)) {
                $existing->update(['tags' => [...($existing->tags ?? []), $tag]]);
            }
        }

        return back()->with('status', "Imported {$added} new subscriber(s).");
    }

    public function export(Request $request): StreamedResponse
    {
        $status = $request->query('status');
        $rows = Subscriber::query()
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Email', 'Name', 'Status', 'Tags', 'Source', 'Subscribed At']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->email,
                    $this->safeCsvCell((string) ($row->name ?? '')),
                    $row->status,
                    $this->safeCsvCell(implode(', ', $row->tags ?? [])),
                    $this->safeCsvCell((string) ($row->source ?? '')),
                    $row->created_at?->toIso8601String() ?? '',
                ]);
            }

            fclose($handle);
        }, 'newsletter-subscribers.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(Subscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return back()->with('status', 'Subscriber deleted.');
    }

    /** @return array<int, string> */
    private function parseTags(string $raw): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($item) => Str::slug(trim($item)),
            explode(',', $raw),
        ))));
    }

    private function safeCsvCell(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
