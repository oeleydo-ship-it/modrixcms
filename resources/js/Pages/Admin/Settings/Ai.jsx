import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/cms/admin/AdminLayout';
import { Check, ErrorFlash, Field } from '@/cms/admin/forms';
import SettingsTabs from './Tabs';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export default function Ai({ values, aiKeySaved, aiConfigured, aiProviders = {}, aiGeneratorUrl }) {
    const form = useForm({ ...values, ai_api_key: '', clear_ai_api_key: false });
    const [testing, setTesting] = useState(false);
    const [testResult, setTestResult] = useState(null);

    const providerKey = form.data.ai_provider || 'openai';
    const providerMeta = aiProviders[providerKey] ?? {
        label: 'OpenAI',
        default_model: 'gpt-4o-mini',
        base_url_placeholder: 'https://api.openai.com/v1',
    };

    const providerOptions = Object.fromEntries(
        Object.entries(aiProviders).map(([key, meta]) => [key, meta.label]),
    );

    const handleProviderChange = (e) => {
        const nextProvider = e.target.value;
        const previousDefault = aiProviders[providerKey]?.default_model;
        const nextDefault = aiProviders[nextProvider]?.default_model ?? '';

        setTestResult(null);
        form.setData((prev) => ({
            ...prev,
            ai_provider: nextProvider,
            ai_model: !prev.ai_model || prev.ai_model === previousDefault ? nextDefault : prev.ai_model,
        }));
    };

    const suggestedModels = Array.isArray(providerMeta.models) ? providerMeta.models : [];

    const handleTestConnection = async () => {
        setTesting(true);
        setTestResult(null);

        try {
            const response = await fetch(route('admin.settings.ai.test'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    ai_provider: form.data.ai_provider,
                    ai_api_key: form.data.ai_api_key || undefined,
                    ai_model: form.data.ai_model || undefined,
                    ai_base_url: form.data.ai_base_url || undefined,
                }),
            });

            const json = await response.json();

            if (!response.ok || json.ok === false) {
                throw new Error(json?.message || 'Connection test failed.');
            }

            setTestResult({ ok: true, message: json.message || 'Connection verified.' });
        } catch (err) {
            setTestResult({
                ok: false,
                message: err instanceof Error ? err.message : 'Unable to connect to AI provider.',
            });
        } finally {
            setTesting(false);
        }
    };

    return (
        <AdminLayout title="Settings" subtitle="Configure your AI provider for multi-page website generation.">
            <SettingsTabs />
            <ErrorFlash errors={form.errors} />

            <div className="grid-2" style={{ alignItems: 'start' }}>
                <form
                    className="panel"
                    onSubmit={(e) => {
                        e.preventDefault();
                        setTestResult(null);
                        form.put(route('admin.settings.ai.update'), {
                            preserveScroll: true,
                            onSuccess: () => form.setData((prev) => ({ ...prev, ai_api_key: '', clear_ai_api_key: false })),
                        });
                    }}
                >
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12, flexWrap: 'wrap', marginBottom: 4 }}>
                        <h2>AI API</h2>
                        <span className={`badge ${aiConfigured ? 'green' : 'gray'}`}>
                            {aiConfigured ? 'Configured' : 'Not configured'}
                        </span>
                    </div>
                    <p className="hint">Connect OpenAI, Anthropic (Claude), Google Gemini, DeepSeek or any OpenAI-compatible gateway.</p>

                    <Field form={form} name="ai_provider" label="AI provider">
                        <select id="field-ai_provider" name="ai_provider" value={providerKey} onChange={handleProviderChange}>
                            {Object.entries(providerOptions).map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </select>
                    </Field>

                    <Field
                        form={form}
                        name="ai_api_key"
                        label="API key"
                        type="password"
                        autoComplete="new-password"
                        placeholder={aiKeySaved ? '••••••••••••••••' : 'sk-...'}
                        help={aiKeySaved ? 'Saved encrypted at rest. Leave blank to keep existing key.' : 'Stored encrypted at rest.'}
                    />

                    {aiKeySaved ? (
                        <Check form={form} name="clear_ai_api_key" label="Remove saved API key" />
                    ) : null}

                    <div className="row">
                        <Field
                            form={form}
                            name="ai_model"
                            label="Model"
                            list="ai-model-suggestions"
                            placeholder={providerMeta.default_model}
                            help={
                                suggestedModels.length
                                    ? `Default: ${providerMeta.default_model} · Latest presets: ${suggestedModels.join(', ')}`
                                    : `Default: ${providerMeta.default_model}`
                            }
                        />
                        <Field
                            form={form}
                            name="ai_base_url"
                            label="Base URL (optional)"
                            type="url"
                            placeholder={providerMeta.base_url_placeholder}
                            help={providerKey === 'openai_compatible' ? 'Required for custom gateways (e.g. OpenRouter, Groq, Ollama).' : 'Only needed for custom proxies.'}
                        />
                    </div>

                    {suggestedModels.length ? (
                        <>
                            <datalist id="ai-model-suggestions">
                                {suggestedModels.map((modelId) => (
                                    <option key={modelId} value={modelId} />
                                ))}
                            </datalist>
                            <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap', marginTop: -6, marginBottom: 14 }}>
                                {suggestedModels.map((modelId) => (
                                    <button
                                        key={modelId}
                                        type="button"
                                        className={form.data.ai_model === modelId ? 'btn btn-sm' : 'btn btn-light btn-sm'}
                                        onClick={() => {
                                            setTestResult(null);
                                            form.setData('ai_model', modelId);
                                        }}
                                    >
                                        {modelId}
                                    </button>
                                ))}
                            </div>
                        </>
                    ) : null}

                    <div className="actions" style={{ marginTop: 8, gap: 8, flexWrap: 'wrap' }}>
                        <button className="btn" type="submit" disabled={form.processing}>
                            {form.processing ? 'Saving…' : 'Save AI settings'}
                        </button>
                    </div>
                </form>

                <div className="panel">
                    <h2>Test connection</h2>
                    <p className="hint">Verify the credentials and model entered on the left (or your saved key if the API key field is left blank).</p>

                    <div style={{ display: 'flex', flexDirection: 'column', gap: 6, marginBottom: 14, fontSize: 13 }}>
                        <div><strong>Provider:</strong> {providerMeta.label}</div>
                        <div><strong>Model:</strong> <code>{form.data.ai_model || providerMeta.default_model}</code></div>
                        {form.data.ai_base_url ? (
                            <div><strong>Base URL:</strong> <code>{form.data.ai_base_url}</code></div>
                        ) : null}
                        <div><strong>Key status:</strong> {form.data.ai_api_key ? 'Using key typed in form' : aiKeySaved ? 'Using saved encrypted key' : 'No key provided'}</div>
                    </div>

                    {testResult ? (
                        <div
                            className={`flash ${testResult.ok ? 'ok' : 'err'}`}
                            role={testResult.ok ? 'status' : 'alert'}
                            style={{ marginBottom: 14 }}
                        >
                            {testResult.message}
                        </div>
                    ) : null}

                    <div className="actions" style={{ gap: 8, flexWrap: 'wrap' }}>
                        <button
                            className="btn btn-light"
                            type="button"
                            onClick={handleTestConnection}
                            disabled={testing || form.processing}
                        >
                            {testing ? 'Testing connection…' : 'Test AI connection'}
                        </button>
                        {aiGeneratorUrl ? (
                            <Link className="btn btn-light" href={aiGeneratorUrl}>Open AI generator →</Link>
                        ) : null}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
