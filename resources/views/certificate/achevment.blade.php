@extends('certificate.layout')
<div class="page">
    <div class="page-content">
        @if($index == 1)
        <br />
        <p class="main-header" style="text-align: center">
            <u>{{$mainTitle}}</u>
        </p>
        @endif
        <p class="break-space">&nbsp;</p>
        <p class="break-space">&nbsp;</p>

        <p class="sub-header"> {{ $subTitle}} {{ $index}}</p>
        <p class="mb-0 text-primary">
            التقييم || {{ $textDegree }}
        </p>
        <p class="break-space">&nbsp;</p>
        <p>
            {{ $achevmentText}}
        </p>
        <p class="break-space">&nbsp;</p>
@if(isset($quotes))
        <p class="sub-header">إجابة السؤال</p>
        <p class="break-space">&nbsp;</p>
            @foreach($quotes as $quote)
                <p class="quote" dir="rtl" style="font-size: 12pt; margin-right:2rem">
                    <strong  style="font-size: 15pt;">•</strong> {{ $quote->text }}
                </p>
            @endforeach

        @endif

    </div>
</div>
