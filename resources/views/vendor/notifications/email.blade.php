@component('mail::message')
{{-- Greeting --}}
@if (! empty($greeting))
# مرحبا 👋🏻
@else
@if ($level === 'error')
# @lang('Whoops!')
@else
# مرحبا 👋🏻
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
@component('mail::button', ['url' => $actionUrl, 'color' => $color])
{{ $actionText }}
@endcomponent
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
@lang('تحية وتقدير منا'),<br>
طاقم أصبوحة 180
@endif

{{-- Subcopy --}}
@isset($actionText)
@slot('subcopy')
@lang(
    "إذا كنت تواجه مشكلة في النقر فوق الزر \":actionText\" , قم بنسخ الرابط أدناه\n".
    'وألصقه في المتصفح الخاص بك : ',
    [
        'actionText' => $actionText,
    ]
) <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
@endslot
@endisset
@endcomponent
