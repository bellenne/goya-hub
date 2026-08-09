<?php

namespace App\Http\Requests;

use App\Enums\SessionMusicSourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSessionMusicSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'source_type' => ['required', Rule::enum(SessionMusicSourceType::class)],
            'title' => ['nullable', 'string', 'max:255'],
            'track' => ['nullable', 'file'],
            'direct_url' => ['nullable', 'url', 'max:2048'],
            'youtube_url' => ['nullable', 'url', 'max:2048'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $sourceType = $this->input('source_type');

            if ($sourceType === SessionMusicSourceType::Uploaded->value && ! $this->hasFile('track')) {
                $validator->errors()->add('track', 'Audio file is required for uploaded source.');
            }

            if ($sourceType === SessionMusicSourceType::Uploaded->value && $this->hasFile('track')) {
                $extension = strtolower((string) $this->file('track')->getClientOriginalExtension());
                $allowedExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'webm'];

                if (! in_array($extension, $allowedExtensions, true)) {
                    $validator->errors()->add('track', 'Track must be an audio file: mp3, wav, ogg, m4a, aac, flac, or webm.');
                }
            }

            if ($sourceType === SessionMusicSourceType::DirectUrl->value && blank($this->input('direct_url'))) {
                $validator->errors()->add('direct_url', 'Direct audio URL is required.');
            }

            if ($sourceType === SessionMusicSourceType::Youtube->value && blank($this->input('youtube_url'))) {
                $validator->errors()->add('youtube_url', 'YouTube URL is required.');
            }

            if ($sourceType === SessionMusicSourceType::Youtube->value && filled($this->input('youtube_url'))) {
                $host = parse_url((string) $this->input('youtube_url'), PHP_URL_HOST);

                if (! str($host ?? '')->lower()->contains(['youtube.com', 'youtu.be'])) {
                    $validator->errors()->add('youtube_url', 'URL must point to YouTube.');
                }
            }
        });
    }
}
