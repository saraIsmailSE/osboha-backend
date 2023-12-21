@extends('certificate.layout')
<style>  .bold-title {
  
font-weight: 900;
  }
</style>

<div class="pageWithCertificate">
      <div class="page-content">
            <br />
            <p style="text-align: center">
                  <span style="font-size: 30pt; font-family: 'Arial', sans-serif">بسم الله الرحمن الرحيم
                  </span>
            </p>
            <p class="break-space">&nbsp;</p>
            <p>التاريخ: {{$date}}</p>

            <p class="main-header" style="text-align: center">
                  <u><span>توثيق إنجاز قارئ</span></u>
            </p>
            <br />
            <p style="text-align: center">
                  <strong>مستوى الكتاب: {{$level}} </strong>
            </p>
            <p>
                  <strong><span style="font-size: 13px"> </span></strong>
            </p>
            <p>
                  تثبت هذه الوثيقة من مشروع صناعة القرّاء (أصبوحة 180) بأن 
                                <u> <span class='bold-title'>{{$name}}</span></u>


                  قد حصل عليها بموجب المعايير المتبعة
                  قد طبق معايير القراءة المنهجية من خلال إنجازه قراءة كتاب
                                    <strong><span style="font-weight: bolder;"> {{$book}}</span></strong>


                  . ويثمن المشروع جهوده المبذولة وسعيه
                  الحثيث في زيادة حصيلته المعرفية، ورغبته باستمرارية التعلم عبر
                  القراءة المنهجية؛ ليبني صرحه المعرفيّ الذي يؤسّس لما بعده من
                  الأفعال، والتي تؤدي بدورها إلى النّهضة الحقيقيّة على المستوى الفردي
                  والجماعي، فينفع أمّته ويساهم في بناء نهضتها نحو القوّة والتطور
                  والرفعة.
            </p>
            <p class="break-space">&nbsp;</p>
            <p class="break-space">&nbsp;</p>
            <p>
                  علمًا أن مشروع أصبوحة 180 هو الحاضنة الأساسية للقرّاء، بصفته المشروع
                  الأكبر عربيّاً لصناعة الفكر، مُركزًا على عوامل الفكر عن طريق استثمار
                  القراءة المنهجيّة ونواتجها؛ لصناعة مجتمع واعٍ قادرٍ على الوصول
                  للنّهضة، وتحقيق التنمية مستعيناً بالتكنولوجيا الحديثة.
            </p>
      </div>
</div>
