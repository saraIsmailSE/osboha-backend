@extends('certificate.layout')
<!-- ##### GENERAL INFORMATION ##### -->
<div class="page">
  <div class="page-content">
  <br/>
    <p class="main-header" style="text-align: center">
      <u><span>التلخيص العام</span></u>
    </p>
    <p class="mb-0 text-primary" style="line-height: 300%;">
      التقييم || {{ $textDegree }}
    </p>
    <p class="sub-header" style="line-height: 300%;">السؤال العام</p>
   <p style="font-size: 14pt;">
      {{ $certificate->general_question }}
    
  </p>
  <p class="sub-header" style="line-height: 300%;">التلخيص العام</p>
  <p style="font-size: 14pt;">
      {{ $summary }}
    </p>
  </div>
</div>
<!-- ##### END GRNRRAL INFORMATION ##### -->
